<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/inc/functions.php';
require_once __DIR__ . '/inc/user_auth.php';
require_user();

$user_id = $_SESSION['user_id'];

// Mode: Single conversation or Inbox
$ad_id = isset($_GET['ad_id']) ? (int)$_GET['ad_id'] : 0;
$other_user_id = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0;

if ($ad_id > 0) {
    // SINGLE CONVERSATION MODE
    $stmt = $pdo->prepare("SELECT a.*, u.full_name as seller_name, u.verification_tier FROM ads a JOIN users u ON a.user_id = u.id WHERE a.id = ?");
    $stmt->execute([$ad_id]);
    $ad = $stmt->fetch();

    if (!$ad) {
        redirect('/chat.php', 'Ad not found.', 'error');
    }

    $receiver_id = ($ad['user_id'] == $user_id) ? $other_user_id : $ad['user_id'];

    if ($receiver_id <= 0) {
        // If it's a new chat from ad page, receiver is the seller
        $receiver_id = $ad['user_id'];
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['message'])) {
        $message = trim($_POST['message']);
        $stmt = $pdo->prepare("INSERT INTO messages (sender_id, receiver_id, ad_id, message) VALUES (?, ?, ?, ?)");
        $stmt->execute([$user_id, $receiver_id, $ad_id, $message]);

        $redirect_url = "/chat.php?ad_id=$ad_id&user_id=$receiver_id";
        redirect($redirect_url, 'Message sent.');
    }

    // Mark messages as read
    try {
        $stmt = $pdo->prepare("UPDATE messages SET is_read = 1 WHERE ad_id = ? AND receiver_id = ? AND sender_id = ?");
        $stmt->execute([$ad_id, $user_id, $receiver_id]);
    } catch (Exception $e) {
        error_log("Failed to update message read status: " . $e->getMessage());
    }

    // Fetch messages
    $stmt = $pdo->prepare("SELECT * FROM messages WHERE ad_id = ? AND ((sender_id = ? AND receiver_id = ?) OR (sender_id = ? AND receiver_id = ?)) ORDER BY created_at ASC");
    $stmt->execute([$ad_id, $user_id, $receiver_id, $receiver_id, $user_id]);
    $messages = $stmt->fetchAll();

    // Fetch partner info
    $stmt_p = $pdo->prepare("SELECT full_name, verification_tier FROM users WHERE id = ?");
    $stmt_p->execute([$receiver_id]);
    $partner = $stmt_p->fetch();
    $target_name = $partner['full_name'] ?? "User";

} else {
    // INBOX MODE
    try {
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
    } catch (Exception $e) {
        $conversations = [];
        error_log("Failed to fetch conversations: " . $e->getMessage());
    }
}

include __DIR__ . '/templates/header.php';
?>

