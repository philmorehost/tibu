<!-- Mobile Bottom Navigation (Hidden on Desktop) -->
<div class="md:hidden fixed bottom-0 left-0 right-0 bg-white border-t border-gray-100 z-[90] px-4 py-3 shadow-[0_-5px_15px_rgba(0,0,0,0.05)]">
    <div class="flex justify-between items-center max-w-lg mx-auto">
        <a href="/" class="flex flex-col items-center gap-1 <?php echo $_SERVER['PHP_SELF'] == '/index.php' ? 'text-primary-600' : 'text-gray-400'; ?>">
            <i class="fas fa-home text-lg"></i>
            <span class="text-[9px] font-black uppercase">Home</span>
        </a>

        <a href="/saved_ads" class="flex flex-col items-center gap-1 text-gray-400 relative">
            <i class="fas fa-heart text-lg"></i>
            <span class="text-[9px] font-black uppercase">Saved</span>
            <?php if (is_user_logged_in()): ?>
                <?php
                $s_count = $pdo->prepare("SELECT COUNT(*) FROM saved_ads WHERE user_id = ?");
                $s_count->execute([$_SESSION['user_id']]);
                $count = $s_count->fetchColumn();
                if ($count > 0):
                ?>
                <span id="savedCounter" class="absolute -top-1 -right-1 bg-red-500 text-white text-[8px] w-4 h-4 rounded-full flex items-center justify-center font-black <?php echo $count == 0 ? 'hidden' : ''; ?>"><?php echo $count; ?></span>
                <?php endif; ?>
            <?php endif; ?>
        </a>

        <a href="/post-ad" class="flex flex-col items-center -mt-8">
            <div class="w-14 h-14 bg-yellow-500 rounded-full flex items-center justify-center text-white shadow-xl border-4 border-white active:scale-90 transition-transform">
                <i class="fas fa-plus text-xl"></i>
            </div>
            <span class="text-[9px] font-black text-yellow-600 uppercase mt-1">Sell</span>
        </a>

        <a href="/chat" class="flex flex-col items-center gap-1 <?php echo (strpos($_SERVER['PHP_SELF'], '/chat.php') !== false) ? 'text-primary-600' : 'text-gray-400'; ?>">
            <i class="fas fa-comments text-lg"></i>
            <span class="text-[9px] font-black uppercase">Chats</span>
        </a>

        <a href="/profile" class="flex flex-col items-center gap-1 <?php echo (strpos($_SERVER['PHP_SELF'], '/profile.php') !== false) ? 'text-primary-600' : 'text-gray-400'; ?>">
            <i class="fas fa-user text-lg"></i>
            <span class="text-[9px] font-black uppercase">Profile</span>
        </a>
    </div>
</div>
<!-- Space for bottom nav -->
<div class="md:hidden h-20"></div>
