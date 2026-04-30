<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../inc/functions.php';
if (session_status() === PHP_SESSION_NONE) session_start();

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    return;
}

$seller_id = $_SESSION['user_id'];
$review_id = (int)($_POST['review_id'] ?? 0);

$stmt = $pdo->prepare("SELECT seller_id, challenged FROM reviews WHERE id = ?");
$stmt->execute([$review_id]);
$review = $stmt->fetch();

if (!$review || $review['seller_id'] != $seller_id) {
    echo json_encode(['success' => false, 'message' => 'Access denied.']);
    return;
}

if ($review['challenged']) {
    echo json_encode(['success' => false, 'message' => 'Review already challenged.']);
    return;
}

$deadline = date('Y-m-d H:i:s', strtotime('+24 hours'));
$stmt = $pdo->prepare("UPDATE reviews SET challenged = 1 WHERE id = ?");
$stmt->execute([$review_id]);

$stmt = $pdo->prepare("INSERT INTO review_challenges (review_id, seller_id, deadline_at) VALUES (?, ?, ?)");
$stmt->execute([$review_id, $seller_id, $deadline]);

echo json_encode(['success' => true, 'message' => 'Challenge submitted. Reviewer has 24 hours to provide evidence.']);
