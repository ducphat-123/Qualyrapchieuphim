<?php

require_once BASE_PATH . '/be/core/Response.php';
require_once BASE_PATH . '/be/core/Request.php';
require_once BASE_PATH . '/be/models/User.php';
require_once BASE_PATH . '/be/services/AuthService.php';

class AuthController
{
    private AuthService $service;
    private Request $request;

    public function __construct(PDO $pdo)
    {
        $this->service = new AuthService($pdo);
        $this->request = new Request();
    }

    public function login(): void
    {
        $identifier = trim($_POST['identifier'] ?? '');
        $password   = $_POST['password'] ?? '';
        $result     = $this->service->login($identifier, $password);
        Response::json($result);
    }

    public function register(): void
    {
        $result = $this->service->register($_POST);
        Response::json($result);
    }

    public function logout(): void
    {
        $result = $this->service->logout();
        Response::json($result);
    }

    public function sendOtp(): void
    {
        $email  = trim($_POST['email'] ?? '');
        $result = $this->service->sendPasswordResetOtp($email);

        if ($result['success']) {
            $_SESSION['pwd_step']  = 2;
            $_SESSION['pwd_email'] = $email;
        }

        Response::json($result);
    }

    public function verifyOtp(): void
    {
        $email  = $_SESSION['pwd_email'] ?? '';
        $otp    = trim($_POST['otp'] ?? '');
        $result = $this->service->verifyPasswordResetOtp($email, $otp);

        if ($result['success']) {
            $_SESSION['pwd_step'] = 3;
            $_SESSION['pwd_otp']  = $otp;
        }

        Response::json($result);
    }

    public function resetPwd(): void
    {
        $email   = $_SESSION['pwd_email'] ?? '';
        $otp     = $_SESSION['pwd_otp']   ?? '';
        $newPwd  = $_POST['new_pwd']      ?? '';
        $confPwd = $_POST['confirm_pwd']  ?? '';
        $result  = $this->service->resetPassword($email, $otp, $newPwd, $confPwd);

        if ($result['success']) {
            unset($_SESSION['pwd_step'], $_SESSION['pwd_email'], $_SESSION['pwd_otp']);
        }

        Response::json($result);
    }
}