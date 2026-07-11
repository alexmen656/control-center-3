<?php

require_once __DIR__ . '/../controllers/ApisController.php';

$router->group('/v2/apis', function ($router) {

    // GET /v2/apis/available
    $router->get('/available', [ApisController::class, 'getAvailable']);

    // GET /v2/apis/project?project=...
    $router->get('/project', [ApisController::class, 'getProjectApis']);

    // GET /v2/apis/by-id/{id}
    $router->get('/by-id/{id}', [ApisController::class, 'getDetailsById']);

    // POST /v2/apis/subscribe
    $router->post('/subscribe', [ApisController::class, 'subscribe']);

    // DELETE /v2/apis/subscriptions/{id}
    $router->delete('/subscriptions/{id}', [ApisController::class, 'unsubscribe']);

    // PUT /v2/apis/subscriptions/{id}
    $router->put('/subscriptions/{id}', [ApisController::class, 'updateSubscription']);

    // PUT /v2/apis/subscriptions/{id}/settings
    $router->put('/subscriptions/{id}/settings', [ApisController::class, 'updateSettings']);

    // GET /v2/apis/subscriptions/{id}/usage?days=30
    $router->get('/subscriptions/{id}/usage', [ApisController::class, 'getUsage']);

    $router->get('/subscriptions/{id}/logs', [ApisController::class, 'getLogs']);

    // POST /v2/apis/subscriptions/{id}/regenerate-key
    $router->post('/subscriptions/{id}/regenerate-key', [ApisController::class, 'regenerateKey']);

    // GET /v2/apis/{slug}?project=... (muss als letztes stehen wegen {slug} Catch-All)
    $router->get('/{slug}', [ApisController::class, 'getDetailsBySlug']);

}, [AuthMiddleware::class]);
