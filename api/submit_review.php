<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../inc/functions.php';
require_once __DIR__ . '/../inc/user_auth.php';

header('Content-Type: application/json');

if (!is_user_logged_in()) {
    echo json_encode(['success' => false, 'message' => 'Please login to submit a review.']);
    exit;
}

$reviewer_id = $_SESSION['user_id'];
$seller_id = (int)($_POST['seller_id'] ?? 0);
$stars = (int)($_POST['stars'] ?? 0);
$body = trim($_POST['body'] ?? '');

if ($seller_id <= 0 || $stars < 1 || $stars > 5 || empty($body)) {
    echo json_encode(['success' => false, 'message' => 'All fields are required.']);
    exit;
}

if ($reviewer_id === $seller_id) {
    echo json_encode(['success' => false, 'message' => 'You cannot review yourself.']);
    exit;
}

try {
    $stmt = $pdo->prepare("INSERT INTO reviews (reviewer_id, seller_id, stars, body) VALUES (?, ?, ?, ?)");
    $stmt->execute([$reviewer_id, $seller_id, $stars, $body]);
    $review_id = $pdo->lastInsertId();

    // Notify seller
    $stmt = $pdo->prepare("INSERT INTO notifications (user_id, title, message, link) VALUES (?, ?, ?, ?)");
    $msg = "You received a new " . $stars . "-star review.";
    $stmt->execute([$seller_id, "New Review Received", $msg, "/profile.php"]);

    echo json_encode(['success' => true, 'message' => 'Review submitted successfully.']);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
