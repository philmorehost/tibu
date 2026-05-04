<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/inc/functions.php';
require_once __DIR__ . '/inc/user_auth.php';
require_user();

$user_id = $_SESSION['user_id'];

// Mode: Single conversation or Inbox
$ad_id = isset($_GET['ad_id']) ? (int)$_GET['ad_id'] : 0;

if ($ad_id > 0) {
    // SINGLE CONVERSATION MODE
    $stmt = $pdo->prepare("SELECT a.*, u.full_name as seller_name FROM ads a JOIN users u ON a.user_id = u.id WHERE a.id = ?");
    $stmt->execute([$ad_id]);
    $ad = $stmt->fetch();

    if (!$ad) {
        redirect('/chat.php', 'Ad not found.', 'error');
    }

    $receiver_id = $ad['user_id'];

    if ($receiver_id == $user_id) {
        // If owner is opening chat, they might be replying to someone.
        // For simplicity in this v1.2, we assume owner opens chat from inbox with a specific user.
        $other_user_id = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0;
        if ($other_user_id <= 0) {
            redirect('/chat.php', 'Select a conversation to reply.');
        }
        $receiver_id = $other_user_id;
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $message = $_POST['message'];
        $stmt = $pdo->prepare("INSERT INTO messages (sender_id, receiver_id, ad_id, message) VALUES (?, ?, ?, ?)");
        $stmt->execute([$user_id, $receiver_id, $ad_id, $message]);

        $redirect_url = "/chat.php?ad_id=$ad_id";
        if (isset($_GET['user_id'])) $redirect_url .= "&user_id=" . (int)$_GET['user_id'];

        redirect($redirect_url, 'Message sent.');
    }

    // Mark messages as read
    $stmt = $pdo->prepare("UPDATE messages SET is_read = 1 WHERE ad_id = ? AND receiver_id = ? AND sender_id = ?");
    $stmt->execute([$ad_id, $user_id, $receiver_id]);

} else {
    // INBOX MODE
    // Get unique conversations: unique pairs of (ad_id, other_user)
    $stmt = $pdo->prepare("
        SELECT m.*, a.title as ad_title, u.full_name as other_name,
        (SELECT COUNT(*) FROM messages WHERE ad_id = m.ad_id AND receiver_id = ? AND sender_id = (CASE WHEN m.sender_id = ? THEN m.receiver_id ELSE m.sender_id END) AND is_read = 0) as unread_count
        FROM messages m
        JOIN ads a ON m.ad_id = a.id
        JOIN users u ON u.id = (CASE WHEN m.sender_id = ? THEN m.receiver_id ELSE m.sender_id END)
        WHERE (m.sender_id = ? OR m.receiver_id = ?)
        AND m.id IN (
            SELECT MAX(id) FROM messages WHERE (sender_id = ? OR receiver_id = ?) GROUP BY ad_id, (CASE WHEN sender_id = ? THEN receiver_id ELSE sender_id END)
        )
        ORDER BY m.created_at DESC
    ");
    $stmt->execute([$user_id, $user_id, $user_id, $user_id, $user_id, $user_id, $user_id, $user_id]);
    $conversations = $stmt->fetchAll();
}

include __DIR__ . '/templates/header.php';
?>

<div class="container mx-auto px-4 py-10">
    <div class="max-w-5xl mx-auto flex flex-col md:flex-row gap-6 bg-white rounded-3xl shadow-xl overflow-hidden border border-gray-100 min-h-[600px]">

        <!-- Sidebar: Conversation List (Hidden on mobile if in single chat) -->
        <div class="w-full md:w-1/3 border-r border-gray-100 <?php echo ($ad_id > 0) ? 'hidden md:block' : ''; ?>">
            <div class="p-6 border-b border-gray-100 bg-gray-50/50">
                <h2 class="text-xl font-black text-gray-800 uppercase tracking-tighter italic">My <span class="text-primary-600">Messages</span></h2>
            </div>
            <div class="overflow-y-auto h-[500px] scrollbar-hide">
                <?php
                if ($ad_id <= 0) {
                    $sidebar_conversations = $conversations;
                } else {
                    // Re-fetch for sidebar when in chat mode
                    $stmt = $pdo->prepare("
                        SELECT m.*, a.title as ad_title, u.full_name as other_name,
                        (SELECT COUNT(*) FROM messages WHERE ad_id = m.ad_id AND receiver_id = ? AND sender_id = (CASE WHEN m.sender_id = ? THEN m.receiver_id ELSE m.sender_id END) AND is_read = 0) as unread_count
                        FROM messages m
                        JOIN ads a ON m.ad_id = a.id
                        JOIN users u ON u.id = (CASE WHEN m.sender_id = ? THEN m.receiver_id ELSE m.sender_id END)
                        WHERE (m.sender_id = ? OR m.receiver_id = ?)
                        AND m.id IN (
                            SELECT MAX(id) FROM messages WHERE (sender_id = ? OR receiver_id = ?) GROUP BY ad_id, (CASE WHEN sender_id = ? THEN receiver_id ELSE sender_id END)
                        )
                        ORDER BY m.created_at DESC
                    ");
                    $stmt->execute([$user_id, $user_id, $user_id, $user_id, $user_id, $user_id, $user_id, $user_id]);
                    $sidebar_conversations = $stmt->fetchAll();
                }

                if (empty($sidebar_conversations)): ?>
                    <div class="p-10 text-center text-gray-400">
                        <i class="fas fa-comment-slash text-4xl mb-4 opacity-20"></i>
                        <p class="text-xs font-bold uppercase tracking-widest">No conversations yet</p>
                    </div>
                <?php else: foreach ($sidebar_conversations as $conv):
                    $other_uid = ($conv['sender_id'] == $user_id) ? $conv['receiver_id'] : $conv['sender_id'];
                    $is_active = ($ad_id == $conv['ad_id'] && (isset($_GET['user_id']) ? $_GET['user_id'] == $other_uid : true));
                ?>
                    <a href="/chat.php?ad_id=<?php echo $conv['ad_id']; ?>&user_id=<?php echo $other_uid; ?>" class="block p-5 border-b border-gray-50 hover:bg-primary-50 transition <?php echo $is_active ? 'bg-primary-50 border-l-4 border-l-primary-600' : ''; ?>">
                        <div class="flex justify-between items-start mb-1">
                            <h4 class="text-sm font-black text-gray-800 truncate pr-2"><?php echo h($conv['other_name']); ?></h4>
                            <span class="text-[9px] text-gray-400 font-bold"><?php echo date('H:i', strtotime($conv['created_at'])); ?></span>
                        </div>
                        <p class="text-[10px] text-primary-600 font-bold uppercase tracking-tighter truncate mb-2"><?php echo h($conv['ad_title']); ?></p>
                        <div class="flex justify-between items-center">
                            <p class="text-xs text-gray-500 line-clamp-1 <?php echo $conv['unread_count'] > 0 ? 'font-black text-gray-800' : ''; ?>"><?php echo h($conv['message']); ?></p>
                            <?php if ($conv['unread_count'] > 0): ?>
                                <span class="bg-primary-600 text-white text-[8px] font-black w-4 h-4 rounded-full flex items-center justify-center"><?php echo $conv['unread_count']; ?></span>
                            <?php endif; ?>
                        </div>
                    </a>
                <?php endforeach; endif; ?>
            </div>
        </div>

        <!-- Main Chat Area -->
        <div class="flex-1 flex flex-col">
            <?php if ($ad_id > 0): ?>
                <!-- Header -->
                <div class="p-6 border-b border-gray-100 flex items-center justify-between bg-white sticky top-0 z-10">
                    <div class="flex items-center gap-4">
                        <a href="/chat.php" class="md:hidden text-gray-400 hover:text-primary-600"><i class="fas fa-arrow-left"></i></a>
                        <div class="w-12 h-12 bg-primary-100 rounded-2xl flex items-center justify-center text-primary-600 font-black uppercase text-xl shadow-inner">
                            <?php
                            $target_name = "";
                            if ($ad['user_id'] == $user_id) {
                                // I am seller, show buyer name
                                $stmt_u = $pdo->prepare("SELECT full_name FROM users WHERE id = ?");
                                $stmt_u->execute([$receiver_id]);
                                $target_name = $stmt_u->fetchColumn();
                            } else {
                                $target_name = $ad['seller_name'];
                            }
                            echo substr($target_name, 0, 1);
                            ?>
                        </div>
                        <div>
                            <h3 class="font-black text-gray-800 leading-tight"><?php echo h($target_name); ?></h3>
                            <a href="/ad.php?id=<?php echo $ad['id']; ?>" class="text-[10px] text-primary-600 font-black uppercase tracking-widest hover:underline"><?php echo h($ad['title']); ?></a>
                        </div>
                    </div>
                    <div class="hidden sm:block">
                        <span class="bg-green-100 text-green-700 text-[8px] font-black px-3 py-1 rounded-full uppercase tracking-widest shadow-sm">Verified Deal</span>
                    </div>
                </div>

                <!-- Messages -->
                <div id="chatBox" class="flex-1 overflow-y-auto p-6 space-y-6 bg-gray-50/30">
                    <?php
                    $stmt = $pdo->prepare("SELECT * FROM messages WHERE ad_id = ? AND ((sender_id = ? AND receiver_id = ?) OR (sender_id = ? AND receiver_id = ?)) ORDER BY created_at ASC");
                    $stmt->execute([$ad_id, $user_id, $receiver_id, $receiver_id, $user_id]);
                    $messages = $stmt->fetchAll();

                    $last_date = "";
                    foreach ($messages as $msg):
                        $curr_date = date('Y-m-d', strtotime($msg['created_at']));
                        if ($curr_date != $last_date):
                    ?>
                        <div class="flex justify-center my-4">
                            <span class="bg-white border border-gray-100 text-[9px] font-black text-gray-400 px-4 py-1 rounded-full uppercase shadow-sm">
                                <?php echo $curr_date == date('Y-m-d') ? 'Today' : date('M d, Y', strtotime($msg['created_at'])); ?>
                            </span>
                        </div>
                    <?php $last_date = $curr_date; endif;
                        $is_me = ($msg['sender_id'] == $user_id);
                    ?>
                    <div class="flex <?php echo $is_me ? 'justify-end' : 'justify-start'; ?>">
                        <div class="max-w-[80%] <?php echo $is_me ? 'bg-primary-600 text-white rounded-2xl rounded-tr-none shadow-lg shadow-primary-100' : 'bg-white text-gray-800 rounded-2xl rounded-tl-none border border-gray-100 shadow-sm'; ?> p-4">
                            <p class="text-sm font-bold leading-relaxed"><?php echo h($msg['message']); ?></p>
                            <div class="flex items-center justify-end gap-1 mt-2 opacity-60">
                                <span class="text-[9px] font-black"><?php echo date('H:i', strtotime($msg['created_at'])); ?></span>
                                <?php if ($is_me): ?>
                                    <i class="fas fa-check-double text-[8px] <?php echo $msg['is_read'] ? 'text-blue-300' : ''; ?>"></i>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>

                <!-- Input -->
                <div class="p-6 bg-white border-t border-gray-100">
                    <!-- Quick Reply Chips -->
                    <div class="flex gap-2 overflow-x-auto pb-4 scrollbar-hide">
                        <?php
                        $chips = ["Is it still available?", "What's the last price?", "Where is your location?", "Can we swap?", "I'm interested!"];
                        foreach ($chips as $chip):
                        ?>
                            <button type="button" onclick="setQuickReply('<?php echo addslashes($chip); ?>')" class="whitespace-nowrap bg-gray-50 hover:bg-primary-50 text-gray-600 hover:text-primary-600 border border-gray-100 hover:border-primary-200 px-4 py-2 rounded-xl text-[10px] font-black uppercase tracking-tight transition active:scale-95">
                                <?php echo $chip; ?>
                            </button>
                        <?php endforeach; ?>
                    </div>

                    <form method="POST" id="chatForm" class="flex gap-3 items-end">
                        <div class="flex-1 bg-gray-50 rounded-2xl p-2 border-2 border-transparent focus-within:border-primary-500 transition relative">
                            <textarea id="messageInput" name="message" rows="1" class="w-full bg-transparent p-3 pr-10 outline-none text-sm font-bold text-gray-700 resize-none" placeholder="Type your message..." required autofocus onkeydown="if(event.keyCode == 13 && !event.shiftKey) { this.form.submit(); return false; }"></textarea>
                            <button type="button" onclick="toggleEmojiPicker()" class="absolute right-4 bottom-5 text-gray-400 hover:text-primary-600 transition">
                                <i class="far fa-smile text-xl"></i>
                            </button>

                            <!-- Simple Emoji Picker -->
                            <div id="emojiPicker" class="hidden absolute bottom-full right-0 mb-4 bg-white shadow-2xl border border-gray-100 rounded-2xl p-3 grid grid-cols-6 gap-2 z-50">
                                <?php
                                $emojis = ['😊', '🤝', '🔥', '👍', '💰', '📍', '🙌', '📱', '✅', '⭐', '🚗', '🏠'];
                                foreach ($emojis as $e):
                                ?>
                                    <button type="button" onclick="addEmoji('<?php echo $e; ?>')" class="text-xl hover:scale-125 transition active:scale-95"><?php echo $e; ?></button>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <button type="submit" class="bg-primary-600 text-white w-14 h-14 rounded-2xl flex items-center justify-center hover:bg-primary-700 transition shadow-xl shadow-primary-100 active:scale-95">
                            <i class="fas fa-paper-plane text-lg"></i>
                        </button>
                    </form>
                </div>

            <?php else: ?>
                <!-- Empty Inbox State -->
                <div class="flex-1 flex flex-col items-center justify-center p-10 text-center bg-gray-50/30">
                    <div class="w-32 h-32 bg-white rounded-full flex items-center justify-center shadow-xl mb-8">
                        <i class="fas fa-comments text-5xl text-primary-200"></i>
                    </div>
                    <h3 class="text-2xl font-black text-gray-800 uppercase tracking-tighter italic mb-2">Welcome to <span class="text-primary-600">Tibung Chat</span></h3>
                    <p class="text-gray-400 font-bold max-w-sm">Select a conversation from the list to start messaging or reply to buyers.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
    const chatBox = document.getElementById('chatBox');
    const messageInput = document.getElementById('messageInput');
    const emojiPicker = document.getElementById('emojiPicker');

    if (chatBox) chatBox.scrollTop = chatBox.scrollHeight;

    function setQuickReply(text) {
        messageInput.value = text;
        messageInput.focus();
    }

    function toggleEmojiPicker() {
        emojiPicker.classList.toggle('hidden');
    }

    function addEmoji(emoji) {
        messageInput.value += emoji;
        emojiPicker.classList.add('hidden');
        messageInput.focus();
    }

    // Close emoji picker when clicking outside
    document.addEventListener('click', (e) => {
        if (!emojiPicker.contains(e.target) && !e.target.closest('button')) {
            emojiPicker.classList.add('hidden');
        }
    });
</script>

<?php include __DIR__ . '/templates/footer.php'; ?>
