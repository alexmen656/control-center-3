<?php

require_once __DIR__ . '/../controllers/RuntimeLogsController.php';

$router->group('/v2/runtime-logs', function ($router) {

    $router->get('', [RuntimeLogsController::class, 'get']);

}, [AuthMiddleware::class]);
