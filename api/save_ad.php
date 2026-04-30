<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../inc/functions.php';
require_once __DIR__ . '/../inc/user_auth.php';

header('Content-Type: application/json');

if (!is_user_logged_in()) {
    echo json_encode(['success' => false, 'message' => 'Please login to save ads']);
    exit;
}

$user_id = $_SESSION['user_id'];
$ad_id = (int)($_GET['ad_id'] ?? 0);

if ($ad_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid ad']);
    exit;
}

// Toggle logic
$stmt = $pdo->prepare("SELECT id FROM saved_ads WHERE user_id = ? AND ad_id = ?");
$stmt->execute([$user_id, $ad_id]);
$existing = $stmt->fetch();

if ($existing) {
    $pdo->prepare("DELETE FROM saved_ads WHERE id = ?")->execute([$existing['id']]);
    $saved = false;
} else {
    $pdo->prepare("INSERT INTO saved_ads (user_id, ad_id) VALUES (?, ?)")->execute([$user_id, $ad_id]);
    $saved = true;
}

$count = $pdo->prepare("SELECT COUNT(*) FROM saved_ads WHERE user_id = ?");
$count->execute([$user_id]);

echo json_encode(['success' => true, 'saved' => $saved, 'count' => $count->fetchColumn()]);
