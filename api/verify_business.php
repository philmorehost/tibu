<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../inc/functions.php';
if (session_status() === PHP_SESSION_NONE) session_start();

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    return;
}

$user_id = $_SESSION['user_id'];
$cac_number = $_POST['cac_number'] ?? '';
$director_nin = $_POST['director_nin'] ?? '';

if (empty($cac_number) || strlen($director_nin) !== 11) {
    echo json_encode(['success' => false, 'message' => 'Invalid CAC or NIN format.']);
    return;
}

// Fetch site name for response
$stmt_site = $pdo->query("SELECT setting_value FROM settings WHERE setting_key = 'site_name'");
$site_name = $stmt_site->fetchColumn() ?: 'Classifieds';

// Simulate CAC/NIMC validation
$stmt = $pdo->prepare("UPDATE users SET verification_tier = 'business_verified', is_verified = 1 WHERE id = ?");
$stmt->execute([$user_id]);

echo json_encode([
    'success' => true,
    'message' => 'Business verification submitted for review. ' . $site_name . ' Business badge will be active shortly.'
]);
