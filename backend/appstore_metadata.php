<?php
require_once 'head.php';
require_once 'ECSign.php';

class AppStoreMetadataAPI
{
    private $private_key;
    private $key_id;
    private $issuer_id;
    private $base_url = 'https://api.appstoreconnect.apple.com/v1';

    public function __construct($private_key, $key_id, $issuer_id)
    {
        $this->private_key = $private_key;
        $this->key_id = $key_id;
        $this->issuer_id = $issuer_id;
    }

    private function generateJWT()
    {
        $header = [
            'alg' => 'ES256',
            'kid' => $this->key_id,
            'typ' => 'JWT',
        ];

        $payload = [
            'iss' => $this->issuer_id,
            'exp' => time() + 1200, // 20 minutes
            'aud' => 'appstoreconnect-v1'
        ];

        return ECSign::sign($payload, $header, $this->private_key);
    }

    private function makeRequest($endpoint, $method = 'GET', $data = null)
    {
        $token = $this->generateJWT();
        $url = $this->base_url . $endpoint;

        $headers = [
            "Authorization: Bearer $token",
            "Content-Type: application/json"
        ];

        $httpOptions = [
            'http' => [
                'method' => $method,
                'header' => implode("\r\n", $headers),
                'timeout' => 30,
                'ignore_errors' => true
            ]
        ];

        if ($data && in_array($method, ['POST', 'PATCH', 'PUT'])) {
            $httpOptions['http']['content'] = json_encode($data);
        }

        $context = stream_context_create($httpOptions);
        $response = @file_get_contents($url, false, $context);

        // Get HTTP status code from response headers
        $httpCode = 0;
        if (isset($http_response_header)) {
            foreach ($http_response_header as $header) {
                if (preg_match('#^HTTP/\d+\.\d+\s+(\d+)#', $header, $matches)) {
                    $httpCode = (int) $matches[1];
                    break;
                }
            }
        }

        if ($response === false) {
            throw new Exception("HTTP Request failed to: $endpoint");
        }

        if ($httpCode >= 400) {
            $errorData = json_decode($response, true);
            $errorMessage = $errorData['errors'][0]['detail'] ?? "HTTP Error: $httpCode";
            throw new Exception($errorMessage);
        }

        return json_decode($response, true);
    }

    // Get all apps
    public function getApps()
    {
        $response = $this->makeRequest('/apps?limit=200');
        return $response['data'] ?? [];
    }

    // Get single app
    public function getApp($appId)
    {
        $response = $this->makeRequest("/apps/$appId");
        return $response['data'] ?? null;
    }

    // Get app info (for localizations)
    public function getAppInfo($appId)
    {
        $response = $this->makeRequest("/apps/$appId/appInfos");
        return $response['data'] ?? [];
    }

    // Get app info localizations
    public function getAppInfoLocalizations($appId)
    {
        $appInfos = $this->getAppInfo($appId);
        $localizations = [];

        foreach ($appInfos as $appInfo) {
            $appInfoId = $appInfo['id'];
            $response = $this->makeRequest("/appInfos/$appInfoId/appInfoLocalizations");
            $localizations = array_merge($localizations, $response['data'] ?? []);
        }

        return $localizations;
    }

    // Update app info localization
    public function updateAppInfoLocalization($localizationId, $data)
    {
        $payload = [
            'data' => [
                'type' => 'appInfoLocalizations',
                'id' => $localizationId,
                'attributes' => array_filter([
                    'name' => $data['name'] ?? null,
                    'subtitle' => $data['subtitle'] ?? null,
                    'privacyPolicyUrl' => $data['privacyPolicyUrl'] ?? null,
                    'privacyPolicyText' => $data['privacyPolicyText'] ?? null,
                    'privacyChoicesUrl' => $data['privacyChoicesUrl'] ?? null,
                ])
            ]
        ];

        return $this->makeRequest("/appInfoLocalizations/$localizationId", 'PATCH', $payload);
    }

    public function createAppInfoLocalization($appInfoId, $locale, $data)
    {
        $payload = [
            'data' => [
                'type' => 'appInfoLocalizations',
                'attributes' => array_filter([
                    'locale' => $locale,
                    'name' => $data['name'] ?? null,
                    'subtitle' => $data['subtitle'] ?? null,
                    'privacyPolicyUrl' => $data['privacyPolicyUrl'] ?? null,
                    'privacyPolicyText' => $data['privacyPolicyText'] ?? null,
                    'privacyChoicesUrl' => $data['privacyChoicesUrl'] ?? null,
                ]),
                'relationships' => [
                    'appInfo' => [
                        'data' => [
                            'type' => 'appInfos',
                            'id' => $appInfoId
                        ]
                    ]
                ]
            ]
        ];

        return $this->makeRequest("/appInfoLocalizations", 'POST', $payload);
    }

    // Get app store versions
    public function getAppStoreVersions($appId)
    {
        $response = $this->makeRequest("/apps/$appId/appStoreVersions?limit=10");
        return $response['data'] ?? [];
    }

    // Get version localizations
    public function getAppStoreVersionLocalizations($versionId)
    {
        $response = $this->makeRequest("/appStoreVersions/$versionId/appStoreVersionLocalizations");
        return $response['data'] ?? [];
    }

    // Update version localization
    public function updateAppStoreVersionLocalization($localizationId, $data)
    {
        $payload = [
            'data' => [
                'type' => 'appStoreVersionLocalizations',
                'id' => $localizationId,
                'attributes' => array_filter([
                    'description' => $data['description'] ?? null,
                    'keywords' => $data['keywords'] ?? null,
                    'whatsNew' => $data['whatsNew'] ?? null,
                    'marketingUrl' => $data['marketingUrl'] ?? null,
                    'supportUrl' => $data['supportUrl'] ?? null,
                    'promotionalText' => $data['promotionalText'] ?? null,
                ])
            ]
        ];

        return $this->makeRequest("/appStoreVersionLocalizations/$localizationId", 'PATCH', $payload);
    }

    // Create version localization
    public function createAppStoreVersionLocalization($versionId, $locale, $data)
    {
        $payload = [
            'data' => [
                'type' => 'appStoreVersionLocalizations',
                'attributes' => array_filter([
                    'locale' => $locale,
                    'description' => $data['description'] ?? null,
                    'keywords' => $data['keywords'] ?? null,
                    'whatsNew' => $data['whatsNew'] ?? null,
                    'marketingUrl' => $data['marketingUrl'] ?? null,
                    'supportUrl' => $data['supportUrl'] ?? null,
                    'promotionalText' => $data['promotionalText'] ?? null,
                ]),
                'relationships' => [
                    'appStoreVersion' => [
                        'data' => [
                            'type' => 'appStoreVersions',
                            'id' => $versionId
                        ]
                    ]
                ]
            ]
        ];

        return $this->makeRequest("/appStoreVersionLocalizations", 'POST', $payload);
    }

    // Get screenshots for a localization
    public function getScreenshots($localizationId)
    {
        $response = $this->makeRequest("/appStoreVersionLocalizations/$localizationId/appScreenshotSets");
        return $response['data'] ?? [];
    }

    // Get app categories
    public function getAppCategories($appId)
    {
        $appInfos = $this->getAppInfo($appId);
        if (empty($appInfos)) {
            return null;
        }

        $appInfoId = $appInfos[0]['id'];
        $response = $this->makeRequest("/appInfos/$appInfoId?include=primaryCategory,primarySubcategoryOne,primarySubcategoryTwo,secondaryCategory,secondarySubcategoryOne,secondarySubcategoryTwo");

        $data = $response['data'] ?? null;
        $included = $response['included'] ?? [];

        if (!$data) {
            return null;
        }

        $result = [
            'appInfoId' => $appInfoId,
            'primaryCategoryId' => null,
            'primaryCategoryName' => null,
            'primarySubcategoryId' => null,
            'primarySubcategoryName' => null,
            'secondaryCategoryId' => null,
            'secondaryCategoryName' => null,
            'secondarySubcategoryId' => null,
            'secondarySubcategoryName' => null
        ];

        // Build ID to category map (id => name)
        $categoryMap = [];
        foreach ($included as $item) {
            if ($item['type'] === 'appCategories') {
                $categoryMap[$item['id']] = $item['attributes']['name'] ?? $item['id'];
            }
        }

        // Extract relationships
        $relationships = $data['relationships'] ?? [];

        // Primary category
        if (isset($relationships['primaryCategory']['data']['id'])) {
            $catId = $relationships['primaryCategory']['data']['id'];
            $result['primaryCategoryId'] = $catId;
            $result['primaryCategoryName'] = $categoryMap[$catId] ?? $catId;
        }

        // Primary subcategory (only first one)
        if (isset($relationships['primarySubcategoryOne']['data']['id'])) {
            $subId = $relationships['primarySubcategoryOne']['data']['id'];
            $result['primarySubcategoryId'] = $subId;
            $result['primarySubcategoryName'] = $categoryMap[$subId] ?? $subId;
        }

        // Secondary category
        if (isset($relationships['secondaryCategory']['data']['id'])) {
            $catId = $relationships['secondaryCategory']['data']['id'];
            $result['secondaryCategoryId'] = $catId;
            $result['secondaryCategoryName'] = $categoryMap[$catId] ?? $catId;
        }

        // Secondary subcategory (only first one)
        if (isset($relationships['secondarySubcategoryOne']['data']['id'])) {
            $subId = $relationships['secondarySubcategoryOne']['data']['id'];
            $result['secondarySubcategoryId'] = $subId;
            $result['secondarySubcategoryName'] = $categoryMap[$subId] ?? $subId;
        }

        return $result;
    }

    // Update app categories
    public function updateAppCategories($appInfoId, $categories)
    {
        $relationships = [];

        $categoryKeys = [
            'primaryCategory',
            'primarySubcategoryOne',
            'primarySubcategoryTwo',
            'secondaryCategory',
            'secondarySubcategoryOne',
            'secondarySubcategoryTwo'
        ];

        foreach ($categoryKeys as $key) {
            if (!empty($categories[$key])) {
                $relationships[$key] = [
                    'data' => [
                        'type' => 'appCategories',
                        'id' => $categories[$key]
                    ]
                ];
            } else {
                $relationships[$key] = ['data' => null];
            }
        }

        $payload = [
            'data' => [
                'type' => 'appInfos',
                'id' => $appInfoId,
                'relationships' => $relationships
            ]
        ];

        return $this->makeRequest("/appInfos/$appInfoId", 'PATCH', $payload);
    }

    // Get age rating declaration
    public function getAgeRatingDeclaration($appId)
    {
        $appInfos = $this->getAppInfo($appId);
        if (empty($appInfos)) {
            return null;
        }

        $appInfoId = $appInfos[0]['id'];
        $response = $this->makeRequest("/appInfos/$appInfoId?include=ageRatingDeclaration");

        $included = $response['included'] ?? [];
        foreach ($included as $item) {
            if ($item['type'] === 'ageRatingDeclarations') {
                return $item;
            }
        }

        return null;
    }

    // Update age rating declaration
    public function updateAgeRatingDeclaration($ageRatingDeclarationId, $ratings)
    {
        $payload = [
            'data' => [
                'type' => 'ageRatingDeclarations',
                'id' => $ageRatingDeclarationId,
                'attributes' => $ratings
            ]
        ];

        return $this->makeRequest("/ageRatingDeclarations/$ageRatingDeclarationId", 'PATCH', $payload);
    }

    // Get available categories
    public function getAvailableCategories()
    {
        $response = $this->makeRequest('/appCategories?limit=200');
        return $response['data'] ?? [];
    }
}

