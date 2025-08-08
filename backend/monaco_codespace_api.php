<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: *');
header('Access-Control-Allow-Methods: *');
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once 'head.php';

function getUserIDFromToken() {
    global $jwt_secret;
    
    $headers = getallheaders();
    $authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? '';
    
    if (empty($authHeader)) {
        return 'dev_user'; // Development fallback
    }
    
    $token = $authHeader;
    $userData = SimpleJWT::verify($token, $jwt_secret);
    if (!$userData || !isset($userData['sub'])) {
        return 'dev_user';
    }
    
    return $userData['sub'];
}

function getCodespacePath($project, $codespace, $userID) {
    return __DIR__ . '/../data/projects/' . $userID . '/' . $project . '/' . $codespace;
}

function getProjectPath($project, $userID) {
    return __DIR__ . '/../data/projects/' . $userID . '/' . $project . '/main'; // Default to main codespace
}

$userID = getUserIDFromToken();
$method = $_SERVER['REQUEST_METHOD'];
$input = json_decode(file_get_contents('php://input'), true);

$project = $input['project'] ?? $_GET['project'] ?? '';
$codespace = $input['codespace'] ?? $_GET['codespace'] ?? 'main';

try {
    if ($method === 'POST' && isset($input['action'])) {
        switch ($input['action']) {
            case 'load_file':
                $filename = $input['filename'] ?? '';
                $path = getCodespacePath($project, $codespace, $userID) . '/' . ltrim($filename, '/');
                
                if (!file_exists($path)) {
                    echo json_encode(['success' => false, 'error' => 'File not found']);
                    exit;
                }
                
                $content = file_get_contents($path);
                echo json_encode(['success' => true, 'content' => $content]);
                break;
                
            case 'save_file':
                $filename = $input['filename'] ?? '';
                $content = $input['content'] ?? '';
                $codespacePath = getCodespacePath($project, $codespace, $userID);
                $path = $codespacePath . '/' . ltrim($filename, '/');
                
                // Ensure directory exists
                $dir = dirname($path);
                if (!is_dir($dir)) {
                    mkdir($dir, 0777, true);
                }
                
                $result = file_put_contents($path, $content);
                if ($result !== false) {
                    echo json_encode(['success' => true, 'message' => 'File saved successfully']);
                } else {
                    echo json_encode(['success' => false, 'error' => 'Failed to save file']);
                }
                break;
                
            case 'list_files':
                $codespacePath = getCodespacePath($project, $codespace, $userID);
                
                if (!is_dir($codespacePath)) {
                    echo json_encode(['success' => false, 'error' => 'Codespace not found']);
                    exit;
                }
                
                function scanDirectory($dir, $basePath) {
                    $files = [];
                    $items = scandir($dir);
                    
                    foreach ($items as $item) {
                        if ($item === '.' || $item === '..' || strpos($item, '.monaco') === 0) {
                            continue;
                        }
                        
                        $fullPath = $dir . '/' . $item;
                        $relativePath = substr($fullPath, strlen($basePath) + 1);
                        
                        if (is_dir($fullPath)) {
                            $files[] = [
                                'name' => $item,
                                'path' => $relativePath,
                                'type' => 'directory',
                                'children' => scanDirectory($fullPath, $basePath)
                            ];
                        } else {
                            $files[] = [
                                'name' => $item,
                                'path' => $relativePath,
                                'type' => 'file',
                                'size' => filesize($fullPath),
                                'modified' => filemtime($fullPath)
                            ];
                        }
                    }
                    
                    return $files;
                }
                
                $files = scanDirectory($codespacePath, $codespacePath);
                echo json_encode(['success' => true, 'files' => $files]);
                break;
                
            case 'create_file':
                $filename = $input['filename'] ?? '';
                $content = $input['content'] ?? '';
                $codespacePath = getCodespacePath($project, $codespace, $userID);
                $path = $codespacePath . '/' . ltrim($filename, '/');
                
                if (file_exists($path)) {
                    echo json_encode(['success' => false, 'error' => 'File already exists']);
                    exit;
                }
                
                // Ensure directory exists
                $dir = dirname($path);
                if (!is_dir($dir)) {
                    mkdir($dir, 0777, true);
                }
                
                $result = file_put_contents($path, $content);
                if ($result !== false) {
                    echo json_encode(['success' => true, 'message' => 'File created successfully']);
                } else {
                    echo json_encode(['success' => false, 'error' => 'Failed to create file']);
                }
                break;
                
            case 'delete_file':
                $filename = $input['filename'] ?? '';
                $path = getCodespacePath($project, $codespace, $userID) . '/' . ltrim($filename, '/');
                
                if (!file_exists($path)) {
                    echo json_encode(['success' => false, 'error' => 'File not found']);
                    exit;
                }
                
                if (is_dir($path)) {
                    function deleteDirectory($dir) {
                        $files = array_diff(scandir($dir), ['.', '..']);
                        foreach ($files as $file) {
                            $filePath = $dir . '/' . $file;
                            is_dir($filePath) ? deleteDirectory($filePath) : unlink($filePath);
                        }
                        return rmdir($dir);
                    }
                    
                    $result = deleteDirectory($path);
                } else {
                    $result = unlink($path);
                }
                
                if ($result) {
                    echo json_encode(['success' => true, 'message' => 'File deleted successfully']);
                } else {
                    echo json_encode(['success' => false, 'error' => 'Failed to delete file']);
                }
                break;
                
            case 'get_codespace_info':
                // Get codespace information from database
                $projectID = '';
                $projectQuery = query("SELECT projectID FROM projects WHERE link='$project'");
                if ($projectQuery && mysqli_num_rows($projectQuery) > 0) {
                    $projectData = mysqli_fetch_assoc($projectQuery);
                    $projectID = $projectData['projectID'];
                }
                
                $codespaceQuery = query("SELECT * FROM project_codespaces WHERE project_id='$projectID' AND slug='$codespace'");
                if ($codespaceQuery && mysqli_num_rows($codespaceQuery) > 0) {
                    $codespaceData = mysqli_fetch_assoc($codespaceQuery);
                    echo json_encode(['success' => true, 'codespace' => $codespaceData]);
                } else {
                    echo json_encode(['success' => false, 'error' => 'Codespace not found']);
                }
                break;
                
            default:
                echo json_encode(['success' => false, 'error' => 'Unknown action']);
        }
    } else {
        echo json_encode(['success' => false, 'error' => 'Invalid request']);
    }
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
