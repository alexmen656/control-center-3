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

// Function to authenticate via token or API key
function authenticate() {
    global $auth_token, $api_key;
    $result = [
        'authenticated' => false,
        'user_id' => null
    ];
    
    if ($auth_token) {
        // Validate user token
        $token = escape_string($auth_token);
        $userData = query("SELECT * FROM control_center_users WHERE loginToken='$token'");
        
        if (mysqli_num_rows($userData) == 1) {
            $userData = fetch_assoc($userData);
            $result['user_id'] = $userData['userID'];
            $result['authenticated'] = true;
        }
    } elseif ($api_key) {
        // Validate API key
        $key_data = validateApiKey($api_key);
        if ($key_data) {
            $result['authenticated'] = true;
            $result['user_id'] = $key_data['user_id'];
        }
    }
    
    return $result;
}

// Function to get service ID from project_id and service_link
function getServiceId($project_id, $service_link) {
    $project_id = escape_string($project_id);
    $service_link = escape_string($service_link);
    
    $result = query("SELECT id FROM project_services WHERE projectID = '$project_id' AND link = '$service_link'");
    
    if (mysqli_num_rows($result) > 0) {
        $row = fetch_assoc($result);
        return $row['id'];
    }
    
    return null;
}

// Handle GET request to fetch service status history
if ($method === 'GET' && isset($_GET['action']) && $_GET['action'] === 'history') {
    $auth = authenticate();
    
    if ($auth['authenticated']) {
        // Get query parameters
        $project_id = isset($_GET['project_id']) ? escape_string($_GET['project_id']) : null;
        $service_link = isset($_GET['service']) ? escape_string($_GET['service']) : null;
        $days = isset($_GET['days']) ? intval($_GET['days']) : 7; // Default to last 7 days
        
        if (!$project_id || !$service_link) {
            $response = [
                'success' => false,
                'error' => 'Missing required parameters: project_id, service'
            ];
            http_response_code(400);
        } else {
            $service_id = getServiceId($project_id, $service_link);
            
            if (!$service_id) {
                $response = [
                    'success' => false,
                    'error' => 'Service not found'
                ];
                http_response_code(404);
            } else {
                // Calculate date range
                $end_date = date('Y-m-d H:i:s');
                $start_date = date('Y-m-d H:i:s', strtotime("-$days days"));
                
                // Get status history
                $sql = "SELECT * FROM service_status_history 
                        WHERE service_id = '$service_id' 
                        AND ((start_time BETWEEN '$start_date' AND '$end_date') 
                             OR (end_time IS NULL) 
                             OR (end_time BETWEEN '$start_date' AND '$end_date'))
                        ORDER BY start_time DESC";
                
                $history = query($sql);
                $result = [];
                
                while ($status = fetch_assoc($history)) {
                    $result[] = [
                        'id' => $status['id'],
                        'status' => $status['status'],
                        'start_time' => $status['start_time'],
                        'end_time' => $status['end_time']
                    ];
                }
                
                // Get the current status
                $current_status = 'down';
                $last_ping = null;
                
                $sql = "SELECT * FROM control_center_services_logs 
                        WHERE project_id = '$project_id' 
                        AND service = '$service_link'
                        ORDER BY timestamp DESC 
                        LIMIT 1";
                
                $last_log = query($sql);
                
                if (mysqli_num_rows($last_log) > 0) {
                    $log = fetch_assoc($last_log);
                    $last_ping = $log['timestamp'];
                    
                    // Check if last ping is within 30 minutes
                    $thirty_min_ago = date('Y-m-d H:i:s', strtotime('-30 minutes'));
                    $current_status = $last_ping > $thirty_min_ago ? 'up' : 'down';
                }
                
                $response = [
                    'success' => true,
                    'data' => [
                        'history' => $result,
                        'current_status' => $current_status,
                        'last_ping' => $last_ping
                    ]
                ];
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
// Handle POST request to update service status
elseif ($method === 'POST') {
    $auth = authenticate();
    
    if ($auth['authenticated']) {
        // Get JSON data
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!$input) {
            $response = [
                'success' => false,
                'error' => 'Invalid JSON data'
            ];
            http_response_code(400);
        } else {
            // Required fields
            $project_id = isset($input['project_id']) ? escape_string($input['project_id']) : null;
            $service_link = isset($input['service']) ? escape_string($input['service']) : null;
            
            if (!$project_id || !$service_link) {
                $response = [
                    'success' => false,
                    'error' => 'Missing required fields: project_id, service'
                ];
                http_response_code(400);
            } else {
                $service_id = getServiceId($project_id, $service_link);
                
                if (!$service_id) {
                    $response = [
                        'success' => false,
                        'error' => 'Service not found'
                    ];
                    http_response_code(404);
                } else {
                    // Update the last ping for this service
                    // We'll create a log entry to track the ping
                    $message = "Service status ping";
                    $type = "info";
                    $data = json_encode(['ping' => true]);
                    $ip_address = $_SERVER['REMOTE_ADDR'];
                    $id = sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
                        mt_rand(0, 0xffff), mt_rand(0, 0xffff),
                        mt_rand(0, 0xffff),
                        mt_rand(0, 0x0fff) | 0x4000,
                        mt_rand(0, 0x3fff) | 0x8000,
                        mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
                    );
                    
                    $sql = "INSERT INTO control_center_services_logs 
                            (id, project_id, api_key, service, message, type, data, user_id, ip_address) 
                            VALUES (
                                '$id', 
                                '$project_id', 
                                " . ($api_key ? "'$api_key'" : "NULL") . ", 
                                '$service_link', 
                                '$message', 
                                '$type', 
                                '$data', 
                                " . ($auth['user_id'] ? "'".$auth['user_id']."'" : "NULL") . ", 
                                '$ip_address'
                            )";
                    
                    $result = query($sql);
                    
                    if ($result) {
                        // Check current status
                        $sql = "SELECT * FROM service_status_history 
                                WHERE service_id = '$service_id' 
                                AND end_time IS NULL";
                                
                        $current_status = query($sql);
                        
                        if (mysqli_num_rows($current_status) === 0) {
                            // No open status record, create an 'up' status
                            $sql = "INSERT INTO service_status_history 
                                    (service_id, status, start_time) 
                                    VALUES ('$service_id', 'up', NOW())";
                            query($sql);
                        } else {
                            $status = fetch_assoc($current_status);
                            
                            // If current status is 'down', close it and create a new 'up' status
                            if ($status['status'] === 'down') {
                                query("UPDATE service_status_history SET end_time = NOW() WHERE id = '" . $status['id'] . "'");
                                query("INSERT INTO service_status_history (service_id, status, start_time) VALUES ('$service_id', 'up', NOW())");
                            }
                            // If it's already 'up', we don't need to change anything
                        }
                        
                        $response = [
                            'success' => true,
                            'data' => [
                                'message' => 'Service status updated',
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

// Return JSON response
header('Content-Type: application/json');
echo json_encode($response);