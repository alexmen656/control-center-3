<?php

require_once __DIR__ . '/../controllers/TablesController.php';

$router->group('/v2/tables', function ($router) {

    // GET /v2/tables/single?form=...&project=...
    $router->get('/single', [TablesController::class, 'get']);

    // GET /v2/tables/data?form=...&project=...
    $router->get('/data', [TablesController::class, 'getData']);

    // GET /v2/tables/list?project=...
    $router->get('/list', [TablesController::class, 'list']);

    // GET /v2/tables/exists?table_name=...&project=...
    $router->get('/exists', [TablesController::class, 'exists']);

    // GET /v2/tables/schema?table_name=...&project=...
    $router->get('/schema', [TablesController::class, 'getSchema']);

    // GET /v2/tables/entry/{id}?table_name=...&project=...
    $router->get('/entry/{id}', [TablesController::class, 'getEntry']);

    // GET /v2/tables/tables?project=...
    $router->get('/tables', [TablesController::class, 'getTables']);

    // GET /v2/tables/tables-from-project?source_project=...
    $router->get('/tables-from-project', [TablesController::class, 'getTablesFromProject']);

    // POST /v2/tables
    $router->post('/', [TablesController::class, 'create']);

    // POST /v2/tables/submit
    $router->post('/submit', [TablesController::class, 'submit']);

    // POST /v2/tables/rename
    $router->post('/rename', [TablesController::class, 'rename']);

    // POST /v2/tables/import
    $router->post('/import', [TablesController::class, 'importTable']);

    // PUT /v2/tables/entry/{id}
    $router->put('/entry/{id}', [TablesController::class, 'updateEntry']);

    // PUT /v2/tables/structure
    $router->put('/structure', [TablesController::class, 'updateStructure']);

    // DELETE /v2/tables/entry/{id}
    $router->delete('/entry/{id}', [TablesController::class, 'deleteEntry']);

    // DELETE /v2/tables/table
    $router->delete('/table', [TablesController::class, 'dropTable']);

    $router->get('/info', [TablesController::class, 'getInfo']);

    // GET /v2/tables/export/csv
    $router->get('/export/csv', [TablesController::class, 'exportCSV']);

    // GET /v2/tables/export/excel
    $router->get('/export/excel', [TablesController::class, 'exportExcel']);

}, [AuthMiddleware::class]);
