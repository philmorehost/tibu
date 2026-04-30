<?php
/**
 * Jiji-Inspired-1.0 Security Middleware
 * Application-level blocking & Brute Force protection
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/functions.php';

// CRITICAL: Prevent fatal error if $pdo is not defined or connection failed
if (!isset($pdo) || !($pdo instanceof PDO)) {
    // If we're here and $pdo is missing, we can't do DB-based security checks.
    // We should probably allow execution to continue so the user can see
    // the "Database connection failed" message from config.php,
    // OR die here if we want to be strict.
    return;
}

$ip = get_client_ip();

// 1. Check IP Blacklist
try {
    $stmt = $pdo->prepare("SELECT status FROM ip_security WHERE ip_address = ?");
    $stmt->execute([$ip]);
    $ip_status = $stmt->fetchColumn();

    if ($ip_status === 'blacklisted') {
        die("Access Denied: Your IP address ($ip) has been blacklisted.");
    }

    // 2. Automated Whitelisting (King Icon Logic)
    // If IP has 5+ successful logins, whitelist it
    $stmt = $pdo->prepare("SELECT COUNT(DISTINCT(DATE(attempted_at))) FROM login_logs WHERE ip_address = ? AND is_success = 1");
    $stmt->execute([$ip]);
    $sessions = $stmt->fetchColumn();
    if ($sessions >= 5 && $ip_status !== 'whitelisted') {
        $stmt = $pdo->prepare("INSERT INTO ip_security (ip_address, status, reason) VALUES (?, 'whitelisted', 'Auto-whitelisted after 5 successful sessions') ON DUPLICATE KEY UPDATE status = 'whitelisted'");
        $stmt->execute([$ip]);
    }
} catch (Exception $e) {
    // Fail gracefully if security tables don't exist yet
}

// 3. Brute Force Protection Logic
function check_brute_force($username, $is_admin = 0) {
    global $pdo;
    if (!$pdo) return ['blocked' => false];

    $ip = get_client_ip();

    // Get settings
    $settings = [];
    try {
        $stmt = $pdo->query("SELECT setting_key, setting_value FROM settings WHERE setting_key IN ('brute_force_period', 'max_failures_account', 'max_failures_ip')");
        while ($row = $stmt->fetch()) {
            $settings[$row['setting_key']] = $row['setting_value'];
        }
    } catch (Exception $e) {}

    $period = (int)($settings['brute_force_period'] ?? 15);
    $max_failures_account = (int)($settings['max_failures_account'] ?? 5);
    $max_failures_ip = (int)($settings['max_failures_ip'] ?? 10);

    // Check failures by account
    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM login_logs WHERE username = ? AND is_admin = ? AND is_success = 0 AND attempted_at > DATE_SUB(NOW(), INTERVAL ? MINUTE)");
        $stmt->execute([$username, $is_admin, $period]);
        $account_failures = $stmt->fetchColumn();

        if ($account_failures >= $max_failures_account) {
            if (!$is_admin) {
                $stmt = $pdo->prepare("UPDATE users SET is_suspended = 1 WHERE email = ?");
                $stmt->execute([$username]);
            }
            return ['blocked' => true, 'reason' => 'account_locked'];
        }

        // Check failures by IP
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM login_logs WHERE ip_address = ? AND is_success = 0 AND attempted_at > DATE_SUB(NOW(), INTERVAL ? MINUTE)");
        $stmt->execute([$ip, $period]);
        $ip_failures = $stmt->fetchColumn();

        if ($ip_failures >= $max_failures_ip) {
            $stmt = $pdo->prepare("INSERT INTO ip_security (ip_address, status, reason) VALUES (?, 'blacklisted', 'Brute force detection') ON DUPLICATE KEY UPDATE status = 'blacklisted'");
            $stmt->execute([$ip]);
            return ['blocked' => true, 'reason' => 'ip_blacklisted'];
        }
    } catch (Exception $e) {}

    return ['blocked' => false];
}

function log_login_attempt($username, $is_admin, $is_success) {
    global $pdo;
    if (!$pdo) return;
    $ip = get_client_ip();
    try {
        $stmt = $pdo->prepare("INSERT INTO login_logs (ip_address, username, is_admin, is_success) VALUES (?, ?, ?, ?)");
        $stmt->execute([$ip, $username, $is_admin, $is_success ? 1 : 0]);
    } catch (Exception $e) {}
}
