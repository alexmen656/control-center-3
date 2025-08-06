<?php
include 'head.php';
include 'apis_helper.php';

if (isset($_POST['getAvailableApis'])) {
    $apis = query("SELECT * FROM cms_apis WHERE is_active=1 ORDER BY category, name ASC");
    $json = [];
    foreach ($apis as $api) {
        $json[] = formatAvailableApiData($api);
    }
    showJSON($json);
} elseif (isset($_POST['getApiDetails']) && isset($_POST['api_slug']) && isset($_POST['project'])) {
    $apiSlug = escape_string($_POST['api_slug']);
    $projectName = escape_string($_POST['project']);
    $projectID = getProjectID($projectName);

    $api_query = query("
                SELECT ca.*, pas.id as subscription_id, pas.api_key, pas.rate_limit, pas.usage_count, pas.last_used, pas.is_enabled
                FROM cms_apis ca
                LEFT JOIN project_api_subscriptions pas ON ca.id = pas.api_id AND pas.projectID='$projectID'
                WHERE ca.slug='$apiSlug'
            ");

    if (mysqli_num_rows($api_query) == 0) {
        header('HTTP/1.1 404 Not Found');
        echo json_encode(['error' => 'API not found']);
        exit;
    }

    $api = fetch_assoc($api_query);
    $endpoints = query("SELECT * FROM cms_api_endpoints WHERE api_id='" . $api['id'] . "' ORDER BY endpoint ASC");
    $api['endpoints'] = [];

    foreach ($endpoints as $endpoint) {
        $api['endpoints'][] = formatEndpointData($endpoint);
    }

    $api['usage_stats'] = calculateUsageStats($api['subscription_id']);

    $activity_query = query("
                SELECT method, path, status_code, response_time, timestamp
                FROM cms_api_usage_logs
                WHERE subscription_id='" . $api['subscription_id'] . "'
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

    showJSON($api);
} elseif (isset($_POST['updateApiSettings']) && isset($_POST['subscription_id'])) {
    $subscriptionId = intval($_POST['subscription_id']);
    $rateLimit = intval($_POST['rate_limit']);
    $isEnabled = $_POST['is_enabled'] === 'true' ? 1 : 0;

    query("UPDATE project_api_subscriptions SET rate_limit='$rateLimit', is_enabled='$isEnabled' WHERE id='$subscriptionId'");
    echo json_encode(['success' => true]);
} elseif (isset($_POST['regenerateApiKey']) && isset($_POST['subscription_id'])) {
    $subscriptionId = intval($_POST['subscription_id']);
    $newApiKey = generateApiKey();

    query("UPDATE project_api_subscriptions SET api_key='$newApiKey' WHERE id='$subscriptionId'");
    echo json_encode(['success' => true, 'api_key' => $newApiKey]);
} elseif (isset($_POST['getProjectApis']) && isset($_POST['project'])) {
    $projectName = escape_string($_POST['project']);
    $projectID = getProjectID($projectName);

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
} elseif (isset($_POST['getApiDetails']) && isset($_POST['apiId'])) {
    $apiId = escape_string($_POST['apiId']);
    $api = query("SELECT * FROM cms_apis WHERE id='$apiId'");

    if (mysqli_num_rows($api) == 1) {
        $apiData = fetch_assoc($api);

        // Get endpoints for this API
        $endpoints = query("SELECT * FROM cms_api_endpoints WHERE api_id='$apiId' AND is_active=1 ORDER BY method, endpoint");
        $apiEndpoints = [];
        foreach ($endpoints as $endpoint) {
            $apiEndpoints[] = formatEndpointData($endpoint);
        }

        $apiData['endpoints'] = $apiEndpoints;
        showJSON($apiData);
    } else {
        showJSON(['error' => 'API not found']);
    }
} elseif (isset($_POST['subscribeToApi']) && isset($_POST['project']) && isset($_POST['apiId'])) {
    $projectName = escape_string($_POST['project']);
    $projectID = getProjectID($projectName);
    $apiId = escape_string($_POST['apiId']);

    $existing = query("SELECT * FROM project_api_subscriptions WHERE projectID='$projectID' AND api_id='$apiId'");
    if (mysqli_num_rows($existing) > 0) {
        showJSON(['error' => 'Already subscribed to this API']);
        exit;
    }

    $apiKey = generateApiKey($projectID);
    $api = fetch_assoc(query("SELECT rate_limit_default, slug FROM cms_apis WHERE id='$apiId'"));
    $rateLimit = $api['rate_limit_default'];
    $apiSlug = $api['slug'];

    $insertSub = query("INSERT INTO project_api_subscriptions (projectID, api_id, api_key, rate_limit) 
                              VALUES ('$projectID', '$apiId', '$apiKey', '$rateLimit')");

    if ($insertSub) {
        $copyResult = copyAPISDKToProject($projectName, $apiSlug, $userID);
        showJSON($copyResult ? ['success' => true, 'message' => 'Successfully subscribed to API and SDK installed', 'api_key' => $apiKey] : ['success' => true, 'message' => 'Subscribed to API but SDK copy failed', 'api_key' => $apiKey]);
    } else {
        showJSON(['error' => 'Failed to subscribe to API']);
    }
} elseif (isset($_POST['unsubscribeFromApi']) && isset($_POST['subscriptionId'])) {
    $subscriptionId = escape_string($_POST['subscriptionId']);

    $subInfo = fetch_assoc(query("
                SELECT pas.projectID, ca.slug, p.link as project_name
                FROM project_api_subscriptions pas
                JOIN cms_apis ca ON pas.api_id = ca.id
                JOIN projects p ON pas.projectID = p.projectID
                WHERE pas.id='$subscriptionId'
            "));

    $deleteSub = query("DELETE FROM project_api_subscriptions WHERE id='$subscriptionId'");

    if ($deleteSub && $subInfo) {
        $removeResult = removeAPISDKFromProject($subInfo['project_name'], $subInfo['slug'], $userID);
        showJSON($removeResult ? ['success' => true, 'message' => 'Successfully unsubscribed from API and SDK removed'] : ['success' => true, 'message' => 'Unsubscribed from API but SDK removal failed']);
    } else {
        showJSON(['error' => 'Failed to unsubscribe from API']);
    }
} elseif (isset($_POST['updateSubscription']) && isset($_POST['subscriptionId'])) {
    $subscriptionId = escape_string($_POST['subscriptionId']);
    $rateLimit = escape_string($_POST['rateLimit'] ?? 100);
    $isEnabled = isset($_POST['isEnabled']) ? 1 : 0;

    $updateSub = query("UPDATE project_api_subscriptions SET 
                              rate_limit='$rateLimit', 
                              is_enabled='$isEnabled'
                              WHERE id='$subscriptionId'");

    showJSON($updateSub ? ['success' => true, 'message' => 'Subscription updated successfully'] : ['error' => 'Failed to update subscription']);
} elseif (isset($_POST['getApiUsage']) && isset($_POST['subscriptionId'])) {
    $subscriptionId = escape_string($_POST['subscriptionId']);
    $days = escape_string($_POST['days'] ?? 30);

    $usage = query("
                SELECT 
                    DATE(created_at) as date,
                    COUNT(*) as requests,
                    AVG(response_time_ms) as avg_response_time,
                    SUM(CASE WHEN response_status >= 200 AND response_status < 300 THEN 1 ELSE 0 END) as successful_requests
                FROM api_usage_logs 
                WHERE subscription_id='$subscriptionId' 
                AND created_at >= DATE_SUB(NOW(), INTERVAL $days DAY)
                GROUP BY DATE(created_at)
                ORDER BY date DESC
            ");

    $usageData = [];
    foreach ($usage as $day) {
        $usageData[] = formatUsageData($day);
    }

    showJSON($usageData);
} elseif (isset($_POST['regenerateApiKey']) && isset($_POST['subscriptionId'])) {
    $subscriptionId = escape_string($_POST['subscriptionId']);
    $sub = fetch_assoc(query("SELECT projectID FROM project_api_subscriptions WHERE id='$subscriptionId'"));
    $projectID = $sub['projectID'];

    $newApiKey = generateApiKey($projectID);
    $updateKey = query("UPDATE project_api_subscriptions SET api_key='$newApiKey' WHERE id='$subscriptionId'");

    showJSON($updateKey ? ['success' => true, 'message' => 'API key regenerated successfully', 'api_key' => $newApiKey] : ['error' => 'Failed to regenerate API key']);
} else {
    showJSON(['error' => 'Invalid request']);
}