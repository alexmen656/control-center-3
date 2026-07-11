<?php

require_once __DIR__ . '/../controllers/ProjectDomainController.php';

$router->group('/v2/project-domain', function ($router) {

    $router->post('/connect', [ProjectDomainController::class, 'connect']);
    $router->post('/get', [ProjectDomainController::class, 'get']);
    $router->post('/delete', [ProjectDomainController::class, 'delete']);

}, [AuthMiddleware::class]);
