<?php
include 'head.php';
include 'apis_helper.php';
include 'api_keys.php';

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

elseif (isset($_POST['getCodespaceAPIs']) && isset($_POST['project']) && isset($_POST['codespace'])) {
    $projectName = escape_string($_POST['project']);
    $codespaceSlug = escape_string($_POST['codespace']);
    $projectID = getProjectID($projectName);

    if (!checkUserProjectPermission($userID, $projectID)) {
        showJSON(['error' => 'No permission for this project']);
        exit;
    }

    $codespaceResult = query("SELECT id FROM project_codespaces WHERE project_id='$projectID' AND slug='$codespaceSlug' LIMIT 1");
    if (mysqli_num_rows($codespaceResult) === 0) {
        showJSON(['error' => 'Codespace not found']);
        exit;
    }
    $codespace = fetch_assoc($codespaceResult);
    $codespaceId = $codespace['id'];

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

elseif (isset($_POST['activateCodespaceAPI']) && isset($_POST['project']) && isset($_POST['codespace']) && isset($_POST['subscription_id'])) {
    $projectName = escape_string($_POST['project']);
    $codespaceSlug = escape_string($_POST['codespace']);
    $subscriptionId = escape_string($_POST['subscription_id']);
    $projectID = getProjectID($projectName);

    if (!checkUserProjectPermission($userID, $projectID)) {
        showJSON(['error' => 'No permission for this project']);
        exit;
    }

    $codespaceResult = query("SELECT id FROM project_codespaces WHERE project_id='$projectID' AND slug='$codespaceSlug' LIMIT 1");
    if (mysqli_num_rows($codespaceResult) === 0) {
        showJSON(['error' => 'Codespace not found']);
        exit;
    }
    $codespace = fetch_assoc($codespaceResult);
    $codespaceId = $codespace['id'];

    $subscriptionResult = query("SELECT api_id FROM project_api_subscriptions WHERE id='$subscriptionId' AND projectID='$projectID' LIMIT 1");
    if (mysqli_num_rows($subscriptionResult) === 0) {
        showJSON(['error' => 'Invalid subscription']);
        exit;
    }

    $existingResult = query("SELECT id FROM codespace_api_activations WHERE codespace_id='$codespaceId' AND subscription_id='$subscriptionId' LIMIT 1");
    if (mysqli_num_rows($existingResult) > 0) {
        $updateResult = query("UPDATE codespace_api_activations SET is_active=1 WHERE codespace_id='$codespaceId' AND subscription_id='$subscriptionId'");
    } else {
        $updateResult = query("INSERT INTO codespace_api_activations (codespace_id, subscription_id, is_active) VALUES ('$codespaceId', '$subscriptionId', 1)");
    }

    if ($updateResult) {
        $sub = fetch_assoc(query("
            SELECT pas.*, ca.slug
            FROM project_api_subscriptions pas
            JOIN cms_apis ca ON pas.api_id = ca.id
            WHERE pas.id='$subscriptionId'
        "));

        $envName = api_env_name($sub['slug']);
        $key = api_decrypt_key($sub);
        deploy_set_env_var($codespaceId, $envName, $key, 'both');
        deploy_set_env_var($codespaceId, 'FRINGELO_API_URL', 'https://gw.fringelo.com', 'both');

        showJSON(['success' => true, 'message' => 'API activated; key injected as ' . $envName . ' on next deploy', 'env_var' => $envName]);
    } else {
        showJSON(['error' => 'Failed to activate API']);
    }
}

elseif (isset($_POST['deactivateCodespaceAPI']) && isset($_POST['project']) && isset($_POST['codespace']) && isset($_POST['subscription_id'])) {
    $projectName = escape_string($_POST['project']);
    $codespaceSlug = escape_string($_POST['codespace']);
    $subscriptionId = escape_string($_POST['subscription_id']);
    $projectID = getProjectID($projectName);

    if (!checkUserProjectPermission($userID, $projectID)) {
        showJSON(['error' => 'No permission for this project']);
        exit;
    }

    $codespaceResult = query("SELECT id FROM project_codespaces WHERE project_id='$projectID' AND slug='$codespaceSlug' LIMIT 1");
    if (mysqli_num_rows($codespaceResult) === 0) {
        showJSON(['error' => 'Codespace not found']);
        exit;
    }
    $codespace = fetch_assoc($codespaceResult);
    $codespaceId = $codespace['id'];

    $updateResult = query("UPDATE codespace_api_activations SET is_active=0 WHERE codespace_id='$codespaceId' AND subscription_id='$subscriptionId'");

    if ($updateResult) {
        $api = fetch_assoc(query("
            SELECT ca.slug
            FROM project_api_subscriptions pas
            JOIN cms_apis ca ON pas.api_id = ca.id
            WHERE pas.id='$subscriptionId'
        "));
        deploy_delete_env_var($codespaceId, api_env_name($api['slug']));
        showJSON(['success' => true, 'message' => 'API deactivated; key removed on next deploy']);
    } else {
        showJSON(['error' => 'Failed to deactivate API']);
    }
}

elseif (isset($_POST['getCodespaceAPIDetails']) && isset($_POST['project']) && isset($_POST['codespace']) && isset($_POST['api_slug'])) {
    $projectName = escape_string($_POST['project']);
    $codespaceSlug = escape_string($_POST['codespace']);
    $apiSlug = escape_string($_POST['api_slug']);
    $projectID = getProjectID($projectName);

    if (!checkUserProjectPermission($userID, $projectID)) {
        showJSON(['error' => 'No permission for this project']);
        exit;
    }

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
    
    $endpoints = query("SELECT * FROM cms_api_endpoints WHERE api_id='" . $api['id'] . "' ORDER BY endpoint ASC");
    $api['endpoints'] = [];
    foreach ($endpoints as $endpoint) {
        $api['endpoints'][] = formatEndpointData($endpoint);
    }

    if ($api['activation_id']) {
        $api['usage_stats'] = calculateCodespaceUsageStats($api['activation_id']);
        
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

elseif (isset($_POST['syncCodespaceAPIKeysToVercel']) && isset($_POST['project']) && isset($_POST['codespace'])) {
    $projectName = escape_string($_POST['project']);
    $codespaceSlug = escape_string($_POST['codespace']);
    $projectID = getProjectID($projectName);

    if (!checkUserProjectPermission($userID, $projectID)) {
        showJSON(['error' => 'No permission for this project']);
        exit;
    }

    $codespaceResult = query("SELECT id FROM project_codespaces WHERE project_id='$projectID' AND slug='$codespaceSlug' LIMIT 1");
    if (mysqli_num_rows($codespaceResult) === 0) {
        showJSON(['error' => 'Codespace not found']);
        exit;
    }
    $codespaceId = fetch_assoc($codespaceResult)['id'];

    $active = query("
        SELECT ca.slug, pas.*
        FROM codespace_api_activations caa
        JOIN project_api_subscriptions pas ON caa.subscription_id = pas.id
        JOIN cms_apis ca ON pas.api_id = ca.id
        WHERE caa.codespace_id='$codespaceId' AND caa.is_active=1
    ");
    $synced = [];
    foreach ($active as $sub) {
        $envName = api_env_name($sub['slug']);
        deploy_set_env_var($codespaceId, $envName, api_decrypt_key($sub), 'both');
        $synced[] = $envName;
    }
    deploy_set_env_var($codespaceId, 'FRINGELO_API_URL', 'https://gw.fringelo.com', 'both');

    showJSON(['success' => true, 'synced' => $synced, 'message' => count($synced) . ' API keys injected as env vars']);
}

elseif (isset($_POST['publishCodespaceAsAPI']) && isset($_POST['project']) && isset($_POST['codespace'])) {
    $projectName = escape_string($_POST['project']);
    $codespaceSlug = escape_string($_POST['codespace']);
    $projectID = getProjectID($projectName);

    if (!checkUserProjectPermission($userID, $projectID)) {
        showJSON(['error' => 'No permission for this project']);
        exit;
    }

    $cs = fetch_assoc(query("SELECT id, name FROM project_codespaces WHERE project_id='$projectID' AND slug='$codespaceSlug' LIMIT 1"));
    if (!$cs) {
        showJSON(['error' => 'Codespace not found']);
        exit;
    }
    $codespaceId = (int) $cs['id'];

    $name = escape_string($_POST['name'] ?? $cs['name']);
    $description = escape_string($_POST['description'] ?? '');
    $rateLimit = (int) ($_POST['rate_limit'] ?? 60);
    $slug = trim(strtolower(preg_replace('/[^a-zA-Z0-9]+/', '-', $_POST['slug'] ?? ($projectName . '-' . $codespaceSlug))), '-');

    $exists = fetch_assoc(query("SELECT id, codespace_id FROM cms_apis WHERE slug='$slug' LIMIT 1"));
    if ($exists && (int) $exists['codespace_id'] !== $codespaceId) {
        showJSON(['error' => 'API slug already taken, choose another']);
        exit;
    }

    query("INSERT INTO cms_apis (name, slug, description, category, version, endpoint_base, auth_required, rate_limit_default, is_active, source_type, codespace_id)
           VALUES ('$name', '$slug', '$description', 'codespace', 'v1', '/', 1, '$rateLimit', 1, 'codespace', '$codespaceId')
           ON DUPLICATE KEY UPDATE name='$name', description='$description', rate_limit_default='$rateLimit', is_active=1, source_type='codespace', codespace_id='$codespaceId'");

    showJSON(['success' => true, 'slug' => $slug, 'gateway_url' => 'https://gw.fringelo.com/' . $slug]);
}

elseif (isset($_POST['unpublishCodespaceAPI']) && isset($_POST['project']) && isset($_POST['codespace'])) {
    $projectName = escape_string($_POST['project']);
    $codespaceSlug = escape_string($_POST['codespace']);
    $projectID = getProjectID($projectName);

    if (!checkUserProjectPermission($userID, $projectID)) {
        showJSON(['error' => 'No permission for this project']);
        exit;
    }

    $cs = fetch_assoc(query("SELECT id FROM project_codespaces WHERE project_id='$projectID' AND slug='$codespaceSlug' LIMIT 1"));
    if (!$cs) {
        showJSON(['error' => 'Codespace not found']);
        exit;
    }
    $codespaceId = (int) $cs['id'];
    query("UPDATE cms_apis SET is_active=0 WHERE source_type='codespace' AND codespace_id='$codespaceId'");
    showJSON(['success' => true, 'message' => 'Codespace API unpublished']);
}

else {
    showJSON(['error' => 'Invalid request']);
}
