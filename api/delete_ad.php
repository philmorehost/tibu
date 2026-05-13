<?php
require_once __DIR__ . '/../config/config.php';
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../inc/functions.php';
require_once __DIR__ . '/../inc/user_auth.php';

header('Content-Type: application/json');

if (!is_user_logged_in()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$ad_id = $_POST['ad_id'] ?? null;
if (!$ad_id) {
    echo json_encode(['success' => false, 'message' => 'Ad ID is required']);
    exit;
}

$user_id = $_SESSION['user_id'];

// Check if ad exists and belongs to the user
$stmt = $pdo->prepare("SELECT id, ad_tier FROM ads WHERE id = ? AND user_id = ?");
$stmt->execute([$ad_id, $user_id]);
$ad = $stmt->fetch();

if (!$ad) {
    echo json_encode(['success' => false, 'message' => 'Ad not found or unauthorized']);
    exit;
}

try {
    // Delete the ad. cascade should handle related images, payments, etc.
    $stmt = $pdo->prepare("DELETE FROM ads WHERE id = ?");
    $stmt->execute([$ad_id]);

    echo json_encode(['success' => true, 'message' => 'Ad deleted successfully']);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Failed to delete ad: ' . $e->getMessage()]);
}
