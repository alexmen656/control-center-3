<?php

require_once __DIR__ . '/../controllers/CustomLoginDomainsController.php';

$router->group('/v2/custom-login-domains', function ($router) {

    $router->post('/get', [CustomLoginDomainsController::class, 'get']);
    $router->post('/save', [CustomLoginDomainsController::class, 'save']);
    $router->post('/delete', [CustomLoginDomainsController::class, 'delete']);

}, [AuthMiddleware::class]);
