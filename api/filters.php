<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../inc/functions.php';
require_once __DIR__ . '/../inc/filters_config.php';

header('Content-Type: application/json');

$cat_id = (int)($_GET['cat_id'] ?? 0);

if ($cat_id <= 0) {
    echo json_encode([]);
    die();
}

$stmt = $pdo->prepare("SELECT name FROM categories WHERE id = ?");
$stmt->execute([$cat_id]);
$cat = $stmt->fetch();

if (!$cat) {
    echo json_encode([]);
    die();
}

$filters = get_category_filters($cat['name']);
echo json_encode($filters);
