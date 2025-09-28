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
 * Video Analytics API
 * Handles fetching views, likes, comments and other metrics from various platforms
 */
class VideoAnalyticsAPI {
    
    /**
     * HTTP request helper function
     */
    private function makeHttpRequest($url, $options = []) {
        $method = $options['method'] ?? 'GET';
        $headers = $options['headers'] ?? [];
        $data = $options['data'] ?? null;
        
        $context_options = [
            'http' => [
                'method' => $method,
                'timeout' => 30,
                'ignore_errors' => true
            ]
        ];
        
        if (!empty($headers)) {
            $context_options['http']['header'] = implode("\r\n", $headers);
        }
        
        if ($data && ($method === 'POST' || $method === 'PUT')) {
            $context_options['http']['content'] = is_array($data) ? http_build_query($data) : $data;
        }
        
        $context = stream_context_create($context_options);
        
        // Capture response headers globally
        global $http_response_header;
        $response = file_get_contents($url, false, $context);
        
        $http_code = 200;
        if (isset($http_response_header)) {
            foreach ($http_response_header as $header) {
                if (strpos($header, 'HTTP/') === 0) {
                    $http_code = intval(substr($header, 9, 3));
                    break;
                }
            }
        }
        
        return [
            'response' => $response,
            'http_code' => $http_code,
            'success' => $response !== false && $http_code >= 200 && $http_code < 400,
            'headers' => $http_response_header ?? []
        ];
    }
    
    private function getTableName($project) {
        return 'video_uploads_' . str_replace('-', '_', $project);
    }
    
    public function handleRequest() {
        $action = isset($_REQUEST['action']) ? $_REQUEST['action'] : '';
        $project = isset($_REQUEST['project']) ? $_REQUEST['project'] : '';
        
        if (empty($project)) {
            $this->sendResponse(['error' => 'Project parameter is required'], 400);
            return;
        }
        
        switch ($action) {
            case 'sync_video_analytics':
                $this->syncVideoAnalytics($project);
                break;
            case 'sync_all_analytics':
                $this->syncAllAnalytics($project);
                break;
            case 'get_platform_analytics':
                $this->getPlatformAnalytics($project);
                break;
            case 'get_video_analytics':
                $this->getVideoAnalytics($project);
                break;
            default:
                $this->sendResponse(['error' => 'Invalid action'], 400);
                break;
        }
    }
    
    private function syncVideoAnalytics($project) {
        $videoId = isset($_REQUEST['video_id']) ? (int)$_REQUEST['video_id'] : 0;
        
        if ($videoId <= 0) {
            $this->sendResponse(['error' => 'Invalid video ID'], 400);
            return;
        }
        
        // Get video data
        $video = $this->getVideoById($videoId, $project);
        if (!$video) {
            $this->sendResponse(['error' => 'Video not found'], 404);
            return;
        }
        
        $analytics = [];
        $platforms = json_decode($video['platforms'], true) ?: [$video['platform']];
        
        foreach ($platforms as $platform) {
            $platformAnalytics = $this->fetchPlatformAnalytics($platform, $video, $project);
            if ($platformAnalytics) {
                $analytics[$platform] = $platformAnalytics;
                // Update database with new analytics
                $this->updateVideoAnalytics($videoId, $project, $platformAnalytics);
            }
        }
        
        $this->sendResponse(['success' => true, 'analytics' => $analytics]);
    }
    
    private function syncAllAnalytics($project) {
        $tableName = $this->getTableName($project);
        global $con;
        
        $sql = "SELECT * FROM `$tableName` WHERE status = 'published' AND platform_video_id IS NOT NULL";
        $result = $con->query($sql);
        
        $updatedCount = 0;
        $errors = [];
        
        if ($result->num_rows > 0) {
            while ($video = $result->fetch_assoc()) {
                try {
                    $platforms = json_decode($video['platforms'], true) ?: [$video['platform']];
                    
                    foreach ($platforms as $platform) {
                        $analytics = $this->fetchPlatformAnalytics($platform, $video, $project);
                        if ($analytics) {
                            $this->updateVideoAnalytics($video['id'], $project, $analytics);
                            $updatedCount++;
                        }
                    }
                } catch (Exception $e) {
                    $errors[] = "Video {$video['id']}: " . $e->getMessage();
                }
            }
        }
        
        $this->sendResponse([
            'success' => true, 
            'updated_count' => $updatedCount,
            'errors' => $errors
        ]);
    }
    
