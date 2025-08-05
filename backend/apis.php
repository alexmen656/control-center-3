<?php
include 'head.php';

$headers = getRequestHeaders();

if ($headers['Authorization']) {
    $token = escape_string($headers['Authorization']);
    $userData = query("SELECT * FROM control_center_users WHERE loginToken='$token'");

    if (mysqli_num_rows($userData) == 1) {
        $userData = fetch_assoc($userData);
        $userID = $userData['userID'];

        // Get all APIs for a project
        if (isset($_POST['getApis']) && isset($_POST['project'])) {
            $projectName = escape_string($_POST['project']);
            $projectID = fetch_assoc(query("SELECT * FROM projects WHERE link='$projectName'"))['projectID'];

            $apis = query("SELECT * FROM project_apis WHERE projectID='$projectID' ORDER BY `id` ASC");
            $json = [];
            $i = 0;
            foreach ($apis as $api) {
                $json[$i]['id'] = $api['id'];
                $json[$i]['name'] = $api['name'];
                $json[$i]['slug'] = $api['slug'];
                $json[$i]['description'] = $api['description'];
                $json[$i]['icon'] = $api['icon'];
                $json[$i]['type'] = $api['type'];
                $json[$i]['base_url'] = $api['base_url'];
                $json[$i]['auth_type'] = $api['auth_type'];
                $json[$i]['status'] = $api['status'];
                $json[$i]['rate_limit'] = $api['rate_limit'];
                $json[$i]['created_at'] = $api['created_at'];
                $json[$i]['updated_at'] = $api['updated_at'];
                $i++;
            }
            echo echoJSON($json);
        }

        // Get specific API details
        elseif (isset($_POST['getApi']) && isset($_POST['apiId'])) {
            $apiId = escape_string($_POST['apiId']);
            $api = query("SELECT * FROM project_apis WHERE id='$apiId'");
            
            if (mysqli_num_rows($api) == 1) {
                $apiData = fetch_assoc($api);
                
                // Get API keys
                $keys = query("SELECT * FROM project_api_keys WHERE api_id='$apiId'");
                $apiKeys = [];
                foreach ($keys as $key) {
                    $apiKeys[] = [
                        'id' => $key['id'],
                        'key_name' => $key['key_name'],
                        'key_value' => $key['is_encrypted'] ? '***hidden***' : $key['key_value'],
                        'is_encrypted' => $key['is_encrypted']
                    ];
                }
                
                // Get endpoints
                $endpoints = query("SELECT * FROM project_api_endpoints WHERE api_id='$apiId'");
                $apiEndpoints = [];
                foreach ($endpoints as $endpoint) {
                    $apiEndpoints[] = [
                        'id' => $endpoint['id'],
                        'name' => $endpoint['name'],
                        'method' => $endpoint['method'],
                        'endpoint' => $endpoint['endpoint'],
                        'description' => $endpoint['description'],
                        'parameters' => json_decode($endpoint['parameters'] ?? '[]'),
                        'headers' => json_decode($endpoint['headers'] ?? '[]'),
                        'response_example' => json_decode($endpoint['response_example'] ?? '{}'),
                        'is_active' => $endpoint['is_active']
                    ];
                }
                
                $apiData['keys'] = $apiKeys;
                $apiData['endpoints'] = $apiEndpoints;
                
                echo echoJSON($apiData);
            } else {
                echo echoJSON(['error' => 'API not found']);
            }
        }

        // Add a new API
        elseif (isset($_POST['addApi']) && isset($_POST['name']) && isset($_POST['project'])) {
            $projectName = escape_string($_POST['project']);
            $projectID = fetch_assoc(query("SELECT * FROM projects WHERE link='$projectName'"))['projectID'];
            
            $name = escape_string($_POST['name']);
            $slug = escape_string($_POST['slug'] ?? strtolower(str_replace(' ', '-', $name)));
            $description = escape_string($_POST['description'] ?? '');
            $icon = escape_string($_POST['icon'] ?? 'code-outline');
            $type = escape_string($_POST['type'] ?? 'REST');
            $base_url = escape_string($_POST['base_url'] ?? '');
            $auth_type = escape_string($_POST['auth_type'] ?? 'none');

            $insertAPI = query("INSERT INTO project_apis (projectID, name, slug, description, icon, type, base_url, auth_type) 
                              VALUES ('$projectID', '$name', '$slug', '$description', '$icon', '$type', '$base_url', '$auth_type')");

            if ($insertAPI) {
                echo echoJSON(['success' => true, 'message' => 'API added successfully']);
            } else {
                echo echoJSON(['error' => 'Failed to add API']);
            }
        }

        // Update API
        elseif (isset($_POST['updateApi']) && isset($_POST['apiId'])) {
            $apiId = escape_string($_POST['apiId']);
            $name = escape_string($_POST['name']);
            $description = escape_string($_POST['description'] ?? '');
            $icon = escape_string($_POST['icon'] ?? 'code-outline');
            $type = escape_string($_POST['type'] ?? 'REST');
            $base_url = escape_string($_POST['base_url'] ?? '');
            $auth_type = escape_string($_POST['auth_type'] ?? 'none');
            $status = escape_string($_POST['status'] ?? 'inactive');
            $rate_limit = escape_string($_POST['rate_limit'] ?? 100);

            $updateAPI = query("UPDATE project_apis SET 
                              name='$name', 
                              description='$description', 
                              icon='$icon', 
                              type='$type', 
                              base_url='$base_url', 
                              auth_type='$auth_type', 
                              status='$status', 
                              rate_limit='$rate_limit',
                              updated_at=NOW()
                              WHERE id='$apiId'");

            if ($updateAPI) {
                echo echoJSON(['success' => true, 'message' => 'API updated successfully']);
            } else {
                echo echoJSON(['error' => 'Failed to update API']);
            }
        }

        // Delete API
        elseif (isset($_POST['deleteApi']) && isset($_POST['apiId'])) {
            $apiId = escape_string($_POST['apiId']);
            
            $deleteAPI = query("DELETE FROM project_apis WHERE id='$apiId'");

            if ($deleteAPI) {
                echo echoJSON(['success' => true, 'message' => 'API deleted successfully']);
            } else {
                echo echoJSON(['error' => 'Failed to delete API']);
            }
        }

        // Add API Key
        elseif (isset($_POST['addApiKey']) && isset($_POST['apiId'])) {
            $apiId = escape_string($_POST['apiId']);
            $keyName = escape_string($_POST['keyName']);
            $keyValue = escape_string($_POST['keyValue']);
            $isEncrypted = isset($_POST['isEncrypted']) ? 1 : 0;

            // If encryption is requested, encrypt the value
            if ($isEncrypted) {
                $keyValue = base64_encode($keyValue); // Simple encoding, use proper encryption in production
            }

            $insertKey = query("INSERT INTO project_api_keys (api_id, key_name, key_value, is_encrypted) 
                              VALUES ('$apiId', '$keyName', '$keyValue', '$isEncrypted')");

            if ($insertKey) {
                echo echoJSON(['success' => true, 'message' => 'API key added successfully']);
            } else {
                echo echoJSON(['error' => 'Failed to add API key']);
            }
        }

        // Add API Endpoint
        elseif (isset($_POST['addEndpoint']) && isset($_POST['apiId'])) {
            $apiId = escape_string($_POST['apiId']);
            $name = escape_string($_POST['name']);
            $method = escape_string($_POST['method'] ?? 'GET');
            $endpoint = escape_string($_POST['endpoint']);
            $description = escape_string($_POST['description'] ?? '');
            $parameters = escape_string($_POST['parameters'] ?? '[]');
            $headers = escape_string($_POST['headers'] ?? '[]');
            $responseExample = escape_string($_POST['responseExample'] ?? '{}');

            $insertEndpoint = query("INSERT INTO project_api_endpoints (api_id, name, method, endpoint, description, parameters, headers, response_example) 
                                   VALUES ('$apiId', '$name', '$method', '$endpoint', '$description', '$parameters', '$headers', '$responseExample')");

            if ($insertEndpoint) {
                echo echoJSON(['success' => true, 'message' => 'Endpoint added successfully']);
            } else {
                echo echoJSON(['error' => 'Failed to add endpoint']);
            }
        }

        else {
            echo echoJSON(['error' => 'Invalid request']);
        }
    } else {
        echo echoJSON(['error' => 'Unauthorized']);
    }
} else {
    echo echoJSON(['error' => 'No authorization token provided']);
}
?>
