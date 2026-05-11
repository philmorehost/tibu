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

// Global Site Settings
$settings = [];
if (isset($pdo)) {
    try {
        $stmt = $pdo->query("SELECT setting_key, setting_value FROM settings");
        while ($row = $stmt->fetch()) {
            $settings[$row['setting_key']] = $row['setting_value'];
        }
    } catch (Exception $e) {
        // Fallback or log error
    }
}

/**
 * Sanitize output for XSS prevention
 */
function h($string) {
    return htmlspecialchars((string)($string ?? ''), ENT_QUOTES, 'UTF-8');
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
 * Record Search History for Personalized Recommendations
 */
/**
 * Truncate string by words
 */
function truncate_words($text, $limit = 70) {
    $text = strip_tags($text);
    $words = preg_split("/[\s]+/", $text, $limit + 1);
    if (count($words) > $limit) {
        array_pop($words);
        return implode(' ', $words) . "...";
    }
    return implode(' ', $words);
}

function record_search_history($cat_id = null, $keyword = null) {
    global $pdo;
    if (!$cat_id && !$keyword) return;

    if (isset($_SESSION['user_id'])) {
        $stmt = $pdo->prepare("INSERT INTO search_history (user_id, cat_id, keyword) VALUES (?, ?, ?)");
        $stmt->execute([$_SESSION['user_id'], $cat_id, $keyword]);
    } else {
        // Guest user - record in session
        if (!isset($_SESSION['search_history'])) {
            $_SESSION['search_history'] = [];
        }
        // Limit session history to last 5 entries
        array_unshift($_SESSION['search_history'], [
            'cat_id' => $cat_id,
            'keyword' => $keyword,
            'created_at' => date('Y-m-d H:i:s')
        ]);
        $_SESSION['search_history'] = array_slice($_SESSION['search_history'], 0, 5);
    }
}

/**
 * Image Upload & Processing (GD Library) - Enhanced with pHash & Watermark
 */
function process_image_upload($file_tmp, $target_dir, $max_width = 800, $user_id = 0, $ad_id = 0, $watermark = true) {
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

    $filename = md5(uniqid(rand(), true)) . ".jpg";
    $target_file = $target_dir . "/" . $filename;

    $new_width = (int)min($width, $max_width);
    $new_height = (int)(($height / $width) * $new_width);
    $tmp = imagecreatetruecolor($new_width, $new_height);
    imagecopyresampled($tmp, $src, 0, 0, 0, 0, $new_width, $new_height, $width, $height);

    // Apply Watermark to the resized image for better quality
    if ($watermark) {
        apply_site_watermark($tmp, $seller_info);
    }

    imagejpeg($tmp, $target_file, 85);

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
 * Jiji-style enhanced watermark
 */
function apply_site_watermark($resource, $seller_info = "") {
    global $pdo;
    $width = imagesx($resource);
    $height = imagesy($resource);
    
    // Improved Font Path Detection (Linux & Windows)
    $font_paths = [
        '/usr/share/fonts/truetype/dejavu/DejaVuSans-Bold.ttf',
        'C:\Windows\Fonts\arialbd.ttf',
        'C:\Windows\Fonts\arial.ttf',
        __DIR__ . '/../assets/fonts/DejaVuSans-Bold.ttf'
    ];
    
    $font_path = '';
    foreach ($font_paths as $path) {
        if (file_exists($path)) {
            $font_path = $path;
            break;
        }
    }

    // Attempt to fetch site name for watermark
    static $site_name = null;
    if ($site_name === null && $pdo) {
        try {
            $stmt = $pdo->query("SELECT setting_value FROM settings WHERE setting_key = 'site_name'");
            $site_name = $stmt->fetchColumn();
        } catch (Exception $e) {
            $site_name = "Tibung";
        }
    }

    $site_text = $site_name ?: "Tibung";

    // Allocate Colors
    $white = imagecolorallocate($resource, 255, 255, 255);
    $black = imagecolorallocate($resource, 0, 0, 0);
    $bg_alpha = imagecolorallocatealpha($resource, 0, 0, 0, 80); // Semi-transparent dark strip

    if ($font_path && function_exists('imagettftext')) {
        // 1. MAIN CENTERED WATERMARK (Site Name + Seller Info)
        // Increased font size: width / 4 (e.g. 200px for 800px image)
        $center_font_size = (int)($width / 5); 
        $center_color = imagecolorallocatealpha($resource, 255, 255, 255, 60); // More visible alpha
        
        // Calculate Site Name Bounding Box
        $bbox_site = imagettfbbox($center_font_size, 0, $font_path, $site_text);
        $sw = $bbox_site[2] - $bbox_site[0];
        $sh = $bbox_site[1] - $bbox_site[7];
        
        $cx = (int)(($width / 2) - ($sw / 2));
        $cy = (int)(($height / 2) + ($sh / 4)); // Shift up slightly for seller info
        
        // Draw Site Name
        imagettftext($resource, $center_font_size, 0, $cx, $cy, $center_color, $font_path, $site_text);
        
        // Draw Seller Info below Site Name if available
        if ($seller_info) {
            $seller_text = strtoupper($seller_info);
            $seller_font_size = (int)($center_font_size / 3);
            $bbox_seller = imagettfbbox($seller_font_size, 0, $font_path, $seller_text);
            $slw = $bbox_seller[2] - $bbox_seller[0];
            $slh = $bbox_seller[1] - $bbox_seller[7];
            
            $slx = (int)(($width / 2) - ($slw / 2));
            $sly = (int)($cy + $sh / 1.2); // Positioned nicely below the main site name
            
            // Draw a subtle "strip" background for the seller name
            $strip_padding = (int)($seller_font_size * 0.4);
            $strip_y1 = $sly - $slh - $strip_padding;
            $strip_y2 = $sly + $strip_padding;
            $strip_alpha = imagecolorallocatealpha($resource, 0, 0, 0, 90); // Subtle dark strip
            
            imagefilledrectangle($resource, (int)($width * 0.1), $strip_y1, (int)($width * 0.9), $strip_y2, $strip_alpha);
            
            // Draw Seller Name with a slight shadow for readability
            imagettftext($resource, $seller_font_size, 0, $slx + 1, $sly + 1, $black, $font_path, $seller_text);
            imagettftext($resource, $seller_font_size, 0, $slx, $sly, $white, $font_path, $seller_text);
        }

        // 2. Tiled Faint Watermarks (Keeping them but making them even fainter)
        $tile_size = (int)max(12, $width / 30);
        $tile_color = imagecolorallocatealpha($resource, 255, 255, 255, 110); 
        for ($tx = -50; $tx < $width + 100; $tx += ($width/2)) {
            for ($ty = -50; $ty < $height + 100; $ty += ($height/3)) {
                imagettftext($resource, $tile_size, 30, (int)$tx, (int)$ty, $tile_color, $font_path, $site_text);
            }
        }

        // 3. Bottom Branding Strip (Keep for professional look, but smaller)
        $bottom_text = "Posted on " . $site_text;
        $font_size = (int)max(14, $width / 25);
        $bbox = imagettfbbox($font_size, 0, $font_path, $bottom_text);
        $text_w = $bbox[2] - $bbox[0];
        $text_h = $bbox[1] - $bbox[7];

        $padding = (int)($height * 0.02);
        $rect_h = $text_h + ($padding * 2);

        imagefilledrectangle($resource, 0, $height - $rect_h, $width, $height, $bg_alpha);
        $tx = (int)(($width / 2) - ($text_w / 2));
        $ty = (int)($height - $padding);
        imagettftext($resource, $font_size, 0, $tx + 1, $ty + 1, $black, $font_path, $bottom_text);
        imagettftext($resource, $font_size, 0, $tx, $ty, $white, $font_path, $bottom_text);

    } else {
        // Fallback to basic GD font (positioned in middle)
        $main_text = $site_text . ($seller_info ? " - " . $seller_info : "");
        $font_size = 5;
        $tx = (int)(($width / 2) - (strlen($main_text) * imagefontwidth($font_size) / 2));
        $ty = (int)($height / 2);
        
        // Draw background for fallback
        imagefilledrectangle($resource, 0, $ty - 10, $width, $ty + 20, $bg_alpha);
        imagestring($resource, $font_size, $tx, $ty, $main_text, $white);
    }
}

/**
 * Calculate Deal Safety Score (Feature 08)
 */
function get_tier_info($tier) {
    switch ($tier) {
        case 'diamond':
            return ['label' => 'Diamond', 'color' => 'purple-600', 'bg' => 'bg-purple-600', 'badge' => 'bg-purple-600', 'border' => 'tier-diamond-border', 'shimmer' => true];
        case 'vip':
            return ['label' => 'VIP', 'color' => 'yellow-600', 'bg' => 'bg-yellow-500', 'badge' => 'bg-yellow-500', 'border' => 'tier-vip-border', 'shimmer' => false];
        case 'premium':
            return ['label' => 'Premium', 'color' => 'blue-600', 'bg' => 'bg-blue-600', 'badge' => 'bg-blue-600', 'border' => 'tier-premium-border', 'shimmer' => false];
        default:
            return null;
    }
}

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
