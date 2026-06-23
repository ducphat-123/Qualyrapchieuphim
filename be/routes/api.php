<?php

/**
 * API Routes
 *
 * All routes defined here are dispatched via /be/api.php.
 * $router and $pdo are available from the entry point.
 *
 * Middleware conventions:
 *   AuthMiddleware::api()   — requires login, returns 401 JSON if not
 *   AdminMiddleware::api()  — requires admin role, returns 403 JSON if not
 */

require_once BASE_PATH . '/be/models/User.php';
require_once BASE_PATH . '/be/services/AuthService.php';
require_once BASE_PATH . '/be/services/UserService.php';
require_once BASE_PATH . '/be/controllers/AuthController.php';
require_once BASE_PATH . '/be/controllers/UserController.php';

$authController = new AuthController($pdo);
$userController = new UserController($pdo);

// ── AUTH (public) ──────────────────────────────────────────────────────────

$router->post('login',    [$authController, 'login']);
$router->post('register', [$authController, 'register']);
$router->post('logout',   [$authController, 'logout']);

// ── FORGOT PASSWORD (public, session-tracked) ──────────────────────────────

$router->post('send_otp',   [$authController, 'sendOtp']);
$router->post('verify_otp', [$authController, 'verifyOtp']);
$router->post('reset_pwd',  [$authController, 'resetPwd']);

// ── USER (requires login) ──────────────────────────────────────────────────

$auth = [AuthMiddleware::class, 'api'];

$router->post('profile',              [$userController, 'profile'],            [$auth]);
$router->post('update_profile',       [$userController, 'updateProfile'],      [$auth]);
$router->post('change_password',      [$userController, 'changePassword'],     [$auth]);
$router->post('my_tickets',           [$userController, 'myTickets'],          [$auth]);
$router->post('cancel_booking',       [$userController, 'cancelBooking'],      [$auth]);
$router->post('submit_review',        [$userController, 'submitReview'],       [$auth]);
$router->post('my_vouchers',          [$userController, 'myVouchers'],         [$auth]);
$router->post('submit_support_ticket',[$userController, 'submitSupportTicket'],[$auth]);