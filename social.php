<?php
/**
 * Jiji-Inspired-1.0 Social Login Handler (OAuth)
 */

require_once __DIR__ . '/config/config.php';
if (session_status() === PHP_SESSION_NONE) session_start();

require_once __DIR__ . '/inc/functions.php';

$provider = $_GET['provider'] ?? '';
if (!in_array($provider, ['google', 'facebook'])) {
    redirect('login.php', 'Invalid provider selected.', 'error');
}

// Get settings
$stmt = $pdo->prepare("SELECT setting_key, setting_value FROM settings WHERE setting_key IN (?, ?, ?, ?)");
$stmt->execute([$provider . '_login_active', $provider . '_client_id', $provider . '_app_id', 'site_name']);
$settings = [];
while ($row = $stmt->fetch()) {
    $settings[$row['setting_key']] = $row['setting_value'];
}

if (($settings[$provider . '_login_active'] ?? '0') !== '1') {
    redirect('login.php', ucfirst($provider) . " login is currently disabled.", 'error');
}

$client_id = $settings[$provider . '_client_id'] ?? ($settings[$provider . '_app_id'] ?? '');
if (empty($client_id)) {
    redirect('login.php', ucfirst($provider) . " login is not fully configured in admin panel.", 'error');
}

// Indicate that real OAuth logic should be implemented.
redirect('login.php', "OAuth logic for " . ucfirst($provider) . " must be implemented using official SDKs.", 'error');

