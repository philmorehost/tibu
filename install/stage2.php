<?php
require_once __DIR__ . '/../inc/functions.php';
/**
 * Marketplace Installer
 * Stage 2: Database Configuration & Schema Installation
 */

if (session_status() === PHP_SESSION_NONE) session_start();

if (!isset($_SESSION['install_stage']) || $_SESSION['install_stage'] < 2) {
    header('Location: index.php');
    exit;
}

$error = null;
$success = null;

if (isset($_POST['install'])) {
    $db_host = $_POST['db_host'];
    $db_name = $_POST['db_name'];
    $db_user = $_POST['db_user'];
    $db_pass = $_POST['db_pass'];

    try {
        $pdo = new PDO("mysql:host=$db_host", $db_user, $db_pass);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Create database if not exists
        $pdo->exec("CREATE DATABASE IF NOT EXISTS `$db_name` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        $pdo->exec("USE `$db_name` ");

        // Run Schema
        $sql = file_get_contents('schema.sql');
        $pdo->exec($sql);

        // Save to config file
        $config_content = "<?php
define('DB_HOST', '$db_host');
define('DB_NAME', '$db_name');
define('DB_USER', '$db_user');
define('DB_PASS', '$db_pass');

try {
    \$pdo = new PDO(\"mysql:host=\" . DB_HOST . \";dbname=\" . DB_NAME . \";charset=utf8mb4\", DB_USER, DB_PASS);
    \$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    \$pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException \$e) {
    die(\"Database connection failed: \" . \$e->getMessage());
}
";
        file_put_contents('../config/config.php', $config_content);

        $_SESSION['install_stage'] = 3;
        $_SESSION['db_config'] = [
            'host' => $db_host,
            'name' => $db_name,
            'user' => $db_user,
            'pass' => $db_pass
        ];

        header('Location: stage3.php');
        exit;

    } catch (PDOException $e) {
        $error = "Database Error: " . $e->getMessage();
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Marketplace Installer - Stage 2</title>
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
    <div class="bg-white p-8 rounded-lg shadow-md w-full max-w-md">
        <h1 class="text-2xl font-bold mb-6 text-primary-600">Database Configuration</h1>

        <?php if ($error): ?>
            <div class="bg-red-100 text-red-700 p-3 rounded mb-4 text-sm"><?php echo h($error); ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="mb-4">
                <label class="block text-gray-700 font-bold mb-2">DB Host</label>
                <input type="text" name="db_host" value="localhost" class="w-full p-2 border rounded" required>
            </div>
            <div class="mb-4">
                <label class="block text-gray-700 font-bold mb-2">DB Name</label>
                <input type="text" name="db_name" value="classifieds_db" class="w-full p-2 border rounded" required>
            </div>
            <div class="mb-4">
                <label class="block text-gray-700 font-bold mb-2">DB User</label>
                <input type="text" name="db_user" value="root" class="w-full p-2 border rounded" required>
            </div>
            <div class="mb-6">
                <label class="block text-gray-700 font-bold mb-2">DB Password</label>
                <input type="password" name="db_pass" class="w-full p-2 border rounded">
            </div>

            <button type="submit" name="install" class="w-full bg-primary-600 text-white py-3 rounded-lg font-bold hover:bg-primary-700 transition">Next: Admin & Seeding</button>
        </form>
    </div>
</body>
</html>
