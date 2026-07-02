<?php
include 'head.php';

class FilesystemManager
{
    private const TYPE_FOLDER = 0;
    private const TYPE_FILE = 1;

    private const BASE_PATH = '/var/www/api.fringelo.com/filesystem';
    private const PROJECT_BASE_PATH = '/var/www/api.fringelo.com/project_filesystems';

    private static function generateUUID()
    {
        return sprintf(
            '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff)
        );
    }

    private $tableName;
    private $baseDir;
    private $projectID;

    public function __construct($projectLink = null)
    {
        if (!$projectLink) {
            throw new Exception('A project is required');
        }

        $this->initializeProjectFilesystem($projectLink);
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
                return ['rootId' => null, 'items' => []];
            }
        }

        return [
            'rootId' => $this->projectID ? $this->getRootParentId() : 0,
            'items' => $this->buildStructureRecursive($parentId)
        ];
    }

    public function getRootParentId()
    {
        if (!$this->projectID) {
            return 0;
        }
        $sql = "SELECT id FROM {$this->tableName} WHERE name = '' AND projectID = '{$this->projectID}'";
        $result = fetch_assoc(query($sql));
        return $result ? intval($result['id']) : null;
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
                'id' => intval($row['id']),
                'name' => $row['name'],
                'type' => $row['type'] == self::TYPE_FOLDER ? 'folder' : 'file',
                'parent' => intval($row['parent']),
                'location' => $row['location']
            ];

            if ($this->projectID) {
                $item['projectID'] = $this->projectID;
            }

            if ($row['type'] == self::TYPE_FOLDER) {
                $item['children'] = $this->buildStructureRecursive($row['id']);
            }

            $result[] = $item;
        }

        return $result;
    }

    public function moveItem($sourceItemId, $targetFolderId)
    {
        $sourceItemId = intval($sourceItemId);
        $targetFolderId = intval($targetFolderId);

        $sourceData = $this->getItemById($sourceItemId);
        if (!$sourceData) {
            throw new Exception('Source item not found');
        }

        if ($targetFolderId !== 0) {
            $targetData = $this->getItemById($targetFolderId);
            if (!$targetData || $targetData['type'] != self::TYPE_FOLDER) {
                throw new Exception('Target folder not found');
            }
        } elseif ($this->projectID) {
            $targetFolderId = $this->getRootParentId();
            if (!$targetFolderId) {
                throw new Exception('Root folder not found');
            }
        }

        $this->updateFileParent($sourceItemId, $targetFolderId);
        return true;
    }

    private function getItemById($id)
    {
        $sql = "SELECT * FROM {$this->tableName} WHERE id = $id";
        if ($this->projectID) {
            $sql .= " AND projectID = '{$this->projectID}'";
        }
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

    public function createFolder($name, $parentId)
    {
        $name = escape_string($name);
        $parentId = intval($parentId);

        if ($parentId === 0 && $this->projectID) {
            $parentId = $this->getRootParentId();
        }

        $this->insertFilesystemEntry($name, null, $parentId, self::TYPE_FOLDER);
        return true;
    }

    public function uploadFile($tmpName, $fileName, $parentId)
    {
        $fileName = escape_string($fileName);
        $parentId = intval($parentId);

        if ($parentId === 0 && $this->projectID) {
            $parentId = $this->getRootParentId();
        }

        $uuid = self::generateUUID();
        $extension = pathinfo($fileName, PATHINFO_EXTENSION);
        $location = $extension ? $uuid . '.' . $extension : $uuid;

        if (!is_dir($this->baseDir)) {
            mkdir($this->baseDir, 0777, true);
        }

        $destination = $this->baseDir . '/' . $location;

        if (!move_uploaded_file($tmpName, $destination)) {
            throw new Exception('Failed to upload file');
        }

        $this->insertFilesystemEntry($fileName, $location, $parentId, self::TYPE_FILE);
        return true;
    }

    private function insertFilesystemEntry($name, $location, $parentId, $type)
    {
        $locationValue = $location === null ? 'NULL' : "'$location'";
        $sql = "INSERT INTO {$this->tableName} (name, location, parent, type";

        if ($this->projectID) {
            $sql .= ", projectID) VALUES ('$name', $locationValue, '$parentId', $type, '{$this->projectID}')";
        } else {
            $sql .= ") VALUES ('$name', $locationValue, '$parentId', $type)";
        }

        $result = query($sql);
        if (!$result) {
            throw new Exception('Failed to insert into database');
        }
    }

    public function deleteFile($name, $directory = '')
    {
        $name = escape_string($name);
        $directory = escape_string($directory);

        $parentId = 0;
        if (!empty($directory)) {
            $sql = "SELECT id FROM {$this->tableName} WHERE name = '$directory' AND type = 0";
            if ($this->projectID) {
                $sql .= " AND projectID = '{$this->projectID}'";
            }
            $parentResult = query($sql);
            if (!$parentResult || mysqli_num_rows($parentResult) == 0) {
                throw new Exception('Directory not found');
            }
            $parentId = fetch_assoc($parentResult)['id'];
        } elseif ($this->projectID) {
            $parentId = $this->getRootParentId();
        }

        $sql = "SELECT id, location FROM {$this->tableName} WHERE name = '$name' AND parent = '$parentId' AND type = 1";
        if ($this->projectID) {
            $sql .= " AND projectID = '{$this->projectID}'";
        }

        $result = query($sql);
        if (!$result || mysqli_num_rows($result) == 0) {
            throw new Exception('File not found');
        }

        $fileData = fetch_assoc($result);
        $fileId = $fileData['id'];
        $location = $fileData['location'];

        if ($location) {
            $filePath = $this->baseDir . '/' . $location;
            if (file_exists($filePath)) {
                if (!unlink($filePath)) {
                    throw new Exception('Failed to delete physical file');
                }
            }
        }

        $deleteSql = "DELETE FROM {$this->tableName} WHERE id = $fileId";
        if (!query($deleteSql)) {
            throw new Exception('Failed to delete from database');
        }

        return true;
    }

    public function getFile($name, $directory = '')
    {
        $name = escape_string($name);
        $directory = escape_string($directory);

        $parentId = 0;
        if (!empty($directory)) {
            $sql = "SELECT id FROM {$this->tableName} WHERE name = '$directory' AND type = 0";
            if ($this->projectID) {
                $sql .= " AND projectID = '{$this->projectID}'";
            }
            $parentResult = query($sql);
            if (!$parentResult || mysqli_num_rows($parentResult) == 0) {
                throw new Exception('Directory not found');
            }
            $parentId = fetch_assoc($parentResult)['id'];
        } elseif ($this->projectID) {
            $parentId = $this->getRootParentId();
        }

        $sql = "SELECT * FROM {$this->tableName} WHERE name = '$name' AND parent = '$parentId' AND type = 1";
        if ($this->projectID) {
            $sql .= " AND projectID = '{$this->projectID}'";
        }

        $result = query($sql);
        if (!$result || mysqli_num_rows($result) == 0) {
            throw new Exception('File not found');
        }

        return fetch_assoc($result);
    }
}

