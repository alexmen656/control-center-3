<?php

require_once __DIR__ . '/../controllers/AiSchemaGeneratorController.php';

$router->group('/v2/ai-schema', function ($router) {

    // POST /v2/ai-schema/generate
    $router->post('/generate', [AiSchemaGeneratorController::class, 'generate']);

    // POST /v2/ai-schema/create-form
    $router->post('/create-form', [AiSchemaGeneratorController::class, 'createForm']);

}, [AuthMiddleware::class]);
