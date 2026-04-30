<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/inc/functions.php';
require_once __DIR__ . '/inc/user_auth.php';
require_user();

$user_id = $_SESSION['user_id'];

// Get saved ads
$stmt = $pdo->prepare("SELECT a.*, (SELECT image_path FROM ad_images WHERE ad_id = a.id AND is_main = 1 LIMIT 1) as image, s.name as state_name, c.name as cat_name
                     FROM ads a
                     JOIN saved_ads sa ON a.id = sa.ad_id
                     JOIN states s ON a.state_id = s.id
                     JOIN categories c ON a.cat_id = c.id
                     WHERE sa.user_id = ? AND a.status = 'active'
                     ORDER BY sa.created_at DESC");
$stmt->execute([$user_id]);
$ads = $stmt->fetchAll();

$page_title = "My Saved Ads - " . ($settings['site_name'] ?? 'Classifieds');

include __DIR__ . '/templates/header.php';
?>

<div class="container mx-auto px-4 py-8">
    <div class="mb-10">
        <h1 class="text-3xl font-black text-gray-800 uppercase tracking-tighter italic">Saved <span class="text-red-500">Items</span></h1>
        <p class="text-gray-400 font-bold">Manage the deals you're interested in.</p>
    </div>

    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-6">
        <?php foreach ($ads as $ad): ?>
        <div class="bg-white rounded-[2rem] shadow-sm overflow-hidden border border-gray-100 group relative">
            <a href="<?php echo generate_ad_url($ad); ?>">
                <?php $ad_img = $ad['image'] ? '/uploads/ads/'.$ad['image'] : 'https://placehold.co/400x300?text=No+Image'; ?>
                <div class="relative h-48 overflow-hidden fit-to-frame" style="--bg-image: url('<?php echo $ad_img; ?>')">
                    <img src="<?php echo $ad_img; ?>" class="group-hover:scale-110 transition duration-700">
                    <div class="absolute bottom-4 left-4 z-10">
                        <span class="bg-black/50 backdrop-blur-md text-white text-[9px] font-black px-3 py-1 rounded-full uppercase"><?php echo h($ad['cat_name']); ?></span>
                    </div>
                </div>
            </a>
            <div class="p-5">
                <h4 class="text-sm font-black text-gray-800 line-clamp-2 h-10 mb-4 group-hover:text-primary-600 transition"><?php echo h($ad['title']); ?></h4>
                <div class="flex justify-between items-end">
                    <div>
                        <p class="text-primary-600 font-black text-xl">₦<?php echo number_format($ad['price']); ?></p>
                        <p class="text-[10px] text-gray-400 font-bold uppercase tracking-widest mt-1"><i class="fas fa-map-marker-alt text-primary-500 mr-1"></i> <?php echo h($ad['state_name']); ?></p>
                    </div>
                    <button onclick="toggleSave(<?php echo $ad['id']; ?>, this)" class="w-10 h-10 rounded-2xl bg-red-50 flex items-center justify-center text-red-500 transition-colors duration-300">
                        <i class="fas fa-heart text-sm"></i>
                    </button>
                </div>
            </div>
        </div>
        <?php endforeach; ?>

        <?php if (empty($ads)): ?>
            <div class="col-span-full bg-white p-20 rounded-[3rem] text-center border-2 border-dashed border-gray-100">
                <div class="w-24 h-24 bg-gray-50 rounded-full flex items-center justify-center mx-auto mb-8">
                    <i class="far fa-heart text-gray-200 text-4xl"></i>
                </div>
                <h2 class="text-2xl font-black text-gray-800 mb-2 tracking-tighter">Your wishlist is empty</h2>
                <p class="text-gray-400 font-bold">Save items you like to keep track of them!</p>
                <a href="/" class="bg-primary-600 text-white px-10 py-5 rounded-2xl font-black hover:bg-primary-700 transition uppercase shadow-2xl inline-block mt-10 tracking-widest text-xs">START BROWSING</a>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
function toggleSave(ad_id, btn) {
    fetch(`/api/save_ad.php?ad_id=${ad_id}`)
        .then(res => res.json())
        .then(data => {
            if (data.success && !data.saved) {
                // If removed from wishlist, hide the card
                btn.closest('.group').remove();
                if (document.querySelectorAll('.group').length === 0) {
                    location.reload();
                }
            }
        });
}
</script>

<?php include __DIR__ . '/templates/footer.php'; ?>
