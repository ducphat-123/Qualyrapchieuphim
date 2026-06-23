<?php

/**
 * ForgotPasswordController
 *
 * Handles the 3-step password reset flow as a JSON API.
 * Called by fe/pages/forgot-password.php via fetch().
 *
 * POST /be/controllers/ForgotPasswordController.php
 *   action=send_otp    { email }
 *   action=verify_otp  { otp }           (email stored in session)
 *   action=reset_pwd   { new_pwd, confirm_pwd }  (email + otp in session)
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../core/Response.php';
require_once __DIR__ . '/../core/Request.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../services/AuthService.php';

header('Content-Type: application/json; charset=utf-8');

$request = new Request();
$service = new AuthService($pdo);
$action  = trim($_POST['action'] ?? '');

switch ($action) {

    case 'send_otp':
        $email  = trim($_POST['email'] ?? '');
        $result = $service->sendPasswordResetOtp($email);

        if ($result['success']) {
            $_SESSION['pwd_step']  = 2;
            $_SESSION['pwd_email'] = $email;
        }

        Response::json($result);
        break;

    case 'verify_otp':
        $email  = $_SESSION['pwd_email'] ?? '';
        $otp    = trim($_POST['otp'] ?? '');
        $result = $service->verifyPasswordResetOtp($email, $otp);

        if ($result['success']) {
            $_SESSION['pwd_step'] = 3;
            $_SESSION['pwd_otp']  = $otp;
        }

        Response::json($result);
        break;

    case 'reset_pwd':
        $email   = $_SESSION['pwd_email'] ?? '';
        $otp     = $_SESSION['pwd_otp']   ?? '';
        $newPwd  = $_POST['new_pwd']      ?? '';
        $confPwd = $_POST['confirm_pwd']  ?? '';
        $result  = $service->resetPassword($email, $otp, $newPwd, $confPwd);

        if ($result['success']) {
            unset($_SESSION['pwd_step'], $_SESSION['pwd_email'], $_SESSION['pwd_otp']);
        }

        Response::json($result);
        break;

    default:
        Response::error('Hành động không hợp lệ.', 400);
        break;
}