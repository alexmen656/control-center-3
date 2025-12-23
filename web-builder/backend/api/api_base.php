<?php
/**
 * API Base Handler
 * 
 * Provides common functionality for all API endpoints
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
 * Send JSON response with status, message, and data (Used in the newer API endpoints)
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
    
    return $data;
}

/**
 * Validate required fields in request data
 * 
 * @param array $data The request data
 * @param array $requiredFields List of required field names
 * @return bool True if all required fields are present
 */
function validateRequiredFields($data, $requiredFields) {
  //  print_r($data);
    foreach ($requiredFields as $field) {
        if (!isset($data[$field]) || empty($data[$field])) {
            sendError("Missing required field: $field", 400);
            return false;
        }
    }
    
    return true;
}