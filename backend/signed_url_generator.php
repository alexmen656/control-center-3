<?php
include 'head.php';

define('FILE_SIGNATURE_SECRET', 'cc_secure_file_sign_2026_secret_key');
define('DEFAULT_VALIDITY', 3600);

class SignedUrlGenerator
{
    public function generateSignedUrl($path, $validitySeconds = DEFAULT_VALIDITY, $projectID = null)
    {
        $expires = time() + $validitySeconds;
        $data = $path . '|' . $expires;

        if ($projectID !== null) {
            $data .= '|' . $projectID;
        }

        $signature = hash_hmac('sha256', $data, FILE_SIGNATURE_SECRET);

        $params = [
            'path' => $path,
            'expires' => $expires,
            'signature' => $signature
        ];

        if ($projectID !== null) {
            $params['project'] = $projectID;
        }

        $baseUrl = $this->getBaseUrl() . '/secure_file_provider.php';
        $signedUrl = $baseUrl . '?' . http_build_query($params);

        return [
            'url' => $signedUrl,
            'expires' => $expires,
            'expiresIn' => $validitySeconds
        ];
    }

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

    private function getBaseUrl()
    {
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $scriptPath = dirname($_SERVER['SCRIPT_NAME']);

        return $protocol . '://' . $host . $scriptPath;
    }
}

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
    $input = json_decode(file_get_contents('php://input'), true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('Invalid JSON input');
    }

    $generator = new SignedUrlGenerator();

    if (isset($input['files']) && is_array($input['files'])) {
        $validitySeconds = $input['validitySeconds'] ?? DEFAULT_VALIDITY;
        $result = $generator->generateBulkSignedUrls($input['files'], $validitySeconds);

        echo json_encode([
            'success' => true,
            'urls' => $result,
            'count' => count($result)
        ]);
    } else if (isset($input['path'])) {
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
