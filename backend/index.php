<?php
/**
 * Router Entry Point
 */

// Core dependencies
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db_connection.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/jwt_helper.php';

// Router framework
require_once __DIR__ . '/router/Request.php';
require_once __DIR__ . '/router/Response.php';
require_once __DIR__ . '/router/Middleware.php';
require_once __DIR__ . '/router/Router.php';

$router = new Router();
require_once __DIR__ . '/routes/index.php';

$router->dispatch();
