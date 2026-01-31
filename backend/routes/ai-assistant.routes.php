<?php

require_once __DIR__ . '/../controllers/AiAssistantController.php';

$router->group('/v2/ai-assistant', function ($router) {

    // POST /v2/ai-assistant
    $router->post('/', [AiAssistantController::class, 'processQuestion']);

}, [AuthMiddleware::class]);
