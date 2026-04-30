<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../inc/functions.php';

if (isset($_GET['ref']) && isset($_GET['ad_id']) && isset($_GET['method'])) {
    $ref = $_GET['ref'];
    $ad_id = (int)$_GET['ad_id'];
    $method = $_GET['method'];
    $tier = $_GET['tier'] ?? 'premium';
    if (!in_array($tier, ['premium', 'vip', 'diamond'])) $tier = 'premium';

    // In a real application, we would call Paystack/Flutterwave API to verify the reference

    // Fetch dynamic price for the selected tier
    $price_key = $tier . '_ad_price';
    $stmt = $pdo->prepare("SELECT setting_value FROM settings WHERE setting_key = ?");
    $stmt->execute([$price_key]);
    $price = (float)($stmt->fetchColumn() ?: ($tier === 'premium' ? 2000 : 0));

    if (!$price) {
        // Fallback for renamed boost_price
        $stmt = $pdo->query("SELECT setting_value FROM settings WHERE setting_key = 'boost_price'");
        $price = (float)($stmt->fetchColumn() ?: 2000);
    }

    // For this clone, we simulate successful verification
    $stmt = $pdo->prepare("INSERT INTO payments (ad_id, reference, method, amount, status) VALUES (?, ?, ?, ?, 'successful')");
    $stmt->execute([$ad_id, $ref, $method, $price]);

    // Fetch duration for the selected tier
    $duration_key = $tier . '_ad_duration';
    $stmt = $pdo->prepare("SELECT setting_value FROM settings WHERE setting_key = ?");
    $stmt->execute([$duration_key]);
    $duration = (int)($stmt->fetchColumn() ?: 30);
    $new_expiry = date('Y-m-d H:i:s', strtotime("+$duration days"));

    // Boost the ad, extend expiry, set tier, and bump to top
    $stmt = $pdo->prepare("UPDATE ads SET ad_tier = ?, is_featured = 1, status = 'active', expires_at = ?, bumped_at = CURRENT_TIMESTAMP WHERE id = ?");
    $stmt->execute([$tier, $new_expiry, $ad_id]);

    redirect('../profile.php', 'Payment successful! Your ad is now ' . ucfirst($tier) . '.');
} else {
    redirect('../index.php');
}
