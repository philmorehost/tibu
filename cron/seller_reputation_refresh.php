<?php
require_once __DIR__ . '/../config/config.php';

try {
    $users = $pdo->query("SELECT id FROM users")->fetchAll();
    foreach ($users as $user) {
        $user_id = $user['id'];
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM ads WHERE user_id = ? AND status IN ('sold', 'swapped')");
        $stmt->execute([$user_id]);
        $deals = $stmt->fetchColumn();
        $stmt = $pdo->prepare("SELECT AVG(stars) FROM reviews WHERE seller_id = ? AND removed = 0");
        $stmt->execute([$user_id]);
        $rating = $stmt->fetchColumn() ?: 0;
        $badge = 'new';
        if ($deals >= 20 && $rating >= 4.5) $badge = 'trusted';
        elseif ($deals >= 5 && $rating >= 4.0) $badge = 'active';
        $stmt = $pdo->prepare("SELECT verification_tier FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $tier = $stmt->fetchColumn();
        if ($tier === 'business_verified') $badge = 'business';
        elseif ($tier === 'nin_verified' && $badge === 'new') $badge = 'verified';
        $pdo->prepare("INSERT INTO seller_reputation (user_id, badge_tier, transaction_count, avg_rating) VALUES (?, ?, ?, ?) ON DUPLICATE KEY UPDATE badge_tier = VALUES(badge_tier), transaction_count = VALUES(transaction_count), avg_rating = VALUES(avg_rating)")->execute([$user_id, $badge, $deals, $rating]);
    }
} catch (Exception $e) { error_log("Reputation Cron Error: " . $e->getMessage()); }
