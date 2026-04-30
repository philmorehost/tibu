<?php
/**
 * Jiji-Inspired-1.0 Email Helper
 */

require_once __DIR__ . '/../config/config.php';

function send_email($to, $subject, $body) {
    global $pdo;

    // Get SMTP settings
    $stmt = $pdo->query("SELECT setting_key, setting_value FROM settings WHERE setting_key LIKE 'smtp_%'");
    $settings = [];
    while ($row = $stmt->fetch()) {
        $settings[$row['setting_key']] = $row['setting_value'];
    }

    // For this implementation, we simulate sending via SMTP or mail()
    // In production, we'd use PHPMailer or SwiftMailer here.

    $headers = "MIME-Version: 1.0" . "\r\n";
    $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
    $headers .= 'From: <no-reply@classifieds.ng>' . "\r\n";

    // log email for debugging in this environment
    error_log("Email to: $to, Subject: $subject, Body: $body");

    // Since we don't have a real SMTP server, we just return true.
    return true;
}
