<?php
// This script checks for services that have been down for more than 40 minutes
// and sends push notifications about them directly via Firebase
// Protected by hardcoded credentials for security

include '../head.php';

// Hardcoded credentials - only requests with these credentials will be processed
$AUTH_USERNAME = "service_monitor";
$AUTH_PASSWORD = "Mwgs78HJg12!3sKs";

// Firebase configuration
$FIREBASE_SERVER_KEY = "AAAAKvG_jc0:APA91bHfnXLWA2TMS-WQ3cTJy5J2L40J1V8ZD6TnK6Fpkn9Ty7BXGorBPY35wXDcU6RQ1dthQNndfjyvJIN4lW4cFqK0sb2czh4p3zamYVJZYDLgQH9PgOIxBAN6gVFkrEwVyTCTeI-j";
$FIREBASE_URL = "https://fcm.googleapis.com/fcm/send";

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

// Get all services from all projects
$services_query = query("SELECT ps.id as service_id, ps.name as service_name, ps.link as service_link, 
                         p.projectID as project_id, p.name as project_name
                         FROM project_services ps 
                         JOIN projects p ON ps.projectID = p.projectID");

$down_services = [];

// For each service, check when it last reported in the logs
while ($service = fetch_assoc($services_query)) {
    $service_id = $service['service_id'];
    $project_id = $service['project_id'];
    $service_link = $service['service_link'];
    $service_name = $service['service_name'];
    $project_name = $service['project_name'];
    
    // Check last log entry time from control_center_services_logs
    // We specifically look for ping logs (is_ping = true) as in ServiceStatusHistory.vue
    $sql = "SELECT * FROM control_center_services_logs 
            WHERE project_id = '$project_id' 
            AND service = '$service_link'
            AND data LIKE '%\"is_ping\":true%'
            ORDER BY timestamp DESC 
            LIMIT 1";
    
    $last_log = query($sql);
    
    // If we have a log entry, check if it's more than 40 minutes old
    if (mysqli_num_rows($last_log) > 0) {
        $log = fetch_assoc($last_log);
        $last_ping_time = strtotime($log['timestamp']);
        $current_time = time();
        $minutes_since_last_ping = floor(($current_time - $last_ping_time) / 60);
        
        // If last ping was more than 40 minutes ago, consider the service down
        if ($minutes_since_last_ping >= 40) {
            $service['downtime_minutes'] = $minutes_since_last_ping;
            $service['last_ping'] = $log['timestamp'];
            $down_services[] = $service;
        }
    } else {
        // If no ping logs at all, check if there are any other logs for this service
        $any_logs_sql = "SELECT * FROM control_center_services_logs 
                        WHERE project_id = '$project_id' 
                        AND service = '$service_link'
                        ORDER BY timestamp DESC 
                        LIMIT 1";
        $any_logs_result = query($any_logs_sql);
        
        if (mysqli_num_rows($any_logs_result) > 0) {
            // Service has logs but no ping logs - consider it down since it's never pinged
            $log = fetch_assoc($any_logs_result);
            $first_log_time = strtotime($log['timestamp']);
            $current_time = time();
            $minutes_since_first_log = floor(($current_time - $first_log_time) / 60);
            
            if ($minutes_since_first_log >= 40) {
                $service['downtime_minutes'] = $minutes_since_first_log;
                $service['last_ping'] = 'Never';
                $service['has_logs'] = true;
                $down_services[] = $service;
            }
        } else {
            // If no logs at all, the service might be new or never reported
            // We'll consider it down only if it was created more than 40 minutes ago
            $service_creation_sql = "SELECT created_at FROM project_services WHERE id = '$service_id'";
            $service_creation_result = query($service_creation_sql);
            
            if (mysqli_num_rows($service_creation_result) > 0) {
                $creation_data = fetch_assoc($service_creation_result);
                if ($creation_data['created_at']) {
                    $creation_time = strtotime($creation_data['created_at']);
                    $current_time = time();
                    $minutes_since_creation = floor(($current_time - $creation_time) / 60);
                    
                    if ($minutes_since_creation >= 40) {
                        $service['downtime_minutes'] = $minutes_since_creation;
                        $service['last_ping'] = 'Never';
                        $service['has_logs'] = false;
                        $down_services[] = $service;
                    }
                }
            }
        }
    }
}

// If there are any down services, send push notifications via Firebase
if (count($down_services) > 0) {
    $response['success'] = true;
    $response['down_services'] = $down_services;
    
    // Get all registered push notification tokens
    $tokens_result = query("SELECT * FROM control_center_push_notifications_token");
    $tokens = [];
    
    while ($token_row = fetch_assoc($tokens_result)) {
        $tokens[] = $token_row['token'];
    }
    
    // If we have tokens to send to
    if (count($tokens) > 0) {
        foreach ($down_services as $service) {
            $service_name = $service['service_name'];
            $project_name = $service['project_name'];
            $downtime_minutes = $service['downtime_minutes'];
            $downtime_hours = floor($downtime_minutes / 60);
            $downtime_mins = $downtime_minutes % 60;
            
            // Format the downtime message
            $downtime_text = "";
            if ($downtime_hours > 0) {
                $downtime_text = "$downtime_hours hour" . ($downtime_hours > 1 ? "s" : "");
                if ($downtime_mins > 0) {
                    $downtime_text .= " and $downtime_mins minute" . ($downtime_mins > 1 ? "s" : "");
                }
            } else {
                $downtime_text = "$downtime_minutes minutes";
            }
            
            // Prepare the notification message
            $title = "Service Down Alert";
            $message = "Service '$service_name' in project '$project_name' has been down for $downtime_text";
            
            // Send Firebase push notification to all tokens
            foreach ($tokens as $token) {
                sendFirebasePushNotification($token, $title, $message);
                
                // Also store in the database for tracking purposes
                // Escape special characters in the message and title to prevent SQL errors
                $escaped_message = escape_string($message);
                $escaped_title = escape_string($title);
                query("INSERT INTO control_center_push_notifications (date, time, token, body, title)
                    VALUES (CURDATE(), CURTIME(), '$token', '$escaped_message', '$escaped_title')");
            }
        }
    } else {
        $response['warning'] = "No push notification tokens found";
    }
} else {
    $response['success'] = true;
    $response['down_services'] = [];
    $response['message'] = 'No services have been down for more than 40 minutes';
}

/**
 * Send a push notification via Firebase Cloud Messaging using file_get_contents instead of curl
 * 
 * @param string $token The FCM token to send the message to
 * @param string $title The title of the notification
 * @param string $body The body text of the notification
 * @return array|false The response from Firebase or false on failure
 */
function sendFirebasePushNotification($token, $title, $body) {
    global $FIREBASE_SERVER_KEY, $FIREBASE_URL;
    
    // Prepare the notification payload
    $fields = [
        'to' => "emJJaM7Kyhpo2b6rwnpaQ3:APA91bGbR4D_IwEzPCDVC3Qa6Iu1yK9NB9hvVybnLsZF4MsCLzlo8ASIZPSaPFcoUCa5kCWFESMeFEOGC-yJhQbfPS--GKRJbyB1sGT_q5ysidJNlHfOzH4",
        'notification' => [
            'title' => $title,
            'body' => $body,
            'sound' => 'default',
            'badge' => '1'
        ],
        'data' => [
            'title' => $title,
            'body' => $body,
            'click_action' => 'OPEN_SERVICE_STATUS'
        ],
        'priority' => 'high'
    ];
    
    // Set up the HTTP request context
    $options = [
        'http' => [
            'method' => 'POST',
            'header' => [
                'Authorization: key=' . $FIREBASE_SERVER_KEY,
                'Content-Type: application/json'
            ],
            'content' => json_encode($fields),
            'timeout' => 15,
            'ignore_errors' => true
        ]
    ];
    
    // Fix the headers array
    $options['http']['header'] = implode("\r\n", $options['http']['header']) . "\r\n";
    
    // Create the context
    $context = stream_context_create($options);
    
    // Execute the request using file_get_contents
    $result = @file_get_contents($FIREBASE_URL, false, $context);
    
    // Check for errors
    if ($result === false) {
        // Log the error
        error_log("Firebase notification failed for token: $token");
        echo $token;
        return false;
    }
    
    // Return the response as an array
    return json_decode($result, true);
}

// Return JSON response
header('Content-Type: application/json');
echo json_encode($response);