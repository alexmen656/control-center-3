<?php

require_once __DIR__ . '/../controllers/DeployLogsController.php';

$router->group('/v2/deploy-logs', function ($router) {

    $router->get('', [DeployLogsController::class, 'show']);

});
