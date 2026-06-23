<?php

/**
 * Web Entry Point
 *
 * Handles page-level routing (redirects after login, role dispatch, etc.).
 * URL: /be/web.php
 *
 * Routes are defined in be/routes/web.php.
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

define('BASE_PATH', dirname(__DIR__));

require_once BASE_PATH . '/be/config/db.php';
require_once BASE_PATH . '/be/core/Router.php';
require_once BASE_PATH . '/be/core/Request.php';
require_once BASE_PATH . '/be/core/Response.php';
require_once BASE_PATH . '/be/middleware/AuthMiddleware.php';
require_once BASE_PATH . '/be/middleware/AdminMiddleware.php';

$router = new Router();

require_once BASE_PATH . '/be/routes/web.php';

$router->dispatch();