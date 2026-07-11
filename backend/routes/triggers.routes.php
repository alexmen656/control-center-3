<?php

require_once __DIR__ . '/../controllers/TriggersController.php';

$router->group('/v2/triggers', function ($router) {

    $router->post('/create', [TriggersController::class, 'create']);
    $router->post('/list', [TriggersController::class, 'list']);
    $router->post('/delete', [TriggersController::class, 'delete']);
    $router->post('/toggle', [TriggersController::class, 'toggle']);
    $router->post('/export-csv', [TriggersController::class, 'exportCsv']);

}, [AuthMiddleware::class]);
