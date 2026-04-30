<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../inc/functions.php';
require_once __DIR__ . '/../inc/user_auth.php';

header('Content-Type: application/json');

if (!is_user_logged_in()) {
    echo json_encode(['success' => false, 'message' => 'Please login to reply.']);
    exit;
}

$user_id = $_SESSION['user_id'];
$review_id = (int)($_POST['review_id'] ?? 0);
$reply_text = trim($_POST['reply_text'] ?? '');

if ($review_id <= 0 || empty($reply_text)) {
    echo json_encode(['success' => false, 'message' => 'Reply text is required.']);
    exit;
}

try {
    $stmt = $pdo->prepare("UPDATE reviews SET reply_text = ?, replied_at = NOW() WHERE id = ? AND seller_id = ?");
    $stmt->execute([$reply_text, $review_id, $user_id]);

    if ($stmt->rowCount() > 0) {
        echo json_encode(['success' => true, 'message' => 'Reply posted successfully.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Unable to post reply. Ensure you are the seller of this review.']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
