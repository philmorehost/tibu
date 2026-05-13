<?php
define('DB_HOST', 'localhost');
define('DB_NAME', 'tibung_site');
define('DB_USER', 'tibung_site');
define('DB_PASS', 'Ge4xEPtD$}p~4KqW');

// Scaling: Support for Read Replicas (Optional)
$db_hosts = explode(',', DB_HOST);
$current_host = $db_hosts[array_rand($db_hosts)];

try {
    $pdo = new PDO("mysql:host=" . $current_host . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS, [
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci",
        PDO::ATTR_PERSISTENT => true, // Performance: Reuse connections
        PDO::ATTR_EMULATE_PREPARES => false, // Security and performance
        PDO::ATTR_TIMEOUT => 5 // Scaling: Fail fast if server is pressured
    ]);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}
