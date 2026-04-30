<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/inc/functions.php';
require_once __DIR__ . '/inc/user_auth.php';
require_user();

$user_id = $_SESSION['user_id'];

// Get user profile
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

// Get user ads
$stmt = $pdo->prepare("SELECT a.*, (SELECT image_path FROM ad_images WHERE ad_id = a.id AND is_main = 1 LIMIT 1) as image, s.name as state_name, c.name as cat_name,
                     (SELECT status FROM payments WHERE ad_id = a.id ORDER BY created_at DESC LIMIT 1) as last_payment_status,
                     (SELECT reject_reason FROM payments WHERE ad_id = a.id ORDER BY created_at DESC LIMIT 1) as last_payment_reject_reason
                     FROM ads a
                     LEFT JOIN states s ON a.state_id = s.id
                     LEFT JOIN categories c ON a.cat_id = c.id
                     WHERE a.user_id = ?
                     ORDER BY a.created_at DESC");
$stmt->execute([$user_id]);
$user_ads = $stmt->fetchAll();

include __DIR__ . '/templates/header.php';
?>

<main class="flex-1">
<div class="container mx-auto px-4 py-8">
    <div class="flex flex-col lg:flex-row gap-10">
        <!-- Profile Sidebar -->
        <aside class="w-full lg:w-1/4">
            <div class="bg-white p-8 rounded-[2rem] shadow-sm border border-gray-100 mb-8 sticky top-24">
                <div class="relative w-28 h-28 mx-auto mb-6">
                    <div class="w-full h-full bg-primary-100 rounded-full flex items-center justify-center text-primary-600 text-4xl font-black uppercase shadow-inner">
                        <?php echo substr($user['full_name'], 0, 1); ?>
                    </div>
                    <div class="absolute bottom-1 right-1 w-8 h-8 bg-white rounded-full flex items-center justify-center shadow-md">
                        <i class="fas fa-camera text-gray-300 text-xs"></i>
                    </div>
                </div>

                <div class="text-center">
                    <h3 class="font-black text-xl text-gray-800 tracking-tight mb-1"><?php echo h($user['full_name']); ?></h3>
                    <p class="text-[10px] text-gray-400 font-bold mb-4 uppercase tracking-widest"><?php echo h($user['email']); ?></p>
                    <?php if ($user['is_verified']): ?>
                        <div class="inline-flex items-center gap-2 bg-primary-50 text-primary-700 px-4 py-1.5 rounded-full text-[10px] font-black border border-primary-100 uppercase tracking-widest">
                            <i class="fas fa-check-circle"></i> <?php echo ($user["verification_tier"] == "business_verified" ? strtoupper(h($settings['site_name'] ?? 'Classifieds')) . " BUSINESS" : "NIN VERIFIED"); ?>
                        </div>
                    <?php else: ?>
                        <div class="inline-flex items-center gap-2 bg-gray-50 text-gray-500 px-4 py-1.5 rounded-full text-[10px] font-black border border-gray-100 uppercase tracking-widest">
                            PHONE VERIFIED
                        </div>
                    <?php endif; ?>
                </div>

                <div class="mt-10 pt-8 border-t border-gray-50 space-y-2">
                    <a href="/profile" class="flex items-center gap-3 p-4 rounded-2xl bg-primary-600 text-white font-black text-xs uppercase tracking-widest shadow-xl shadow-primary-200 transition transform hover:-translate-y-0.5">
                        <i class="fas fa-th-large"></i>
                        <span>My Inventory</span>
                    </a>
                    <a href="/support" class="flex items-center gap-3 p-4 rounded-2xl text-gray-500 hover:bg-gray-50 font-black text-xs uppercase tracking-widest transition">
                        <i class="fas fa-headset opacity-50"></i>
                        <span>Help Center</span>
                    </a>
                    <a href="/logout" class="flex items-center gap-3 p-4 rounded-2xl text-red-400 hover:bg-red-50 font-black text-xs uppercase tracking-widest transition">
                        <i class="fas fa-power-off opacity-50"></i>
                        <span>Sign Out</span>
                    </a>
                </div>
            </div>
        </aside>

        <!-- Main Profile Content -->
        <div class="w-full lg:w-3/4">
            <!-- Seller Hub Quick Access -->
            <div class="mb-12">
                <div class="flex items-center gap-3 mb-6">
                    <div class="w-2 h-6 bg-primary-600 rounded-full"></div>
                    <h3 class="text-[11px] font-black text-gray-800 uppercase tracking-[3px]">Seller Dashboard</h3>
                </div>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                    <a href="/post-ad" class="group bg-gradient-to-br from-primary-600 to-primary-700 p-6 rounded-[2rem] shadow-xl shadow-primary-100 border border-primary-500 flex flex-col items-center text-white hover:scale-105 transition-all duration-300">
                        <div class="w-12 h-12 rounded-2xl bg-white/20 flex items-center justify-center mb-3 group-hover:rotate-12 transition-transform">
                            <i class="fas fa-plus text-xl"></i>
                        </div>
                        <span class="text-[11px] font-black uppercase tracking-widest">Create Ad</span>
                    </a>
                    <a href="/support" class="group bg-white p-6 rounded-[2rem] shadow-sm border border-gray-100 flex flex-col items-center hover:border-blue-400 transition-all duration-300">
                        <div class="w-12 h-12 rounded-2xl bg-blue-50 flex items-center justify-center mb-3 text-blue-600 group-hover:bg-blue-600 group-hover:text-white transition-colors">
                            <i class="fas fa-comments text-xl"></i>
                        </div>
                        <span class="text-[11px] font-black text-gray-800 uppercase tracking-widest">Messages</span>
                    </a>
                    <a href="/profile_edit" class="group bg-white p-6 rounded-[2rem] shadow-sm border border-gray-100 flex flex-col items-center hover:border-yellow-400 transition-all duration-300">
                        <div class="w-12 h-12 rounded-2xl bg-yellow-50 flex items-center justify-center mb-3 text-yellow-600 group-hover:bg-yellow-400 group-hover:text-white transition-colors">
                            <i class="fas fa-user-edit text-xl"></i>
                        </div>
                        <span class="text-[11px] font-black text-gray-800 uppercase tracking-widest">Settings</span>
                    </a>
                    <a href="/logout" class="group bg-white p-6 rounded-[2rem] shadow-sm border border-gray-100 flex flex-col items-center hover:border-red-400 transition-all duration-300">
                        <div class="w-12 h-12 rounded-2xl bg-red-50 flex items-center justify-center mb-3 text-red-600 group-hover:bg-red-600 group-hover:text-white transition-colors">
                            <i class="fas fa-power-off text-xl"></i>
                        </div>
                        <span class="text-[11px] font-black text-gray-800 uppercase tracking-widest">Logout</span>
                    </a>
                </div>
            </div>

            <!-- Seller Reviews Section -->
            <div class="mb-12">
                <div class="flex items-center gap-3 mb-6">
                    <div class="w-2 h-6 bg-yellow-500 rounded-full"></div>
                    <h3 class="text-[11px] font-black text-gray-800 uppercase tracking-[3px]">My Ratings & Reviews</h3>
                </div>
                <div class="bg-white rounded-[2rem] p-8 shadow-sm border border-gray-100">
                    <div class="space-y-6">
                        <?php
                        $stmt_reviews = $pdo->prepare("SELECT r.*, u.full_name FROM reviews r JOIN users u ON r.reviewer_id = u.id WHERE r.seller_id = ? ORDER BY r.submitted_at DESC");
                        $stmt_reviews->execute([$user_id]);
                        $reviews = $stmt_reviews->fetchAll();
                        foreach ($reviews as $rev):
                        ?>
                            <div class="border-b border-gray-50 pb-6 last:border-0">
                                <div class="flex justify-between items-start mb-2">
                                    <div class="flex flex-col">
                                        <h5 class="text-xs font-black text-gray-800 uppercase"><?php echo h($rev['full_name']); ?></h5>
                                        <span class="text-[9px] text-gray-400 font-bold"><?php echo date('d M, Y', strtotime($rev['submitted_at'])); ?></span>
                                    </div>
                                    <div class="flex text-yellow-400 text-[10px]">
                                        <?php for($i=1; $i<=5; $i++) echo $i <= $rev['stars'] ? '<i class="fas fa-star"></i>' : '<i class="far fa-star"></i>'; ?>
                                    </div>
                                </div>
                                <p class="text-xs text-gray-600 font-medium mb-4"><?php echo h($rev['body']); ?></p>

                                <?php if ($rev['reply_text']): ?>
                                    <div class="bg-primary-50 p-4 rounded-2xl ml-4 border-l-4 border-primary-500">
                                        <p class="text-[10px] font-black text-primary-600 uppercase mb-1">Your Reply</p>
                                        <p class="text-[11px] text-gray-600 italic font-medium"><?php echo h($rev['reply_text']); ?></p>
                                    </div>
                                <?php else: ?>
                                    <button onclick="toggleReplyForm(<?php echo $rev['id']; ?>)" class="text-[9px] font-black text-primary-600 uppercase tracking-widest hover:underline ml-4"><i class="fas fa-reply mr-1"></i> Public Reply</button>
                                    <form id="replyForm-<?php echo $rev['id']; ?>" class="hidden mt-4 ml-4 space-y-3 bg-gray-50 p-4 rounded-2xl">
                                        <input type="hidden" name="review_id" value="<?php echo $rev['id']; ?>">
                                        <textarea name="reply_text" class="w-full p-3 bg-white border border-gray-200 rounded-xl text-xs font-bold text-gray-700 focus:ring-2 focus:ring-primary-500 transition" rows="2" placeholder="Write your response..."></textarea>
                                        <div class="flex gap-2">
                                            <button type="submit" class="bg-primary-600 text-white px-4 py-2 rounded-lg font-black text-[9px] uppercase tracking-widest hover:bg-primary-700 transition">Post Reply</button>
                                            <button type="button" onclick="toggleReplyForm(<?php echo $rev['id']; ?>)" class="text-gray-400 font-black text-[9px] uppercase tracking-widest">Cancel</button>
                                        </div>
                                    </form>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                        <?php if (empty($reviews)): ?>
                            <div class="text-center py-4">
                                <p class="text-xs text-gray-400 font-bold">You haven't received any reviews yet.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-10 gap-4">
                <h1 class="text-4xl font-black text-gray-800 uppercase tracking-tighter italic">My Marketplace <span class="text-primary-600 ml-2">Listing</span></h1>
                <a href="/post-ad" class="bg-yellow-500 text-white px-10 py-4 rounded-2xl font-black hover:bg-yellow-600 transition shadow-2xl flex items-center gap-3 active:scale-95">
                    <i class="fas fa-plus-circle"></i>
                    <span>SELL SOMETHING</span>
                </a>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">
            <?php foreach ($user_ads as $ad): ?>
            <div class="bg-white rounded-2xl shadow-sm overflow-hidden border border-gray-100 group">
                <div class="relative aspect-square overflow-hidden rounded-xl">
                    <img src="<?php echo $ad['image'] ? '/uploads/ads/'.$ad['image'] : 'https://placehold.co/400x300?text=No+Image'; ?>" class="w-full h-full object-cover">
                    <span class="absolute top-4 left-4 text-[10px] font-bold px-3 py-1 rounded-full uppercase shadow-md <?php
                        echo $ad['status'] == 'active' ? 'bg-primary-500 text-white' : ($ad['status'] == 'pending' ? 'bg-yellow-400 text-white' : ($ad['status'] == 'expired' ? 'bg-gray-700 text-white' : 'bg-red-500 text-white'));
                    ?>">
                        <?php echo $ad['status']; ?>
                    </span>
                    <?php if ($ad['is_featured']): ?>
                        <span class="absolute top-4 right-4 bg-yellow-400 text-white text-[10px] font-bold px-3 py-1 rounded-full uppercase shadow-md"><i class="fas fa-rocket"></i> BOOSTED</span>
                    <?php endif; ?>
                </div>
                <div class="p-5">
                    <h4 class="font-bold text-gray-800 mb-3 truncate group-hover:text-primary-600 transition"><?php echo h($ad['title']); ?></h4>
                    <p class="text-primary-600 font-extrabold text-lg mb-4">₦<?php echo number_format($ad['price']); ?></p>

                    <div class="flex flex-col gap-2">
                        <?php if ($ad['last_payment_status'] == 'failed'): ?>
                            <div class="bg-red-50 p-2 rounded border border-red-100 mb-1">
                                <p class="text-[9px] text-red-600 font-bold uppercase">Boost Payment Rejected</p>
                                <p class="text-[8px] text-red-500 italic"><?php echo h($ad['last_payment_reject_reason']); ?></p>
                            </div>
                        <?php endif; ?>

                        <div class="flex gap-2">
                        <?php if (!$ad['is_featured'] && $ad['status'] == 'active'): ?>
                            <a href="boost.php?ad_id=<?php echo $ad['id']; ?>" class="flex-1 text-center bg-primary-600 text-white py-2 rounded-lg text-xs font-bold hover:bg-primary-700 transition uppercase shadow-md tracking-wider">
                                <?php echo ($ad['last_payment_status'] == 'failed') ? 'Retry Boost' : 'Boost Ad'; ?>
                            </a>
                        <?php endif; ?>
                        <?php if ($ad['status'] != 'sold' && $ad['status'] != 'expired'): ?>
                            <a href="edit-ad.php?id=<?php echo $ad['id']; ?>" class="flex-1 text-center bg-yellow-500 text-white py-2 rounded-lg text-xs font-bold hover:bg-yellow-600 transition uppercase shadow-md tracking-wider">Edit</a>
                        <?php else: ?>
                            <a href="api/republish.php?id=<?php echo $ad['id']; ?>" class="flex-1 text-center bg-blue-600 text-white py-2 rounded-lg text-xs font-bold hover:bg-blue-700 transition uppercase shadow-md tracking-wider">Republish</a>
                        <?php endif; ?>
                        <a href="<?php echo generate_ad_url($ad); ?>" class="flex-1 text-center bg-gray-100 text-gray-600 py-2 rounded-lg text-xs font-bold hover:bg-gray-200 transition uppercase tracking-wider border border-gray-200">View</a>
                    </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>

            <?php if (empty($user_ads)): ?>
            <div class="col-span-full bg-white p-20 rounded-2xl text-center border-2 border-dashed border-gray-100">
                <div class="w-20 h-20 bg-gray-50 rounded-full flex items-center justify-center mx-auto mb-6">
                    <i class="fas fa-ad text-gray-200 text-3xl"></i>
                </div>
                <h2 class="text-xl font-bold text-gray-400 mb-2">You haven't posted any ads yet</h2>
                <p class="text-gray-300 font-bold mb-8">Start selling today and reach millions of buyers!</p>
                <a href="/post-ad" class="bg-primary-600 text-white px-10 py-4 rounded-xl font-bold hover:bg-primary-700 transition uppercase shadow-lg tracking-widest inline-block">POST YOUR FIRST AD</a>
            </div>
            <?php endif; ?>
            </div>
        </div>
    </div>
</div>
</main>

<script>
function toggleReplyForm(id) {
    const form = document.getElementById('replyForm-' + id);
    form.classList.toggle('hidden');
}

document.querySelectorAll('[id^="replyForm-"]').forEach(form => {
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        fetch('/api/reply_review.php', {
            method: 'POST',
            body: new FormData(this)
        }).then(res => res.json()).then(data => {
            alert(data.message);
            if(data.success) location.reload();
        });
    });
});
</script>

<?php include __DIR__ . '/templates/footer.php'; ?>
