<?php
require_once __DIR__ . '/../config/config.php';
if (session_status() === PHP_SESSION_NONE) session_start();

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    return;
}

$user_id = $_SESSION['user_id'];

// Aggregate stats for all ads of this user
$stmt = $pdo->prepare("
    SELECT a.id, a.title,
    (SELECT COUNT(*) FROM seller_analytics WHERE ad_id = a.id) as views,
    (SELECT COUNT(*) FROM messages WHERE ad_id = a.id) as enquiries
    FROM ads a
    WHERE a.user_id = ?
");
$stmt->execute([$user_id]);
$stats = $stmt->fetchAll();

echo json_encode($stats);
