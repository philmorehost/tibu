<?php
/**
 * Classifieds — Aggressive Schema Sync
 * Ensures all required columns exist by trying to add them.
 */

if (!isset($pdo)) {
    require_once __DIR__ . '/../config/config.php';
}
if (!isset($pdo)) return;

if (isset($_SESSION["schema_verified"])) return;

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
        'bumped_at' => "TIMESTAMP DEFAULT CURRENT_TIMESTAMP",
        'ad_tier' => "ENUM('free', 'premium', 'vip', 'diamond') DEFAULT 'free' AFTER is_featured"
    ],
    'users' => [
        'business_name' => "VARCHAR(200) DEFAULT NULL AFTER full_name",
        'verification_tier' => "ENUM('phone_verified', 'nin_verified', 'business_verified') DEFAULT 'phone_verified' AFTER is_verified",
        'nin_number' => "VARCHAR(11) DEFAULT NULL AFTER verification_tier",
        'kyc_reference' => "VARCHAR(100) DEFAULT NULL AFTER nin_number",
        'last_verified_at' => "DATETIME DEFAULT NULL AFTER kyc_reference",
        'verification_fails' => "INT DEFAULT 0 AFTER last_verified_at",
        'locked_until' => "DATETIME DEFAULT NULL AFTER verification_fails",
        'is_suspended' => "TINYINT(1) DEFAULT 0",
        'website_url' => "VARCHAR(255) DEFAULT NULL",
        'instagram_url' => "VARCHAR(255) DEFAULT NULL",
        'twitter_url' => "VARCHAR(255) DEFAULT NULL",
        'cashback_balance' => "DECIMAL(15, 2) DEFAULT 0"
    ],
    'categories' => [
        'is_top' => "TINYINT(1) DEFAULT 0",
        'sort_order' => "INT DEFAULT 0",
        'slug' => "VARCHAR(255) UNIQUE DEFAULT NULL"
    ],
    'payments' => [
        'reject_reason' => "TEXT DEFAULT NULL",
        'proof_image' => "VARCHAR(255) DEFAULT NULL",
        'ad_tier' => "VARCHAR(20) DEFAULT NULL"
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
    "CREATE TABLE IF NOT EXISTS states (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(50) NOT NULL UNIQUE
    )",
    "CREATE TABLE IF NOT EXISTS lgas (
        id INT AUTO_INCREMENT PRIMARY KEY,
        state_id INT,
        name VARCHAR(100) NOT NULL,
        INDEX (state_id),
        FOREIGN KEY (state_id) REFERENCES states(id) ON DELETE CASCADE
    )",
    "CREATE TABLE IF NOT EXISTS categories (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(50) NOT NULL,
        parent_id INT DEFAULT 0,
        icon_class VARCHAR(50),
        slug VARCHAR(255) UNIQUE,
        is_top TINYINT(1) DEFAULT 0,
        sort_order INT DEFAULT 0
    )",
    "CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        full_name VARCHAR(100),
        business_name VARCHAR(200) DEFAULT NULL,
        email VARCHAR(100) UNIQUE,
        phone VARCHAR(20),
        password VARCHAR(255),
        is_verified TINYINT(1) DEFAULT 0,
        verification_tier ENUM('phone_verified', 'nin_verified', 'business_verified') DEFAULT 'phone_verified',
        nin_number VARCHAR(11) DEFAULT NULL,
        kyc_reference VARCHAR(100) DEFAULT NULL,
        last_verified_at DATETIME DEFAULT NULL,
        verification_fails INT DEFAULT 0,
        locked_until DATETIME DEFAULT NULL,
        is_suspended TINYINT(1) DEFAULT 0,
        website_url VARCHAR(255) DEFAULT NULL,
        instagram_url VARCHAR(255) DEFAULT NULL,
        twitter_url VARCHAR(255) DEFAULT NULL,
        cashback_balance DECIMAL(15, 2) DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )",
    "CREATE TABLE IF NOT EXISTS ads (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT,
        cat_id INT,
        state_id INT,
        lga_id INT,
        title VARCHAR(150),
        description TEXT,
        ad_data JSON DEFAULT NULL,
        price DECIMAL(15, 2),
        listing_type ENUM('for_sale', 'for_swap', 'for_sale_or_swap') DEFAULT 'for_sale',
        estimated_value DECIMAL(15, 2) DEFAULT NULL,
        swap_preference TEXT DEFAULT NULL,
        allow_cash_topup TINYINT(1) DEFAULT 0,
        status ENUM('pending', 'active', 'declined', 'sold', 'swapped', 'expired', 'moderation') DEFAULT 'pending',
        safety_score INT DEFAULT 50,
        is_featured TINYINT(1) DEFAULT 0,
        ad_tier ENUM('free', 'premium', 'vip', 'diamond') DEFAULT 'free',
        views INT DEFAULT 0,
        decline_reason TEXT,
        expires_at TIMESTAMP NULL DEFAULT NULL,
        bumped_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX (cat_id),
        INDEX (state_id),
        INDEX (status)
    )",
    "CREATE TABLE IF NOT EXISTS ad_images (
        id INT AUTO_INCREMENT PRIMARY KEY,
        ad_id INT,
        image_path VARCHAR(255),
        is_main TINYINT(1) DEFAULT 0,
        FOREIGN KEY (ad_id) REFERENCES ads(id) ON DELETE CASCADE
    )",
    "CREATE TABLE IF NOT EXISTS image_hashes (
        id INT AUTO_INCREMENT PRIMARY KEY,
        ad_id INT,
        user_id INT,
        phash VARCHAR(64) NOT NULL,
        uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX (phash),
        FOREIGN KEY (ad_id) REFERENCES ads(id) ON DELETE CASCADE
    )",
    "CREATE TABLE IF NOT EXISTS reviews (
        id INT AUTO_INCREMENT PRIMARY KEY,
        reviewer_id INT NOT NULL,
        seller_id INT NOT NULL,
        stars TINYINT NOT NULL,
        tags JSON DEFAULT NULL,
        body TEXT,
        reply_text TEXT DEFAULT NULL,
        reply_at TIMESTAMP NULL DEFAULT NULL,
        submitted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        challenged TINYINT(1) DEFAULT 0,
        challenge_resolved_at DATETIME DEFAULT NULL,
        removed TINYINT(1) DEFAULT 0,
        removed_by INT DEFAULT NULL,
        remove_reason TEXT
    )",
    "CREATE TABLE IF NOT EXISTS settings (
        setting_key VARCHAR(100) PRIMARY KEY,
        setting_value TEXT
    )",
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
    "CREATE TABLE IF NOT EXISTS messages (
        id INT AUTO_INCREMENT PRIMARY KEY,
        sender_id INT NOT NULL,
        receiver_id INT NOT NULL,
        ad_id INT NOT NULL,
        message TEXT NOT NULL,
        is_read TINYINT(1) DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )",
    "CREATE TABLE IF NOT EXISTS search_history (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        keyword VARCHAR(255),
        cat_id INT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )",
    "CREATE TABLE IF NOT EXISTS packages (
        id INT AUTO_INCREMENT PRIMARY KEY,
        tier ENUM('free', 'premium', 'vip', 'diamond') UNIQUE NOT NULL,
        name VARCHAR(50) NOT NULL,
        price DECIMAL(15, 2) DEFAULT 0,
        duration_days INT DEFAULT 30,
        cashback DECIMAL(15, 2) DEFAULT 0,
        power_up VARCHAR(50) DEFAULT '1x',
        listings_cars INT DEFAULT 0,
        listings_property INT DEFAULT 0,
        listings_others INT DEFAULT 0,
        promo_ads_count INT DEFAULT 0,
        auto_renew_hours INT DEFAULT 0,
        has_social_links TINYINT(1) DEFAULT 0,
        has_personal_manager TINYINT(1) DEFAULT 0,
        has_insights_report TINYINT(1) DEFAULT 0,
        has_feedback_tool TINYINT(1) DEFAULT 0,
        has_pro_sales TINYINT(1) DEFAULT 0,
        has_email_promo TINYINT(1) DEFAULT 0,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )"
];

