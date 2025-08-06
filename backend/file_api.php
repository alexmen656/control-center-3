<?php
require_once 'head.php';

function getUserIDFromToken() {
    global $jwt_secret;
    
    $headers = getallheaders();
    $authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? '';
    
    if (empty($authHeader)) {
        // For development, allow without token
        return 'dev_user';
    }
    
    $token = $authHeader;
    $userData = SimpleJWT::verify($token, $jwt_secret);
    if (!$userData || !isset($userData['sub'])) {
        return 'dev_user'; // Fallback for development
    }
    
    return $userData['sub'];
}

function getProjectDataPath($project, $userID) {
    $dataDir = __DIR__ . '/../data/projects/' . $userID . '/' . $project;
    if (!is_dir($dataDir)) {
        mkdir($dataDir, 0755, true);
    }
    return $dataDir;
}

function getProjectGitPath($project, $userID) {
    $gitDir = getProjectDataPath($project, $userID) . '/.git';
    return $gitDir;
}

function initializeGitRepo($projectPath) {
    // Create a simple marker file instead of actual git init
    $gitMarker = $projectPath . '/.monaco_git';
    if (!file_exists($gitMarker)) {
        file_put_contents($gitMarker, json_encode([
            'initialized' => true,
            'created' => date('c')
        ]));
        
        // Create initial metadata files
        file_put_contents($projectPath . '/.monaco_staged.json', '{}');
        file_put_contents($projectPath . '/.monaco_lastcommit.json', '{}');
        file_put_contents($projectPath . '/.monaco_commits.json', '[]');
    }
}

try {
    $userID = getUserIDFromToken();
    $project = $_GET['project'] ?? 'default-project';
    $action = $_GET['action'] ?? '';
    
    $projectPath = getProjectDataPath($project, $userID);
    
    // Initialize git repo if it doesn't exist
    initializeGitRepo($projectPath);
    
    switch ($_SERVER['REQUEST_METHOD']) {
        case 'GET':
            handleGetRequest($action, $projectPath, $project, $userID);
            break;
        case 'POST':
            handlePostRequest($projectPath, $project, $userID);
            break;
        case 'PUT':
            handlePutRequest($projectPath, $project, $userID);
            break;
        case 'DELETE':
            handleDeleteRequest($projectPath, $project, $userID);
            break;
        default:
            throw new Exception('Method not allowed');
    }
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['error' => $e->getMessage()]);
}

function handleGetRequest($action, $projectPath, $project, $userID) {
    switch ($action) {
        case 'list':
            echo json_encode(listFiles($projectPath));
            break;
        case 'read':
            $file = $_GET['file'] ?? '';
            echo json_encode(readProjectFile($projectPath, $file));
            break;
        case 'changes':
            // Redirect to monaco_git_api for git operations
            echo json_encode(['success' => true, 'message' => 'Use monaco_git_api.php for git operations']);
            break;
        case 'commits':
            // Redirect to monaco_git_api for git operations
            echo json_encode(['success' => true, 'message' => 'Use monaco_git_api.php for git operations']);
            break;
        default:
            throw new Exception('Invalid action');
    }
}

function handlePostRequest($projectPath, $project, $userID) {
    $input = json_decode(file_get_contents('php://input'), true);
    $action = $input['action'] ?? '';
    
    switch ($action) {
        case 'create_file':
            echo json_encode(createProjectFile($projectPath, $input['path'], $input['content'] ?? ''));
            break;
        case 'create_folder':
            echo json_encode(createProjectFolder($projectPath, $input['path']));
            break;
        case 'commit':
            // Redirect to monaco_git_api for git operations
            echo json_encode(['success' => true, 'message' => 'Use monaco_git_api.php for git operations']);
            break;
        default:
            throw new Exception('Invalid action');
    }
}

function handlePutRequest($projectPath, $project, $userID) {
    $input = json_decode(file_get_contents('php://input'), true);
    $file = $input['file'] ?? '';
    $content = $input['content'] ?? '';
    
    echo json_encode(writeFile($projectPath, $file, $content));
}