/*try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $projectLink = isset($_POST['project']) ? $_POST['project'] : null;
        $fs = new FilesystemManager($projectLink);

        if (isset($_POST['action']) && $_POST['action'] === 'move') {
            $fs->moveItem($_POST['sourceId'], $_POST['targetFolderId']);
            echo json_encode(['success' => true, 'message' => 'Item moved successfully']);
        } elseif (isset($_POST['action']) && $_POST['action'] === 'delete') {
            $name = $_POST['name'] ?? '';
            $directory = $_POST['directory'] ?? '';
            $fs->deleteFile($name, $directory);
            echo json_encode(['success' => true, 'message' => 'File deleted successfully']);
        } elseif (isset($_POST['action']) && $_POST['action'] === 'getFile') {
            $name = $_POST['name'] ?? '';
            $directory = $_POST['directory'] ?? '';
            $file = $fs->getFile($name, $directory);
            echo json_encode(['success' => true, 'file' => $file]);
        } elseif (isset($_POST['parentId']) && isset($_POST['name'])) {
            if (isset($_FILES["files"])) {
                foreach ($_FILES['files']['tmp_name'] as $key => $tmpName) {
                    $fs->uploadFile($tmpName, $_FILES['files']['name'][$key], $_POST['parentId']);
                }
                echo json_encode(['success' => true, 'message' => 'File(s) uploaded successfully']);
            } else {
                $fs->createFolder($_POST['name'], $_POST['parentId']);
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
*/