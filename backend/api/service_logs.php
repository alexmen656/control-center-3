<?php
include '../head.php';

// Get the request method
$method = $_SERVER['REQUEST_METHOD'];

// Get request headers
$headers = getRequestHeaders();
$auth_token = isset($headers['Authorization']) ? $headers['Authorization'] : null;
$api_key = isset($headers['X-Api-Key']) ? $headers['X-Api-Key'] : null;

// Initialize response array
$response = ['success' => false];

// Function to validate API key
function validateApiKey($api_key) {
    if (!$api_key) return false;
    
    // Check if the API key exists in the database
    $api_key = escape_string($api_key);
    $result = query("SELECT * FROM api_keys WHERE api_key = '$api_key' AND active = 1");
    
    return mysqli_num_rows($result) > 0 ? fetch_assoc($result) : false;
}

// Function to generate UUID v4
function generateUUID() {
    return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
        mt_rand(0, 0xffff), mt_rand(0, 0xffff),
        mt_rand(0, 0xffff),
        mt_rand(0, 0x0fff) | 0x4000,
        mt_rand(0, 0x3fff) | 0x8000,
        mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
    );
}

// Handle GET request to fetch logs
if ($method === 'GET') {
    // Authenticate via token or API key
    $authenticated = false;
    $user_id = null;
    
    if ($auth_token) {
        // Validate user token
        $token = escape_string($auth_token);
        $userData = query("SELECT * FROM control_center_users WHERE loginToken='$token'");
        
        if (mysqli_num_rows($userData) == 1) {
            $userData = fetch_assoc($userData);
            $user_id = $userData['userID'];
            $authenticated = true;
        }
    } elseif ($api_key) {
        // Validate API key
        $key_data = validateApiKey($api_key);
        if ($key_data) {
            $authenticated = true;
            $user_id = $key_data['user_id'];
        }
    }
    
    if ($authenticated) {
        // Get query parameters
        $project_id = isset($_GET['project_id']) ? escape_string($_GET['project_id']) : null;
        $service = isset($_GET['service']) ? escape_string($_GET['service']) : null;
        $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 100;
        $offset = isset($_GET['offset']) ? intval($_GET['offset']) : 0;
        $type = isset($_GET['type']) ? escape_string($_GET['type']) : null;
        
        // Build query
        $sql = "SELECT * FROM control_center_services_logs WHERE 1=1";
        
        if ($project_id) {
            $sql .= " AND project_id='$project_id'";
        }
        
        if ($service) {
            $sql .= " AND service='$service'";
        }
        
        if ($type) {
            $sql .= " AND type='$type'";
        }
        
        $sql .= " ORDER BY timestamp DESC LIMIT $limit OFFSET $offset";
        
        $logs = query($sql);
        $result = [];
        
        while ($log = fetch_assoc($logs)) {
            // Parse JSON fields
            if ($log['data']) {
                $log['data'] = json_decode($log['data']);
            }
            if ($log['meta']) {
                $log['meta'] = json_decode($log['meta']);
            }
            $result[] = $log;
        }
        
        $response = [
            'success' => true,
            'data' => $result
        ];
    } else {
        $response = [
            'success' => false,
            'error' => 'Unauthorized'
        ];
        http_response_code(401);
    }
}

// Handle POST request to create new log entry
if ($method === 'POST') {
    // Get JSON data
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        $response = [
            'success' => false,
            'error' => 'Invalid JSON data'
        ];
        http_response_code(400);
    } else {
        // Try to authenticate via API key first
        $auth_successful = false;
        $user_id = null;
        
        if ($api_key) {
            $key_data = validateApiKey($api_key);
            if ($key_data) {
                $auth_successful = true;
                $user_id = $key_data['user_id'];
            }
        } elseif ($auth_token) {
            // Fall back to token authentication
            $token = escape_string($auth_token);
            $userData = query("SELECT * FROM control_center_users WHERE loginToken='$token'");
            
            if (mysqli_num_rows($userData) == 1) {
                $userData = fetch_assoc($userData);
                $user_id = $userData['userID'];
                $auth_successful = true;
            }
        }
        
        if ($auth_successful) {
            // Required fields
            $project_id = isset($input['project_id']) ? escape_string($input['project_id']) : null;
            $service = isset($input['service']) ? escape_string($input['service']) : null;
            $message = isset($input['message']) ? escape_string($input['message']) : null;
            
            // Optional fields with defaults
            $type = isset($input['type']) ? escape_string($input['type']) : 'info';
            $environment = isset($input['environment']) ? escape_string($input['environment']) : null;
            
            // JSON fields
            $data = isset($input['data']) ? json_encode($input['data']) : null;
            $meta = isset($input['meta']) ? json_encode($input['meta']) : null;
            
            // System fields
            $id = generateUUID();
            $ip_address = $_SERVER['REMOTE_ADDR'];
            
            // Validate required fields
            if (!$project_id || !$service || !$message) {
                $response = [
                    'success' => false,
                    'error' => 'Missing required fields: project_id, service, message'
                ];
                http_response_code(400);
            } else {
                // Validate log type
                $valid_types = ['info', 'warn', 'error', 'success'];
                if (!in_array($type, $valid_types)) {
                    $type = 'info';
                }
                
                // Insert log
                $sql = "INSERT INTO control_center_services_logs 
                        (id, project_id, api_key, service, message, type, data, environment, user_id, ip_address, meta) 
                        VALUES (
                            '$id', 
                            '$project_id', 
                            " . ($api_key ? "'$api_key'" : "NULL") . ", 
                            '$service', 
                            '$message', 
                            '$type', 
                            " . ($data ? "'$data'" : "NULL") . ", 
                            " . ($environment ? "'$environment'" : "NULL") . ", 
                            " . ($user_id ? "'$user_id'" : "NULL") . ", 
                            '$ip_address', 
                            " . ($meta ? "'$meta'" : "NULL") . "
                        )";
                
                $result = query($sql);
                
                if ($result) {
                    $response = [
                        'success' => true,
                        'data' => [
                            'id' => $id,
                            'timestamp' => date('Y-m-d H:i:s')
                        ]
                    ];
                } else {
                    $response = [
                        'success' => false,
                        'error' => 'Database error: ' . mysqli_error($conn)
                    ];
                    http_response_code(500);
                }
            }
        } else {
            $response = [
                'success' => false,
                'error' => 'Unauthorized'
            ];
            http_response_code(401);
        }
    }
}

// Return JSON response
header('Content-Type: application/json');
echo json_encode($response);