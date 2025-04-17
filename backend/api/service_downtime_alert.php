<?php
// This script checks for services that have been down for more than 40 minutes
// It doesn't send notifications directly anymore - this is now handled by the JavaScript service_monitor.js

include '../head.php';

// Hardcoded credentials - only requests with these credentials will be processed
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

// Just return the information about down services - no notifications are sent from PHP
$response['success'] = true;
$response['down_services'] = $down_services;
$response['message'] = count($down_services) > 0 
    ? count($down_services) . ' service(s) have been down for more than 40 minutes' 
    : 'No services have been down for more than 40 minutes';

// Return JSON response
header('Content-Type: application/json');
echo json_encode($response);