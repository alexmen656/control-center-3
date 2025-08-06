<?php
include 'head.php';

$headers = getRequestHeaders();
if (isset($headers['Authorization'])) {
    $token = $headers['Authorization'];
    $payload = SimpleJWT::verify($token, $jwt_secret);
    if (!$payload || empty($payload['sub'])) {
        header('HTTP/1.1 401 Unauthorized');
        echo json_encode(['error' => 'No valid token']);
        exit;
    }
    $userID = intval($payload['sub']);

    if (isset($_POST['getAvailableApis'])) {
        $apis = query("SELECT * FROM cms_apis WHERE is_active=1 ORDER BY category, name ASC");
        $json = [];
        $i = 0;
        foreach ($apis as $api) {
            $json[$i]['id'] = $api['id'];
            $json[$i]['name'] = $api['name'];
            $json[$i]['slug'] = $api['slug'];
            $json[$i]['description'] = $api['description'];
            $json[$i]['icon'] = $api['icon'];
            $json[$i]['category'] = $api['category'];
            $json[$i]['version'] = $api['version'];
            $json[$i]['endpoint_base'] = $api['endpoint_base'];
            $json[$i]['auth_required'] = $api['auth_required'];
            $json[$i]['rate_limit_default'] = $api['rate_limit_default'];
            $json[$i]['documentation_url'] = $api['documentation_url'];
            $i++;
        }
        echo echoJSON($json);
    } elseif (isset($_POST['getApiDetails']) && isset($_POST['api_slug']) && isset($_POST['project'])) {
        $apiSlug = escape_string($_POST['api_slug']);
        $projectName = escape_string($_POST['project']);
        $projectID = fetch_assoc(query("SELECT * FROM projects WHERE link='$projectName'"))['projectID'];

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
            $api['endpoints'][] = [
                'id' => $endpoint['id'],
                'name' => $endpoint['name'],
                'method' => $endpoint['method'],
                'endpoint' => $endpoint['endpoint'],
                'description' => $endpoint['description'],
                'parameters' => json_decode($endpoint['parameters'], true) ?: [],
                'response_schema' => json_decode($endpoint['response_schema'], true) ?: [],
                'example_request' => json_decode($endpoint['example_request'], true) ?: [],
                'example_response' => json_decode($endpoint['example_response'], true) ?: []
            ];
        }

        $stats_query = query("
                SELECT 
                    COUNT(*) as total_requests,
                    AVG(response_time) as avg_response_time,
                    (COUNT(CASE WHEN status_code >= 200 AND status_code < 300 THEN 1 END) * 100.0 / COUNT(*)) as success_rate,
                    COUNT(CASE WHEN DATE(timestamp) = CURDATE() THEN 1 END) as requests_today
                FROM cms_api_usage_logs
                WHERE subscription_id='" . $api['subscription_id'] . "'
            ");

        if (mysqli_num_rows($stats_query) > 0) {
            $stats = fetch_assoc($stats_query);
            $api['usage_stats'] = [
                'totalRequests' => $stats['total_requests'] ?: 0,
                'avgResponseTime' => round($stats['avg_response_time'] ?: 0),
                'successRate' => round($stats['success_rate'] ?: 0, 1),
                'requestsToday' => $stats['requests_today'] ?: 0
            ];
        } else {
            $api['usage_stats'] = [
                'totalRequests' => 0,
                'avgResponseTime' => 0,
                'successRate' => 0,
                'requestsToday' => 0
            ];
        }

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

        echo echoJSON($api);
    }

    // Update API subscription settings
    elseif (isset($_POST['updateApiSettings']) && isset($_POST['subscription_id'])) {
        $subscriptionId = intval($_POST['subscription_id']);
        $rateLimit = intval($_POST['rate_limit']);
        $isEnabled = $_POST['is_enabled'] === 'true' ? 1 : 0;

        query("UPDATE project_api_subscriptions SET rate_limit='$rateLimit', is_enabled='$isEnabled' WHERE id='$subscriptionId'");
        echo json_encode(['success' => true]);
    }

    // Regenerate API key
    elseif (isset($_POST['regenerateApiKey']) && isset($_POST['subscription_id'])) {
        $subscriptionId = intval($_POST['subscription_id']);
        $newApiKey = 'cms_' . bin2hex(random_bytes(16)) . '_' . time();

        query("UPDATE project_api_subscriptions SET api_key='$newApiKey' WHERE id='$subscriptionId'");
        echo json_encode(['success' => true, 'api_key' => $newApiKey]);
    }

    // Get project's subscribed APIs
    elseif (isset($_POST['getProjectApis']) && isset($_POST['project'])) {
        $projectName = escape_string($_POST['project']);
        $projectID = fetch_assoc(query("SELECT * FROM projects WHERE link='$projectName'"))['projectID'];
        echo $projectID;
        $subscriptions = query("
                SELECT pas.*, ca.name, ca.slug, ca.description, ca.icon, ca.category, ca.endpoint_base, ca.documentation_url
                FROM project_api_subscriptions pas
                JOIN cms_apis ca ON pas.api_id = ca.id
                WHERE pas.projectID='$projectID' AND pas.is_enabled=1
                ORDER BY ca.category, ca.name ASC
            ");

        $json = [];
        $i = 0;
        foreach ($subscriptions as $sub) {
            $json[$i]['subscription_id'] = $sub['id'];
            $json[$i]['api_id'] = $sub['api_id'];
            $json[$i]['name'] = $sub['name'];
            $json[$i]['slug'] = $sub['slug'];
            $json[$i]['description'] = $sub['description'];
            $json[$i]['icon'] = $sub['icon'];
            $json[$i]['category'] = $sub['category'];
            $json[$i]['endpoint_base'] = $sub['endpoint_base'];
            $json[$i]['api_key'] = substr($sub['api_key'], 0, 8) . '...'; // Show only first 8 chars
            $json[$i]['rate_limit'] = $sub['rate_limit'];
            $json[$i]['usage_count'] = $sub['usage_count'];
            $json[$i]['last_used'] = $sub['last_used'];
            $json[$i]['documentation_url'] = $sub['documentation_url'];
            $i++;
        }
        echo echoJSON($json);
    }

    // Get specific API details with endpoints
    elseif (isset($_POST['getApiDetails']) && isset($_POST['apiId'])) {
        $apiId = escape_string($_POST['apiId']);
        $api = query("SELECT * FROM cms_apis WHERE id='$apiId'");

        if (mysqli_num_rows($api) == 1) {
            $apiData = fetch_assoc($api);

            // Get endpoints for this API
            $endpoints = query("SELECT * FROM cms_api_endpoints WHERE api_id='$apiId' AND is_active=1 ORDER BY method, endpoint");
            $apiEndpoints = [];
            foreach ($endpoints as $endpoint) {
                $apiEndpoints[] = [
                    'id' => $endpoint['id'],
                    'name' => $endpoint['name'],
                    'method' => $endpoint['method'],
                    'endpoint' => $endpoint['endpoint'],
                    'description' => $endpoint['description'],
                    'parameters' => json_decode($endpoint['parameters'] ?? '{}'),
                    'response_schema' => json_decode($endpoint['response_schema'] ?? '{}'),
                    'example_request' => json_decode($endpoint['example_request'] ?? '{}'),
                    'example_response' => json_decode($endpoint['example_response'] ?? '{}'),
                    'requires_auth' => $endpoint['requires_auth']
                ];
            }

            $apiData['endpoints'] = $apiEndpoints;
            echo echoJSON($apiData);
        } else {
            echo echoJSON(['error' => 'API not found']);
        }
    }

    // Subscribe project to an API
    elseif (isset($_POST['subscribeToApi']) && isset($_POST['project']) && isset($_POST['apiId'])) {
        $projectName = escape_string($_POST['project']);
        $projectID = fetch_assoc(query("SELECT * FROM projects WHERE link='$projectName'"))['projectID'];
        $apiId = escape_string($_POST['apiId']);

        // Check if already subscribed
        $existing = query("SELECT * FROM project_api_subscriptions WHERE projectID='$projectID' AND api_id='$apiId'");
        if (mysqli_num_rows($existing) > 0) {
            echo echoJSON(['error' => 'Already subscribed to this API']);
            exit;
        }

        $apiKey = 'cms_' . bin2hex(random_bytes(16)) . '_' . $projectID;
        $api = fetch_assoc(query("SELECT rate_limit_default, slug FROM cms_apis WHERE id='$apiId'"));
        $rateLimit = $api['rate_limit_default'];
        $apiSlug = $api['slug'];

        $insertSub = query("INSERT INTO project_api_subscriptions (projectID, api_id, api_key, rate_limit) 
                              VALUES ('$projectID', '$apiId', '$apiKey', '$rateLimit')");

        if ($insertSub) {
            $copyResult = copyAPISDKToProject($projectName, $apiSlug, $userID);

            if ($copyResult) {
                echo echoJSON(['success' => true, 'message' => 'Successfully subscribed to API and SDK installed', 'api_key' => $apiKey]);
            } else {
                echo echoJSON(['success' => true, 'message' => 'Subscribed to API but SDK copy failed', 'api_key' => $apiKey]);
            }
        } else {
            echo echoJSON(['error' => 'Failed to subscribe to API']);
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

            if ($removeResult) {
                echo echoJSON(['success' => true, 'message' => 'Successfully unsubscribed from API and SDK removed']);
            } else {
                echo echoJSON(['success' => true, 'message' => 'Unsubscribed from API but SDK removal failed']);
            }
        } else {
            echo echoJSON(['error' => 'Failed to unsubscribe from API']);
        }
    } elseif (isset($_POST['updateSubscription']) && isset($_POST['subscriptionId'])) {
        $subscriptionId = escape_string($_POST['subscriptionId']);
        $rateLimit = escape_string($_POST['rateLimit'] ?? 100);
        $isEnabled = isset($_POST['isEnabled']) ? 1 : 0;

        $updateSub = query("UPDATE project_api_subscriptions SET 
                              rate_limit='$rateLimit', 
                              is_enabled='$isEnabled'
                              WHERE id='$subscriptionId'");

        if ($updateSub) {
            echo echoJSON(['success' => true, 'message' => 'Subscription updated successfully']);
        } else {
            echo echoJSON(['error' => 'Failed to update subscription']);
        }
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
            $usageData[] = [
                'date' => $day['date'],
                'requests' => intval($day['requests']),
                'avg_response_time' => floatval($day['avg_response_time']),
                'successful_requests' => intval($day['successful_requests']),
                'success_rate' => $day['requests'] > 0 ? round(($day['successful_requests'] / $day['requests']) * 100, 2) : 0
            ];
        }

        echo echoJSON($usageData);
    } elseif (isset($_POST['regenerateApiKey']) && isset($_POST['subscriptionId'])) {
        $subscriptionId = escape_string($_POST['subscriptionId']);

        $sub = fetch_assoc(query("SELECT projectID FROM project_api_subscriptions WHERE id='$subscriptionId'"));
        $projectID = $sub['projectID'];

        $newApiKey = 'cms_' . bin2hex(random_bytes(16)) . '_' . $projectID;

        $updateKey = query("UPDATE project_api_subscriptions SET api_key='$newApiKey' WHERE id='$subscriptionId'");

        if ($updateKey) {
            echo echoJSON(['success' => true, 'message' => 'API key regenerated successfully', 'api_key' => $newApiKey]);
        } else {
            echo echoJSON(['error' => 'Failed to regenerate API key']);
        }
    } else {
        echo echoJSON(['error' => 'Invalid request']);
    }
} else {
    echo echoJSON(['error' => 'No authorization token provided']);
}

function copyAPISDKToProject($projectName, $apiSlug, $userID)
{
    $projectDir = __DIR__ . "/../data/projects/" . $userID . "/" . $projectName;
    $apisDir = $projectDir . '/.monaco_apis';

    if (!is_dir($apisDir)) {
        mkdir($apisDir, 0777, true);
    }

    $sourceFile = __DIR__ . '/apis_sdk/' . $apiSlug . 'SDK.js';
    $targetFile = $apisDir . '/' . $apiSlug . 'SDK.js';

    if (!file_exists($sourceFile)) {
        return false;
    }

    if (!copy($sourceFile, $targetFile)) {
        return false;
    }

    updateAPIBundle($projectName, $userID);
    return true;
}

function removeAPISDKFromProject($projectName, $apiSlug, $userID)
{
    $projectDir = __DIR__ . "/../data/projects/" . $userID . "/" . $projectName;
    $apisDir = $projectDir . '/.monaco_apis';
    $targetFile = $apisDir . '/' . $apiSlug . 'SDK.js';

    if (file_exists($targetFile)) {
        unlink($targetFile);
    }

    updateAPIBundle($projectName, $userID);
    return true;
}

function updateAPIBundle($projectName, $userID)
{
    $projectDir = __DIR__ . "/../data/projects/" . $userID . "/" . $projectName;
    $apisDir = $projectDir . '/.monaco_apis';

    if (!is_dir($apisDir)) {
        return false;
    }

    $sdkFiles = glob($apisDir . '/*SDK.js');
    $installedAPIs = [];

    foreach ($sdkFiles as $file) {
        $filename = basename($file, 'SDK.js');
        $installedAPIs[] = $filename;
    }

    $imports = '';
    $exports = '';

    foreach ($installedAPIs as $apiSlug) {
        $className = ucfirst($apiSlug) . 'API';
        $imports .= "import {$className} from './{$apiSlug}SDK.js';\n";
        $exports .= "  {$className},\n";
    }

    if (count($installedAPIs) > 0) {
        $indexContent = '// CMS APIs Integration - Auto-generated
            // This file contains all subscribed APIs for your project

            ' . $imports . '
            // Export all APIs
            export {
            ' . $exports . '};

            // Default export for convenience
            export default {
            ' . $exports . '};

            // Usage example:
            // import { ' . implode(', ', array_map(function ($api) {
            return ucfirst($api) . 'API';
        }, $installedAPIs)) . ' } from \'apis\';
            ';
    } else {
        $indexContent = '// CMS APIs Integration
            // No APIs are currently subscribed for this project
            // Subscribe to APIs in the main Control Center to get access

            export default {};
        ';
    }

    file_put_contents($apisDir . '/index.js', $indexContent);

    return true;
}
