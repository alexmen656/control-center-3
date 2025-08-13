<?php
include 'head.php';
include 'apis_helper.php';
include 'vercel_helper.php';

if (isset($_POST['getProjectAPIs']) && isset($_POST['project'])) {
    $projectName = escape_string($_POST['project']);
    $projectID = getProjectID($projectName);

    if (!checkUserProjectPermission($userID, $projectID)) {
        showJSON(['error' => 'No permission for this project']);
        exit;
    }

    $subscriptions = query("
        SELECT pas.*, ca.name, ca.slug, ca.description, ca.icon, ca.category, ca.endpoint_base, ca.documentation_url
        FROM project_api_subscriptions pas
        JOIN cms_apis ca ON pas.api_id = ca.id
        WHERE pas.projectID='$projectID' AND pas.is_enabled=1
        ORDER BY ca.category, ca.name ASC
    ");

    $json = [];
    foreach ($subscriptions as $sub) {
        $json[] = formatProjectSubscriptionData($sub);
    }
    showJSON($json);
}

// Get activated APIs for a specific codespace
elseif (isset($_POST['getCodespaceAPIs']) && isset($_POST['project']) && isset($_POST['codespace'])) {
    $projectName = escape_string($_POST['project']);
    $codespaceSlug = escape_string($_POST['codespace']);
    $projectID = getProjectID($projectName);

    if (!checkUserProjectPermission($userID, $projectID)) {
        showJSON(['error' => 'No permission for this project']);
        exit;
    }

    // Get codespace ID
    $codespaceResult = query("SELECT id FROM project_codespaces WHERE project_id='$projectID' AND slug='$codespaceSlug' LIMIT 1");
    if (mysqli_num_rows($codespaceResult) === 0) {
        showJSON(['error' => 'Codespace not found']);
        exit;
    }
    $codespace = fetch_assoc($codespaceResult);
    $codespaceId = $codespace['id'];

    // Get all project APIs with their activation status for this codespace
    $apis = query("
        SELECT 
            pas.id as subscription_id,
            pas.api_id,
            ca.name,
            ca.slug,
            ca.description,
            ca.icon,
            ca.category,
            ca.endpoint_base,
            ca.documentation_url,
            caa.id as activation_id,
            caa.is_active,
            caa.api_key as codespace_api_key,
            pas.api_key as project_api_key,
            pas.rate_limit
        FROM project_api_subscriptions pas
        JOIN cms_apis ca ON pas.api_id = ca.id
        LEFT JOIN codespace_api_activations caa ON caa.subscription_id = pas.id AND caa.codespace_id = '$codespaceId'
        WHERE pas.projectID='$projectID' AND pas.is_enabled=1
        ORDER BY ca.category, ca.name ASC
    ");

    $json = [];
    foreach ($apis as $api) {
        $json[] = formatCodespaceApiData($api);
    }
    showJSON($json);
}

// Activate an API for a specific codespace
elseif (isset($_POST['activateCodespaceAPI']) && isset($_POST['project']) && isset($_POST['codespace']) && isset($_POST['subscription_id'])) {
    $projectName = escape_string($_POST['project']);
    $codespaceSlug = escape_string($_POST['codespace']);
    $subscriptionId = escape_string($_POST['subscription_id']);
    $projectID = getProjectID($projectName);

    if (!checkUserProjectPermission($userID, $projectID)) {
        showJSON(['error' => 'No permission for this project']);
        exit;
    }

    // Get codespace ID
    $codespaceResult = query("SELECT id FROM project_codespaces WHERE project_id='$projectID' AND slug='$codespaceSlug' LIMIT 1");
    if (mysqli_num_rows($codespaceResult) === 0) {
        showJSON(['error' => 'Codespace not found']);
        exit;
    }
    $codespace = fetch_assoc($codespaceResult);
    $codespaceId = $codespace['id'];

    // Verify subscription belongs to this project
    $subscriptionResult = query("SELECT api_id FROM project_api_subscriptions WHERE id='$subscriptionId' AND projectID='$projectID' LIMIT 1");
    if (mysqli_num_rows($subscriptionResult) === 0) {
        showJSON(['error' => 'Invalid subscription']);
        exit;
    }

    // Check if already activated
    $existingResult = query("SELECT id FROM codespace_api_activations WHERE codespace_id='$codespaceId' AND subscription_id='$subscriptionId' LIMIT 1");
    if (mysqli_num_rows($existingResult) > 0) {
        // Update existing activation
        $updateResult = query("UPDATE codespace_api_activations SET is_active=1 WHERE codespace_id='$codespaceId' AND subscription_id='$subscriptionId'");
    } else {
        // Create new activation
        $updateResult = query("INSERT INTO codespace_api_activations (codespace_id, subscription_id, is_active) VALUES ('$codespaceId', '$subscriptionId', 1)");
    }

    if ($updateResult) {
        // Get API slug and api_key for SDK installation and Vercel env var
        $apiResult = query("
            SELECT ca.slug, pas.api_key
            FROM project_api_subscriptions pas 
            JOIN cms_apis ca ON pas.api_id = ca.id 
            WHERE pas.id='$subscriptionId'
        ");
        $api = fetch_assoc($apiResult);
        
        // Install SDK files
        $copyResult = copyAPISDKToCodespace($projectName, $codespaceSlug, $api['slug'], $userID);
        
        // Set API key as environment variable in Vercel
        $vercelResult = ['success' => true, 'message' => 'No Vercel integration'];
        try {
            $vercelHelper = new VercelHelper($userID);
            $vercelResult = $vercelHelper->setAPIKeyEnvironmentVariable($projectName, $codespaceSlug, $api['slug'], $api['api_key']);
        } catch (Exception $e) {
            error_log("Vercel API key setup failed: " . $e->getMessage());
            $vercelResult = ['success' => false, 'error' => $e->getMessage()];
        }
        
        $message = 'API activated';
        if ($copyResult) {
            $message .= ' and SDK installed';
        } else {
            $message .= ' but SDK installation failed';
        }
        
        if ($vercelResult['success']) {
            $message .= ', API key set in Vercel as ' . ($vercelResult['env_var_name'] ?? 'environment variable');
        } else {
            $message .= ', but Vercel API key setup failed: ' . ($vercelResult['error'] ?? 'Unknown error');
        }
        
        showJSON(['success' => true, 'message' => $message, 'vercel_result' => $vercelResult]);
    } else {
        showJSON(['error' => 'Failed to activate API']);
    }
}

// Deactivate an API for a specific codespace
elseif (isset($_POST['deactivateCodespaceAPI']) && isset($_POST['project']) && isset($_POST['codespace']) && isset($_POST['subscription_id'])) {
    $projectName = escape_string($_POST['project']);
    $codespaceSlug = escape_string($_POST['codespace']);
    $subscriptionId = escape_string($_POST['subscription_id']);
    $projectID = getProjectID($projectName);

    if (!checkUserProjectPermission($userID, $projectID)) {
        showJSON(['error' => 'No permission for this project']);
        exit;
    }

    // Get codespace ID
    $codespaceResult = query("SELECT id FROM project_codespaces WHERE project_id='$projectID' AND slug='$codespaceSlug' LIMIT 1");
    if (mysqli_num_rows($codespaceResult) === 0) {
        showJSON(['error' => 'Codespace not found']);
        exit;
    }
    $codespace = fetch_assoc($codespaceResult);
    $codespaceId = $codespace['id'];

    // Deactivate API
    $updateResult = query("UPDATE codespace_api_activations SET is_active=0 WHERE codespace_id='$codespaceId' AND subscription_id='$subscriptionId'");

    if ($updateResult) {
        // Get API slug for SDK removal and Vercel env var removal
        $apiResult = query("
            SELECT ca.slug 
            FROM project_api_subscriptions pas 
            JOIN cms_apis ca ON pas.api_id = ca.id 
            WHERE pas.id='$subscriptionId'
        ");
        $api = fetch_assoc($apiResult);
        
        // Remove SDK files
        $removeResult = removeAPISDKFromCodespace($projectName, $codespaceSlug, $api['slug'], $userID);
        
        // Remove API key environment variable from Vercel
        $vercelResult = ['success' => true, 'message' => 'No Vercel integration'];
        try {
            $vercelHelper = new VercelHelper($userID);
            $vercelResult = $vercelHelper->removeAPIKeyEnvironmentVariable($projectName, $codespaceSlug, $api['slug']);
        } catch (Exception $e) {
            error_log("Vercel API key removal failed: " . $e->getMessage());
            $vercelResult = ['success' => false, 'error' => $e->getMessage()];
        }
        
        $message = 'API deactivated';
        if ($removeResult) {
            $message .= ' and SDK removed';
        } else {
            $message .= ' but SDK removal failed';
        }
        
        if ($vercelResult['success']) {
            if ($vercelResult['action'] === 'deleted') {
                $message .= ', API key removed from Vercel';
            } else if ($vercelResult['action'] === 'not_found') {
                $message .= ', API key was not found in Vercel';
            }
        } else {
            $message .= ', but Vercel API key removal failed: ' . ($vercelResult['error'] ?? 'Unknown error');
        }
        
        showJSON(['success' => true, 'message' => $message, 'vercel_result' => $vercelResult]);
    } else {
        showJSON(['error' => 'Failed to deactivate API']);
    }
}

// Get API details for a codespace
elseif (isset($_POST['getCodespaceAPIDetails']) && isset($_POST['project']) && isset($_POST['codespace']) && isset($_POST['api_slug'])) {
    $projectName = escape_string($_POST['project']);
    $codespaceSlug = escape_string($_POST['codespace']);
    $apiSlug = escape_string($_POST['api_slug']);
    $projectID = getProjectID($projectName);

    if (!checkUserProjectPermission($userID, $projectID)) {
        showJSON(['error' => 'No permission for this project']);
        exit;
    }

    // Get codespace ID
    $codespaceResult = query("SELECT id FROM project_codespaces WHERE project_id='$projectID' AND slug='$codespaceSlug' LIMIT 1");
    if (mysqli_num_rows($codespaceResult) === 0) {
        showJSON(['error' => 'Codespace not found']);
        exit;
    }
    $codespace = fetch_assoc($codespaceResult);
    $codespaceId = $codespace['id'];

    $api_query = query("
        SELECT 
            ca.*,
            pas.id as subscription_id, 
            pas.api_key as project_api_key, 
            pas.rate_limit, 
            pas.usage_count, 
            pas.last_used, 
            pas.is_enabled,
            caa.id as activation_id,
            caa.is_active as codespace_active,
            caa.api_key as codespace_api_key
        FROM cms_apis ca
        JOIN project_api_subscriptions pas ON ca.id = pas.api_id AND pas.projectID='$projectID'
        LEFT JOIN codespace_api_activations caa ON caa.subscription_id = pas.id AND caa.codespace_id = '$codespaceId'
        WHERE ca.slug='$apiSlug'
    ");

    if (mysqli_num_rows($api_query) == 0) {
        header('HTTP/1.1 404 Not Found');
        echo json_encode(['error' => 'API not found or not subscribed']);
        exit;
    }

    $api = fetch_assoc($api_query);
    
    // Get endpoints for this API
    $endpoints = query("SELECT * FROM cms_api_endpoints WHERE api_id='" . $api['id'] . "' ORDER BY endpoint ASC");
    $api['endpoints'] = [];
    foreach ($endpoints as $endpoint) {
        $api['endpoints'][] = formatEndpointData($endpoint);
    }

    // Get usage stats for this codespace activation
    if ($api['activation_id']) {
        $api['usage_stats'] = calculateCodespaceUsageStats($api['activation_id']);
        
        // Get recent activity
        $activity_query = query("
            SELECT method, path, status_code, response_time, timestamp
            FROM cms_api_usage_logs
            WHERE activation_id='" . $api['activation_id'] . "'
            ORDER BY timestamp DESC
            LIMIT 10
        ");

        $api['recent_activity'] = [];
        foreach ($activity_query as $activity) {
            $api['recent_activity'][] = [
                'method' => $activity['method'],
                'path' => $activity['path'],
                'status' => $activity['status_code'],
                'response_time' => $activity['response_time'],
                'timestamp' => $activity['timestamp']
            ];
        }
    } else {
        $api['usage_stats'] = [
            'totalRequests' => 0,
            'avgResponseTime' => 0,
            'successRate' => 0,
            'requestsToday' => 0
        ];
        $api['recent_activity'] = [];
    }

    showJSON($api);
}

// Sync all API keys for a codespace to Vercel
elseif (isset($_POST['syncCodespaceAPIKeysToVercel']) && isset($_POST['project']) && isset($_POST['codespace'])) {
    $projectName = escape_string($_POST['project']);
    $codespaceSlug = escape_string($_POST['codespace']);
    $projectID = getProjectID($projectName);

    if (!checkUserProjectPermission($userID, $projectID)) {
        showJSON(['error' => 'No permission for this project']);
        exit;
    }

    try {
        $vercelHelper = new VercelHelper($userID);
        $result = $vercelHelper->syncCodespaceAPIKeys($projectName, $codespaceSlug);
        showJSON($result);
    } catch (Exception $e) {
        showJSON(['success' => false, 'error' => $e->getMessage()]);
    }
}

else {
    showJSON(['error' => 'Invalid request']);
}
