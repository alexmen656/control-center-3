<?php

require_once __DIR__ . '/../controllers/SecureFileController.php';

$router->group('/v2/secure-file', function ($router) {

    $router->get('', [SecureFileController::class, 'serve']);

});
