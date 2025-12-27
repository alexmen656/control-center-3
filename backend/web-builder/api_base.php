<?php
/**
 * Web Builder API Base Handler
 * 
 * Provides common functionality for all Web Builder API endpoints
 * Uses Control Center authentication system
 */

// Enable CORS for all requests
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Credentials: true");

// Handle preflight OPTIONS requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Include Control Center dependencies
include_once __DIR__ . '/../jwt_helper.php';
include_once __DIR__ . '/../config.php';
include_once '/www/paxar/components/php_head.php';

/**
 * Send JSON response
 * 
 * @param mixed $data The data to send
 * @param int $statusCode HTTP status code
 * @return void
 */
function sendResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    echo json_encode($data);
    exit();
}

/**
 * Send error response
 * 
 * @param string $message Error message
 * @param int $statusCode HTTP status code
 * @return void
 */
function sendError($message, $statusCode = 400) {
    sendResponse(['error' => true, 'message' => $message], $statusCode);
}

/**
 * Send JSON response with status, message, and data
 * 
 * @param string $status Status of the response ('success' or 'error')
 * @param string $message Message to send
 * @param int $statusCode HTTP status code
 * @param mixed $data Optional data to include in the response
 * @return void
 */
function sendJsonResponse($status, $message, $statusCode = 200, $data = null) {
    $response = [
        'status' => $status,
        'message' => $message
    ];
    
    if ($data !== null) {
        $response['data'] = $data;
    }
    
    http_response_code($statusCode);
    echo json_encode($response);
    exit();
}

/**
 * Get JSON data from request body
 * 
 * @return array The decoded JSON data
 */
function getJsonData() {
    $json = file_get_contents('php://input');
    $data = json_decode($json, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        sendError("Invalid JSON data: " . json_last_error_msg(), 400);
    }
    
    return $data ?? [];
}

/**
 * Validate required fields in data
 * 
 * @param array $data The data to validate
 * @param array $requiredFields List of required field names
 * @return void
 */
function validateRequiredFields($data, $requiredFields) {
    $missingFields = [];
    
    foreach ($requiredFields as $field) {
        if (!isset($data[$field]) || (is_string($data[$field]) && trim($data[$field]) === '')) {
            $missingFields[] = $field;
        }
    }
    
    if (!empty($missingFields)) {
        sendError("Missing required fields: " . implode(', ', $missingFields), 400);
    }
}

/**
 * Authenticate user using Control Center JWT token
 * Returns user ID from token or sends error response
 * 
 * @return int The authenticated user ID
 */
function authenticateUser() {
    global $jwt_secret;
    
    $headers = getRequestHeaders();
    $token = $headers['Authorization'] ?? $headers['authorization'] ?? null;
    
    if (!$token) {
        sendError('Unauthorized: No token provided', 401);
    }
    
    // Remove "Bearer " prefix if present
    if (strpos($token, 'Bearer ') === 0) {
        $token = substr($token, 7);
    }
    
    // Verify JWT token
    $payload = SimpleJWT::verify($token, $jwt_secret);
    
    if (!$payload || empty($payload['sub'])) {
        sendError('Unauthorized: Invalid token', 401);
    }
    
    return intval($payload['sub']);
}

/**
 * Get user data from Control Center database
 * 
 * @param int $userId The user ID
 * @return array|null User data or null if not found
 */
function getUserData($userId) {
    $result = query("SELECT userID, email, firstname, lastname FROM control_center_users WHERE userID = " . intval($userId));
    
    if ($result && mysqli_num_rows($result) > 0) {
        return fetch_assoc($result);
    }
    
    return null;
}

/**
 * Verify user has access to a Control Center project
 * 
 * @param int $userId The user ID
 * @param string $projectId The Control Center project ID (projects.projectID)
 * @return bool True if user has access, false otherwise
 */
function userHasProjectAccess($userId, $projectId) {
    $projectId = escape_string($projectId);
    $userId = intval($userId);
    
    // Check if project exists
    $projectResult = query("SELECT projectID FROM projects WHERE projectID = '$projectId'");
    if (!$projectResult || mysqli_num_rows($projectResult) === 0) {
        return false;
    }
    
    // Check if user has access to this project
    $accessResult = query("SELECT userID FROM control_center_user_projects 
                          WHERE userID = $userId AND projectID = '$projectId'");
    
    return $accessResult && mysqli_num_rows($accessResult) > 0;
}

/**
 * Get Control Center project data
 * 
 * @param string $projectId The Control Center project ID
 * @return array|null Project data or null if not found
 */
function getControlCenterProject($projectId) {
    $projectId = escape_string($projectId);
    $result = query("SELECT projectID, name, link, icon FROM projects WHERE projectID = '$projectId'");
    
    if ($result && mysqli_num_rows($result) > 0) {
        return fetch_assoc($result);
    }
    
    return null;
}

/**
 * Get all Control Center projects for a user
 * 
 * @param int $userId The user ID
 * @return array List of projects the user has access to
 */
function getUserControlCenterProjects($userId) {
    $userId = intval($userId);
    $result = query("SELECT p.projectID, p.name, p.link, p.icon 
                    FROM projects p
                    INNER JOIN control_center_user_projects up ON p.projectID = up.projectID
                    WHERE up.userID = $userId
                    ORDER BY p.name ASC");
    
    $projects = [];
    if ($result) {
        while ($row = fetch_assoc($result)) {
            $projects[] = $row;
        }
    }
    
    return $projects;
}

/**
 * Debug logging for development
 * 
 * @param string $message The log message
 * @param mixed $data Optional data to log
 * @return void
 */
function debug_log($message, $data = null) {
    $logFile = __DIR__ . '/logs/debug.log';
    $dir = dirname($logFile);
    
    if (!file_exists($dir)) {
        mkdir($dir, 0777, true);
    }
    
    $timestamp = date('Y-m-d H:i:s');
    $logMessage = "[$timestamp] $message";
    
    if ($data !== null) {
        $logMessage .= ": " . json_encode($data);
    }
    
    file_put_contents($logFile, $logMessage . PHP_EOL, FILE_APPEND);
}
