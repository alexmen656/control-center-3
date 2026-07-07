<?php

require_once __DIR__ . '/../controllers/ProjectsController.php';

$router->group('/v2/projects', function ($router) {

    // GET /v2/projects
    $router->get('/', [ProjectsController::class, 'getUserProjects']);

    // GET /v2/projects/all
    $router->get('/all', [ProjectsController::class, 'getAll']);

    // GET /v2/projects/import
    $router->get('/import', [ProjectsController::class, 'getForImport']);

    // POST /v2/projects
    $router->post('/', [ProjectsController::class, 'create']);

    // PUT /v2/projects/{id}
    $router->put('/{id}', [ProjectsController::class, 'update']);

    // DELETE /v2/projects/{id}
    $router->delete('/{id}', [ProjectsController::class, 'delete']);

    // PUT /v2/projects/{id}/visibility
    $router->put('/{id}/visibility', [ProjectsController::class, 'toggleVisibility']);

    // GET /v2/projects/{link}/info
    $router->get('/{link}/info', [ProjectsController::class, 'getInfo']);

    // GET /v2/projects/{link}/users
    $router->get('/{link}/users', [ProjectsController::class, 'getUsers']);

    // GET /v2/projects/{link}/views
    $router->get('/{link}/views', [ProjectsController::class, 'getViews']);

    // GET /v2/projects/{link}/permissions
    $router->get('/{link}/permissions', [ProjectsController::class, 'checkPermissions']);

    // POST /v2/projects/{link}/users
    $router->post('/{link}/users', [ProjectsController::class, 'addUser']);

    // GET /v2/projects/{link}
    $router->get('/{link}', [ProjectsController::class, 'getByLink']);

}, [AuthMiddleware::class]);
