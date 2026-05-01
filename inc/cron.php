<?php
/**
 * Jiji-Inspired-1.0 Automation Cron Script
 * Sets expired status for ads whose duration has passed.
 */

// Allow execution only from CLI for security
if (php_sapi_name() !== 'cli' && !isset($_GET['run_cron_secure_token'])) {
    die("Unauthorized access.");
}

require_once __DIR__ . '/../config/config.php';

try {
    // 1. Mark expired ads
    $stmt = $pdo->prepare("UPDATE ads SET status = 'expired' WHERE status = 'active' AND expires_at < NOW()");
    $stmt->execute();
    $count = $stmt->rowCount();

    // 2. Handle Auto-renew for active packages
    // Bumps ads that have auto_renew_hours set in their tier
    $stmt_renew = $pdo->query("SELECT a.id, p.auto_renew_hours FROM ads a
                               JOIN packages p ON a.ad_tier = p.tier
                               WHERE a.status = 'active' AND p.auto_renew_hours > 0
                               AND a.bumped_at < DATE_SUB(NOW(), INTERVAL p.auto_renew_hours HOUR)");
    $ads_to_renew = $stmt_renew->fetchAll();
    foreach ($ads_to_renew as $ad) {
        $pdo->prepare("UPDATE ads SET bumped_at = NOW() WHERE id = ?")->execute([$ad['id']]);
    }
    $renew_count = count($ads_to_renew);

    echo "[" . date('Y-m-d H:i:s') . "] Cron executed: $count expired, $renew_count auto-renewed.\n";
} catch (PDOException $e) {
    echo "[" . date('Y-m-d H:i:s') . "] Cron Error: " . $e->getMessage() . "\n";
}
