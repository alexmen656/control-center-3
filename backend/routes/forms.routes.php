<?php

require_once __DIR__ . '/../controllers/FormsController.php';

$router->group('/v2/forms', function ($router) {

    // GET /v2/forms/single?form=...&project=...
    $router->get('/single', [FormsController::class, 'get']);

    // GET /v2/forms/data?form=...&project=...
    $router->get('/data', [FormsController::class, 'getData']);

    // GET /v2/forms/list?project=...
    $router->get('/list', [FormsController::class, 'list']);

    // GET /v2/forms/exists?form_name=...&project=...
    $router->get('/exists', [FormsController::class, 'exists']);

    // GET /v2/forms/schema?form_name=...&project=...
    $router->get('/schema', [FormsController::class, 'getSchema']);

    // GET /v2/forms/entry/{id}?form_name=...&project=...
    $router->get('/entry/{id}', [FormsController::class, 'getEntry']);

    // GET /v2/forms/tables?project=...
    $router->get('/tables', [FormsController::class, 'getTables']);

    // GET /v2/forms/tables-from-project?source_project=...
    $router->get('/tables-from-project', [FormsController::class, 'getTablesFromProject']);

    // POST /v2/forms
    $router->post('/', [FormsController::class, 'create']);

    // POST /v2/forms/submit
    $router->post('/submit', [FormsController::class, 'submit']);

    // POST /v2/forms/rename
    $router->post('/rename', [FormsController::class, 'rename']);

    // POST /v2/forms/import
    $router->post('/import', [FormsController::class, 'importTable']);

    // PUT /v2/forms/entry/{id}
    $router->put('/entry/{id}', [FormsController::class, 'updateEntry']);

    // PUT /v2/forms/structure
    $router->put('/structure', [FormsController::class, 'updateStructure']);

    // DELETE /v2/forms/entry/{id}
    $router->delete('/entry/{id}', [FormsController::class, 'deleteEntry']);

    // DELETE /v2/forms/table
    $router->delete('/table', [FormsController::class, 'dropTable']);

    $router->get('/info', [FormsController::class, 'getInfo']);

    // GET /v2/forms/export/csv
    $router->get('/export/csv', [FormsController::class, 'exportCSV']);

    // GET /v2/forms/export/excel
    $router->get('/export/excel', [FormsController::class, 'exportExcel']);

}, [AuthMiddleware::class]);
