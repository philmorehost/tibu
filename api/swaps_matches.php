<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../inc/functions.php';

header('Content-Type: application/json');

$ad_id = (int)($_GET['id'] ?? 0);
if (!$ad_id) {
    echo json_encode([]);
    return;
}

// Fetch current ad details
$stmt = $pdo->prepare("SELECT * FROM ads WHERE id = ?");
$stmt->execute([$ad_id]);
$ad = $stmt->fetch();

if (!$ad || $ad['listing_type'] === 'for_sale') {
    echo json_encode([]);
    return;
}

// Matching Algorithm:
// item_wanted_category = item_offered_category (simplified here as same cat_id)
// location within same state (simplified proxy for 50km)
// value within 35% range
$min_val = $ad['price'] * 0.65;
$max_val = $ad['price'] * 1.35;

$stmt = $pdo->prepare("
    SELECT a.*, u.is_verified, u.verification_tier,
    (SELECT image_path FROM ad_images WHERE ad_id = a.id AND is_main = 1 LIMIT 1) as image
    FROM ads a
    JOIN users u ON a.user_id = u.id
    WHERE a.cat_id = ?
    AND a.state_id = ?
    AND a.price BETWEEN ? AND ?
    AND a.id != ?
    AND a.listing_type != 'for_sale'
    AND a.status = 'active'
    ORDER BY a.created_at DESC
    LIMIT 10
");
$stmt->execute([$ad['cat_id'], $ad['state_id'], $min_val, $max_val, $ad_id]);
$matches = $stmt->fetchAll();

echo json_encode($matches);
