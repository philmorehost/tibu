<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - <?php echo h($settings['site_name'] ?? 'Marketplace'); ?></title>
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
    <style>
        .admin-nav-link {
            display: flex;
            align-items: center;
            padding: 0.75rem 1rem;
            color: #64748b;
            font-weight: 700;
            font-size: 0.875rem;
            border-radius: 0.75rem;
            transition: all 0.3s;
        }
        .admin-nav-link:hover {
            background-color: #f0f7ff;
            color: #1a7fe8;
        }
        .admin-nav-link i {
            width: 1.25rem;
            margin-right: 0.75rem;
            color: #94a3b8;
            transition: color 0.3s;
        }
        .admin-nav-link:hover i {
            color: #1a7fe8;
        }
        .admin-nav-link.active {
            background-color: #1a7fe8;
            color: white;
            box-shadow: 0 10px 15px -3px rgba(26, 127, 232, 0.2);
        }
        .admin-nav-link.active i {
            color: white;
        }
    </style>
</head>
<body class="bg-gray-50 min-h-screen">
    <div class="flex">
        <!-- Sidebar -->
        <aside class="w-72 bg-white border-r border-gray-100 min-h-screen sticky top-0">
            <div class="p-8">
                <div class="flex flex-col">
                    <span class="text-xl font-black text-primary-600 leading-none"><?php echo h($settings['site_name'] ?? 'Classifieds'); ?></span>
                    <span class="text-[10px] font-black text-gray-400 uppercase tracking-[3px] mt-1">Admin Panel</span>
                </div>
            </div>

            <nav class="px-4 pb-8">
                <div class="space-y-6">
                    <div>
                        <span class="px-4 text-[10px] font-black text-gray-400 uppercase tracking-widest">Main Menu</span>
                        <ul class="mt-4 space-y-1">
                            <li><a href="index.php" class="admin-nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : ''; ?>"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                            <li><a href="ads.php" class="admin-nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'ads.php' ? 'active' : ''; ?>"><i class="fas fa-ad"></i> Ad Moderation</a></li>
                            <li><a href="users.php" class="admin-nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'users.php' ? 'active' : ''; ?>"><i class="fas fa-users"></i> User Management</a></li>
                        </ul>
                    </div>

                    <div>
                        <span class="px-4 text-[10px] font-black text-gray-400 uppercase tracking-widest">Content</span>
                        <ul class="mt-4 space-y-1">
                            <li><a href="pages.php" class="admin-nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'pages.php' ? 'active' : ''; ?>"><i class="fas fa-file-alt"></i> CMS Pages</a></li>
                            <li><a href="blog.php" class="admin-nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'blog.php' ? 'active' : ''; ?>"><i class="fas fa-newspaper"></i> Blog Posts</a></li>
                            <li><a href="categories.php" class="admin-nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'categories.php' ? 'active' : ''; ?>"><i class="fas fa-list"></i> Categories</a></li>
                            <li><a href="locations.php" class="admin-nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'locations.php' ? 'active' : ''; ?>"><i class="fas fa-map-marker-alt"></i> Locations</a></li>
                        </ul>
                    </div>

                    <div>
                        <span class="px-4 text-[10px] font-black text-gray-400 uppercase tracking-widest">System</span>
                        <ul class="mt-4 space-y-1">
                            <li><a href="security.php" class="admin-nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'security.php' ? 'active' : ''; ?>"><i class="fas fa-shield-alt"></i> Security & Firewall</a></li>
                            <li><a href="payments.php" class="admin-nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'payments.php' ? 'active' : ''; ?>"><i class="fas fa-receipt"></i> Revenue & Payments</a></li>
                            <li><a href="settings.php" class="admin-nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'settings.php' ? 'active' : ''; ?>"><i class="fas fa-cog"></i> Global Settings</a></li>
                        </ul>
                    </div>
                </div>

                <div class="mt-10 pt-6 border-t border-gray-50 px-4">
                    <a href="/logout" class="flex items-center text-xs font-black text-red-400 uppercase tracking-widest hover:text-red-600 transition">
                        <i class="fas fa-sign-out-alt mr-3"></i> Logout System
                    </a>
                </div>
            </nav>
        </aside>

        <!-- Main Content -->
        <main class="flex-1 p-8">
            <header class="flex justify-between items-center mb-8 border-b pb-4">
                <h1 class="text-3xl font-bold text-gray-800">Admin Dashboard</h1>
                <div class="flex items-center space-x-4">
                    <span class="text-gray-600">Welcome, <strong><?php echo h($_SESSION['admin_user']); ?></strong></span>
                    <?php
                    $my_ip = get_client_ip();
                    $stmt_ip = $pdo->prepare("SELECT status FROM ip_security WHERE ip_address = ?");
                    $stmt_ip->execute([$my_ip]);
                    if ($stmt_ip->fetchColumn() === 'whitelisted'):
                    ?>
                        <div class="flex items-center gap-1 bg-primary-50 px-3 py-1 rounded-full border border-primary-200">
                            <i class="fas fa-crown text-primary-500 text-xs"></i>
                            <span class="text-[9px] font-black text-primary-600 uppercase tracking-tighter">Trusted IP</span>
                        </div>
                    <?php endif; ?>
                </div>
            </header>
