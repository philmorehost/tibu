<?php
/**
 * Jiji-Inspired-1.0 Admin Authentication
 */

if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/functions.php';

function is_admin_logged_in() {
    return isset($_SESSION['admin_id']);
}

function require_admin() {
    if (!is_admin_logged_in()) {
        header('Location: /admin/login.php');
        exit;
    }
}

function admin_logout() {
    unset($_SESSION['admin_id']);
    unset($_SESSION['admin_user']);
    session_destroy();
    header('Location: /admin/login.php');
    exit;
}
