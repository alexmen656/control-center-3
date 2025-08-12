<?php
function generateApiKey($projectID = null) {
    $suffix = $projectID ? '_' . $projectID : '_' . time();
    return 'cms_' . bin2hex(random_bytes(16)) . $suffix;
}

function formatEndpointData($endpoint) {
    return [
        'id' => $endpoint['id'],
        'name' => $endpoint['name'],
        'method' => $endpoint['method'],
        'endpoint' => $endpoint['endpoint'],
        'description' => $endpoint['description'],
        'parameters' => json_decode($endpoint['parameters'] ?? '{}', true) ?: [],
        'response_schema' => json_decode($endpoint['response_schema'] ?? '{}', true) ?: [],
        'example_request' => json_decode($endpoint['example_request'] ?? '{}', true) ?: [],
        'example_response' => json_decode($endpoint['example_response'] ?? '{}', true) ?: [],
        'requires_auth' => $endpoint['requires_auth'] ?? null
    ];
}

function formatAvailableApiData($api) {
    return [
        'id' => $api['id'],
        'name' => $api['name'],
        'slug' => $api['slug'],
        'description' => $api['description'],
        'icon' => $api['icon'],
        'category' => $api['category'],
        'version' => $api['version'],
        'endpoint_base' => $api['endpoint_base'],
        'auth_required' => $api['auth_required'],
        'rate_limit_default' => $api['rate_limit_default'],
        'documentation_url' => $api['documentation_url']
    ];
}

function formatProjectSubscriptionData($sub) {
    return [
        'subscription_id' => $sub['id'],
        'api_id' => $sub['api_id'],
        'name' => $sub['name'],
        'slug' => $sub['slug'],
        'description' => $sub['description'],
        'icon' => $sub['icon'],
        'category' => $sub['category'],
        'endpoint_base' => $sub['endpoint_base'],
        'api_key' => substr($sub['api_key'], 0, 8) . '...',
        'rate_limit' => $sub['rate_limit'],
        'usage_count' => $sub['usage_count'],
        'last_used' => $sub['last_used'],
        'documentation_url' => $sub['documentation_url']
    ];
}

function formatUsageData($day) {
    return [
        'date' => $day['date'],
        'requests' => intval($day['requests']),
        'avg_response_time' => floatval($day['avg_response_time']),
        'successful_requests' => intval($day['successful_requests']),
        'success_rate' => $day['requests'] > 0 ? round(($day['successful_requests'] / $day['requests']) * 100, 2) : 0
    ];
}

function calculateUsageStats($subscriptionId) {
    if (!$subscriptionId) {
        return [
            'totalRequests' => 0,
            'avgResponseTime' => 0,
            'successRate' => 0,
            'requestsToday' => 0
        ];
    }

    $stats_query = query("
        SELECT 
            COUNT(*) as total_requests,
            AVG(response_time) as avg_response_time,
            (COUNT(CASE WHEN status_code >= 200 AND status_code < 300 THEN 1 END) * 100.0 / COUNT(*)) as success_rate,
            COUNT(CASE WHEN DATE(timestamp) = CURDATE() THEN 1 END) as requests_today
        FROM cms_api_usage_logs
        WHERE subscription_id='$subscriptionId'
    ");

    if (mysqli_num_rows($stats_query) > 0) {
        $stats = fetch_assoc($stats_query);
        return [
            'totalRequests' => $stats['total_requests'] ?: 0,
            'avgResponseTime' => round($stats['avg_response_time'] ?: 0),
            'successRate' => round($stats['success_rate'] ?: 0, 1),
            'requestsToday' => $stats['requests_today'] ?: 0
        ];
    }

    return [
        'totalRequests' => 0,
        'avgResponseTime' => 0,
        'successRate' => 0,
        'requestsToday' => 0
    ];
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

function formatCodespaceApiData($api) {
    return [
        'subscription_id' => $api['subscription_id'],
        'activation_id' => $api['activation_id'] ?? null,
        'api_id' => $api['api_id'],
        'name' => $api['name'],
        'slug' => $api['slug'],
        'description' => $api['description'],
        'icon' => $api['icon'],
        'category' => $api['category'],
        'endpoint_base' => $api['endpoint_base'],
        'documentation_url' => $api['documentation_url'],
        'is_active' => $api['is_active'] ?? false,
        'api_key' => $api['codespace_api_key'] ?: substr($api['project_api_key'], 0, 8) . '...',
        'rate_limit' => $api['rate_limit']
    ];
}

function calculateCodespaceUsageStats($activationId) {
    if (!$activationId) {
        return [
            'totalRequests' => 0,
            'avgResponseTime' => 0,
            'successRate' => 0,
            'requestsToday' => 0
        ];
    }

    $stats_query = query("
        SELECT 
            COUNT(*) as total_requests,
            AVG(response_time) as avg_response_time,
            (COUNT(CASE WHEN status_code >= 200 AND status_code < 300 THEN 1 END) * 100.0 / COUNT(*)) as success_rate,
            COUNT(CASE WHEN DATE(timestamp) = CURDATE() THEN 1 END) as requests_today
        FROM cms_api_usage_logs
        WHERE activation_id='$activationId'
    ");

    if (mysqli_num_rows($stats_query) > 0) {
        $stats = fetch_assoc($stats_query);
        return [
            'totalRequests' => $stats['total_requests'] ?: 0,
            'avgResponseTime' => round($stats['avg_response_time'] ?: 0),
            'successRate' => round($stats['success_rate'] ?: 0, 1),
            'requestsToday' => $stats['requests_today'] ?: 0
        ];
    }

    return [
        'totalRequests' => 0,
        'avgResponseTime' => 0,
        'successRate' => 0,
        'requestsToday' => 0
    ];
}

function copyAPISDKToCodespace($projectName, $codespaceSlug, $apiSlug, $userID)
{
    $codespaceDir = __DIR__ . "/../data/projects/" . $userID . "/" . $projectName . "/" . $codespaceSlug;
    $apisDir = $codespaceDir . '/.monaco_apis';

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

    updateCodespaceAPIBundle($projectName, $codespaceSlug, $userID);
    return true;
}

function removeAPISDKFromCodespace($projectName, $codespaceSlug, $apiSlug, $userID)
{
    $codespaceDir = __DIR__ . "/../data/projects/" . $userID . "/" . $projectName . "/" . $codespaceSlug;
    $apisDir = $codespaceDir . '/.monaco_apis';
    $targetFile = $apisDir . '/' . $apiSlug . 'SDK.js';

    if (file_exists($targetFile)) {
        unlink($targetFile);
    }

    updateCodespaceAPIBundle($projectName, $codespaceSlug, $userID);
    return true;
}

function updateCodespaceAPIBundle($projectName, $codespaceSlug, $userID)
{
    $codespaceDir = __DIR__ . "/../data/projects/" . $userID . "/" . $projectName . "/" . $codespaceSlug;
    $apisDir = $codespaceDir . '/.monaco_apis';

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
// This file contains all activated APIs for this codespace

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
// No APIs are currently activated for this codespace
// Activate APIs in the sidebar to get access

export default {};
';
    }

    file_put_contents($apisDir . '/index.js', $indexContent);

    return true;
}