<?php
/**
 * Classifieds — Aggressive Schema Sync
 * Ensures all required columns exist by trying to add them.
 */

if (!isset($pdo)) {
    require_once __DIR__ . '/../config/config.php';
}
if (!isset($pdo)) return;

// Simple guard to prevent running on every page load after first successful run in a session
if (isset($_SESSION['schema_verified'])) return;

$tables = [
    'ads' => [
        'ad_data' => "JSON DEFAULT NULL AFTER description",
        'listing_type' => "ENUM('for_sale', 'for_swap', 'for_sale_or_swap') DEFAULT 'for_sale' AFTER price",
        'estimated_value' => "DECIMAL(15, 2) DEFAULT NULL AFTER listing_type",
        'swap_preference' => "TEXT DEFAULT NULL AFTER estimated_value",
        'allow_cash_topup' => "TINYINT(1) DEFAULT 0 AFTER swap_preference",
        'safety_score' => "INT DEFAULT 50 AFTER status",
        'video_url' => "VARCHAR(255) DEFAULT NULL",
        'expires_at' => "TIMESTAMP NULL DEFAULT NULL",
        'bumped_at' => "TIMESTAMP DEFAULT CURRENT_TIMESTAMP"
    ],
    'users' => [
        'business_name' => "VARCHAR(200) DEFAULT NULL AFTER full_name",
        'verification_tier' => "ENUM('phone_verified', 'nin_verified', 'business_verified') DEFAULT 'phone_verified' AFTER is_verified",
        'nin_number' => "VARCHAR(11) DEFAULT NULL AFTER verification_tier",
        'kyc_reference' => "VARCHAR(100) DEFAULT NULL AFTER nin_number",
        'last_verified_at' => "DATETIME DEFAULT NULL AFTER kyc_reference",
        'verification_fails' => "INT DEFAULT 0 AFTER last_verified_at",
        'locked_until' => "DATETIME DEFAULT NULL AFTER verification_fails"
    ],
    'categories' => [
        'is_top' => "TINYINT(1) DEFAULT 0",
        'sort_order' => "INT DEFAULT 0",
        'slug' => "VARCHAR(255) UNIQUE DEFAULT NULL"
    ],
    'payments' => [
        'reject_reason' => "TEXT DEFAULT NULL",
        'proof_image' => "VARCHAR(255) DEFAULT NULL"
    ],
    'reviews' => [
        'reply_text' => "TEXT DEFAULT NULL AFTER body",
        'replied_at' => "DATETIME DEFAULT NULL AFTER reply_text"
    ]
];

foreach ($tables as $table => $cols) {
    foreach ($cols as $col => $def) {
        try {
            $pdo->exec("ALTER TABLE `$table` ADD COLUMN `$col` $def");
        } catch (Exception $e) {
            if ($col === 'ad_data' && strpos($e->getMessage(), 'JSON') !== false) {
                try { $pdo->exec("ALTER TABLE ads ADD COLUMN ad_data LONGTEXT DEFAULT NULL AFTER description"); } catch (Exception $e2) {}
            }
        }
    }
}

// Update status ENUM
try {
    $pdo->exec("ALTER TABLE ads MODIFY COLUMN status ENUM('pending', 'active', 'declined', 'sold', 'swapped', 'expired', 'moderation') DEFAULT 'pending'");
} catch (Exception $e) {}

// Missing Tables
$missing_tables = [
    "CREATE TABLE IF NOT EXISTS countries (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        code CHAR(2) NOT NULL UNIQUE,
        status ENUM('active', 'inactive') DEFAULT 'active'
    )",
    "CREATE TABLE IF NOT EXISTS admin_users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(50) NOT NULL UNIQUE,
        password VARCHAR(255) NOT NULL,
        email VARCHAR(100),
        full_name VARCHAR(100),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )",
    "CREATE TABLE IF NOT EXISTS login_logs (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(50),
        ip_address VARCHAR(45),
        is_success TINYINT(1),
        attempted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )",
    "CREATE TABLE IF NOT EXISTS ip_security (
        ip_address VARCHAR(45) PRIMARY KEY,
        status ENUM('whitelisted', 'blacklisted') DEFAULT 'whitelisted',
        reason TEXT,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )",
    "CREATE TABLE IF NOT EXISTS payments (
        id INT AUTO_INCREMENT PRIMARY KEY,
        ad_id INT,
        user_id INT,
        amount DECIMAL(15, 2),
        status ENUM('pending', 'successful', 'failed') DEFAULT 'pending',
        payment_method VARCHAR(50),
        reference VARCHAR(100),
        proof_image VARCHAR(255) DEFAULT NULL,
        reject_reason TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (ad_id) REFERENCES ads(id) ON DELETE CASCADE,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )",
    "CREATE TABLE IF NOT EXISTS seller_reputation (
        user_id INT PRIMARY KEY,
        badge_tier ENUM('new', 'verified', 'active', 'trusted', 'business') DEFAULT 'new',
        transaction_count INT DEFAULT 0,
        avg_rating DECIMAL(3, 2) DEFAULT 0,
        dispute_count INT DEFAULT 0,
        last_calculated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )",
    "CREATE TABLE IF NOT EXISTS seller_analytics (
        id INT AUTO_INCREMENT PRIMARY KEY,
        ad_id INT NOT NULL,
        viewer_id INT DEFAULT NULL,
        source VARCHAR(50),
        ip_address VARCHAR(45),
        viewed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (ad_id) REFERENCES ads(id) ON DELETE CASCADE
    )",
    "CREATE TABLE IF NOT EXISTS notifications (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        title VARCHAR(255) NOT NULL,
        message TEXT NOT NULL,
        link VARCHAR(255) DEFAULT NULL,
        is_read TINYINT(1) DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )",
    "CREATE TABLE IF NOT EXISTS pages (
        id INT AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(150) NOT NULL,
        slug VARCHAR(255) UNIQUE NOT NULL,
        content LONGTEXT,
        meta_desc TEXT,
        meta_keys TEXT,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )",
    "CREATE TABLE IF NOT EXISTS blog_posts (
        id INT AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(200) NOT NULL,
        slug VARCHAR(255) UNIQUE NOT NULL,
        summary TEXT,
        content LONGTEXT,
        image VARCHAR(255) DEFAULT NULL,
        meta_desc TEXT,
        meta_keywords TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )",
    "CREATE TABLE IF NOT EXISTS swap_proposals (
        id INT AUTO_INCREMENT PRIMARY KEY,
        ad_id INT,
        offered_ad_id INT,
        sender_id INT,
        receiver_id INT,
        cash_topup DECIMAL(15, 2) DEFAULT 0,
        message TEXT,
        status ENUM('pending', 'accepted', 'declined', 'countered', 'expired') DEFAULT 'pending',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (ad_id) REFERENCES ads(id) ON DELETE CASCADE,
        FOREIGN KEY (offered_ad_id) REFERENCES ads(id) ON DELETE CASCADE,
        FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (receiver_id) REFERENCES users(id) ON DELETE CASCADE
    )",
    "CREATE TABLE IF NOT EXISTS saved_ads (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        ad_id INT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (ad_id) REFERENCES ads(id) ON DELETE CASCADE,
        UNIQUE KEY (user_id, ad_id)
    )",
    "CREATE TABLE IF NOT EXISTS search_history (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        keyword VARCHAR(255),
        cat_id INT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )"
];

foreach ($missing_tables as $sql) {
    try { $pdo->exec($sql); } catch (Exception $e) {}
}

$_SESSION['schema_verified'] = true;
