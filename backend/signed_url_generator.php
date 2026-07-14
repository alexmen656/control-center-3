<?php
include 'head.php';
require_once __DIR__ . '/helpers/signed_url.php';

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
