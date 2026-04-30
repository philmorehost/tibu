<?php
/**
 * Jiji-Inspired-1.0 User Authentication Logic
 */

if (session_status() === PHP_SESSION_NONE) session_start();

function is_user_logged_in() {
    return isset($_SESSION['user_id']);
}

function require_user() {
    if (!is_user_logged_in()) {
        header('Location: /login.php');
        exit;
    }
}

function user_logout() {
    unset($_SESSION['user_id']);
    unset($_SESSION['user_name']);
    session_destroy();
    header('Location: /index.php');
    exit;
}

/**
 * Send OTP via Email
 */
function send_otp($email, $otp) {
    global $pdo;
    $site_name = $pdo->query("SELECT setting_value FROM settings WHERE setting_key = 'site_name'")->fetchColumn() ?: 'Classifieds';

    $subject = "Your Registration OTP - $site_name";
    $body = "
        <div style='font-family: Arial, sans-serif; max-width: 600px; margin: auto; border: 1px solid #eee; padding: 20px; border-radius: 10px;'>
            <h2 style='color: #1A7FE8; text-align: center;'>Welcome to $site_name</h2>
            <p>Thank you for signing up. Please use the following One-Time Password (OTP) to verify your account:</p>
            <div style='background: #f0fdf4; padding: 20px; text-align: center; border-radius: 10px; margin: 20px 0;'>
                <span style='font-size: 32px; font-weight: bold; letter-spacing: 5px; color: #0966ce;'>$otp</span>
            </div>
            <p style='color: #666; font-size: 12px; text-align: center;'>This code will expire in 10 minutes. If you did not request this code, please ignore this email.</p>
        </div>
    ";

    // Using the previously defined send_email function
    require_once __DIR__ . '/email.php';
    return send_email($email, $subject, $body);
}
