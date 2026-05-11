<?php
require_once __DIR__ . '/../config/config.php';

$size = isset($_GET['size']) ? (int)$_GET['size'] : 512;
$stmt = $pdo->query("SELECT setting_value FROM settings WHERE setting_key = 'site_logo'");
$logo = $stmt->fetchColumn();

header('Content-Type: image/png');

if ($logo && file_exists(__DIR__ . '/../uploads/branding/' . $logo)) {
    $logo_path = __DIR__ . '/../uploads/branding/' . $logo;
    list($w, $h, $type) = getimagesize($logo_path);

    $img = imagecreatetruecolor($size, $size);
    // Transparent background
    imagealphablending($img, false);
    imagesavealpha($img, true);
    $transparent = imagecolorallocatealpha($img, 255, 255, 255, 127);
    imagefill($img, 0, 0, $transparent);

    // Load logo
    switch($type) {
        case IMAGETYPE_PNG: $src = imagecreatefrompng($logo_path); break;
        case IMAGETYPE_JPEG: $src = imagecreatefromjpeg($logo_path); break;
        default: die();
    }

    // Resize and center
    $scale = min($size/$w, $size/$h) * 0.8;
    $new_w = (int)($w * $scale);
    $new_h = (int)($h * $scale);
    $x = (int)(($size - $new_w) / 2);
    $y = (int)(($size - $new_h) / 2);

    imagecopyresampled($img, $src, $x, $y, 0, 0, $new_w, $new_h, $w, $h);
    imagepng($img);
    imagedestroy($img);
} else {
    // Fallback icon
    $img = imagecreatetruecolor($size, $size);
    $blue = imagecolorallocate($img, 26, 127, 232);
    imagefill($img, 0, 0, $blue);
    imagepng($img);
}
