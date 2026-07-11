<?php

require_once __DIR__ . '/../controllers/ComponentsController.php';

$router->group('/v2/components', function ($router) {

    $router->post('/by-project', [ComponentsController::class, 'getComponentsByProject']);
    $router->post('/get', [ComponentsController::class, 'getComponent']);
    $router->post('/delete', [ComponentsController::class, 'deleteComponent']);
    $router->post('/update-html', [ComponentsController::class, 'updateHTML']);
    $router->post('/new', [ComponentsController::class, 'newComponent']);

}, [AuthMiddleware::class]);
