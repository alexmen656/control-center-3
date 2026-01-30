<?php

require_once __DIR__ . '/../controllers/BookmarksController.php';

$router->group('/v2/bookmarks', function ($router) {

    // GET /v2/bookmarks
    $router->get('/', [BookmarksController::class, 'getAll']);

    // GET /v2/bookmarks/check?location=...
    $router->get('/check', [BookmarksController::class, 'check']);

    // POST /v2/bookmarks
    $router->post('/', [BookmarksController::class, 'create']);

    // DELETE /v2/bookmarks
    $router->delete('/', [BookmarksController::class, 'delete']);

}, [AuthMiddleware::class]);
