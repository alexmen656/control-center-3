<?php
include 'head.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Only POST requests allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    echo json_encode(['success' => false, 'message' => 'Invalid JSON input']);
    exit;
}

$action = $input['action'] ?? 'upload';

if ($action === 'upload_file') {
    // Upload file with base64 content
    $name = escape_string($input['name'] ?? '');
    $content = $input['content'] ?? '';
    $directory = escape_string($input['directory'] ?? '');
    $project = isset($input['project']) ? escape_string($input['project']) : null;
    $isBase64 = $input['isBase64'] ?? false;
    
    if (empty($name)) {
        echo json_encode(['success' => false, 'message' => 'File name is required']);
        exit;
    }
    
    if (empty($content)) {
        echo json_encode(['success' => false, 'message' => 'File content is required']);
        exit;
    }
    
    try {
        if ($project) {
            // Project filesystem
            $projectData = fetch_assoc(query("SELECT projectID FROM projects WHERE link='$project'"));
            if (!$projectData) {
                echo json_encode(['success' => false, 'message' => 'Project not found']);
                exit;
            }
            $projectID = $projectData['projectID'];
            
            $dir = '/data/project_filesystems/' . $projectID;
            
            // Get parent ID
            $parentQuery = query("SELECT id FROM project_filesystem WHERE name = '$directory' AND projectID = '$projectID'");
            $parentId = $parentQuery ? $parentQuery->fetch_assoc()['id'] : 0;
            
            if ($parentId == 0 && !empty($directory)) {
                echo json_encode(['success' => false, 'message' => 'Parent directory not found']);
                exit;
            }
            
            // Create file path
            $filePath = $dir . '/' . $directory;
            if (!empty($directory)) {
                $filePath .= '/';
            }
            $filePath .= $name;
            
            // Ensure directory exists
            $fileDir = dirname($filePath);
            if (!file_exists($fileDir)) {
                mkdir($fileDir, 0777, true);
            }
            
            // Decode and write file
            if ($isBase64) {
                $fileContent = base64_decode($content);
            } else {
                $fileContent = $content;
            }
            
            if (file_put_contents($filePath, $fileContent) === false) {
                echo json_encode(['success' => false, 'message' => 'Failed to write file']);
                exit;
            }
            
            // Update database
            $fileLocation = $directory;
            if (!empty($directory)) {
                $fileLocation .= '/';
            }
            $fileLocation .= $name;
            
            $insert = query("INSERT INTO project_filesystem (name, location, parent, type, projectID) VALUES ('$name', '$fileLocation', '$parentId', 1, '$projectID')");
            
            if (!$insert) {
                unlink($filePath); // Rollback file creation
                echo json_encode(['success' => false, 'message' => 'Database insert failed']);
                exit;
            }
            
            echo json_encode([
                'success' => true,
                'message' => 'File uploaded successfully',
                'path' => $fileLocation
            ]);
            
        } else {
            // Control Center filesystem
            $dir = '/data/filesystem';
            
            // Get parent ID
            $parentQuery = query("SELECT id FROM control_center_filesystem WHERE name = '$directory'");
            $parentId = $parentQuery ? $parentQuery->fetch_assoc()['id'] : 0;
            
            if ($parentId == 0 && !empty($directory)) {
                echo json_encode(['success' => false, 'message' => 'Parent directory not found']);
                exit;
            }
            
            // Create file path
            $filePath = $dir . '/' . $directory;
            if (!empty($directory)) {
                $filePath .= '/';
            }
            $filePath .= $name;
            
            // Ensure directory exists
            $fileDir = dirname($filePath);
            if (!file_exists($fileDir)) {
                mkdir($fileDir, 0777, true);
            }
            
            // Decode and write file
            if ($isBase64) {
                $fileContent = base64_decode($content);
            } else {
                $fileContent = $content;
            }
            
            if (file_put_contents($filePath, $fileContent) === false) {
                echo json_encode(['success' => false, 'message' => 'Failed to write file']);
                exit;
            }
            
            // Update database
            $fileLocation = $directory;
            if (!empty($directory)) {
                $fileLocation .= '/';
            }
            $fileLocation .= $name;
            
            $insert = query("INSERT INTO control_center_filesystem (name, location, parent, type) VALUES ('$name', '$fileLocation', '$parentId', 1)");
            
            if (!$insert) {
                unlink($filePath); // Rollback file creation
                echo json_encode(['success' => false, 'message' => 'Database insert failed']);
                exit;
            }
            
            echo json_encode([
                'success' => true,
                'message' => 'File uploaded successfully',
                'path' => $fileLocation
            ]);
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
    
} elseif ($action === 'create_folder') {
    // Create folder
    $name = escape_string($input['name'] ?? '');
    $directory = escape_string($input['directory'] ?? '');
    $project = isset($input['project']) ? escape_string($input['project']) : null;
    
    if (empty($name)) {
        echo json_encode(['success' => false, 'message' => 'Folder name is required']);
        exit;
    }
    
    try {
        if ($project) {
            // Project filesystem
            $projectData = fetch_assoc(query("SELECT projectID FROM projects WHERE link='$project'"));
            if (!$projectData) {
                echo json_encode(['success' => false, 'message' => 'Project not found']);
                exit;
            }
            $projectID = $projectData['projectID'];
            
            $dir = '/data/project_filesystems/' . $projectID;
            
            // Get parent ID
            $parentQuery = query("SELECT id FROM project_filesystem WHERE name = '$directory' AND projectID = '$projectID'");
            $parentId = $parentQuery ? $parentQuery->fetch_assoc()['id'] : 0;
            
            // Create folder path
            $folderPath = $dir . '/' . $directory;
            if (!empty($directory)) {
                $folderPath .= '/';
            }
            $folderPath .= $name;
            
            if (file_exists($folderPath)) {
                echo json_encode(['success' => false, 'message' => 'Folder already exists']);
                exit;
            }
            
            if (!mkdir($folderPath, 0777, true)) {
                echo json_encode(['success' => false, 'message' => 'Failed to create folder']);
                exit;
            }
            
            // Update database
            $folderLocation = $directory;
            if (!empty($directory)) {
                $folderLocation .= '/';
            }
            $folderLocation .= $name;
            
            $insert = query("INSERT INTO project_filesystem (name, location, parent, type, projectID) VALUES ('$name', '$folderLocation', '$parentId', 0, '$projectID')");
            
            if (!$insert) {
                rmdir($folderPath); // Rollback folder creation
                echo json_encode(['success' => false, 'message' => 'Database insert failed']);
                exit;
            }
            
            echo json_encode([
                'success' => true,
                'message' => 'Folder created successfully',
                'path' => $folderLocation
            ]);
            
        } else {
            // Control Center filesystem
            $dir = '/data/filesystem';
            
            // Get parent ID
            $parentQuery = query("SELECT id FROM control_center_filesystem WHERE name = '$directory'");
            $parentId = $parentQuery ? $parentQuery->fetch_assoc()['id'] : 0;
            
            // Create folder path
            $folderPath = $dir . '/' . $directory;
            if (!empty($directory)) {
                $folderPath .= '/';
            }
            $folderPath .= $name;
            
            if (file_exists($folderPath)) {
                echo json_encode(['success' => false, 'message' => 'Folder already exists']);
                exit;
            }
            
            if (!mkdir($folderPath, 0777, true)) {
                echo json_encode(['success' => false, 'message' => 'Failed to create folder']);
                exit;
            }
            
            // Update database
            $folderLocation = $directory;
            if (!empty($directory)) {
                $folderLocation .= '/';
            }
            $folderLocation .= $name;
            
            $insert = query("INSERT INTO control_center_filesystem (name, location, parent, type) VALUES ('$name', '$folderLocation', '$parentId', 0)");
            
            if (!$insert) {
                rmdir($folderPath); // Rollback folder creation
                echo json_encode(['success' => false, 'message' => 'Database insert failed']);
                exit;
            }
            
            echo json_encode([
                'success' => true,
                'message' => 'Folder created successfully',
                'path' => $folderLocation
            ]);
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid action']);
}
