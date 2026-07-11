<?php

require_once __DIR__ . '/../controllers/DashboardController.php';

$router->group('/v2/dashboard', function ($router) {

    $router->post('/create', [DashboardController::class, 'create']);
    $router->post('/get', [DashboardController::class, 'get']);
    $router->post('/charts', [DashboardController::class, 'addChart']);
    $router->post('/charts/update', [DashboardController::class, 'updateCharts']);
    $router->post('/charts/delete', [DashboardController::class, 'deleteChart']);

}, [AuthMiddleware::class]);
