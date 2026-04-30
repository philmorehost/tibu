<?php
/**
 * Classifieds Common Functions
 */

// Check if schema needs update (migration logic)
if (file_exists(__DIR__ . '/../config/config.php')) {
    require_once __DIR__ . '/update_schema.php';
}

require_once __DIR__ . '/marketing.php';

// Initialize project directories
$required_dirs = [
    __DIR__ . "/../uploads/branding",
    __DIR__ . '/../config',
    __DIR__ . '/../uploads',
    __DIR__ . '/../uploads/ads',
    __DIR__ . '/../uploads/proofs',
    __DIR__ . '/../uploads/blog'
];
foreach ($required_dirs as $dir) {
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
}

/**
 * Sanitize output for XSS prevention
 */
function h($string) {
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}

/**
 * Redirect with a message
 */
function redirect($url, $message = null, $type = 'info') {
    if ($message) {
        $_SESSION['flash'] = ['message' => $message, 'type' => $type];
    }
    header("Location: $url");
    exit;
}

/**
 * Get client IP address
 */
function get_client_ip() {
    $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        $ip = $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ip = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0];
    }
    return trim($ip);
}

/**
 * Image Upload & Processing (GD Library) - Enhanced with pHash & Watermark
 */
function process_image_upload($file_tmp, $target_dir, $max_width = 800, $user_id = 0, $ad_id = 0) {
    global $pdo;
    if (!is_dir($target_dir)) {
        mkdir($target_dir, 0755, true);
    }

    list($width, $height, $type) = getimagesize($file_tmp);
    switch ($type) {
        case IMAGETYPE_JPEG: $src = imagecreatefromjpeg($file_tmp); break;
        case IMAGETYPE_PNG: $src = imagecreatefrompng($file_tmp); break;
        case IMAGETYPE_GIF: $src = imagecreatefromgif($file_tmp); break;
        case IMAGETYPE_WEBP: $src = imagecreatefromwebp($file_tmp); break;
        default: return false;
    }

    // Perceptual Hash Check
    $phash = generate_phash($src);
    if ($pdo) {
        $stmt = $pdo->prepare("SELECT id FROM image_hashes WHERE phash = ?");
        $stmt->execute([$phash]);
        if ($stmt->fetch()) {
            imagedestroy($src);
            return "DUPLICATE";
        }
    }

    // Fetch seller info for watermark
    $seller_info = "";
    if ($pdo && $user_id) {
        $stmt_s = $pdo->prepare("SELECT full_name, business_name FROM users WHERE id = ?");
        $stmt_s->execute([$user_id]);
        $s_info = $stmt_s->fetch();
        if ($s_info) {
            $seller_info = $s_info['business_name'] ?: $s_info['full_name'];
        }
    }

    // Apply Watermark
    apply_site_watermark($src, $seller_info);

    $filename = md5(uniqid(rand(), true)) . ".jpg";
    $target_file = $target_dir . "/" . $filename;

    $new_width = min($width, $max_width);
    $new_height = ($height / $width) * $new_width;
    $tmp = imagecreatetruecolor($new_width, $new_height);
    imagecopyresampled($tmp, $src, 0, 0, 0, 0, $new_width, $new_height, $width, $height);
    imagejpeg($tmp, $target_file, 80);

    // Save hash
    if ($pdo && $ad_id) {
        $pdo->prepare("INSERT INTO image_hashes (ad_id, user_id, phash) VALUES (?, ?, ?)")->execute([$ad_id, $user_id, $phash]);
    }

    imagedestroy($src);
    imagedestroy($tmp);
    return $filename;
}

/**
 * Generate SEO friendly Ad URL
 */
function generate_ad_url($ad) {
    $title = $ad['title'] ?? 'ad';
    $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $title)));

    $state_name = $ad['state_name'] ?? 'nigeria';
    $state = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $state_name)));

    $cat_name = $ad['cat_name'] ?? 'others';
    $cat = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $cat_name)));

    // Ensure we don't have empty segments
    $state = $state ?: 'nigeria';
    $cat = $cat ?: 'others';

    return "/$state/$cat/$slug-{$ad['id']}";
}

/**
 * Extract SEO Keywords from text
 */
function extract_keywords($text, $additional = "") {
    $premium_words = ['buy', 'sell', 'cheap', 'best', 'nigeria', 'price', 'new', 'used', 'deals', 'marketplace', 'classifieds', 'online shop'];
    $text = strtolower($text . " " . $additional);
    $text = preg_replace('/[^a-z0-9\s]/', '', $text);
    $words = explode(' ', $text);

    // Filter out short and common words
    $common = ['the', 'and', 'with', 'for', 'this', 'that', 'your', 'from', 'have', 'more', 'about'];
    $filtered = array_filter($words, function($w) use ($common) {
        return strlen($w) > 3 && !in_array($w, $common);
    });

    // Prioritize premium words
    $keywords = array_unique(array_merge($premium_words, $filtered));
    return implode(', ', array_slice($keywords, 0, 15));
}

/**
 * Generate Auto SEO Meta tags for any page
 */
