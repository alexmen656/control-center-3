<?php
/**
 * Helper-Funktionen für API-Setup und -Verwaltung
 */

/**
 * Registriert eine neue API in cms_apis
 */
function registerNewAPI($name, $slug, $description = '', $category = 'general', $endpointBase = '') {
    $name = escape_string($name);
    $slug = escape_string($slug);
    $description = escape_string($description);
    $category = escape_string($category);
    $endpointBase = escape_string($endpointBase);
    
    // Prüfe ob slug bereits existiert
    $existing = query("SELECT id FROM cms_apis WHERE slug = '$slug'");
    if ($existing && mysqli_num_rows($existing) > 0) {
        return ['success' => false, 'message' => 'API slug already exists'];
    }
    
    $result = query("
        INSERT INTO cms_apis (name, slug, description, category, endpoint_base, created_at) 
        VALUES ('$name', '$slug', '$description', '$category', '$endpointBase', NOW())
    ");
    
    if ($result) {
        $apiId = mysqli_insert_id($GLOBALS['connection']);
        return ['success' => true, 'api_id' => $apiId, 'message' => 'API registered successfully'];
    }
    
    return ['success' => false, 'message' => 'Failed to register API'];
}

/**
 * Erstellt eine API-Subscription für ein Projekt
 */
function createAPISubscription($projectID, $apiSlug, $customRateLimit = null) {
    $projectID = escape_string($projectID);
    $apiSlug = escape_string($apiSlug);
    
    // Hole API-ID
    $apiResult = query("SELECT id, rate_limit_default FROM cms_apis WHERE slug = '$apiSlug' AND is_active = 1");
    if (!$apiResult || mysqli_num_rows($apiResult) === 0) {
        return ['success' => false, 'message' => 'API not found or inactive'];
    }
    
    $api = mysqli_fetch_assoc($apiResult);
    $apiId = $api['id'];
    $rateLimit = $customRateLimit ?? $api['rate_limit_default'];
    
    // Prüfe ob Subscription bereits existiert
    $existing = query("SELECT id FROM project_api_subscriptions WHERE projectID = '$projectID' AND api_id = '$apiId'");
    if ($existing && mysqli_num_rows($existing) > 0) {
        return ['success' => false, 'message' => 'Subscription already exists'];
    }
    
    // Generiere API-Key
    $apiKey = generateAPIKey($projectID, $apiSlug);
    
    $result = query("
        INSERT INTO project_api_subscriptions (projectID, api_id, api_key, rate_limit, created_at) 
        VALUES ('$projectID', '$apiId', '$apiKey', '$rateLimit', NOW())
    ");
    
    if ($result) {
        return [
            'success' => true, 
            'api_key' => $apiKey, 
            'rate_limit' => $rateLimit,
            'message' => 'API subscription created successfully'
        ];
    }
    
    return ['success' => false, 'message' => 'Failed to create subscription'];
}

/**
 * Generiert einen sicheren API-Key
 */
function generateAPIKey($projectID, $apiSlug) {
    $prefix = substr($apiSlug, 0, 4); // Erste 4 Zeichen des API-Slugs
    $projectSuffix = substr($projectID, -4); // Letzte 4 Zeichen der Projekt-ID
    $randomString = bin2hex(random_bytes(16)); // 32 Zeichen zufällig
    
    return $prefix . '_' . $projectSuffix . '_' . $randomString;
}

/**
 * Validiert einen API-Key für eine bestimmte App
 */
function validateAPIKeyForApp($apiKey, $appSlug) {
    $apiKey = escape_string($apiKey);
    $appSlug = escape_string($appSlug);
    
    $result = query("
        SELECT pas.*, ca.slug, ca.name as api_name, p.projectID as project_name
        FROM project_api_subscriptions pas
        JOIN cms_apis ca ON pas.api_id = ca.id
        LEFT JOIN projects p ON pas.projectID = p.projectID
        WHERE pas.api_key = '$apiKey' 
        AND ca.slug = '$appSlug'
        AND pas.is_enabled = 1
        AND ca.is_active = 1
    ");
    
    if ($result && mysqli_num_rows($result) === 1) {
        return mysqli_fetch_assoc($result);
    }
    
    return false;
}

/**
 * Listet alle verfügbaren APIs auf
 */
function listAvailableAPIs() {
    $result = query("
        SELECT id, name, slug, description, category, endpoint_base, rate_limit_default
        FROM cms_apis 
        WHERE is_active = 1 
        ORDER BY category, name
    ");
    
    $apis = [];
    while ($api = mysqli_fetch_assoc($result)) {
        $apis[] = $api;
    }
    
    return $apis;
}

/**
 * Listet alle API-Subscriptions für ein Projekt auf
 */
function listProjectAPISubscriptions($projectID) {
    $projectID = escape_string($projectID);
    
    $result = query("
        SELECT pas.*, ca.name, ca.slug, ca.description, ca.category
        FROM project_api_subscriptions pas
        JOIN cms_apis ca ON pas.api_id = ca.id
        WHERE pas.projectID = '$projectID'
        ORDER BY ca.category, ca.name
    ");
    
    $subscriptions = [];
    while ($subscription = mysqli_fetch_assoc($result)) {
        $subscriptions[] = $subscription;
    }
    
    return $subscriptions;
}

/**
 * Aktualisiert Rate Limit für eine Subscription
 */
function updateSubscriptionRateLimit($subscriptionId, $newRateLimit) {
    $subscriptionId = escape_string($subscriptionId);
    $newRateLimit = (int)$newRateLimit;
    
    $result = query("
        UPDATE project_api_subscriptions 
        SET rate_limit = '$newRateLimit' 
        WHERE id = '$subscriptionId'
    ");
    
    return $result ? true : false;
}

/**
 * Deaktiviert eine API-Subscription
 */
function disableAPISubscription($subscriptionId) {
    $subscriptionId = escape_string($subscriptionId);
    
    $result = query("
        UPDATE project_api_subscriptions 
        SET is_enabled = 0 
        WHERE id = '$subscriptionId'
    ");
    
    return $result ? true : false;
}

/**
 * Generiert API-Usage Statistiken
 */
function getAPIUsageStats($projectID, $days = 30) {
    $projectID = escape_string($projectID);
    $days = (int)$days;
    
    $result = query("
        SELECT 
            ca.name, 
            ca.slug,
            pas.usage_count,
            pas.rate_limit,
            pas.last_used,
            COUNT(al.id) as log_entries
        FROM project_api_subscriptions pas
        JOIN cms_apis ca ON pas.api_id = ca.id
        LEFT JOIN api_logs al ON al.project_id = pas.projectID AND al.app_id = ca.slug 
            AND al.created_at >= DATE_SUB(NOW(), INTERVAL $days DAY)
        WHERE pas.projectID = '$projectID'
        GROUP BY pas.id, ca.id
        ORDER BY pas.usage_count DESC
    ");
    
    $stats = [];
    while ($stat = mysqli_fetch_assoc($result)) {
        $stats[] = $stat;
    }
    
    return $stats;
}
