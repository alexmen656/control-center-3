<?php
header('Content-Type: text/html; charset=utf-8');
include_once 'jwt_helper.php';
include_once 'config.php';

$origin_url = $_SERVER['HTTP_ORIGIN'] ?? $_SERVER['HTTP_REFERER'];
$allowed_origins = ['alexsblog.de', 'localhost:8100', 'polan.sk', 'http://localhost:8100/login', 'http://localhost:8100', 'localhost'];
$request_host = parse_url($origin_url, PHP_URL_HOST);
$host_domain = implode('.', array_slice(explode('.', $request_host), -2));
//echo $host_domain;
//if (! in_array($host_domain, $allowed_origins, false)) {
//  header('HTTP/1.0 403 Forbidden');
//  die('You are not allowed to access this.');     
//}
ini_set('display_errors', true);
session_start();

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: *');
header('Access-Control-Allow-Methods: *');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

include "use_template_function.php";
include "db_connection.php";
include "functions.php";

/**
 * Instagram OAuth Callback Handler
 * Processes the OAuth response from Instagram and completes the authentication
 */

// Get parameters from URL
$code = isset($_GET['code']) ? $_GET['code'] : '';
$state = isset($_GET['state']) ? $_GET['state'] : '';
$error = isset($_GET['error']) ? $_GET['error'] : '';
$error_description = isset($_GET['error_description']) ? $_GET['error_description'] : '';

// Find project by matching the state parameter in all project tables
$project = 'default';
if (!empty($state)) {
    // Query all video_uploads_config tables to find the matching state
    global $con;
    $tables_result = $con->query("SHOW TABLES LIKE 'video_uploads_config_%'");
    
    while ($table_row = $tables_result->fetch_array()) {
        $tableName = $table_row[0];
        $projectName = str_replace('video_uploads_config_', '', $tableName);
        $projectName = str_replace('_', '-', $projectName);
        
        $stmt = $con->prepare("SELECT value FROM `$tableName` WHERE platform = 'instagram' AND name = 'oauth_state' AND value = ?");
        $stmt->bind_param("s", $state);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $project = $projectName;
            $stmt->close();
            break;
        }
        $stmt->close();
    }
}

function redirectToFrontend($status, $platform, $message = '', $error = '') {
    $baseUrl = 'http://localhost:5178';
    $redirectUrl = $baseUrl . '/video-uploads?callback=true&platform=' . urlencode($platform) . '&status=' . urlencode($status);
    
    if (!empty($message)) {
        $redirectUrl .= '&message=' . urlencode($message);
    }
    
    if (!empty($error)) {
        $redirectUrl .= '&error=' . urlencode($error);
    }
    
    header('Location: ' . $redirectUrl);
    exit();
}

function savePlatformConfig($platform, $project, $name, $value) {
    global $con;
    $tableName = 'video_uploads_config_' . str_replace('-', '_', $project);
    
    $sql = "INSERT INTO `$tableName` (platform, name, value) VALUES (?, ?, ?) 
            ON DUPLICATE KEY UPDATE value = ?";
    
    $stmt = $con->prepare($sql);
    $stmt->bind_param("ssss", $platform, $name, $value, $value);
    $result = $stmt->execute();
    $stmt->close();
    
    return $result;
}

function getPlatformConfig($platform, $project) {
    global $con;
    $tableName = 'video_uploads_config_' . str_replace('-', '_', $project);
    
    $sql = "SELECT name, value FROM `$tableName` WHERE platform = ?";
    $stmt = $con->prepare($sql);
    $stmt->bind_param("s", $platform);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $config = [];
    while ($row = $result->fetch_assoc()) {
        $config[$row['name']] = $row['value'];
    }
    $stmt->close();
    
    return $config;
}

// Handle OAuth error
if (!empty($error)) {
    savePlatformConfig('instagram', $project, 'oauth_status', 'error');
    $errorMsg = $error . (!empty($error_description) ? ': ' . $error_description : '');
    savePlatformConfig('instagram', $project, 'oauth_error', $errorMsg);
    redirectToFrontend('error', 'instagram', '', 'OAuth authorization failed: ' . $errorMsg);
}

// Validate required parameters
if (empty($code) || empty($state)) {
    savePlatformConfig('instagram', $project, 'oauth_status', 'error');
    savePlatformConfig('instagram', $project, 'oauth_error', 'Missing required parameters');
    redirectToFrontend('error', 'instagram', '', 'Missing authorization code or state parameter');
}

