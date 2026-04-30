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
    if (isset($_POST['register'])) {
        $full_name = $_POST['full_name'];
        $email = $_POST['email'];
        $phone = $_POST['phone'];
        $password = $_POST['password'];
        $confirm_password = $_POST['confirm_password'];

        if ($password !== $confirm_password) {
            $error = "Passwords do not match.";
        } else {
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                $error = "Email already registered.";
            } else {
                $otp = rand(100000, 999999);
                $otp_expires = date('Y-m-d H:i:s', strtotime('+10 minutes'));

                $_SESSION['reg_data'] = [
                    'full_name' => $full_name,
                    'email' => $email,
                    'phone' => $phone,
                    'password' => password_hash($password, PASSWORD_DEFAULT),
                    'otp' => $otp,
                    'otp_expires' => $otp_expires
                ];

                if (send_otp($email, $otp)) {
                    $show_otp = true;
                } else {
                    $error = "Failed to send OTP email. Please check your SMTP settings.";
                }
            }
        }
    }

    if (isset($_POST['verify_otp'])) {
        $entered_otp = $_POST['otp'];
        $reg = $_SESSION['reg_data'] ?? null;

        if ($reg && $entered_otp == $reg['otp'] && strtotime($reg['otp_expires']) > time()) {
            $stmt = $pdo->prepare("INSERT INTO users (full_name, email, phone, password, is_verified) VALUES (?, ?, ?, ?, 1)");
            $stmt->execute([$reg['full_name'], $reg['email'], $reg['phone'], $reg['password']]);

            $_SESSION['user_id'] = $pdo->lastInsertId();
            $_SESSION['user_name'] = $reg['full_name'];
            unset($_SESSION['reg_data']);
            header('Location: index.php');
            exit;
        } else {
            $error = "Invalid or expired OTP.";
            $show_otp = true;
        }
    }
}

include __DIR__ . '/templates/header.php';
?>

<div class="container mx-auto px-4 py-20 flex justify-center">
    <div class="bg-white p-8 rounded-lg shadow-md w-full max-w-md">
        <h1 class="text-2xl font-bold mb-6 text-primary-600 text-center uppercase tracking-wider">Create Account</h1>

        <?php if ($error): ?>
            <div class="bg-red-100 text-red-700 p-3 rounded mb-4 text-sm font-bold text-center"><?php echo h($error); ?></div>
        <?php endif; ?>

        <form method="POST">
            <?php if (isset($show_otp)): ?>
                <div class="mb-6 p-4 bg-primary-50 rounded-lg border border-primary-100 text-center">
                    <p class="text-sm text-primary-700 font-bold mb-4 italic">Verification code sent to <?php echo h($_SESSION['reg_data']['email']); ?></p>
                    <label class="block text-gray-700 font-bold mb-2">Enter 6-Digit OTP</label>
                    <input type="text" name="otp" class="w-full p-4 text-center text-3xl font-bold tracking-[10px] border-2 border-primary-200 rounded-xl focus:border-primary-500 outline-none" placeholder="000000" maxlength="6" required autofocus>
                    <button type="submit" name="verify_otp" class="w-full mt-6 bg-primary-600 text-white py-3 rounded-lg font-bold hover:bg-primary-700 transition shadow-lg uppercase">Verify & Create Account</button>
                    <p class="mt-4 text-xs text-gray-400">Didn't receive it? <a href="#" class="text-primary-600 font-bold hover:underline">Resend OTP</a></p>
                </div>
            <?php else: ?>
            <div class="mb-4 text-sm font-bold text-gray-700">
                <label class="block mb-2">Full Name</label>
                <input type="text" name="full_name" class="w-full p-3 border-2 border-gray-100 rounded-lg focus:border-primary-500 outline-none transition" placeholder="John Doe" required autofocus>
            </div>
            <div class="mb-4 text-sm font-bold text-gray-700">
                <label class="block mb-2">Email Address</label>
                <input type="email" name="email" class="w-full p-3 border-2 border-gray-100 rounded-lg focus:border-primary-500 outline-none transition" placeholder="example@mail.com" required>
            </div>
            <div class="mb-4 text-sm font-bold text-gray-700">
                <label class="block mb-2">Phone Number</label>
                <input type="text" name="phone" class="w-full p-3 border-2 border-gray-100 rounded-lg focus:border-primary-500 outline-none transition" placeholder="08012345678" required>
            </div>
            <div class="mb-4 text-sm font-bold text-gray-700">
                <label class="block mb-2">Password</label>
                <input type="password" name="password" class="w-full p-3 border-2 border-gray-100 rounded-lg focus:border-primary-500 outline-none transition" placeholder="********" required>
            </div>
            <div class="mb-6 text-sm font-bold text-gray-700">
                <label class="block mb-2">Confirm Password</label>
                <input type="password" name="confirm_password" class="w-full p-3 border-2 border-gray-100 rounded-lg focus:border-primary-500 outline-none transition" placeholder="********" required>
            </div>

            <button type="submit" name="register" class="w-full bg-primary-600 text-white py-3 rounded-lg font-bold hover:bg-primary-700 transition shadow-lg uppercase">Sign Up</button>
            <?php endif; ?>
        </form>

        <div class="mt-6 text-center text-gray-600 font-bold text-sm">
            Already have an account? <a href="/login" class="text-primary-600 hover:underline">Sign In</a>
        </div>
    </div>
</div>

<?php include __DIR__ . '/templates/footer.php'; ?>