    private function fetchPlatformAnalytics($platform, $video, $project) {
        $config = $this->getPlatformConfig($platform, $project);
        
        if (empty($config['access_token'])) {
            return null;
        }
        
        switch ($platform) {
            case 'youtube':
                return $this->fetchYouTubeAnalytics($video, $config);
            case 'instagram':
                return $this->fetchInstagramAnalytics($video, $config);
            case 'tiktok':
                return $this->fetchTikTokAnalytics($video, $config);
            case 'facebook':
                return $this->fetchFacebookAnalytics($video, $config);
            case 'linkedin':
                return $this->fetchLinkedInAnalytics($video, $config);
            default:
                return null;
        }
    }
    
    private function fetchYouTubeAnalytics($video, $config) {
        $accessToken = $config['access_token'];
        $videoId = $video['platform_video_id'];
        
        if (empty($videoId)) {
            return null;
        }
        
        // YouTube Analytics API
        $url = "https://www.googleapis.com/youtube/v3/videos?part=statistics&id={$videoId}";
        
        $result = $this->makeHttpRequest($url, [
            'headers' => [
                "Authorization: Bearer {$accessToken}",
                "Content-Type: application/json"
            ]
        ]);
        
        if ($result['success']) {
            $data = json_decode($result['response'], true);
            
            if (isset($data['items'][0]['statistics'])) {
                $stats = $data['items'][0]['statistics'];
                return [
                    'views' => intval($stats['viewCount'] ?? 0),
                    'likes' => intval($stats['likeCount'] ?? 0),
                    'comments' => intval($stats['commentCount'] ?? 0),
                    'shares' => 0, // YouTube doesn't provide share count in API
                    'platform' => 'youtube'
                ];
            }
        }
        
        return null;
    }
    
    private function fetchInstagramAnalytics($video, $config) {
        $accessToken = $config['access_token'];
        $mediaId = $video['platform_video_id'];
        
        if (empty($mediaId)) {
            return null;
        }
        
        // Instagram Graph API
        $url = "https://graph.facebook.com/v18.0/{$mediaId}?fields=like_count,comments_count,views&access_token={$accessToken}";
        
        $result = $this->makeHttpRequest($url);
        
        if ($result['success']) {
            $data = json_decode($result['response'], true);
            
            return [
                'views' => intval($data['views'] ?? 0),
                'likes' => intval($data['like_count'] ?? 0),
                'comments' => intval($data['comments_count'] ?? 0),
                'shares' => 0, // Instagram doesn't provide share count
                'platform' => 'instagram'
            ];
        }
        
        return null;
    }
    
    private function fetchTikTokAnalytics($video, $config) {
        $accessToken = $config['access_token'];
        $videoId = $video['platform_video_id'];
        
        if (empty($videoId)) {
            return null;
        }
        
        // TikTok Research API
        $url = "https://open.tiktokapis.com/v2/research/video/query/";
        
        $requestData = [
            'query' => [
                'and' => [
                    [
                        'operation' => 'EQ',
                        'field_name' => 'video_id',
                        'field_values' => [$videoId]
                    ]
                ]
            ],
            'fields' => ['video_id', 'like_count', 'comment_count', 'share_count', 'view_count']
        ];
        
        $result = $this->makeHttpRequest($url, [
            'method' => 'POST',
            'headers' => [
                "Authorization: Bearer {$accessToken}",
                "Content-Type: application/json"
            ],
            'data' => json_encode($requestData)
        ]);
        
        if ($result['success']) {
            $data = json_decode($result['response'], true);
            
            if (isset($data['data']['videos'][0])) {
                $stats = $data['data']['videos'][0];
                return [
                    'views' => intval($stats['view_count'] ?? 0),
                    'likes' => intval($stats['like_count'] ?? 0),
                    'comments' => intval($stats['comment_count'] ?? 0),
                    'shares' => intval($stats['share_count'] ?? 0),
                    'platform' => 'tiktok'
                ];
            }
        }
        
        return null;
    }
    
    private function fetchFacebookAnalytics($video, $config) {
        $accessToken = $config['access_token'];
        $videoId = $video['platform_video_id'];
        
        if (empty($videoId)) {
            return null;
        }
        
        // Facebook Graph API
        $url = "https://graph.facebook.com/v18.0/{$videoId}?fields=insights.metric(post_video_views,post_reactions_by_type_total,post_comments,post_shares)&access_token={$accessToken}";
        
        $result = $this->makeHttpRequest($url);
        
        if ($result['success']) {
            $data = json_decode($result['response'], true);
            
            if (isset($data['insights']['data'])) {
                $insights = $data['insights']['data'];
                $analytics = [
                    'views' => 0,
                    'likes' => 0,
                    'comments' => 0,
                    'shares' => 0,
                    'platform' => 'facebook'
                ];
                
                foreach ($insights as $insight) {
                    switch ($insight['name']) {
                        case 'post_video_views':
                            $analytics['views'] = intval($insight['values'][0]['value'] ?? 0);
                            break;
                        case 'post_reactions_by_type_total':
                            $reactions = $insight['values'][0]['value'] ?? [];
                            $analytics['likes'] = array_sum($reactions);
                            break;
                        case 'post_comments':
                            $analytics['comments'] = intval($insight['values'][0]['value'] ?? 0);
                            break;
                        case 'post_shares':
                            $analytics['shares'] = intval($insight['values'][0]['value'] ?? 0);
                            break;
                    }
                }
                
                return $analytics;
            }
        }
        
        return null;
    }
    
