<?php

require_once __DIR__ . '/../filesystem.php';

class FilesystemController
{
    public function getStructure(Request $request, Response $response): void
    {
        $projectLink = $request->input('project');
        if (!$projectLink) {
            $response->error('project is required', 400);
            return;
        }
        $fs = new FilesystemManager($projectLink);
        $response->json($fs->getDirectoryStructure());
    }

    public function createFolder(Request $request, Response $response): void
    {
        $projectLink = $request->input('project');
        $name = $request->input('name');
        $parentId = $request->input('parentId', 0);

        if (!$projectLink) {
            $response->error('project is required', 400);
            return;
        }

        if (!$name) {
            $response->error('name is required', 400);
            return;
        }

        $fs = new FilesystemManager($projectLink);
        $fs->createFolder($name, $parentId);
        $response->json(['success' => true, 'message' => 'Folder created successfully']);
    }

    /**
     * POST /v2/filesystem/upload
     * Upload files
     */
    public function upload(Request $request, Response $response): void
    {
        $projectLink = $request->input('project');
        $parentId = $request->input('parentId', 0);

        if (!$projectLink) {
            $response->error('project is required', 400);
            return;
        }

        if (!isset($_FILES['files'])) {
            $response->error('No files uploaded', 400);
            return;
        }

        $fs = new FilesystemManager($projectLink);

        foreach ($_FILES['files']['tmp_name'] as $key => $tmpName) {
            $fs->uploadFile($tmpName, $_FILES['files']['name'][$key], $parentId);
        }

        $response->json(['success' => true, 'message' => 'File(s) uploaded successfully']);
    }

    /**
     * POST /v2/filesystem/move
     * Move an item
     */
    public function move(Request $request, Response $response): void
    {
        $projectLink = $request->input('project');
        $sourceId = $request->input('sourceId');
        $targetFolderId = $request->input('targetFolderId');

        if (!$projectLink) {
            $response->error('project is required', 400);
            return;
        }

        if (!$sourceId || $targetFolderId === null) {
            $response->error('sourceId and targetFolderId are required', 400);
            return;
        }

        $fs = new FilesystemManager($projectLink);
        $fs->moveItem($sourceId, $targetFolderId);
        $response->json(['success' => true, 'message' => 'Item moved successfully']);
    }

    /**
     * POST /v2/filesystem/delete
     * Delete a file
     */
    public function delete(Request $request, Response $response): void
    {
        $projectLink = $request->input('project');
        $name = $request->input('name', '');
        $directory = $request->input('directory', '');

        if (!$projectLink) {
            $response->error('project is required', 400);
            return;
        }

        if (!$name) {
            $response->error('name is required', 400);
            return;
        }

        $fs = new FilesystemManager($projectLink);
        $fs->deleteFile($name, $directory);
        $response->json(['success' => true, 'message' => 'File deleted successfully']);
    }

    /**
     * POST /v2/filesystem/get-file
     * Get file info
     */
    public function getFile(Request $request, Response $response): void
    {
        $projectLink = $request->input('project');
        $name = $request->input('name', '');
        $directory = $request->input('directory', '');

        if (!$projectLink) {
            $response->error('project is required', 400);
            return;
        }

        if (!$name) {
            $response->error('name is required', 400);
            return;
        }

        $fs = new FilesystemManager($projectLink);
        $file = $fs->getFile($name, $directory);
        $response->json(['success' => true, 'file' => $file]);
    }
}
