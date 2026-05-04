<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/inc/functions.php';
require_once __DIR__ . '/inc/security.php';

if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'];
    $password = $_POST['password'];

    $brute = check_brute_force($email, 0);
    if ($brute['blocked']) {
        $error = "Too many failed attempts. Account suspended or IP blocked.";
    } else {
        $stmt = $pdo->prepare("SELECT id, full_name, password, is_suspended FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && !$user['is_suspended'] && password_verify($password, $user['password'])) {
            log_login_attempt($email, 0, 1);
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['full_name'];
            header('Location: index.php');
            exit;
        } else {
            log_login_attempt($email, 0, 0);
            $error = $user && $user['is_suspended'] ? "Account suspended." : "Invalid email or password.";
        }
    }
}

include __DIR__ . '/templates/header.php';
?>

<div class="container mx-auto px-4 py-20 flex justify-center">
    <div class="bg-white p-8 rounded-lg shadow-md w-full max-w-md">
        <h1 class="text-2xl font-bold mb-6 text-primary-600 text-center uppercase tracking-wider">Sign In</h1>

        <?php if ($error): ?>
            <div class="bg-red-100 text-red-700 p-3 rounded mb-4 text-sm font-bold text-center"><?php echo h($error); ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="mb-4">
                <label class="block text-gray-700 font-bold mb-2">Email Address</label>
                <input type="email" name="email" class="w-full p-3 border-2 border-gray-100 rounded-lg focus:border-primary-500 outline-none transition" placeholder="example@mail.com" required autofocus>
            </div>
            <div class="mb-6">
                <label class="block text-gray-700 font-bold mb-2">Password</label>
                <input type="password" name="password" class="w-full p-3 border-2 border-gray-100 rounded-lg focus:border-primary-500 outline-none transition" placeholder="********" required>
            </div>

            <button type="submit" class="w-full bg-primary-600 text-white py-3 rounded-lg font-bold hover:bg-primary-700 transition shadow-lg uppercase">Sign In</button>
        </form>

        <?php
        $google_active = ($settings['google_login_active'] ?? '0') == '1';
        $google_client_id = $settings['google_client_id'] ?? '';
        $facebook_active = ($settings['facebook_login_active'] ?? '0') == '1';
        if ($google_active || $facebook_active):
        ?>
        <div class="mt-8 border-t pt-6">
            <p class="text-center text-gray-500 font-bold text-sm mb-4">OR LOGIN WITH</p>
            <div class="grid grid-cols-<?php echo ($google_active && $facebook_active) ? '2' : '1'; ?> gap-4">
                <?php if ($google_active && !empty($google_client_id)): ?>
                <div id="g_id_onload"
                     data-client_id="<?php echo h($google_client_id); ?>"
                     data-context="signin"
                     data-ux_mode="popup"
                     data-callback="handleGoogleCredentialResponse"
                     data-auto_prompt="false">
                </div>
                <div class="g_id_signin"
                     data-type="standard"
                     data-shape="rectangular"
                     data-theme="outline"
                     data-text="signin_with"
                     data-size="large"
                     data-logo_alignment="left">
                </div>
                <script>
                function handleGoogleCredentialResponse(response) {
                    const xhr = new XMLHttpRequest();
                    xhr.open('POST', 'api/google_verify.php');
                    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
                    xhr.onload = function() {
                        if (xhr.status === 200) {
                            try {
                                const res = JSON.parse(xhr.responseText);
                                if (res.success) {
                                    if (res.needs_phone) {
                                        window.location.href = 'profile_edit.php?notice=please_add_phone';
                                    } else {
                                        window.location.href = 'index.php';
                                    }
                                } else {
                                    alert(res.message || 'Login failed');
                                }
                            } catch (e) {
                                alert('Error processing server response');
                            }
                        } else {
                            alert('Google verification failed');
                        }
                    };
                    xhr.send('id_token=' + response.credential);
                }
                </script>
                <?php elseif ($google_active): ?>
                <div class="text-[10px] text-red-500 text-center font-bold">Google Login Not Configured</div>
                <?php endif; ?>
                <?php if ($facebook_active): ?>
                <a href="social.php?provider=facebook" class="flex items-center justify-center bg-white border-2 border-gray-200 py-2 rounded-lg hover:bg-gray-50 transition">
                    <i class="fab fa-facebook text-blue-600 mr-2"></i> Facebook
                </a>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>

        <div class="mt-6 text-center text-gray-600 font-bold text-sm">
            Don't have an account? <a href="/register" class="text-primary-600 hover:underline">Register Now</a>
        </div>
    </div>
</div>

<?php include __DIR__ . '/templates/footer.php'; ?>
