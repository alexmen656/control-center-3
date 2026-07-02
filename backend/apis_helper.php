<?php
function generateApiKey($projectID = null)
{
    $suffix = $projectID ? '_' . $projectID : '_' . time();
    return 'cms_' . bin2hex(random_bytes(16)) . $suffix;
}

function formatEndpointData($endpoint)
{
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

function formatAvailableApiData($api)
{
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

function formatProjectSubscriptionData($sub)
{
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

function formatUsageData($day)
{
    return [
        'date' => $day['date'],
        'requests' => intval($day['requests']),
        'avg_response_time' => floatval($day['avg_response_time']),
        'successful_requests' => intval($day['successful_requests']),
        'success_rate' => $day['requests'] > 0 ? round(($day['successful_requests'] / $day['requests']) * 100, 2) : 0
    ];
}

function calculateUsageStats($subscriptionId)
{
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

function logApiCall($subscriptionId, array $data)
{
    $subscriptionId = intval($subscriptionId);
    if (!$subscriptionId) {
        return false;
    }

    $method        = escape_string($data['method'] ?? 'GET');
    $path          = escape_string($data['path'] ?? '/');
    $statusCode    = intval($data['status_code'] ?? 0);
    $responseTime  = intval($data['response_time'] ?? 0);
    $endpointId    = isset($data['endpoint_id']) ? intval($data['endpoint_id']) : null;
    $ip            = escape_string($data['ip_address'] ?? ($_SERVER['REMOTE_ADDR'] ?? ''));
    $userAgent     = escape_string($data['user_agent'] ?? ($_SERVER['HTTP_USER_AGENT'] ?? ''));
    $requestQuery  = escape_string($data['request_query'] ?? '');
    $requestBody   = escape_string($data['request_body'] ?? '');
    $responseBody  = escape_string($data['response_body'] ?? '');
    $errorMessage  = escape_string($data['error_message'] ?? '');

    $reqHeaders = isset($data['request_headers'])
        ? escape_string(is_string($data['request_headers']) ? $data['request_headers'] : json_encode($data['request_headers']))
        : null;
    $resHeaders = isset($data['response_headers'])
        ? escape_string(is_string($data['response_headers']) ? $data['response_headers'] : json_encode($data['response_headers']))
        : null;

    $endpointSql   = $endpointId ? "'$endpointId'" : "NULL";
    $reqHeadersSql = $reqHeaders !== null ? "'$reqHeaders'" : "NULL";
    $resHeadersSql = $resHeaders !== null ? "'$resHeaders'" : "NULL";

    $ok = query("
        INSERT INTO cms_api_usage_logs
            (subscription_id, endpoint_id, method, path, status_code, response_time,
             ip_address, user_agent, request_query, request_headers, request_body,
             response_headers, response_body, error_message)
        VALUES
            ('$subscriptionId', $endpointSql, '$method', '$path', '$statusCode', '$responseTime',
             '$ip', '$userAgent', '$requestQuery', $reqHeadersSql, '$requestBody',
             $resHeadersSql, '$responseBody', '$errorMessage')
    ");

    query("UPDATE project_api_subscriptions
           SET usage_count = usage_count + 1, last_used = NOW()
           WHERE id='$subscriptionId'");

    return $ok;
}

function getApiCallLogs($subscriptionId, array $filters = [])
{
    $subscriptionId = intval($subscriptionId);
    $page  = max(1, intval($filters['page'] ?? 1));
    $limit = intval($filters['limit'] ?? 25);
    $limit = max(1, min(100, $limit));
    $offset = ($page - 1) * $limit;

    $where = ["subscription_id='$subscriptionId'"];

    if (!empty($filters['method'])) {
        $method = escape_string(strtoupper($filters['method']));
        $where[] = "method='$method'";
    }

    if (!empty($filters['statusGroup'])) {
        $group = intval(substr($filters['statusGroup'], 0, 1));
        if ($group >= 1 && $group <= 5) {
            $low = $group * 100;
            $high = $low + 99;
            $where[] = "status_code BETWEEN $low AND $high";
        }
    }

    if (!empty($filters['search'])) {
        $search = escape_string($filters['search']);
        $where[] = "(path LIKE '%$search%' OR ip_address LIKE '%$search%' OR method LIKE '%$search%')";
    }

    $whereSql = implode(' AND ', $where);

    $countRow = fetch_assoc(query("SELECT COUNT(*) as total FROM cms_api_usage_logs WHERE $whereSql"));
    $total = intval($countRow['total'] ?? 0);

    $rows = query("
        SELECT id, subscription_id, endpoint_id, method, path, status_code, response_time,
               ip_address, user_agent, request_query, request_headers, request_body,
               response_headers, response_body, error_message, timestamp
        FROM cms_api_usage_logs
        WHERE $whereSql
        ORDER BY timestamp DESC, id DESC
        LIMIT $limit OFFSET $offset
    ");

    $logs = [];
    foreach ($rows as $row) {
        $logs[] = formatCallLogEntry($row);
    }

    return [
        'logs' => $logs,
        'total' => $total,
        'page' => $page,
        'limit' => $limit,
        'totalPages' => $limit > 0 ? (int) ceil($total / $limit) : 1
    ];
}

function formatCallLogEntry($row)
{
    return [
        'id' => intval($row['id']),
        'endpoint_id' => $row['endpoint_id'] !== null ? intval($row['endpoint_id']) : null,
        'method' => $row['method'],
        'path' => $row['path'],
        'status' => intval($row['status_code']),
        'response_time' => intval($row['response_time']),
        'ip_address' => $row['ip_address'],
        'user_agent' => $row['user_agent'],
        'request_query' => $row['request_query'],
        'request_headers' => json_decode($row['request_headers'] ?? 'null', true),
        'request_body' => $row['request_body'],
        'response_headers' => json_decode($row['response_headers'] ?? 'null', true),
        'response_body' => $row['response_body'],
        'error_message' => $row['error_message'],
        'timestamp' => $row['timestamp']
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
            // Subscribe to APIs in the main Fringelo to get access

            export default {};
        ';
    }

    file_put_contents($apisDir . '/index.js', $indexContent);

    return true;
}

function formatCodespaceApiData($api)
{
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

function calculateCodespaceUsageStats($activationId)
{
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

    $content = file_get_contents($targetFile);
    $content = str_replace('[{[apiKey]}]', strtoupper($apiSlug).'_'.strtoupper($codespaceSlug).'_API_KEY', $content);

    file_put_contents($targetFile, $content);

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
