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
            `category` varchar(100),
            `publish_date` date,
            `publish_time` time,
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
            
            // Validate required fields
            if (empty($title)) {
                $this->sendResponse(['error' => 'Title is required'], 400);
                return;
            }
            
            $stmt = $con->prepare("INSERT INTO `$tableName` (title, description, status, platform, category, 
                                    publish_date, publish_time, video_file, thumbnail_url, tags, goals) 
                                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            
            $stmt->bind_param("sssssssssss", $title, $description, $status, $platform, $category, 
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
            
            // Validate required fields
            if (empty($title)) {
                $this->sendResponse(['error' => 'Title is required'], 400);
                return;
            }
            
            $stmt = $con->prepare("INSERT INTO `$tableName` (title, description, status, platform, category, 
                                    publish_date, publish_time, video_file, thumbnail_url, tags, goals) 
                                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            
            $stmt->bind_param("sssssssssss", $title, $description, $status, $platform, $category, 
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
    
    private function sendResponse($data, $statusCode = 200) {
        http_response_code($statusCode);
        echo json_encode($data);
        exit();
    }
}

$api = new VideoUploadsAPI();
$api->handleRequest();
?>