<style>
    .chat-container {
        height: calc(100vh - 160px);
        min-height: 500px;
    }
    .message-bubble {
        position: relative;
        max-width: 85%;
        padding: 12px 16px;
        border-radius: 20px;
        font-size: 0.9rem;
        line-height: 1.5;
        transition: all 0.2s ease;
    }
    .message-me {
        background: linear-gradient(135deg, #1a7fe8, #0966ce);
        color: white;
        border-bottom-right-radius: 4px;
        box-shadow: 0 4px 15px rgba(26, 127, 232, 0.2);
    }
    .message-other {
        background: white;
        color: #374151;
        border-bottom-left-radius: 4px;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        border: 1px solid #f3f4f6;
    }
    .scrollbar-hide::-webkit-scrollbar { display: none; }
    .scrollbar-hide { -ms-overflow-style: none; scrollbar-width: none; }
    
    @media (max-width: 768px) {
        .chat-container {
            height: calc(100vh - 130px);
            margin-top: -20px;
        }
    }
    
    .quick-reply-btn {
        transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
    }
    .quick-reply-btn:active {
        transform: scale(0.95);
    }
</style>

<div class="container mx-auto px-4 py-6 md:py-10">
    <div class="chat-container max-w-6xl mx-auto bg-white rounded-[2rem] shadow-2xl overflow-hidden flex border border-gray-100">
        
        <!-- Sidebar -->
        <div class="w-full md:w-1/3 lg:w-1/4 flex flex-col bg-gray-50 border-r border-gray-100 <?php echo ($ad_id > 0) ? 'hidden md:flex' : 'flex'; ?>">
            <div class="p-6 border-b border-gray-100 bg-white">
                <div class="flex items-center justify-between mb-2">
                    <h2 class="text-xl font-black text-gray-800 tracking-tighter">Messages</h2>
                    <span class="bg-primary-100 text-primary-600 text-[10px] font-black px-2 py-1 rounded-lg uppercase tracking-widest">
                        Inbox
                    </span>
                </div>
                <div class="relative">
                    <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-xs"></i>
                    <input type="text" placeholder="Search chats..." class="w-full pl-9 pr-4 py-2 bg-gray-50 border border-gray-100 rounded-xl text-xs font-bold focus:bg-white focus:border-primary-500 transition-all outline-none">
                </div>
            </div>

            <div class="flex-1 overflow-y-auto scrollbar-hide p-2 space-y-1">
                <?php
                // Get sidebar conversations if in single chat
                if ($ad_id > 0) {
                    try {
                        $stmt_side = $pdo->prepare("
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
                        $stmt_side->execute([$user_id, $user_id, $user_id, $user_id, $user_id, $user_id, $user_id, $user_id]);
                        $sidebar_conversations = $stmt_side->fetchAll();
                    } catch (Exception $e) { $sidebar_conversations = []; }
                } else {
                    $sidebar_conversations = $conversations;
                }

                if (empty($sidebar_conversations)): ?>
                    <div class="py-20 text-center px-6">
                        <div class="w-16 h-16 bg-white rounded-full flex items-center justify-center mx-auto mb-4 shadow-sm">
                            <i class="fas fa-comment-dots text-gray-200 text-2xl"></i>
                        </div>
                        <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest">No conversations</p>
                    </div>
                <?php else: foreach ($sidebar_conversations as $conv):
                    $other_uid = ($conv['sender_id'] == $user_id) ? $conv['receiver_id'] : $conv['sender_id'];
                    $is_active = ($ad_id == $conv['ad_id'] && $other_user_id == $other_uid);
                ?>
                    <a href="/chat.php?ad_id=<?php echo $conv['ad_id']; ?>&user_id=<?php echo $other_uid; ?>" 
                       class="group flex items-center gap-3 p-4 rounded-2xl transition-all duration-300 <?php echo $is_active ? 'bg-white shadow-lg ring-1 ring-black/5 scale-[1.02] z-10' : 'hover:bg-white/60'; ?>">
                        <div class="relative shrink-0">
                            <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-primary-100 to-primary-50 flex items-center justify-center text-primary-600 font-black text-lg shadow-inner">
                                <?php echo substr($conv['other_name'], 0, 1); ?>
                            </div>
                            <?php if ($conv['unread_count'] > 0): ?>
                                <span class="absolute -top-1 -right-1 w-5 h-5 bg-primary-600 text-white text-[9px] font-black rounded-full flex items-center justify-center border-2 border-white animate-pulse">
                                    <?php echo $conv['unread_count']; ?>
                                </span>
                            <?php endif; ?>
                        </div>
                        <div class="flex-1 min-w-0">
                            <div class="flex justify-between items-center mb-1">
                                <h4 class="text-sm font-black text-gray-800 truncate"><?php echo h($conv['other_name']); ?></h4>
                                <span class="text-[9px] font-bold text-gray-400 uppercase"><?php echo date('H:i', strtotime($conv['created_at'])); ?></span>
                            </div>
                            <p class="text-[10px] text-primary-600 font-black truncate uppercase tracking-tighter mb-1 opacity-70"><?php echo h($conv['ad_title']); ?></p>
                            <p class="text-xs text-gray-500 truncate <?php echo $conv['unread_count'] > 0 ? 'font-black text-gray-800' : ''; ?>">
                                <?php echo h($conv['message']); ?>
                            </p>
                        </div>
                    </a>
                <?php endforeach; endif; ?>
            </div>
        </div>

        <!-- Chat Area -->
        <div class="flex-1 flex flex-col min-w-0 bg-white <?php echo ($ad_id <= 0) ? 'hidden md:flex' : 'flex'; ?>">
            <?php if ($ad_id > 0): ?>
                <!-- Chat Header -->
                <div class="p-4 md:p-6 border-b border-gray-100 bg-white/80 backdrop-blur-md sticky top-0 z-20 flex items-center justify-between">
                    <div class="flex items-center gap-4 min-w-0">
                        <a href="/chat.php" class="md:hidden w-10 h-10 flex items-center justify-center text-gray-400 hover:text-primary-600 bg-gray-50 rounded-xl">
                            <i class="fas fa-chevron-left"></i>
                        </a>
                        <div class="relative hidden sm:block">
                            <div class="w-12 h-12 bg-primary-600 rounded-2xl flex items-center justify-center text-white font-black text-xl shadow-lg shadow-primary-200">
                                <?php echo substr($target_name, 0, 1); ?>
                            </div>
                            <div class="absolute -bottom-1 -right-1 w-4 h-4 bg-green-500 border-2 border-white rounded-full"></div>
                        </div>
                        <div class="min-w-0">
                            <div class="flex items-center gap-2">
                                <h3 class="font-black text-gray-800 truncate text-base md:text-lg"><?php echo h($target_name); ?></h3>
                                <?php if (($partner['verification_tier'] ?? '') === 'nin_verified'): ?>
                                    <i class="fas fa-check-circle text-blue-500 text-xs" title="NIN Verified"></i>
                                <?php endif; ?>
                            </div>
                            <a href="/ad.php?id=<?php echo $ad['id']; ?>" class="text-[10px] text-primary-600 font-black uppercase tracking-[1px] hover:underline flex items-center gap-1">
                                <span class="truncate"><?php echo h($ad['title']); ?></span>
                                <i class="fas fa-external-link-alt scale-75"></i>
                            </a>
                        </div>
                    </div>
                    <div class="flex gap-2">
                        <button class="w-10 h-10 rounded-xl bg-gray-50 text-gray-400 hover:bg-primary-50 hover:text-primary-600 transition flex items-center justify-center">
                            <i class="fas fa-phone-alt"></i>
                        </button>
                        <button class="w-10 h-10 rounded-xl bg-gray-50 text-gray-400 hover:bg-primary-50 hover:text-primary-600 transition flex items-center justify-center">
                            <i class="fas fa-ellipsis-v"></i>
                        </button>
                    </div>
                </div>

                <!-- Message List -->
                <div id="chatBox" class="flex-1 overflow-y-auto p-4 md:p-8 space-y-6 scroll-smooth bg-gray-50/50">
                    <?php
                    $last_date = "";
                    foreach ($messages as $msg):
                        $curr_date = date('Y-m-d', strtotime($msg['created_at']));
                        if ($curr_date != $last_date):
                    ?>
                        <div class="flex justify-center my-8">
                            <span class="bg-white border border-gray-100 text-[10px] font-black text-gray-400 px-6 py-2 rounded-2xl uppercase shadow-sm tracking-widest">
                                <?php echo $curr_date == date('Y-m-d') ? 'Today' : date('F d, Y', strtotime($msg['created_at'])); ?>
                            </span>
                        </div>
                    <?php $last_date = $curr_date; endif;
                        $is_me = ($msg['sender_id'] == $user_id);
                    ?>
                    <div class="flex flex-col <?php echo $is_me ? 'items-end' : 'items-start'; ?> group">
                        <div class="message-bubble <?php echo $is_me ? 'message-me' : 'message-other'; ?>">
                            <?php echo h($msg['message']); ?>
                        </div>
                        <div class="flex items-center gap-2 mt-1 px-1 opacity-0 group-hover:opacity-100 transition-opacity">
                            <span class="text-[9px] font-black text-gray-400"><?php echo date('H:i', strtotime($msg['created_at'])); ?></span>
                            <?php if ($is_me): ?>
                                <i class="fas fa-check-double text-[9px] <?php echo $msg['is_read'] ? 'text-primary-500' : 'text-gray-300'; ?>"></i>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>

                <!-- Chat Footer -->
                <div class="p-4 md:p-6 bg-white border-t border-gray-100">
                    <!-- Quick Replies -->
                    <div class="flex gap-2 overflow-x-auto pb-4 scrollbar-hide -mx-2 px-2">
                        <?php
                        $chips = ["Still available?", "Last price?", "Location?", "Swap?", "Interested!"];
                        foreach ($chips as $chip):
                        ?>
                            <button type="button" onclick="setQuickReply('<?php echo addslashes($chip); ?>')" 
                                    class="quick-reply-btn whitespace-nowrap bg-white border border-gray-200 text-gray-600 hover:border-primary-500 hover:text-primary-600 px-5 py-2.5 rounded-full text-[11px] font-black uppercase tracking-tight shadow-sm transition-all">
                                <?php echo $chip; ?>
                            </button>
                        <?php endforeach; ?>
                    </div>

                    <form method="POST" id="chatForm" class="flex gap-3 items-center bg-gray-50 p-2 rounded-[1.5rem] border-2 border-transparent focus-within:border-primary-100 focus-within:bg-white transition-all">
                        <button type="button" onclick="toggleEmojiPicker()" class="w-12 h-12 flex items-center justify-center text-gray-400 hover:text-primary-600 transition">
                            <i class="far fa-smile text-xl"></i>
                        </button>
                        <textarea id="messageInput" name="message" rows="1" 
                                  class="flex-1 bg-transparent py-3 px-1 outline-none text-sm font-bold text-gray-700 resize-none max-h-32" 
                                  placeholder="Type a message..." required 
                                  onkeydown="if(event.keyCode == 13 && !event.shiftKey) { this.form.submit(); return false; }"></textarea>
                        
                        <div class="relative">
                            <div id="emojiPicker" class="hidden absolute bottom-full right-0 mb-6 bg-white shadow-2xl border border-gray-100 rounded-3xl p-4 grid grid-cols-6 gap-3 z-50 w-72">
                                <h4 class="col-span-6 text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2 border-b pb-2">Quick Emojis</h4>
                                <?php
                                $emojis = ['😊', '🤝', '🔥', '👍', '💰', '📍', '🙌', '📱', '✅', '⭐', '🚗', '🏠', '😍', '🤔', '😎', '💯', '🛒', '⚡'];
                                foreach ($emojis as $e):
                                ?>
                                    <button type="button" onclick="addEmoji('<?php echo $e; ?>')" class="text-2xl hover:scale-125 transition active:scale-95"><?php echo $e; ?></button>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <button type="submit" class="bg-primary-600 text-white w-12 h-12 rounded-2xl flex items-center justify-center hover:bg-primary-700 hover:rotate-12 transition-all shadow-lg shadow-primary-200 active:scale-90">
                            <i class="fas fa-paper-plane text-base"></i>
                        </button>
                    </form>
                </div>

            <?php else: ?>
                <!-- Empty State -->
                <div class="flex-1 flex flex-col items-center justify-center p-10 text-center bg-gray-50/20">
                    <div class="relative mb-10">
                        <div class="w-40 h-40 bg-white rounded-full flex items-center justify-center shadow-2xl">
                            <i class="fas fa-comments text-6xl text-primary-100"></i>
                        </div>
                        <div class="absolute -top-2 -right-2 w-12 h-12 bg-primary-600 rounded-2xl flex items-center justify-center text-white shadow-lg animate-bounce">
                            <i class="fas fa-bolt"></i>
                        </div>
                    </div>
                    <h3 class="text-3xl font-black text-gray-800 tracking-tighter mb-4">Start a <span class="text-primary-600 italic">Conversation</span></h3>
                    <p class="text-gray-400 font-bold max-w-sm leading-relaxed">Choose a chat from the inbox on the left to start messaging. Buy, Sell, and Swap safely with Tibung Verified users.</p>
                    
                    <div class="mt-10 grid grid-cols-2 gap-4">
                        <div class="bg-white p-4 rounded-2xl shadow-sm border border-gray-100">
                            <i class="fas fa-shield-alt text-primary-600 mb-2 block"></i>
                            <span class="text-[10px] font-black uppercase text-gray-800">Secure Chats</span>
                        </div>
                        <div class="bg-white p-4 rounded-2xl shadow-sm border border-gray-100">
                            <i class="fas fa-check-double text-primary-600 mb-2 block"></i>
                            <span class="text-[10px] font-black uppercase text-gray-800">Real-time</span>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
    const chatBox = document.getElementById('chatBox');
    const messageInput = document.getElementById('messageInput');
    const emojiPicker = document.getElementById('emojiPicker');

    if (chatBox) {
        chatBox.scrollTop = chatBox.scrollHeight;
        // Scroll again after a short delay for mobile browsers
        setTimeout(() => {
            chatBox.scrollTop = chatBox.scrollHeight;
        }, 100);
    }

    function setQuickReply(text) {
        messageInput.value = text;
        messageInput.focus();
        // Auto-expand textarea
        messageInput.style.height = 'auto';
        messageInput.style.height = messageInput.scrollHeight + 'px';
    }

    function toggleEmojiPicker() {
        emojiPicker.classList.toggle('hidden');
    }

    function addEmoji(emoji) {
        messageInput.value += emoji;
        emojiPicker.classList.add('hidden');
        messageInput.focus();
    }

    // Auto-resize textarea
    if (messageInput) {
        messageInput.addEventListener('input', function() {
            this.style.height = 'auto';
            this.style.height = (this.scrollHeight) + 'px';
        });
    }

    // Close emoji picker when clicking outside
    document.addEventListener('click', (e) => {
        if (emojiPicker && !emojiPicker.contains(e.target) && !e.target.closest('button')) {
            emojiPicker.classList.add('hidden');
        }
    });
</script>

<?php include __DIR__ . '/templates/footer.php'; ?>
