<?php

/**
 * API Entry Point
 *
 * All AJAX/fetch requests from the frontend are sent here.
 * URL: /be/api.php
 *
 * The request must include an 'action' field (POST body or GET param).
 * Routes are defined in be/routes/api.php.
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

define('BASE_PATH', dirname(__DIR__));

require_once BASE_PATH . '/be/config/db.php';
require_once BASE_PATH . '/be/core/Router.php';
require_once BASE_PATH . '/be/core/Request.php';
require_once BASE_PATH . '/be/core/Response.php';
require_once BASE_PATH . '/be/core/Logger.php';
require_once BASE_PATH . '/be/middleware/CorsMiddleware.php';
require_once BASE_PATH . '/be/middleware/AuthMiddleware.php';
require_once BASE_PATH . '/be/middleware/AdminMiddleware.php';

CorsMiddleware::apply();

header('Content-Type: application/json; charset=utf-8');

$router = new Router();

require_once BASE_PATH . '/be/routes/api.php';

$router->dispatch();