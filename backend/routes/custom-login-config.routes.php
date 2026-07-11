<?php

require_once __DIR__ . '/../controllers/CustomLoginConfigController.php';

$router->group('/v2/custom-login-config', function ($router) {

    $router->get('', [CustomLoginConfigController::class, 'get']);

});
