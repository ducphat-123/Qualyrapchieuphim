<?php

/**
 * Web Routes
 *
 * Handles page-level dispatching (redirects, role-based routing).
 * Dispatched via /be/web.php.
 *
 * These routes return redirects (header Location), not JSON.
 */

// ── INDEX / ROLE DISPATCH ──────────────────────────────────────────────────
//
// Called by fe/pages/index.php after session check.
// Redirects user to the correct page based on role.

$router->get('dispatch', function () {
    if (empty($_SESSION['user_id'])) {
        header('Location: ../fe/pages/login.php');
        exit;
    }

    $role = $_SESSION['user_role'] ?? 'user';

    match (true) {
        in_array($role, ['admin', 'admin_monitor'], true) => header('Location: ../fe/admin/index.php'),
        $role === 'staff'                                  => header('Location: ../fe/pages/staff.php'),
        default                                            => header('Location: ../fe/pages/home.php'),
    };

    exit;
});