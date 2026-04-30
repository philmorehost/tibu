<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/inc/functions.php';
require_once __DIR__ . '/inc/user_auth.php';
require_user();

if (isset($_GET['ad_id'])) {
    $ad_id = (int)$_GET['ad_id'];
    $user_id = $_SESSION['user_id'];

    // Check if ad exists
    $stmt = $pdo->prepare("SELECT user_id, title FROM ads WHERE id = ?");
    $stmt->execute([$ad_id]);
    $ad = $stmt->fetch();

    if (!$ad) {
        redirect('index.php', 'Ad not found.');
    }

    $receiver_id = $ad['user_id'];

    // Prevent messaging self
    if ($receiver_id == $user_id) {
        redirect('ad.php?id='.$ad_id, 'You cannot message yourself.');
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $message = $_POST['message'];
        $stmt = $pdo->prepare("INSERT INTO messages (sender_id, receiver_id, ad_id, message) VALUES (?, ?, ?, ?)");
        $stmt->execute([$user_id, $receiver_id, $ad_id, $message]);
        redirect('chat.php?ad_id='.$ad_id, 'Message sent.');
    }
} else {
    redirect('index.php');
}

include __DIR__ . '/templates/header.php';
?>

<div class="container mx-auto px-4 py-10 flex justify-center">
    <div class="bg-white p-6 rounded-lg shadow-md w-full max-w-xl">
        <h1 class="text-xl font-bold mb-6 text-gray-800">Chat about: <?php echo h($ad['title']); ?></h1>

<div class="bg-blue-50 border-l-4 border-blue-500 p-4 mb-6 rounded-r-xl">
    <div class="flex items-center gap-3">
        <i class="fas fa-info-circle text-blue-500 text-lg"></i>
        <p class="text-[10px] font-black text-blue-800 uppercase tracking-widest leading-relaxed">Keep all deal discussions here. <?php echo h($settings['site_name'] ?? 'Classifieds'); ?> cannot help resolve disputes for deals made outside the platform.</p>
    </div>
</div>
        <div id="chatBox" class="h-96 overflow-y-auto mb-6 p-4 bg-gray-50 rounded-lg space-y-4">
            <?php
            $stmt = $pdo->prepare("SELECT * FROM messages WHERE ad_id = ? AND ((sender_id = ? AND receiver_id = ?) OR (sender_id = ? AND receiver_id = ?)) ORDER BY created_at ASC");
            $stmt->execute([$ad_id, $user_id, $receiver_id, $receiver_id, $user_id]);
            $messages = $stmt->fetchAll();
            foreach ($messages as $msg):
                $is_me = ($msg['sender_id'] == $user_id);
            ?>
            <div class="flex <?php echo $is_me ? 'justify-end' : 'justify-start'; ?>">
                <div class="<?php echo $is_me ? 'bg-primary-600 text-white rounded-br-none' : 'bg-gray-200 text-gray-800 rounded-bl-none'; ?> p-3 rounded-lg max-w-[80%] text-sm font-bold shadow-sm">
                    <?php echo h($msg['message']); ?>
                    <div class="text-[10px] mt-1 opacity-70"><?php echo date('H:i', strtotime($msg['created_at'])); ?></div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <form method="POST" class="flex gap-2">
            <input type="text" name="message" class="flex-1 p-3 border-2 border-gray-100 rounded-lg focus:border-primary-500 outline-none" placeholder="Type your message..." required autofocus>
            <button type="submit" class="bg-primary-600 text-white px-6 py-2 rounded-lg font-bold hover:bg-primary-700 transition uppercase shadow-md">Send</button>
        </form>
    </div>
</div>

<script>
    // Scroll to bottom
    const chatBox = document.getElementById('chatBox');
    chatBox.scrollTop = chatBox.scrollHeight;
</script>

<?php include __DIR__ . '/templates/footer.php'; ?>
