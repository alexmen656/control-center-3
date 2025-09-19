<?php
include '../head.php';

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

$method = $_SERVER['REQUEST_METHOD'];
$response = ['success' => false];

function generateApiKey()
{
    return bin2hex(random_bytes(32));
}

if ($method === 'GET') {
    $projectId = isset($_GET['project_id']) ? escape_string($_GET['project_id']) : null;

    if ($projectId) {
        $sql = "SELECT * FROM api_keys WHERE user_id = '$userID' AND project_id = '$projectId' ORDER BY created_at DESC";
        $result = query($sql);
        $apiKeys = [];

        while ($row = fetch_assoc($result)) {
            $fullKey = $row['api_key'];
            $keyLength = strlen($fullKey);
            $maskedKey = substr($fullKey, 0, 6) . '...' . substr($fullKey, $keyLength - 6, 6);

            $row['api_key'] = $maskedKey;
            if ($row['permissions']) {
                $row['permissions'] = json_decode($row['permissions']);
            }
            $apiKeys[] = $row;
        }

        $response = [
            'success' => true,
            'data' => $apiKeys
        ];
    } else {
        $response = [
            'success' => false,
            'error' => 'Missing project_id parameter'
        ];
        http_response_code(400);
    }
}

if ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);

    if ($input) {
        $projectId = isset($input['project_id']) ? escape_string($input['project_id']) : null;
        $name = isset($input['name']) ? escape_string($input['name']) : null;
        $description = isset($input['description']) ? escape_string($input['description']) : null;
        $expiresAt = isset($input['expires_at']) ? escape_string($input['expires_at']) : null;
        $permissions = isset($input['permissions']) ? json_encode($input['permissions']) : null;

        // Validate required fields
        if (!$projectId || !$name) {
            $response = [
                'success' => false,
                'error' => 'Missing required parameters: project_id, name'
            ];
            http_response_code(400);
        } else {
            // Check if the project exists and user has access to it
            $projectResult = query("SELECT * FROM projects WHERE projectID='$projectId'"); // AND owner='$userID'

            if (mysqli_num_rows($projectResult) > 0) {
                // Generate a unique API key
                $apiKey = generateApiKey();

                // Create SQL for insert
                $sql = "INSERT INTO api_keys (api_key, name, description, user_id, project_id, expires_at, permissions) 
                                VALUES ('$apiKey', '$name', " .
                    ($description ? "'$description'" : "NULL") .
                    ", '$userID', '$projectId', " .
                    ($expiresAt ? "'$expiresAt'" : "NULL") .
                    ", " . ($permissions ? "'$permissions'" : "NULL") . ")";

                $result = query($sql);

                if ($result) {
                    $response = [
                        'success' => true,
                        'data' => [
                            'id' => mysqli_insert_id($con),
                            'api_key' => $apiKey,
                            'name' => $name,
                            'created_at' => date('Y-m-d H:i:s')
                        ],
                        'message' => 'API key created successfully'
                    ];
                } else {
                    $response = [
                        'success' => false,
                        'error' => 'Database error: ' . mysqli_error($con)
                    ];
                    http_response_code(500);
                }
            } else {
                $response = [
                    'success' => false,
                    'error' => 'Project not found or you don\'t have permission'
                ];
                http_response_code(404);
            }
        }
    } else {
        $response = [
            'success' => false,
            'error' => 'Invalid request data'
        ];
        http_response_code(400);
    }
}

// Handle DELETE request to revoke/delete an API key
if ($method === 'DELETE') {
    $keyId = isset($_GET['id']) ? intval($_GET['id']) : null;

    if ($keyId) {
        // Check if the key belongs to the user
        $keyResult = query("SELECT * FROM api_keys WHERE id='$keyId' AND user_id='$userID'");

        if (mysqli_num_rows($keyResult) > 0) {
            $sql = "UPDATE api_keys SET active = 0 WHERE id = '$keyId'";
            $result = query($sql);

            if ($result) {
                $response = [
                    'success' => true,
                    'message' => 'API key revoked successfully'
                ];
            } else {
                $response = [
                    'success' => false,
                    'error' => 'Database error: ' . mysqli_error($con)
                ];
                http_response_code(500);
            }
        } else {
            $response = [
                'success' => false,
                'error' => 'API key not found or you don\'t have permission'
            ];
            http_response_code(404);
        }
    } else {
        $response = [
            'success' => false,
            'error' => 'Missing id parameter'
        ];
        http_response_code(400);
    }
}

header('Content-Type: application/json');
echo json_encode($response);
