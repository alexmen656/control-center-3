<?php
/**
 * Secure File Provider with Signature Authentication
 * 
 * This file validates signed URLs and serves files only if the signature is valid.
 * Signatures prevent unauthorized access to protected files.
 */

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Secret key for signing - should match the one used in the frontend
define('FILE_SIGNATURE_SECRET', 'cc_secure_file_sign_2026_secret_key');

// Signature validity duration in seconds (default: 1 hour)
define('SIGNATURE_VALIDITY', 3600);

class SecureFileProvider
{
    private const BASE_PATH = '/data/filesystem';
    private const PROJECT_BASE_PATH = '/data/project_filesystems';

    /**
     * Verify the signature of the request
     */
    private function verifySignature($path, $expires, $signature, $projectID = null)
    {
        // Check if signature has expired
        if (time() > $expires) {
            return ['valid' => false, 'error' => 'Signature expired'];
        }

        // Generate expected signature
        $data = $path . '|' . $expires;
        if ($projectID !== null) {
            $data .= '|' . $projectID;
        }
        
        $expectedSignature = hash_hmac('sha256', $data, FILE_SIGNATURE_SECRET);

        // Compare signatures (timing-safe comparison)
        if (!hash_equals($expectedSignature, $signature)) {
            return ['valid' => false, 'error' => 'Invalid signature'];
        }

        return ['valid' => true];
    }

    /**
     * Get the full file path based on path and project ID
     */
    private function getFilePath($path, $projectID = null)
    {
        if ($projectID !== null) {
            return self::PROJECT_BASE_PATH . '/' . $projectID . '/' . $path;
        } else {
            return self::BASE_PATH . '/' . $path;
        }
    }

    /**
     * Serve the file if all validations pass
     */
    public function serveFile()
    {
        // Get parameters
        $path = $_GET['path'] ?? '';
        $expires = $_GET['expires'] ?? 0;
        $signature = $_GET['signature'] ?? '';
        $projectID = isset($_GET['project']) ? $_GET['project'] : null;

        // Validate required parameters
        if (empty($path)) {
            $this->sendError(400, 'No file path specified');
            return;
        }

        if (empty($expires) || empty($signature)) {
            $this->sendError(400, 'Missing signature or expiration');
            return;
        }

        // Verify signature
        $verification = $this->verifySignature($path, $expires, $signature, $projectID);
        if (!$verification['valid']) {
            $this->sendError(403, $verification['error']);
            return;
        }

        // Prevent directory traversal attacks
        $safePath = str_replace(['../', '..\\'], '', $path);
        if ($safePath !== $path) {
            $this->sendError(400, 'Invalid file path');
            return;
        }

        // Get full file path
        $filePath = $this->getFilePath($safePath, $projectID);

        // Check if file exists
        if (!file_exists($filePath)) {
            $this->sendError(404, 'File not found');
            return;
        }

        // Check if it's a file (not a directory)
        if (!is_file($filePath)) {
            $this->sendError(400, 'Invalid file type');
            return;
        }

        // Get mime type
        $mimeType = mime_content_type($filePath);
        
        // Whitelist allowed mime types for security
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

        // Serve the file
        header('Content-Type: ' . $mimeType);
        header('Content-Length: ' . filesize($filePath));
        header('Cache-Control: private, max-age=3600');
        header('X-Content-Type-Options: nosniff');
        
        // Optional: Add content disposition for downloads
        // header('Content-Disposition: inline; filename="' . basename($filePath) . '"');
        
        readfile($filePath);
        exit;
    }

    /**
     * Send error response
     */
    private function sendError($code, $message)
    {
        http_response_code($code);
        header('Content-Type: application/json');
        echo json_encode([
            'error' => true,
            'message' => $message,
            'code' => $code
        ]);
        exit;
    }
}

// Initialize and serve file
$provider = new SecureFileProvider();
$provider->serveFile();
