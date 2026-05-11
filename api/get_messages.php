<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../inc/functions.php';
require_once __DIR__ . '/../inc/user_auth.php';

header('Content-Type: application/json');

if (!is_user_logged_in()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$user_id = $_SESSION['user_id'];
$ad_id = (int)($_GET['ad_id'] ?? 0);
$partner_id = (int)($_GET['partner_id'] ?? 0);
$last_id = (int)($_GET['last_id'] ?? 0);

if ($ad_id <= 0 || $partner_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid parameters']);
    exit;
}

try {
    // Fetch new messages from the partner
    $stmt = $pdo->prepare("SELECT * FROM messages WHERE ad_id = ? AND sender_id = ? AND receiver_id = ? AND id > ? ORDER BY created_at ASC");
    $stmt->execute([$ad_id, $partner_id, $user_id, $last_id]);
    $new_messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (!empty($new_messages)) {
        // Mark as read
        $stmt_read = $pdo->prepare("UPDATE messages SET is_read = 1 WHERE ad_id = ? AND sender_id = ? AND receiver_id = ? AND id > ?");
        $stmt_read->execute([$ad_id, $partner_id, $user_id, $last_id]);
    }

    // Format messages for JS
    $formatted = [];
    foreach ($new_messages as $msg) {
        $formatted[] = [
            'id' => $msg['id'],
            'sender_id' => $msg['sender_id'],
            'message' => h($msg['message']),
            'created_at' => date('H:i', strtotime($msg['created_at']))
        ];
    }

    echo json_encode([
        'success' => true,
        'messages' => $formatted
    ]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
