<?php
require_once __DIR__ . '/../config/config.php';
if (session_status() === PHP_SESSION_NONE) session_start();

require_once __DIR__ . '/../inc/functions.php';

header('Content-Type: application/json');

$id_token = $_POST['id_token'] ?? '';

if (empty($id_token)) {
    echo json_encode(['success' => false, 'message' => 'Token missing']);
    exit;
}

// Verify with Google's tokeninfo endpoint
$url = "https://oauth2.googleapis.com/tokeninfo?id_token=" . urlencode($id_token);
$response = file_get_contents($url);
if ($response === false) {
    echo json_encode(['success' => false, 'message' => 'Verification request failed']);
    exit;
}

$payload = json_decode($response, true);

if (isset($payload['error'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid token: ' . $payload['error_description']]);
    exit;
}

// Check if client ID matches
$stmt = $pdo->prepare("SELECT setting_value FROM settings WHERE setting_key = 'google_client_id'");
$stmt->execute();
$google_client_id = $stmt->fetchColumn();

if ($payload['aud'] !== $google_client_id) {
    echo json_encode(['success' => false, 'message' => 'Client ID mismatch']);
    exit;
}

$email = $payload['email'];
$name = $payload['name'];

// Find or create user
$stmt = $pdo->prepare("SELECT id, full_name, phone, is_suspended FROM users WHERE email = ?");
$stmt->execute([$email]);
$user = $stmt->fetch();

$needs_phone = false;

if ($user) {
    if ($user['is_suspended']) {
        echo json_encode(['success' => false, 'message' => 'Account suspended']);
        exit;
    }
    if (empty($user['phone'])) {
        $needs_phone = true;
    }
} else {
    // Create new user (social login users are verified by default)
    $stmt = $pdo->prepare("INSERT INTO users (full_name, email, is_verified) VALUES (?, ?, 1)");
    $stmt->execute([$name, $email]);
    $user = ['id' => $pdo->lastInsertId(), 'full_name' => $name];
    $needs_phone = true;
}

$_SESSION['user_id'] = $user['id'];
$_SESSION['user_name'] = $user['full_name'];

echo json_encode(['success' => true, 'needs_phone' => $needs_phone]);

