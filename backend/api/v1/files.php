<?php


require_once 'helper/BaseAPI.php';
require_once __DIR__ . '/../../helpers/signed_url.php';

class FilesAPI extends BaseAPI {
    private $uploadDir;
    private $allowedTypes = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'pdf', 'doc', 'docx', 'txt', 'csv', 'zip'];
    private $maxFileSize = 10 * 1024 * 1024;

    public function __construct() {
        parent::__construct('2');
        $this->uploadDir = $this->getProjectDirectory();
        $this->ensureUploadDir();
    }

    public function handleRequest() {
        $method = $_SERVER['REQUEST_METHOD'];
        $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $pathParts = explode('/', trim($path, '/'));

        $this->logApiCall('files', $method);

        switch ($method) {
            case 'GET':
                if (isset($pathParts[4]) && $pathParts[4] === 'download-url') {
                    $this->getDownloadUrl($pathParts[3]);
                } else {
                    $this->listFiles();
                }
                break;
            case 'POST':
                if (isset($pathParts[3]) && $pathParts[3] === 'upload') {
                    $this->uploadFile();
                } else {
                    $this->sendError('Not found', 404);
                }
                break;
            case 'DELETE':
                if (isset($pathParts[3])) {
                    $this->deleteFile($pathParts[3]);
                } else {
                    $this->sendError('File id required', 400);
                }
                break;
            default:
                $this->sendError('Method not allowed', 405);
        }
    }

    private function ensureUploadDir() {
        if (!is_dir($this->uploadDir)) {
            mkdir($this->uploadDir, 0755, true);
        }
    }

    private function generateUUID() {
        return sprintf(
            '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff), mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
        );
    }

    private function sanitizeFolder($folder) {
        $folder = str_replace('\\', '/', $folder);
        $parts = array_filter(explode('/', $folder), function ($p) {
            return $p !== '' && $p !== '.' && $p !== '..';
        });
        $safeParts = array_map(function ($p) {
            return preg_replace('/[^a-zA-Z0-9_-]/', '_', $p);
        }, $parts);
        return implode('/', $safeParts);
    }

    private function uploadFile() {
        if (!isset($_FILES['file'])) {
            $this->sendError('No file uploaded', 400);
        }

        $file = $_FILES['file'];
        $folder = $this->sanitizeFolder($_POST['folder'] ?? '');

        if ($file['error'] !== UPLOAD_ERR_OK) {
            $this->sendError('File upload error: ' . $file['error'], 400);
        }

        if ($file['size'] > $this->maxFileSize) {
            $this->sendError('File too large. Maximum size is 10MB', 400);
        }

        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($extension, $this->allowedTypes)) {
            $this->sendError('File type not allowed. Allowed types: ' . implode(', ', $this->allowedTypes), 400);
        }

        $targetDir = $this->uploadDir;
        if ($folder) {
            $targetDir .= $folder . '/';
            if (!is_dir($targetDir)) {
                mkdir($targetDir, 0755, true);
            }
        }

        $uuid = $this->generateUUID();
        $fileName = $extension ? $uuid . '.' . $extension : $uuid;
        $targetPath = $targetDir . $fileName;

        if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
            $this->sendError('Failed to save file', 500);
        }

        $relativePath = ($folder ? $folder . '/' : '') . $fileName;
        $this->saveFileInfo($relativePath, $file['name'], $file['size'], $folder);

        $this->sendSuccess([
            'id' => $relativePath,
            'filename' => $fileName,
            'original_name' => $file['name'],
            'size' => $file['size'],
            'folder' => $folder,
            'url' => $this->getFileUrl($relativePath)
        ], 'File uploaded successfully');
    }

    private function listFiles() {
        $folder = $this->sanitizeFolder($_GET['folder'] ?? '');
        $files = [];

        $searchDir = $this->uploadDir;
        if ($folder) {
            $searchDir .= $folder . '/';
        }

        if (is_dir($searchDir)) {
            $items = scandir($searchDir);
            foreach ($items as $item) {
                if ($item === '.' || $item === '..' || $item === '.files_meta.json') continue;

                $fullPath = $searchDir . $item;
                $isDir = is_dir($fullPath);
                $relativePath = ($folder ? $folder . '/' : '') . $item;

                $files[] = [
                    'id' => $isDir ? null : $relativePath,
                    'name' => $item,
                    'type' => $isDir ? 'directory' : 'file',
                    'size' => $isDir ? null : filesize($fullPath),
                    'modified' => date('Y-m-d H:i:s', filemtime($fullPath)),
                    'url' => $isDir ? null : $this->getFileUrl($relativePath)
                ];
            }
        }

        $this->sendSuccess($files);
    }

    private function deleteFile($fileId) {
        $file = $this->findFileById($fileId);

        if (!$file) {
            $this->sendError('File not found', 404);
        }

        $filePath = $this->uploadDir . $fileId;

        if (file_exists($filePath)) {
            if (unlink($filePath)) {
                $this->removeFileInfo($fileId);
                $this->sendSuccess(null, 'File deleted successfully');
            } else {
                $this->sendError('Failed to delete file', 500);
            }
        } else {
            $this->sendError('File not found', 404);
        }
    }

    private function getDownloadUrl($fileId) {
        $file = $this->findFileById($fileId);

        if (!$file) {
            $this->sendError('File not found', 404);
        }

        $this->sendSuccess(['downloadUrl' => $this->getFileUrl($fileId)]);
    }

    private function getFileUrl($relativePath) {
        $generator = new SignedUrlGenerator();
        $signed = $generator->generateSignedUrl($relativePath, 3600, $this->projectID);
        return $signed['url'];
    }

    private function getProjectDirectory() {
        return '/var/www/api.fringelo.com/project_filesystems/' . $this->projectID . '/';
    }

    private function saveFileInfo($relativePath, $originalName, $size, $folder) {
        $metaFile = $this->uploadDir . '.files_meta.json';
        $meta = [];

        if (file_exists($metaFile)) {
            $meta = json_decode(file_get_contents($metaFile), true) ?: [];
        }

        $meta[$relativePath] = [
            'original_name' => $originalName,
            'size' => $size,
            'folder' => $folder,
            'created_at' => date('Y-m-d H:i:s'),
            'project_id' => $this->projectID,
            'user_id' => $this->userID
        ];

        file_put_contents($metaFile, json_encode($meta, JSON_PRETTY_PRINT));
    }

    private function removeFileInfo($relativePath) {
        $metaFile = $this->uploadDir . '.files_meta.json';

        if (file_exists($metaFile)) {
            $meta = json_decode(file_get_contents($metaFile), true) ?: [];

            if (isset($meta[$relativePath])) {
                unset($meta[$relativePath]);
                file_put_contents($metaFile, json_encode($meta, JSON_PRETTY_PRINT));
            }
        }
    }

    private function findFileById($relativePath) {
        $metaFile = $this->uploadDir . '.files_meta.json';

        if (file_exists($metaFile)) {
            $meta = json_decode(file_get_contents($metaFile), true) ?: [];

            if (isset($meta[$relativePath])) {
                return $meta[$relativePath];
            }
        }

        return null;
    }
}

$api = new FilesAPI();
$api->handleRequest();
