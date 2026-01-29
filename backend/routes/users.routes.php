<?php

require_once __DIR__ . '/../controllers/UsersController.php';

$router->group('/v2/users', function ($router) {

    // GET /v2/users
    $router->get('/', [UsersController::class, 'getAll']);

    // GET /v2/users/assignments
    $router->get('/assignments', [UsersController::class, 'getAssignments']);

    // POST /v2/users
    $router->post('/', [UsersController::class, 'create']);

    // PUT /v2/users/{id}
    $router->put('/{id}', [UsersController::class, 'update']);

    // DELETE /v2/users/{id}
    $router->delete('/{id}', [UsersController::class, 'delete']);

    // PUT /v2/users/{id}/deactivate
    $router->put('/{id}/deactivate', [UsersController::class, 'deactivate']);

    // PUT /v2/users/{id}/status
    $router->put('/{id}/status', [UsersController::class, 'updateStatus']);

    // POST /v2/users/{id}/project
    $router->post('/{id}/project', [UsersController::class, 'assignProject']);

}, [AuthMiddleware::class]);
