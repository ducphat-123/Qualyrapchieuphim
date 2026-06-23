<?php

require_once __DIR__ . '/../core/Response.php';

class AuthMiddleware
{
    /**
     * For API routes: return 401 JSON if not authenticated.
     */
    public static function api(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (empty($_SESSION['user_id'])) {
            Response::unauthorized('Vui lòng đăng nhập để tiếp tục.');
        }
    }

    /**
     * For page routes: redirect to login if not authenticated.
     */
    public static function page(string $loginUrl = '/fe/pages/login.php'): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (empty($_SESSION['user_id'])) {
            header('Location: ' . $loginUrl);
            exit;
        }
    }

    /**
     * Check if current session belongs to a specific role.
     */
    public static function hasRole(string|array $roles): bool
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $userRole = $_SESSION['user_role'] ?? '';

        if (is_array($roles)) {
            return in_array($userRole, $roles, true);
        }

        return $userRole === $roles;
    }

    /**
     * Return current authenticated user ID or null.
     */
    public static function userId(): ?int
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        return isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : null;
    }
}