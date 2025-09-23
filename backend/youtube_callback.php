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
 * YouTube OAuth Callback Handler
 * Processes the OAuth response from YouTube and completes the authentication
 */

// Get parameters from URL
$code = isset($_GET['code']) ? $_GET['code'] : '';
$state = isset($_GET['state']) ? $_GET['state'] : '';
$error = isset($_GET['error']) ? $_GET['error'] : '';

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
        
        $stmt = $con->prepare("SELECT value FROM `$tableName` WHERE platform = 'youtube' AND name = 'oauth_state' AND value = ?");
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

// Log received parameters for debugging
error_log("YouTube Callback - Project: $project, Code: " . substr($code, 0, 20) . "..., State: $state");

function redirectToFrontend($status, $platform, $message = '', $error = '') {
    $baseUrl = 'http://localhost:5179';
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
    savePlatformConfig('youtube', $project, 'oauth_status', 'error');
    savePlatformConfig('youtube', $project, 'oauth_error', $error);
    redirectToFrontend('error', 'youtube', '', 'OAuth authorization was denied or failed: ' . $error);
}

// Validate required parameters
if (empty($code) || empty($state)) {
    savePlatformConfig('youtube', $project, 'oauth_status', 'error');
    savePlatformConfig('youtube', $project, 'oauth_error', 'Missing required parameters');
    redirectToFrontend('error', 'youtube', '', 'Missing authorization code or state parameter');
}

// Verify state parameter
$storedConfig = getPlatformConfig('youtube', $project);
$storedState = isset($storedConfig['oauth_state']) ? $storedConfig['oauth_state'] : '';

if (empty($storedState) || $state !== $storedState) {
    savePlatformConfig('youtube', $project, 'oauth_status', 'error');
    savePlatformConfig('youtube', $project, 'oauth_error', 'Invalid state parameter');
    redirectToFrontend('error', 'youtube', '', 'Invalid state parameter - possible CSRF attack');
}

// Get stored client credentials
$clientId = isset($storedConfig['client_id']) ? $storedConfig['client_id'] : '';
$clientSecret = isset($storedConfig['client_secret']) ? $storedConfig['client_secret'] : '';

if (empty($clientId) || empty($clientSecret)) {
    savePlatformConfig('youtube', $project, 'oauth_status', 'error');
    savePlatformConfig('youtube', $project, 'oauth_error', 'Missing client credentials');
    redirectToFrontend('error', 'youtube', '', 'Missing client credentials');
}

// Exchange authorization code for access token
$tokenUrl = 'https://oauth2.googleapis.com/token';
$redirectUri = "https://alex.polan.sk/control-center/youtube_callback.php";

$postData = [
    'client_id' => $clientId,
    'client_secret' => $clientSecret,
    'code' => $code,
    'grant_type' => 'authorization_code',
    'redirect_uri' => $redirectUri
];

// Use file_get_contents instead of cURL
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

// Check if request was successful
if ($response === false) {
    $error = error_get_last();
    $errorMsg = "Failed to connect to Google API";
    if ($error) {
        $errorMsg .= " - Error: " . $error['message'];
    }
    
    savePlatformConfig('youtube', $project, 'oauth_status', 'error');
    savePlatformConfig('youtube', $project, 'oauth_error', $errorMsg);
    redirectToFrontend('error', 'youtube', '', 'Failed to exchange authorization code for access token: ' . $errorMsg);
}

// Parse HTTP response headers to get status code
$httpCode = 200; // Default assumption for file_get_contents success
if (isset($http_response_header)) {
    foreach ($http_response_header as $header) {
        if (preg_match('/^HTTP\/\d\.\d\s+(\d+)/', $header, $matches)) {
            $httpCode = intval($matches[1]);
            break;
        }
    }
}

// Log the response for debugging
error_log("YouTube Token Exchange - HTTP Code: $httpCode, Response: " . substr($response, 0, 500));

if ($httpCode !== 200) {
    $errorMsg = "HTTP $httpCode - Response: $response";
    
    savePlatformConfig('youtube', $project, 'oauth_status', 'error');
    savePlatformConfig('youtube', $project, 'oauth_error', $errorMsg);
    redirectToFrontend('error', 'youtube', '', 'Failed to exchange authorization code for access token: ' . $errorMsg);
}

$tokenData = json_decode($response, true);

if (!isset($tokenData['access_token'])) {
    savePlatformConfig('youtube', $project, 'oauth_status', 'error');
    savePlatformConfig('youtube', $project, 'oauth_error', 'No access token in response');
    redirectToFrontend('error', 'youtube', '', 'Invalid token response from YouTube');
}

// Get user information
$userInfoUrl = 'https://www.googleapis.com/youtube/v3/channels?part=snippet&mine=true';

$options = [
    'http' => [
        'header' => "Authorization: Bearer " . $tokenData['access_token'] . "\r\n" .
                   "Accept: application/json\r\n",
        'method' => 'GET'
    ]
];

$context = stream_context_create($options);
$userResponse = file_get_contents($userInfoUrl, false, $context);

$userHttpCode = 200;
if (isset($http_response_header)) {
    foreach ($http_response_header as $header) {
        if (preg_match('/^HTTP\/\d\.\d\s+(\d+)/', $header, $matches)) {
            $userHttpCode = intval($matches[1]);
            break;
        }
    }
}

$username = 'Unknown';
$channelName = 'Unknown';

if ($userHttpCode === 200) {
    $userData = json_decode($userResponse, true);
    if (isset($userData['items'][0]['snippet'])) {
        $channelName = $userData['items'][0]['snippet']['title'];
        $username = $userData['items'][0]['snippet']['customUrl'] ?? $channelName;
    }
}

// Save tokens and user information
savePlatformConfig('youtube', $project, 'access_token', $tokenData['access_token']);

if (isset($tokenData['refresh_token'])) {
    savePlatformConfig('youtube', $project, 'refresh_token', $tokenData['refresh_token']);
}

$expiresAt = time() + ($tokenData['expires_in'] ?? 3600);
savePlatformConfig('youtube', $project, 'expires_at', $expiresAt);
savePlatformConfig('youtube', $project, 'username', $username);
savePlatformConfig('youtube', $project, 'channel', $channelName);
savePlatformConfig('youtube', $project, 'oauth_status', 'completed');
savePlatformConfig('youtube', $project, 'updated_at', time());

// Clear temporary OAuth data
savePlatformConfig('youtube', $project, 'oauth_state', '');
savePlatformConfig('youtube', $project, 'oauth_error', '');

// Redirect to frontend with success
redirectToFrontend('success', 'youtube', 'YouTube account connected successfully!');
?>
