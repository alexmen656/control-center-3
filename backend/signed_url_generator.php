<?php
/**
 * Signed URL Generator API
 * 
 * This endpoint generates signed URLs for secure file access.
 * Frontend requests signed URLs from this endpoint before accessing files.
 */

include 'head.php';

// Secret key for signing - must match secure_file_provider.php
define('FILE_SIGNATURE_SECRET', 'cc_secure_file_sign_2026_secret_key');

// Default signature validity in seconds (1 hour)
define('DEFAULT_VALIDITY', 3600);

class SignedUrlGenerator
{
    /**
     * Generate a signed URL for a file
     * 
     * @param string $path The file path relative to the base directory
     * @param int $validitySeconds How long the URL should be valid (default: 1 hour)
     * @param string|null $projectID Optional project ID for project-specific files
     * @return array Contains the signed URL and expiration time
     */
    public function generateSignedUrl($path, $validitySeconds = DEFAULT_VALIDITY, $projectID = null)
    {
        // Calculate expiration time
        $expires = time() + $validitySeconds;

        // Create signature data
        $data = $path . '|' . $expires;
        if ($projectID !== null) {
            $data .= '|' . $projectID;
        }

        // Generate HMAC signature
        $signature = hash_hmac('sha256', $data, FILE_SIGNATURE_SECRET);

        // Build URL parameters
        $params = [
            'path' => $path,
            'expires' => $expires,
            'signature' => $signature
        ];

        if ($projectID !== null) {
            $params['project'] = $projectID;
        }

        // Build the complete URL
        $baseUrl = $this->getBaseUrl() . '/secure_file_provider.php';
        $signedUrl = $baseUrl . '?' . http_build_query($params);

        return [
            'url' => $signedUrl,
            'expires' => $expires,
            'expiresIn' => $validitySeconds
        ];
    }

    /**
     * Generate multiple signed URLs at once
     * 
     * @param array $files Array of file objects with 'path' and optional 'projectID'
     * @param int $validitySeconds Validity duration for all URLs
     * @return array Array of signed URL objects
     */
    public function generateBulkSignedUrls($files, $validitySeconds = DEFAULT_VALIDITY)
    {
        $result = [];
        
        foreach ($files as $file) {
            $path = $file['path'] ?? $file['location'] ?? '';
            $projectID = $file['projectID'] ?? $file['project'] ?? null;
            
            if (empty($path)) {
                continue;
            }

            $signed = $this->generateSignedUrl($path, $validitySeconds, $projectID);
            $result[] = [
                'originalPath' => $path,
                'signedUrl' => $signed['url'],
                'expires' => $signed['expires'],
                'projectID' => $projectID
            ];
        }

        return $result;
    }

    /**
     * Get the base URL for the application
     */
    private function getBaseUrl()
    {
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $scriptPath = dirname($_SERVER['SCRIPT_NAME']);
        
        return $protocol . '://' . $host . $scriptPath;
    }
}

// Handle API requests
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

try {
    // Get JSON input
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('Invalid JSON input');
    }

    $generator = new SignedUrlGenerator();

    // Check if it's a bulk request or single request
    if (isset($input['files']) && is_array($input['files'])) {
        // Bulk request
        $validitySeconds = $input['validitySeconds'] ?? DEFAULT_VALIDITY;
        $result = $generator->generateBulkSignedUrls($input['files'], $validitySeconds);
        
        echo json_encode([
            'success' => true,
            'urls' => $result,
            'count' => count($result)
        ]);
    } else if (isset($input['path'])) {
        // Single request
        $path = $input['path'];
        $validitySeconds = $input['validitySeconds'] ?? DEFAULT_VALIDITY;
        $projectID = $input['projectID'] ?? $input['project'] ?? null;
        
        $result = $generator->generateSignedUrl($path, $validitySeconds, $projectID);
        
        echo json_encode([
            'success' => true,
            'url' => $result['url'],
            'expires' => $result['expires'],
            'expiresIn' => $result['expiresIn']
        ]);
    } else {
        throw new Exception('Missing required parameters: path or files');
    }

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
