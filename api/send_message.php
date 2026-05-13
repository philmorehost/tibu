<?php
require_once __DIR__ . '/../config/config.php'`r`nif (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../inc/functions.php';
require_once __DIR__ . '/../inc/user_auth.php';

header('Content-Type: application/json');

if (!is_user_logged_in()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_SESSION['user_id'];
    $ad_id = (int)($_POST['ad_id'] ?? 0);
    $receiver_id = (int)($_POST['receiver_id'] ?? 0);
    $message = trim($_POST['message'] ?? '');

    if ($ad_id <= 0 || $receiver_id <= 0 || empty($message)) {
        echo json_encode(['success' => false, 'message' => 'Invalid data']);
        exit;
    }

    try {
        $stmt = $pdo->prepare("INSERT INTO messages (sender_id, receiver_id, ad_id, message) VALUES (?, ?, ?, ?)");
        $stmt->execute([$user_id, $receiver_id, $ad_id, $message]);
        $msg_id = $pdo->lastInsertId();

        echo json_encode([
            'success' => true,
            'message_id' => $msg_id,
            'data' => [
                'id' => $msg_id,
                'sender_id' => $user_id,
                'message' => h($message),
                'created_at' => date('H:i')
            ]
        ]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

