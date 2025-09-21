<?php
include 'head.php';

if (isset($_POST['getProject']) && isset($_POST['project'])) {
    $projectName = escape_string($_POST['project']);
    $project = query("SELECT * FROM projects WHERE link='$projectName'");

    if (mysqli_num_rows($project) == 1) {
        $projectData = fetch_assoc($project);
        echo echoJSON($projectData);
    } else {
        echo echoJSON(['error' => 'Project not found']);
    }
}
// Get all services for a project
elseif (isset($_POST['getServices']) && isset($_POST['project'])) {
    $projectName = escape_string($_POST['project']);
    $projectID = fetch_assoc(query("SELECT * FROM projects WHERE link='$projectName'"))['projectID'];

    $services = query("SELECT * FROM project_services WHERE projectID='$projectID' ORDER BY `id` ASC");
    $json = [];
    $i = 0;
    foreach ($services as $service) {
        $json[$i]['id'] = $service['id'];
        $json[$i]['icon'] = $service['icon'];
        $json[$i]['name'] = $service['name'];
        $json[$i]['link'] = $service['link'];
        $json[$i]['description'] = $service['description'];
        $json[$i]['status'] = $service['status'];
        $i++;
    }
    echo echoJSON($json);
}
// Add a new service
elseif (isset($_POST['addService']) && isset($_POST['name']) && isset($_POST['project'])) {
    $projectName = escape_string($_POST['project']);
    $name = escape_string($_POST['name']);
    $icon = escape_string($_POST['icon'] ?? 'cog-outline');
    $description = escape_string($_POST['description'] ?? '');
    $link = createLink($name);
    
    $projectID = fetch_assoc(query("SELECT * FROM projects WHERE link='$projectName'"))['projectID'];
    $query = query("INSERT INTO project_services VALUES (0, '$icon', '$name', '$link', '$description', 'active', '$projectID')");

    if ($query) {
        // Get the new service ID
        //$serviceId = mysqli_insert_id($con);

        // Add the service page to the control center pages
        $url = "project/" . $projectName . "/services/" . $link;
        $configUrl = $url . "/config";

        query("INSERT INTO control_center_pages VALUES (0, '$url', 'true', '$icon', '$name', '', 0)");
        query("INSERT INTO control_center_pages VALUES (0, '$configUrl', 'true', 'cog-outline', '$name Config', '', 0)");

        // Return a JSON response with success status and service details
        $response = [
            'status' => 'success',
            'message' => 'Service created successfully',
            'service' => [
                //'id' => $serviceId,
                'icon' => $icon,
                'name' => $name,
                'link' => $link,
                'description' => $description,
                'status' => 'active',
                //'projectID' => $projectID
            ]
        ];
        echo echoJSON($response);
    } else {
        $response = [
            'status' => 'error',
            'message' => 'Database error: ' . mysqli_error($con)
        ];
        echo echoJSON($response);
    }
}
// Delete a service completely (including all associated data)
elseif (isset($_POST['deleteServiceComplete']) && isset($_POST['serviceId'])) {
    $serviceId = escape_string($_POST['serviceId']);
    
    // Start transaction
    mysqli_autocommit($con, false);
    
    try {
        // Get service details for cleanup
        $serviceResult = query("SELECT * FROM project_services WHERE id='$serviceId'");
        if (mysqli_num_rows($serviceResult) == 0) {
            throw new Exception('Service not found');
        }
        $service = fetch_assoc($serviceResult);
        $serviceLink = $service['link'];
        $projectID = $service['projectID'];
        
        // Get project link for URL cleanup
        $projectResult = query("SELECT link FROM projects WHERE projectID='$projectID'");
        $project = fetch_assoc($projectResult);
        $projectLink = $project['link'];
        
        // Delete API keys associated with this service
        $deleteApiKeys = query("DELETE FROM api_keys WHERE project_id='$projectID' AND service='$serviceLink'");
        if (!$deleteApiKeys) {
            throw new Exception('Failed to delete API keys: ' . mysqli_error($con));
        }
        
        // Delete service logs
        $deleteLogs = query("DELETE FROM control_center_service_logs WHERE project_id='$projectID' AND service='$serviceLink'");
        if (!$deleteLogs) {
            throw new Exception('Failed to delete service logs: ' . mysqli_error($con));
        }
        
        // Delete service status history
        $deleteStatus = query("DELETE FROM service_status_history WHERE project_id='$projectID' AND service='$serviceLink'");
        if (!$deleteStatus) {
            throw new Exception('Failed to delete service status history: ' . mysqli_error($con));
        }
        
        // Delete control center pages for this service
        $serviceUrl = "project/" . $projectLink . "/services/" . $serviceLink;
        $configUrl = $serviceUrl . "/config";
        $deletePages = query("DELETE FROM control_center_pages WHERE url='$serviceUrl' OR url='$configUrl'");
        if (!$deletePages) {
            throw new Exception('Failed to delete control center pages: ' . mysqli_error($con));
        }
        
        // Finally, delete the service itself
        $deleteService = query("DELETE FROM project_services WHERE id='$serviceId'");
        if (!$deleteService) {
            throw new Exception('Failed to delete service: ' . mysqli_error($con));
        }
        
        // Commit transaction
        mysqli_commit($con);
        
        $response = [
            'status' => 'success',
            'message' => 'Service and all associated data deleted successfully'
        ];
        echo echoJSON($response);
        
    } catch (Exception $e) {
        // Rollback transaction on error
        mysqli_rollback($con);
        
        $response = [
            'status' => 'error',
            'message' => $e->getMessage()
        ];
        echo echoJSON($response);
    } finally {
        // Re-enable autocommit
        mysqli_autocommit($con, true);
    }
}
// Delete a service (simple version - backward compatibility)
elseif (isset($_POST['deleteService']) && isset($_POST['serviceId'])) {
    $serviceId = escape_string($_POST['serviceId']);

    $query = query("DELETE FROM project_services WHERE id='$serviceId'");

    if ($query) {
        echo "success";
    } else {
        echo "error: " . mysqli_error($con);
    }
}
// Update a service
elseif (isset($_POST['updateService']) && isset($_POST['serviceId']) && isset($_POST['name'])) {
    $serviceId = escape_string($_POST['serviceId']);
    $name = escape_string($_POST['name']);
    $icon = escape_string($_POST['icon'] ?? 'cog-outline');
    $description = escape_string($_POST['description'] ?? '');
    $status = escape_string($_POST['status'] ?? 'active');

    $query = query("UPDATE project_services SET name='$name', icon='$icon', description='$description', status='$status' WHERE id='$serviceId'");

    if ($query) {
        echo "success";
    } else {
        echo "error: " . mysqli_error($con);
    }
}
// Get a specific service
elseif (isset($_POST['getService']) && isset($_POST['serviceId'])) {
    $serviceId = escape_string($_POST['serviceId']);

    $service = query("SELECT * FROM project_services WHERE id='$serviceId'");

    if (mysqli_num_rows($service) == 1) {
        echo echoJSON(fetch_assoc($service));
    } else {
        echo "error: Service not found";
    }
}
