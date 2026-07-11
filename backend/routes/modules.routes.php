<?php

require_once __DIR__ . '/../controllers/ModulesController.php';

$router->group('/v2/modules', function ($router) {

    $router->post('/list', [ModulesController::class, 'list']);

}, [AuthMiddleware::class]);