// Verify state parameter
$storedConfig = getPlatformConfig('instagram', $project);
$storedState = isset($storedConfig['oauth_state']) ? $storedConfig['oauth_state'] : '';

if (empty($storedState) || $state !== $storedState) {
    savePlatformConfig('instagram', $project, 'oauth_status', 'error');
    savePlatformConfig('instagram', $project, 'oauth_error', 'Invalid state parameter');
    redirectToFrontend('error', 'instagram', '', 'Invalid state parameter - possible CSRF attack');
}

// Get stored client credentials
$appId = isset($storedConfig['app_id']) ? $storedConfig['app_id'] : '';
$appSecret = isset($storedConfig['app_secret']) ? $storedConfig['app_secret'] : '';

if (empty($appId) || empty($appSecret)) {
    savePlatformConfig('instagram', $project, 'oauth_status', 'error');
    savePlatformConfig('instagram', $project, 'oauth_error', 'Missing app credentials');
    redirectToFrontend('error', 'instagram', '', 'Missing app credentials');
}

// Exchange authorization code for access token
$tokenUrl = 'https://api.instagram.com/oauth/access_token';
$redirectUri = "https://alex.polan.sk/control-center/instagram_callback.php";

$postData = [
    'client_id' => $appId,
    'client_secret' => $appSecret,
    'grant_type' => 'authorization_code',
    'redirect_uri' => $redirectUri,
    'code' => $code
];

$options = [
    'http' => [
        'header' => "Content-Type: application/x-www-form-urlencoded\r\n" .
                   "Accept: application/json\r\n",
        'method' => 'POST',
        'content' => http_build_query($postData)
    ]
];

$context = stream_context_create($options);
$response = file_get_contents($tokenUrl, false, $context);

if ($response === false) {
    savePlatformConfig('instagram', $project, 'oauth_status', 'error');
    savePlatformConfig('instagram', $project, 'oauth_error', 'Failed to connect to Instagram API');
    redirectToFrontend('error', 'instagram', '', 'Failed to exchange authorization code for access token');
}

$tokenData = json_decode($response, true);

if (!isset($tokenData['access_token'])) {
    savePlatformConfig('instagram', $project, 'oauth_status', 'error');
    savePlatformConfig('instagram', $project, 'oauth_error', 'No access token in response');
    redirectToFrontend('error', 'instagram', '', 'Invalid token response from Instagram');
}

// Get long-lived access token
$longLivedTokenUrl = 'https://graph.instagram.com/access_token';
$longLivedData = [
    'grant_type' => 'ig_exchange_token',
    'client_secret' => $appSecret,
    'access_token' => $tokenData['access_token']
];

$longLivedResponse = file_get_contents($longLivedTokenUrl . '?' . http_build_query($longLivedData));

$finalAccessToken = $tokenData['access_token'];
$expiresIn = 3600; // Default 1 hour

if ($longLivedResponse !== false) {
    $longLivedTokenData = json_decode($longLivedResponse, true);
    if (isset($longLivedTokenData['access_token'])) {
        $finalAccessToken = $longLivedTokenData['access_token'];
        $expiresIn = $longLivedTokenData['expires_in'] ?? 5184000; // 60 days
    }
}

// Get user information
$userInfoUrl = 'https://graph.instagram.com/me?fields=id,username,account_type&access_token=' . $finalAccessToken;
$userResponse = file_get_contents($userInfoUrl);

$username = 'Unknown';
$accountType = 'Business';

if ($userResponse !== false) {
    $userData = json_decode($userResponse, true);
    if (isset($userData['username'])) {
        $username = $userData['username'];
        $accountType = $userData['account_type'] ?? 'Business';
    }
}

// Save tokens and user information
savePlatformConfig('instagram', $project, 'access_token', $finalAccessToken);

$expiresAt = time() + $expiresIn;
savePlatformConfig('instagram', $project, 'expires_at', $expiresAt);
savePlatformConfig('instagram', $project, 'username', $username);
savePlatformConfig('instagram', $project, 'account_type', $accountType);
savePlatformConfig('instagram', $project, 'oauth_status', 'completed');
savePlatformConfig('instagram', $project, 'updated_at', time());

// Clear temporary OAuth data
savePlatformConfig('instagram', $project, 'oauth_state', '');
savePlatformConfig('instagram', $project, 'oauth_error', '');

// Redirect to frontend with success
redirectToFrontend('success', 'instagram', 'Instagram account connected successfully!');
?>
