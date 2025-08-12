<?php

/**
 * Files API - Dateimanagement für CMS Projekte
 * Handles file upload, listing, deletion and download URLs
 */

require_once 'helper/BaseAPI.php';

class FilesAPI extends BaseAPI {
    private $uploadDir;
    private $allowedTypes = ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'doc', 'docx', 'txt', 'csv', 'zip'];
    private $maxFileSize = 10 * 1024 * 1024; // 10MB

    public function __construct() {
        parent::__construct();
        // Verwende das echte Projektverzeichnis des CMS
        $this->uploadDir = $this->getProjectDirectory();
        $this->ensureUploadDir();
    }

    public function handleRequest() {
        $method = $_SERVER['REQUEST_METHOD'];
        $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $pathParts = explode('/', trim($path, '/'));
        
        // Log API call
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
                if (isset($pathParts[4]) && $pathParts[4] === 'upload') {
                    $this->uploadFile();
                }
                break;
            case 'DELETE':
                if (isset($pathParts[3])) {
                    $this->deleteFile($pathParts[3]);
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

    private function uploadFile() {
        if (!isset($_FILES['file'])) {
            $this->sendError('No file uploaded', 400);
        }

        $file = $_FILES['file'];
        $folder = $_POST['folder'] ?? '';

        // Validierung
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

        // Zielverzeichnis und Dateiname
        $targetDir = $this->uploadDir;
        if ($folder) {
            $targetDir .= $this->sanitize($folder) . '/';
            if (!is_dir($targetDir)) {
                mkdir($targetDir, 0755, true);
            }
        }

        $fileName = uniqid() . '_' . $this->sanitize($file['name']);
        $targetPath = $targetDir . $fileName;

        // Datei verschieben
        if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
            $this->sendError('Failed to save file', 500);
        }

        // Datei-Info in DB speichern (falls verfügbar)
        $fileId = $this->saveFileInfo($fileName, $file['name'], $file['size'], $folder);

        $this->sendSuccess([
            'id' => $fileId,
            'filename' => $fileName,
            'original_name' => $file['name'],
            'size' => $file['size'],
            'folder' => $folder,
            'url' => $this->getFileUrl($fileName, $folder)
        ], 'File uploaded successfully');
    }

    private function listFiles() {
        $folder = $_GET['folder'] ?? '';
        $files = [];

        $searchDir = $this->uploadDir;
        if ($folder) {
            $searchDir .= $this->sanitize($folder) . '/';
        }

        if (is_dir($searchDir)) {
            $items = scandir($searchDir);
            foreach ($items as $item) {
                if ($item === '.' || $item === '..') continue;

                $fullPath = $searchDir . $item;
                $isDir = is_dir($fullPath);

                $files[] = [
                    'id' => $isDir ? null : md5($item),
                    'name' => $item,
                    'type' => $isDir ? 'directory' : 'file',
                    'size' => $isDir ? null : filesize($fullPath),
                    'modified' => date('Y-m-d H:i:s', filemtime($fullPath)),
                    'url' => $isDir ? null : $this->getFileUrl($item, $folder)
                ];
            }
        }

        $this->sendSuccess($files);
    }

    private function deleteFile($fileId) {
        // Einfache Implementierung - suche Datei nach ID
        $files = $this->findFileById($fileId);
        
        if (empty($files)) {
            $this->sendError('File not found', 404);
        }

        $file = $files[0];
        $filePath = $this->uploadDir . ($file['folder'] ? $file['folder'] . '/' : '') . $file['filename'];

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
        $files = $this->findFileById($fileId);
        
        if (empty($files)) {
            $this->sendError('File not found', 404);
        }

        $file = $files[0];
        $downloadUrl = $this->getFileUrl($file['filename'], $file['folder']);

        $this->sendSuccess(['downloadUrl' => $downloadUrl]);
    }

    private function getFileUrl($filename, $folder = '') {
        // Projekt-Link abrufen für URL
        if (file_exists('../../mysql.php')) {
            require_once '../../mysql.php';
            $projectResult = query("SELECT link FROM projects WHERE projectID = '{$this->projectID}'");
            if ($projectResult && mysqli_num_rows($projectResult) > 0) {
                $project = mysqli_fetch_assoc($projectResult);
                $projectLink = $project['link'];
                $baseUrl = 'https://alex.polan.sk/data/projects/1/' . $projectLink . '/';
                return $baseUrl . ($folder ? $folder . '/' : '') . $filename;
            }
        }
        
        // Fallback
        $baseUrl = 'https://alex.polan.sk/data/uploads/project_' . $this->projectID . '/';
        return $baseUrl . ($folder ? $folder . '/' : '') . $filename;
    }

    /**
     * Holt das echte Projektverzeichnis basierend auf der projectID
     */
    private function getProjectDirectory() {
        if (file_exists('../../mysql.php')) {
            require_once '../../mysql.php';
            $projectResult = query("SELECT link FROM projects WHERE projectID = '{$this->projectID}'");
            if ($projectResult && mysqli_num_rows($projectResult) > 0) {
                $project = mysqli_fetch_assoc($projectResult);
                $projectLink = $project['link'];
                
                // Verwende das echte CMS-Projektverzeichnis
                return __DIR__ . '/../../../data/projects/1/' . $projectLink . '/';
            }
        }
        
        // Fallback
        return $_SERVER['DOCUMENT_ROOT'] . '/data/uploads/project_' . $this->projectID . '/';
    }

    private function saveFileInfo($filename, $originalName, $size, $folder) {
        $fileId = uniqid();
        
        // Speichere in JSON-Datei als Fallback
        $metaFile = $this->uploadDir . '.files_meta.json';
        $meta = [];
        
        if (file_exists($metaFile)) {
            $meta = json_decode(file_get_contents($metaFile), true) ?: [];
        }

        $meta[$fileId] = [
            'filename' => $filename,
            'original_name' => $originalName,
            'size' => $size,
            'folder' => $folder,
            'created_at' => date('Y-m-d H:i:s'),
            'project_id' => $this->projectID,
            'user_id' => $this->userID
        ];

        file_put_contents($metaFile, json_encode($meta, JSON_PRETTY_PRINT));

        return $fileId;
    }

    private function removeFileInfo($fileId) {
        $metaFile = $this->uploadDir . '.files_meta.json';
        
        if (file_exists($metaFile)) {
            $meta = json_decode(file_get_contents($metaFile), true) ?: [];
            
            if (isset($meta[$fileId])) {
                unset($meta[$fileId]);
                file_put_contents($metaFile, json_encode($meta, JSON_PRETTY_PRINT));
            }
        }
    }

    private function findFileById($fileId) {
        $metaFile = $this->uploadDir . '.files_meta.json';
        
        if (file_exists($metaFile)) {
            $meta = json_decode(file_get_contents($metaFile), true) ?: [];
            
            if (isset($meta[$fileId])) {
                return [$meta[$fileId]];
            }
        }

        return [];
    }
}

// Handle the request
$api = new FilesAPI();
$api->handleRequest();
