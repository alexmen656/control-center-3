<?php

require_once __DIR__ . '/../controllers/PushTokenController.php';

$router->group('/v2/push-token', function ($router) {

    $router->post('/register', [PushTokenController::class, 'register']);

}, [AuthMiddleware::class]);
