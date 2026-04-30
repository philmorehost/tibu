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
$nin = $_POST['nin'] ?? '';

if (strlen($nin) !== 11 || !is_numeric($nin)) {
    echo json_encode(['success' => false, 'message' => 'Invalid NIN format. Must be 11 digits.']);
    return;
}

// Check for recent fails
$stmt = $pdo->prepare("SELECT verification_fails, locked_until FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

if ($user['locked_until'] && strtotime($user['locked_until']) > time()) {
    echo json_encode(['success' => false, 'message' => 'Account locked due to multiple failed attempts. Please wait.']);
    return;
}

// Simulate NIMC API call (Prembly / Smile Identity)
$kyc_reference = 'KYC-'.uniqid();
$id_photo_token = 'TOK-'.md5($nin);

$stmt = $pdo->prepare("UPDATE users SET nin_number = ?, kyc_reference = ? WHERE id = ?");
$stmt->execute([$nin, $kyc_reference, $user_id]);

echo json_encode([
    'success' => true,
    'kyc_reference' => $kyc_reference,
    'id_photo_token' => $id_photo_token,
    'message' => 'NIN record retrieved. Proceed to live selfie.'
]);