// Get project from query or session (projectLink is the project identifier)
$project = $_GET['project'] ?? $_POST['project'] ?? $_SESSION['project'] ?? null;

if (!$project) {
    http_response_code(400);
    echo json_encode(['error' => 'Project is required']);
    exit;
}

// Get numeric project_id from project link
$project = escape_string($project);
$project_result = query("SELECT id FROM projects WHERE link = '$project'");
$project_row = fetch_assoc($project_result);
$project_id = $project_row ? (int) $project_row['id'] : null;

if (!$project_id) {
    http_response_code(400);
    echo json_encode(['error' => 'Project not found']);
    exit;
}

$action = $_GET['action'] ?? '';
$method = $_SERVER['REQUEST_METHOD'];

try {
    switch ($action) {
        // ============================================
        // APP MANAGEMENT
        // ============================================
        case 'apps':
            handleApps($project_id, $method);
            break;

        case 'app':
            $app_id = isset($_GET['app_id']) ? (int) $_GET['app_id'] : null;
            handleSingleApp($project_id, $app_id, $method);
            break;

        // ============================================
        // VERSION MANAGEMENT
        // ============================================
        case 'versions':
            $app_id = isset($_GET['app_id']) ? (int) $_GET['app_id'] : null;
            handleVersions($project_id, $app_id, $method);
            break;

        case 'version':
            $version_id = isset($_GET['version_id']) ? (int) $_GET['version_id'] : null;
            handleSingleVersion($project_id, $version_id, $method);
            break;

        // ============================================
        // LOCALIZATION MANAGEMENT
        // ============================================
        case 'app_localizations':
        case 'localizations': // Alias
            $app_id = isset($_GET['app_id']) ? (int) $_GET['app_id'] : null;
            handleAppLocalizations($project_id, $app_id, $method);
            break;

        case 'localization': // Single app localization
        case 'app_localization':
            $id = isset($_GET['id']) ? (int) $_GET['id'] : null;
            handleSingleAppLocalization($project_id, $id, $method);
            break;

        case 'version_localizations':
            $version_id = isset($_GET['version_id']) ? (int) $_GET['version_id'] : null;
            handleVersionLocalizations($project_id, $version_id, $method);
            break;

        case 'version_localization': // Single version localization
            $id = isset($_GET['id']) ? (int) $_GET['id'] : null;
            handleSingleVersionLocalization($project_id, $id, $method);
            break;

        // ============================================
        // SCREENSHOTS MANAGEMENT
        // ============================================
        case 'screenshots':
            $version_id = isset($_GET['version_id']) ? (int) $_GET['version_id'] : null;
            handleScreenshots($project_id, $version_id, $method);
            break;

        // ============================================
        // API CREDENTIALS
        // ============================================
        case 'credentials':
            handleCredentials($project_id, $method);
            break;

        // ============================================
        // SUPPORTED LOCALES
        // ============================================
        case 'locales':
            handleLocales($method);
            break;

        // ============================================
        // CATEGORIES
        // ============================================
        case 'categories':
            $app_id = isset($_GET['app_id']) ? (int) $_GET['app_id'] : null;
            handleCategories($project_id, $app_id, $method);
            break;

        // ============================================
        // AGE RATINGS
        // ============================================
        case 'age_ratings':
            $app_id = isset($_GET['app_id']) ? (int) $_GET['app_id'] : null;
            handleAgeRatings($project_id, $app_id, $method);
            break;

        // ============================================
        // SYNC WITH APP STORE CONNECT
        // ============================================
        case 'sync_pull':
            $app_id = isset($_GET['app_id']) ? (int) $_GET['app_id'] : null;
            handleSyncPull($project_id, $app_id);
            break;

        case 'sync_push':
            $app_id = isset($_GET['app_id']) ? (int) $_GET['app_id'] : null;
            handleSyncPush($project_id, $app_id);
            break;

        // ============================================
        // BROWSE & CONNECT APPS FROM APP STORE CONNECT
        // ============================================
        case 'browse_apps':
            handleBrowseApps($project_id);
            break;

        case 'connect_app':
            handleConnectApp($project_id);
            break;

        // ============================================
        // DASHBOARD / OVERVIEW
        // ============================================
        case 'dashboard':
            handleDashboard($project_id);
            break;

        default:
            // Return overview if no action
            handleDashboard($project_id);
            break;
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => $e->getMessage()
    ]);
}

// ============================================
// HANDLER FUNCTIONS
// ============================================

