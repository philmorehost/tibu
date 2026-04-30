<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../inc/functions.php';

header('Content-Type: application/json');

$cat_id = (int)($_GET['cat_id'] ?? 0);
$type = $_GET['type'] ?? 'all';

$user_id = (int)($_SESSION['user_id'] ?? 0);
$query = "SELECT a.*,
         (SELECT image_path FROM ad_images WHERE ad_id = a.id AND is_main = 1 LIMIT 1) as image,
         s.name as state_name, c.name as cat_name,
         (SELECT COUNT(*) FROM saved_ads WHERE user_id = $user_id AND ad_id = a.id) as is_saved
         FROM ads a
         JOIN states s ON a.state_id = s.id
         JOIN categories c ON a.cat_id = c.id
         JOIN users u ON a.user_id = u.id
         WHERE a.status = 'active' AND a.is_featured = 0 AND u.is_suspended = 0";

if ($cat_id > 0) {
    $query .= " AND a.cat_id = $cat_id";
}

if ($type === 'swap') {
    $query .= " AND (a.listing_type = 'for_swap' OR a.listing_type = 'for_sale_or_swap')";
}

$query .= " ORDER BY a.bumped_at DESC LIMIT 20";

$ads = $pdo->query($query)->fetchAll();

foreach ($ads as &$ad) {
    $ad['url'] = generate_ad_url($ad);
}

echo json_encode($ads);
