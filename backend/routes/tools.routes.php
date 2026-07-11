<?php

require_once __DIR__ . '/../controllers/ToolsController.php';

$router->group('/v2/tools', function ($router) {

    $router->post('/create', [ToolsController::class, 'create']);
    $router->post('/config', [ToolsController::class, 'saveConfig']);
    $router->get('/config', [ToolsController::class, 'getConfig']);
    $router->get('/project', [ToolsController::class, 'getProjectTools']);
    $router->delete('/{id}', [ToolsController::class, 'delete']);

}, [AuthMiddleware::class]);
