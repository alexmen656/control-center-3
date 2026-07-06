<?php

require_once __DIR__ . '/../controllers/MarketingController.php';

$router->group('/v2/marketing', function ($router) {

    $router->get('/campaigns', [MarketingController::class, 'list']);
    $router->get('/campaigns/stats', [MarketingController::class, 'stats']);
    $router->get('/campaigns/{id}', [MarketingController::class, 'get']);

    $router->post('/campaigns', [MarketingController::class, 'create']);
    $router->post('/campaigns/import', [MarketingController::class, 'importCsv']);
    $router->post('/campaigns/{id}/duplicate', [MarketingController::class, 'duplicate']);

    $router->put('/campaigns/{id}/status', [MarketingController::class, 'updateStatus']);
    $router->put('/campaigns/{id}', [MarketingController::class, 'update']);

    $router->delete('/campaigns/{id}', [MarketingController::class, 'delete']);

}, [AuthMiddleware::class]);
