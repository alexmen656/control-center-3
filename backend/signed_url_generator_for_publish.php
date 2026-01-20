<?php
define('FILE_SIGNATURE_SECRET', 'cc_secure_file_sign_2026_secret_key');
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

       // $path = str_replace('%2F', '/', $path);
       $path = urldecode($path);
       echo "Generating signed URL for path: $path\n";
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
        // Use PHP_QUERY_RFC3986 to encode spaces as %20 instead of +
        // This prevents urldecode() from incorrectly converting + to spaces
        $baseUrl = $this->getBaseUrl() . '/secure_file_provider.php';
        $signedUrl = $baseUrl . '?' . http_build_query($params, '', '&', PHP_QUERY_RFC3986);

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
        
        //return $protocol . '://' . $host . $scriptPath;
        return "https://alex.polan.sk/control-center";
    }
}
