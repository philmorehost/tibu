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
$selfie_data = $_POST['selfie'] ?? '';
$token = $_POST['token'] ?? '';

// Simulate Facial Match (Smile Identity / AWS Rekognition)
$match_score = rand(85, 99);
$pass = $match_score >= 80;

if ($pass) {
    $stmt = $pdo->prepare("UPDATE users SET verification_tier = 'nin_verified', is_verified = 1, last_verified_at = NOW(), verification_fails = 0, locked_until = NULL WHERE id = ?");
    $stmt->execute([$user_id]);
    echo json_encode(['success' => true, 'score' => $match_score, 'message' => 'Identity verified successfully! Verified Seller badge activated.']);
} else {
    $pdo->exec("UPDATE users SET verification_fails = verification_fails + 1 WHERE id = $user_id");
    $stmt = $pdo->prepare("SELECT verification_fails FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $fails = $stmt->fetchColumn();

    if ($fails >= 3) {
        $locked_until = date('Y-m-d H:i:s', strtotime('+30 days'));
        $pdo->prepare("UPDATE users SET locked_until = ? WHERE id = ?")->execute([$locked_until, $user_id]);
    }

    echo json_encode(['success' => false, 'score' => $match_score, 'message' => 'Face match failed. You can retry after 24 hours.']);
}
