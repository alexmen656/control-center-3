<?php

require_once __DIR__ . '/../controllers/FilesystemController.php';

$router->group('/v2/filesystem', function ($router) {

    // GET /v2/filesystem?project=...
    $router->get('/', [FilesystemController::class, 'getStructure']);

    // POST /v2/filesystem/folder
    $router->post('/folder', [FilesystemController::class, 'createFolder']);

    // POST /v2/filesystem/upload
    $router->post('/upload', [FilesystemController::class, 'upload']);

    // POST /v2/filesystem/move
    $router->post('/move', [FilesystemController::class, 'move']);

    // POST /v2/filesystem/delete
    $router->post('/delete', [FilesystemController::class, 'delete']);

    // POST /v2/filesystem/get-file
    $router->post('/get-file', [FilesystemController::class, 'getFile']);

}, [AuthMiddleware::class]);
