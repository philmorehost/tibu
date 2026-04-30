<?php
require_once __DIR__ . '/../inc/functions.php';
/**
 * Marketplace Installer
 * Stage 1: System Requirements Check
 */

if (session_status() === PHP_SESSION_NONE) session_start();

// Current PHP version
$php_version = PHP_VERSION;
$required_php_version = '8.0.0';
$php_version_ok = version_compare($php_version, $required_php_version, '>=');

// Required extensions
$required_extensions = [
    'pdo_mysql',
    'gd',
    'mbstring',
    'curl',
    'openssl',
    'session',
    'json'
];

$extension_status = [];
foreach ($required_extensions as $ext) {
    $extension_status[$ext] = extension_loaded($ext);
}

$all_extensions_ok = !in_array(false, $extension_status, true);

// Attempt to auto-create missing directories
$required_dirs = [
    '../config',
    '../uploads',
    '../uploads/ads',
    '../uploads/proofs',
    '../inc'
];
foreach ($required_dirs as $dir) {
    if (!is_dir($dir)) {
        @mkdir($dir, 0755, true);
    }
}

// Writable directories check
$writable_status = [];
foreach ($required_dirs as $dir) {
    $writable_status[$dir] = is_writable($dir);
}

$all_writable_ok = !in_array(false, $writable_status, true);

$can_proceed = $php_version_ok && $all_extensions_ok && $all_writable_ok;

if (isset($_POST['next']) && $can_proceed) {
    $_SESSION['install_stage'] = 2;
    header('Location: stage2.php');
    exit;
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Marketplace Installer - Stage 1</title>
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
</head>
<body class="bg-gray-100 flex items-center justify-center min-h-screen">
    <div class="bg-white p-8 rounded-lg shadow-md w-full max-w-2xl">
        <h1 class="text-2xl font-bold mb-6 text-primary-600">Marketplace Installation</h1>

        <h2 class="text-xl font-semibold mb-4 border-b pb-2">Stage 1: Welcome & Requirements Check</h2>

        <div class="mb-6">
            <h3 class="font-bold mb-2">PHP Version (Required: <?php echo $required_php_version; ?>+)</h3>
            <div class="flex items-center justify-between p-2 bg-gray-50 rounded">
                <span>Current: <?php echo $php_version; ?></span>
                <?php if ($php_version_ok): ?>
                    <span class="text-primary-600 font-bold">✓ OK</span>
                <?php else: ?>
                    <span class="text-red-600 font-bold">✗ Failed</span>
                <?php endif; ?>
            </div>
        </div>

        <div class="mb-6">
            <h3 class="font-bold mb-2">PHP Extensions</h3>
            <div class="grid grid-cols-2 gap-4">
                <?php foreach ($extension_status as $ext => $ok): ?>
                    <div class="flex items-center justify-between p-2 bg-gray-50 rounded">
                        <span><?php echo $ext; ?></span>
                        <?php if ($ok): ?>
                            <span class="text-primary-600 font-bold">✓ OK</span>
                        <?php else: ?>
                            <span class="text-red-600 font-bold">✗ Missing</span>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="mb-6">
            <h3 class="font-bold mb-2">Folder Permissions</h3>
            <div class="grid grid-cols-2 gap-4">
                <?php foreach ($writable_status as $dir => $ok): ?>
                    <div class="flex items-center justify-between p-2 bg-gray-50 rounded">
                        <span class="text-sm"><?php echo $dir; ?></span>
                        <?php if ($ok): ?>
                            <span class="text-primary-600 font-bold text-sm">✓ Writable</span>
                        <?php else: ?>
                            <span class="text-red-600 font-bold text-sm">✗ No Write Access</span>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <form method="POST">
            <?php if ($can_proceed): ?>
                <button type="submit" name="next" class="w-full bg-primary-600 text-white py-3 rounded-lg font-bold hover:bg-primary-700 transition">Next: Database Configuration</button>
            <?php else: ?>
                <div class="p-4 bg-red-100 text-red-700 rounded mb-4">
                    Please fix the issues above to proceed with the installation.
                </div>
                <button type="button" onclick="location.reload()" class="w-full bg-gray-500 text-white py-3 rounded-lg font-bold hover:bg-gray-600 transition">Re-check Requirements</button>
            <?php endif; ?>
        </form>
    </div>
</body>
</html>
