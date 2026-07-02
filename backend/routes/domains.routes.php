<?php

require_once __DIR__ . '/../controllers/DomainsController.php';

$router->group('/v2/domains', function ($router) {

    // GET /v2/domains
    $router->get('/', [DomainsController::class, 'list']);

    // GET /v2/domains/expiring
    $router->get('/expiring', [DomainsController::class, 'expiring']);

    // GET /v2/domains/available
    $router->get('/available', [DomainsController::class, 'listAvailable']);

    // GET /v2/domains/{id}/subdomains
    $router->get('/{id}/subdomains', [DomainsController::class, 'subdomains']);

    // POST /v2/domains
    $router->post('/', [DomainsController::class, 'save']);

    // POST /v2/domains/fetch-cloudflare
    $router->post('/fetch-cloudflare', [DomainsController::class, 'fetchCloudflare']);

    // DELETE /v2/domains/{id}
    $router->delete('/{id}', [DomainsController::class, 'delete']);

}, [AuthMiddleware::class]);
