<?php

require_once __DIR__ . '/../controllers/AiDashboardController.php';

$router->group('/v2/ai-dashboard', function ($router) {

    $router->post('/generate', [AiDashboardController::class, 'generate']);
    $router->post('/create', [AiDashboardController::class, 'create']);

}, [AuthMiddleware::class]);
