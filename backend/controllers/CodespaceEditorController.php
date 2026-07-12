<?php

class CodespaceEditorController
{
    private function codespacePath(Request $request): array
    {
        $userID = $request->userID;
        $project = $request->input('project', '');
        $codespace = $request->input('codespace', 'main');

        $path = dirname(__DIR__) . '/../data/projects/' . $userID . '/' . $project . '/' . $codespace;

        return [$path, $project, $codespace];
    }

    public function handle(Request $request, Response $response): void
    {
        try {
            $action = $request->input('action', '');
            [$codespacePath, $project, $codespace] = $this->codespacePath($request);

            switch ($action) {
                case 'load_file':
                    $this->loadFile($response, $codespacePath, $request->input('filename', ''));
                    break;
                case 'save_file':
                    $this->saveFile($response, $codespacePath, $request->input('filename', ''), $request->input('content', ''));
                    break;
                case 'list_files':
                    $this->listFiles($response, $codespacePath);
                    break;
                case 'create_file':
                    $this->createFile($response, $codespacePath, $request->input('filename', ''), $request->input('content', ''));
                    break;
                case 'delete_file':
                    $this->deleteFile($response, $codespacePath, $request->input('filename', ''));
                    break;
                case 'get_codespace_info':
                    $this->getCodespaceInfo($response, $project, $codespace);
                    break;
                default:
                    $response->json(['success' => false, 'error' => 'Unknown action']);
            }
        } catch (Exception $e) {
            $response->json(['success' => false, 'error' => $e->getMessage()]);
        }
    }

    private function loadFile(Response $response, $codespacePath, $filename): void
    {
        $path = $codespacePath . '/' . ltrim($filename, '/');

        if (!file_exists($path)) {
            $response->json(['success' => false, 'error' => 'File not found']);
            return;
        }

        $response->json(['success' => true, 'content' => file_get_contents($path)]);
    }

    private function saveFile(Response $response, $codespacePath, $filename, $content): void
    {
        $path = $codespacePath . '/' . ltrim($filename, '/');

        $dir = dirname($path);
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }

        $result = file_put_contents($path, $content);
        if ($result !== false) {
            $response->json(['success' => true, 'message' => 'File saved successfully']);
        } else {
            $response->json(['success' => false, 'error' => 'Failed to save file']);
        }
    }

    private function listFiles(Response $response, $codespacePath): void
    {
        if (!is_dir($codespacePath)) {
            $response->json(['success' => false, 'error' => 'Codespace not found']);
            return;
        }

        $response->json(['success' => true, 'files' => $this->scanDirectory($codespacePath, $codespacePath)]);
    }

    private function scanDirectory($dir, $basePath): array
    {
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
                    'children' => $this->scanDirectory($fullPath, $basePath)
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

    private function createFile(Response $response, $codespacePath, $filename, $content): void
    {
        $path = $codespacePath . '/' . ltrim($filename, '/');

        if (file_exists($path)) {
            $response->json(['success' => false, 'error' => 'File already exists']);
            return;
        }

        $dir = dirname($path);
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }

        $result = file_put_contents($path, $content);
        if ($result !== false) {
            $response->json(['success' => true, 'message' => 'File created successfully']);
        } else {
            $response->json(['success' => false, 'error' => 'Failed to create file']);
        }
    }

    private function deleteFile(Response $response, $codespacePath, $filename): void
    {
        $path = $codespacePath . '/' . ltrim($filename, '/');

        if (!file_exists($path)) {
            $response->json(['success' => false, 'error' => 'File not found']);
            return;
        }

        if (is_dir($path)) {
            $result = $this->deleteDirectory($path);
        } else {
            $result = unlink($path);
        }

        if ($result) {
            $response->json(['success' => true, 'message' => 'File deleted successfully']);
        } else {
            $response->json(['success' => false, 'error' => 'Failed to delete file']);
        }
    }

    private function deleteDirectory($dir): bool
    {
        $files = array_diff(scandir($dir), ['.', '..']);
        foreach ($files as $file) {
            $filePath = $dir . '/' . $file;
            is_dir($filePath) ? $this->deleteDirectory($filePath) : unlink($filePath);
        }
        return rmdir($dir);
    }

    private function getCodespaceInfo(Response $response, $project, $codespace): void
    {
        $projectEsc = escape_string($project);
        $projectID = '';
        $projectQuery = query("SELECT projectID FROM projects WHERE link='$projectEsc'");
        if ($projectQuery && mysqli_num_rows($projectQuery) > 0) {
            $projectID = mysqli_fetch_assoc($projectQuery)['projectID'];
        }

        $projectID = escape_string($projectID);
        $codespaceEsc = escape_string($codespace);
        $codespaceQuery = query("SELECT * FROM project_codespaces WHERE project_id='$projectID' AND slug='$codespaceEsc'");
        if ($codespaceQuery && mysqli_num_rows($codespaceQuery) > 0) {
            $response->json(['success' => true, 'codespace' => mysqli_fetch_assoc($codespaceQuery)]);
        } else {
            $response->json(['success' => false, 'error' => 'Codespace not found']);
        }
    }
}