function handleDeleteRequest($projectPath, $project, $userID) {
    $input = json_decode(file_get_contents('php://input'), true);
    $file = $input['file'] ?? '';
    
    echo json_encode(deleteFile($projectPath, $file));
}

function listFiles($projectPath, $subPath = '') {
    $fullPath = $projectPath . ($subPath ? '/' . $subPath : '');
    $files = [];
    
    if (!is_dir($fullPath)) {
        return $files;
    }
    
    $items = scandir($fullPath);
    foreach ($items as $item) {
        if ($item === '.' || $item === '..' || $item === '.git') {// || $item === '.monaco_apis'
            continue;
        }
        
        $itemPath = $fullPath . '/' . $item;
        $relativePath = $subPath ? $subPath . '/' . $item : $item;
        
        if (is_dir($itemPath)) {
            $files[] = [
                'name' => $item,
                'path' => $relativePath,
                'type' => 'directory',
                'children' => listFiles($projectPath, $relativePath)
            ];
        } else {
            $files[] = [
                'name' => $item,
                'path' => $relativePath,
                'type' => 'file',
                'size' => filesize($itemPath),
                'modified' => filemtime($itemPath)
            ];
        }
    }
    
    return $files;
}

function readProjectFile($projectPath, $file) {
    $filePath = $projectPath . '/' . $file;
    
    if (!file_exists($filePath) || !is_file($filePath)) {
        throw new Exception('File not found');
    }
    
    if (!is_readable($filePath)) {
        throw new Exception('File not readable');
    }
    
    return [
        'content' => file_get_contents($filePath),
        'path' => $file,
        'size' => filesize($filePath),
        'modified' => filemtime($filePath)
    ];
}

function writeFile($projectPath, $file, $content) {
    $filePath = $projectPath . '/' . $file;
    
    // Create directory if it doesn't exist
    $dir = dirname($filePath);
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
    
    $result = file_put_contents($filePath, $content);
    if ($result === false) {
        throw new Exception('Failed to write file');
    }
    
    return [
        'success' => true,
        'path' => $file,
        'size' => $result,
        'modified' => filemtime($filePath)
    ];
}

function createProjectFile($projectPath, $file, $content = '') {
    $filePath = $projectPath . '/' . $file;
    
    if (file_exists($filePath)) {
        throw new Exception('File already exists');
    }
    
    return writeFile($projectPath, $file, $content);
}

function deleteFile($projectPath, $file) {
    $filePath = $projectPath . '/' . $file;
    
    if (!file_exists($filePath)) {
        throw new Exception('File not found');
    }
    
    if (is_dir($filePath)) {
        // Recursive directory deletion
        $result = removeDirectory($filePath);
    } else {
        $result = unlink($filePath);
    }
    
    if (!$result) {
        throw new Exception('Failed to delete file');
    }
    
    return [
        'success' => true,
        'path' => $file
    ];
}

function createProjectFolder($projectPath, $folderPath) {
    // Validate path
    if (empty($folderPath) || strpos($folderPath, '..') !== false) {
        throw new Exception('Invalid folder path');
    }
    
    $fullFolderPath = $projectPath . '/' . ltrim($folderPath, '/');
    
    // Check if folder already exists
    if (is_dir($fullFolderPath)) {
        throw new Exception('Folder already exists');
    }
    
    // Create the folder
    if (!mkdir($fullFolderPath, 0755, true)) {
        throw new Exception('Failed to create folder');
    }
    
    return [
        'success' => true,
        'path' => $folderPath,
        'message' => 'Folder created successfully'
    ];
}

function removeDirectory($dir) {
    if (!is_dir($dir)) {
        return false;
    }
    
    $files = scandir($dir);
    foreach ($files as $file) {
        if ($file === '.' || $file === '..') {
            continue;
        }
        
        $filePath = $dir . '/' . $file;
        if (is_dir($filePath)) {
            removeDirectory($filePath);
        } else {
            unlink($filePath);
        }
    }
    
    return rmdir($dir);
}
?>
