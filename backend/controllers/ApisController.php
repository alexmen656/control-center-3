<?php

require_once __DIR__ . '/../helpers/apis.php';

class ApisController
{
    
    public function getAvailable(Request $request, Response $response): void
    {
        $apis = query("SELECT * FROM cms_apis WHERE is_active=1 ORDER BY category, name ASC");
        $json = [];
        foreach ($apis as $api) {
            $json[] = formatAvailableApiData($api);
        }
        $response->json($json);
    }

    
    public function getDetailsBySlug(Request $request, Response $response): void
    {
        $apiSlug = escape_string($request->params['slug']);
        $projectName = escape_string($request->input('project', ''));

        if (empty($projectName)) {
            $response->error('project parameter is required', 400);
            return;
        }

        $projectID = getProjectID($projectName);

        $api_query = query("
            SELECT ca.*, pas.id as subscription_id, pas.api_key, pas.rate_limit, pas.usage_count, pas.last_used, pas.is_enabled
            FROM cms_apis ca
            LEFT JOIN project_api_subscriptions pas ON ca.id = pas.api_id AND pas.projectID='$projectID'
            WHERE ca.slug='$apiSlug'
        ");

        if (mysqli_num_rows($api_query) == 0) {
            $response->error('API not found', 404);
            return;
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

        $response->json($api);
    }

    
    public function getDetailsById(Request $request, Response $response): void
    {
        $apiId = escape_string($request->params['id']);
        $api = query("SELECT * FROM cms_apis WHERE id='$apiId'");

        if (mysqli_num_rows($api) != 1) {
            $response->error('API not found', 404);
            return;
        }

        $apiData = fetch_assoc($api);

        $endpoints = query("SELECT * FROM cms_api_endpoints WHERE api_id='$apiId' AND is_active=1 ORDER BY method, endpoint");
        $apiEndpoints = [];
        foreach ($endpoints as $endpoint) {
            $apiEndpoints[] = formatEndpointData($endpoint);
        }

        $apiData['endpoints'] = $apiEndpoints;
        $response->json($apiData);
    }

    
    public function getProjectApis(Request $request, Response $response): void
    {
        $projectName = escape_string($request->input('project', ''));

        if (empty($projectName)) {
            $response->error('project parameter is required', 400);
            return;
        }

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
        $response->json($json);
    }

    
    public function subscribe(Request $request, Response $response): void
    {
        $projectName = escape_string($request->input('project', ''));
        $apiId = escape_string($request->input('apiId', ''));

        if (empty($projectName) || empty($apiId)) {
            $response->error('project and apiId are required', 400);
            return;
        }

        $projectID = getProjectID($projectName);

        $existing = query("SELECT * FROM project_api_subscriptions WHERE projectID='$projectID' AND api_id='$apiId'");
        if (mysqli_num_rows($existing) > 0) {
            $response->error('Already subscribed to this API', 409);
            return;
        }

        $apiKey = generateApiKey($projectID);
        $api = fetch_assoc(query("SELECT rate_limit_default, slug FROM cms_apis WHERE id='$apiId'"));
        $rateLimit = $api['rate_limit_default'];
        $apiSlug = $api['slug'];

        $insertSub = query("INSERT INTO project_api_subscriptions (projectID, api_id, api_key, rate_limit)
            VALUES ('$projectID', '$apiId', '$apiKey', '$rateLimit')");

        if ($insertSub) {
            $copyResult = copyAPISDKToProject($projectName, $apiSlug, $request->userID);
            $response->success([
                'api_key' => $apiKey,
                'message' => $copyResult
                    ? 'Successfully subscribed to API and SDK installed'
                    : 'Subscribed to API but SDK copy failed'
            ]);
        } else {
            $response->error('Failed to subscribe to API', 500);
        }
    }

    
    public function unsubscribe(Request $request, Response $response): void
    {
        $subscriptionId = escape_string($request->params['id']);

        $subInfo = fetch_assoc(query("
            SELECT pas.projectID, ca.slug, p.link as project_name
            FROM project_api_subscriptions pas
            JOIN cms_apis ca ON pas.api_id = ca.id
            JOIN projects p ON pas.projectID = p.projectID
            WHERE pas.id='$subscriptionId'
        "));

        $deleteSub = query("DELETE FROM project_api_subscriptions WHERE id='$subscriptionId'");

        if ($deleteSub && $subInfo) {
            $removeResult = removeAPISDKFromProject($subInfo['project_name'], $subInfo['slug'], $request->userID);
            $response->success([
                'message' => $removeResult
                    ? 'Successfully unsubscribed from API and SDK removed'
                    : 'Unsubscribed from API but SDK removal failed'
            ]);
        } else {
            $response->error('Failed to unsubscribe from API', 500);
        }
    }

    
    public function updateSubscription(Request $request, Response $response): void
    {
        $subscriptionId = escape_string($request->params['id']);
        $rateLimit = escape_string($request->input('rateLimit', '100'));
        $isEnabled = $request->input('isEnabled') ? 1 : 0;

        if (query("UPDATE project_api_subscriptions SET rate_limit='$rateLimit', is_enabled='$isEnabled' WHERE id='$subscriptionId'")) {
            $response->success([], 'Subscription updated successfully');
        } else {
            $response->error('Failed to update subscription', 500);
        }
    }

    
    public function updateSettings(Request $request, Response $response): void
    {
        $subscriptionId = intval($request->params['id']);
        $rateLimit = intval($request->input('rate_limit', 100));
        $isEnabled = $request->input('is_enabled') === 'true' ? 1 : 0;

        query("UPDATE project_api_subscriptions SET rate_limit='$rateLimit', is_enabled='$isEnabled' WHERE id='$subscriptionId'");
        $response->success([], 'Settings updated');
    }

    
    public function getUsage(Request $request, Response $response): void
    {
        $subscriptionId = escape_string($request->params['id']);
        $days = escape_string($request->input('days', '30'));

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
        $response->json($usageData);
    }

    
    public function regenerateKey(Request $request, Response $response): void
    {
        $subscriptionId = escape_string($request->params['id']);
        $sub = fetch_assoc(query("SELECT projectID FROM project_api_subscriptions WHERE id='$subscriptionId'"));

        if (!$sub) {
            $response->error('Subscription not found', 404);
            return;
        }

        $newApiKey = generateApiKey($sub['projectID']);

        if (query("UPDATE project_api_subscriptions SET api_key='$newApiKey' WHERE id='$subscriptionId'")) {
            $response->success(['api_key' => $newApiKey], 'API key regenerated successfully');
        } else {
            $response->error('Failed to regenerate API key', 500);
        }
    }
}
