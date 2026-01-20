<?php
include 'head.php';

class FilesystemManager
{
    private const TYPE_FOLDER = 0;
    private const TYPE_FILE = 1;

    private const BASE_PATH = '/data/filesystem';
    private const PROJECT_BASE_PATH = '/data/project_filesystems';

    private static function generateUUID()
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

    private $tableName;
    private $baseDir;
    private $projectID;

    public function __construct($projectLink = null)
    {
        if ($projectLink) {
            $this->initializeProjectFilesystem($projectLink);
        } else {
            $this->tableName = 'control_center_filesystem';
            $this->baseDir = self::BASE_PATH;
            $this->projectID = null;
        }
    }

    private function initializeProjectFilesystem($projectLink)
    {
        $projectLink = escape_string($projectLink);
        $projectData = fetch_assoc(query("SELECT projectID FROM projects WHERE link='$projectLink'"));

        if (!$projectData || !isset($projectData['projectID'])) {
            throw new Exception('Project not found');
        }

        $this->projectID = $projectData['projectID'];
        $this->tableName = 'project_filesystem';
        $this->baseDir = self::PROJECT_BASE_PATH . '/' . $this->projectID;
    }

    public function getDirectoryStructure($parentId = 0)
    {
        if ($parentId === 0 && $this->projectID) {
            $parentId = $this->getRootParentId();
            if (!$parentId) {
                return [];
            }
        }

        return $this->buildStructureRecursive($parentId);
    }

    private function getRootParentId()
    {
        $sql = "SELECT id FROM {$this->tableName} WHERE name = '' AND projectID = '{$this->projectID}'";
        $result = fetch_assoc(query($sql));
        return $result ? $result['id'] : null;
    }

    private function buildStructureRecursive($parentId)
    {
        $result = [];
        $sql = "SELECT * FROM {$this->tableName} WHERE parent = $parentId";

        if ($this->projectID) {
            $sql .= " AND projectID = '{$this->projectID}'";
        }

        $query = query($sql);
        while ($row = $query->fetch_assoc()) {
            $item = [
                'id' => $row['id'],
                'name' => $row['name'],
                'type' => $row['type'] == self::TYPE_FOLDER ? 'folder' : 'file'
            ];

            // Include projectID if this is a project filesystem
            if ($this->projectID) {
                $item['projectID'] = $this->projectID;
            }

            if ($row['type'] == self::TYPE_FOLDER) {
                $item['children'] = $this->buildStructureRecursive($row['id']);
            } else {
                $item['location'] = $row['location'];
            }

            $result[] = $item;
        }

        return $result;
    }

    public function moveFile($sourceFile, $targetFolder)
    {
        $sourceFile = escape_string($sourceFile);
        $targetFolder = escape_string($targetFolder);

        $sourceData = $this->getFileByLocation($sourceFile);
        if (!$sourceData) {
            throw new Exception('Source file not found');
        }

        $targetData = $this->getFolderByName($targetFolder);
        if (!$targetData) {
            throw new Exception('Target folder not found');
        }

        // Location bleibt UUID-basiert, nur parent wird geändert
        $this->updateFileParent($sourceData['id'], $targetData['id']);

        return true;
    }

    private function getFileByLocation($location)
    {
        $sql = "SELECT * FROM {$this->tableName} WHERE location = '$location'";
        $result = query($sql);
        return $result ? $result->fetch_assoc() : null;
    }

    private function getFolderByName($name)
    {
        $sql = "SELECT * FROM {$this->tableName} WHERE name = '$name' AND type = " . self::TYPE_FOLDER;
        $result = query($sql);
        return $result ? $result->fetch_assoc() : null;
    }

    private function updateFileParent($fileId, $newParentId)
    {
        $sql = "UPDATE {$this->tableName} SET parent = $newParentId WHERE id = $fileId";
        $result = query($sql);

        if (!$result) {
            throw new Exception('Failed to update database');
        }
    }

    public function createFolder($name, $parentName)
    {
        $name = escape_string($name);
        $parentName = escape_string($parentName);

        $parentId = $this->getParentId($parentName);
        $uuid = self::generateUUID();
        $folderPath = $this->baseDir . '/' . $uuid;

        if (!mkdir($folderPath, 0777, true)) {
            throw new Exception('Failed to create folder');
        }

        $this->insertFilesystemEntry($name, $uuid, $parentId, self::TYPE_FOLDER);//'/'.

        return true;
    }

    public function uploadFile($tmpName, $fileName, $parentName)
    {
        $fileName = escape_string($fileName);
        $parentName = escape_string($parentName);

        $parentId = $this->getParentId($parentName);
        $uuid = self::generateUUID();
        $extension = pathinfo($fileName, PATHINFO_EXTENSION);
        $location = $extension ? $uuid . '.' . $extension : $uuid;
        $destination = $this->baseDir . '/' . $location;

        if (!move_uploaded_file($tmpName, $destination)) {
            throw new Exception('Failed to upload file');
        }

        $this->insertFilesystemEntry($fileName, $location, $parentId, self::TYPE_FILE);//'/'.

        return true;
    }

    private function getParentId($parentName)
    {
        $sql = "SELECT id FROM {$this->tableName} WHERE name = '$parentName'";

        if ($this->projectID) {
            $sql .= " AND projectID = '{$this->projectID}'";
        }

        $result = query($sql);
        return $result && $result->num_rows > 0 ? $result->fetch_assoc()['id'] : 0;
    }

    private function insertFilesystemEntry($name, $location, $parentId, $type)
    {
        $sql = "INSERT INTO {$this->tableName} (name, location, parent, type";

        if ($this->projectID) {
            $sql .= ", projectID) VALUES ('$name', '$location', '$parentId', $type, '{$this->projectID}')";
        } else {
            $sql .= ") VALUES ('$name', '$location', '$parentId', $type)";
        }

        $result = query($sql);
        if (!$result) {
            throw new Exception('Failed to insert into database');
        }
    }
}

try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $projectLink = isset($_POST['project']) ? $_POST['project'] : null;
        $fs = new FilesystemManager($projectLink);

        if (isset($_POST['action']) && $_POST['action'] === 'move') {
            $fs->moveFile($_POST['sourceFile'], $_POST['targetFolder']);
            echo json_encode(['success' => true, 'message' => 'File moved successfully']);
        } elseif (isset($_POST['directory']) && isset($_POST['name'])) {
            if (isset($_FILES["files"])) {
                foreach ($_FILES['files']['tmp_name'] as $key => $tmpName) {
                    $fs->uploadFile($tmpName, $_POST['name'], $_POST['directory']);
                }
                echo json_encode(['success' => true, 'message' => 'File(s) uploaded successfully']);
            } else {
                $fs->createFolder($_POST['name'], $_POST['directory']);
                echo json_encode(['success' => true, 'message' => 'Folder created successfully']);
            }
        }
    } elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $projectLink = isset($_GET['project']) ? $_GET['project'] : null;
        $fs = new FilesystemManager($projectLink);

        header('Content-Type: application/json');
        echo json_encode($fs->getDirectoryStructure());
    }
} catch (Exception $e) {
    header('Content-Type: application/json');
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
