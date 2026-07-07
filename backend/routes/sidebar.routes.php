<?php

require_once __DIR__ . '/../controllers/SidebarController.php';

$router->group('/v2/sidebar', function ($router) {

    $router->get('/sections', [SidebarController::class, 'listSections']);
    $router->get('/section-templates', [SidebarController::class, 'templates']);

    $router->post('/sections', [SidebarController::class, 'createSection']);
    $router->post('/sections/reorder', [SidebarController::class, 'reorderSections']);
    $router->post('/sections/default', [SidebarController::class, 'createDefaultSections']);
    $router->post('/sections/{id}/items/reorder', [SidebarController::class, 'reorderSectionItems']);
    $router->post('/sections/{id}/tools/reorder', [SidebarController::class, 'reorderTools']);
    $router->put('/sections/{id}', [SidebarController::class, 'updateSection']);
    $router->delete('/sections/{id}', [SidebarController::class, 'deleteSection']);

    $router->post('/assign-tool', [SidebarController::class, 'assignToolToSection']);
    $router->post('/assign-table', [SidebarController::class, 'assignTableToSection']);
    $router->put('/tables/{formId}', [SidebarController::class, 'updateFormSidebar']);

    $router->post('/tools/update', [SidebarController::class, 'updateTool']);

}, [AuthMiddleware::class]);
