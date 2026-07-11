<?php

require_once __DIR__ . '/../controllers/UsersController.php';

$router->group('/v2/users', function ($router) {

    // GET /v2/users
    $router->get('/', [UsersController::class, 'getAll']);

    // GET /v2/users/assignments
    $router->get('/assignments', [UsersController::class, 'getAssignments']);

    $router->get('/me', [UsersController::class, 'getMe']);

    $router->put('/me', [UsersController::class, 'updateMe']);

    $router->post('/me/profile-image', [UsersController::class, 'updateMyProfileImage']);

    $router->put('/me/login-with-google', [UsersController::class, 'updateMyLoginWithGoogle']);

    $router->put('/me/email-2fa', [UsersController::class, 'updateMyEmail2FA']);

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
