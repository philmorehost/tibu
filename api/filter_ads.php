<?php
require_once __DIR__ . '/../config/config.php';
if (session_status() === PHP_SESSION_NONE) session_start();

require_once __DIR__ . '/../inc/functions.php';

header('Content-Type: application/json');

$cat_id = (int)($_GET['cat_id'] ?? 0);
$state_id = (int)($_GET['state_id'] ?? 0);
$lga_id = (int)($_GET['lga_id'] ?? 0);
$min_price = (float)($_GET['min_price'] ?? 0);
$max_price = (float)($_GET['max_price'] ?? 0);
$type = $_GET['type'] ?? 'all';
$extra = $_GET['extra'] ?? [];
$q = $_GET['q'] ?? '';

$query = "SELECT a.*, (SELECT image_path FROM ad_images WHERE ad_id = a.id AND is_main = 1 LIMIT 1) as image, s.name as state_name, c.name as cat_name
          FROM ads a
          JOIN states s ON a.state_id = s.id
          JOIN categories c ON a.cat_id = c.id
          JOIN users u ON a.user_id = u.id
          WHERE a.status = 'active' AND u.is_suspended = 0";

$params = [];

if ($cat_id) {
    $query .= " AND (a.cat_id = ? OR a.cat_id IN (SELECT id FROM categories WHERE parent_id = ?))";
    $params[] = $cat_id;
    $params[] = $cat_id;
}

if ($q) {
    $query .= " AND (a.title LIKE ? OR a.description LIKE ?)";
    $params[] = "%$q%";
    $params[] = "%$q%";
}

if ($state_id) {
    $query .= " AND a.state_id = ?";
    $params[] = $state_id;
}
if ($lga_id) {
    $query .= " AND a.lga_id = ?";
    $params[] = $lga_id;
}
if ($min_price) {
    $query .= " AND a.price >= ?";
    $params[] = $min_price;
}
if ($max_price) {
    $query .= " AND a.price <= ?";
    $params[] = $max_price;
}

if ($type === 'sale') {
    $query .= " AND (a.listing_type = 'for_sale' OR a.listing_type = 'for_sale_or_swap')";
} elseif ($type === 'swap') {
    $query .= " AND (a.listing_type = 'for_swap' OR a.listing_type = 'for_sale_or_swap')";
}

if ($extra) {
    require_once __DIR__ . '/../inc/filters_config.php';
    $cat_name = "";
    if ($cat_id) {
        $stmt_cat = $pdo->prepare("SELECT name FROM categories WHERE id = ?");
        $stmt_cat->execute([$cat_id]);
        $cat_name = $stmt_cat->fetchColumn() ?: "";
    }
    $valid_filters = get_category_filters($cat_name);

    foreach ($extra as $key => $value) {
        if (empty($value)) continue;

        if (isset($valid_filters[$key]) || $key === 'verified_seller' || $key === 'trusted_agent' || $key === 'discount') {
            if ($key === 'verified_seller' && $value === 'Verified sellers only') {
                $query .= " AND (u.is_verified = 1 OR u.verification_tier IN ('nin_verified', 'business_verified'))";
            } elseif ($key === 'trusted_agent' && $value === 'Yes') {
                $query .= " AND (u.is_verified = 1 OR u.verification_tier IN ('nin_verified', 'business_verified'))";
            } elseif ($key === 'discount' && $value === 'With discount') {
                $query .= " AND JSON_UNQUOTE(JSON_EXTRACT(a.ad_data, '$.\"discount\"')) = '1'";
            } else {
                if (is_array($value)) {
                    $json_placeholders = implode(',', array_fill(0, count($value), '?'));
                    $query .= " AND JSON_UNQUOTE(JSON_EXTRACT(a.ad_data, '$.\"$key\"')) IN ($json_placeholders)";
                    foreach ($value as $v) {
                        $params[] = $v;
                    }
                } else {
                    $query .= " AND JSON_UNQUOTE(JSON_EXTRACT(a.ad_data, '$.\"$key\"')) = ?";
                    $params[] = $value;
                }
            }
        } elseif (strpos($key, 'min_') === 0 || strpos($key, 'max_') === 0) {
            $base_key = substr($key, 4);
            if (isset($valid_filters[$base_key]) || $base_key === 'price') {
                $op = (strpos($key, 'min_') === 0) ? '>=' : '<=';
                if ($base_key === 'price') {
                    $query .= " AND a.price $op ?";
                } else {
                    $query .= " AND CAST(JSON_UNQUOTE(JSON_EXTRACT(a.ad_data, '$.\"$base_key\"')) AS DECIMAL(15,2)) $op ?";
                }
                $params[] = (float)$value;
            }
        }
    }
}

$query .= " ORDER BY CASE a.ad_tier
            WHEN 'diamond' THEN 1
            WHEN 'vip' THEN 2
            WHEN 'premium' THEN 3
            ELSE 4 END ASC, a.bumped_at DESC LIMIT 40";

try {
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $ads = $stmt->fetchAll();
} catch (Exception $e) {
    error_log("Filter Ads API error: " . $e->getMessage());
    $ads = [];
}

// Process ads for frontend
foreach ($ads as &$ad) {
    $ad['url'] = generate_ad_url($ad);
    $ad['tier_info'] = get_tier_info($ad['ad_tier'] ?? 'free');
    $ad['formatted_price'] = number_format($ad['price']);
    $ad['image_url'] = $ad['image'] ? '/uploads/ads/' . $ad['image'] : 'https://placehold.co/400x300?text=No+Image';
}

echo json_encode(['success' => true, 'ads' => $ads]);

