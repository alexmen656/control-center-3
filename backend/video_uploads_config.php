<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

include_once 'config.php';
include_once 'head.php';

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

/**
 * Video Uploads Configuration API
 * Handles API integrations with YouTube, Instagram, TikTok and other platforms
 */
class VideoUploadsConfigAPI {
    
    /**
     * HTTP request helper function to replace cURL
     */
    private function makeHttpRequest($url, $options = []) {
        $method = $options['method'] ?? 'GET';
        $headers = $options['headers'] ?? [];
        $data = $options['data'] ?? null;
        
        $context_options = [
            'http' => [
                'method' => $method,
                'ignore_errors' => true
            ]
        ];
        
        if (!empty($headers)) {
            $context_options['http']['header'] = implode("\r\n", $headers);
        }
        
        if ($data && $method === 'POST') {
            $context_options['http']['content'] = is_array($data) ? http_build_query($data) : $data;
            if (!isset($context_options['http']['header'])) {
                $context_options['http']['header'] = '';
            }
            if (strpos($context_options['http']['header'], 'Content-Type') === false) {
                $context_options['http']['header'] .= "\r\nContent-Type: application/x-www-form-urlencoded";
            }
        }
        
        $context = stream_context_create($context_options);
        $response = file_get_contents($url, false, $context);
        
        $http_code = 200;
        if (isset($http_response_header)) {
            foreach ($http_response_header as $header) {
                if (preg_match('/^HTTP\/\d\.\d\s+(\d+)/', $header, $matches)) {
                    $http_code = intval($matches[1]);
                    break;
                }
            }
        }
        
        return [
            'response' => $response,
            'http_code' => $http_code,
            'success' => $response !== false && $http_code >= 200 && $http_code < 300
        ];
    }
    
    private function getTableName($project) {
        return 'video_uploads_config_' . str_replace('-', '_', $project);
    }
    
