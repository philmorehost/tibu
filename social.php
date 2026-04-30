<?php
/**
 * Jiji-Inspired-1.0 Social Login Handler (OAuth)
 */

if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/inc/functions.php';

$provider = $_GET['provider'] ?? '';
if (!in_array($provider, ['google', 'facebook'])) {
    die("Invalid provider selected.");
}

// Get settings
$stmt = $pdo->prepare("SELECT setting_key, setting_value FROM settings WHERE setting_key LIKE ?");
$stmt->execute([$provider . '_%']);
$settings = [];
while ($row = $stmt->fetch()) {
    $settings[$row['setting_key']] = $row['setting_value'];
}

$client_id = $settings[$provider . '_client_id'] ?? ($settings[$provider . '_app_id'] ?? '');
$client_secret = $settings[$provider . '_client_secret'] ?? ($settings[$provider . '_app_secret'] ?? '');

if (empty($client_id) || empty($client_secret)) {
    die("Social login for " . ucfirst($provider) . " is not configured in the admin panel.");
}

// SIMULATION: In a real app, you would use a library like HybridAuth or Google API Client
// Here we simulate the redirect to the provider and back.

if (!isset($_GET['code'])) {
    // Stage 1: Redirect to Provider
    // Real URL would be something like: https://accounts.google.com/o/oauth2/auth?...
    // For this blueprint, we simulate the provider's auth screen with a simple confirmation
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <title>OAuth Simulation - <?php echo ucfirst($provider); ?></title>
        <script src="https://cdn.tailwindcss.com"></script>
    </head>
    <body class="bg-gray-100 flex items-center justify-center min-h-screen">
        <div class="bg-white p-8 rounded-2xl shadow-2xl max-w-sm w-full text-center">
            <div class="mb-6">
                <?php if ($provider === 'google'): ?>
                    <i class="fab fa-google text-5xl text-red-500"></i>
                <?php else: ?>
                    <i class="fab fa-facebook text-5xl text-blue-600"></i>
                <?php endif; ?>
            </div>
            <h1 class="text-xl font-black mb-4">Sign in with <?php echo ucfirst($provider); ?></h1>
            <p class="text-gray-500 text-sm mb-8">This is a simulated OAuth screen. In production, this would be the official <?php echo ucfirst($provider); ?> login page.</p>

            <a href="social.php?provider=<?php echo h($provider); ?>&code=simulated_code_<?php echo time(); ?>"
               class="block w-full bg-<?php echo $provider === 'google' ? 'red-500' : 'blue-600'; ?> text-white py-3 rounded-xl font-bold hover:opacity-90 transition">
                Continue as Test User
            </a>
            <a href="login.php" class="block mt-4 text-xs font-bold text-gray-400 uppercase tracking-widest hover:text-gray-600">Cancel</a>
        </div>
        <script src="https://kit.fontawesome.com/your-code.js" crossorigin="anonymous"></script>
    </body>
    </html>
    <?php
    exit;
} else {
    // Stage 2: Handle Callback
    // Simulate user data from provider
    $social_id = "social_" . $provider . "_" . rand(1000, 9999);
    $email = $provider . "_user_" . rand(100, 999) . "@example.com";
    $full_name = ucfirst($provider) . " User";

    // Check if user exists by email
    $stmt = $pdo->prepare("SELECT id, is_suspended FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user) {
        if ($user['is_suspended']) {
            header('Location: login.php?error=account_suspended');
            exit;
        }
        $user_id = $user['id'];
    } else {
        // Create new user
        $stmt = $pdo->prepare("INSERT INTO users (full_name, email, password, is_verified) VALUES (?, ?, ?, 1)");
        $stmt->execute([$full_name, $email, password_hash(bin2hex(random_bytes(10)), PASSWORD_DEFAULT)]);
        $user_id = $pdo->lastInsertId();
    }

    // Log them in
    $_SESSION['user_id'] = $user_id;
    $_SESSION['user_name'] = $full_name;

    header('Location: index.php');
    exit;
}
