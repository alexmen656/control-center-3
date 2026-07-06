<?php

require_once __DIR__ . '/../controllers/NewsletterController.php';

$router->group('/v2/newsletter', function ($router) {

    $router->get('/stats', [NewsletterController::class, 'getStats']);
    $router->get('/recent', [NewsletterController::class, 'getRecent']);
    $router->get('/performance', [NewsletterController::class, 'getPerformance']);
    $router->get('/settings', [NewsletterController::class, 'getSettings']);
    $router->get('/smtp', [NewsletterController::class, 'getSmtp']);

    $router->post('/send', [NewsletterController::class, 'send']);
    $router->post('/smtp/test', [NewsletterController::class, 'testSmtp']);

    $router->put('/settings', [NewsletterController::class, 'saveSettings']);
    $router->put('/smtp', [NewsletterController::class, 'saveSmtp']);

    $router->delete('/{id}', [NewsletterController::class, 'delete']);

}, [AuthMiddleware::class]);
