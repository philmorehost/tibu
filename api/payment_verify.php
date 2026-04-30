<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../inc/functions.php';

if (isset($_GET['ref']) && isset($_GET['ad_id']) && isset($_GET['method'])) {
    $ref = $_GET['ref'];
    $ad_id = (int)$_GET['ad_id'];
    $method = $_GET['method'];

    // In a real application, we would call Paystack/Flutterwave API to verify the reference
    // Fetch dynamic boost price
    $stmt = $pdo->query("SELECT setting_value FROM settings WHERE setting_key = 'boost_price'");
    $boost_price = (float)($stmt->fetchColumn() ?: 2000);

    // For this clone, we simulate successful verification

    $stmt = $pdo->prepare("INSERT INTO payments (ad_id, reference, method, amount, status) VALUES (?, ?, ?, ?, 'successful')");
    $stmt->execute([$ad_id, $ref, $method, $boost_price]);

    // Fetch premium duration
    $stmt = $pdo->query("SELECT setting_value FROM settings WHERE setting_key = 'premium_ad_duration'");
    $p_duration = (int)($stmt->fetchColumn() ?: 30);
    $new_expiry = date('Y-m-d H:i:s', strtotime("+$p_duration days"));

    // Boost the ad, extend expiry, and bump to top
    $stmt = $pdo->prepare("UPDATE ads SET is_featured = 1, status = 'active', expires_at = ?, bumped_at = CURRENT_TIMESTAMP WHERE id = ?");
    $stmt->execute([$new_expiry, $ad_id]);

    redirect('../profile.php', 'Payment successful! Your ad is now featured.');
} else {
    redirect('../index.php');
}
