<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../inc/functions.php';
if (session_status() === PHP_SESSION_NONE) session_start();

if (isset($_GET['id']) && isset($_SESSION['user_id'])) {
    $ad_id = (int)$_GET['id'];
    $user_id = $_SESSION['user_id'];

    // Verify ownership and status
    $stmt = $pdo->prepare("SELECT id, is_featured FROM ads WHERE id = ? AND user_id = ?");
    $stmt->execute([$ad_id, $user_id]);
    $ad = $stmt->fetch();

    if ($ad) {
        $stmt = $pdo->query("SELECT setting_value FROM settings WHERE setting_key = 'free_ad_duration'");
        $duration = (int)($stmt->fetchColumn() ?: 15);
        $expires_at = date('Y-m-d H:i:s', strtotime("+$duration days"));

        // If it's a free ad, we update expiry but NOT bumped_at.
        // This prevents free ads from jumping to the top of new ads.
        $stmt = $pdo->prepare("UPDATE ads SET status = 'pending', expires_at = ? WHERE id = ?");
        $stmt->execute([$expires_at, $ad_id]);
        redirect('../profile.php', 'Ad republished! It will be live after moderation.');
    }
}
redirect('../profile.php');