function handleApps($project_id, $method)
{
    global $con;

    if ($method === 'GET') {
        // List all apps for project
        $result = query("
            SELECT a.*, 
                   COUNT(DISTINCT v.id) as version_count,
                   COUNT(DISTINCT l.id) as locale_count,
                   MAX(v.version_string) as latest_version
            FROM appstore_apps a
            LEFT JOIN appstore_app_versions v ON a.id = v.app_id
            LEFT JOIN appstore_app_localizations l ON a.id = l.app_id
            WHERE a.project_id = $project_id
            GROUP BY a.id
            ORDER BY a.updated_at DESC
        ");

        $apps = [];
        while ($row = fetch_assoc($result)) {
            $apps[] = $row;
        }

        echo json_encode([
            'success' => true,
            'apps' => $apps,
            'count' => count($apps)
        ]);
    } elseif ($method === 'POST') {
        // Create new app
        $input = json_decode(file_get_contents('php://input'), true);

        $required = ['app_id', 'bundle_id', 'name'];
        foreach ($required as $field) {
            if (empty($input[$field])) {
                http_response_code(400);
                echo json_encode(['error' => "Field '$field' is required"]);
                return;
            }
        }

        $app_store_id = escape_string($input['app_id']);
        $bundle_id = escape_string($input['bundle_id']);
        $name = escape_string($input['name']);
        $sku = escape_string($input['sku'] ?? '');
        $primary_locale = escape_string($input['primary_locale'] ?? 'en-US');

        query("
            INSERT INTO appstore_apps (project_id, app_id, bundle_id, name, sku, primary_locale, status)
            VALUES ($project_id, '$app_store_id', '$bundle_id', '$name', '$sku', '$primary_locale', 'draft')
        ");

        $newId = mysqli_insert_id($con);

        // Create default localization for primary locale
        query("
            INSERT INTO appstore_app_localizations (app_id, locale, name)
            VALUES ($newId, '$primary_locale', '$name')
        ");

        // Log operation
        logOperation($project_id, $newId, 'create_app', 'success', json_encode(['app_id' => $app_store_id]));

        echo json_encode([
            'success' => true,
            'id' => $newId,
            'message' => 'App created successfully'
        ]);
    }
}

function handleSingleApp($project_id, $app_id, $method)
{
    global $con;

    if (!$app_id) {
        http_response_code(400);
        echo json_encode(['error' => 'App ID is required']);
        return;
    }

    // Verify app belongs to project
    $result = query("SELECT * FROM appstore_apps WHERE id = $app_id AND project_id = $project_id");
    $app = fetch_assoc($result);

    if (!$app) {
        http_response_code(404);
        echo json_encode(['error' => 'App not found']);
        return;
    }

    if ($method === 'GET') {
        // Get localizations
        $locResult = query("SELECT * FROM appstore_app_localizations WHERE app_id = $app_id");
        $localizations = [];
        while ($row = fetch_assoc($locResult)) {
            $localizations[] = $row;
        }

        // Get versions with counts
        $verResult = query("SELECT * FROM appstore_app_versions WHERE app_id = $app_id ORDER BY created_at DESC");
        $versions = [];
        while ($row = fetch_assoc($verResult)) {
            // Get locale count for this version
            $localeCountResult = query("SELECT COUNT(DISTINCT locale) as count FROM appstore_version_localizations WHERE version_id = {$row['id']}");
            $localeCount = fetch_assoc($localeCountResult);
            $row['locale_count'] = (int) $localeCount['count'];

            // Get screenshot count for this version
            $screenshotCountResult = query("SELECT COUNT(*) as count FROM appstore_screenshots WHERE version_id = {$row['id']}");
            $screenshotCount = fetch_assoc($screenshotCountResult);
            $row['screenshot_count'] = (int) $screenshotCount['count'];

            $versions[] = $row;
        }

        // Get categories
        $catResult = query("SELECT * FROM appstore_app_categories WHERE app_id = $app_id");
        $categories = [];
        while ($row = fetch_assoc($catResult)) {
            $categories[] = $row;
        }

        // Get age rating
        $ageResult = query("SELECT * FROM appstore_age_ratings WHERE app_id = $app_id");
        $ageRating = fetch_assoc($ageResult);

        echo json_encode([
            'success' => true,
            'app' => $app,
            'localizations' => $localizations,
            'versions' => $versions,
            'categories' => $categories,
            'age_rating' => $ageRating
        ]);
    } elseif ($method === 'PUT' || $method === 'PATCH') {
        // Update app
        $input = json_decode(file_get_contents('php://input'), true);

        $updateParts = [];
        $allowedFields = [
            'name',
            'sku',
            'primary_locale',
            'content_rights_declaration',
            'is_available_in_new_territories',
            'status'
        ];

        foreach ($allowedFields as $field) {
            if (isset($input[$field])) {
                $value = escape_string($input[$field]);
                $updateParts[] = "$field = '$value'";
            }
        }

        if (empty($updateParts)) {
            http_response_code(400);
            echo json_encode(['error' => 'No fields to update']);
            return;
        }

        query("UPDATE appstore_apps SET " . implode(', ', $updateParts) . " WHERE id = $app_id AND project_id = $project_id");

        echo json_encode([
            'success' => true,
            'message' => 'App updated successfully'
        ]);
    } elseif ($method === 'DELETE') {
        query("DELETE FROM appstore_apps WHERE id = $app_id AND project_id = $project_id");

        echo json_encode([
            'success' => true,
            'message' => 'App deleted successfully'
        ]);
    }
}

function handleVersions($project_id, $app_id, $method)
{
    global $con;

    if (!$app_id) {
        http_response_code(400);
        echo json_encode(['error' => 'App ID is required']);
        return;
    }

    // Verify app belongs to project
    $result = query("SELECT id FROM appstore_apps WHERE id = $app_id AND project_id = $project_id");
    if (!fetch_assoc($result)) {
        http_response_code(404);
        echo json_encode(['error' => 'App not found']);
        return;
    }

    if ($method === 'GET') {
        $result = query("
            SELECT v.*, 
                   COUNT(DISTINCT l.id) as locale_count,
                   COUNT(DISTINCT s.id) as screenshot_count
            FROM appstore_app_versions v
            LEFT JOIN appstore_version_localizations l ON v.id = l.version_id
            LEFT JOIN appstore_screenshots s ON v.id = s.version_id
            WHERE v.app_id = $app_id
            GROUP BY v.id
            ORDER BY v.created_at DESC
        ");

        $versions = [];
        while ($row = fetch_assoc($result)) {
            $versions[] = $row;
        }

        echo json_encode([
            'success' => true,
            'versions' => $versions
        ]);
    } elseif ($method === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);

        if (empty($input['version_string'])) {
            http_response_code(400);
            echo json_encode(['error' => 'version_string is required']);
            return;
        }

        $version_string = escape_string($input['version_string']);
        $build_number = escape_string($input['build_number'] ?? '');
        $platform = escape_string($input['platform'] ?? 'iOS');
        $release_type = escape_string($input['release_type'] ?? 'afterApproval');
        $copyright = escape_string($input['copyright'] ?? '');
        $review_notes = escape_string($input['review_notes'] ?? '');

        query("
            INSERT INTO appstore_app_versions 
            (app_id, version_string, build_number, platform, release_type, copyright, review_notes, status)
            VALUES ($app_id, '$version_string', '$build_number', '$platform', '$release_type', '$copyright', '$review_notes', 'draft')
        ");

        $newId = mysqli_insert_id($con);

        logOperation($project_id, $app_id, 'create_version', 'success', json_encode([
            'version_id' => $newId,
            'version_string' => $version_string
        ]));

        echo json_encode([
            'success' => true,
            'id' => $newId,
            'message' => 'Version created successfully'
        ]);
    }
}

function handleSingleVersion($project_id, $version_id, $method)
{
    global $con;

    if (!$version_id) {
        http_response_code(400);
        echo json_encode(['error' => 'Version ID is required']);
        return;
    }

    // Verify version belongs to project
    $result = query("
        SELECT v.* FROM appstore_app_versions v
        JOIN appstore_apps a ON v.app_id = a.id
        WHERE v.id = $version_id AND a.project_id = $project_id
    ");
    $version = fetch_assoc($result);

    if (!$version) {
        http_response_code(404);
        echo json_encode(['error' => 'Version not found']);
        return;
    }

    if ($method === 'GET') {
        // Get localizations
        $locResult = query("SELECT * FROM appstore_version_localizations WHERE version_id = $version_id");
        $localizations = [];
        while ($row = fetch_assoc($locResult)) {
            $localizations[] = $row;
        }

        // Get screenshots
        $ssResult = query("SELECT * FROM appstore_screenshots WHERE version_id = $version_id ORDER BY locale, display_type, position");
        $screenshots = [];
        while ($row = fetch_assoc($ssResult)) {
            $screenshots[] = $row;
        }

        echo json_encode([
            'success' => true,
            'version' => $version,
            'localizations' => $localizations,
            'screenshots' => $screenshots
        ]);
    } elseif ($method === 'PUT' || $method === 'PATCH') {
        $input = json_decode(file_get_contents('php://input'), true);

        $updateParts = [];
        $allowedFields = [
            'version_string',
            'build_number',
            'platform',
            'release_type',
            'earliest_release_date',
            'copyright',
            'review_notes',
            'status'
        ];

        foreach ($allowedFields as $field) {
            if (isset($input[$field])) {
                $value = escape_string($input[$field]);
                $updateParts[] = "$field = '$value'";
            }
        }

        if (empty($updateParts)) {
            http_response_code(400);
            echo json_encode(['error' => 'No fields to update']);
            return;
        }

        query("UPDATE appstore_app_versions SET " . implode(', ', $updateParts) . " WHERE id = $version_id");

        echo json_encode([
            'success' => true,
            'message' => 'Version updated successfully'
        ]);
    } elseif ($method === 'DELETE') {
        query("DELETE FROM appstore_app_versions WHERE id = $version_id");

        echo json_encode([
            'success' => true,
            'message' => 'Version deleted successfully'
        ]);
    }
}

function handleAppLocalizations($project_id, $app_id, $method)
{
    global $con;

    if (!$app_id) {
        http_response_code(400);
        echo json_encode(['error' => 'App ID is required']);
        return;
    }

    // Verify app belongs to project
    $result = query("SELECT id FROM appstore_apps WHERE id = $app_id AND project_id = $project_id");
    if (!fetch_assoc($result)) {
        http_response_code(404);
        echo json_encode(['error' => 'App not found']);
        return;
    }

    if ($method === 'GET') {
        $result = query("
            SELECT l.*, s.name as locale_name, s.native_name 
            FROM appstore_app_localizations l
            LEFT JOIN appstore_supported_locales s ON l.locale = s.code
            WHERE l.app_id = $app_id
            ORDER BY l.locale
        ");

        $localizations = [];
        while ($row = fetch_assoc($result)) {
            $localizations[] = $row;
        }

        echo json_encode([
            'success' => true,
            'localizations' => $localizations
        ]);
    } elseif ($method === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);

        if (empty($input['locale'])) {
            http_response_code(400);
            echo json_encode(['error' => 'locale is required']);
            return;
        }

        $locale = escape_string($input['locale']);
        $name = escape_string($input['name'] ?? '');
        $subtitle = escape_string($input['subtitle'] ?? '');
        $privacy_policy_url = escape_string($input['privacy_policy_url'] ?? '');
        $privacy_policy_text = escape_string($input['privacy_policy_text'] ?? '');
        $privacy_choices_url = escape_string($input['privacy_choices_url'] ?? '');

        query("
            INSERT INTO appstore_app_localizations 
            (app_id, locale, name, subtitle, privacy_policy_url, privacy_policy_text, privacy_choices_url, is_dirty)
            VALUES ($app_id, '$locale', '$name', '$subtitle', '$privacy_policy_url', '$privacy_policy_text', '$privacy_choices_url', 1)
            ON DUPLICATE KEY UPDATE
                name = '$name',
                subtitle = '$subtitle',
                privacy_policy_url = '$privacy_policy_url',
                privacy_policy_text = '$privacy_policy_text',
                privacy_choices_url = '$privacy_choices_url',
                is_dirty = 1,
                updated_at = CURRENT_TIMESTAMP
        ");

        echo json_encode([
            'success' => true,
            'message' => 'Localization saved successfully'
        ]);
    } elseif ($method === 'DELETE') {
        $locale = escape_string($_GET['locale'] ?? '');
        if (!$locale) {
            http_response_code(400);
            echo json_encode(['error' => 'locale is required']);
            return;
        }

        query("DELETE FROM appstore_app_localizations WHERE app_id = $app_id AND locale = '$locale'");

        echo json_encode([
            'success' => true,
            'message' => 'Localization deleted successfully'
        ]);
    }
}

function handleVersionLocalizations($project_id, $version_id, $method)
{
    global $con;

    if (!$version_id) {
        http_response_code(400);
        echo json_encode(['error' => 'Version ID is required']);
        return;
    }

    // Verify version belongs to project
    $result = query("
        SELECT v.id FROM appstore_app_versions v
        JOIN appstore_apps a ON v.app_id = a.id
        WHERE v.id = $version_id AND a.project_id = $project_id
    ");
    if (!fetch_assoc($result)) {
        http_response_code(404);
        echo json_encode(['error' => 'Version not found']);
        return;
    }

    if ($method === 'GET') {
        $result = query("
            SELECT l.*, s.name as locale_name, s.native_name 
            FROM appstore_version_localizations l
            LEFT JOIN appstore_supported_locales s ON l.locale = s.code
            WHERE l.version_id = $version_id
            ORDER BY l.locale
        ");

        $localizations = [];
        while ($row = fetch_assoc($result)) {
            $localizations[] = $row;
        }

        echo json_encode([
            'success' => true,
            'localizations' => $localizations
        ]);
    } elseif ($method === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);

        if (empty($input['locale'])) {
            http_response_code(400);
            echo json_encode(['error' => 'locale is required']);
            return;
        }

        $locale = escape_string($input['locale']);
        $description = escape_string($input['description'] ?? '');
        $keywords = escape_string($input['keywords'] ?? '');
        $whats_new = escape_string($input['whats_new'] ?? '');
        $marketing_url = escape_string($input['marketing_url'] ?? '');
        $support_url = escape_string($input['support_url'] ?? '');
        $promotional_text = escape_string($input['promotional_text'] ?? '');

        query("
            INSERT INTO appstore_version_localizations 
            (version_id, locale, description, keywords, whats_new, marketing_url, support_url, promotional_text, is_dirty)
            VALUES ($version_id, '$locale', '$description', '$keywords', '$whats_new', '$marketing_url', '$support_url', '$promotional_text', 1)
            ON DUPLICATE KEY UPDATE
                description = '$description',
                keywords = '$keywords',
                whats_new = '$whats_new',
                marketing_url = '$marketing_url',
                support_url = '$support_url',
                promotional_text = '$promotional_text',
                is_dirty = 1,
                updated_at = CURRENT_TIMESTAMP
        ");

        echo json_encode([
            'success' => true,
            'message' => 'Version localization saved successfully'
        ]);
    } elseif ($method === 'DELETE') {
        $locale = escape_string($_GET['locale'] ?? '');
        if (!$locale) {
            http_response_code(400);
            echo json_encode(['error' => 'locale is required']);
            return;
        }

        query("DELETE FROM appstore_version_localizations WHERE version_id = $version_id AND locale = '$locale'");

        echo json_encode([
            'success' => true,
            'message' => 'Version localization deleted successfully'
        ]);
    }
}

// Handle single version localization (GET, PUT, DELETE by id)
function handleSingleVersionLocalization($project_id, $id, $method)
{
    global $con;

    if (!$id) {
        http_response_code(400);
        echo json_encode(['error' => 'Localization ID is required']);
        return;
    }

    // Verify localization belongs to project
    $result = query("
        SELECT vl.*, v.version_string, a.name as app_name
        FROM appstore_version_localizations vl
        JOIN appstore_app_versions v ON vl.version_id = v.id
        JOIN appstore_apps a ON v.app_id = a.id
        WHERE vl.id = $id AND a.project_id = $project_id
    ");
    $localization = fetch_assoc($result);

    if (!$localization) {
        http_response_code(404);
        echo json_encode(['error' => 'Version localization not found']);
        return;
    }

    if ($method === 'GET') {
        echo json_encode([
            'success' => true,
            'localization' => $localization
        ]);
    } elseif ($method === 'PUT' || $method === 'PATCH') {
        $input = json_decode(file_get_contents('php://input'), true);

        $updateParts = [];
        $allowedFields = ['description', 'keywords', 'whats_new', 'marketing_url', 'support_url', 'promotional_text'];

        foreach ($allowedFields as $field) {
            if (isset($input[$field])) {
                $value = escape_string($input[$field]);
                $updateParts[] = "$field = '$value'";
            }
        }

        if (empty($updateParts)) {
            http_response_code(400);
            echo json_encode(['error' => 'No fields to update']);
            return;
        }

        query("UPDATE appstore_version_localizations SET " . implode(', ', $updateParts) . ", is_dirty = 1, updated_at = CURRENT_TIMESTAMP WHERE id = $id");

        echo json_encode([
            'success' => true,
            'message' => 'Version localization updated successfully'
        ]);
    } elseif ($method === 'DELETE') {
        query("DELETE FROM appstore_version_localizations WHERE id = $id");

        echo json_encode([
            'success' => true,
            'message' => 'Version localization deleted successfully'
        ]);
    }
}

// Handle single app localization (GET, PUT, DELETE by id)
function handleSingleAppLocalization($project_id, $id, $method)
{
    global $con;

    if (!$id) {
        http_response_code(400);
        echo json_encode(['error' => 'Localization ID is required']);
        return;
    }

    // Verify localization belongs to project
    $result = query("
        SELECT al.*, a.name as app_name
        FROM appstore_app_localizations al
        JOIN appstore_apps a ON al.app_id = a.id
        WHERE al.id = $id AND a.project_id = $project_id
    ");
    $localization = fetch_assoc($result);

    if (!$localization) {
        http_response_code(404);
        echo json_encode(['error' => 'App localization not found']);
        return;
    }

    if ($method === 'GET') {
        echo json_encode([
            'success' => true,
            'localization' => $localization
        ]);
    } elseif ($method === 'PUT' || $method === 'PATCH') {
        $input = json_decode(file_get_contents('php://input'), true);

        $updateParts = [];
        $allowedFields = ['name', 'subtitle', 'privacy_policy_url', 'privacy_policy_text', 'privacy_choices_url'];

        foreach ($allowedFields as $field) {
            if (isset($input[$field])) {
                $value = escape_string($input[$field]);
                $updateParts[] = "$field = '$value'";
            }
        }

        if (empty($updateParts)) {
            http_response_code(400);
            echo json_encode(['error' => 'No fields to update']);
            return;
        }

        query("UPDATE appstore_app_localizations SET " . implode(', ', $updateParts) . ", is_dirty = 1, updated_at = CURRENT_TIMESTAMP WHERE id = $id");

        echo json_encode([
            'success' => true,
            'message' => 'App localization updated successfully'
        ]);
    } elseif ($method === 'DELETE') {
        query("DELETE FROM appstore_app_localizations WHERE id = $id");

        echo json_encode([
            'success' => true,
            'message' => 'App localization deleted successfully'
        ]);
    }
}

function handleScreenshots($project_id, $version_id, $method)
{
    global $con;

    if (!$version_id && $method !== 'GET') {
        http_response_code(400);
        echo json_encode(['error' => 'Version ID is required']);
        return;
    }

    if ($version_id) {
        // Verify version belongs to project
        $result = query("
            SELECT v.id FROM appstore_app_versions v
            JOIN appstore_apps a ON v.app_id = a.id
            WHERE v.id = $version_id AND a.project_id = $project_id
        ");
        if (!fetch_assoc($result)) {
            http_response_code(404);
            echo json_encode(['error' => 'Version not found']);
            return;
        }
    }

    if ($method === 'GET') {
        $locale = escape_string($_GET['locale'] ?? '');
        $display_type = escape_string($_GET['display_type'] ?? '');

        $sql = "SELECT * FROM appstore_screenshots WHERE version_id = $version_id";

        if ($locale) {
            $sql .= " AND locale = '$locale'";
        }
        if ($display_type) {
            $sql .= " AND display_type = '$display_type'";
        }

        $sql .= " ORDER BY locale, display_type, position";

        $result = query($sql);
        $screenshots = [];
        while ($row = fetch_assoc($result)) {
            $row['file_path'] = str_replace('../', 'https://alex.polan.sk/', $row['file_path']);
            $screenshots[] = $row;
        }

        echo json_encode([
            'success' => true,
            'screenshots' => $screenshots
        ]);
    } elseif ($method === 'POST') {
        // Handle file upload
        $locale = escape_string($_POST['locale'] ?? 'en-US');
        $display_type = escape_string($_POST['display_type'] ?? 'APP_IPHONE_67');
        $position = (int) ($_POST['position'] ?? 0);

        if (!isset($_FILES['screenshot']) && !isset($_FILES['file'])) {
            http_response_code(400);
            echo json_encode(['error' => 'No file uploaded']);
            return;
        }

        $file = $_FILES['screenshot'] ?? $_FILES['file'];
        $uploadDir = '../uploads/screenshots/' . $project_id . '/' . $version_id . '/';

        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $fileName = uniqid() . '.' . pathinfo($file['name'], PATHINFO_EXTENSION);
        $filePath = $uploadDir . $fileName;

        if (move_uploaded_file($file['tmp_name'], $filePath)) {
            $imageSize = getimagesize($filePath);
            $width = $imageSize[0] ?? 0;
            $height = $imageSize[1] ?? 0;
            $fileSize = $file['size'];

            query("
                INSERT INTO appstore_screenshots 
                (version_id, locale, display_type, asset_type, file_name, file_path, file_size, width, height, position)
                VALUES ($version_id, '$locale', '$display_type', 'screenshot', '$fileName', '$filePath', $fileSize, $width, $height, $position)
            ");

            echo json_encode([
                'success' => true,
                'id' => mysqli_insert_id($con),
                'message' => 'Screenshot uploaded successfully'
            ]);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to upload file']);
        }
    } elseif ($method === 'PUT') {
        // Update order
        $input = json_decode(file_get_contents('php://input'), true);

        if (isset($input['order_updates']) && is_array($input['order_updates'])) {
            foreach ($input['order_updates'] as $update) {
                $id = (int) $update['id'];
                $order = (int) $update['display_order'];
                query("UPDATE appstore_screenshots SET position = $order WHERE id = $id");
            }
        }

        echo json_encode([
            'success' => true,
            'message' => 'Order updated successfully'
        ]);
    } elseif ($method === 'DELETE') {
        $screenshot_id = (int) ($_GET['id'] ?? $_GET['screenshot_id'] ?? 0);
        if (!$screenshot_id) {
            http_response_code(400);
            echo json_encode(['error' => 'screenshot id is required']);
            return;
        }

        // Get file path before deleting
        $result = query("SELECT file_path FROM appstore_screenshots WHERE id = $screenshot_id");
        $screenshot = fetch_assoc($result);

        if ($screenshot && !empty($screenshot['file_path']) && file_exists($screenshot['file_path'])) {
            unlink($screenshot['file_path']);
        }

        query("DELETE FROM appstore_screenshots WHERE id = $screenshot_id");

        echo json_encode([
            'success' => true,
            'message' => 'Screenshot deleted successfully'
        ]);
    }
}

function handleCredentials($project_id, $method)
{
    global $con;

    if ($method === 'GET') {
        $result = query("
            SELECT id, issuer_id, key_id, vendor_number, is_active, last_used_at, created_at, updated_at
            FROM appstore_api_credentials 
            WHERE project_id = $project_id
        ");
        $credentials = fetch_assoc($result);

        echo json_encode([
            'success' => true,
            'credentials' => $credentials,
            'has_credentials' => (bool) $credentials
        ]);
    } elseif ($method === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);

        $required = ['issuer_id', 'key_id', 'private_key'];
        foreach ($required as $field) {
            if (empty($input[$field])) {
                http_response_code(400);
                echo json_encode(['error' => "Field '$field' is required"]);
                return;
            }
        }

        $issuer_id = escape_string($input['issuer_id']);
        $key_id = escape_string($input['key_id']);
        $private_key = escape_string(base64_encode($input['private_key']));
        $vendor_number = escape_string($input['vendor_number'] ?? '');

        query("
            INSERT INTO appstore_api_credentials 
            (project_id, issuer_id, key_id, private_key, vendor_number)
            VALUES ($project_id, '$issuer_id', '$key_id', '$private_key', '$vendor_number')
            ON DUPLICATE KEY UPDATE
                issuer_id = '$issuer_id',
                key_id = '$key_id',
                private_key = '$private_key',
                vendor_number = '$vendor_number',
                updated_at = CURRENT_TIMESTAMP
        ");

        echo json_encode([
            'success' => true,
            'message' => 'Credentials saved successfully'
        ]);
    } elseif ($method === 'DELETE') {
        query("DELETE FROM appstore_api_credentials WHERE project_id = $project_id");

        echo json_encode([
            'success' => true,
            'message' => 'Credentials deleted successfully'
        ]);
    }
}

function handleLocales($method)
{
    if ($method === 'GET') {
        $result = query("SELECT * FROM appstore_supported_locales WHERE is_active = 1 ORDER BY name");

        $locales = [];
        while ($row = fetch_assoc($result)) {
            $locales[] = $row;
        }

        echo json_encode([
            'success' => true,
            'locales' => $locales
        ]);
    }
}

function handleCategories($project_id, $app_id, $method)
{
    global $con;

    if (!$app_id) {
        // Return available categories list
        $categories = getAppStoreCategories();
        echo json_encode([
            'success' => true,
            'categories' => $categories
        ]);
        return;
    }

    // Verify app belongs to project
    $result = query("SELECT id FROM appstore_apps WHERE id = $app_id AND project_id = $project_id");
    if (!fetch_assoc($result)) {
        http_response_code(404);
        echo json_encode(['error' => 'App not found']);
        return;
    }

    if ($method === 'GET') {
        $result = query("SELECT * FROM appstore_app_categories WHERE app_id = $app_id");
        $categories = [];
        while ($row = fetch_assoc($result)) {
            $categories[] = $row;
        }

        echo json_encode([
            'success' => true,
            'categories' => $categories
        ]);
    } elseif ($method === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);

        if (empty($input['category_type']) || empty($input['category_id'])) {
            http_response_code(400);
            echo json_encode(['error' => 'category_type and category_id are required']);
            return;
        }

        $category_type = escape_string($input['category_type']);
        $category_id = escape_string($input['category_id']);
        $category_name = escape_string($input['category_name'] ?? '');
        $subcategory_id = escape_string($input['subcategory_id'] ?? '');
        $subcategory_name = escape_string($input['subcategory_name'] ?? '');

        query("
            INSERT INTO appstore_app_categories 
            (app_id, category_type, category_id, category_name, subcategory_id, subcategory_name)
            VALUES ($app_id, '$category_type', '$category_id', '$category_name', '$subcategory_id', '$subcategory_name')
            ON DUPLICATE KEY UPDATE
                category_id = '$category_id',
                category_name = '$category_name',
                subcategory_id = '$subcategory_id',
                subcategory_name = '$subcategory_name'
        ");

        echo json_encode([
            'success' => true,
            'message' => 'Category saved successfully'
        ]);
    }
}

function handleAgeRatings($project_id, $app_id, $method)
{
    global $con;

    if (!$app_id) {
        http_response_code(400);
        echo json_encode(['error' => 'App ID is required']);
        return;
    }

    // Verify app belongs to project
    $result = query("SELECT id FROM appstore_apps WHERE id = $app_id AND project_id = $project_id");
    if (!fetch_assoc($result)) {
        http_response_code(404);
        echo json_encode(['error' => 'App not found']);
        return;
    }

    if ($method === 'GET') {
        $result = query("SELECT * FROM appstore_age_ratings WHERE app_id = $app_id");
        $ageRating = fetch_assoc($result);

        echo json_encode([
            'success' => true,
            'age_rating' => $ageRating
        ]);
    } elseif ($method === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);

        $fields = [
            'alcohol_tobacco_or_drug_use_or_references',
            'contests',
            'gambling_simulated',
            'gambling',
            'horror_fear_themes',
            'mature_suggestive_themes',
            'medical_treatment_info',
            'profanity_or_crude_humor',
            'sexual_content_graphic_nudity',
            'sexual_content_or_nudity',
            'violence_cartoon_or_fantasy',
            'violence_realistic',
            'violence_realistic_prolonged_graphic',
            'unrestricted_web_access',
            'kids_band',
            'seventeen_plus'
        ];

        $insertFields = ['app_id'];
        $insertValues = [$app_id];
        $updateParts = [];

        foreach ($fields as $field) {
            if (isset($input[$field])) {
                $value = escape_string($input[$field]);
                $insertFields[] = $field;
                $insertValues[] = "'$value'";
                $updateParts[] = "$field = '$value'";
            }
        }

        $sql = "INSERT INTO appstore_age_ratings (" . implode(', ', $insertFields) . ") 
                VALUES (" . implode(', ', $insertValues) . ")
                ON DUPLICATE KEY UPDATE " . implode(', ', $updateParts) . ", updated_at = CURRENT_TIMESTAMP";

        query($sql);

        echo json_encode([
            'success' => true,
            'message' => 'Age rating saved successfully'
        ]);
    }
}

function handleSyncPull($project_id, $app_id)
{
    global $con;

    logOperation($project_id, $app_id, 'pull', 'started', '{}');

    try {
        // Get API credentials
        $result = query("SELECT * FROM appstore_api_credentials WHERE project_id = $project_id AND is_active = 1");
        $credentials = fetch_assoc($result);

        if (!$credentials) {
            logOperation($project_id, $app_id, 'pull', 'failed', '{}', 'No API credentials configured');
            echo json_encode([
                'success' => false,
                'error' => 'No API credentials configured. Please add your App Store Connect API credentials in Settings.'
            ]);
            return;
        }

        // Initialize API client
        $api = new AppStoreMetadataAPI(
            base64_decode($credentials['private_key']),
            $credentials['key_id'],
            $credentials['issuer_id']
        );

        // If app_id is provided, sync specific app; otherwise sync all apps
        if ($app_id) {
            $result = query("SELECT * FROM appstore_apps WHERE id = $app_id AND project_id = $project_id");
            $app = fetch_assoc($result);

            if (!$app) {
                throw new Exception('App not found');
            }

            $syncResult = syncAppFromAppStore($api, $app, $project_id);
            logOperation($project_id, $app_id, 'pull', 'completed', json_encode($syncResult));

            echo json_encode([
                'success' => true,
                'message' => 'App synced successfully',
                'details' => $syncResult
            ]);
        } else {
            // Fetch all apps from App Store Connect
            $appsData = $api->getApps();
            $syncedApps = [];

            foreach ($appsData as $appData) {
                $syncResult = syncAppDataToDatabase($appData, $project_id, $api);
                $syncedApps[] = $syncResult;
            }

            logOperation($project_id, null, 'pull', 'completed', json_encode(['synced_apps' => count($syncedApps)]));

            echo json_encode([
                'success' => true,
                'message' => 'Synced ' . count($syncedApps) . ' apps from App Store Connect',
                'synced_apps' => $syncedApps
            ]);
        }

        // Update last_used_at for credentials
        query("UPDATE appstore_api_credentials SET last_used_at = NOW() WHERE project_id = $project_id");

    } catch (Exception $e) {
        logOperation($project_id, $app_id, 'pull', 'failed', '{}', $e->getMessage());
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage()
        ]);
    }
}

function handleSyncPush($project_id, $app_id)
{
    global $con;

    logOperation($project_id, $app_id, 'push', 'started', '{}');

    try {
        // Get API credentials
        $result = query("SELECT * FROM appstore_api_credentials WHERE project_id = $project_id AND is_active = 1");
        $credentials = fetch_assoc($result);

        if (!$credentials) {
            logOperation($project_id, $app_id, 'push', 'failed', '{}', 'No API credentials configured');
            echo json_encode([
                'success' => false,
                'error' => 'No API credentials configured. Please add your App Store Connect API credentials in Settings.'
            ]);
            return;
        }

        if (!$app_id) {
            echo json_encode([
                'success' => false,
                'error' => 'App ID is required for push operation'
            ]);
            return;
        }

        // Get app data
        $result = query("SELECT * FROM appstore_apps WHERE id = $app_id AND project_id = $project_id");
        $app = fetch_assoc($result);

        if (!$app) {
            throw new Exception('App not found');
        }

        // Initialize API client
        $api = new AppStoreMetadataAPI(
            base64_decode($credentials['private_key']),
            $credentials['key_id'],
            $credentials['issuer_id']
        );

        $pushResults = [];

        // ========================================
        // ONLY PUSH DIRTY (MODIFIED) LOCALIZATIONS
        // ========================================

        // Count dirty entries first
        $dirtyAppLocsResult = query("SELECT COUNT(*) as count FROM appstore_app_localizations WHERE app_id = $app_id AND is_dirty = 1");
        $dirtyAppLocsCount = fetch_assoc($dirtyAppLocsResult)['count'] ?? 0;

        $dirtyVersionLocsResult = query("SELECT COUNT(*) as count FROM appstore_version_localizations vl 
            INNER JOIN appstore_app_versions v ON vl.version_id = v.id 
            WHERE v.app_id = $app_id AND vl.is_dirty = 1");
        $dirtyVersionLocsCount = fetch_assoc($dirtyVersionLocsResult)['count'] ?? 0;

        // If nothing is dirty, return early
        if ($dirtyAppLocsCount == 0 && $dirtyVersionLocsCount == 0) {
            echo json_encode([
                'success' => true,
                'message' => 'Keine Änderungen zu pushen - alles ist bereits synchronisiert.',
                'results' => [],
                'skipped_reason' => 'no_changes'
            ]);
            return;
        }

        // Get current appInfo ID from App Store (needed for creating new localizations)
        $appInfoId = null;
        $localeToIdMap = [];

        // Only fetch App Store data if we have dirty app localizations
        if ($dirtyAppLocsCount > 0) {
            try {
                $appInfos = $api->getAppInfo($app['app_id']);
                if (!empty($appInfos) && isset($appInfos[0]['id'])) {
                    $appInfoId = $appInfos[0]['id'];

                    // Fetch existing localizations to build ID map
                    try {
                        $existingAppLocalizations = $api->getAppInfoLocalizations($app['app_id']);
                        foreach ($existingAppLocalizations as $existingLoc) {
                            $locale = $existingLoc['attributes']['locale'] ?? null;
                            $id = $existingLoc['id'] ?? null;
                            if ($locale && $id) {
                                $localeToIdMap[$locale] = $id;
                            }
                        }
                    } catch (Exception $e) {
                        // Continue without map
                    }
                }
            } catch (Exception $e) {
                // Continue without appInfoId
            }
        }

        // Push ONLY dirty app-level localizations
        $locResult = query("SELECT * FROM appstore_app_localizations WHERE app_id = $app_id AND is_dirty = 1");
        while ($loc = fetch_assoc($locResult)) {
            $locId = (int) $loc['id'];

            // First, sync ID from our map if we don't have one
            if (empty($loc['appstore_localization_id']) && isset($localeToIdMap[$loc['locale']])) {
                $correctId = escape_string($localeToIdMap[$loc['locale']]);
                query("UPDATE appstore_app_localizations SET appstore_localization_id = '$correctId' WHERE id = $locId");
                $loc['appstore_localization_id'] = $correctId;
            }

            try {
                if (!empty($loc['appstore_localization_id'])) {
                    // Update existing
                    try {
                        $api->updateAppInfoLocalization($loc['appstore_localization_id'], [
                            'name' => $loc['name'],
                            'subtitle' => $loc['subtitle'],
                            'privacyPolicyUrl' => $loc['privacy_policy_url'],
                            'privacyPolicyText' => $loc['privacy_policy_text'],
                            'privacyChoicesUrl' => $loc['privacy_choices_url']
                        ]);
                        // Mark as synced
                        query("UPDATE appstore_app_localizations SET is_dirty = 0, last_synced_at = NOW() WHERE id = $locId");
                        $pushResults[] = ['type' => 'app_localization', 'locale' => $loc['locale'], 'status' => 'updated'];
                    } catch (Exception $updateError) {
                        if (strpos($updateError->getMessage(), 'There is no resource') !== false && $appInfoId) {
                            // Resource doesn't exist - create it
                            $createResult = $api->createAppInfoLocalization($appInfoId, $loc['locale'], [
                                'name' => $loc['name'],
                                'subtitle' => $loc['subtitle'],
                                'privacyPolicyUrl' => $loc['privacy_policy_url'],
                                'privacyPolicyText' => $loc['privacy_policy_text'],
                                'privacyChoicesUrl' => $loc['privacy_choices_url']
                            ]);
                            if (!empty($createResult['data']['id'])) {
                                $newLocId = escape_string($createResult['data']['id']);
                                query("UPDATE appstore_app_localizations SET appstore_localization_id = '$newLocId', is_dirty = 0, last_synced_at = NOW() WHERE id = $locId");
                            }
                            $pushResults[] = ['type' => 'app_localization', 'locale' => $loc['locale'], 'status' => 'recreated'];
                        } else {
                            throw $updateError;
                        }
                    }
                } elseif ($appInfoId) {
                    // Create new
                    try {
                        $createResult = $api->createAppInfoLocalization($appInfoId, $loc['locale'], [
                            'name' => $loc['name'],
                            'subtitle' => $loc['subtitle'],
                            'privacyPolicyUrl' => $loc['privacy_policy_url'],
                            'privacyPolicyText' => $loc['privacy_policy_text'],
                            'privacyChoicesUrl' => $loc['privacy_choices_url']
                        ]);
                        if (!empty($createResult['data']['id'])) {
                            $newLocId = escape_string($createResult['data']['id']);
                            query("UPDATE appstore_app_localizations SET appstore_localization_id = '$newLocId', is_dirty = 0, last_synced_at = NOW() WHERE id = $locId");
                        }
                        $pushResults[] = ['type' => 'app_localization', 'locale' => $loc['locale'], 'status' => 'created'];
                    } catch (Exception $createError) {
                        if (strpos($createError->getMessage(), 'already exists') !== false) {
                            // Already exists - fetch ID and update
                            $allLocs = $api->getAppInfoLocalizations($app['app_id']);
                            foreach ($allLocs as $existingLoc) {
                                if (($existingLoc['attributes']['locale'] ?? null) === $loc['locale']) {
                                    $existingId = escape_string($existingLoc['id']);
                                    $api->updateAppInfoLocalization($existingId, [
                                        'name' => $loc['name'],
                                        'subtitle' => $loc['subtitle'],
                                        'privacyPolicyUrl' => $loc['privacy_policy_url'],
                                        'privacyPolicyText' => $loc['privacy_policy_text'],
                                        'privacyChoicesUrl' => $loc['privacy_choices_url']
                                    ]);
                                    query("UPDATE appstore_app_localizations SET appstore_localization_id = '$existingId', is_dirty = 0, last_synced_at = NOW() WHERE id = $locId");
                                    $pushResults[] = ['type' => 'app_localization', 'locale' => $loc['locale'], 'status' => 'synced_and_updated'];
                                    break;
                                }
                            }
                        } else {
                            throw $createError;
                        }
                    }
                } else {
                    $pushResults[] = ['type' => 'app_localization', 'locale' => $loc['locale'], 'status' => 'skipped', 'reason' => 'No appInfoId available'];
                }
            } catch (Exception $e) {
                $pushResults[] = ['type' => 'app_localization', 'locale' => $loc['locale'], 'status' => 'failed', 'error' => $e->getMessage()];
            }
        }

        // Editable version states
        $editableStates = ['PREPARE_FOR_SUBMISSION', 'DEVELOPER_REJECTED', 'REJECTED', 'WAITING_FOR_REVIEW', 'DEVELOPER_REMOVED_FROM_SALE'];

        // Push ONLY dirty version localizations
        $verResult = query("SELECT v.*, 
            (SELECT COUNT(*) FROM appstore_version_localizations vl WHERE vl.version_id = v.id AND vl.is_dirty = 1) as dirty_count 
            FROM appstore_app_versions v WHERE v.app_id = $app_id HAVING dirty_count > 0");

        while ($version = fetch_assoc($verResult)) {
            $versionState = strtoupper($version['status'] ?? '');
            $isEditable = in_array($versionState, $editableStates) || empty($versionState) || $versionState === 'DRAFT';

            if (!$isEditable) {
                $pushResults[] = [
                    'type' => 'version',
                    'version' => $version['version_string'],
                    'status' => 'skipped',
                    'reason' => "Version state '$versionState' is not editable"
                ];
                continue;
            }

            // Build version locale ID map only if needed
            $versionLocaleToIdMap = [];
            if (!empty($version['appstore_version_id'])) {
                try {
                    $existingVersionLocs = $api->getAppStoreVersionLocalizations($version['appstore_version_id']);
                    foreach ($existingVersionLocs as $existingVLoc) {
                        $locale = $existingVLoc['attributes']['locale'] ?? null;
                        $id = $existingVLoc['id'] ?? null;
                        if ($locale && $id) {
                            $versionLocaleToIdMap[$locale] = $id;
                        }
                    }
                } catch (Exception $e) {
                    // Continue
                }
            }

            // Only get dirty version localizations
            $vlocResult = query("SELECT * FROM appstore_version_localizations WHERE version_id = " . (int) $version['id'] . " AND is_dirty = 1");
            while ($vloc = fetch_assoc($vlocResult)) {
                $vlocId = (int) $vloc['id'];

                // Sync ID from map if needed
                if (empty($vloc['appstore_localization_id']) && isset($versionLocaleToIdMap[$vloc['locale']])) {
                    $correctId = escape_string($versionLocaleToIdMap[$vloc['locale']]);
                    query("UPDATE appstore_version_localizations SET appstore_localization_id = '$correctId' WHERE id = $vlocId");
                    $vloc['appstore_localization_id'] = $correctId;
                }

                try {
                    if (!empty($vloc['appstore_localization_id'])) {
                        try {
                            $api->updateAppStoreVersionLocalization($vloc['appstore_localization_id'], [
                                'description' => $vloc['description'],
                                'keywords' => $vloc['keywords'],
                                'whatsNew' => $vloc['whats_new'],
                                'marketingUrl' => $vloc['marketing_url'],
                                'supportUrl' => $vloc['support_url'],
                                'promotionalText' => $vloc['promotional_text']
                            ]);
                            query("UPDATE appstore_version_localizations SET is_dirty = 0, last_synced_at = NOW() WHERE id = $vlocId");
                            $pushResults[] = ['type' => 'version_localization', 'version' => $version['version_string'], 'locale' => $vloc['locale'], 'status' => 'updated'];
                        } catch (Exception $updateError) {
                            if (strpos($updateError->getMessage(), 'There is no resource') !== false && !empty($version['appstore_version_id'])) {
                                $createResult = $api->createAppStoreVersionLocalization($version['appstore_version_id'], $vloc['locale'], [
                                    'description' => $vloc['description'],
                                    'keywords' => $vloc['keywords'],
                                    'whatsNew' => $vloc['whats_new'],
                                    'marketingUrl' => $vloc['marketing_url'],
                                    'supportUrl' => $vloc['support_url'],
                                    'promotionalText' => $vloc['promotional_text']
                                ]);
                                if (!empty($createResult['data']['id'])) {
                                    $newLocId = escape_string($createResult['data']['id']);
                                    query("UPDATE appstore_version_localizations SET appstore_localization_id = '$newLocId', is_dirty = 0, last_synced_at = NOW() WHERE id = $vlocId");
                                }
                                $pushResults[] = ['type' => 'version_localization', 'version' => $version['version_string'], 'locale' => $vloc['locale'], 'status' => 'recreated'];
                            } else {
                                throw $updateError;
                            }
                        }
                    } elseif (!empty($version['appstore_version_id'])) {
                        try {
                            $createResult = $api->createAppStoreVersionLocalization($version['appstore_version_id'], $vloc['locale'], [
                                'description' => $vloc['description'],
                                'keywords' => $vloc['keywords'],
                                'whatsNew' => $vloc['whats_new'],
                                'marketingUrl' => $vloc['marketing_url'],
                                'supportUrl' => $vloc['support_url'],
                                'promotionalText' => $vloc['promotional_text']
                            ]);
                            if (!empty($createResult['data']['id'])) {
                                $newLocId = escape_string($createResult['data']['id']);
                                query("UPDATE appstore_version_localizations SET appstore_localization_id = '$newLocId', is_dirty = 0, last_synced_at = NOW() WHERE id = $vlocId");
                            }
                            $pushResults[] = ['type' => 'version_localization', 'version' => $version['version_string'], 'locale' => $vloc['locale'], 'status' => 'created'];
                        } catch (Exception $createError) {
                            if (strpos($createError->getMessage(), 'already exists') !== false) {
                                $allVersionLocs = $api->getAppStoreVersionLocalizations($version['appstore_version_id']);
                                foreach ($allVersionLocs as $existingVLoc) {
                                    if (($existingVLoc['attributes']['locale'] ?? null) === $vloc['locale']) {
                                        $existingId = escape_string($existingVLoc['id']);
                                        $api->updateAppStoreVersionLocalization($existingId, [
                                            'description' => $vloc['description'],
                                            'keywords' => $vloc['keywords'],
                                            'whatsNew' => $vloc['whats_new'],
                                            'marketingUrl' => $vloc['marketing_url'],
                                            'supportUrl' => $vloc['support_url'],
                                            'promotionalText' => $vloc['promotional_text']
                                        ]);
                                        query("UPDATE appstore_version_localizations SET appstore_localization_id = '$existingId', is_dirty = 0, last_synced_at = NOW() WHERE id = $vlocId");
                                        $pushResults[] = ['type' => 'version_localization', 'version' => $version['version_string'], 'locale' => $vloc['locale'], 'status' => 'synced_and_updated'];
                                        break;
                                    }
                                }
                            } else {
                                throw $createError;
                            }
                        }
                    } else {
                        $pushResults[] = ['type' => 'version_localization', 'version' => $version['version_string'], 'locale' => $vloc['locale'], 'status' => 'skipped', 'reason' => 'No appstore IDs available'];
                    }
                } catch (Exception $e) {
                    $pushResults[] = ['type' => 'version_localization', 'version' => $version['version_string'], 'locale' => $vloc['locale'], 'status' => 'failed', 'error' => $e->getMessage()];
                }
            }
        }

        logOperation($project_id, $app_id, 'push', 'completed', json_encode($pushResults));

        // Update last_used_at for credentials
        query("UPDATE appstore_api_credentials SET last_used_at = NOW() WHERE project_id = $project_id");

        // ========================================
        // PUSH CATEGORIES
        // ========================================
        try {
            // Get both primary and secondary categories
            $catResult = query("SELECT * FROM appstore_app_categories WHERE app_id = $app_id");
            $primaryCat = null;
            $secondaryCat = null;

            while ($cat = fetch_assoc($catResult)) {
                if ($cat['category_type'] === 'primary') {
                    $primaryCat = $cat;
                } elseif ($cat['category_type'] === 'secondary') {
                    $secondaryCat = $cat;
                }
            }

            if ($primaryCat || $secondaryCat) {
                // Get appInfoId from App Store
                $appInfos = $api->getAppInfo($app['app_id']);
                if (!empty($appInfos) && isset($appInfos[0]['id'])) {
                    $appInfoId = $appInfos[0]['id'];

                    $categoryData = [
                        'primaryCategory' => $primaryCat['category_id'] ?? null,
                        'primarySubcategoryOne' => $primaryCat['subcategory_id'] ?? null,
                        'secondaryCategory' => $secondaryCat['category_id'] ?? null,
                        'secondarySubcategoryOne' => $secondaryCat['subcategory_id'] ?? null
                    ];

                    $api->updateAppCategories($appInfoId, $categoryData);
                    $pushResults[] = ['type' => 'categories', 'status' => 'updated'];
                }
            }
        } catch (Exception $e) {
            $pushResults[] = ['type' => 'categories', 'status' => 'failed', 'error' => $e->getMessage()];
        }

        // ========================================
        // PUSH AGE RATING
        // ========================================
        try {
            $ageResult = query("SELECT * FROM appstore_age_ratings WHERE app_id = $app_id");
            $ageRating = fetch_assoc($ageResult);

            if ($ageRating) {
                // Get age rating declaration from App Store
                $ageRatingDeclaration = $api->getAgeRatingDeclaration($app['app_id']);

                if ($ageRatingDeclaration && isset($ageRatingDeclaration['id'])) {
                    $ageRatingData = [
                        'alcoholTobaccoOrDrugUseOrReferences' => $ageRating['alcohol_tobacco_or_drug_use_or_references'] ?? 'NONE',
                        'contests' => $ageRating['contests'] ?? 'NONE',
                        'gamblingSimulated' => $ageRating['gambling_simulated'] ?? 'NONE',
                        'gambling' => $ageRating['gambling'] === '1' || $ageRating['gambling'] === true,
                        'horrorOrFearThemes' => $ageRating['horror_fear_themes'] ?? 'NONE',
                        'matureOrSuggestiveThemes' => $ageRating['mature_suggestive_themes'] ?? 'NONE',
                        'medicalOrTreatmentInformation' => $ageRating['medical_treatment_info'] ?? 'NONE',
                        'profanityOrCrudeHumor' => $ageRating['profanity_or_crude_humor'] ?? 'NONE',
                        'sexualContentGraphicAndNudity' => $ageRating['sexual_content_graphic_nudity'] ?? 'NONE',
                        'sexualContentOrNudity' => $ageRating['sexual_content_or_nudity'] ?? 'NONE',
                        'violenceCartoonOrFantasy' => $ageRating['violence_cartoon_or_fantasy'] ?? 'NONE',
                        'violenceRealistic' => $ageRating['violence_realistic'] ?? 'NONE',
                        'violenceRealisticProlongedGraphicOrSadistic' => $ageRating['violence_realistic_prolonged_graphic'] ?? 'NONE',
                        'kidsAgeBand' => $ageRating['kids_band'] !== 'NOT_MADE_FOR_KIDS' ? $ageRating['kids_band'] : null,
                        'seventeenPlus' => $ageRating['seventeen_plus'] === '1' || $ageRating['seventeen_plus'] === true,
                        'unrestrictedWebAccess' => $ageRating['unrestricted_web_access'] === '1' || $ageRating['unrestricted_web_access'] === true
                    ];

                    // Remove null values
                    $ageRatingData = array_filter($ageRatingData, function ($value) {
                        return $value !== null;
                    });

                    $api->updateAgeRatingDeclaration($ageRatingDeclaration['id'], $ageRatingData);
                    $pushResults[] = ['type' => 'age_rating', 'status' => 'updated'];
                }
            }
        } catch (Exception $e) {
            $pushResults[] = ['type' => 'age_rating', 'status' => 'failed', 'error' => $e->getMessage()];
        }

        echo json_encode([
            'success' => true,
            'message' => 'Changes pushed to App Store Connect',
            'results' => $pushResults,
            'stats' => [
                'dirty_app_localizations' => $dirtyAppLocsCount,
                'dirty_version_localizations' => $dirtyVersionLocsCount
            ]
        ]);

    } catch (Exception $e) {
        logOperation($project_id, $app_id, 'push', 'failed', '{}', $e->getMessage());
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage()
        ]);
    }
}

// Browse all apps from App Store Connect account
function handleBrowseApps($project_id)
{
    try {
        // Get API credentials
        $result = query("SELECT * FROM appstore_api_credentials WHERE project_id = $project_id AND is_active = 1");
        $credentials = fetch_assoc($result);

        if (!$credentials) {
            echo json_encode([
                'success' => false,
                'error' => 'No API credentials configured. Please add your App Store Connect API credentials in Settings.'
            ]);
            return;
        }

        // Initialize API client
        $api = new AppStoreMetadataAPI(
            base64_decode($credentials['private_key']),
            $credentials['key_id'],
            $credentials['issuer_id']
        );

        // Get all apps from App Store Connect
        $appsData = $api->getApps();

        // Get already connected apps for this project
        $connectedResult = query("SELECT app_id FROM appstore_apps WHERE project_id = $project_id");
        $connectedAppIds = [];
        while ($row = fetch_assoc($connectedResult)) {
            $connectedAppIds[] = $row['app_id'];
        }

        // Format apps for response
        $apps = [];
        foreach ($appsData as $appData) {
            $attrs = $appData['attributes'] ?? [];
            $apps[] = [
                'id' => $appData['id'],
                'name' => $attrs['name'] ?? 'Unknown',
                'bundle_id' => $attrs['bundleId'] ?? '',
                'sku' => $attrs['sku'] ?? '',
                'primary_locale' => $attrs['primaryLocale'] ?? 'en-US',
                'is_connected' => in_array($appData['id'], $connectedAppIds)
            ];
        }

        // Update last_used_at for credentials
        query("UPDATE appstore_api_credentials SET last_used_at = NOW() WHERE project_id = $project_id");

        echo json_encode([
            'success' => true,
            'apps' => $apps,
            'count' => count($apps)
        ]);

    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage()
        ]);
    }
}

// Connect an existing app from App Store Connect
function handleConnectApp($project_id)
{
    global $con;

    $input = json_decode(file_get_contents('php://input'), true);
    $appStoreId = $input['app_store_id'] ?? null;

    if (!$appStoreId) {
        http_response_code(400);
        echo json_encode(['error' => 'app_store_id is required']);
        return;
    }

    try {
        // Get API credentials
        $result = query("SELECT * FROM appstore_api_credentials WHERE project_id = $project_id AND is_active = 1");
        $credentials = fetch_assoc($result);

        if (!$credentials) {
            echo json_encode([
                'success' => false,
                'error' => 'No API credentials configured.'
            ]);
            return;
        }

        // Check if already connected
        $appStoreIdEsc = escape_string($appStoreId);
        $existing = query("SELECT id FROM appstore_apps WHERE app_id = '$appStoreIdEsc' AND project_id = $project_id");
        if (fetch_assoc($existing)) {
            echo json_encode([
                'success' => false,
                'error' => 'This app is already connected to this project.'
            ]);
            return;
        }

        // Initialize API client
        $api = new AppStoreMetadataAPI(
            base64_decode($credentials['private_key']),
            $credentials['key_id'],
            $credentials['issuer_id']
        );

        // Fetch app data from App Store Connect
        $appData = $api->getApp($appStoreId);

        if (!$appData) {
            throw new Exception('App not found in App Store Connect');
        }

        // Sync the app to our database
        $syncResult = syncAppDataToDatabase($appData, $project_id, $api);

        logOperation($project_id, $syncResult['app_id'], 'connect', 'completed', json_encode([
            'app_store_id' => $appStoreId,
            'name' => $syncResult['name']
        ]));

        // Update last_used_at for credentials
        query("UPDATE appstore_api_credentials SET last_used_at = NOW() WHERE project_id = $project_id");

        echo json_encode([
            'success' => true,
            'message' => 'App connected successfully',
            'app' => $syncResult
        ]);

    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage()
        ]);
    }
}

// Sync specific app from App Store
function syncAppFromAppStore($api, $app, $project_id)
{
    global $con;

    $appStoreId = $app['app_id'];
    $appData = $api->getApp($appStoreId);

    if (!$appData) {
        throw new Exception('Could not fetch app from App Store Connect');
    }

    return syncAppDataToDatabase($appData, $project_id, $api, $app['id']);
}

// Save app data from API to database
function syncAppDataToDatabase($appData, $project_id, $api, $existingAppId = null)
{
    global $con;

    $attributes = $appData['attributes'] ?? [];
    $app_store_id = escape_string($appData['id'] ?? '');
    $bundle_id = escape_string($attributes['bundleId'] ?? '');
    $name = escape_string($attributes['name'] ?? '');
    $sku = escape_string($attributes['sku'] ?? '');
    $primary_locale = escape_string($attributes['primaryLocale'] ?? 'en-US');
    $available_territories = isset($attributes['isAvailableInNewTerritories']) && $attributes['isAvailableInNewTerritories'] ? '1' : '0';

    // Fetch content rights from appInfo - it's not in the main app attributes
    $content_rights = '';
    try {
        $appInfos = $api->getAppInfo($app_store_id);
        if (!empty($appInfos)) {
            $firstAppInfo = $appInfos[0];
            $appInfoAttrs = $firstAppInfo['attributes'] ?? [];
            $content_rights = escape_string($appInfoAttrs['contentRightsDeclaration'] ?? '');
        }
    } catch (Exception $e) {
        error_log("Failed to fetch contentRightsDeclaration: " . $e->getMessage());
    }

    if ($existingAppId) {
        // Update existing app
        query("
            UPDATE appstore_apps SET
                bundle_id = '$bundle_id',
                name = '$name',
                sku = '$sku',
                primary_locale = '$primary_locale',
                content_rights_declaration = '$content_rights',
                is_available_in_new_territories = '$available_territories',
                last_synced_at = NOW()
            WHERE id = $existingAppId
        ");
        $localAppId = $existingAppId;
    } else {
        // Check if app already exists
        $result = query("SELECT id FROM appstore_apps WHERE app_id = '$app_store_id' AND project_id = $project_id");
        $existing = fetch_assoc($result);

        if ($existing) {
            $localAppId = $existing['id'];
            query("
                UPDATE appstore_apps SET
                    bundle_id = '$bundle_id',
                    name = '$name',
                    sku = '$sku',
                    primary_locale = '$primary_locale',
                    content_rights_declaration = '$content_rights',
                    is_available_in_new_territories = '$available_territories',
                    last_synced_at = NOW()
                WHERE id = $localAppId
            ");
        } else {
            // Insert new app
            query("
                INSERT INTO appstore_apps (project_id, app_id, bundle_id, name, sku, primary_locale, content_rights_declaration, is_available_in_new_territories, last_synced_at, status)
                VALUES ($project_id, '$app_store_id', '$bundle_id', '$name', '$sku', '$primary_locale', '$content_rights', '$available_territories', NOW(), 'active')
            ");
            $localAppId = mysqli_insert_id($con);
        }
    }

    // Sync app info localizations
    try {
        $appInfoLocalizations = $api->getAppInfoLocalizations($app_store_id);
        foreach ($appInfoLocalizations as $loc) {
            $locId = escape_string($loc['id'] ?? '');
            $locAttrs = $loc['attributes'] ?? [];
            $locale = escape_string($locAttrs['locale'] ?? '');
            $locName = escape_string($locAttrs['name'] ?? '');
            $subtitle = escape_string($locAttrs['subtitle'] ?? '');
            $privacyUrl = escape_string($locAttrs['privacyPolicyUrl'] ?? '');
            $privacyText = escape_string($locAttrs['privacyPolicyText'] ?? '');
            $privacyChoices = escape_string($locAttrs['privacyChoicesUrl'] ?? '');

            if ($locale) {
                query("
                    INSERT INTO appstore_app_localizations 
                    (app_id, locale, name, subtitle, privacy_policy_url, privacy_policy_text, privacy_choices_url, appstore_localization_id)
                    VALUES ($localAppId, '$locale', '$locName', '$subtitle', '$privacyUrl', '$privacyText', '$privacyChoices', '$locId')
                    ON DUPLICATE KEY UPDATE
                        name = '$locName',
                        subtitle = '$subtitle',
                        privacy_policy_url = '$privacyUrl',
                        privacy_policy_text = '$privacyText',
                        privacy_choices_url = '$privacyChoices',
                        appstore_localization_id = '$locId',
                        updated_at = NOW()
                ");
            }
        }
    } catch (Exception $e) {
        error_log("Failed to sync app localizations: " . $e->getMessage());
    }

    // Sync versions
    try {
        $versions = $api->getAppStoreVersions($app_store_id);
        foreach ($versions as $ver) {
            $verId = escape_string($ver['id'] ?? '');
            $verAttrs = $ver['attributes'] ?? [];
            $versionString = escape_string($verAttrs['versionString'] ?? '');
            $platform = escape_string($verAttrs['platform'] ?? 'IOS');
            $appStoreState = escape_string($verAttrs['appStoreState'] ?? '');
            $releaseType = escape_string($verAttrs['releaseType'] ?? '');
            $copyright = escape_string($verAttrs['copyright'] ?? '');

            // Check if version exists
            $result = query("SELECT id FROM appstore_app_versions WHERE appstore_version_id = '$verId'");
            $existingVer = fetch_assoc($result);

            if ($existingVer) {
                $localVersionId = $existingVer['id'];
                query("
                    UPDATE appstore_app_versions SET
                        version_string = '$versionString',
                        platform = '$platform',
                        release_type = '$releaseType',
                        copyright = '$copyright',
                        status = '$appStoreState'
                    WHERE id = $localVersionId
                ");
            } else {
                query("
                    INSERT INTO appstore_app_versions 
                    (app_id, appstore_version_id, version_string, platform, release_type, copyright, status)
                    VALUES ($localAppId, '$verId', '$versionString', '$platform', '$releaseType', '$copyright', '$appStoreState')
                ");
                $localVersionId = mysqli_insert_id($con);
            }

            // Sync version localizations
            try {
                $versionLocalizations = $api->getAppStoreVersionLocalizations($verId);
                foreach ($versionLocalizations as $vloc) {
                    $vlocId = escape_string($vloc['id'] ?? '');
                    $vlocAttrs = $vloc['attributes'] ?? [];
                    $vlocLocale = escape_string($vlocAttrs['locale'] ?? '');
                    $description = escape_string($vlocAttrs['description'] ?? '');
                    $keywords = escape_string($vlocAttrs['keywords'] ?? '');
                    $whatsNew = escape_string($vlocAttrs['whatsNew'] ?? '');
                    $marketingUrl = escape_string($vlocAttrs['marketingUrl'] ?? '');
                    $supportUrl = escape_string($vlocAttrs['supportUrl'] ?? '');
                    $promoText = escape_string($vlocAttrs['promotionalText'] ?? '');

                    if ($vlocLocale) {
                        query("
                            INSERT INTO appstore_version_localizations 
                            (version_id, locale, description, keywords, whats_new, marketing_url, support_url, promotional_text, appstore_localization_id)
                            VALUES ($localVersionId, '$vlocLocale', '$description', '$keywords', '$whatsNew', '$marketingUrl', '$supportUrl', '$promoText', '$vlocId')
                            ON DUPLICATE KEY UPDATE
                                description = '$description',
                                keywords = '$keywords',
                                whats_new = '$whatsNew',
                                marketing_url = '$marketingUrl',
                                support_url = '$supportUrl',
                                promotional_text = '$promoText',
                                appstore_localization_id = '$vlocId',
                                updated_at = NOW()
                        ");
                    }
                }
            } catch (Exception $e) {
                error_log("Failed to sync version localizations: " . $e->getMessage());
            }
        }
    } catch (Exception $e) {
        error_log("Failed to sync versions: " . $e->getMessage());
    }

    // Sync categories
    try {
        $categoryData = $api->getAppCategories($app_store_id);
        if ($categoryData && $categoryData['appInfoId']) {
            // Primary category
            if (!empty($categoryData['primaryCategoryId'])) {
                $primaryCatId = escape_string($categoryData['primaryCategoryId']);
                $primaryCatName = escape_string($categoryData['primaryCategoryName'] ?? '');
                $primarySubId = escape_string($categoryData['primarySubcategoryId'] ?? '');
                $primarySubName = escape_string($categoryData['primarySubcategoryName'] ?? '');

                query("
                    INSERT INTO appstore_app_categories 
                    (app_id, category_type, category_id, category_name, subcategory_id, subcategory_name)
                    VALUES ($localAppId, 'primary', '$primaryCatId', '$primaryCatName', '$primarySubId', '$primarySubName')
                    ON DUPLICATE KEY UPDATE
                        category_id = '$primaryCatId',
                        category_name = '$primaryCatName',
                        subcategory_id = '$primarySubId',
                        subcategory_name = '$primarySubName'
                ");
            }

            // Secondary category
            if (!empty($categoryData['secondaryCategoryId'])) {
                $secondaryCatId = escape_string($categoryData['secondaryCategoryId']);
                $secondaryCatName = escape_string($categoryData['secondaryCategoryName'] ?? '');
                $secondarySubId = escape_string($categoryData['secondarySubcategoryId'] ?? '');
                $secondarySubName = escape_string($categoryData['secondarySubcategoryName'] ?? '');

                query("
                    INSERT INTO appstore_app_categories 
                    (app_id, category_type, category_id, category_name, subcategory_id, subcategory_name)
                    VALUES ($localAppId, 'secondary', '$secondaryCatId', '$secondaryCatName', '$secondarySubId', '$secondarySubName')
                    ON DUPLICATE KEY UPDATE
                        category_id = '$secondaryCatId',
                        category_name = '$secondaryCatName',
                        subcategory_id = '$secondarySubId',
                        subcategory_name = '$secondarySubName'
                ");
            }
        }
    } catch (Exception $e) {
        error_log("Failed to sync categories: " . $e->getMessage());
    }

    // Sync age rating
    try {
        $ageRatingData = $api->getAgeRatingDeclaration($app_store_id);
        if ($ageRatingData && isset($ageRatingData['attributes'])) {
            $attrs = $ageRatingData['attributes'];

            $alcoholTobaccoDrugs = escape_string($attrs['alcoholTobaccoOrDrugUseOrReferences'] ?? 'NONE');
            $contests = escape_string($attrs['contests'] ?? 'NONE');
            $gamblingSimulated = escape_string($attrs['gamblingSimulated'] ?? 'NONE');
            $gambling = isset($attrs['gambling']) && $attrs['gambling'] ? '1' : '0';
            $horrorFear = escape_string($attrs['horrorOrFearThemes'] ?? 'NONE');
            $matureSuggestive = escape_string($attrs['matureOrSuggestiveThemes'] ?? 'NONE');
            $medicalTreatment = escape_string($attrs['medicalOrTreatmentInformation'] ?? 'NONE');
            $profanityHumor = escape_string($attrs['profanityOrCrudeHumor'] ?? 'NONE');
            $sexualContent = escape_string($attrs['sexualContentGraphicAndNudity'] ?? 'NONE');
            $sexualContentNudity = escape_string($attrs['sexualContentOrNudity'] ?? 'NONE');
            $violenceCartoon = escape_string($attrs['violenceCartoonOrFantasy'] ?? 'NONE');
            $violenceRealistic = escape_string($attrs['violenceRealistic'] ?? 'NONE');
            $violenceGraphic = escape_string($attrs['violenceRealisticProlongedGraphicOrSadistic'] ?? 'NONE');
            $unrestrictedWeb = isset($attrs['unrestrictedWebAccess']) && $attrs['unrestrictedWebAccess'] ? '1' : '0';
            $kidsBand = escape_string($attrs['kidsAgeBand'] ?? 'NOT_MADE_FOR_KIDS');
            $seventeenPlus = isset($attrs['seventeenPlus']) && $attrs['seventeenPlus'] ? '1' : '0';

            query("
                INSERT INTO appstore_age_ratings 
                (app_id, alcohol_tobacco_or_drug_use_or_references, contests, gambling_simulated, gambling,
                 horror_fear_themes, mature_suggestive_themes, medical_treatment_info, profanity_or_crude_humor,
                 sexual_content_graphic_nudity, sexual_content_or_nudity, violence_cartoon_or_fantasy,
                 violence_realistic, violence_realistic_prolonged_graphic, unrestricted_web_access, kids_band, seventeen_plus)
                VALUES ($localAppId, '$alcoholTobaccoDrugs', '$contests', '$gamblingSimulated', '$gambling',
                        '$horrorFear', '$matureSuggestive', '$medicalTreatment', '$profanityHumor',
                        '$sexualContent', '$sexualContentNudity', '$violenceCartoon', '$violenceRealistic',
                        '$violenceGraphic', '$unrestrictedWeb', '$kidsBand', '$seventeenPlus')
                ON DUPLICATE KEY UPDATE
                    alcohol_tobacco_or_drug_use_or_references = '$alcoholTobaccoDrugs',
                    contests = '$contests',
                    gambling_simulated = '$gamblingSimulated',
                    gambling = '$gambling',
                    horror_fear_themes = '$horrorFear',
                    mature_suggestive_themes = '$matureSuggestive',
                    medical_treatment_info = '$medicalTreatment',
                    profanity_or_crude_humor = '$profanityHumor',
                    sexual_content_graphic_nudity = '$sexualContent',
                    sexual_content_or_nudity = '$sexualContentNudity',
                    violence_cartoon_or_fantasy = '$violenceCartoon',
                    violence_realistic = '$violenceRealistic',
                    violence_realistic_prolonged_graphic = '$violenceGraphic',
                    unrestricted_web_access = '$unrestrictedWeb',
                    kids_band = '$kidsBand',
                    seventeen_plus = '$seventeenPlus',
                    updated_at = NOW()
            ");
        }
    } catch (Exception $e) {
        error_log("Failed to sync age rating: " . $e->getMessage());
    }

    return [
        'app_id' => $localAppId,
        'app_store_id' => $app_store_id,
        'name' => $name
    ];
}

function handleDashboard($project_id)
{
    $stats = [];

    // Total apps
    $result = query("SELECT COUNT(*) as cnt FROM appstore_apps WHERE project_id = $project_id");
    $row = fetch_assoc($result);
    $stats['total_apps'] = (int) ($row['cnt'] ?? 0);

    // Total versions
    $result = query("
        SELECT COUNT(*) as cnt FROM appstore_app_versions v
        JOIN appstore_apps a ON v.app_id = a.id
        WHERE a.project_id = $project_id
    ");
    $row = fetch_assoc($result);
    $stats['total_versions'] = (int) ($row['cnt'] ?? 0);

    // Total localizations
    $result = query("
        SELECT COUNT(DISTINCT l.locale) as cnt FROM appstore_app_localizations l
        JOIN appstore_apps a ON l.app_id = a.id
        WHERE a.project_id = $project_id
    ");
    $row = fetch_assoc($result);
    $stats['total_locales'] = (int) ($row['cnt'] ?? 0);

    // Has credentials
    $result = query("SELECT COUNT(*) as cnt FROM appstore_api_credentials WHERE project_id = $project_id AND is_active = 1");
    $row = fetch_assoc($result);
    $stats['has_credentials'] = (int) ($row['cnt'] ?? 0) > 0;

    // Recent apps
    $result = query("
        SELECT a.*, COUNT(DISTINCT v.id) as version_count
        FROM appstore_apps a
        LEFT JOIN appstore_app_versions v ON a.id = v.app_id
        WHERE a.project_id = $project_id
        GROUP BY a.id
        ORDER BY a.updated_at DESC
        LIMIT 5
    ");
    $recentApps = [];
    while ($row = fetch_assoc($result)) {
        $recentApps[] = $row;
    }

    // Recent activity
    $result = query("
        SELECT * FROM appstore_sync_log
        WHERE project_id = $project_id
        ORDER BY created_at DESC
        LIMIT 10
    ");
    $recentActivity = [];
    while ($row = fetch_assoc($result)) {
        $recentActivity[] = $row;
    }

    echo json_encode([
        'success' => true,
        'stats' => $stats,
        'recent_apps' => $recentApps,
        'recent_activity' => $recentActivity
    ]);
}

// ============================================
// HELPER FUNCTIONS
// ============================================

function logOperation($project_id, $app_id, $operation, $status, $details, $error = null)
{
    $details = escape_string($details);
    $error = $error ? "'" . escape_string($error) . "'" : "NULL";
    $app_id = $app_id ?: "NULL";

    query("
        INSERT INTO appstore_sync_log (project_id, app_id, operation, status, details, error_message)
        VALUES ($project_id, $app_id, '$operation', '$status', '$details', $error)
    ");
}

function getAppStoreCategories()
{
    return [
        ['id' => 'BOOKS', 'name' => 'Books'],
        ['id' => 'BUSINESS', 'name' => 'Business'],
        ['id' => 'DEVELOPER_TOOLS', 'name' => 'Developer Tools'],
        ['id' => 'EDUCATION', 'name' => 'Education'],
        ['id' => 'ENTERTAINMENT', 'name' => 'Entertainment'],
        ['id' => 'FINANCE', 'name' => 'Finance'],
        ['id' => 'FOOD_AND_DRINK', 'name' => 'Food & Drink'],
        [
            'id' => 'GAMES',
            'name' => 'Games',
            'subcategories' => [
                ['id' => 'GAMES_ACTION', 'name' => 'Action'],
                ['id' => 'GAMES_ADVENTURE', 'name' => 'Adventure'],
                ['id' => 'GAMES_BOARD', 'name' => 'Board'],
                ['id' => 'GAMES_CARD', 'name' => 'Card'],
                ['id' => 'GAMES_CASINO', 'name' => 'Casino'],
                ['id' => 'GAMES_CASUAL', 'name' => 'Casual'],
                ['id' => 'GAMES_FAMILY', 'name' => 'Family'],
                ['id' => 'GAMES_PUZZLE', 'name' => 'Puzzle'],
                ['id' => 'GAMES_RACING', 'name' => 'Racing'],
                ['id' => 'GAMES_ROLE_PLAYING', 'name' => 'Role Playing'],
                ['id' => 'GAMES_SIMULATION', 'name' => 'Simulation'],
                ['id' => 'GAMES_SPORTS', 'name' => 'Sports'],
                ['id' => 'GAMES_STRATEGY', 'name' => 'Strategy'],
                ['id' => 'GAMES_TRIVIA', 'name' => 'Trivia'],
                ['id' => 'GAMES_WORD', 'name' => 'Word'],
            ]
        ],
        ['id' => 'GRAPHICS_AND_DESIGN', 'name' => 'Graphics & Design'],
        ['id' => 'HEALTH_AND_FITNESS', 'name' => 'Health & Fitness'],
        ['id' => 'LIFESTYLE', 'name' => 'Lifestyle'],
        ['id' => 'MAGAZINES_AND_NEWSPAPERS', 'name' => 'Magazines & Newspapers'],
        ['id' => 'MEDICAL', 'name' => 'Medical'],
        ['id' => 'MUSIC', 'name' => 'Music'],
        ['id' => 'NAVIGATION', 'name' => 'Navigation'],
        ['id' => 'NEWS', 'name' => 'News'],
        ['id' => 'PHOTO_AND_VIDEO', 'name' => 'Photo & Video'],
        ['id' => 'PRODUCTIVITY', 'name' => 'Productivity'],
        ['id' => 'REFERENCE', 'name' => 'Reference'],
        ['id' => 'SHOPPING', 'name' => 'Shopping'],
        ['id' => 'SOCIAL_NETWORKING', 'name' => 'Social Networking'],
        ['id' => 'SPORTS', 'name' => 'Sports'],
        ['id' => 'TRAVEL', 'name' => 'Travel'],
        ['id' => 'UTILITIES', 'name' => 'Utilities'],
        ['id' => 'WEATHER', 'name' => 'Weather'],
    ];
}
?>