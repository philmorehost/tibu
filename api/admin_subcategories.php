<?php
require_once __DIR__ . '/../inc/auth.php';
require_once __DIR__ . '/../inc/functions.php';
require_admin();

header('Content-Type: application/json');

$parent_id = (int)($_GET['parent_id'] ?? 0);

if (!$parent_id) {
    echo json_encode(['error' => 'Parent ID required']);
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM categories WHERE parent_id = ? ORDER BY sort_order ASC, name ASC");
$stmt->execute([$parent_id]);
$subcategories = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode($subcategories);
