<?php

class CodespaceFilesController
{
    private function resolve(Request $request): array
    {
        $userID = $request->userID;
        $project = $request->input('project', 'default-project');
        $codespace = $request->input('codespace', 'main');

        requireExistingCodespace($userID, $project, $codespace);

        $dataDir = dirname(__DIR__) . '/../data/projects/' . $userID . '/' . $project . '/' . $codespace;
        if (!is_dir($dataDir)) {
            mkdir($dataDir, 0755, true);
        }

        return [$dataDir, $project, $codespace];
    }

    public function handleGet(Request $request, Response $response): void
    {
        try {
            [$projectPath] = $this->resolve($request);
            $action = $request->input('action', '');

            switch ($action) {
                case 'list':
                    $response->json($this->listFiles($projectPath));
                    break;
                case 'read':
                    $response->json($this->readProjectFile($projectPath, $request->input('file', '')));
                    break;
                default:
                    $response->error('Invalid action', 400);
            }
        } catch (Exception $e) {
            $response->error($e->getMessage(), 400);
        }
    }

    public function handlePost(Request $request, Response $response): void
    {
        try {
            [$projectPath] = $this->resolve($request);
            $action = $request->input('action', '');

            switch ($action) {
                case 'create_file':
                    $response->json($this->createProjectFile($projectPath, $request->input('path', ''), $request->input('content', '')));
                    break;
                case 'create_folder':
                    $response->json($this->createProjectFolder($projectPath, $request->input('path', '')));
                    break;
                default:
                    $response->error('Invalid action', 400);
            }
        } catch (Exception $e) {
            $response->error($e->getMessage(), 400);
        }
    }

    public function handlePut(Request $request, Response $response): void
    {
        try {
            [$projectPath] = $this->resolve($request);
            $response->json($this->writeFile($projectPath, $request->input('file', ''), $request->input('content', '')));
        } catch (Exception $e) {
            $response->error($e->getMessage(), 400);
        }
    }

    public function handleDelete(Request $request, Response $response): void
    {
        try {
            [$projectPath] = $this->resolve($request);
            $response->json($this->deleteFile($projectPath, $request->input('file', '')));
        } catch (Exception $e) {
            $response->error($e->getMessage(), 400);
        }
    }

    private function listFiles($projectPath, $subPath = ''): array
    {
        $fullPath = $projectPath . ($subPath ? '/' . $subPath : '');
        $files = [];

        if (!is_dir($fullPath)) {
            return $files;
        }

        $items = scandir($fullPath);
        foreach ($items as $item) {
            if ($item === '.' || $item === '..' || $item === '.git') {
                continue;
            }

            $itemPath = $fullPath . '/' . $item;
            $relativePath = $subPath ? $subPath . '/' . $item : $item;

            if (is_dir($itemPath)) {
                $files[] = [
                    'name' => $item,
                    'path' => $relativePath,
                    'type' => 'directory',
                    'children' => $this->listFiles($projectPath, $relativePath)
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

    private function readProjectFile($projectPath, $file): array
    {
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

    private function writeFile($projectPath, $file, $content): array
    {
        $filePath = $projectPath . '/' . $file;

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

    private function createProjectFile($projectPath, $file, $content = ''): array
    {
        $filePath = $projectPath . '/' . $file;

        if (file_exists($filePath)) {
            throw new Exception('File already exists');
        }

        return $this->writeFile($projectPath, $file, $content);
    }

    private function deleteFile($projectPath, $file): array
    {
        $filePath = $projectPath . '/' . $file;

        if (!file_exists($filePath)) {
            throw new Exception('File not found');
        }

        if (is_dir($filePath)) {
            $result = $this->removeDirectory($filePath);
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

    private function createProjectFolder($projectPath, $folderPath): array
    {
        if (empty($folderPath) || strpos($folderPath, '..') !== false) {
            throw new Exception('Invalid folder path');
        }

        $fullFolderPath = $projectPath . '/' . ltrim($folderPath, '/');

        if (is_dir($fullFolderPath)) {
            throw new Exception('Folder already exists');
        }

        if (!mkdir($fullFolderPath, 0755, true)) {
            throw new Exception('Failed to create folder');
        }

        return [
            'success' => true,
            'path' => $folderPath,
            'message' => 'Folder created successfully'
        ];
    }

    private function removeDirectory($dir): bool
    {
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
                $this->removeDirectory($filePath);
            } else {
                unlink($filePath);
            }
        }

        return rmdir($dir);
    }
}
