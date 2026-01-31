<?php

require_once __DIR__ . '/../controllers/AccessLogsController.php';

$router->group('/v2/access-logs', function ($router) {

    // GET /v2/access-logs
    $router->get('/', [AccessLogsController::class, 'getAllLogs']);

    // GET /v2/access-logs/stats
    $router->get('/stats', [AccessLogsController::class, 'getStats']);

    // GET /v2/access-logs/chart
    $router->get('/chart', [AccessLogsController::class, 'getChartData']);

    // GET /v2/access-logs/top-failed
    $router->get('/top-failed', [AccessLogsController::class, 'getTopFailedAttempts']);

    // GET /v2/access-logs/top-ips
    $router->get('/top-ips', [AccessLogsController::class, 'getTopIPs']);

}, [AuthMiddleware::class]);
