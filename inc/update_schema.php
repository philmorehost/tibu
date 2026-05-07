<?php
/**
 * Classifieds — Aggressive Schema Sync
 * Ensures all required columns exist by trying to add them.
 */

if (!isset($pdo)) {
    require_once __DIR__ . '/../config/config.php';
}
if (!isset($pdo)) return;

// Check if messages table exists - if not, clear session to force sync
if (isset($_SESSION["schema_verified"])) {
    try {
        $pdo->query("SELECT 1 FROM messages LIMIT 1");
    } catch (Exception $e) {
        unset($_SESSION["schema_verified"]);
    }
}

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
    ],
    'blog_posts' => [
        'meta_title' => "VARCHAR(255) DEFAULT NULL AFTER image",
        'summary' => "TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci",
        'content' => "LONGTEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"
    ],
    'pages' => [
        'title' => "VARCHAR(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL",
        'content' => "LONGTEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"
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

// Robust UTF8MB4 Support for Multi-currency and Special Characters
try {
    $pdo->exec("ALTER TABLE pages CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $pdo->exec("ALTER TABLE blog_posts CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $pdo->exec("ALTER TABLE ads CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
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
        title VARCHAR(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
        slug VARCHAR(255) UNIQUE NOT NULL,
        content LONGTEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
        meta_desc TEXT,
        meta_keys TEXT,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )",
    "CREATE TABLE IF NOT EXISTS blog_posts (
        id INT AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(200) NOT NULL,
        slug VARCHAR(255) UNIQUE NOT NULL,
        summary TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
        content LONGTEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
        image VARCHAR(255) DEFAULT NULL,
        meta_title VARCHAR(255) DEFAULT NULL,
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
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX (sender_id),
        INDEX (receiver_id),
        INDEX (ad_id)
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
    try {
        $pdo->exec($sql);
    } catch (Exception $e) {
        error_log("Schema update failed for SQL: $sql. Error: " . $e->getMessage());
    }
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

// Seed Default Pages with Professional Content
$pages = [
    [
        'slug' => 'billing',
        'title' => 'Billing Policy',
        'content' => "# Billing Policy\n\n**Last updated: April 2026**\n**Effective date: April 2026**\n\nThis Billing Policy explains all paid features on Tibu.ng, how charges work, and your rights regarding refunds and cancellations.\n\n---\n\n## 1. Free Features — What Is Always Free\n\nThe following features on Tibu.ng are permanently free and will never be charged for:\n\n- Account registration\n- Up to 20 listings for phone + email verified accounts\n- Unlimited listings for NIN Verified accounts\n- Browsing and searching all listings\n- Contacting sellers via in-app messaging\n- Seller analytics dashboard (views, enquiry rates, listing health)\n- Receiving and displaying reviews\n- Swap & Exchange marketplace access\n- Basic seller profile page\n\n**Free listings are never suppressed.** Free listings rank based on recency, listing quality, and Deal Safety Score — never on payment status.\n\n---\n\n## 2. Promoted Listing Tiers\n\n### Tibu Boost — ₦1,500 for 7 days\n- 3× more impressions in your category and city feed\n- \"Sponsored\" badge\n\n### Tibu Top — ₦3,500 for 7 days\n- Top 3 placement in category search results\n\n### Tibu Pro — ₦12,000 per month\n- Unlimited listings, Full analytics, Priority support\n\n### Tibu Business — ₦35,000 per month\n- Everything in Tibu Pro + Dedicated business profile and account manager\n\n---\n\n## 3. Payment Methods\n\nAll payments are processed securely through **Paystack** or **Flutterwave**. We do not store card details.\n\n---\n\n## 8. Refund Policy\n\nFull refunds are issued for technical errors or double-charges. Email billing@tibu.ng for assistance."
    ],
    [
        'slug' => 'about-us',
        'title' => 'About Tibu.ng',
        'content' => "# About Tibu.ng\n\n## Who We Are\nTibu.ng is Nigeria's safest buy, sell and swap marketplace. We built Tibu because buying and selling online in Nigeria had a trust problem.\n\n## What Makes Tibu Different\nEvery seller on Tibu.ng must verify their identity. Phone and email sign-up gives you 20 free listings. NIN verification unlocks unlimited listings and the Tibu Verified Badge.\n\nWe are the only Nigerian marketplace with a dedicated, verified Swap & Exchange marketplace and a Deal Safety Score on every listing.\n\n## Our Mission\nTo make buying and selling in Nigeria as safe, transparent, and straightforward as it should always have been."
    ],
    [
        'slug' => 'contact',
        'title' => 'Contact Us',
        'content' => "# Contact Us — Tibu.ng\n\n## Support Channels\n\n### General Support\n**Email:** support@tibu.ng\n**Response time:** Within 24 hours on business days\n\n### Billing & Promotions\n**Email:** billing@tibu.ng\n\n### Safety Reports\n**Email:** safety@tibu.ng\n**Response time:** Within 24 hours; urgent cases reviewed same day\n\n---\n\n## Report a Listing or User\nIf you encounter a suspicious listing or user, tap the flag icon (⚑) on the listing page or seller profile."
    ],
    [
        'slug' => 'faq',
        'title' => 'Frequently Asked Questions',
        'content' => "JSON: [{\"q\": \"What is Tibu.ng?\", \"a\": \"Tibu.ng is Nigeria's safest buy, sell and swap marketplace with NIN-verified sellers.\"}, {\"q\": \"Is Tibu.ng free?\", \"a\": \"Yes. Browsing, searching, and standard listings are free.\"}, {\"q\": \"What is the Verified Badge?\", \"a\": \"It means a seller has completed NIN verification matched with a live selfie.\"}, {\"q\": \"How to post an ad?\", \"a\": \"Click 'Post Ad' on the homepage and follow the steps.\"}, {\"q\": \"Is it safe to pay a seller directly?\", \"a\": \"Always inspect items in person before payment. Use Tibu's in-app messaging for records.\"}]"
    ],
    [
        'slug' => 'privacy',
        'title' => 'Privacy Policy',
        'content' => "# Privacy Policy\n\nTibu.ng is committed to protecting your personal information. We collect registration data, identity verification data (NIN), and usage data to provide a safe marketplace experience. We do not store raw ID documents or selfie images after verification."
    ],
    [
        'slug' => 'safety-tips',
        'title' => 'Safety Tips',
        'content' => "# Safety Tips — Tibu.ng\n\n- **Check verification badges.** Prefer NIN-verified sellers.\n- **Read reviews.** Look for detailed experiences.\n- **Keep conversations on Tibu.** Avoid moving to WhatsApp too early.\n- **Never pay before seeing the item.** This is the most common scam pattern.\n- **Meet in public.** Banks, malls, or busy markets are ideal."
    ],
    [
        'slug' => 'terms',
        'title' => 'Terms & Conditions',
        'content' => "# Terms & Conditions\n\nBy using Tibu.ng, you agree to comply with our rules. Users must be 18+, provide truthful info, and follow listing requirements. Prohibited items include illegal drugs, firearms, and adult content."
    ]
];

foreach ($pages as $p) {
    try {
        $stmt = $pdo->prepare("INSERT INTO pages (title, slug, content) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE title = IF(content LIKE '%[Detailed%' OR content = '', VALUES(title), title), content = IF(content LIKE '%[Detailed%' OR content = '', VALUES(content), content)");
        $stmt->execute([$p['title'], $p['slug'], $p['content']]);
    } catch (Exception $e) {}
}

error_log("Schema sync and page seeding completed successfully.");
$_SESSION["schema_verified"] = true;
