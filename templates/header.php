<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../inc/functions.php';
require_once __DIR__ . '/../inc/user_auth.php';

// Fetch global settings for site name, etc.
if (isset($pdo)) {
    $stmt = $pdo->query("SELECT setting_key, setting_value FROM settings");
    $settings = [];
    while ($row = $stmt->fetch()) {
        $settings[$row['setting_key']] = $row['setting_value'];
    }
}

// Global IP Blacklist/Country Check
if (isset($pdo)) {
    require_once __DIR__ . '/../inc/security.php';

    // Run migrations to ensure schema is up to date
    require_once __DIR__ . '/../inc/update_schema.php';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo h($page_title ?? ($settings['site_name'] ?? 'Classifieds')); ?></title>
    <meta name="description" content="<?php echo h($page_desc ?? ($settings['meta_description'] ?? '')); ?>">
    <meta name="keywords" content="<?php echo h($page_keywords ?? ($settings['meta_keywords'] ?? '')); ?>">

    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: {
                            50: '#f0f7ff',
                            100: '#e0effe',
                            200: '#bae0fd',
                            300: '#7cc7fb',
                            400: '#38a9f8',
                            500: '#1a7fe8',
                            600: '#0966ce',
                            700: '#0a52a6',
                            800: '#0d4687',
                            900: '#103b71',
                            950: '#0b264b',
                        },
                    }
                }
            }
        }
    </script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="manifest" href="/manifest.json">
    <?php
    $site_initial = mb_substr($settings['site_name'] ?? 'Classifieds', 0, 1);
    $favicon_svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><rect width="100" height="100" rx="20" fill="#1a7fe8"/><text y="50%" x="50%" text-anchor="middle" dominant-baseline="central" font-size="60" fill="white" font-family="sans-serif" font-weight="bold">' . htmlspecialchars($site_initial) . '</text></svg>';
    ?>
    <link rel="icon" href="data:image/svg+xml,<?php echo rawurlencode($favicon_svg); ?>">
    <style>
        .scrollbar-hide::-webkit-scrollbar { display: none; }
        .scrollbar-hide { -ms-overflow-style: none; scrollbar-width: none; }
        @keyframes slideUp {
            from { transform: translateY(100%); }
            to { transform: translateY(0); }
        }
        .animate-slide-up {
            animation: slideUp 0.3s ease-out forwards;
        }

        /* Jiji-style Fit-to-Frame Image Containers */
        .fit-to-frame {
            position: relative;
            background-color: #f9fafb;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }
        .fit-to-frame img {
            max-width: 100%;
            max-height: 100%;
            width: auto;
            height: auto;
            object-fit: contain;
            position: relative;
            z-index: 2;
        }
        .fit-to-frame::before {
            content: "";
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-image: var(--bg-image);
            background-size: cover;
            background-position: center;
            filter: blur(20px) brightness(0.9);
            opacity: 0.3;
            z-index: 1;
        }
    </style>

    <!-- Open Graph / WhatsApp Integration (Feature 10) -->
    <meta property="og:type" content="website">
    <meta property="og:url" content="<?php echo (isset($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] === "on" ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]"; ?>">
    <meta property="og:title" content="<?php echo h($page_title ?? ($settings["site_name"] ?? "Classifieds")); ?>">
    <meta property="og:description" content="<?php echo h($page_desc ?? ($settings["meta_description"] ?? "")); ?>">
    <meta property="og:image" content="<?php echo isset($ad["image"]) ? "/uploads/ads/".$ad["image"] : "/assets/img/og-image.png"; ?>">
</head>
<body class="bg-gray-100 min-h-screen flex flex-col">
    <nav class="bg-white shadow-sm border-b sticky top-0 z-50">
        <div class="container mx-auto px-4 py-3 flex justify-between items-center">
            <a href="/" class="flex items-center gap-3">
                <?php if (!empty($settings['site_logo'])): ?>
                    <img src="/uploads/branding/<?php echo h($settings['site_logo']); ?>" alt="<?php echo h($settings['site_name'] ?? 'Logo'); ?>" class="h-12 md:h-20 w-auto object-contain">
                <?php else: ?>
                    <div class="flex flex-col">
                        <span class="text-lg md:text-2xl font-black text-primary-600 leading-none"><?php echo h($settings['site_name'] ?? 'Classifieds'); ?></span>
                        <span class="text-[8px] md:text-[9px] font-bold text-gray-400 uppercase tracking-widest">Buy, Sell & Swap</span>
                    </div>
                <?php endif; ?>
            </a>

            <div class="flex-1 max-w-2xl mx-8 hidden md:block">
                <form action="/search.php" method="GET" class="flex items-center bg-gray-100 rounded-xl overflow-hidden border-2 border-transparent focus-within:border-primary-500 focus-within:bg-white transition-all shadow-sm">
                    <div class="flex items-center px-4 border-r border-gray-200 gap-2">
                        <i class="fas fa-map-marker-alt text-primary-600 text-sm"></i>
                        <select name="state_id" class="bg-transparent text-xs font-bold text-gray-600 outline-none py-3 cursor-pointer">
                            <option value="">All Nigeria</option>
                            <?php
                            $h_states = $pdo->query("SELECT id, name FROM states ORDER BY name ASC")->fetchAll();
                            foreach ($h_states as $hs) echo "<option value='{$hs['id']}'>".h($hs['name'])."</option>";
                            ?>
                        </select>
                    </div>
                    <input type="text" name="q" placeholder="Search for anything..." class="flex-1 bg-transparent p-3 text-sm font-bold text-gray-700 outline-none" required>
                    <button type="submit" class="bg-primary-600 text-white px-6 py-3 hover:bg-primary-700 transition">
                        <i class="fas fa-search"></i>
                    </button>
                </form>
            </div>

            <div class="flex items-center space-x-2 md:space-x-4">
                <?php if (is_user_logged_in()): ?>
                    <a href="/profile" class="text-gray-600 hover:text-primary-600 font-bold flex flex-col items-center">
                        <i class="fas fa-user text-xl md:text-base md:mr-1"></i>
                        <span class="hidden md:inline">Profile</span>
                    </a>
                    <a href="/post-ad" class="bg-yellow-500 text-white p-2 md:px-4 md:py-2 rounded-lg font-bold hover:bg-yellow-600 transition flex items-center justify-center">
                        <i class="fas fa-plus text-xl md:text-base md:mr-1"></i>
                        <span class="hidden md:inline">SELL</span>
                    </a>
                <?php else: ?>
                    <a href="/login" class="text-gray-600 hover:text-primary-600 font-bold flex flex-col items-center">
                        <i class="fas fa-sign-in-alt text-xl md:text-base md:mr-1"></i>
                        <span class="hidden md:inline">Sign In</span>
                    </a>
                    <a href="/register" class="text-primary-600 font-bold md:border-2 md:border-primary-600 p-2 md:px-4 md:py-1 rounded-lg hover:bg-primary-600 hover:text-white transition flex items-center justify-center">
                        <i class="fas fa-user-plus text-xl md:text-base md:mr-1"></i>
                        <span class="hidden md:inline">Registration</span>
                    </a>
                    <!-- New Post AD Icon for Guest Mobile -->
                    <a href="/post-ad" class="md:hidden text-yellow-500 p-2 flex flex-col items-center">
                        <i class="fas fa-plus-circle text-2xl"></i>
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </nav>
<div class="bg-primary-600 text-white py-3 shadow-inner">
    <div class="container mx-auto px-4 flex items-center justify-center gap-3">
        <i class="fas fa-shield-check text-xl"></i>
        <p class="text-[10px] md:text-xs font-black uppercase tracking-[2px]"><?php echo h($settings['site_name'] ?? 'Classifieds'); ?> Verified Sellers have completed NIN + live identity verification. Always look for the <span class="text-yellow-400">Verified Badge</span>.</p>
    </div>
</div>
