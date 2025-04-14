<?php
include 'head.php';

$headers = getRequestHeaders();

if ($headers['Authorization']) {
    $token = escape_string($headers['Authorization']);
    $userData = query("SELECT * FROM control_center_users WHERE loginToken='$token'");

    if (mysqli_num_rows($userData) == 1) {
        $userData = fetch_assoc($userData);
        $userID = $userData['userID'];

        // Get project details by link
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
            
            $link = str_replace(" ", "-", strtolower($name));
            $link = str_replace(["ä", "Ä", "ü", "Ü", "ö", "Ö"], ["a", "a", "u", "u", "o", "o"], $link);
            
            $projectID = fetch_assoc(query("SELECT * FROM projects WHERE link='$projectName'"))['projectID'];
            
            $query = query("INSERT INTO project_services VALUES (0, '$icon', '$name', '$link', '$description', 'active', '$projectID')");
            
            if ($query) {
                // Get the new service ID
                //$serviceId = mysqli_insert_id($conn);
                
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
                    'message' => 'Database error: ' . mysqli_error($conn)
                ];
                echo echoJSON($response);
            }
        }
        // Delete a service
        elseif (isset($_POST['deleteService']) && isset($_POST['serviceId'])) {
            $serviceId = escape_string($_POST['serviceId']);
            
            $query = query("DELETE FROM project_services WHERE id='$serviceId'");
            
            if ($query) {
                echo "success";
            } else {
                echo "error: " . mysqli_error($conn);
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
                echo "error: " . mysqli_error($conn);
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
    }
}
?>