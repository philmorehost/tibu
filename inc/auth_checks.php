<?php
function can_user_list_swap($pdo, $user_id) {
    $stmt = $pdo->prepare("SELECT verification_tier FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $tier = $stmt->fetchColumn();
    return in_array($tier, ['nin_verified', 'business_verified']);
}

function get_user_post_count($pdo, $user_id) {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM ads WHERE user_id = ?");
    $stmt->execute([$user_id]);
    return (int)$stmt->fetchColumn();
}
