<?php

require_once __DIR__ . '/../controllers/CodespacesController.php';
require_once __DIR__ . '/../controllers/CodespaceDomainsController.php';
require_once __DIR__ . '/../controllers/CodespaceFilesController.php';
require_once __DIR__ . '/../controllers/CodespaceGitController.php';
require_once __DIR__ . '/../controllers/CodespaceEditorController.php';
require_once __DIR__ . '/../controllers/CodespaceDeployController.php';

$router->group('/v2/codespaces', function ($router) {

    $router->get('/', [CodespacesController::class, 'list']);
    $router->post('/', [CodespacesController::class, 'create']);

    $router->get('/templates', [CodespacesController::class, 'templates']);
    $router->get('/user-projects', [CodespacesController::class, 'userProjects']);
    $router->post('/reorder', [CodespacesController::class, 'reorder']);

    $router->get('/files', [CodespaceFilesController::class, 'handleGet']);
    $router->post('/files', [CodespaceFilesController::class, 'handlePost']);
    $router->put('/files', [CodespaceFilesController::class, 'handlePut']);
    $router->delete('/files', [CodespaceFilesController::class, 'handleDelete']);

    $router->get('/git', [CodespaceGitController::class, 'handleGet']);
    $router->post('/git', [CodespaceGitController::class, 'handlePost']);

    $router->get('/deployments', [CodespaceDeployController::class, 'list']);
    $router->post('/deploy', [CodespaceDeployController::class, 'trigger']);

    $router->post('/editor', [CodespaceEditorController::class, 'handle']);

    $router->get('/{id}/domain', [CodespaceDomainsController::class, 'get']);
    $router->post('/{id}/domain', [CodespaceDomainsController::class, 'connect']);
    $router->delete('/{id}/domain', [CodespaceDomainsController::class, 'disconnect']);

    $router->post('/{id}/transfer', [CodespacesController::class, 'transfer']);

    $router->put('/{id}', [CodespacesController::class, 'update']);
    $router->delete('/{id}', [CodespacesController::class, 'delete']);

}, [AuthMiddleware::class]);
