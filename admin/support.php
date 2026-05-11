<?php
require_once __DIR__ . '/../inc/auth.php';
require_once __DIR__ . '/../inc/functions.php';
require_once __DIR__ . '/../inc/security.php';
require_admin();

$user_id = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $user_id > 0) {
    $message = $_POST['message'];
    $stmt = $pdo->prepare("INSERT INTO messages (sender_id, receiver_id, ad_id, message) VALUES (0, ?, 0, ?)");
    $stmt->execute([$user_id, $message]);

    // Notify User via Email
    $user_email = $pdo->query("SELECT email FROM users WHERE id = $user_id")->fetchColumn();
    if ($user_email) {
        require_once __DIR__ . '/../inc/email.php';
        $subject = "New Support Reply from Admin";
        $body = "Admin has replied to your support ticket:<br><br>\"$message\"<br><br>Login to read and reply.";
        send_email($user_email, $subject, $body);
    }

    redirect("support.php?user_id=$user_id", "Reply sent to user.");
}

include __DIR__ . '/../templates/admin_header.php';
?>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
    <!-- User List -->
    <div class="bg-white rounded-3xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="bg-gray-50/50 p-6 border-b border-gray-100">
            <h2 class="font-black text-gray-800 uppercase text-[10px] tracking-[2px]">Support Tickets</h2>
        </div>
        <div class="overflow-y-auto max-h-[600px] divide-y divide-gray-50">
            <?php
            $stmt = $pdo->query("SELECT DISTINCT u.id, u.full_name, u.email, (SELECT MAX(created_at) FROM messages WHERE (sender_id = u.id AND receiver_id = 0) OR (sender_id = 0 AND receiver_id = u.id)) as last_msg FROM users u JOIN messages m ON (m.sender_id = u.id AND m.receiver_id = 0) OR (m.sender_id = 0 AND m.receiver_id = u.id) ORDER BY last_msg DESC");
            $tickets = $stmt->fetchAll();
            foreach ($tickets as $t):
            ?>
            <a href="support.php?user_id=<?php echo $t['id']; ?>" class="block p-5 hover:bg-gray-50 transition <?php echo $user_id == $t['id'] ? 'bg-primary-50 border-l-4 border-primary-600' : ''; ?>">
                <p class="font-black text-xs text-gray-800 uppercase"><?php echo h($t['full_name']); ?></p>
                <p class="text-[10px] text-gray-400 font-bold"><?php echo h($t['email']); ?></p>
                <p class="text-[9px] text-primary-600 font-black mt-2"><?php echo date('M d, H:i', strtotime($t['last_msg'])); ?></p>
            </a>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Chat Area -->
    <div class="lg:col-span-2 flex flex-col bg-white rounded-3xl shadow-sm border border-gray-100 overflow-hidden min-h-[600px]">
        <?php if ($user_id > 0):
            $user = $pdo->query("SELECT * FROM users WHERE id = $user_id")->fetch();
        ?>
        <div class="p-6 border-b border-gray-100 bg-gray-50/50 flex justify-between items-center">
            <div>
                <h2 class="font-black text-gray-800 uppercase text-[10px] tracking-[2px]">Chat with <?php echo h($user['full_name']); ?></h2>
                <p class="text-[9px] text-gray-400 font-bold"><?php echo h($user['email']); ?> | ID: #<?php echo $user['id']; ?></p>
            </div>
        </div>
        <div id="chatBox" class="flex-1 overflow-y-auto p-6 space-y-6 bg-gray-50/30">
            <?php
            $stmt = $pdo->prepare("SELECT * FROM messages WHERE (sender_id = ? AND receiver_id = 0) OR (sender_id = 0 AND receiver_id = ?) ORDER BY created_at ASC");
            $stmt->execute([$user_id, $user_id]);
            $messages = $stmt->fetchAll();
            foreach ($messages as $msg):
                $is_me = ($msg['sender_id'] == 0);
            ?>
            <div class="flex <?php echo $is_me ? 'justify-end' : 'justify-start'; ?>">
                <div class="max-w-[85%] <?php echo $is_me ? 'bg-gray-800 text-white rounded-2xl rounded-tr-none' : 'bg-white text-gray-800 rounded-2xl rounded-tl-none border border-gray-100'; ?> p-4 shadow-sm">
                    <p class="text-xs font-bold leading-relaxed"><?php echo h($msg['message']); ?></p>
                    <p class="text-[9px] mt-2 opacity-60 font-black uppercase text-right"><?php echo date('H:i', strtotime($msg['created_at'])); ?></p>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <div class="p-6 border-t border-gray-100">
            <form method="POST" class="flex gap-3">
                <input type="text" name="message" class="flex-1 p-4 bg-gray-50 border-none rounded-2xl text-xs font-bold focus:ring-2 focus:ring-primary-500" placeholder="Type your reply..." required>
                <button type="submit" class="bg-primary-600 text-white px-8 rounded-2xl font-black text-[10px] uppercase tracking-widest hover:bg-primary-700 transition shadow-lg">Send</button>
            </form>
        </div>
        <script>
            const chatBox = document.getElementById('chatBox');
            chatBox.scrollTop = chatBox.scrollHeight;
        </script>
        <?php else: ?>
        <div class="flex-1 flex flex-col items-center justify-center p-10 text-center">
            <div class="w-20 h-20 bg-gray-50 rounded-full flex items-center justify-center mb-6">
                <i class="fas fa-headset text-gray-200 text-3xl"></i>
            </div>
            <h3 class="text-lg font-black text-gray-800 uppercase tracking-tighter italic mb-2">Select a ticket to respond</h3>
            <p class="text-xs font-bold text-gray-400 max-w-xs">All user inquiries from the Contact Support page will appear here for your immediate attention.</p>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php include __DIR__ . '/../templates/admin_footer.php'; ?>