foreach ($missing_tables as $sql) {
    try { $pdo->exec($sql); } catch (Exception $e) {}
}

// Data Migration: is_featured to ad_tier
try {
    // Check if column exists first to avoid errors during initial run
    $stmt = $pdo->query("SHOW COLUMNS FROM ads LIKE 'ad_tier'");
    if ($stmt->fetch()) {
        $pdo->exec("UPDATE ads SET ad_tier = 'premium' WHERE is_featured = 1 AND (ad_tier IS NULL OR ad_tier = 'free')");
    }
} catch (Exception $e) {}

// Settings Migration & Defaults
$default_settings = [
    'google_login_active' => '0',
    'facebook_login_active' => '0',
    'vip_ad_duration' => '60',
    'diamond_ad_duration' => '90',
    'vip_ad_price' => '10000',
    'diamond_ad_price' => '20000',
    'registration_otp_enabled' => '1'
];

// Rename boost_price to premium_ad_price if it exists
try {
    $pdo->exec("UPDATE settings SET setting_key = 'premium_ad_price' WHERE setting_key = 'boost_price'");
} catch (Exception $e) {}

foreach ($default_settings as $key => $val) {
    try {
        $stmt = $pdo->prepare("INSERT IGNORE INTO settings (setting_key, setting_value) VALUES (?, ?)");
        $stmt->execute([$key, $val]);
    } catch (Exception $e) {}
}

// Seed Packages Table
try {
    $stmt = $pdo->query("SELECT COUNT(*) FROM packages");
    if ($stmt->fetchColumn() == 0) {
        $packages = [
            ['free', 'Free', 0, 15, 0, '1x', 1, 1, 10, 0, 0, 0, 0, 0, 0, 0, 0],
            ['premium', 'Premium', 49999, 30, 38500, '5x', 15, 5, 50, 5, 24, 0, 0, 0, 0, 1, 0],
            ['vip', 'VIP', 71999, 30, 55500, '7x', 30, 10, 100, 10, 12, 0, 0, 0, 0, 1, 0],
            ['diamond', 'Diamond Gold', 123499, 30, 95100, '20x', 70, 999999, 500, 20, 3, 1, 1, 1, 1, 1, 1]
        ];
        $insert = $pdo->prepare("INSERT INTO packages (tier, name, price, duration_days, cashback, power_up, listings_cars, listings_property, listings_others, promo_ads_count, auto_renew_hours, has_social_links, has_personal_manager, has_insights_report, has_feedback_tool, has_pro_sales, has_email_promo) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        foreach ($packages as $pkg) {
            $insert->execute($pkg);
        }
    }
} catch (Exception $e) {}

error_log("Schema sync completed successfully.");
$_SESSION["schema_verified"] = true;
