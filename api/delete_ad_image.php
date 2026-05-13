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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_SESSION['user_id'];
    $image_id = (int)($_POST['image_id'] ?? 0);

    if ($image_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid image ID']);
        exit;
    }

    try {
        // Verify ownership through join
        $stmt = $pdo->prepare("SELECT ai.*, a.user_id FROM ad_images ai JOIN ads a ON ai.ad_id = a.id WHERE ai.id = ?");
        $stmt->execute([$image_id]);
        $image = $stmt->fetch();

        if (!$image || $image['user_id'] != $user_id) {
            echo json_encode(['success' => false, 'message' => 'Access denied']);
            exit;
        }

        // Delete from disk
        $file_path = __DIR__ . '/../uploads/ads/' . $image['image_path'];
        if (file_exists($file_path)) {
            unlink($file_path);
        }

        // Delete from DB
        $stmt = $pdo->prepare("DELETE FROM ad_images WHERE id = ?");
        $stmt->execute([$image_id]);

        // Clean up perceptual hash to allow re-upload
        $stmt_h = $pdo->prepare("DELETE FROM image_hashes WHERE ad_id = ? AND image_path = ?");
        $stmt_h->execute([$image['ad_id'], $image['image_path']]);

        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

