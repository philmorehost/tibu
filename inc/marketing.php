<?php
/**
 * Automated Marketing System - Recommendations Email
 */

function send_recommendations_email($user_id, $pdo, $settings) {
    $stmt = $pdo->prepare("SELECT full_name, email FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();

    if (!$user) return;

    // Get last 5 search history
    $history = $pdo->prepare("SELECT keyword, cat_id FROM search_history WHERE user_id = ? ORDER BY created_at DESC LIMIT 5");
    $history->execute([$user_id]);
    $items = $history->fetchAll();

    if (empty($items)) return;

    // Logic to pick ads based on history
    $rec_ads = [];
    foreach ($items as $item) {
        $q = "SELECT a.*, (SELECT image_path FROM ad_images WHERE ad_id = a.id AND is_main = 1 LIMIT 1) as image, c.name as cat_name
              FROM ads a JOIN categories c ON a.cat_id = c.id
              WHERE a.status = 'active' AND a.user_id != ?";
        $params = [$user_id];

        if ($item['cat_id']) {
            $q .= " AND a.cat_id = ?";
            $params[] = $item['cat_id'];
        } else {
            $q .= " AND a.title LIKE ?";
            $params[] = "%" . $item['keyword'] . "%";
        }

        $q .= " ORDER BY RAND() LIMIT 2";
        $stmt = $pdo->prepare($q);
        $stmt->execute($params);
        $res = $stmt->fetchAll();
        $rec_ads = array_merge($rec_ads, $res);
    }

    // De-duplicate and limit
    $unique_ads = [];
    $seen_ids = [];
    foreach ($rec_ads as $ad) {
        if (!in_array($ad['id'], $seen_ids)) {
            $unique_ads[] = $ad;
            $seen_ids[] = $ad['id'];
        }
    }
    $unique_ads = array_slice($unique_ads, 0, 6);

    if (empty($unique_ads)) return;

    // Email Design (HTML)
    $site_name = $settings['site_name'] ?? 'Classifieds';
    $subject = "Handpicked for you: Top deals on $site_name";

    $message = "
    <div style='font-family: sans-serif; max-width: 600px; margin: auto; border: 1px solid #eee; padding: 20px;'>
        <h2 style='color: #1A7FE8; text-align: center;'>$site_name Recommendations</h2>
        <p>Hi {$user['full_name']}, we found some items you might like based on your recent activity:</p>
        <div style='display: grid; grid-template-columns: 1fr 1fr; gap: 20px;'>";

    foreach ($unique_ads as $ad) {
        $img = $ad['image'] ? "http://".$_SERVER['HTTP_HOST']."/uploads/ads/".$ad['image'] : "https://placehold.co/400x300?text=No+Image";
        $url = "http://".$_SERVER['HTTP_HOST']."/ad.php?id=".$ad['id'];
        $message .= "
            <div style='border: 1px solid #f0f0f0; border-radius: 10px; overflow: hidden;'>
                <img src='$img' style='width: 100%; height: 150px; object-fit: cover;'>
                <div style='padding: 10px;'>
                    <h4 style='margin: 0 0 5px 0; font-size: 14px;'>{$ad['title']}</h4>
                    <p style='color: #1A7FE8; font-weight: bold; margin: 0;'>₦".number_format($ad['price'])."</p>
                    <a href='$url' style='display: inline-block; margin-top: 10px; padding: 5px 10px; background: #1A7FE8; color: #fff; text-decoration: none; border-radius: 5px; font-size: 12px;'>View Details</a>
                </div>
            </div>";
    }

    $message .= "
        </div>
        <p style='text-align: center; font-size: 12px; color: #999; margin-top: 30px;'>
            You received this because of your search history on $site_name.
        </p>
    </div>";

    // Simulate sending email (log it for now since we don't have real SMTP)
    error_log("Recommendations email triggered for {$user['email']} with " . count($unique_ads) . " ads.");
    // In real app: mail($user['email'], $subject, $message, $headers);
}
