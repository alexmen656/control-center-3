<?php

require_once __DIR__ . '/../controllers/AuthController.php';

$router->group('/v2/auth', function ($router) {

    $router->post('/login', [AuthController::class, 'login']);
    $router->post('/verify-token', [AuthController::class, 'verifyToken']);
    $router->post('/sign-up', [AuthController::class, 'signUp']);
    $router->post('/verify-email', [AuthController::class, 'verifyEmail']);
    $router->post('/mcp-login', [AuthController::class, 'mcpLogin']);

});
