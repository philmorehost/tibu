<?php
require_once __DIR__ . '/config/config.php'`r`nif (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/inc/functions.php';
require_once __DIR__ . '/inc/user_auth.php';
require_user();

$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $message = $_POST['message'];
    $stmt = $pdo->prepare("INSERT INTO messages (sender_id, receiver_id, ad_id, message) VALUES (?, 0, 0, ?)");
    $stmt->execute([$user_id, $message]);

    // Notify Admin via Email
    $admin_email = $pdo->query("SELECT email FROM admin_users LIMIT 1")->fetchColumn();
    if ($admin_email) {
        require_once __DIR__ . '/inc/email.php';
        $user_email = $pdo->query("SELECT email FROM users WHERE id = $user_id")->fetchColumn();
        $subject = "New Support Ticket from User #$user_id";
        $body = "User ($user_email) has sent a new support message:<br><br>\"$message\"<br><br>Login to admin to reply.";
        send_email($admin_email, $subject, $body);
    }

    redirect('/support.php', 'Support request sent. We will get back to you shortly.');
}

// Check for unread admin replies to notify user (Simplified logic: usually done via background task or on login)
// For this task, we assume notification happens when admin replies.

include __DIR__ . '/templates/header.php';
?>

<div class="container mx-auto px-4 py-16 flex justify-center flex-1">
    <div class="bg-white rounded-[2.5rem] shadow-xl w-full max-w-4xl border border-gray-100 overflow-hidden flex flex-col md:flex-row min-h-[600px]">

        <!-- Sidebar Info -->
        <div class="w-full md:w-1/3 bg-primary-600 p-10 text-white flex flex-col justify-between">
            <div>
                <h1 class="text-3xl font-black uppercase tracking-tighter italic mb-6">Support <span class="text-yellow-400">Center</span></h1>
                <p class="text-sm font-bold opacity-80 leading-relaxed mb-10">Our support team is available 24/7 to help you with any issues regarding your listings or account.</p>

                <div class="space-y-6">
                    <div class="flex items-center gap-4">
                        <div class="w-10 h-10 bg-white/20 rounded-xl flex items-center justify-center"><i class="fas fa-envelope"></i></div>
                        <div>
                            <p class="text-[10px] font-black uppercase tracking-widest opacity-60">Email Support</p>
                            <p class="text-xs font-bold">support@tibung.ng</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-4">
                        <div class="w-10 h-10 bg-white/20 rounded-xl flex items-center justify-center"><i class="fas fa-phone-alt"></i></div>
                        <div>
                            <p class="text-[10px] font-black uppercase tracking-widest opacity-60">Hotline</p>
                            <p class="text-xs font-bold">+234 800 TIBUNG</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="mt-10 p-6 bg-white/10 rounded-2xl border border-white/20">
                <p class="text-[10px] font-black uppercase tracking-widest mb-2">Response Time</p>
                <p class="text-xs font-bold">Typically under 2 hours</p>
            </div>
        </div>

        <!-- Chat Area -->
        <div class="flex-1 flex flex-col bg-gray-50/30">
            <div class="p-6 border-b border-gray-100 bg-white flex justify-between items-center">
                <h2 class="font-black text-gray-800 uppercase tracking-widest text-xs">Direct Support Chat</h2>
                <span class="bg-green-100 text-green-700 text-[8px] font-black px-3 py-1 rounded-full uppercase tracking-widest">Active Ticket</span>
            </div>

            <div id="supportChat" class="flex-1 overflow-y-auto p-6 space-y-6 scrollbar-hide">
                <?php
                $stmt = $pdo->prepare("SELECT * FROM messages WHERE (sender_id = ? AND receiver_id = 0) OR (sender_id = 0 AND receiver_id = ?) ORDER BY created_at ASC");
                $stmt->execute([$user_id, $user_id]);
                $messages = $stmt->fetchAll();

                if (empty($messages)):
                ?>
                    <div class="flex flex-col items-center justify-center h-full text-center p-10">
                        <div class="w-20 h-20 bg-primary-50 rounded-full flex items-center justify-center mb-4">
                            <i class="fas fa-comments text-primary-200 text-3xl"></i>
                        </div>
                        <p class="text-xs font-bold text-gray-400 uppercase tracking-widest">No messages yet. Send your first inquiry below.</p>
                    </div>
                <?php else: foreach ($messages as $msg):
                    $is_me = ($msg['sender_id'] == $user_id);
                ?>
                <div class="flex <?php echo $is_me ? 'justify-end' : 'justify-start'; ?>">
                    <div class="max-w-[85%] <?php echo $is_me ? 'bg-primary-600 text-white rounded-2xl rounded-tr-none shadow-lg shadow-primary-100' : 'bg-white text-gray-800 rounded-2xl rounded-tl-none border border-gray-100 shadow-sm'; ?> p-4">
                        <p class="text-sm font-bold leading-relaxed"><?php echo h($msg['message']); ?></p>
                        <div class="flex items-center justify-end gap-2 mt-2 opacity-60">
                            <span class="text-[9px] font-black uppercase tracking-tighter"><?php echo date('H:i', strtotime($msg['created_at'])); ?></span>
                            <?php if (!$is_me): ?>
                                <i class="fas fa-shield-check text-[9px]"></i>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; endif; ?>
            </div>

            <div class="p-6 bg-white border-t border-gray-100">
                <form method="POST" class="flex gap-3 items-end">
                    <div class="flex-1 bg-gray-50 rounded-2xl p-2 border-2 border-transparent focus-within:border-primary-500 transition">
                        <textarea name="message" rows="1" class="w-full bg-transparent p-3 outline-none text-sm font-bold text-gray-700 resize-none" placeholder="Type your message..." required autofocus onkeydown="if(event.keyCode == 13 && !event.shiftKey) { this.form.submit(); return false; }"></textarea>
                    </div>
                    <button type="submit" class="bg-primary-600 text-white w-14 h-14 rounded-2xl flex items-center justify-center hover:bg-primary-700 transition shadow-xl shadow-primary-100 active:scale-95">
                        <i class="fas fa-paper-plane text-lg"></i>
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    const supportChat = document.getElementById('supportChat');
    supportChat.scrollTop = supportChat.scrollHeight;
</script>

<?php include __DIR__ . '/templates/footer.php'; ?>

