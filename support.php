<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/inc/functions.php';
require_once __DIR__ . '/inc/user_auth.php';
require_user();

$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $message = $_POST['message'];
    $stmt = $pdo->prepare("INSERT INTO messages (sender_id, receiver_id, ad_id, message) VALUES (?, 0, 0, ?)");
    $stmt->execute([$user_id, $message]);
    redirect('support.php', 'Support request sent.');
}

include __DIR__ . '/templates/header.php';
?>

<div class="container mx-auto px-4 py-10 flex justify-center">
    <div class="bg-white p-8 rounded-xl shadow-lg w-full max-w-xl">
        <h1 class="text-2xl font-bold mb-8 text-primary-600 border-b pb-4"><i class="fas fa-headset mr-2"></i> Contact Support</h1>

        <div id="supportChat" class="h-96 overflow-y-auto mb-6 p-4 bg-gray-50 rounded-lg space-y-4">
            <?php
            $stmt = $pdo->prepare("SELECT * FROM messages WHERE (sender_id = ? AND receiver_id = 0) OR (sender_id = 0 AND receiver_id = ?) ORDER BY created_at ASC");
            $stmt->execute([$user_id, $user_id]);
            $messages = $stmt->fetchAll();
            foreach ($messages as $msg):
                $is_me = ($msg['sender_id'] == $user_id);
            ?>
            <div class="flex <?php echo $is_me ? 'justify-end' : 'justify-start'; ?>">
                <div class="<?php echo $is_me ? 'bg-primary-600 text-white' : 'bg-blue-600 text-white'; ?> p-4 rounded-xl max-w-[85%] text-sm font-bold shadow-md">
                    <p><?php echo h($msg['message']); ?></p>
                    <div class="text-[10px] mt-2 opacity-70 flex justify-between">
                        <span><?php echo $is_me ? 'You' : 'Admin'; ?></span>
                        <span><?php echo date('M d, H:i', strtotime($msg['created_at'])); ?></span>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <form method="POST" class="space-y-4">
            <textarea name="message" class="w-full p-4 border-2 border-gray-100 rounded-xl focus:border-primary-500 outline-none transition" placeholder="How can we help you today?" required></textarea>
            <button type="submit" class="w-full bg-primary-600 text-white py-4 rounded-xl font-bold hover:bg-primary-700 transition uppercase shadow-lg">Send Support Message</button>
        </form>
    </div>
</div>

<script>
    const supportChat = document.getElementById('supportChat');
    supportChat.scrollTop = supportChat.scrollHeight;
</script>

<?php include __DIR__ . '/templates/footer.php'; ?>
