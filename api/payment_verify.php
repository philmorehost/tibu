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

    // Fetch dynamic price & duration from packages table (Source of Truth)
    $stmt = $pdo->prepare("SELECT price, duration_days FROM packages WHERE tier = ?");
    $stmt->execute([$tier]);
    $pkg = $stmt->fetch();

    $price = (float)($pkg['price'] ?? 0);
    $duration = (int)($pkg['duration_days'] ?? 30);

    // Get user_id from ad
    $stmt_uid = $pdo->prepare("SELECT user_id FROM ads WHERE id = ?");
    $stmt_uid->execute([$ad_id]);
    $uid = $stmt_uid->fetchColumn();

    // For this clone, we simulate successful verification
    $stmt = $pdo->prepare("INSERT INTO payments (ad_id, user_id, reference, method, amount, status, ad_tier) VALUES (?, ?, ?, ?, ?, 'successful', ?)");
    $stmt->execute([$ad_id, $uid, $ref, $method, $price, $tier]);

    // Handle Cashback
    $cashback = (float)($pkg['cashback'] ?? 0);
    if ($cashback > 0 && $uid) {
        $pdo->prepare("UPDATE users SET cashback_balance = cashback_balance + ? WHERE id = ?")->execute([$cashback, $uid]);
    }

    $new_expiry = date('Y-m-d H:i:s', strtotime("+$duration days"));

    // Boost the ad, extend expiry, set tier, and bump to top
    $stmt = $pdo->prepare("UPDATE ads SET ad_tier = ?, is_featured = 1, status = 'active', expires_at = ?, bumped_at = CURRENT_TIMESTAMP WHERE id = ?");
    $stmt->execute([$tier, $new_expiry, $ad_id]);

    redirect('../profile.php', 'Payment successful! Your ad is now ' . ucfirst($tier) . '.');
} else {
    redirect('../index.php');
}
