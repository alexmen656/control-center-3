<?php
/**
 * API endpoint to get all push notification tokens
 * Used by the service monitor JavaScript to send direct Firebase notifications
 */
include '../head.php';

// Basic authentication for security
$AUTH_USERNAME = "service_monitor";
$AUTH_PASSWORD = "Mwgs78HJg12!3sKs";

// Get authorization headers
$headers = getRequestHeaders();
$auth_header = isset($headers['Authorization']) ? $headers['Authorization'] : '';

// Check if Basic Auth is provided
if (empty($auth_header) || !preg_match('/Basic\s+(.*)$/i', $auth_header, $matches)) {
    header('HTTP/1.0 401 Unauthorized');
    echo json_encode(['error' => 'Authentication required']);
    exit;
}

// Decode and verify credentials
$credentials = explode(':', base64_decode($matches[1]), 2);
if (count($credentials) != 2 || $credentials[0] !== $AUTH_USERNAME || $credentials[1] !== $AUTH_PASSWORD) {
    header('HTTP/1.0 401 Unauthorized');
    echo json_encode(['error' => 'Invalid credentials']);
    exit;
}

// Get all tokens from the database
$tokens_result = query("SELECT * FROM control_center_push_notifications_token");
$tokens = [];

while ($token_row = fetch_assoc($tokens_result)) {
    $tokens[] = $token_row['token'];
}

// Return the tokens as JSON
header('Content-Type: application/json');
echo json_encode([
    'success' => true,
    'tokens' => $tokens
]);
?>