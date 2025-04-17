<?php
include '../head.php';

// This script should be run by a cron job every few minutes
// It will check all services and mark them as down if they haven't pinged in the last 30 minutes

// Get all services from all projects
$services = query("SELECT ps.id as service_id, ps.link as service_link, p.projectID as project_id, p.link as project_link 
                  FROM project_services ps 
                  JOIN projects p ON ps.projectID = p.projectID");

// For each service, check if it has pinged in the last 30 minutes
while ($service = fetch_assoc($services)) {
    $service_id = $service['service_id'];
    $project_id = $service['project_id'];
    $service_link = $service['service_link'];
    
    // Check last ping time
    $sql = "SELECT * FROM control_center_services_logs 
            WHERE project_id = '$project_id' 
            AND service = '$service_link'
            ORDER BY timestamp DESC 
            LIMIT 1";
    
    $last_log = query($sql);
    $is_down = true;
    
    if (mysqli_num_rows($last_log) > 0) {
        $log = fetch_assoc($last_log);
        $last_ping = $log['timestamp'];
        
        // Check if last ping is within 30 minutes
        $thirty_min_ago = date('Y-m-d H:i:s', strtotime('-30 minutes'));
        $is_down = $last_ping <= $thirty_min_ago;
    }
    
    // Check current status
    $sql = "SELECT * FROM service_status_history 
            WHERE service_id = '$service_id' 
            AND end_time IS NULL";
            
    $current_status = query($sql);
    
    if (mysqli_num_rows($current_status) > 0) {
        $status = fetch_assoc($current_status);
        $current_is_down = $status['status'] === 'down';
        
        // If status has changed
        if ($current_is_down !== $is_down) {
            // Close the current status
            query("UPDATE service_status_history SET end_time = NOW() WHERE id = '" . $status['id'] . "'");
            
            // Create a new status
            $new_status = $is_down ? 'down' : 'up';
            query("INSERT INTO service_status_history (service_id, status, start_time) VALUES ('$service_id', '$new_status', NOW())");
            
            // Log the status change
            $message = "Service status changed to " . strtoupper($new_status);
            $type = $is_down ? "error" : "success";
            $data = json_encode(['automatic' => true, 'status' => $new_status]);
            $id = sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
                mt_rand(0, 0xffff), mt_rand(0, 0xffff),
                mt_rand(0, 0xffff),
                mt_rand(0, 0x0fff) | 0x4000,
                mt_rand(0, 0x3fff) | 0x8000,
                mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
            );
            
            $sql = "INSERT INTO control_center_services_logs 
                    (id, project_id, service, message, type, data) 
                    VALUES (
                        '$id', 
                        '$project_id', 
                        '$service_link', 
                        '$message', 
                        '$type', 
                        '$data'
                    )";
            
            query($sql);
        }
    } else {
        // No current status, create one
        $new_status = $is_down ? 'down' : 'up';
        query("INSERT INTO service_status_history (service_id, status, start_time) VALUES ('$service_id', '$new_status', NOW())");
    }
}

echo "Service status check completed at " . date('Y-m-d H:i:s');