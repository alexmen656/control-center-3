<?php

require_once __DIR__ . '/../controllers/DeploymentsController.php';

$router->group('/v2/deployments', function ($router) {

    $router->get('/', [DeploymentsController::class, 'listAll']);

}, [AuthMiddleware::class]);
