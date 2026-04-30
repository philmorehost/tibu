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
    $stmt = $pdo->prepare("UPDATE ads SET status = 'expired' WHERE status = 'active' AND expires_at < NOW()");
    $stmt->execute();
    $count = $stmt->rowCount();

    echo "[" . date('Y-m-d H:i:s') . "] Cron executed: $count ads marked as expired.\n";
} catch (PDOException $e) {
    echo "[" . date('Y-m-d H:i:s') . "] Cron Error: " . $e->getMessage() . "\n";
}