    private function createTableIfNotExists($project) {
        $tableName = $this->getTableName($project);
        
        $sql = "CREATE TABLE IF NOT EXISTS `$tableName` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `platform` varchar(50) NOT NULL,
            `name` varchar(255) NOT NULL,
            `value` text,
            `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            UNIQUE KEY `platform_name` (`platform`, `name`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
        
        global $con;
        if ($con->query($sql) !== TRUE) {
            return false;
        }
        return true;
    }
    
    public function handleRequest() {
        $action = isset($_REQUEST['action']) ? $_REQUEST['action'] : '';
        $project = isset($_REQUEST['project']) ? $_REQUEST['project'] : '';
        
        if (empty($project)) {
            $this->sendResponse(['error' => 'Project parameter is required'], 400);
            return;
        }
        
        // Create table if not exists
        if (!$this->createTableIfNotExists($project)) {
            $this->sendResponse(['error' => 'Failed to create config table'], 500);
            return;
        }
        
        switch ($action) {
            case 'get_connections':
                $this->getConnections($project);
                break;
            case 'get_settings':
                $this->getSettings($project);
                break;
            case 'init_oauth':
                $this->initOAuth($project);
                break;
            case 'check_oauth':
                $this->checkOAuthStatus($project);
                break;
            case 'disconnect':
                $this->disconnectPlatform($project);
                break;
            case 'refresh_token':
                $this->refreshToken($project);
                break;
            case 'test_connections':
                $this->testConnections($project);
                break;
            case 'save_settings':
                $this->saveSettings($project);
                break;
            default:
                $this->sendResponse(['error' => 'Invalid action'], 400);
                break;
        }
    }
    
    private function getConnections($project) {
        $tableName = $this->getTableName($project);
        global $con;
        
        // Default connection structure
        $conections = [
            'youtube' => [
                'connected' => false,
                'username' => '',
                'channel' => '',
                'lastUpdated' => null,
                'expiresAt' => null
            ],
            'instagram' => [
                'connected' => false,
                'username' => '',
                'accountType' => '',
                'lastUpdated' => null,
                'expiresAt' => null
            ],
            'tiktok' => [
                'connected' => false,
                'username' => '',
                'lastUpdated' => null,
                'expiresAt' => null
            ]
        ];
        
        // Query for YouTube connection
        $youtube = $this->getPlatformConfig('youtube', $project);
        if (!empty($youtube['access_token'])) {
            $conections['youtube']['connected'] = true;
            $conections['youtube']['username'] = $youtube['username'] ?? 'Unknown';
            $conections['youtube']['channel'] = $youtube['channel'] ?? 'Unknown';
            $conections['youtube']['lastUpdated'] = $youtube['updated_at'] ?? null;
            $conections['youtube']['expiresAt'] = $youtube['expires_at'] ?? null;
        }
        
        // Query for Instagram connection
        $instagram = $this->getPlatformConfig('instagram', $project);
        if (!empty($instagram['access_token'])) {
            $conections['instagram']['connected'] = true;
            $conections['instagram']['username'] = $instagram['username'] ?? 'Unknown';
            $conections['instagram']['accountType'] = $instagram['account_type'] ?? 'Business';
            $conections['instagram']['lastUpdated'] = $instagram['updated_at'] ?? null;
            $conections['instagram']['expiresAt'] = $instagram['expires_at'] ?? null;
        }
        
        // Query for TikTok connection
        $tiktok = $this->getPlatformConfig('tiktok', $project);
        if (!empty($tiktok['access_token'])) {
            $conections['tiktok']['connected'] = true;
            $conections['tiktok']['username'] = $tiktok['username'] ?? 'Unknown';
            $conections['tiktok']['lastUpdated'] = $tiktok['updated_at'] ?? null;
            $conections['tiktok']['expiresAt'] = $tiktok['expires_at'] ?? null;
        }
        
        $this->sendResponse(['connections' => $conections]);
    }
    
    private function getPlatformConfig($platform, $project) {
        $tableName = $this->getTableName($project);
        global $con;
        
        $sql = "SELECT name, value FROM `$tableName` WHERE platform = ?";
        $stmt = $con->prepare($sql);
        $stmt->bind_param("s", $platform);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $config = [];
        while ($row = $result->fetch_assoc()) {
            $config[$row['name']] = $row['value'];
        }
        
        return $config;
    }
    
    private function getSettings($project) {
        $tableName = $this->getTableName($project);
        global $con;
        
        // Default settings
        $settings = [
            'apiTimeout' => 60,
            'maxUploadSize' => 500,
            'enableRateLimiting' => true,
            'requestsPerMinute' => 30,
            'useProxy' => false,
            'proxyHost' => '',
            'proxyPort' => '',
            'proxyUsername' => '',
            'proxyPassword' => ''
        ];
        
        // Get settings from database
        $sql = "SELECT name, value FROM `$tableName` WHERE platform = 'settings'";
        $result = $con->query($sql);
        
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $name = $row['name'];
                $value = $row['value'];
                
                // Convert boolean values
                if ($value === 'true') $value = true;
                if ($value === 'false') $value = false;
                
                // Convert numeric values
                if (is_numeric($value)) {
                    if (strpos($value, '.') !== false) {
                        $value = floatval($value);
                    } else {
                        $value = intval($value);
                    }
                }
                
                $settings[$name] = $value;
            }
        }
        
        $this->sendResponse(['settings' => $settings]);
    }
    
    private function initOAuth($project) {
        $platform = isset($_REQUEST['platform']) ? $_REQUEST['platform'] : '';
        
        if (empty($platform)) {
            $this->sendResponse(['error' => 'Platform parameter is required'], 400);
            return;
        }
        
        // Store OAuth state
        $state = bin2hex(random_bytes(16));
        $this->savePlatformConfig($platform, $project, 'oauth_state', $state);
        $this->savePlatformConfig($platform, $project, 'oauth_status', 'pending');
        
        $redirectUri = "https://alex.polan.sk/control-center/{$platform}_callback.php";
        
        // Platform specific OAuth initialization
        switch ($platform) {
            case 'youtube':
                $clientId = isset($_REQUEST['clientId']) ? $_REQUEST['clientId'] : '';
                $clientSecret = isset($_REQUEST['clientSecret']) ? $_REQUEST['clientSecret'] : '';
                
                if (empty($clientId) || empty($clientSecret)) {
                    $this->sendResponse(['error' => 'Client ID and Client Secret are required'], 400);
                    return;
                }
                
                // Save client credentials
                $this->savePlatformConfig($platform, $project, 'client_id', $clientId);
                $this->savePlatformConfig($platform, $project, 'client_secret', $clientSecret);
                
                // Generate YouTube OAuth URL
                $scope = 'https://www.googleapis.com/auth/youtube.upload https://www.googleapis.com/auth/youtube';
                $authUrl = 'https://accounts.google.com/o/oauth2/auth';
                $authUrl .= '?client_id=' . urlencode($clientId);
                $authUrl .= '&redirect_uri=' . urlencode($redirectUri);
                $authUrl .= '&scope=' . urlencode($scope);
                $authUrl .= '&access_type=offline';
                $authUrl .= '&response_type=code';
                $authUrl .= '&state=' . urlencode($state);
                $authUrl .= '&prompt=consent';
                $this->sendResponse(['auth_url' => $authUrl]);
                break;
                
            case 'instagram':
                $appId = isset($_REQUEST['appId']) ? $_REQUEST['appId'] : '';
                $appSecret = isset($_REQUEST['appSecret']) ? $_REQUEST['appSecret'] : '';
                
                if (empty($appId) || empty($appSecret)) {
                    $this->sendResponse(['error' => 'App ID and App Secret are required'], 400);
                    return;
                }
                
                // Save app credentials
                $this->savePlatformConfig($platform, $project, 'app_id', $appId);
                $this->savePlatformConfig($platform, $project, 'app_secret', $appSecret);
                
                // Generate Instagram OAuth URL
                $scope = 'instagram_basic,instagram_content_publish,pages_read_engagement';
                $authUrl = 'https://www.facebook.com/v14.0/dialog/oauth';
                $authUrl .= '?client_id=' . urlencode($appId);
                $authUrl .= '&redirect_uri=' . urlencode($redirectUri);
                $authUrl .= '&scope=' . urlencode($scope);
                $authUrl .= '&state=' . urlencode($state);
                
                $this->sendResponse(['auth_url' => $authUrl]);
                break;
                
            case 'tiktok':
                $clientKey = isset($_REQUEST['clientKey']) ? $_REQUEST['clientKey'] : '';
                $clientSecret = isset($_REQUEST['clientSecret']) ? $_REQUEST['clientSecret'] : '';
                
                if (empty($clientKey) || empty($clientSecret)) {
                    $this->sendResponse(['error' => 'Client Key and Client Secret are required'], 400);
                    return;
                }
                
                // Save client credentials
                $this->savePlatformConfig($platform, $project, 'client_key', $clientKey);
                $this->savePlatformConfig($platform, $project, 'client_secret', $clientSecret);
                
                // Generate TikTok OAuth URL
                $scope = 'user.info.basic,video.list,video.upload,video.publish';
                $authUrl = 'https://www.tiktok.com/v2/auth/authorize/';
                $authUrl .= '?client_key=' . urlencode($clientKey);
                $authUrl .= '&scope=' . urlencode($scope);
                $authUrl .= '&redirect_uri=' . urlencode($redirectUri);
                $authUrl .= '&state=' . urlencode($state);
                $authUrl .= '&response_type=code';
                
                $this->sendResponse(['auth_url' => $authUrl]);
                break;
                
            default:
                $this->sendResponse(['error' => 'Unsupported platform'], 400);
                break;
        }
    }
    
    private function checkOAuthStatus($project) {
        $platform = isset($_REQUEST['platform']) ? $_REQUEST['platform'] : '';
        
        if (empty($platform)) {
            $this->sendResponse(['error' => 'Platform parameter is required'], 400);
            return;
        }
        
        $config = $this->getPlatformConfig($platform, $project);
        $status = isset($config['oauth_status']) ? $config['oauth_status'] : 'pending';
        $error = isset($config['oauth_error']) ? $config['oauth_error'] : null;
        
        $this->sendResponse([
            'status' => $status,
            'error' => $error
        ]);
    }
    
    private function disconnectPlatform($project) {
        $platform = isset($_REQUEST['platform']) ? $_REQUEST['platform'] : '';
        
        if (empty($platform)) {
            $this->sendResponse(['error' => 'Platform parameter is required'], 400);
            return;
        }
        
        $tableName = $this->getTableName($project);
        global $con;
        
        // Delete access token and related data
        $sql = "DELETE FROM `$tableName` WHERE platform = ? AND name IN ('access_token', 'refresh_token', 'expires_at', 'username', 'channel', 'account_type', 'oauth_status')";
        $stmt = $con->prepare($sql);
        $stmt->bind_param("s", $platform);
        
        if ($stmt->execute()) {
            $this->sendResponse(['success' => true, 'message' => 'Platform disconnected successfully']);
        } else {
            $this->sendResponse(['error' => 'Failed to disconnect platform'], 500);
        }
    }
    
    private function refreshToken($project) {
        $platform = isset($_REQUEST['platform']) ? $_REQUEST['platform'] : '';
        
        if (empty($platform)) {
            $this->sendResponse(['error' => 'Platform parameter is required'], 400);
            return;
        }
        
        $config = $this->getPlatformConfig($platform, $project);
        
        // Check if refresh token exists
        if (empty($config['refresh_token'])) {
            $this->sendResponse(['error' => 'No refresh token available'], 400);
            return;
        }
        
        switch ($platform) {
            case 'youtube':
                $this->refreshYouTubeToken($config, $project);
                break;
                
            case 'instagram':
                $this->refreshInstagramToken($config, $project);
                break;
                
            case 'tiktok':
                $this->refreshTikTokToken($config, $project);
                break;
                
            default:
                $this->sendResponse(['error' => 'Unsupported platform'], 400);
                break;
        }
    }
    
private function refreshYouTubeToken($config, $project) {
    $clientId = $config['client_id'] ?? '';
    $clientSecret = $config['client_secret'] ?? '';
    $refreshToken = $config['refresh_token'] ?? '';
    
    if (empty($clientId) || empty($clientSecret) || empty($refreshToken)) {
        $this->sendResponse(['error' => 'Missing required credentials'], 400);
        return;
    }
    
    $url = 'https://oauth2.googleapis.com/token';
    $data = [
        'client_id' => $clientId,
        'client_secret' => $clientSecret,
        'refresh_token' => $refreshToken,
        'grant_type' => 'refresh_token'
    ];

    $options = [
        'http' => [
            'header'  => "Content-Type: application/x-www-form-urlencoded\r\n",
            'method'  => 'POST',
            'content' => http_build_query($data),
            'ignore_errors' => true // wichtig, damit auch Fehlerantworten gelesen werden
        ]
    ];

    $context  = stream_context_create($options);
    $response = file_get_contents($url, false, $context);

    // HTTP-Status auslesen
    $httpCode = 0;
    if (isset($http_response_header[0])) {
        preg_match('{HTTP\/\S*\s(\d{3})}', $http_response_header[0], $match);
        $httpCode = (int)$match[1];
    }

    if ($httpCode === 200 && $response !== false) {
        $tokenData = json_decode($response, true);

        if (isset($tokenData['access_token'])) {
            // Update access token and expiry
            $this->savePlatformConfig('youtube', $project, 'access_token', $tokenData['access_token']);

            $expiresAt = time() + ($tokenData['expires_in'] ?? 3600);
            $this->savePlatformConfig('youtube', $project, 'expires_at', $expiresAt);
            $this->savePlatformConfig('youtube', $project, 'updated_at', time());

            $this->sendResponse(['success' => true, 'message' => 'Token refreshed successfully']);
        } else {
            $this->sendResponse(['error' => 'Invalid response from YouTube API'], 500);
        }
    } else {
        $this->sendResponse(['error' => 'Failed to refresh token: ' . $response], 500);
    }
}

    
    private function refreshInstagramToken($config, $project) {
        $accessToken = $config['access_token'] ?? '';
        
        if (empty($accessToken)) {
            $this->sendResponse(['error' => 'No access token available'], 400);
            return;
        }
        
        $url = 'https://graph.instagram.com/refresh_access_token';
        $data = [
            'grant_type' => 'ig_refresh_token',
            'access_token' => $accessToken
        ];
        
        $ch = curl_init($url . '?' . http_build_query($data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode == 200) {
            $tokenData = json_decode($response, true);
            
            if (isset($tokenData['access_token'])) {
                // Update access token and expiry
                $this->savePlatformConfig('instagram', $project, 'access_token', $tokenData['access_token']);
                
                $expiresAt = time() + ($tokenData['expires_in'] ?? 5184000); // Default to 60 days
                $this->savePlatformConfig('instagram', $project, 'expires_at', $expiresAt);
                $this->savePlatformConfig('instagram', $project, 'updated_at', time());
                
                $this->sendResponse(['success' => true, 'message' => 'Token refreshed successfully']);
            } else {
                $this->sendResponse(['error' => 'Invalid response from Instagram API'], 500);
            }
        } else {
            $this->sendResponse(['error' => 'Failed to refresh token: ' . $response], 500);
        }
    }
    
    private function refreshTikTokToken($config, $project) {
        $clientKey = $config['client_key'] ?? '';
        $clientSecret = $config['client_secret'] ?? '';
        $refreshToken = $config['refresh_token'] ?? '';
        
        if (empty($clientKey) || empty($clientSecret) || empty($refreshToken)) {
            $this->sendResponse(['error' => 'Missing required credentials'], 400);
            return;
        }
        
        $url = 'https://open-api.tiktok.com/oauth/refresh_token/';
        $data = [
            'client_key' => $clientKey,
            'client_secret' => $clientSecret,
            'grant_type' => 'refresh_token',
            'refresh_token' => $refreshToken
        ];
        
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/x-www-form-urlencoded']);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode == 200) {
            $tokenData = json_decode($response, true);
            
            if (isset($tokenData['data']['access_token'])) {
                // Update access token and expiry
                $this->savePlatformConfig('tiktok', $project, 'access_token', $tokenData['data']['access_token']);
                $this->savePlatformConfig('tiktok', $project, 'refresh_token', $tokenData['data']['refresh_token']);
                
                $expiresAt = time() + ($tokenData['data']['expires_in'] ?? 86400);
                $this->savePlatformConfig('tiktok', $project, 'expires_at', $expiresAt);
                $this->savePlatformConfig('tiktok', $project, 'updated_at', time());
                
                $this->sendResponse(['success' => true, 'message' => 'Token refreshed successfully']);
            } else {
                $this->sendResponse(['error' => 'Invalid response from TikTok API'], 500);
            }
        } else {
            $this->sendResponse(['error' => 'Failed to refresh token: ' . $response], 500);
        }
    }
    
    private function testConnections($project) {
        $platforms = ['youtube', 'instagram', 'tiktok'];
        $results = [];
        
        foreach ($platforms as $platform) {
            $config = $this->getPlatformConfig($platform, $project);
            
            if (empty($config['access_token'])) {
                $results[$platform] = ['success' => false, 'error' => 'Not connected'];
                continue;
            }
            
            switch ($platform) {
                case 'youtube':
                    $results[$platform] = $this->testYouTubeConnection($config);
                    break;
                    
                case 'instagram':
                    $results[$platform] = $this->testInstagramConnection($config);
                    break;
                    
                case 'tiktok':
                    $results[$platform] = $this->testTikTokConnection($config);
                    break;
                    
                default:
                    $results[$platform] = ['success' => false, 'error' => 'Unsupported platform'];
                    break;
            }
        }
        
        $this->sendResponse(['results' => $results]);
    }
    
    private function testYouTubeConnection($config) {
        $accessToken = $config['access_token'] ?? '';
        
        if (empty($accessToken)) {
            return ['success' => false, 'error' => 'No access token available'];
        }
        
        // Test YouTube API connection
        $url = 'https://www.googleapis.com/youtube/v3/channels?part=snippet&mine=true';
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . $accessToken]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode == 200) {
            return ['success' => true, 'message' => 'YouTube connection is working'];
        } else {
            return ['success' => false, 'error' => 'Failed to connect to YouTube API: ' . $httpCode];
        }
    }
    
    private function testInstagramConnection($config) {
        $accessToken = $config['access_token'] ?? '';
        
        if (empty($accessToken)) {
            return ['success' => false, 'error' => 'No access token available'];
        }
        
        // Test Instagram Graph API connection
        $url = 'https://graph.instagram.com/me?fields=id,username&access_token=' . $accessToken;
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode == 200) {
            return ['success' => true, 'message' => 'Instagram connection is working'];
        } else {
            return ['success' => false, 'error' => 'Failed to connect to Instagram API: ' . $httpCode];
        }
    }
    
    private function testTikTokConnection($config) {
        $accessToken = $config['access_token'] ?? '';
        
        if (empty($accessToken)) {
            return ['success' => false, 'error' => 'No access token available'];
        }
        
        // Test TikTok API connection
        $url = 'https://open-api.tiktok.com/v2/user/info/';
        $fields = 'open_id,union_id,avatar_url,display_name';
        
        $ch = curl_init($url . '?fields=' . $fields);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . $accessToken]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode == 200) {
            return ['success' => true, 'message' => 'TikTok connection is working'];
        } else {
            return ['success' => false, 'error' => 'Failed to connect to TikTok API: ' . $httpCode];
        }
    }
    
    private function saveSettings($project) {
        $settingsJson = isset($_REQUEST['settings']) ? $_REQUEST['settings'] : '';
        
        if (empty($settingsJson)) {
            $this->sendResponse(['error' => 'Settings are required'], 400);
            return;
        }
        
        $settings = json_decode($settingsJson, true);
        if (!$settings) {
            $this->sendResponse(['error' => 'Invalid settings format'], 400);
            return;
        }
        
        foreach ($settings as $name => $value) {
            // Convert boolean values to strings for database storage
            if (is_bool($value)) {
                $value = $value ? 'true' : 'false';
            }
            
            // Save each setting
            $this->savePlatformConfig('settings', $project, $name, $value);
        }
        
        $this->sendResponse(['success' => true, 'message' => 'Settings saved successfully']);
    }
    
    private function savePlatformConfig($platform, $project, $name, $value) {
        $tableName = $this->getTableName($project);
        global $con;
        
        $sql = "INSERT INTO `$tableName` (platform, name, value) VALUES (?, ?, ?) 
                ON DUPLICATE KEY UPDATE value = ?";
        
        $stmt = $con->prepare($sql);
        $stmt->bind_param("ssss", $platform, $name, $value, $value);
        $stmt->execute();
        
        return $stmt->affected_rows > 0;
    }
    
    private function sendResponse($data, $statusCode = 200) {
        http_response_code($statusCode);
        echo json_encode($data);
        exit();
    }
}

$api = new VideoUploadsConfigAPI();
$api->handleRequest();
?>