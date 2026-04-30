<?php
/**
 * Jiji-Inspired-1.0 Social Login Handler (OAuth)
 */

if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/inc/functions.php';

$provider = $_GET['provider'] ?? '';
if (!in_array($provider, ['google', 'facebook'])) {
    redirect('login.php', 'Invalid provider selected.', 'error');
}

// Get settings
$stmt = $pdo->prepare("SELECT setting_key, setting_value FROM settings WHERE setting_key IN (?, ?)");
$stmt->execute([$provider . '_login_active', 'site_name']);
$settings = [];
while ($row = $stmt->fetch()) {
    $settings[$row['setting_key']] = $row['setting_value'];
}

if (($settings[$provider . '_login_active'] ?? '0') !== '1') {
    redirect('login.php', ucfirst($provider) . " login is currently disabled.", 'error');
}

// Indicate that real OAuth logic should be implemented.
redirect('login.php', "OAuth logic for " . ucfirst($provider) . " must be implemented using official SDKs.", 'error');
