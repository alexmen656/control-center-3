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
 * Video Uploads Management API
 * Handles CRUD operations for video uploads and platform integrations
 */
class VideoUploadsAPI {
    
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
                'ignore_errors' => true,
                'timeout' => 300 // 5 minutes for large uploads
            ]
        ];
        
        if (!empty($headers)) {
            $context_options['http']['header'] = implode("\r\n", $headers);
        }
        
        if ($data && ($method === 'POST' || $method === 'PUT')) {
            $context_options['http']['content'] = is_array($data) ? http_build_query($data) : $data;
            
            // Set default content type if not specified
            if (!isset($context_options['http']['header'])) {
                $context_options['http']['header'] = '';
            }
            
            $hasContentType = false;
            foreach ($headers as $header) {
                if (stripos($header, 'Content-Type:') === 0) {
                    $hasContentType = true;
                    break;
                }
            }
            
            if (!$hasContentType && is_array($data)) {
                $context_options['http']['header'] .= "\r\nContent-Type: application/x-www-form-urlencoded";
            }
        }
        
        $context = stream_context_create($context_options);
        
        // Capture response headers globally
        global $http_response_header;
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
            'success' => $response !== false && $http_code >= 200 && $http_code < 400,
            'headers' => $http_response_header ?? []
        ];
    }
    
    private function getTableName($project) {
        return 'video_uploads_' . str_replace('-', '_', $project);
    }
    
    private function createTableIfNotExists($project) {
        $tableName = $this->getTableName($project);
        
        $sql = "CREATE TABLE IF NOT EXISTS `$tableName` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `title` varchar(255) NOT NULL,
            `description` text,
            `status` enum('draft','scheduled','published','processing','failed') DEFAULT 'draft',
            `platform` enum('youtube','instagram','tiktok','facebook','linkedin') DEFAULT 'youtube',
            `platforms` text COMMENT 'JSON array of target platforms',
            `category` varchar(100),
            `publish_date` date,
            `publish_time` time,
            `scheduled_time` datetime,
            `video_file` varchar(255),
            `thumbnail_url` varchar(255),
            `tags` text,
            `goals` text,
            `views` int(11) DEFAULT 0,
            `likes` int(11) DEFAULT 0,
            `comments` int(11) DEFAULT 0,
            `shares` int(11) DEFAULT 0,
            `like_rate` decimal(5,2) DEFAULT 0.00,
            `platform_video_id` varchar(100),
            `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
        
        if (query($sql) !== TRUE) {
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
            $this->sendResponse(['error' => 'Failed to create table'], 500);
            return;
        }
        
        switch ($action) {
            case 'create':
                $this->createVideo($project);
                break;
            case 'list':
                $this->listVideos($project);
                break;
            case 'get':
                $this->getVideo($project);
                break;
            case 'update':
                $this->updateVideo($project);
                break;
            case 'delete':
                $this->deleteVideo($project);
                break;
            case 'update_status':
                $this->updateVideoStatus($project);
                break;
            case 'upload_to_platform':
                $this->uploadToPlatform($project);
                break;
            case 'schedule_upload':
                $this->scheduleUpload($project);
                break;
            case 'get_upload_progress':
                $this->getUploadProgress($project);
                break;
            default:
                $this->sendResponse(['error' => 'Invalid action'], 400);
                break;
        }
    }
    
    private function createVideo($project) {
        $tableName = $this->getTableName($project);
        global $con;
        
        // Check if it's a multipart form data for file upload
        if ($_SERVER['CONTENT_TYPE'] && strpos($_SERVER['CONTENT_TYPE'], 'multipart/form-data') !== false) {
            // Handle file uploads
            $videoFilePath = '';
            $thumbnailFilePath = '';
            
            if (isset($_FILES['video_file']) && $_FILES['video_file']['error'] === 0) {
                $targetDir = "../uploads/videos/" . $project . "/";
                
                // Create directory if not exists
                if (!is_dir($targetDir)) {
                    mkdir($targetDir, 0755, true);
                }
                
                $fileName = uniqid() . '_' . basename($_FILES["video_file"]["name"]);
                $targetFile = $targetDir . $fileName;
                
                if (move_uploaded_file($_FILES["video_file"]["tmp_name"], $targetFile)) {
                    $videoFilePath = "uploads/videos/" . $project . "/" . $fileName;
                } else {
                    $this->sendResponse(['error' => 'Failed to upload video file'], 500);
                    return;
                }
            }
            
            if (isset($_FILES['thumbnail_file']) && $_FILES['thumbnail_file']['error'] === 0) {
                $targetDir = "../uploads/thumbnails/" . $project . "/";
                
                // Create directory if not exists
                if (!is_dir($targetDir)) {
                    mkdir($targetDir, 0755, true);
                }
                
                $fileName = uniqid() . '_' . basename($_FILES["thumbnail_file"]["name"]);
                $targetFile = $targetDir . $fileName;
                
                if (move_uploaded_file($_FILES["thumbnail_file"]["tmp_name"], $targetFile)) {
                    $thumbnailFilePath = "uploads/thumbnails/" . $project . "/" . $fileName;
                } else {
                    $this->sendResponse(['error' => 'Failed to upload thumbnail file'], 500);
                    return;
                }
            }
            
            // Get other form fields
            $title = isset($_POST['title']) ? $_POST['title'] : '';
            $description = isset($_POST['description']) ? $_POST['description'] : '';
            $status = isset($_POST['status']) ? $_POST['status'] : 'draft';
            $platform = isset($_POST['platform']) ? $_POST['platform'] : 'youtube';
            $category = isset($_POST['category']) ? $_POST['category'] : '';
            $publishDate = isset($_POST['publish_date']) ? $_POST['publish_date'] : null;
            $publishTime = isset($_POST['publish_time']) ? $_POST['publish_time'] : null;
            $tags = isset($_POST['tags']) ? $_POST['tags'] : '';
            $goals = isset($_POST['goals']) ? $_POST['goals'] : '';
            
            // Handle platforms array (new format) or single platform (backward compatibility)
            $platforms = [];
            if (isset($_POST['platforms']) && is_array($_POST['platforms'])) {
                $platforms = $_POST['platforms'];
                $platform = $platforms[0]; // Use first platform as primary for backward compatibility
            } else if (!empty($platform)) {
                $platforms = [$platform];
            }
            $platformsJson = json_encode($platforms);
            
            // Validate required fields
            if (empty($title)) {
                $this->sendResponse(['error' => 'Title is required'], 400);
                return;
            }
            
            if (empty($platforms)) {
                $this->sendResponse(['error' => 'At least one platform is required'], 400);
                return;
            }
            
            $stmt = $con->prepare("INSERT INTO `$tableName` (title, description, status, platform, platforms, category, 
                                    publish_date, publish_time, video_file, thumbnail_url, tags, goals) 
                                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            
            $stmt->bind_param("ssssssssssss", $title, $description, $status, $platform, $platformsJson, $category, 
                            $publishDate, $publishTime, $videoFilePath, $thumbnailFilePath, $tags, $goals);
            
            if ($stmt->execute()) {
                $videoId = $con->insert_id;
                $this->sendResponse(['success' => true, 'id' => $videoId, 'message' => 'Video created successfully']);
            } else {
                $this->sendResponse(['error' => 'Failed to create video: ' . $stmt->error], 500);
            }
            $stmt->close();
        } else {
            // Regular form submission (no files)
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (!$data) {
                $data = $_POST;
            }
            
            // Get form fields
            $title = isset($data['title']) ? $data['title'] : '';
            $description = isset($data['description']) ? $data['description'] : '';
            $status = isset($data['status']) ? $data['status'] : 'draft';
            $platform = isset($data['platform']) ? $data['platform'] : 'youtube';
            $category = isset($data['category']) ? $data['category'] : '';
            $publishDate = isset($data['publish_date']) ? $data['publish_date'] : null;
            $publishTime = isset($data['publish_time']) ? $data['publish_time'] : null;
            $videoFile = isset($data['video_file']) ? $data['video_file'] : '';
            $thumbnailUrl = isset($data['thumbnail_url']) ? $data['thumbnail_url'] : '';
            $tags = isset($data['tags']) ? $data['tags'] : '';
            $goals = isset($data['goals']) ? $data['goals'] : '';
            
            // Handle platforms array (new format) or single platform (backward compatibility)
            $platforms = [];
            if (isset($data['platforms']) && is_array($data['platforms'])) {
                $platforms = $data['platforms'];
                $platform = $platforms[0]; // Use first platform as primary for backward compatibility
            } else if (!empty($platform)) {
                $platforms = [$platform];
            }
            $platformsJson = json_encode($platforms);
            
            // Validate required fields
            if (empty($title)) {
                $this->sendResponse(['error' => 'Title is required'], 400);
                return;
            }
            
            if (empty($platforms)) {
                $this->sendResponse(['error' => 'At least one platform is required'], 400);
                return;
            }
            
            $stmt = $con->prepare("INSERT INTO `$tableName` (title, description, status, platform, platforms, category, 
                                    publish_date, publish_time, video_file, thumbnail_url, tags, goals) 
                                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            
            $stmt->bind_param("ssssssssssss", $title, $description, $status, $platform, $platformsJson, $category, 
                            $publishDate, $publishTime, $videoFile, $thumbnailUrl, $tags, $goals);
            
            if ($stmt->execute()) {
                $videoId = $con->insert_id;
                $this->sendResponse(['success' => true, 'id' => $videoId, 'message' => 'Video created successfully']);
            } else {
                $this->sendResponse(['error' => 'Failed to create video: ' . $stmt->error], 500);
            }
            $stmt->close();
        }
    }
    
    private function listVideos($project) {
        $tableName = $this->getTableName($project);
        global $con;
        
        $sql = "SELECT * FROM `$tableName` ORDER BY created_at DESC";
        $result = $con->query($sql);
        
        $videos = [];
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                // Calculate like rate if we have views
                if ($row['views'] > 0 && isset($row['likes'])) {
                    $row['like_rate'] = round(($row['likes'] / $row['views']) * 100, 2);
                } else {
                    $row['like_rate'] = 0;
                }
                
                // Handle image paths
                if ($row['thumbnail_url'] && strpos($row['thumbnail_url'], 'http') !== 0) {
                    $row['thumbnail_url'] = $row['thumbnail_url']; // Keep the relative path as is
                }
                
                // Parse platforms JSON for frontend compatibility
                if (isset($row['platforms']) && !empty($row['platforms'])) {
                    $row['platforms_array'] = json_decode($row['platforms'], true);
                } else if (isset($row['platform'])) {
                    $row['platforms_array'] = [$row['platform']];
                } else {
                    $row['platforms_array'] = [];
                }
                
                $videos[] = $row;
            }
        }
        
        $this->sendResponse(['videos' => $videos]);
    }
    
    private function getVideo($project) {
        $tableName = $this->getTableName($project);
        global $con;
        
        $id = isset($_REQUEST['id']) ? (int)$_REQUEST['id'] : 0;
        
        if ($id <= 0) {
            $this->sendResponse(['error' => 'Invalid video ID'], 400);
            return;
        }
        
        $stmt = $con->prepare("SELECT * FROM `$tableName` WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $video = $result->fetch_assoc();
            
            // Calculate like rate if we have views
            if ($video['views'] > 0 && isset($video['likes'])) {
                $video['like_rate'] = round(($video['likes'] / $video['views']) * 100, 2);
            } else {
                $video['like_rate'] = 0;
            }
            
            // Handle image paths
            if ($video['thumbnail_url'] && strpos($video['thumbnail_url'], 'http') !== 0) {
                $video['thumbnail_url'] = $video['thumbnail_url']; // Keep the relative path as is
            }
            
            // Parse platforms JSON for frontend compatibility
            if (isset($video['platforms']) && !empty($video['platforms'])) {
                $video['platforms_array'] = json_decode($video['platforms'], true);
            } else if (isset($video['platform'])) {
                $video['platforms_array'] = [$video['platform']];
            } else {
                $video['platforms_array'] = [];
            }
            
            $this->sendResponse(['video' => $video]);
        } else {
            $this->sendResponse(['error' => 'Video not found'], 404);
        }
        
        $stmt->close();
    }
    
    private function updateVideo($project) {
        $tableName = $this->getTableName($project);
        global $con;
        
        $id = isset($_REQUEST['id']) ? (int)$_REQUEST['id'] : 0;
        
        if ($id <= 0) {
            $this->sendResponse(['error' => 'Invalid video ID'], 400);
            return;
        }
        
        // Check if it's a multipart form data for file upload
        if ($_SERVER['CONTENT_TYPE'] && strpos($_SERVER['CONTENT_TYPE'], 'multipart/form-data') !== false) {
            // First, get current video data
            $stmt = $con->prepare("SELECT video_file, thumbnail_url FROM `$tableName` WHERE id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows === 0) {
                $this->sendResponse(['error' => 'Video not found'], 404);
                return;
            }
            
            $currentData = $result->fetch_assoc();
            $videoFilePath = $currentData['video_file'];
            $thumbnailFilePath = $currentData['thumbnail_url'];
            
            // Handle file uploads
            if (isset($_FILES['video_file']) && $_FILES['video_file']['error'] === 0) {
                $targetDir = "../uploads/videos/" . $project . "/";
                
                // Create directory if not exists
                if (!is_dir($targetDir)) {
                    mkdir($targetDir, 0755, true);
                }
                
                $fileName = uniqid() . '_' . basename($_FILES["video_file"]["name"]);
                $targetFile = $targetDir . $fileName;
                
                if (move_uploaded_file($_FILES["video_file"]["tmp_name"], $targetFile)) {
                    // Delete old file if exists
                    if (!empty($currentData['video_file']) && file_exists("../" . $currentData['video_file'])) {
                        unlink("../" . $currentData['video_file']);
                    }
                    $videoFilePath = "uploads/videos/" . $project . "/" . $fileName;
                } else {
                    $this->sendResponse(['error' => 'Failed to upload video file'], 500);
                    return;
                }
            }
            
            if (isset($_FILES['thumbnail_file']) && $_FILES['thumbnail_file']['error'] === 0) {
                $targetDir = "../uploads/thumbnails/" . $project . "/";
                
                // Create directory if not exists
                if (!is_dir($targetDir)) {
                    mkdir($targetDir, 0755, true);
                }
                
                $fileName = uniqid() . '_' . basename($_FILES["thumbnail_file"]["name"]);
                $targetFile = $targetDir . $fileName;
                
                if (move_uploaded_file($_FILES["thumbnail_file"]["tmp_name"], $targetFile)) {
                    // Delete old file if exists
                    if (!empty($currentData['thumbnail_url']) && file_exists("../" . $currentData['thumbnail_url'])) {
                        unlink("../" . $currentData['thumbnail_url']);
                    }
                    $thumbnailFilePath = "uploads/thumbnails/" . $project . "/" . $fileName;
                } else {
                    $this->sendResponse(['error' => 'Failed to upload thumbnail file'], 500);
                    return;
                }
            }
            
            // Get other form fields
            $title = isset($_POST['title']) ? $_POST['title'] : '';
            $description = isset($_POST['description']) ? $_POST['description'] : '';
            $status = isset($_POST['status']) ? $_POST['status'] : 'draft';
            $platform = isset($_POST['platform']) ? $_POST['platform'] : 'youtube';
            $category = isset($_POST['category']) ? $_POST['category'] : '';
            $publishDate = isset($_POST['publish_date']) ? $_POST['publish_date'] : null;
            $publishTime = isset($_POST['publish_time']) ? $_POST['publish_time'] : null;
            $tags = isset($_POST['tags']) ? $_POST['tags'] : '';
            $goals = isset($_POST['goals']) ? $_POST['goals'] : '';
            
            // Validate required fields
            if (empty($title)) {
                $this->sendResponse(['error' => 'Title is required'], 400);
                return;
            }
            
            $stmt = $con->prepare("UPDATE `$tableName` SET title = ?, description = ?, status = ?, platform = ?, 
                                   category = ?, publish_date = ?, publish_time = ?, video_file = ?, thumbnail_url = ?, 
                                   tags = ?, goals = ? WHERE id = ?");
            
            $stmt->bind_param("sssssssssssi", $title, $description, $status, $platform, $category, 
                            $publishDate, $publishTime, $videoFilePath, $thumbnailFilePath, $tags, $goals, $id);
            
        } else {
            // Regular form submission (no files)
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (!$data) {
                $data = $_POST;
            }
            
            // Get form fields
            $title = isset($data['title']) ? $data['title'] : '';
            $description = isset($data['description']) ? $data['description'] : '';
            $status = isset($data['status']) ? $data['status'] : null;
            $platform = isset($data['platform']) ? $data['platform'] : null;
            $category = isset($data['category']) ? $data['category'] : '';
            $publishDate = isset($data['publish_date']) ? $data['publish_date'] : null;
            $publishTime = isset($data['publish_time']) ? $data['publish_time'] : null;
            $videoFile = isset($data['video_file']) ? $data['video_file'] : null;
            $thumbnailUrl = isset($data['thumbnail_url']) ? $data['thumbnail_url'] : null;
            $tags = isset($data['tags']) ? $data['tags'] : '';
            $goals = isset($data['goals']) ? $data['goals'] : '';
            
            // Validate required fields
            if (empty($title)) {
                $this->sendResponse(['error' => 'Title is required'], 400);
                return;
            }
            
            // Prepare update SQL based on provided fields
            $updateFields = [];
            $bindParams = [];
            $bindTypes = "";
            $bindValues = [];
            
            if (!empty($title)) {
                $updateFields[] = "title = ?";
                $bindTypes .= "s";
                $bindValues[] = $title;
            }
            
            if (isset($description)) {
                $updateFields[] = "description = ?";
                $bindTypes .= "s";
                $bindValues[] = $description;
            }
            
            if (isset($status)) {
                $updateFields[] = "status = ?";
                $bindTypes .= "s";
                $bindValues[] = $status;
            }
            
            if (isset($platform)) {
                $updateFields[] = "platform = ?";
                $bindTypes .= "s";
                $bindValues[] = $platform;
            }
            
            if (isset($category)) {
                $updateFields[] = "category = ?";
                $bindTypes .= "s";
                $bindValues[] = $category;
            }
            
            if (isset($publishDate)) {
                $updateFields[] = "publish_date = ?";
                $bindTypes .= "s";
                $bindValues[] = $publishDate;
            }
            
            if (isset($publishTime)) {
                $updateFields[] = "publish_time = ?";
                $bindTypes .= "s";
                $bindValues[] = $publishTime;
            }
            
            if (isset($videoFile)) {
                $updateFields[] = "video_file = ?";
                $bindTypes .= "s";
                $bindValues[] = $videoFile;
            }
            
            if (isset($thumbnailUrl)) {
                $updateFields[] = "thumbnail_url = ?";
                $bindTypes .= "s";
                $bindValues[] = $thumbnailUrl;
            }
            
            if (isset($tags)) {
                $updateFields[] = "tags = ?";
                $bindTypes .= "s";
                $bindValues[] = $tags;
            }
            
            if (isset($goals)) {
                $updateFields[] = "goals = ?";
                $bindTypes .= "s";
                $bindValues[] = $goals;
            }
            
            // Add the ID for the WHERE clause
            $bindTypes .= "i";
            $bindValues[] = $id;
            
            $sql = "UPDATE `$tableName` SET " . implode(", ", $updateFields) . " WHERE id = ?";
            $stmt = $con->prepare($sql);
            
            // Dynamically bind parameters
            $bindParams[] = &$bindTypes;
            foreach ($bindValues as $key => $value) {
                $bindParams[] = &$bindValues[$key];
            }
            
            call_user_func_array([$stmt, 'bind_param'], $bindParams);
        }
        
        if ($stmt->execute()) {
            $this->sendResponse(['success' => true, 'message' => 'Video updated successfully']);
        } else {
            $this->sendResponse(['error' => 'Failed to update video: ' . $stmt->error], 500);
        }
        
        $stmt->close();
    }
    
    private function deleteVideo($project) {
        $tableName = $this->getTableName($project);
        global $con;
        
        $id = isset($_REQUEST['id']) ? (int)$_REQUEST['id'] : 0;
        
        if ($id <= 0) {
            $this->sendResponse(['error' => 'Invalid video ID'], 400);
            return;
        }
        
        // First, get the file paths to delete files
        $stmt = $con->prepare("SELECT video_file, thumbnail_url FROM `$tableName` WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $video = $result->fetch_assoc();
            
            // Delete files if they exist
            if (!empty($video['video_file']) && file_exists("../" . $video['video_file'])) {
                unlink("../" . $video['video_file']);
            }
            
            if (!empty($video['thumbnail_url']) && file_exists("../" . $video['thumbnail_url'])) {
                unlink("../" . $video['thumbnail_url']);
            }
        }
        
        $stmt->close();
        
        // Now delete the record
        $stmt = $con->prepare("DELETE FROM `$tableName` WHERE id = ?");
        $stmt->bind_param("i", $id);
        
        if ($stmt->execute()) {
            $this->sendResponse(['success' => true, 'message' => 'Video deleted successfully']);
        } else {
            $this->sendResponse(['error' => 'Failed to delete video'], 500);
        }
        
        $stmt->close();
    }
    
    private function updateVideoStatus($project) {
        $tableName = $this->getTableName($project);
        global $con;
        
        $id = isset($_REQUEST['id']) ? (int)$_REQUEST['id'] : 0;
        $status = isset($_REQUEST['status']) ? $_REQUEST['status'] : '';
        
        if ($id <= 0) {
            $this->sendResponse(['error' => 'Invalid video ID'], 400);
            return;
        }
        
        if (empty($status)) {
            $this->sendResponse(['error' => 'Status is required'], 400);
            return;
        }
        
        $stmt = $con->prepare("UPDATE `$tableName` SET status = ? WHERE id = ?");
        $stmt->bind_param("si", $status, $id);
        
        if ($stmt->execute()) {
            $this->sendResponse(['success' => true, 'message' => 'Video status updated successfully']);
        } else {
            $this->sendResponse(['error' => 'Failed to update video status'], 500);
        }
        
        $stmt->close();
    }
    
    private function uploadToPlatform($project) {
        $videoId = isset($_REQUEST['video_id']) ? (int)$_REQUEST['video_id'] : 0;
        $platform = isset($_REQUEST['platform']) ? $_REQUEST['platform'] : '';
        
        if ($videoId <= 0) {
            $this->sendResponse(['error' => 'Invalid video ID'], 400);
            return;
        }
        
        if (empty($platform)) {
            $this->sendResponse(['error' => 'Platform is required'], 400);
            return;
        }
        
        // Get video data
        $video = $this->getVideoById($videoId, $project);
        if (!$video) {
            $this->sendResponse(['error' => 'Video not found'], 404);
            return;
        }
        
        // Get platform configuration
        $config = $this->getPlatformConfig($platform, $project);
        if (empty($config['access_token'])) {
            $this->sendResponse(['error' => "Platform $platform is not connected"], 400);
            return;
        }
        
        // Update status to processing
        $this->updateVideoStatusById($videoId, $project, 'processing');
        
        // Upload to the specific platform
        switch ($platform) {
            case 'youtube':
                $result = $this->uploadToYouTube($video, $config, $project);
                break;
            case 'instagram':
                $result = $this->uploadToInstagram($video, $config, $project);
                break;
            case 'tiktok':
                $result = $this->uploadToTikTok($video, $config, $project);
                break;
            default:
                $this->sendResponse(['error' => 'Unsupported platform'], 400);
                return;
        }
        
        // Update video status based on result
        if ($result['success']) {
            $this->updateVideoStatusById($videoId, $project, 'published');
            if (isset($result['platform_video_id'])) {
                $this->updatePlatformVideoId($videoId, $project, $result['platform_video_id']);
            }
            $this->sendResponse(['success' => true, 'message' => "Video uploaded to $platform successfully", 'result' => $result]);
        } else {
            $this->updateVideoStatusById($videoId, $project, 'failed');
            $this->sendResponse(['error' => $result['error']], 500);
        }
    }
    
    private function scheduleUpload($project) {
        $videoId = isset($_REQUEST['video_id']) ? (int)$_REQUEST['video_id'] : 0;
        $platform = isset($_REQUEST['platform']) ? $_REQUEST['platform'] : '';
        $scheduledTime = isset($_REQUEST['scheduled_time']) ? $_REQUEST['scheduled_time'] : '';
        
        if ($videoId <= 0) {
            $this->sendResponse(['error' => 'Invalid video ID'], 400);
            return;
        }
        
        if (empty($platform) || empty($scheduledTime)) {
            $this->sendResponse(['error' => 'Platform and scheduled time are required'], 400);
            return;
        }
        
        // Update video status to scheduled
        $this->updateVideoStatusById($videoId, $project, 'scheduled');
        
        // Here you would typically add the video to a job queue or cron system
        // For now, we'll just update the database with the scheduled time
        $tableName = $this->getTableName($project);
        global $con;
        
        $stmt = $con->prepare("UPDATE `$tableName` SET scheduled_time = ? WHERE id = ?");
        $stmt->bind_param("si", $scheduledTime, $videoId);
        
        if ($stmt->execute()) {
            $this->sendResponse(['success' => true, 'message' => 'Video scheduled for upload']);
        } else {
            $this->sendResponse(['error' => 'Failed to schedule video'], 500);
        }
        
        $stmt->close();
    }
    
    private function getUploadProgress($project) {
        $videoId = isset($_REQUEST['video_id']) ? (int)$_REQUEST['video_id'] : 0;
        
        if ($videoId <= 0) {
            $this->sendResponse(['error' => 'Invalid video ID'], 400);
            return;
        }
        
        // Get video status
        $video = $this->getVideoById($videoId, $project);
        if (!$video) {
            $this->sendResponse(['error' => 'Video not found'], 404);
            return;
        }
        
        // Return upload progress (in a real implementation, this would track actual upload progress)
        $progress = [
            'status' => $video['status'],
            'progress' => $video['status'] === 'processing' ? rand(10, 90) : ($video['status'] === 'published' ? 100 : 0),
            'message' => $this->getStatusMessage($video['status'])
        ];
        
        $this->sendResponse(['progress' => $progress]);
    }
    
    private function uploadToYouTube($video, $config, $project) {
        $accessToken = $config['access_token'];
        
        // Check if video file exists
        $videoPath = "../" . $video['video_file'];
        if (!file_exists($videoPath)) {
            return ['success' => false, 'error' => 'Video file not found'];
        }
        
        // Prepare video metadata
        $metadata = [
            'snippet' => [
                'title' => $video['title'],
                'description' => $video['description'] ?? '',
                'tags' => !empty($video['tags']) ? explode(',', $video['tags']) : [],
                'categoryId' => '22' // People & Blogs category
            ],
            'status' => [
                'privacyStatus' => 'public'
            ]
        ];
        
        // Step 1: Initialize resumable upload
        $uploadUrl = 'https://www.googleapis.com/upload/youtube/v3/videos?uploadType=resumable&part=snippet,status';
        
        $headers = [
            'Authorization: Bearer ' . $accessToken,
            'Content-Type: application/json; charset=UTF-8',
            'X-Upload-Content-Type: video/*',
            'X-Upload-Content-Length: ' . filesize($videoPath)
        ];
        
        $result = $this->makeHttpRequest($uploadUrl, [
            'method' => 'POST',
            'headers' => $headers,
            'data' => json_encode($metadata)
        ]);
        
        if (!$result['success']) {
            return ['success' => false, 'error' => 'Failed to initialize YouTube upload: ' . $result['http_code'] . ' Headers: '. $accessToken];
        }
        
        // Extract upload URL from Location header
        $uploadSessionUrl = null;
        foreach ($result['headers'] as $header) {
            if (strpos($header, 'Location:') === 0) {
                $uploadSessionUrl = trim(substr($header, 9));
                break;
            }
        }
        
        if (!$uploadSessionUrl) {
            return ['success' => false, 'error' => 'Failed to get YouTube upload URL'];
        }
        
        // Step 2: Upload video file in chunks
        $videoData = file_get_contents($videoPath);
        $fileSize = strlen($videoData);
        $chunkSize = 1024 * 1024; // 1MB chunks
        $uploaded = 0;
        
        while ($uploaded < $fileSize) {
            $chunk = substr($videoData, $uploaded, $chunkSize);
            $chunkEnd = min($uploaded + $chunkSize - 1, $fileSize - 1);
            
            $chunkHeaders = [
                'Content-Type: video/*',
                'Content-Range: bytes ' . $uploaded . '-' . $chunkEnd . '/' . $fileSize
            ];
            
            $chunkResult = $this->makeHttpRequest($uploadSessionUrl, [
                'method' => 'PUT',
                'headers' => $chunkHeaders,
                'data' => $chunk
            ]);
            
            if ($chunkResult['http_code'] === 308) {
                // Continue uploading
                $uploaded += strlen($chunk);
            } elseif ($chunkResult['http_code'] === 200 || $chunkResult['http_code'] === 201) {
                // Upload complete
                $responseData = json_decode($chunkResult['response'], true);
                return [
                    'success' => true,
                    'platform_video_id' => $responseData['id'] ?? 'yt_' . uniqid(),
                    'message' => 'Video uploaded to YouTube successfully'
                ];
            } else {
                return ['success' => false, 'error' => 'Failed to upload video chunk to YouTube: ' . $chunkResult['http_code']];
            }
        }
        
        return ['success' => false, 'error' => 'Unexpected end of upload process'];
    }
    
    private function uploadToInstagram($video, $config, $project) {
        $accessToken = $config['access_token'];
        $pageId = $config['page_id'] ?? ''; // Instagram Business Account Page ID
        
        if (empty($pageId)) {
            return ['success' => false, 'error' => 'Instagram Page ID not configured'];
        }
        
        // Check if video file exists
        $videoPath = "../" . $video['video_file'];
        if (!file_exists($videoPath)) {
            return ['success' => false, 'error' => 'Video file not found'];
        }
        
        // For Instagram, we need to upload to a public URL first
        // Then use that URL in the API call
        $videoUrl = 'https://' . $_SERVER['HTTP_HOST'] . '/' . $video['video_file'];
        
        // Step 1: Create media object
        $uploadUrl = "https://graph.facebook.com/v18.0/{$pageId}/media";
        $uploadData = [
            'media_type' => 'REELS',
            'video_url' => $videoUrl,
            'caption' => $video['description'] ?? $video['title'],
            'access_token' => $accessToken
        ];
        
        $result = $this->makeHttpRequest($uploadUrl, [
            'method' => 'POST',
            'data' => $uploadData
        ]);
        
        if (!$result['success']) {
            return ['success' => false, 'error' => 'Failed to create Instagram media: ' . $result['response']];
        }
        
        $responseData = json_decode($result['response'], true);
        if (!isset($responseData['id'])) {
            return ['success' => false, 'error' => 'Invalid response from Instagram API: ' . $result['response']];
        }
        
        $mediaId = $responseData['id'];
        
        // Step 2: Wait for processing (Instagram needs time to process the video)
        $maxAttempts = 30;
        $attempts = 0;
        
        while ($attempts < $maxAttempts) {
            sleep(2); // Wait 2 seconds between checks
            
            $statusUrl = "https://graph.facebook.com/v18.0/{$mediaId}?fields=status_code&access_token={$accessToken}";
            $statusResult = $this->makeHttpRequest($statusUrl);
            
            if ($statusResult['success']) {
                $statusData = json_decode($statusResult['response'], true);
                $statusCode = $statusData['status_code'] ?? '';
                
                if ($statusCode === 'FINISHED') {
                    break; // Ready to publish
                } elseif ($statusCode === 'ERROR') {
                    return ['success' => false, 'error' => 'Instagram video processing failed'];
                }
            }
            
            $attempts++;
        }
        
        // Step 3: Publish the media
        $publishUrl = "https://graph.facebook.com/v18.0/{$pageId}/media_publish";
        $publishData = [
            'creation_id' => $mediaId,
            'access_token' => $accessToken
        ];
        
        $publishResult = $this->makeHttpRequest($publishUrl, [
            'method' => 'POST',
            'data' => $publishData
        ]);
        
        if ($publishResult['success']) {
            $publishResponseData = json_decode($publishResult['response'], true);
            return [
                'success' => true,
                'platform_video_id' => $publishResponseData['id'] ?? $mediaId,
                'message' => 'Video uploaded to Instagram successfully'
            ];
        } else {
            return ['success' => false, 'error' => 'Failed to publish on Instagram: ' . $publishResult['response']];
        }
    }
    
    private function uploadToTikTok($video, $config, $project) {
        $accessToken = $config['access_token'];
        $openId = $config['open_id'] ?? '';
        
        if (empty($openId)) {
            return ['success' => false, 'error' => 'TikTok Open ID not configured'];
        }
        
        // Check if video file exists
        $videoPath = "../" . $video['video_file'];
        if (!file_exists($videoPath)) {
            return ['success' => false, 'error' => 'Video file not found'];
        }
        
        $fileSize = filesize($videoPath);
        $chunkSize = 10 * 1024 * 1024; // 10MB chunks
        
        // Step 1: Initialize video upload
        $initUrl = 'https://open-api.tiktok.com/v2/post/publish/video/init/';
        $initData = [
            'post_info' => [
                'title' => $video['title'],
                'description' => $video['description'] ?? '',
                'privacy_level' => 'PUBLIC_TO_EVERYONE',
                'disable_duet' => false,
                'disable_comment' => false,
                'disable_stitch' => false,
                'video_cover_timestamp_ms' => 1000
            ],
            'source_info' => [
                'source' => 'FILE_UPLOAD',
                'video_size' => $fileSize,
                'chunk_size' => $chunkSize,
                'total_chunk_count' => ceil($fileSize / $chunkSize)
            ]
        ];
        
        $result = $this->makeHttpRequest($initUrl, [
            'method' => 'POST',
            'headers' => [
                'Authorization: Bearer ' . $accessToken,
                'Content-Type: application/json'
            ],
            'data' => json_encode($initData)
        ]);
        
        if (!$result['success']) {
            return ['success' => false, 'error' => 'Failed to initialize TikTok upload: ' . $result['response']];
        }
        
        $responseData = json_decode($result['response'], true);
        if (!isset($responseData['data']['upload_url'])) {
            return ['success' => false, 'error' => 'Invalid response from TikTok API: ' . $result['response']];
        }
        
        $uploadUrl = $responseData['data']['upload_url'];
        $publishId = $responseData['data']['publish_id'];
        
        // Step 2: Upload video file in chunks
        $videoData = file_get_contents($videoPath);
        $totalChunks = ceil($fileSize / $chunkSize);
        
        for ($chunkIndex = 0; $chunkIndex < $totalChunks; $chunkIndex++) {
            $start = $chunkIndex * $chunkSize;
            $chunkData = substr($videoData, $start, $chunkSize);
            $chunkEnd = min($start + $chunkSize - 1, $fileSize - 1);
            
            $chunkHeaders = [
                'Content-Type: video/mp4',
                'Content-Range: bytes ' . $start . '-' . $chunkEnd . '/' . $fileSize
            ];
            
            $chunkResult = $this->makeHttpRequest($uploadUrl, [
                'method' => 'PUT',
                'headers' => $chunkHeaders,
                'data' => $chunkData
            ]);
            
            if (!$chunkResult['success']) {
                return ['success' => false, 'error' => 'Failed to upload chunk to TikTok: ' . $chunkResult['http_code']];
            }
        }
        
        // Step 3: Publish the video
        $publishUrl = 'https://open-api.tiktok.com/v2/post/publish/video/complete/';
        $publishData = [
            'publish_id' => $publishId
        ];
        
        $publishResult = $this->makeHttpRequest($publishUrl, [
            'method' => 'POST',
            'headers' => [
                'Authorization: Bearer ' . $accessToken,
                'Content-Type: application/json'
            ],
            'data' => json_encode($publishData)
        ]);
        
        if ($publishResult['success']) {
            return [
                'success' => true,
                'platform_video_id' => $publishId,
                'message' => 'Video uploaded to TikTok successfully'
            ];
        } else {
            return ['success' => false, 'error' => 'Failed to complete TikTok upload: ' . $publishResult['response']];
        }
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
    
    private function updateVideoStatusById($videoId, $project, $status) {
        $tableName = $this->getTableName($project);
        global $con;
        
        $stmt = $con->prepare("UPDATE `$tableName` SET status = ? WHERE id = ?");
        $stmt->bind_param("si", $status, $videoId);
        $stmt->execute();
        $stmt->close();
    }
    
    private function updatePlatformVideoId($videoId, $project, $platformVideoId) {
        $tableName = $this->getTableName($project);
        global $con;
        
        $stmt = $con->prepare("UPDATE `$tableName` SET platform_video_id = ? WHERE id = ?");
        $stmt->bind_param("si", $platformVideoId, $videoId);
        $stmt->execute();
        $stmt->close();
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
    
    private function getStatusMessage($status) {
        $messages = [
            'draft' => 'Video is in draft status',
            'scheduled' => 'Video is scheduled for upload',
            'processing' => 'Video is being uploaded to platform',
            'published' => 'Video has been successfully published',
            'failed' => 'Video upload failed'
        ];
        
        return $messages[$status] ?? 'Unknown status';
    }
    
    private function sendResponse($data, $statusCode = 200) {
        http_response_code($statusCode);
        echo json_encode($data);
        exit();
    }
}

$api = new VideoUploadsAPI();
$api->handleRequest();
?>
