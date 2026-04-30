<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../inc/functions.php';

header('Content-Type: application/json');

$parent_id = (int)($_GET['parent_id'] ?? 0);

if ($parent_id <= 0) {
    echo json_encode([]);
    die();
}

$stmt = $pdo->prepare("SELECT c.*,
    (SELECT COUNT(*) FROM ads a
     JOIN users u ON a.user_id = u.id
     WHERE a.cat_id = c.id AND a.status = 'active' AND u.is_suspended = 0) as ad_count
    FROM categories c WHERE parent_id = ? ORDER BY name ASC");
$stmt->execute([$parent_id]);
$subs = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode($subs);
