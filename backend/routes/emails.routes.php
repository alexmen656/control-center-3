<?php

require_once __DIR__ . '/../controllers/EmailsController.php';

$router->group('/v2/emails', function ($router) {

    // GET /v2/emails
    $router->get('/', [EmailsController::class, 'list']);

    // GET /v2/emails/stats
    $router->get('/stats', [EmailsController::class, 'stats']);

    // GET /v2/emails/attachments/{id}
    $router->get('/attachments/{id}', [EmailsController::class, 'getAttachment']);

    // GET /v2/emails/{id}
    $router->get('/{id}', [EmailsController::class, 'get']);

    // POST /v2/emails/bulk
    $router->post('/bulk', [EmailsController::class, 'bulkAction']);

    // POST /v2/emails/import-raw
    $router->post('/import-raw', [EmailsController::class, 'importRaw']);

    // POST /v2/emails/test-webhook
    $router->post('/test-webhook', [EmailsController::class, 'testWebhook']);

    // POST /v2/emails/{id}/read
    $router->post('/{id}/read', [EmailsController::class, 'markRead']);

    // POST /v2/emails/{id}/starred
    $router->post('/{id}/starred', [EmailsController::class, 'markStarred']);

    // POST /v2/emails/{id}/move
    $router->post('/{id}/move', [EmailsController::class, 'move']);

    // DELETE /v2/emails/{id}
    $router->delete('/{id}', [EmailsController::class, 'delete']);

}, [AuthMiddleware::class]);
