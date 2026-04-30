<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/inc/functions.php';
require_once __DIR__ . '/inc/user_auth.php';

if (!isset($_GET['id'])) {
    redirect('index.php');
}

$id = (int)$_GET['id'];
$stmt = $pdo->prepare("SELECT a.*, u.full_name as seller_name, u.phone as seller_phone, u.is_verified, u.verification_tier, u.created_at as seller_created_at, c.name as cat_name, s.name as state_name, l.name as lga_name
                     FROM ads a
                     JOIN users u ON a.user_id = u.id
                     JOIN categories c ON a.cat_id = c.id
                     JOIN states s ON a.state_id = s.id
                     JOIN lgas l ON a.lga_id = l.id
                     WHERE a.id = ? AND u.is_suspended = 0");
$stmt->execute([$id]);
$ad = $stmt->fetch();

if (!$ad || ($ad['status'] !== 'active' && (!isset($_SESSION['user_id']) || $_SESSION['user_id'] != $ad['user_id']) && !isset($_SESSION['admin_id']))) {
    redirect('index.php', 'Ad not found or pending moderation.');
}

// Log view for analytics (Feature 07)
$viewer_id = $_SESSION["user_id"] ?? null;
$pdo->prepare("INSERT INTO seller_analytics (ad_id, viewer_id, source, ip_address) VALUES (?, ?, ?, ?)")->execute([$id, $viewer_id, $_GET["source"] ?? "direct", get_client_ip()]);

// Increment views
$pdo->prepare("UPDATE ads SET views = views + 1 WHERE id = ?")->execute([$id]);

// Get images
$stmt = $pdo->prepare("SELECT image_path, is_main FROM ad_images WHERE ad_id = ? ORDER BY is_main DESC");
$stmt->execute([$id]);
$images = $stmt->fetchAll();

// Calculate Deal Safety Score (Feature 08)
$safety_score = calculate_safety_score(['verification_tier' => $ad['verification_tier'], 'is_verified' => $ad['is_verified'], 'created_at' => $ad['seller_created_at']], $ad);

// SEO Meta Data
$meta = generate_meta_tags($ad['title'], $ad['description'], $ad['cat_name'] . " " . $ad['state_name']);
$page_title = $meta['title'] . " - " . ($settings['site_name'] ?? 'Classifieds');
$page_desc = $meta['description'];
$page_keywords = $meta['keywords'];

// Get similar ads
$stmt = $pdo->prepare("SELECT a.*, (SELECT image_path FROM ad_images WHERE ad_id = a.id AND is_main = 1 LIMIT 1) as image, s.name as state_name, c.name as cat_name
                     FROM ads a
                     JOIN states s ON a.state_id = s.id
                     JOIN categories c ON a.cat_id = c.id
                     JOIN users u ON a.user_id = u.id
                     WHERE a.cat_id = ? AND a.status = 'active' AND a.id != ? AND u.is_suspended = 0
                     ORDER BY a.created_at DESC LIMIT 4");
$stmt->execute([$ad['cat_id'], $id]);
$similar_ads = $stmt->fetchAll();

include __DIR__ . '/templates/header.php';
?>

<div class="container mx-auto px-4 py-8">
    <?php if ($ad['status'] !== 'active'): ?>
        <div class="bg-yellow-100 border-l-4 border-yellow-500 text-yellow-700 p-4 mb-6 rounded shadow-sm flex items-center justify-between" role="alert">
            <div>
                <p class="font-bold">Ad Preview Mode (Status: <?php echo h($ad['status']); ?>)</p>
                <p class="text-sm">Only you and admins can see this page.</p>
            </div>
            <?php if ($ad['status'] === 'declined' && isset($_SESSION['user_id']) && $_SESSION['user_id'] == $ad['user_id']): ?>
                <a href="edit-ad.php?id=<?php echo $ad['id']; ?>" class="bg-yellow-500 hover:bg-yellow-600 text-white font-bold py-2 px-4 rounded transition shadow-md">Edit & Re-submit</a>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <div class="flex flex-col lg:flex-row gap-8">
        <!-- Main Content (Left) -->
        <div class="lg:w-2/3">
            <div class="bg-white rounded-3xl shadow-sm overflow-hidden mb-8 border border-gray-100">
                <!-- Gallery -->
                <div class="product-gallery-wrapper mb-8">
                    <?php $main_img = isset($images[0]) ? '/uploads/ads/'.$images[0]['image_path'] : 'https://placehold.co/800x600?text=No+Image'; ?>
                    <div id="mainImageContainer" class="main-image-container relative h-[500px] w-full bg-gray-100 overflow-hidden group flex items-center justify-center rounded-2xl cursor-zoom-in shadow-inner" style="--bg-image: url('<?php echo $main_img; ?>')" onclick="openLightbox()">
                        <div class="absolute inset-0 bg-cover bg-center blur-2xl brightness-[0.8] opacity-50 transition-all duration-500 scale-110" style="background-image: var(--bg-image)"></div>
                        <img id="mainImage" src="<?php echo $main_img; ?>" class="relative z-10 w-full h-auto object-cover transition-all duration-300 shadow-2xl">

                        <?php if (count($images) > 1): ?>
                            <button onclick="prevImage(event)" class="absolute left-4 top-1/2 -translate-y-1/2 w-12 h-12 bg-black/20 hover:bg-black/40 text-white rounded-full flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity z-20 backdrop-blur-sm">
                                <i class="fas fa-chevron-left text-xl"></i>
                            </button>
                            <button onclick="nextImage(event)" class="absolute right-4 top-1/2 -translate-y-1/2 w-12 h-12 bg-black/20 hover:bg-black/40 text-white rounded-full flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity z-20 backdrop-blur-sm">
                                <i class="fas fa-chevron-right text-xl"></i>
                            </button>
                        <?php endif; ?>
                    </div>

                    <?php if (count($images) > 1): ?>
                        <div class="thumbnail-strip flex gap-3 overflow-x-auto py-4 scrollbar-hide">
                            <?php foreach ($images as $index => $img): ?>
                                <img src="/uploads/ads/<?php echo $img['image_path']; ?>" class="thumbnail-item w-20 h-20 min-w-[80px] rounded-lg object-cover cursor-pointer border-2 <?php echo $index === 0 ? 'border-green-500' : 'border-transparent'; ?> transition-all shadow-sm" onclick="updateMainImage(<?php echo $index; ?>)">
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="p-8 md:p-12">
                    <?php if (!$ad["is_verified"]): ?>
                    <div class="bg-yellow-50 border-l-4 border-yellow-400 p-5 mb-8 rounded-r-2xl shadow-sm">
                        <div class="flex items-center gap-4">
                            <div class="w-10 h-10 bg-yellow-100 rounded-full flex items-center justify-center text-yellow-600">
                                <i class="fas fa-exclamation-triangle"></i>
                            </div>
                            <p class="text-xs font-bold text-yellow-800">This seller has not completed identity verification. For safer transactions, we recommend only dealing with <span class="text-primary-600">Verified Sellers</span>.</p>
                        </div>
                    </div>
                    <?php endif; ?>

                    <?php if (strpos(strtoupper($ad["cat_name"]), "PROPERTY") !== false): ?>
                    <div class="bg-red-50 border-l-4 border-red-500 p-5 mb-8 rounded-r-2xl shadow-sm">
                        <div class="flex items-center gap-4">
                            <div class="w-10 h-10 bg-red-100 rounded-full flex items-center justify-center text-red-600">
                                <i class="fas fa-shield-alt"></i>
                            </div>
                            <p class="text-xs font-black text-red-800 uppercase tracking-tight leading-relaxed">NEVER pay a deposit or rent in advance without first: (1) visiting the property in person, (2) confirming the seller's identity, and (3) signing a written agreement.</p>
                        </div>
                    </div>
                    <?php endif; ?>

                    <div class="flex flex-col md:flex-row justify-between items-start mb-8 gap-6">
                        <div>
                            <h1 class="text-3xl md:text-4xl font-black text-gray-800 leading-tight mb-4"><?php echo h($ad['title']); ?></h1>
                            <div class="flex flex-wrap gap-3">
                                <span class="bg-gray-100 text-gray-500 px-4 py-2 rounded-xl text-xs font-bold flex items-center gap-2"><i class="fas fa-map-marker-alt text-primary-500"></i> <?php echo h($ad['state_name']); ?>, <?php echo h($ad['lga_name']); ?></span>
                                <span class="bg-gray-100 text-gray-500 px-4 py-2 rounded-xl text-xs font-bold flex items-center gap-2"><i class="fas fa-clock text-blue-500"></i> <?php echo date('d M, Y', strtotime($ad['created_at'])); ?></span>

                                <?php
                                $color = $safety_score >= 80 ? "green" : ($safety_score >= 50 ? "yellow" : "red");
                                $icon = $safety_score >= 80 ? "fa-shield-alt" : "fa-exclamation-circle";
                                ?>
                                <div class="flex items-center gap-2 bg-<?php echo $color; ?>-50 px-4 py-2 rounded-xl border border-<?php echo $color; ?>-100 shadow-sm">
                                    <i class="fas <?php echo $icon; ?> text-<?php echo $color; ?>-500 text-xs"></i>
                                    <span class="text-[10px] font-black text-<?php echo $color; ?>-700 uppercase tracking-widest">Deal Safety: <?php echo $safety_score; ?>/100</span>
                                </div>

                                <?php
                                $stmt_prop = $pdo->prepare("SELECT * FROM property_declarations WHERE ad_id = ?");
                                $stmt_prop->execute([$ad["id"]]);
                                $prop = $stmt_prop->fetch();
                                if ($prop):
                                    $p_role = $prop["declared_role"];
                                    $p_verified = $prop["verified"];
                                    $p_color = $p_verified ? "green" : "amber";
                                    $p_icon = $p_role == "owner" ? "fa-home" : "fa-briefcase";
                                ?>
                                <div class="flex items-center gap-2 bg-<?php echo $p_color; ?>-50 px-4 py-2 rounded-xl border border-<?php echo $p_color; ?>-100 shadow-sm">
                                    <i class="fas <?php echo $p_icon; ?> text-<?php echo $p_color; ?>-500 text-xs"></i>
                                    <span class="text-[10px] font-black text-<?php echo $p_color; ?>-700 uppercase tracking-widest">
                                        <?php echo $p_verified ? h($settings['site_name'] ?? 'Classifieds') . " Verified" : "Self-Declared"; ?> <?php echo ucfirst($p_role); ?>
                                    </span>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="text-left md:text-right w-full md:w-auto">
                            <?php if ($ad['listing_type'] != 'for_swap'): ?>
                                <p class="text-4xl font-black text-primary-600">₦<?php echo number_format($ad['price']); ?></p>
                            <?php endif; ?>
                            <?php if ($ad['listing_type'] != 'for_sale'): ?>
                                <div class="inline-flex items-center gap-2 bg-blue-600 text-white text-[10px] font-black px-4 py-2 rounded-xl uppercase tracking-widest mt-2 shadow-lg shadow-blue-100">
                                    <i class="fas fa-sync-alt"></i> Available for Swap
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="border-t border-gray-50 pt-10">
                        <?php if ($ad['listing_type'] != 'for_sale'): ?>
                            <div class="bg-blue-50/50 p-8 rounded-3xl border border-blue-100 mb-10">
                                <h3 class="text-blue-800 font-black text-xs uppercase tracking-[3px] mb-6 flex items-center gap-3">
                                    <div class="w-2 h-2 bg-blue-600 rounded-full"></div> Swap Terms
                                </h3>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                                    <div class="bg-white p-4 rounded-2xl shadow-sm">
                                        <p class="text-[10px] text-blue-400 font-black uppercase mb-1">Estimated Value</p>
                                        <p class="text-xl font-black text-blue-900">₦<?php echo number_format($ad['estimated_value']); ?></p>
                                    </div>
                                    <div class="bg-white p-4 rounded-2xl shadow-sm">
                                        <p class="text-[10px] text-blue-400 font-black uppercase mb-1">Desired Item</p>
                                        <p class="text-sm font-bold text-blue-900"><?php echo $ad['swap_preference'] ?: 'Open to all offers'; ?></p>
                                    </div>
                                </div>
                                <?php if ($ad['allow_cash_topup']): ?>
                                    <div class="mt-6 flex items-center gap-2 text-[11px] font-black text-primary-600 uppercase tracking-widest">
                                        <i class="fas fa-check-circle"></i> Item + Cash top-up accepted
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>

                        <?php
                        $extra_data = !empty($ad['ad_data']) ? json_decode($ad['ad_data'], true) : null;
                        if ($extra_data):
                        ?>
                            <div class="mb-10">
                                <h3 class="text-xs font-black text-gray-400 uppercase tracking-[3px] mb-6">Specifications</h3>
                                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                                    <?php foreach ($extra_data as $key => $value): if(empty($value)) continue; ?>
                                        <div class="bg-gray-50 p-4 rounded-2xl border border-gray-100 hover:bg-white transition-colors">
                                            <p class="text-[9px] text-gray-400 font-black uppercase mb-1"><?php echo h(str_replace('_', ' ', $key)); ?></p>
                                            <p class="text-xs font-black text-gray-700"><?php echo h(is_array($value) ? implode(', ', $value) : $value); ?></p>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endif; ?>

                        <div class="mb-10">
                            <h3 class="text-xs font-black text-gray-400 uppercase tracking-[3px] mb-6">Description</h3>
                            <div class="text-gray-600 leading-relaxed font-medium space-y-4">
                                <?php echo nl2br(h($ad['description'])); ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Sidebar (Right) -->
        <div class="lg:w-1/3">
            <div class="bg-white p-8 rounded-[2.5rem] shadow-sm border border-gray-100 sticky top-24">
                <div class="flex items-center gap-5 mb-8">
                    <div class="w-20 h-20 bg-gradient-to-br from-primary-50 to-primary-100 rounded-3xl flex items-center justify-center text-primary-600 text-3xl font-black uppercase shadow-inner">
                        <?php echo substr($ad['seller_name'], 0, 1); ?>
                    </div>
                    <div>
                        <h4 class="font-black text-xl text-gray-800 tracking-tight"><?php echo h($ad['seller_name']); ?></h4>
                        <div class="flex flex-col gap-2 mt-2">
                            <?php if ($ad['is_verified']): ?>
                                <div class="inline-flex items-center gap-2 bg-primary-50 text-primary-600 px-3 py-1.5 rounded-xl text-[10px] font-black border border-primary-100 uppercase tracking-widest shadow-sm">
                            <?php
                            $stmt_rep = $pdo->prepare("SELECT badge_tier FROM seller_reputation WHERE user_id = ?");
                            $stmt_rep->execute([$ad["user_id"]]);
                            $rep = $stmt_rep->fetch();
                            if ($rep && $rep["badge_tier"] == "trusted"): ?>
                                <div class="inline-flex items-center gap-2 bg-yellow-50 text-yellow-700 px-3 py-1.5 rounded-xl text-[10px] font-black border border-yellow-100 uppercase tracking-widest shadow-sm">
                                    <i class="fas fa-crown"></i> <?php echo strtoupper(h($settings['site_name'] ?? 'Classifieds')); ?> TRUSTED
                                </div>
                            <?php endif; ?>
                                    <i class="fas fa-check-circle"></i> <?php echo ($ad["verification_tier"] == "business_verified" ? strtoupper(h($settings['site_name'] ?? 'Classifieds')) . " BUSINESS" : "NIN VERIFIED"); ?>
                                </div>
                            <?php else: ?>
                                <div class="inline-flex items-center gap-2 bg-gray-50 text-gray-400 px-3 py-1.5 rounded-xl text-[10px] font-black border border-gray-100 uppercase tracking-widest">
                                    PHONE VERIFIED
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div class="space-y-4">
                    <div class="space-y-3">
                        <button onclick="showPhoneModal()" class="w-full bg-white border-2 border-primary-600 text-primary-600 py-5 rounded-2xl font-black text-xs uppercase tracking-widest hover:bg-primary-50 transition flex items-center justify-center gap-3 active:scale-95 shadow-sm">
                            <i class="fas fa-phone-alt"></i>
                            <span id="blurredPhone"><?php echo substr($ad["seller_phone"], 0, 7); ?>XXXX</span>
                        </button>
                        <p class="text-[9px] text-gray-400 font-black text-center uppercase tracking-widest">Click to show full number</p>
                    </div>

                    <?php if (is_user_logged_in() && $_SESSION['user_id'] != $ad['user_id']): ?>
                        <a href="chat.php?ad_id=<?php echo $ad['id']; ?>" class="w-full bg-primary-600 text-white py-5 rounded-2xl font-black text-xs uppercase tracking-widest hover:bg-primary-700 transition shadow-xl shadow-primary-100 flex items-center justify-center gap-3 active:scale-95">
                            <i class="fas fa-comment-dots"></i> START CHAT
                        </a>
                        <?php if ($ad['listing_type'] != 'for_sale'): ?>
                            <a href="swap_propose.php?ad_id=<?php echo $ad['id']; ?>" class="w-full bg-blue-600 text-white py-5 rounded-2xl font-black text-xs uppercase tracking-widest hover:bg-blue-700 transition shadow-xl shadow-blue-100 flex items-center justify-center gap-3 active:scale-95">
                                <i class="fas fa-exchange-alt"></i> PROPOSE A SWAP
                            </a>
                        <?php endif; ?>
                    <?php elseif (!is_user_logged_in()): ?>
                        <a href="/login" class="w-full bg-gray-800 text-white py-5 rounded-2xl font-black text-xs uppercase tracking-widest hover:bg-black transition shadow-xl flex items-center justify-center gap-3">
                             LOGIN TO CHAT
                        </a>
                    <?php endif; ?>
                </div>

                <div class="mt-10 border-t border-gray-50 pt-8">
                    <p class="text-[10px] font-black text-gray-400 uppercase mb-6 tracking-[3px] text-center">Share this ad</p>
                    <div class="flex justify-center gap-4">
                        <a href="https://api.whatsapp.com/send?text=<?php echo urlencode($ad["title"] . " - ₦" . number_format($ad["price"]) . ". View on " . ($settings['site_name'] ?? 'Classifieds') . ": ") . (isset($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] === "on" ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]"; ?>" target="_blank" class="w-12 h-12 rounded-2xl bg-primary-50 text-primary-600 flex items-center justify-center hover:bg-primary-600 hover:text-white transition-all shadow-sm">
                            <i class="fab fa-whatsapp text-xl"></i>
                        </a>
                        <a href="https://www.facebook.com/sharer/sharer.php?u=<?php echo urlencode("http://" . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']); ?>" target="_blank" class="w-12 h-12 rounded-2xl bg-blue-50 text-blue-600 flex items-center justify-center hover:bg-blue-600 hover:text-white transition-all shadow-sm"><i class="fab fa-facebook-f text-lg"></i></a>
                    </div>
                </div>

                <div class="mt-10 p-6 bg-yellow-50 rounded-3xl border border-yellow-100">
                    <h5 class="text-[10px] font-black text-yellow-800 uppercase mb-4 tracking-widest flex items-center gap-2"><i class="fas fa-lightbulb"></i> Safety Tips</h5>
                    <ul class="text-[11px] text-yellow-700 font-bold space-y-3">
                        <li class="flex gap-2"><span>•</span> <span>Never pay in advance, even for delivery.</span></li>
                        <li class="flex gap-2"><span>•</span> <span>Meet in a safe, public location.</span></li>
                        <li class="flex gap-2"><span>•</span> <span>Check the item before you buy it.</span></li>
                    </ul>
                </div>
            </div>

            <!-- Seller Reviews Section -->
            <div class="bg-white rounded-[2.5rem] p-8 shadow-sm border border-gray-100 mt-8">
                <div class="flex items-center justify-between mb-8">
                    <h3 class="text-xs font-black text-gray-800 uppercase tracking-[3px] flex items-center gap-3">
                        <i class="fas fa-star text-yellow-400"></i> Ratings & Reviews
                    </h3>
                    <?php
                    $stmt_rev = $pdo->prepare("SELECT COUNT(*) as count, AVG(stars) as avg FROM reviews WHERE seller_id = ?");
                    $stmt_rev->execute([$ad['user_id']]);
                    $rev_stats = $stmt_rev->fetch();
                    ?>
                    <span class="text-xs font-bold text-gray-500"><?php echo number_format($rev_stats['avg'], 1); ?>/5 (<?php echo $rev_stats['count']; ?> reviews)</span>
                </div>

                <div class="space-y-6 mb-10">
                    <?php
                    $stmt_reviews = $pdo->prepare("SELECT r.*, u.full_name FROM reviews r JOIN users u ON r.reviewer_id = u.id WHERE r.seller_id = ? ORDER BY r.submitted_at DESC LIMIT 3");
                    $stmt_reviews->execute([$ad['user_id']]);
                    $reviews = $stmt_reviews->fetchAll();
                    foreach ($reviews as $rev):
                    ?>
                        <div class="border-b border-gray-50 pb-6 last:border-0">
                            <div class="flex justify-between items-start mb-2">
                                <h5 class="text-xs font-black text-gray-800 uppercase"><?php echo h($rev['full_name']); ?></h5>
                                <div class="flex text-yellow-400 text-[10px]">
                                    <?php for($i=1; $i<=5; $i++) echo $i <= $rev['stars'] ? '<i class="fas fa-star"></i>' : '<i class="far fa-star"></i>'; ?>
                                </div>
                            </div>
                            <p class="text-xs text-gray-600 font-medium mb-3"><?php echo h($rev['body']); ?></p>
                            <?php if ($rev['reply_text']): ?>
                                <div class="bg-gray-50 p-4 rounded-2xl ml-4 border-l-4 border-primary-500">
                                    <p class="text-[10px] font-black text-primary-600 uppercase mb-1">Seller Reply</p>
                                    <p class="text-[11px] text-gray-600 italic font-medium"><?php echo h($rev['reply_text']); ?></p>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                    <?php if (empty($reviews)): ?>
                        <p class="text-xs text-gray-400 font-bold text-center py-4">No reviews yet for this seller.</p>
                    <?php endif; ?>
                </div>

                <?php if (is_user_logged_in() && $_SESSION['user_id'] != $ad['user_id']): ?>
                    <form id="reviewForm" class="space-y-4 pt-8 border-t border-gray-50">
                        <div class="bg-red-50 p-4 rounded-2xl border border-red-100 mb-6">
                            <p class="text-[10px] font-black text-red-600 uppercase tracking-widest leading-relaxed">
                                <i class="fas fa-exclamation-circle mr-1"></i> Warning: Before you leave a bad review, make sure you have a proof to back it up or risk being banned from the platform completely.
                            </p>
                        </div>
                        <input type="hidden" name="seller_id" value="<?php echo $ad['user_id']; ?>">
                        <div>
                            <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2">Rating</label>
                            <div class="flex gap-2 text-2xl text-gray-200 cursor-pointer" id="starRating">
                                <i class="fas fa-star" data-value="1"></i>
                                <i class="fas fa-star" data-value="2"></i>
                                <i class="fas fa-star" data-value="3"></i>
                                <i class="fas fa-star" data-value="4"></i>
                                <i class="fas fa-star" data-value="5"></i>
                            </div>
                            <input type="hidden" name="stars" id="starsInput" value="0">
                        </div>
                        <div>
                            <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2">Your Comment</label>
                            <textarea name="body" class="w-full p-4 bg-gray-50 border-none rounded-2xl text-xs font-bold text-gray-700 focus:ring-2 focus:ring-primary-500 transition" rows="3" placeholder="Share your experience with this seller..."></textarea>
                        </div>
                        <button type="submit" class="w-full bg-gray-800 text-white py-4 rounded-xl font-black text-xs uppercase tracking-widest hover:bg-black transition shadow-lg">Submit Review</button>
                    </form>
                    <script>
                        document.querySelectorAll('#starRating i').forEach(star => {
                            star.addEventListener('click', function() {
                                const val = this.dataset.value;
                                document.getElementById('starsInput').value = val;
                                document.querySelectorAll('#starRating i').forEach(s => {
                                    s.classList.toggle('text-yellow-400', s.dataset.value <= val);
                                    s.classList.toggle('text-gray-200', s.dataset.value > val);
                                });
                            });
                        });
                        document.getElementById('reviewForm').addEventListener('submit', function(e) {
                            e.preventDefault();
                            fetch('/api/submit_review.php', {
                                method: 'POST',
                                body: new FormData(this)
                            }).then(res => res.json()).then(data => {
                                alert(data.message);
                                if(data.success) location.reload();
                            });
                        });
                    </script>
                <?php endif; ?>
            </div>

            <?php if ($ad["listing_type"] !== "for_sale"): ?>
            <div class="bg-white rounded-[2.5rem] p-8 shadow-sm border border-gray-100 mt-8">
                <h3 class="text-xs font-black text-gray-800 uppercase tracking-[3px] mb-8 flex items-center gap-3">
                    <i class="fas fa-sync-alt text-blue-600"></i> Smart Matches
                </h3>
                <div id="swapMatches" class="space-y-6">
                    <div class="flex items-center justify-center py-10"><i class="fas fa-spinner fa-spin text-blue-500"></i></div>
                </div>
            </div>
            <script>
            fetch("/api/swaps_matches.php?id=<?php echo $ad["id"]; ?>")
                .then(res => res.json())
                .then(matches => {
                    const container = document.getElementById("swapMatches");
                    if (matches.length === 0) {
                        container.innerHTML = "<p class='text-[10px] font-bold text-gray-400 uppercase tracking-widest text-center'>No exact matches yet.</p>";
                        return;
                    }
                    container.innerHTML = matches.map(m => `
                        <a href="/ad.php?id=${m.id}" class="flex items-center gap-4 p-2 rounded-2xl hover:bg-blue-50 transition group">
                            <img src="/uploads/ads/${m.image || "placeholder.jpg"}" class="w-16 h-16 object-cover rounded-xl shadow-sm">
                            <div class="flex-1">
                                <h4 class="text-xs font-black text-gray-800 line-clamp-1 group-hover:text-blue-600">${m.title}</h4>
                                <p class="text-[10px] font-bold text-gray-400 mt-1">₦${new Intl.NumberFormat().format(m.price)}</p>
                            </div>
                            <i class="fas fa-chevron-right text-gray-200 group-hover:text-blue-600 text-[10px]"></i>
                        </a>
                    `).join("");
                });
            </script>
            <?php endif; ?>
        </div>
    </div>

    <!-- Similar Ads Section -->
    <?php if ($similar_ads): ?>
    <div class="mt-24 border-t border-gray-50 pt-16">
        <div class="flex items-center gap-4 mb-12">
            <div class="w-2 h-10 bg-primary-600 rounded-full"></div>
            <h2 class="text-3xl font-black text-gray-800 uppercase tracking-tighter italic">Recommended Deals</h2>
        </div>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-8">
            <?php foreach ($similar_ads as $s_ad): ?>
            <a href="<?php echo generate_ad_url($s_ad); ?>" class="bg-white rounded-[2rem] shadow-sm overflow-hidden hover:shadow-2xl transition-all duration-500 group border border-gray-50">
                <div class="relative h-48 overflow-hidden">
                    <img src="<?php echo $s_ad['image'] ? '/uploads/ads/'.$s_ad['image'] : 'https://placehold.co/400x300?text=No+Image'; ?>" class="w-full h-full object-cover group-hover:scale-110 transition duration-700">
                    <?php if ($s_ad['is_featured']): ?>
                        <span class="absolute top-4 left-4 bg-yellow-400 text-yellow-900 text-[8px] font-black px-3 py-1 rounded-full uppercase shadow-xl">Premium</span>
                    <?php endif; ?>
                </div>
                <div class="p-6">
                    <h4 class="text-xs font-black text-gray-800 line-clamp-2 h-8 mb-4 group-hover:text-primary-600 transition uppercase tracking-tight"><?php echo h($s_ad['title']); ?></h4>
                    <p class="text-primary-600 font-black text-xl">₦<?php echo number_format($s_ad['price']); ?></p>
                </div>
            </a>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>
</div>

<!-- Lightbox Modal -->
<div id="lightboxModal" class="fixed inset-0 bg-black/95 z-[200] hidden flex-col items-center justify-center backdrop-blur-xl p-4">
    <button onclick="closeLightbox()" class="absolute top-6 right-6 text-white text-2xl w-12 h-12 flex items-center justify-center hover:bg-white/10 rounded-full transition z-[210]">
        <i class="fas fa-times"></i>
    </button>

    <div id="lightboxContainer" class="relative w-full max-w-6xl h-[85vh] flex items-center justify-center group">
        <img id="lightboxImage" src="" class="max-w-full max-h-full object-contain transition-all duration-300 shadow-2xl">

        <button onclick="prevImage(event)" class="absolute left-4 top-1/2 -translate-y-1/2 w-16 h-16 text-white/50 text-4xl flex items-center justify-center hover:text-white hover:bg-white/10 rounded-full transition z-30">
            <i class="fas fa-chevron-left"></i>
        </button>
        <button onclick="nextImage(event)" class="absolute right-4 top-1/2 -translate-y-1/2 w-16 h-16 text-white/50 text-4xl flex items-center justify-center hover:text-white hover:bg-white/10 rounded-full transition z-30">
            <i class="fas fa-chevron-right"></i>
        </button>
    </div>

    <div id="lightboxThumbnails" class="mt-8 flex gap-3 overflow-x-auto p-4 max-w-full scrollbar-hide">
        <?php foreach ($images as $index => $img): ?>
            <img src="/uploads/ads/<?php echo $img['image_path']; ?>" class="lightbox-thumb w-20 h-20 rounded-2xl object-cover cursor-pointer border-4 border-transparent hover:border-green-500 transition-all shadow-xl opacity-60 hover:opacity-100" onclick="updateMainImage(<?php echo $index; ?>)">
        <?php endforeach; ?>
    </div>
</div>

<!-- Phone Safety Modal (Feature 04) -->
<div id="phoneModal" class="fixed inset-0 bg-black/60 z-[100] hidden items-center justify-center backdrop-blur-sm p-4">
    <div class="bg-white w-full max-w-md rounded-[2.5rem] p-8 animate-slide-up shadow-2xl border border-gray-100">
        <div class="w-20 h-20 bg-primary-50 rounded-full flex items-center justify-center mx-auto mb-6">
            <i class="fas fa-shield-alt text-3xl text-primary-600"></i>
        </div>
        <h3 class="text-xl font-black text-gray-800 uppercase tracking-tighter text-center mb-2">Deal Safely on <?php echo h($settings['site_name'] ?? 'Classifieds'); ?></h3>
        <p class="text-sm text-gray-500 font-bold text-center mb-8">Buyers who chat on <?php echo h($settings['site_name'] ?? 'Classifieds'); ?> before paying have full dispute support. Use our message feature to keep a record of your deal.</p>

        <div class="space-y-4">
            <a href="/chat.php?ad_id=<?php echo $ad["id"]; ?>" class="block w-full bg-primary-600 text-white py-5 rounded-2xl font-black text-xs uppercase tracking-widest text-center hover:bg-primary-700 transition shadow-xl">Message on <?php echo h($settings['site_name'] ?? 'Classifieds'); ?></a>
            <button onclick="revealNumber()" class="block w-full bg-gray-50 text-gray-400 py-4 rounded-2xl font-black text-[10px] uppercase tracking-widest text-center hover:bg-gray-100 transition">Show Number Anyway</button>
            <button onclick="closePhoneModal()" class="block w-full text-gray-300 font-black text-[9px] uppercase tracking-widest mt-4">Maybe Later</button>
        </div>
    </div>
</div>

<script>
const adImages = <?php echo json_encode(array_map(function($img) { return '/uploads/ads/' . $img['image_path']; }, $images)); ?>;
let currentImageIndex = 0;

function updateMainImage(index) {
    if (index < 0 || index >= adImages.length) return;
    currentImageIndex = index;
    const src = adImages[index];

    // Update main display
    const mainImg = document.getElementById('mainImage');
    mainImg.style.opacity = '0.5';
    document.getElementById('mainImageContainer').style.setProperty('--bg-image', `url('${src}')`);
    setTimeout(() => {
        mainImg.src = src;
        mainImg.style.opacity = '1';
    }, 50);

    // Update thumbnails active state
    document.querySelectorAll('.thumbnail-item').forEach((el, i) => {
        el.classList.toggle('border-green-500', i === index);
        el.classList.toggle('border-transparent', i !== index);
    });

    // Update lightbox if open
    if (!document.getElementById('lightboxModal').classList.contains('hidden')) {
        const lbImg = document.getElementById('lightboxImage');
        lbImg.style.opacity = '0.5';
        setTimeout(() => {
            lbImg.src = src;
            lbImg.style.opacity = '1';
        }, 50);

        document.querySelectorAll('.lightbox-thumb').forEach((el, i) => {
            el.classList.toggle('border-green-500', i === index);
            el.classList.toggle('opacity-100', i === index);
            el.classList.toggle('opacity-60', i !== index);
        });
    }
}

function nextImage(e) {
    if(e) e.stopPropagation();
    let next = currentImageIndex + 1;
    if (next >= adImages.length) next = 0;
    updateMainImage(next);
}

function prevImage(e) {
    if(e) e.stopPropagation();
    let prev = currentImageIndex - 1;
    if (prev < 0) prev = adImages.length - 1;
    updateMainImage(prev);
}

function openLightbox() {
    const modal = document.getElementById('lightboxModal');
    modal.classList.remove('hidden');
    modal.classList.add('flex');
    updateMainImage(currentImageIndex);
    document.body.style.overflow = 'hidden';
}

function closeLightbox() {
    const modal = document.getElementById('lightboxModal');
    modal.classList.add('hidden');
    modal.classList.remove('flex');
    document.body.style.overflow = 'auto';
}

// Keyboard navigation
document.addEventListener('keydown', (e) => {
    if (document.getElementById('lightboxModal').classList.contains('hidden')) return;
    if (e.key === 'ArrowRight') nextImage();
    if (e.key === 'ArrowLeft') prevImage();
    if (e.key === 'Escape') closeLightbox();
});

function showPhoneModal() {
    document.getElementById("phoneModal").classList.remove("hidden");
    document.getElementById("phoneModal").classList.add("flex");
}
function closePhoneModal() {
    document.getElementById("phoneModal").classList.add("hidden");
    document.getElementById("phoneModal").classList.remove("flex");
}
function revealNumber() {
    const fullPhone = "<?php echo h($ad["seller_phone"]); ?>";
    const telLink = "tel:" + fullPhone.replace(/^0/, "234");
    document.getElementById("blurredPhone").textContent = fullPhone;
    window.location.href = telLink;
    closePhoneModal();
}
</script>

<?php include __DIR__ . '/templates/footer.php'; ?>
