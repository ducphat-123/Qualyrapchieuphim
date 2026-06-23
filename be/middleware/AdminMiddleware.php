<?php

require_once __DIR__ . '/../core/Response.php';
require_once __DIR__ . '/AuthMiddleware.php';

class AdminMiddleware
{
    private const ADMIN_ROLES = ['admin', 'admin_monitor'];

    /**
     * For API routes: return 403 JSON if not an admin.
     */
    public static function api(): void
    {
        AuthMiddleware::api();

        if (!in_array($_SESSION['user_role'] ?? '', self::ADMIN_ROLES, true)) {
            Response::forbidden();
        }
    }

    /**
     * For page routes: redirect if not an admin.
     */
    public static function page(string $redirectUrl = '/fe/pages/home.php'): void
    {
        AuthMiddleware::page();

        if (!in_array($_SESSION['user_role'] ?? '', self::ADMIN_ROLES, true)) {
            header('Location: ' . $redirectUrl);
            exit;
        }
    }

    /**
     * Returns true only if the current user is a full admin (not monitor).
     */
    public static function isFullAdmin(): bool
    {
        return ($_SESSION['user_role'] ?? '') === 'admin';
    }
}