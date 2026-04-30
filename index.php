<?php
/**
 * Main Entry Point for <?php echo h($settings['site_name'] ?? 'Classifieds'); ?>-Inspired-1.0
 */

// Define project root
define('ROOT_PATH', __DIR__);

// Check if installed
if (!file_exists(ROOT_PATH . '/config/config.php')) {
    header('Location: install/index.php');
    exit;
}

// Load configuration
require_once ROOT_PATH . '/config/config.php';
require_once ROOT_PATH . '/inc/functions.php';
require_once ROOT_PATH . '/inc/security.php';
require_once ROOT_PATH . '/inc/user_auth.php';

// Fetch categories for sidebar (Total ads including subcategories)
$stmt = $pdo->query("SELECT c.*,
    (SELECT COUNT(*) FROM ads a
     JOIN users u ON a.user_id = u.id
     WHERE (a.cat_id = c.id OR a.cat_id IN (SELECT id FROM categories WHERE parent_id = c.id))
     AND a.status = 'active' AND u.is_suspended = 0) as ad_count
    FROM categories c WHERE parent_id = 0 ORDER BY sort_order ASC, name ASC");
$categories = $stmt->fetchAll();

// Fetch Top Grid categories for mobile
$stmt = $pdo->query("SELECT * FROM categories WHERE is_top = 1 ORDER BY sort_order ASC LIMIT 4");
$top_grid_categories = $stmt->fetchAll();

// Fetch featured ads
$stmt = $pdo->query("SELECT a.*, (SELECT image_path FROM ad_images WHERE ad_id = a.id AND is_main = 1 LIMIT 1) as image, s.name as state_name, c.name as cat_name
                     FROM ads a
                     JOIN states s ON a.state_id = s.id
                     JOIN categories c ON a.cat_id = c.id
                     JOIN users u ON a.user_id = u.id
                     WHERE a.status = 'active' AND a.is_featured = 1 AND u.is_suspended = 0
                     ORDER BY a.created_at DESC LIMIT 8");
$featured_ads = $stmt->fetchAll();

// Fetch regular ads
$stmt = $pdo->query("SELECT a.*, (SELECT image_path FROM ad_images WHERE ad_id = a.id AND is_main = 1 LIMIT 1) as image, s.name as state_name, c.name as cat_name
                     FROM ads a
                     JOIN states s ON a.state_id = s.id
                     JOIN categories c ON a.cat_id = c.id
                     JOIN users u ON a.user_id = u.id
                     WHERE a.status = 'active' AND a.is_featured = 0 AND u.is_suspended = 0
                     ORDER BY a.created_at DESC LIMIT 20");
$recent_ads = $stmt->fetchAll();

include __DIR__ . '/templates/header.php';
?>

<div class="container mx-auto px-4 py-8">
    <!-- <?php echo h($settings['site_name'] ?? 'Classifieds'); ?>-Style Search Bar (Mobile Only) -->
    <div class="mb-8 md:hidden">
        <form action="/search.php" method="GET" class="flex flex-col md:flex-row items-stretch bg-white rounded-2xl md:rounded-full overflow-hidden shadow-xl border-2 border-primary-50 focus-within:border-primary-500 transition-all">
            <div class="flex flex-1 items-center px-6 border-b md:border-b-0 md:border-r border-gray-100 gap-3">
                <i class="fas fa-search text-gray-400"></i>
                <input type="text" name="q" placeholder="What are you looking for?" class="w-full py-4 md:py-5 text-sm font-bold text-gray-700 outline-none" required>
            </div>

            <div class="flex flex-1 items-center px-6 border-b md:border-b-0 md:border-r border-gray-100 gap-3">
                <i class="fas fa-list text-primary-600"></i>
                <select name="cat_id" class="w-full py-4 md:py-5 bg-transparent text-sm font-bold text-gray-700 outline-none cursor-pointer appearance-none">
                    <option value="">All Categories</option>
                    <?php foreach ($categories as $scat): ?>
                        <option value="<?php echo $scat['id']; ?>"><?php echo h($scat['name']); ?></option>
                    <?php endforeach; ?>
                </select>
                <i class="fas fa-chevron-down text-[10px] text-gray-300"></i>
            </div>

            <div class="flex flex-1 items-center px-6 gap-3">
                <i class="fas fa-map-marker-alt text-red-500"></i>
                <select name="state_id" class="w-full py-4 md:py-5 bg-transparent text-sm font-bold text-gray-700 outline-none cursor-pointer appearance-none">
                    <option value="">All Nigeria</option>
                    <?php
                    $stmt_states = $pdo->query("SELECT id, name FROM states ORDER BY name ASC");
                    while($s = $stmt_states->fetch()) echo "<option value='{$s['id']}'>".h($s['name'])."</option>";
                    ?>
                </select>
                <i class="fas fa-chevron-down text-[10px] text-gray-300"></i>
            </div>

            <button type="submit" class="bg-primary-600 text-white px-10 py-4 md:py-2 font-black text-xs uppercase tracking-widest hover:bg-primary-700 transition">
                Search
            </button>
        </form>
    </div>

    <!-- Categories Top Grid (Admin Managed Mobile) -->
    <?php if ($top_grid_categories): ?>
    <div class="md:hidden grid grid-cols-4 gap-2 mb-8 px-2">
        <?php foreach ($top_grid_categories as $tcat): ?>
        <a href="/category/<?php echo $tcat['slug']; ?>" class="flex flex-col items-center bg-white p-3 rounded-2xl shadow-sm border border-gray-50">
            <div class="w-10 h-10 bg-primary-50 rounded-xl flex items-center justify-center text-primary-600 mb-2">
                <i class="fas <?php echo h($tcat['icon_class']); ?> text-lg"></i>
            </div>
            <span class="text-[7px] font-extrabold text-gray-800 uppercase text-center line-clamp-1"><?php echo h($tcat['name']); ?></span>
        </a>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>


    <!-- Mobile Subcategories Modal -->
    <div id="mobileSubsModal" class="fixed inset-0 bg-black/60 z-[100] hidden items-end justify-center backdrop-blur-sm" onclick="closeMobileSubs()">
        <div class="bg-white w-full rounded-t-[2.5rem] p-8 max-h-[80vh] overflow-y-auto animate-slide-up" onclick="event.stopPropagation()">
            <div class="w-12 h-1.5 bg-gray-200 rounded-full mx-auto mb-6"></div>
            <div class="flex justify-between items-center mb-8">
                <h3 id="mobileSubsTitle" class="text-xl font-black text-gray-800 uppercase tracking-tighter">Category</h3>
                <button onclick="closeMobileSubs()" class="w-10 h-10 bg-gray-50 rounded-full flex items-center justify-center text-gray-400"><i class="fas fa-times"></i></button>
            </div>
            <div id="mobileSubsContent" class="grid grid-cols-1 gap-4">
                <!-- Content via JS -->
            </div>
        </div>
    </div>

    <div class="flex flex-col md:flex-row gap-8">
        <!-- Sidebar Categories (Desktop Only - <?php echo h($settings['site_name'] ?? 'Classifieds'); ?> Perfect Style) -->
        <aside class="hidden md:block w-72 bg-white shadow-sm overflow-visible h-fit sticky top-24 border-r border-gray-100 z-50">
            <div class="py-2">
                <?php foreach ($categories as $cat): ?>
                <div class="group relative px-2">
                    <a href="/category/<?php echo $cat['slug']; ?>" class="flex items-center justify-between p-3 rounded-lg hover:bg-gray-50 transition-all duration-150">
                        <div class="flex items-center gap-4">
                            <div class="w-10 h-10 flex items-center justify-center">
                                <i class="fas <?php echo h($cat['icon_class']); ?> text-xl text-gray-400 group-hover:text-primary-600 transition"></i>
                            </div>
                            <div class="flex flex-col">
                                <span class="text-[14px] font-bold text-gray-700 group-hover:text-primary-600 transition tracking-tight"><?php echo h($cat['name']); ?></span>
                                <span class="text-[11px] text-gray-400 font-medium"><?php echo number_format($cat['ad_count']); ?> ads</span>
                            </div>
                        </div>
                        <i class="fas fa-chevron-right text-[10px] text-gray-300 group-hover:text-primary-600 transition"></i>
                    </a>

                    <!-- Full-Width <?php echo h($settings['site_name'] ?? 'Classifieds'); ?> Submenu on Hover -->
                    <?php
                    $stmt_sub = $pdo->prepare("SELECT c.*, (SELECT COUNT(*) FROM ads a JOIN users u ON a.user_id = u.id WHERE a.cat_id = c.id AND a.status = 'active' AND u.is_suspended = 0) as sub_ad_count FROM categories c WHERE parent_id = ? ORDER BY name ASC");
                    $stmt_sub->execute([$cat['id']]);
                    $subs = $stmt_sub->fetchAll();
                    if ($subs):
                    ?>
                    <div class="hidden group-hover:block absolute left-full top-0 ml-0 w-[500px] bg-white shadow-[15px_0_30px_rgba(0,0,0,0.1)] border border-l-0 border-gray-100 max-h-[70vh] overflow-y-auto p-8 z-[60] rounded-r-2xl scrollbar-hide">
                        <h4 class="font-black text-[12px] text-gray-800 uppercase mb-6 tracking-widest border-b pb-4 sticky top-0 bg-white z-10"><?php echo h($cat['name']); ?></h4>
                        <div class="grid grid-cols-2 gap-x-8 gap-y-4">
                            <?php foreach ($subs as $sub): ?>
                            <a href="/category/<?php echo $sub['slug']; ?>" class="flex flex-col group/sub">
                                <span class="text-[13px] font-bold text-gray-600 group-hover/sub:text-primary-600 transition"><?php echo h($sub['name']); ?></span>
                                <span class="text-[10px] text-gray-400"><?php echo number_format($sub['sub_ad_count']); ?> ads</span>
                            </a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
        </aside>

        <!-- Main Content -->
        <div class="flex-1">
            <!-- Hero Banner (<?php echo h($settings['site_name'] ?? 'Classifieds'); ?>/<?php echo h($settings['site_name'] ?? 'Classifieds'); ?> Hybrid Style) -->
            <div class="hidden md:block relative bg-gradient-to-br from-primary-600 to-primary-700 rounded-[2rem] p-10 mb-10 text-white overflow-hidden shadow-2xl">
                <div class="absolute top-0 right-0 w-64 h-64 bg-white/10 rounded-full -mr-32 -mt-32 blur-3xl"></div>
                <div class="absolute bottom-0 left-0 w-48 h-48 bg-yellow-500/20 rounded-full -ml-24 -mb-24 blur-3xl"></div>

                <div class="relative z-10 flex flex-col md:flex-row items-center justify-between gap-8">
                    <div class="text-center md:text-left">
                        <span class="hidden md:inline-block bg-yellow-500 text-primary-900 text-[10px] font-black px-3 py-1 rounded-full uppercase mb-4 tracking-widest shadow-sm">Verified Marketplace</span>
                        <h2 class="text-4xl md:text-5xl font-black mb-4 leading-tight">Everything is possible <br class="hidden md:block">with <span class="text-yellow-400"><?php echo h($settings['site_name'] ?? 'Classifieds'); ?></span></h2>
                        <p class="text-primary-50 font-bold opacity-90 max-w-md">Nigeria's #1 Verified Marketplace. Buy, Sell and Swap with total peace of mind.</p>
                    </div>
                    <div class="flex flex-col gap-4 w-full md:w-auto">
                        <a href="/post-ad" class="bg-yellow-500 text-white px-10 py-5 rounded-2xl font-black hover:bg-yellow-400 transition transform hover:-translate-y-1 shadow-2xl flex items-center justify-center gap-3">
                            <i class="fas fa-plus-circle text-xl"></i>
                            POST AN AD
                        </a>
                        <p class="text-[10px] text-center font-bold text-primary-200">JOIN 5M+ VERIFIED SELLERS TODAY</p>
                    </div>
                </div>
            </div>

            <!-- Featured Ads (Enhanced Grid) -->
            <?php if ($featured_ads): ?>
            <section class="mb-16">
                <div class="flex justify-between items-center mb-8">
                    <div class="flex items-center gap-3">
                        <div class="w-2 h-8 bg-yellow-400 rounded-full"></div>
                        <h3 class="text-xl md:text-2xl font-black text-gray-800 uppercase tracking-tighter">Premium Boosted</h3>
                    </div>
                    <a href="/search.php" class="text-[10px] md:text-xs font-black text-primary-600 uppercase tracking-widest hover:text-primary-700 transition">View All Listings</a>
                </div>
                <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
                    <?php foreach ($featured_ads as $ad): ?>
                    <a href="<?php echo generate_ad_url($ad); ?>" class="bg-white rounded-lg shadow-sm overflow-hidden hover:shadow-md transition">
                        <?php $ad_img = $ad['image'] ? '/uploads/ads/'.$ad['image'] : 'https://placehold.co/400x300?text=No+Image'; ?>
                        <div class="relative h-40 fit-to-frame" style="--bg-image: url('<?php echo $ad_img; ?>')">
                            <img src="<?php echo $ad_img; ?>">
                            <span class="absolute top-2 left-2 bg-yellow-500 text-white text-[10px] font-bold px-2 py-0.5 rounded-full uppercase z-10">Featured</span>
                            <?php if ($ad['listing_type'] !== 'for_sale'): ?>
                                <span class="absolute top-2 right-2 bg-blue-600 text-white text-[8px] font-black px-2 py-0.5 rounded-full uppercase shadow-sm"><i class="fas fa-sync-alt mr-1"></i> Swap</span>
                            <?php endif; ?>
                        </div>
                        <div class="p-3">
                            <h4 class="text-sm font-bold text-gray-800 line-clamp-2 h-10 mb-2"><?php echo h($ad['title']); ?></h4>
                            <p class="text-primary-600 font-bold mb-2">₦<?php echo number_format($ad['price']); ?></p>
                            <p class="text-[10px] text-gray-400 font-bold"><i class="fas fa-map-marker-alt"></i> <?php echo h($ad['state_name']); ?></p>
                        </div>
                    </a>
                    <?php endforeach; ?>
                </div>
            </section>
            <?php endif; ?>

            <!-- Recommendations Section (Based on History) -->
            <?php
            $recommended_ads = [];
            if (is_user_logged_in()) {
                $uid = $_SESSION['user_id'];
                // Get last search category or keyword
                $history = $pdo->prepare("SELECT keyword, cat_id FROM search_history WHERE user_id = ? ORDER BY created_at DESC LIMIT 1");
                $history->execute([$uid]);
                $last = $history->fetch();

                if ($last) {
                    $rec_query = "SELECT a.*, (SELECT image_path FROM ad_images WHERE ad_id = a.id AND is_main = 1 LIMIT 1) as image, s.name as state_name, c.name as cat_name
                                 FROM ads a
                                 JOIN states s ON a.state_id = s.id
                                 JOIN categories c ON a.cat_id = c.id
                                 JOIN users u ON a.user_id = u.id
                                 WHERE a.status = 'active' AND u.is_suspended = 0 AND a.id NOT IN (SELECT id FROM ads WHERE user_id = ?)";
                    $rec_params = [$uid];

                    if ($last['cat_id']) {
                        $rec_query .= " AND a.cat_id = ?";
                        $rec_params[] = $last['cat_id'];
                    } elseif ($last['keyword']) {
                        $rec_query .= " AND a.title LIKE ?";
                        $rec_params[] = "%".$last['keyword']."%";
                    }

                    $rec_query .= " ORDER BY RAND() LIMIT 4";
                    $rec_stmt = $pdo->prepare($rec_query);
                    $rec_stmt->execute($rec_params);
                    $recommended_ads = $rec_stmt->fetchAll();
                }
            }
            ?>

            <?php if ($recommended_ads): ?>
            <section class="mb-16">
                <div class="flex items-center gap-3 mb-8">
                    <div class="w-2 h-8 bg-blue-500 rounded-full"></div>
                    <h3 class="text-xl md:text-2xl font-black text-gray-800 uppercase tracking-tighter">Recommended For You</h3>
                </div>
                <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
                    <?php foreach ($recommended_ads as $ad): ?>
                    <a href="<?php echo generate_ad_url($ad); ?>" class="bg-white rounded-2xl shadow-sm overflow-hidden hover:shadow-md transition border border-blue-50">
                        <?php $ad_img = $ad['image'] ? '/uploads/ads/'.$ad['image'] : 'https://placehold.co/400x300?text=No+Image'; ?>
                        <div class="relative h-40 fit-to-frame" style="--bg-image: url('<?php echo $ad_img; ?>')">
                            <img src="<?php echo $ad_img; ?>">
                            <?php if ($ad['listing_type'] !== 'for_sale'): ?>
                                <span class="absolute top-2 right-2 bg-blue-600 text-white text-[8px] font-black px-2 py-0.5 rounded-full uppercase shadow-sm"><i class="fas fa-sync-alt mr-1"></i> Swap</span>
                            <?php endif; ?>
                        </div>
                        <div class="p-3">
                            <h4 class="text-sm font-bold text-gray-800 line-clamp-2 h-10 mb-2"><?php echo h($ad['title']); ?></h4>
                            <p class="text-primary-600 font-bold mb-2">₦<?php echo number_format($ad['price']); ?></p>
                            <p class="text-[10px] text-gray-400 font-bold"><i class="fas fa-map-marker-alt"></i> <?php echo h($ad['state_name']); ?></p>
                        </div>
                    </a>
                    <?php endforeach; ?>
                </div>
            </section>
            <?php endif; ?>

            <!-- Recent Ads (<?php echo h($settings['site_name'] ?? 'Classifieds'); ?>/<?php echo h($settings['site_name'] ?? 'Classifieds'); ?> Hybrid Feed) -->
            <section class="mt-20">
                <div class="flex items-center gap-4 mb-10">
                    <div class="w-3 h-8 md:h-10 bg-primary-600 rounded-full shadow-[0_0_15px_rgba(22,163,74,0.5)]"></div>
                    <h3 class="text-xl md:text-3xl font-black text-gray-800 uppercase tracking-tighter italic">The Trending Feed</h3>
                </div>

                <div class="flex flex-col lg:flex-row gap-10">
                    <!-- Left Sidebar Category Filter (Modern List View) -->
                    <aside class="w-full lg:w-64 flex-shrink-0">
                        <!-- Swap Highlight (<?php echo h($settings['site_name'] ?? 'Classifieds'); ?> Differentiator) -->
                        <div class="bg-blue-600 rounded-3xl p-6 shadow-xl mb-6 text-white relative overflow-hidden group cursor-pointer" onclick="filterTrending(0, 'swap')">
                            <div class="absolute -right-4 -top-4 w-20 h-20 bg-white/10 rounded-full group-hover:scale-150 transition-transform duration-700"></div>
                            <h4 class="text-[10px] font-black uppercase tracking-widest mb-2 opacity-80">Swap/Barter</h4>
                            <p class="text-lg font-black leading-tight mb-4">Exchange Items <br>No Cash Needed</p>
                            <div class="flex items-center gap-2 text-[10px] font-bold bg-white/20 w-fit px-3 py-1 rounded-full">
                                <span>Browse Swaps</span>
                                <i class="fas fa-sync-alt animate-spin-slow"></i>
                            </div>
                        </div>

                        <div class="bg-white rounded-3xl p-6 shadow-sm border border-gray-100 sticky top-24">
                            <div class="flex items-center justify-between mb-6 px-2">
                                <h4 class="text-[10px] font-black text-gray-400 uppercase tracking-[3px]">Filter by Category</h4>
                                <button id="backToMainCats" onclick="resetTrendingFilter()" class="hidden text-[9px] font-black text-primary-600 uppercase tracking-widest"><i class="fas fa-arrow-left mr-1"></i> Back</button>
                            </div>
                            <div id="trendingFilterGrid" class="grid grid-cols-2 lg:grid-cols-1 gap-2">
                                <button onclick="filterTrending(0)" class="trending-filter-btn w-full text-left px-4 py-3 rounded-2xl text-[10px] font-black uppercase tracking-wider transition-all duration-300 flex items-center justify-between group bg-primary-600 text-white shadow-xl" data-cat="0">
                                    <span class="truncate pr-1">All Items</span>
                                    <i class="fas fa-th-large opacity-50 group-hover:rotate-12 transition-transform"></i>
                                </button>
                                <?php foreach ($categories as $fcat): ?>
                                    <button onclick="handleCategoryClick(<?php echo $fcat['id']; ?>, '<?php echo addslashes($fcat['name']); ?>', '<?php echo $fcat['icon_class']; ?>')" class="trending-filter-btn w-full text-left px-4 py-3 rounded-2xl text-[10px] font-black uppercase tracking-wider text-gray-500 hover:bg-primary-50 hover:text-primary-600 transition-all duration-300 flex items-center justify-between group" data-cat="<?php echo $fcat['id']; ?>">
                                        <span class="truncate pr-1"><?php echo h($fcat['name']); ?></span>
                                        <i class="fas <?php echo h($fcat['icon_class']); ?> opacity-20 group-hover:opacity-100 transition-opacity"></i>
                                    </button>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </aside>

                    <!-- Feed Grid -->
                    <div class="flex-1">
                        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-3 gap-8" id="trendingContainer">
                    <?php foreach ($recent_ads as $ad): ?>
                    <a href="<?php echo generate_ad_url($ad); ?>" class="bg-white rounded-[2.5rem] shadow-sm overflow-hidden hover:shadow-2xl transition-all duration-500 border border-gray-100 group">
                        <?php $ad_img = $ad['image'] ? '/uploads/ads/'.$ad['image'] : 'https://placehold.co/400x300?text=No+Image'; ?>
                        <div class="h-64 overflow-hidden relative fit-to-frame" style="--bg-image: url('<?php echo $ad_img; ?>')">
                            <img src="<?php echo $ad_img; ?>" class="group-hover:scale-110 transition duration-700">
                            <?php if ($ad['listing_type'] !== 'for_sale'): ?>
                                <div class="absolute top-4 right-4 bg-blue-600 text-white text-[8px] font-black px-3 py-1 rounded-full uppercase shadow-xl border border-blue-500"><i class="fas fa-sync-alt mr-1"></i> Swap</div>
                            <?php endif; ?>
                        </div>
                        <div class="p-6">
                            <h4 class="text-sm font-black text-gray-800 line-clamp-2 h-10 mb-4 group-hover:text-primary-600 transition"><?php echo h($ad['title']); ?></h4>
                            <div class="flex justify-between items-end">
                                <div>
                                    <p class="text-primary-600 font-black text-xl">₦<?php echo number_format($ad['price']); ?></p>
                                    <p class="text-[10px] text-gray-400 font-bold uppercase tracking-widest mt-1"><i class="fas fa-map-marker-alt text-primary-500 mr-1"></i> <?php echo h($ad['state_name']); ?></p>
                                </div>
                            <?php
                            $is_saved = false;
                            if (is_user_logged_in()) {
                                $s_stmt = $pdo->prepare("SELECT 1 FROM saved_ads WHERE user_id = ? AND ad_id = ?");
                                $s_stmt->execute([$_SESSION['user_id'], $ad['id']]);
                                $is_saved = $s_stmt->fetch();
                            }
                            ?>
                            <button onclick="event.preventDefault(); toggleSave(<?php echo $ad['id']; ?>, this)" class="w-10 h-10 rounded-2xl bg-gray-50 flex items-center justify-center <?php echo $is_saved ? 'text-red-500 bg-red-50' : 'text-gray-400'; ?> group-hover:bg-red-50 group-hover:text-red-500 transition-colors duration-300">
                                <i class="<?php echo $is_saved ? 'fas' : 'far'; ?> fa-heart text-sm save-icon-<?php echo $ad['id']; ?>"></i>
                            </button>
                            </div>
                        </div>
                    </a>
                    <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </section>
        </div>
    </div>
</div>

<script>
function showMobileSubs(parentId, parentName) {
    const modal = document.getElementById('mobileSubsModal');
    const content = document.getElementById('mobileSubsContent');
    const title = document.getElementById('mobileSubsTitle');

    title.textContent = parentName;
    content.innerHTML = '<div class="col-span-full py-10 text-center"><i class="fas fa-spinner fa-spin text-2xl text-primary-500"></i></div>';
    modal.classList.remove('hidden');
    modal.classList.add('flex');
    document.body.style.overflow = 'hidden';

    fetch(`api/subcategories.php?parent_id=${parentId}`)
        .then(res => res.json())
        .then(data => {
            if(data.length === 0) {
                // If no subs, just go to the category page
                window.location.href = `/category/${parentName.toLowerCase().replace(/ /g, '-')}`;
                return;
            }
            content.innerHTML = data.map(sub => `
                <a href="/category/${sub.slug}" class="flex items-center justify-between p-4 bg-gray-50 rounded-2xl hover:bg-primary-50 transition-colors">
                    <span class="font-bold text-gray-700 text-sm md:text-base">${sub.name}</span>
                    <span class="text-[9px] md:text-[10px] font-black bg-white px-3 py-1 rounded-full text-gray-400 shadow-sm">${sub.ad_count} ads</span>
                </a>
            `).join('');
            // Add "View All" link at bottom
            const slug = parentName.toLowerCase().replace(/[^a-z0-9]+/g, '-');
            content.innerHTML += `
                <a href="/category/${slug}" class="flex items-center justify-center p-4 bg-primary-600 text-white rounded-2xl font-black uppercase tracking-widest text-xs mt-4">
                    View All ${parentName}
                </a>
            `;
        });
}

function closeMobileSubs() {
    const modal = document.getElementById('mobileSubsModal');
    modal.classList.add('hidden');
    modal.classList.remove('flex');
    document.body.style.overflow = 'auto';
}

function handleCategoryClick(catId, name, icon) {
    if (window.innerWidth < 768) {
        showMobileSubs(catId, name);
    } else {
        selectMainTrending(catId, name, icon);
    }
}

function selectMainTrending(catId, name, icon) {
    const grid = document.getElementById('trendingFilterGrid');
    const backBtn = document.getElementById('backToMainCats');

    // Fetch subs
    fetch(`api/subcategories.php?parent_id=${catId}`)
        .then(res => res.json())
        .then(subs => {
            if (subs.length === 0) {
                filterTrending(catId);
                return;
            }

            backBtn.classList.remove('hidden');
            let html = `
                <button onclick="filterTrending(${catId})" class="trending-filter-btn w-full text-left px-4 py-3 rounded-2xl text-[10px] font-black uppercase tracking-wider bg-primary-50 text-primary-600 flex items-center justify-between group border border-primary-200" data-cat="${catId}">
                    <span class="truncate pr-1">All ${name}</span>
                    <i class="fas ${icon} opacity-50"></i>
                </button>
            `;

            subs.forEach(sub => {
                html += `
                    <button onclick="filterTrending(${sub.id})" class="trending-filter-btn w-full text-left px-4 py-3 rounded-2xl text-[10px] font-black uppercase tracking-wider text-gray-500 hover:bg-primary-50 hover:text-primary-600 transition-all duration-300 flex items-center justify-between group" data-cat="${sub.id}">
                        <span class="truncate pr-1">${sub.name}</span>
                        <i class="fas fa-chevron-right opacity-10 group-hover:opacity-100 transition-opacity"></i>
                    </button>
                `;
            });

            grid.innerHTML = html;
            filterTrending(catId);
        });
}

function resetTrendingFilter() {
    location.reload(); // Simplest way to restore the complex main category grid
}

function filterTrending(catId, type = 'all') {
    // Update buttons
    document.querySelectorAll('.trending-filter-btn').forEach(btn => {
        if(btn.dataset.cat == catId && type !== 'swap') {
            btn.classList.add('bg-primary-600', 'text-white', 'shadow-xl');
            btn.classList.remove('text-gray-500', 'hover:bg-primary-50', 'bg-primary-50', 'text-primary-600');
        } else {
            btn.classList.remove('bg-primary-600', 'text-white', 'shadow-xl');
            btn.classList.add('text-gray-500', 'hover:bg-primary-50');
        }
    });

    // Fetch filtered ads
    const container = document.getElementById('trendingContainer');
    container.innerHTML = '<div class="col-span-full py-20 text-center"><i class="fas fa-spinner fa-spin text-3xl text-primary-500"></i></div>';

    fetch(`api/trending.php?cat_id=${catId}&type=${type}`)
        .then(res => res.json())
        .then(data => {
            if(data.length === 0) {
                container.innerHTML = '<div class="col-span-full py-20 text-center font-bold text-gray-400 uppercase tracking-widest text-sm">No products found for this selection</div>';
                return;
            }
            container.innerHTML = data.map(ad => `
                <a href="${ad.url}" class="bg-white rounded-2xl md:rounded-[2.5rem] shadow-sm overflow-hidden hover:shadow-2xl transition-all duration-500 border border-gray-100 group">
                    <div class="h-48 md:h-64 overflow-hidden relative fit-to-frame" style="--bg-image: url('${ad.image ? '/uploads/ads/'+ad.image : 'https://placehold.co/400x300?text=No+Image'}')">
                        <img src="${ad.image ? '/uploads/ads/'+ad.image : 'https://placehold.co/400x300?text=No+Image'}" class="group-hover:scale-110 transition duration-700">
                        ${ad.is_featured == 1 ? '<div class="absolute top-4 left-4 bg-yellow-400 text-yellow-900 text-[8px] font-black px-3 py-1 rounded-full uppercase shadow-xl border border-yellow-300 z-10">Premium</div>' : ''}
                        ${ad.listing_type !== 'for_sale' ? '<div class="absolute top-4 right-4 bg-blue-600 text-white text-[8px] font-black px-3 py-1 rounded-full uppercase shadow-xl border border-blue-500 z-10"><i class="fas fa-sync-alt mr-1"></i> Swap</div>' : ''}
                    </div>
                    <div class="p-4 md:p-6">
                        <h4 class="text-xs md:text-sm font-black text-gray-800 line-clamp-2 h-8 md:h-10 mb-2 md:mb-4 group-hover:text-primary-600 transition">${ad.title}</h4>
                        <div class="flex justify-between items-end">
                            <div>
                                <p class="text-primary-600 font-black text-base md:text-xl">₦${new Intl.NumberFormat().format(ad.price)}</p>
                                <p class="text-[8px] md:text-[10px] text-gray-400 font-bold uppercase tracking-widest mt-1"><i class="fas fa-map-marker-alt text-primary-500 mr-1"></i> ${ad.state_name}</p>
                            </div>
                            <button onclick="event.preventDefault(); toggleSave(${ad.id}, this)" class="w-10 h-10 rounded-2xl bg-gray-50 flex items-center justify-center text-gray-400 group-hover:bg-red-50 group-hover:text-red-500 transition-colors duration-300">
                                <i class="${ad.is_saved ? 'fas' : 'far'} fa-heart text-sm save-icon-${ad.id}"></i>
                            </button>
                        </div>
                    </div>
                </a>
            `).join('');
        });
}
</script>

<?php include __DIR__ . '/templates/footer.php'; ?>
