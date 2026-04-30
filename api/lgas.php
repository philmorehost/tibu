<?php
require_once __DIR__ . '/../config/config.php';

header('Content-Type: application/json');

if (isset($_GET['state_id'])) {
    $state_id = (int)$_GET['state_id'];
    $stmt = $pdo->prepare("SELECT id, name FROM lgas WHERE state_id = ? ORDER BY name ASC");
    $stmt->execute([$state_id]);
    $lgas = $stmt->fetchAll();
    echo json_encode($lgas);
} else {
    echo json_encode([]);
}
