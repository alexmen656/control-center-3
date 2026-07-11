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

        header('Content-Type: ' . $mimeType);
        header('Content-Length: ' . filesize($filePath));
        header('Cache-Control: private, max-age=3600');
        header('X-Content-Type-Options: nosniff');

        readfile($filePath);
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
