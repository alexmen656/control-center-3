<?php
/**
 * API endpoint for managing service notification history
 * Used by the service monitor to avoid sending duplicate notifications
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

// Initialize response
$response = ['success' => false];

// Handle GET request - Get notification history for a service
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if (isset($_GET['service_id']) && isset($_GET['project_id'])) {
        $service_id = escape_string($_GET['service_id']);
        $project_id = escape_string($_GET['project_id']);
        
        $result = query("SELECT * FROM service_notification_history 
                        WHERE service_id = '$service_id' 
                        AND project_id = '$project_id'");
        
        if (mysqli_num_rows($result) > 0) {
            $history = fetch_assoc($result);
            $response['success'] = true;
            $response['history'] = $history;
        } else {
            $response['success'] = true;
            $response['history'] = null;
        }
    } else {
        $response['error'] = 'Missing required parameters';
    }
}

// Handle POST request - Update notification history
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (isset($data['service_id']) && isset($data['project_id']) && isset($data['downtime_minutes'])) {
        $service_id = escape_string($data['service_id']);
        $project_id = escape_string($data['project_id']);
        $downtime_minutes = (int) $data['downtime_minutes'];
        
        // Check if record exists
        $result = query("SELECT * FROM service_notification_history 
                        WHERE service_id = '$service_id' 
                        AND project_id = '$project_id'");
        
        if (mysqli_num_rows($result) > 0) {
            // Update existing record
            $result = query("UPDATE service_notification_history 
                           SET last_notification_time = NOW(), 
                               downtime_minutes = '$downtime_minutes' 
                           WHERE service_id = '$service_id' 
                           AND project_id = '$project_id'");
            
            if ($result) {
                $response['success'] = true;
                $response['action'] = 'updated';
            } else {
                $response['error'] = 'Failed to update notification history';
            }
        } else {
            // Insert new record
            $result = query("INSERT INTO service_notification_history 
                           (service_id, project_id, last_notification_time, downtime_minutes) 
                           VALUES ('$service_id', '$project_id', NOW(), '$downtime_minutes')");
            
            if ($result) {
                $response['success'] = true;
                $response['action'] = 'created';
            } else {
                $response['error'] = 'Failed to create notification history';
            }
        }
    } else {
        $response['error'] = 'Missing required parameters';
    }
}

// Return JSON response
header('Content-Type: application/json');
echo json_encode($response);
?>