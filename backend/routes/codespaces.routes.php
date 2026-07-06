<?php

require_once __DIR__ . '/../controllers/CodespacesController.php';
require_once __DIR__ . '/../controllers/CodespaceDomainsController.php';

$router->group('/v2/codespaces', function ($router) {

    $router->get('/', [CodespacesController::class, 'list']);
    $router->post('/', [CodespacesController::class, 'create']);

    $router->get('/templates', [CodespacesController::class, 'templates']);
    $router->get('/user-projects', [CodespacesController::class, 'userProjects']);
    $router->post('/reorder', [CodespacesController::class, 'reorder']);

    $router->get('/{id}/domain-info', [CodespaceDomainsController::class, 'info']);
    $router->get('/{id}/domain', [CodespaceDomainsController::class, 'get']);
    $router->post('/{id}/domain', [CodespaceDomainsController::class, 'connect']);
    $router->delete('/{id}/domain', [CodespaceDomainsController::class, 'disconnect']);

    $router->post('/{id}/transfer', [CodespacesController::class, 'transfer']);

    $router->put('/{id}', [CodespacesController::class, 'update']);
    $router->delete('/{id}', [CodespacesController::class, 'delete']);

}, [AuthMiddleware::class]);
