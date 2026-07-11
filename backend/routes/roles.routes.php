<?php

require_once __DIR__ . '/../controllers/RolesController.php';

$router->group('/v2/roles', function ($router) {

    $router->get('/', [RolesController::class, 'getAllRoles']);
    $router->get('/users', [RolesController::class, 'getUsersWithRoles']);
    $router->get('/me', [RolesController::class, 'getUserRole']);
    $router->get('/{id}', [RolesController::class, 'getRole']);

    $router->post('/', [RolesController::class, 'createRole']);
    $router->post('/check-permission', [RolesController::class, 'checkPermission']);
    $router->post('/assign', [RolesController::class, 'assignRole']);

    $router->put('/{id}', [RolesController::class, 'updateRole']);
    $router->delete('/{id}', [RolesController::class, 'deleteRole']);

}, [AuthMiddleware::class]);
