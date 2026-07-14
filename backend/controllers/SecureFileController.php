<?php

class SecureFileController
{
    const FILE_SIGNATURE_SECRET = 'cc_secure_file_sign_2026_secret_key';
    const BASE_PATH = '/var/www/api.fringelo.com/filesystem';
    const PROJECT_BASE_PATH = '/var/www/api.fringelo.com/project_filesystems';

    public function serve(Request $request, Response $response): void
    {
        $path = (string) $request->input('path', '');
        $expires = (int) $request->input('expires', 0);
        $signature = (string) $request->input('signature', '');
        $projectID = $request->input('project', null);

        if (empty($path)) {
            $this->sendError(400, 'No file path specified');
            return;
        }

        if (empty($expires) || empty($signature)) {
            $this->sendError(400, 'Missing signature or expiration');
            return;
        }

        $verification = $this->verifySignature($path, $expires, $signature, $projectID);
        if (!$verification['valid']) {
            $this->sendError(403, $verification['error']);
            return;
        }

        $safePath = str_replace(['../', '..\\'], '', $path);
        if ($safePath !== $path) {
            $this->sendError(400, 'Invalid file path');
            return;
        }

        $filePath = $this->getFilePath($safePath, $projectID);

        if (!file_exists($filePath)) {
            $this->sendError(404, 'File not found ');
            return;
        }

        if (!is_file($filePath)) {
            $this->sendError(400, 'Invalid file type');
            return;
        }

        $mimeType = mime_content_type($filePath);

        $allowedMimeTypes = [
            'image/jpeg',
            'image/jpg',
            'image/png',
            'image/gif',
            'image/webp',
            'image/svg+xml',
            'image/bmp',
            'application/pdf',
            'text/plain',
            'text/csv',
            'application/json',
            'video/mp4',
            'video/webm',
            'audio/mpeg',
            'audio/mp3',
            'audio/wav',
        ];

        if (!in_array($mimeType, $allowedMimeTypes)) {
            $this->sendError(400, 'File type not allowed');
            return;
        }

        $servedPath = $filePath;
        $width = (int) $request->input('w', 0);
        $resizableMimeTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp', 'image/bmp'];

        if ($width > 0 && in_array($mimeType, $resizableMimeTypes)) {
            $width = max(16, min($width, 1024));
            $cacheDir = ($projectID !== null)
                ? self::PROJECT_BASE_PATH . '/' . $projectID . '/.thumbnails'
                : self::BASE_PATH . '/.thumbnails';
            $thumbPath = $this->getOrCreateThumbnail($filePath, $mimeType, $width, $cacheDir);
            if ($thumbPath) {
                $servedPath = $thumbPath;
            }
        }

        header('Content-Type: ' . $mimeType);
        header('Content-Length: ' . filesize($servedPath));
        header('Cache-Control: private, max-age=3600');
        header('X-Content-Type-Options: nosniff');

        readfile($servedPath);
    }

    private function getOrCreateThumbnail($filePath, $mimeType, $width, $cacheDir)
    {
        $thumbPath = $cacheDir . '/' . md5($filePath) . '_w' . $width . '.' . $this->extensionForMime($mimeType);

        if (file_exists($thumbPath) && filemtime($thumbPath) >= filemtime($filePath)) {
            return $thumbPath;
        }

        $source = $this->createImageFromFile($filePath, $mimeType);
        if (!$source) {
            return null;
        }

        $srcWidth = imagesx($source);
        $srcHeight = imagesy($source);

        if ($srcWidth <= $width) {
            imagedestroy($source);
            return null;
        }

        $targetHeight = (int) round($srcHeight * ($width / $srcWidth));
        $thumb = imagecreatetruecolor($width, $targetHeight);

        if ($mimeType === 'image/png' || $mimeType === 'image/gif') {
            imagealphablending($thumb, false);
            imagesavealpha($thumb, true);
            $transparent = imagecolorallocatealpha($thumb, 0, 0, 0, 127);
            imagefilledrectangle($thumb, 0, 0, $width, $targetHeight, $transparent);
        }

        imagecopyresampled($thumb, $source, 0, 0, 0, 0, $width, $targetHeight, $srcWidth, $srcHeight);
        imagedestroy($source);

        if (!is_dir($cacheDir)) {
            @mkdir($cacheDir, 0777, true);
        }

        $saved = $this->saveImage($thumb, $thumbPath, $mimeType);
        imagedestroy($thumb);

        return $saved ? $thumbPath : null;
    }

    private function createImageFromFile($filePath, $mimeType)
    {
        switch ($mimeType) {
            case 'image/jpeg':
            case 'image/jpg':
                return @imagecreatefromjpeg($filePath);
            case 'image/png':
                return @imagecreatefrompng($filePath);
            case 'image/gif':
                return @imagecreatefromgif($filePath);
            case 'image/webp':
                return function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($filePath) : false;
            case 'image/bmp':
                return function_exists('imagecreatefrombmp') ? @imagecreatefrombmp($filePath) : false;
            default:
                return false;
        }
    }

    private function saveImage($image, $path, $mimeType)
    {
        switch ($mimeType) {
            case 'image/jpeg':
            case 'image/jpg':
                return imagejpeg($image, $path, 80);
            case 'image/png':
                return imagepng($image, $path, 6);
            case 'image/gif':
                return imagegif($image, $path);
            case 'image/webp':
                return function_exists('imagewebp') ? imagewebp($image, $path, 80) : false;
            case 'image/bmp':
                return function_exists('imagebmp') ? imagebmp($image, $path) : false;
            default:
                return false;
        }
    }

    private function extensionForMime($mimeType)
    {
        switch ($mimeType) {
            case 'image/jpeg':
            case 'image/jpg':
                return 'jpg';
            case 'image/png':
                return 'png';
            case 'image/gif':
                return 'gif';
            case 'image/webp':
                return 'webp';
            case 'image/bmp':
                return 'bmp';
            default:
                return 'bin';
        }
    }

    private function verifySignature($path, $expires, $signature, $projectID = null)
    {
        if (time() > $expires) {
            return ['valid' => false, 'error' => 'Signature expired'];
        }

        $data = $path . '|' . $expires;
        if ($projectID !== null) {
            $data .= '|' . $projectID;
        }

        $expectedSignature = hash_hmac('sha256', $data, self::FILE_SIGNATURE_SECRET);

        if (!hash_equals($expectedSignature, $signature)) {
            return ['valid' => false, 'error' => 'Invalid signature'];
        }

        return ['valid' => true];
    }

    private function getFilePath($path, $projectID = null)
    {
        if ($projectID !== null) {
            return self::PROJECT_BASE_PATH . '/' . $projectID . '/' . $path;
        }
        return self::BASE_PATH . '/' . $path;
    }

    private function sendError($code, $message)
    {
        http_response_code($code);
        header('Content-Type: application/json');
        echo json_encode([
            'error' => true,
            'message' => $message,
            'code' => $code
        ]);
    }
}