    private function fetchLinkedInAnalytics($video, $config) {
        $accessToken = $config['access_token'];
        $urn = $video['platform_video_id'];
        
        if (empty($urn)) {
            return null;
        }
        
        // LinkedIn API
        $url = "https://api.linkedin.com/v2/socialActions/{$urn}";
        
        $result = $this->makeHttpRequest($url, [
            'headers' => [
                "Authorization: Bearer {$accessToken}",
                "Content-Type: application/json"
            ]
        ]);
        
        if ($result['success']) {
            $data = json_decode($result['response'], true);
            
            return [
                'views' => intval($data['impressionCount'] ?? 0),
                'likes' => intval($data['likeCount'] ?? 0),
                'comments' => intval($data['commentCount'] ?? 0),
                'shares' => intval($data['shareCount'] ?? 0),
                'platform' => 'linkedin'
            ];
        }
        
        return null;
    }
    
    private function updateVideoAnalytics($videoId, $project, $analytics) {
        $tableName = $this->getTableName($project);
        global $con;
        
        // Calculate like rate
        $likeRate = 0;
        if ($analytics['views'] > 0) {
            $likeRate = round(($analytics['likes'] / $analytics['views']) * 100, 2);
        }
        
        $stmt = $con->prepare("UPDATE `$tableName` SET views = ?, likes = ?, comments = ?, shares = ?, like_rate = ?, updated_at = NOW() WHERE id = ?");
        $stmt->bind_param("iiiidi", $analytics['views'], $analytics['likes'], $analytics['comments'], $analytics['shares'], $likeRate, $videoId);
        $stmt->execute();
        $stmt->close();
    }
    
    private function getPlatformAnalytics($project) {
        $platform = isset($_REQUEST['platform']) ? $_REQUEST['platform'] : '';
        
        if (empty($platform)) {
            $this->sendResponse(['error' => 'Platform parameter is required'], 400);
            return;
        }
        
        $tableName = $this->getTableName($project);
        global $con;
        
        // Get analytics summary for platform
        $sql = "SELECT 
                    COUNT(*) as total_videos,
                    SUM(views) as total_views,
                    SUM(likes) as total_likes,
                    SUM(comments) as total_comments,
                    SUM(shares) as total_shares,
                    AVG(like_rate) as avg_like_rate
                FROM `$tableName` 
                WHERE platform = ? AND status = 'published'";
        
        $stmt = $con->prepare($sql);
        $stmt->bind_param("s", $platform);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $analytics = $result->fetch_assoc();
            $this->sendResponse(['analytics' => $analytics]);
        } else {
            $this->sendResponse(['analytics' => null]);
        }
        
        $stmt->close();
    }
    
    private function getVideoAnalytics($project) {
        $videoId = isset($_REQUEST['video_id']) ? (int)$_REQUEST['video_id'] : 0;
        
        if ($videoId <= 0) {
            $this->sendResponse(['error' => 'Invalid video ID'], 400);
            return;
        }
        
        $video = $this->getVideoById($videoId, $project);
        if (!$video) {
            $this->sendResponse(['error' => 'Video not found'], 404);
            return;
        }
        
        $this->sendResponse(['analytics' => [
            'views' => intval($video['views'] ?? 0),
            'likes' => intval($video['likes'] ?? 0),
            'comments' => intval($video['comments'] ?? 0),
            'shares' => intval($video['shares'] ?? 0),
            'like_rate' => floatval($video['like_rate'] ?? 0),
            'last_updated' => $video['updated_at'] ?? null
        ]]);
    }
    
    private function getVideoById($videoId, $project) {
        $tableName = $this->getTableName($project);
        global $con;
        
        $stmt = $con->prepare("SELECT * FROM `$tableName` WHERE id = ?");
        $stmt->bind_param("i", $videoId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            return $result->fetch_assoc();
        }
        
        return null;
    }
    
    private function getPlatformConfig($platform, $project) {
        global $con;
        $configTableName = 'video_uploads_config_' . str_replace('-', '_', $project);
        
        $sql = "SELECT name, value FROM `$configTableName` WHERE platform = ?";
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
    
    private function sendResponse($data, $statusCode = 200) {
        http_response_code($statusCode);
        echo json_encode($data);
        exit();
    }
}

$api = new VideoAnalyticsAPI();
$api->handleRequest();
?>