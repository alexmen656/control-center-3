<?php
// CMS API v1 - User Management Endpoint
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Authorization, Content-Type');

include 'head.php';

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// API Key validation
function validateApiKey($apiKey) {
    if (!$apiKey || !str_starts_with($apiKey, 'cms_')) {
        return false;
    }
    
    // Check if API key exists and is active
    $subscription = query("SELECT * FROM project_api_subscriptions WHERE api_key='$apiKey' AND is_enabled=1");
    return mysqli_num_rows($subscription) > 0;
}

// Rate limiting check
function checkRateLimit($apiKey) {
    // Simple rate limiting implementation
    $subscription = fetch_assoc(query("SELECT * FROM project_api_subscriptions WHERE api_key='$apiKey'"));
    if (!$subscription) return false;
    
    // Check requests in last minute
    $oneMinuteAgo = date('Y-m-d H:i:s', time() - 60);
    $recentRequests = query("
        SELECT COUNT(*) as count 
        FROM api_usage_logs 
        WHERE subscription_id='{$subscription['id']}' 
        AND created_at >= '$oneMinuteAgo'
    ");
    
    $count = fetch_assoc($recentRequests)['count'];
    return $count < $subscription['rate_limit'];
}

// Log API usage
function logApiUsage($subscriptionId, $endpointId, $method, $path, $status, $responseTime) {
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
    
    query("INSERT INTO api_usage_logs (subscription_id, endpoint_id, request_method, request_path, response_status, response_time_ms, ip_address, user_agent) 
           VALUES ('$subscriptionId', '$endpointId', '$method', '$path', '$status', '$responseTime', '$ip', '$userAgent')");
}

// Get API key from header
$headers = getallheaders();
$apiKey = $headers['Authorization'] ?? $headers['authorization'] ?? '';

if (str_starts_with($apiKey, 'Bearer ')) {
    $apiKey = substr($apiKey, 7);
}

// Validate API key
if (!validateApiKey($apiKey)) {
    http_response_code(401);
    echo json_encode(['error' => 'Invalid or missing API key']);
    exit();
}

// Check rate limit
if (!checkRateLimit($apiKey)) {
    http_response_code(429);
    echo json_encode(['error' => 'Rate limit exceeded']);
    exit();
}

// Get subscription info for logging
$subscription = fetch_assoc(query("SELECT * FROM project_api_subscriptions WHERE api_key='$apiKey'"));
$subscriptionId = $subscription['id'];

$startTime = microtime(true);
$method = $_SERVER['REQUEST_METHOD'];
$path = $_SERVER['REQUEST_URI'];
$status = 200;

try {
    // Route handling
    $requestUri = $_SERVER['REQUEST_URI'];
    $pathInfo = parse_url($requestUri, PHP_URL_PATH);
    $pathParts = explode('/', trim($pathInfo, '/'));
    
    // Remove 'api/v1/users' from path to get the action
    $apiIndex = array_search('users', $pathParts);
    $action = $pathParts[$apiIndex + 1] ?? null;
    $userId = $pathParts[$apiIndex + 2] ?? null;

    switch ($method) {
        case 'GET':
            if ($userId) {
                // Get specific user
                $user = query("SELECT userID, firstName, lastName, email, accountStatus FROM control_center_users WHERE userID='$userId'");
                if (mysqli_num_rows($user) > 0) {
                    $userData = fetch_assoc($user);
                    echo json_encode(['user' => $userData]);
                } else {
                    $status = 404;
                    echo json_encode(['error' => 'User not found']);
                }
            } else {
                // Get all users with pagination
                $page = intval($_GET['page'] ?? 1);
                $limit = intval($_GET['limit'] ?? 10);
                $offset = ($page - 1) * $limit;
                
                $users = query("SELECT userID, firstName, lastName, email, accountStatus FROM control_center_users LIMIT $limit OFFSET $offset");
                $totalUsers = fetch_assoc(query("SELECT COUNT(*) as count FROM control_center_users"))['count'];
                
                $userList = [];
                foreach ($users as $user) {
                    $userList[] = $user;
                }
                
                echo json_encode([
                    'users' => $userList,
                    'total' => intval($totalUsers),
                    'page' => $page,
                    'limit' => $limit,
                    'pages' => ceil($totalUsers / $limit)
                ]);
            }
            break;
            
        case 'POST':
            // Create new user
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (!$input['email'] || !$input['firstName']) {
                $status = 400;
                echo json_encode(['error' => 'Email and first name are required']);
                break;
            }
            
            $email = escape_string($input['email']);
            $firstName = escape_string($input['firstName']);
            $lastName = escape_string($input['lastName'] ?? '');
            $password = password_hash('defaultpass123', PASSWORD_DEFAULT);
            
            $result = query("INSERT INTO control_center_users (email, firstName, lastName, password, accountStatus) 
                           VALUES ('$email', '$firstName', '$lastName', '$password', 'active')");
            
            if ($result) {
                $newUserId = mysqli_insert_id($GLOBALS['conn']);
                $status = 201;
                echo json_encode(['message' => 'User created successfully', 'user_id' => $newUserId]);
            } else {
                $status = 500;
                echo json_encode(['error' => 'Failed to create user']);
            }
            break;
            
        case 'PUT':
            // Update user
            if (!$userId) {
                $status = 400;
                echo json_encode(['error' => 'User ID is required']);
                break;
            }
            
            $input = json_decode(file_get_contents('php://input'), true);
            $updates = [];
            
            if (isset($input['firstName'])) {
                $updates[] = "firstName='" . escape_string($input['firstName']) . "'";
            }
            if (isset($input['lastName'])) {
                $updates[] = "lastName='" . escape_string($input['lastName']) . "'";
            }
            if (isset($input['email'])) {
                $updates[] = "email='" . escape_string($input['email']) . "'";
            }
            
            if (empty($updates)) {
                $status = 400;
                echo json_encode(['error' => 'No valid fields to update']);
                break;
            }
            
            $updateSql = "UPDATE control_center_users SET " . implode(', ', $updates) . " WHERE userID='$userId'";
            $result = query($updateSql);
            
            if ($result) {
                echo json_encode(['message' => 'User updated successfully']);
            } else {
                $status = 500;
                echo json_encode(['error' => 'Failed to update user']);
            }
            break;
            
        case 'DELETE':
            // Delete user
            if (!$userId) {
                $status = 400;
                echo json_encode(['error' => 'User ID is required']);
                break;
            }
            
            $result = query("DELETE FROM control_center_users WHERE userID='$userId'");
            
            if ($result) {
                echo json_encode(['message' => 'User deleted successfully']);
            } else {
                $status = 500;
                echo json_encode(['error' => 'Failed to delete user']);
            }
            break;
            
        default:
            $status = 405;
            echo json_encode(['error' => 'Method not allowed']);
            break;
    }
    
} catch (Exception $e) {
    $status = 500;
    echo json_encode(['error' => 'Internal server error']);
}

// Log the API usage
$endTime = microtime(true);
$responseTime = round(($endTime - $startTime) * 1000); // Convert to milliseconds

// For this example, we'll use endpoint_id = 1 (should be dynamic based on the actual endpoint)
logApiUsage($subscriptionId, 1, $method, $path, $status, $responseTime);

// Update usage count
query("UPDATE project_api_subscriptions SET usage_count = usage_count + 1, last_used = NOW() WHERE id='$subscriptionId'");

http_response_code($status);
?>
