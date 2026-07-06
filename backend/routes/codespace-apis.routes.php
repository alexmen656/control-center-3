<?php

require_once __DIR__ . '/../controllers/CodespaceApisController.php';

$router->group('/v2/codespace-apis', function ($router) {

    $router->get('/', [CodespaceApisController::class, 'list']);
    $router->get('/details', [CodespaceApisController::class, 'details']);
    $router->post('/activate', [CodespaceApisController::class, 'activate']);
    $router->post('/deactivate', [CodespaceApisController::class, 'deactivate']);
    $router->post('/sync', [CodespaceApisController::class, 'sync']);
    $router->post('/publish', [CodespaceApisController::class, 'publish']);
    $router->post('/unpublish', [CodespaceApisController::class, 'unpublish']);

}, [AuthMiddleware::class]);
