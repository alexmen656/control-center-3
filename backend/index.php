<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Authorization, Content-Type, Accept, Origin, X-Requested-With');
header('Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS');
header('Access-Control-Max-Age: 86400');

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/./helpers/db_connection.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/helpers/jwt.php';
require_once __DIR__ . '/router/Request.php';
require_once __DIR__ . '/router/Response.php';
require_once __DIR__ . '/router/Middleware.php';
require_once __DIR__ . '/router/Router.php';

$router = new Router();
require_once __DIR__ . '/routes/index.php';

$router->dispatch();
