<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../inc/functions.php';

if (session_status() === PHP_SESSION_NONE) session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$user_id = $_SESSION['user_id'];
$id = (int)($_POST['id'] ?? 0);
$status = $_POST['status'] ?? '';

if (!in_array($status, ['accepted', 'declined'])) {
    echo json_encode(['success' => false, 'error' => 'Invalid status']);
    exit;
}

try {
    // Verify receiver ownership of the proposal
    $stmt = $pdo->prepare("SELECT sp.ad_id, sp.offered_ad_id, sp.sender_id FROM swap_proposals sp WHERE sp.id = ? AND sp.receiver_id = ?");
    $stmt->execute([$id, $user_id]);
    $prop = $stmt->fetch();

    if ($prop) {
        $stmt = $pdo->prepare("UPDATE swap_proposals SET status = ? WHERE id = ?");
        $stmt->execute([$status, $id]);

        if ($status == 'accepted') {
            // Mark both ads as swapped (not active anymore)
            $stmt = $pdo->prepare("UPDATE ads SET status = 'swapped' WHERE id IN (?, ?)");
            $stmt->execute([$prop['ad_id'], $prop['offered_ad_id']]);

            // Decline all other pending proposals for these ads
            $stmt = $pdo->prepare("UPDATE swap_proposals SET status = 'declined' WHERE (ad_id IN (?, ?) OR offered_ad_id IN (?, ?)) AND id != ? AND status = 'pending'");
            $stmt->execute([$prop['ad_id'], $prop['offered_ad_id'], $prop['ad_id'], $prop['offered_ad_id'], $id]);
        }

        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Proposal not found']);
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => 'Database error']);
}
