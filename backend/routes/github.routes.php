<?php

require_once __DIR__ . '/../controllers/GithubController.php';

$router->group('/v2/github', function ($router) {

    $router->get('/api', [GithubController::class, 'api']);
    $router->post('/api', [GithubController::class, 'api']);
    $router->get('/repos', [GithubController::class, 'repos']);
    $router->get('/token-info', [GithubController::class, 'tokenInfo']);
    $router->get('/token-status', [GithubController::class, 'tokenStatus']);

}, [AuthMiddleware::class]);
