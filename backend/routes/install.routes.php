<?php

require_once __DIR__ . '/../controllers/InstallController.php';

$router->group('/v2/install', function ($router) {

    $router->post('/list', [InstallController::class, 'listModules']);
    $router->post('/install', [InstallController::class, 'install']);
    $router->post('/uninstall', [InstallController::class, 'uninstall']);
    $router->post('/modules/create', [InstallController::class, 'createModule']);
    $router->post('/modules/update', [InstallController::class, 'updateModule']);
    $router->post('/modules/delete', [InstallController::class, 'deleteModule']);

}, [AuthMiddleware::class]);
