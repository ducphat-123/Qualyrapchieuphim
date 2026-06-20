<?php
session_start();
if (empty($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$role = $_SESSION['user_role'] ?? 'user';

if ($role === 'admin' || $role === 'admin_monitor') {
    header('Location: admin/index.php');
    exit;
} elseif ($role === 'staff') {
    header('Location: staff.php');
    exit;
} else {
    header('Location: home.php');
    exit;
}
