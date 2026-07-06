<?php

require_once __DIR__ . '/../controllers/ProjectTemplatesController.php';

$router->group('/v2/project-templates', function ($router) {

    $router->get('/', [ProjectTemplatesController::class, 'list']);

    $router->post('/', [ProjectTemplatesController::class, 'create']);

    $router->post('/apply', [ProjectTemplatesController::class, 'apply']);

    $router->get('/{id}', [ProjectTemplatesController::class, 'get']);

    $router->put('/{id}', [ProjectTemplatesController::class, 'update']);

    $router->delete('/{id}', [ProjectTemplatesController::class, 'delete']);

}, [AuthMiddleware::class]);
