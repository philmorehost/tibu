<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/inc/functions.php';
require_once __DIR__ . '/inc/user_auth.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Basic settings update
    $full_name = $_POST['full_name'];
    $phone = $_POST['phone'];
    $business_name = $_POST['business_name'] ?? null;
    $stmt = $pdo->prepare("UPDATE users SET full_name = ?, phone = ?, business_name = ? WHERE id = ?");
    $stmt->execute([$full_name, $phone, $business_name, $_SESSION['user_id']]);
    redirect('profile.php', 'Profile updated.');
}

include __DIR__ . '/templates/header.php';
?>

<div class="container mx-auto px-4 py-10 flex justify-center">
    <div class="bg-white p-8 rounded-xl shadow-lg w-full max-w-md">
        <h1 class="text-2xl font-bold mb-8 text-primary-600 border-b pb-4"><i class="fas fa-user-edit mr-2"></i> Edit Profile</h1>

        <?php
        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch();
        ?>

        <form method="POST" class="space-y-6">
            <div>
                <label class="block text-gray-700 font-bold mb-2">Full Name</label>
                <input type="text" name="full_name" value="<?php echo h($user['full_name']); ?>" class="w-full p-3 border-2 border-gray-100 rounded-lg focus:border-primary-500 outline-none" required>
            </div>
            <div>
                <label class="block text-gray-700 font-bold mb-2">Phone Number</label>
                <input type="text" name="phone" value="<?php echo h($user['phone']); ?>" class="w-full p-3 border-2 border-gray-100 rounded-lg focus:border-primary-500 outline-none" required>
            </div>
            <div>
                <label class="block text-gray-700 font-bold mb-2">Business Name (Optional)</label>
                <input type="text" name="business_name" value="<?php echo h($user['business_name'] ?? ''); ?>" placeholder="e.g. Tunde Electronics" class="w-full p-3 border-2 border-gray-100 rounded-lg focus:border-primary-500 outline-none">
                <p class="text-[10px] text-gray-400 mt-1 font-bold uppercase tracking-widest">This will be used as your watermark on images.</p>
            </div>
            <div class="pt-6">
                <button type="submit" class="w-full bg-primary-600 text-white py-3 rounded-lg font-bold hover:bg-primary-700 transition shadow-lg uppercase">Save Changes</button>
            </div>
        </form>
    </div>
</div>

<?php include __DIR__ . '/templates/footer.php'; ?>
