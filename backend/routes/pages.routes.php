<?php

require_once __DIR__ . '/../controllers/PagesController.php';

$router->group('/v2/pages', function ($router) {

    // GET /v2/pages/check?url=...
    $router->get('/check', [PagesController::class, 'check']);

}, [AuthMiddleware::class]);