function generate_meta_tags($title, $description, $tags = "") {
    $meta_title = h($title);
    // Limit description to 160 characters for SEO
    $meta_desc = h(substr(strip_tags($description), 0, 160));
    $meta_keywords = h(extract_keywords($title, $tags));

    return [
        'title' => $meta_title,
        'description' => $meta_desc,
        'keywords' => $meta_keywords
    ];
}

/**
 * Generate a simple Perceptual Hash for an image (Feature 03)
 */
function generate_phash($resource) {
    $resized = imagecreatetruecolor(8, 8);
    imagecopyresampled($resized, $resource, 0, 0, 0, 0, 8, 8, imagesx($resource), imagesy($resource));
    imagefilter($resized, IMG_FILTER_GRAYSCALE);

    $hash = '';
    for ($y = 0; $y < 8; $y++) {
        for ($x = 0; $x < 8; $x++) {
            $rgb = imagecolorat($resized, $x, $y);
            $hash .= ($rgb & 0xFF) > 128 ? '1' : '0';
        }
    }
    imagedestroy($resized);
    return $hash;
}

/**
 * Apply Site Watermark (Feature 06) - Dynamic with Site and Seller branding
 */
function apply_site_watermark($resource, $seller_info = "") {
    global $pdo;
    $width = imagesx($resource);
    $height = imagesy($resource);
    $font_path = '/usr/share/fonts/truetype/dejavu/DejaVuSans-Bold.ttf';

    // Attempt to fetch site name for watermark
    static $site_name = null;
    if ($site_name === null && $pdo) {
        try {
            $stmt = $pdo->query("SELECT setting_value FROM settings WHERE setting_key = 'site_name'");
            $site_name = $stmt->fetchColumn();
        } catch (Exception $e) {
            $site_name = "Classifieds";
        }
    }

    $site_text = $site_name ?: "Classifieds";
    $white = imagecolorallocatealpha($resource, 255, 255, 255, 35); // Slightly lighter for complex text

    if (file_exists($font_path) && function_exists('imagettftext')) {
        $font_size = $width / 14;

        // Main Centered Site Name
        $bbox = imagettfbbox($font_size, 0, $font_path, $site_text);
        $text_width = $bbox[2] - $bbox[0];
        $text_height = $bbox[7] - $bbox[1];
        $x = ($width / 2) - ($text_width / 2);
        $y = ($height / 2) - ($text_height / 2);
        imagettftext($resource, $font_size, 0, $x, $y, $white, $font_path, $site_text);

        // Bottom Right: Site Name + Seller Info (Business name)
        $seller_text = $site_text . ($seller_info ? " | " . $seller_info : "");
        $small_size = max(8, $width / 45);
        $bbox_small = imagettfbbox($small_size, 0, $font_path, $seller_text);
        $sx = $width - ($bbox_small[2] - $bbox_small[0]) - 20;
        $sy = $height - 20;
        imagettftext($resource, $small_size, 0, $sx, $sy, $white, $font_path, $seller_text);

        // Tiled secondary watermarks (Jiji Style)
        $tile_text = $site_text;
        $tile_size = $small_size / 1.5;
        $tile_color = imagecolorallocatealpha($resource, 255, 255, 255, 15);
        for ($tx = 20; $tx < $width; $tx += ($width/3)) {
            for ($ty = 30; $ty < $height; $ty += ($height/4)) {
                imagettftext($resource, $tile_size, 45, $tx, $ty, $tile_color, $font_path, $tile_text);
            }
        }
    } else {
        // Fallback to basic GD font
        $font_size = 5;
        $x = ($width / 2) - (strlen($site_text) * imagefontwidth($font_size) / 2);
        $y = ($height / 2) - (imagefontheight($font_size) / 2);
        imagestring($resource, $font_size, $x, $y, $site_text, $white);
    }
}

/**
 * Calculate Deal Safety Score (Feature 08)
 */
function calculate_safety_score($user, $ad) {
    $score = 0;
    if (($user['verification_tier'] ?? '') === 'nin_verified' || ($user['verification_tier'] ?? '') === 'business_verified') {
        $score += 35;
    } elseif ($user['is_verified'] ?? 0) {
        $score += 15;
    }
    $score += 15; // History base
    if (strlen($ad['description'] ?? '') > 100) $score += 10;
    $score += 10; // Photos present
    $created = strtotime($user['created_at'] ?? 'now');
    if (time() - $created > 7 * 24 * 3600) $score += 20;
    return min(100, $score);
}

/**
 * Check for duplicate listings by the same user (Feature 03 velocity check)
 */
function is_duplicate_listing($pdo, $user_id, $title, $description) {
    $stmt = $pdo->prepare("SELECT title, description FROM ads WHERE user_id = ? AND status = 'active'");
    $stmt->execute([$user_id]);
    $existing = $stmt->fetchAll();
    foreach ($existing as $ad) {
        similar_text(strtolower($title), strtolower($ad['title']), $title_sim);
        similar_text(strtolower($description), strtolower($ad['description']), $desc_sim);
        if ($title_sim > 85 || $desc_sim > 85) return true;
    }
    return false;
}
