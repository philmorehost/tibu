<?php
require_once __DIR__ . '/../config/config.php';

try {
    // Optimized single-query approach to eliminate N+1 issue
    $sql = "INSERT INTO seller_reputation (user_id, badge_tier, transaction_count, avg_rating)
    SELECT
        u.id,
        CASE
            WHEN u.verification_tier = 'business_verified' THEN 'business'
            WHEN COALESCE(a.deal_count, 0) >= 20 AND COALESCE(r.avg_rating, 0) >= 4.5 THEN 'trusted'
            WHEN COALESCE(a.deal_count, 0) >= 5 AND COALESCE(r.avg_rating, 0) >= 4.0 THEN 'active'
            WHEN u.verification_tier = 'nin_verified' THEN 'verified'
            ELSE 'new'
        END as badge_tier,
        COALESCE(a.deal_count, 0),
        COALESCE(r.avg_rating, 0)
    FROM users u
    LEFT JOIN (
        SELECT user_id, COUNT(*) AS deal_count
        FROM ads
        WHERE status IN ('sold', 'swapped')
        GROUP BY user_id
    ) a ON u.id = a.user_id
    LEFT JOIN (
        SELECT seller_id, AVG(stars) AS avg_rating
        FROM reviews
        WHERE removed = 0
        GROUP BY seller_id
    ) r ON u.id = r.seller_id
    ON DUPLICATE KEY UPDATE
        badge_tier = VALUES(badge_tier),
        transaction_count = VALUES(transaction_count),
        avg_rating = VALUES(avg_rating)";

    $pdo->exec($sql);
} catch (Exception $e) {
    error_log("Reputation Cron Error: " . $e->getMessage());
}
