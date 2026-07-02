<?php
include 'head.php';

function generateUUID()
{
    return sprintf(
        '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
        mt_rand(0, 0xffff), mt_rand(0, 0xffff),
        mt_rand(0, 0xffff),
        mt_rand(0, 0x0fff) | 0x4000,
        mt_rand(0, 0x3fff) | 0x8000,
        mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
    );
}

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
    
    // The global (control center) filesystem has been removed. A project is required.
    if (!$project) {
        echo json_encode(['success' => false, 'message' => 'A project is required']);
        exit;
    }

    try {
        {
            $projectData = fetch_assoc(query("SELECT projectID FROM projects WHERE link='$project'"));
            if (!$projectData) {
                echo json_encode(['success' => false, 'message' => 'Project not found']);
                exit;
            }
            $projectID = $projectData['projectID'];

            $dir = '/var/www/api.fringelo.com/project_filesystems/' . $projectID;

            $parentQuery = query("SELECT id FROM project_filesystem WHERE name = '$directory' AND projectID = '$projectID'");
            $parentId = $parentQuery ? $parentQuery->fetch_assoc()['id'] : 0;

            if ($parentId == 0 && !empty($directory)) {
                echo json_encode(['success' => false, 'message' => 'Parent directory not found']);
                exit;
            }
            
            $uuid = generateUUID();
            $extension = pathinfo($name, PATHINFO_EXTENSION);
            $fileLocation = $extension ? $uuid . '.' . $extension : $uuid;
            $filePath = $dir . '/' . $fileLocation;

            $fileDir = dirname($filePath);
            if (!file_exists($fileDir)) {
                mkdir($fileDir, 0777, true);
            }

            if ($isBase64) {
                $fileContent = base64_decode($content);
            } else {
                $fileContent = $content;
            }

            if (file_put_contents($filePath, $fileContent) === false) {
                echo json_encode(['success' => false, 'message' => 'Failed to write file']);
                exit;
            }

            $insert = query("INSERT INTO project_filesystem (name, location, parent, type, projectID) VALUES ('$name', '$fileLocation', '$parentId', 1, '$projectID')");

            if (!$insert) {
                unlink($filePath);
                echo json_encode(['success' => false, 'message' => 'Database insert failed']);
                exit;
            }
            
            echo json_encode([
                'success' => true,
                'message' => 'File uploaded successfully',
                'path' => $fileLocation
            ]);
            
        } else {
            $dir = '/var/www/api.fringelo.com/filesystem';

            $parentQuery = query("SELECT id FROM control_center_filesystem WHERE name = '$directory'");
            $parentId = $parentQuery ? $parentQuery->fetch_assoc()['id'] : 0;

            if ($parentId == 0 && !empty($directory)) {
                echo json_encode(['success' => false, 'message' => 'Parent directory not found']);
                exit;
            }

            $uuid = generateUUID();
            $extension = pathinfo($name, PATHINFO_EXTENSION);
            $fileLocation = $extension ? $uuid . '.' . $extension : $uuid;
            $filePath = $dir . '/' . $fileLocation;

            $fileDir = dirname($filePath);
            if (!file_exists($fileDir)) {
                mkdir($fileDir, 0777, true);
            }

            if ($isBase64) {
                $fileContent = base64_decode($content);
            } else {
                $fileContent = $content;
            }

            if (file_put_contents($filePath, $fileContent) === false) {
                echo json_encode(['success' => false, 'message' => 'Failed to write file']);
                exit;
            }

            $insert = query("INSERT INTO control_center_filesystem (name, location, parent, type) VALUES ('$name', '$fileLocation', '$parentId', 1)");
            
            if (!$insert) {
                unlink($filePath);
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
    $name = escape_string($input['name'] ?? '');
    $directory = escape_string($input['directory'] ?? '');
    $project = isset($input['project']) ? escape_string($input['project']) : null;
    
    if (empty($name)) {
        echo json_encode(['success' => false, 'message' => 'Folder name is required']);
        exit;
    }
    
    try {
        if ($project) {
            $projectData = fetch_assoc(query("SELECT projectID FROM projects WHERE link='$project'"));
            if (!$projectData) {
                echo json_encode(['success' => false, 'message' => 'Project not found']);
                exit;
            }
            $projectID = $projectData['projectID'];
            
            $dir = '/var/www/api.fringelo.com/project_filesystems/' . $projectID;
            
            $parentQuery = query("SELECT id FROM project_filesystem WHERE name = '$directory' AND projectID = '$projectID'");
            $parentId = $parentQuery ? $parentQuery->fetch_assoc()['id'] : 0;
            
            $uuid = generateUUID();
            $folderPath = $dir . '/' . $uuid;

            if (!mkdir($folderPath, 0777, true)) {
                echo json_encode(['success' => false, 'message' => 'Failed to create folder']);
                exit;
            }

            $insert = query("INSERT INTO project_filesystem (name, location, parent, type, projectID) VALUES ('$name', '/$uuid', '$parentId', 0, '$projectID')");
            
            if (!$insert) {
                rmdir($folderPath);
                echo json_encode(['success' => false, 'message' => 'Database insert failed']);
                exit;
            }
            
            echo json_encode([
                'success' => true,
                'message' => 'Folder created successfully',
                'path' => $uuid
            ]);

        } else {
            $dir = '/var/www/api.fringelo.com/filesystem';

            $parentQuery = query("SELECT id FROM control_center_filesystem WHERE name = '$directory'");
            $parentId = $parentQuery ? $parentQuery->fetch_assoc()['id'] : 0;

            $uuid = generateUUID();
            $folderPath = $dir . '/' . $uuid;

            if (!mkdir($folderPath, 0777, true)) {
                echo json_encode(['success' => false, 'message' => 'Failed to create folder']);
                exit;
            }

            $insert = query("INSERT INTO control_center_filesystem (name, location, parent, type) VALUES ('$name', '/$uuid', '$parentId', 0)");
            
            if (!$insert) {
                rmdir($folderPath);
                echo json_encode(['success' => false, 'message' => 'Database insert failed']);
                exit;
            }
            
            echo json_encode([
                'success' => true,
                'message' => 'Folder created successfully',
                'path' => $uuid
            ]);
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid action']);
}
