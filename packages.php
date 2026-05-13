<?php
require_once __DIR__ . '/config/config.php';
if (session_status() === PHP_SESSION_NONE) session_start();

require_once __DIR__ . '/inc/functions.php';

$packages = $pdo->query("SELECT * FROM packages ORDER BY price ASC")->fetchAll();

$page_title = "Pricing Packages - " . ($settings['site_name'] ?? 'Classifieds');
include __DIR__ . '/templates/header.php';
?>

<div class="container mx-auto px-4 py-16">
    <div class="text-center mb-16">
        <h1 class="text-4xl md:text-5xl font-black text-gray-800 uppercase tracking-tighter italic mb-4">Select Your <span class="text-primary-600">Growth Plan</span></h1>
        <p class="text-gray-500 font-bold max-w-2xl mx-auto">Boost your sales with our premium packages. Reach more customers and sell faster on <?php echo h($settings['site_name'] ?? 'Classifieds'); ?>.</p>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8 max-w-7xl mx-auto">
        <?php
        $colors = ['free' => 'gray', 'premium' => 'blue', 'vip' => 'yellow', 'diamond' => 'purple'];
        foreach ($packages as $p):
            $color = $colors[$p['tier']] ?? 'primary';
            $price_key = $p['tier'] . '_ad_price';
            $duration_key = $p['tier'] . '_ad_duration';
            $price = (float)$p['price'];
            $duration = (int)$p['duration_days'];
        ?>
        <div class="flex flex-col bg-white rounded-[2.5rem] shadow-xl border border-gray-100 overflow-hidden hover:shadow-2xl transition-all duration-500 group relative <?php echo ($p['tier'] == 'diamond') ? 'ring-4 ring-purple-600 ring-offset-4 scale-105 z-10' : ''; ?>">
            <?php if ($p['tier'] == 'diamond'): ?>
                <div class="absolute top-0 left-0 right-0 bg-purple-600 text-white text-[10px] font-black uppercase tracking-widest py-2 text-center">Most Popular</div>
            <?php endif; ?>

            <div class="p-8 <?php echo ($p['tier'] == 'diamond') ? 'mt-6' : ''; ?>">
                <div class="w-16 h-16 bg-<?php echo $color; ?>-50 rounded-2xl flex items-center justify-center mb-6 group-hover:scale-110 transition duration-500">
                    <i class="fas <?php echo ($p['tier'] == 'free') ? 'fa-leaf' : (($p['tier'] == 'premium') ? 'fa-rocket' : (($p['tier'] == 'vip') ? 'fa-crown' : 'fa-gem')); ?> text-<?php echo $color; ?>-600 text-2xl"></i>
                </div>
                <h3 class="text-xl font-black text-gray-800 uppercase tracking-tighter italic mb-2"><?php echo h($p['name']); ?></h3>
                <div class="flex items-baseline gap-1 mb-8">
                    <span class="text-3xl font-black text-gray-900">₦<?php echo number_format($price); ?></span>
                    <span class="text-xs font-bold text-gray-400">/ <?php echo $duration; ?> days</span>
                </div>

                <ul class="space-y-4 mb-10">
                    <li class="flex items-center gap-3 text-xs font-bold text-gray-600">
                        <i class="fas fa-check-circle text-green-500"></i>
                        <span><?php echo $p['power_up']; ?> More Clients</span>
                    </li>
                    <li class="flex items-center gap-3 text-xs font-bold text-gray-600">
                        <i class="fas fa-check-circle text-green-500"></i>
                        <span><?php echo $p['promo_ads_count']; ?> TOP+ Promo Ads</span>
                    </li>
                    <?php if ($p['auto_renew_hours']): ?>
                    <li class="flex items-center gap-3 text-xs font-bold text-gray-600">
                        <i class="fas fa-check-circle text-green-500"></i>
                        <span>Auto-renew every <?php echo $p['auto_renew_hours']; ?>h</span>
                    </li>
                    <?php endif; ?>
                    <li class="flex items-center gap-3 text-xs font-bold text-gray-600">
                        <i class="fas fa-check-circle text-green-500"></i>
                        <span>₦<?php echo number_format($p['cashback']); ?> Cashback</span>
                    </li>
                    <?php if ($p['has_social_links']): ?>
                    <li class="flex items-center gap-3 text-xs font-bold text-gray-600">
                        <i class="fas fa-check-circle text-green-500"></i>
                        <span>Website & Social Links</span>
                    </li>
                    <?php endif; ?>
                    <?php if ($p['has_personal_manager']): ?>
                    <li class="flex items-center gap-3 text-xs font-bold text-gray-600">
                        <i class="fas fa-check-circle text-green-500"></i>
                        <span>Personal Manager</span>
                    </li>
                    <?php endif; ?>
                </ul>

                <a href="/post-ad" class="block w-full text-center py-4 rounded-2xl font-black uppercase tracking-widest text-[10px] transition-all duration-300 <?php
                    echo ($p['tier'] == 'free') ? 'bg-gray-100 text-gray-500 hover:bg-gray-200' : 'bg-primary-600 text-white shadow-xl shadow-primary-100 hover:bg-primary-700 active:scale-95';
                ?>">
                    <?php echo ($p['tier'] == 'free') ? 'Get Started' : 'Go Premium'; ?>
                </a>
            </div>

            <div class="p-6 bg-gray-50/50 border-t border-gray-50">
                <p class="text-[9px] font-black text-gray-400 uppercase tracking-widest mb-4">Listing Limits</p>
                <div class="grid grid-cols-3 gap-2">
                    <div class="text-center">
                        <p class="text-xs font-black text-gray-700"><?php echo $p['listings_cars']; ?></p>
                        <p class="text-[8px] font-bold text-gray-400 uppercase">Cars</p>
                    </div>
                    <div class="text-center border-x border-gray-200">
                        <p class="text-xs font-black text-gray-700"><?php echo $p['listings_property']; ?></p>
                        <p class="text-[8px] font-bold text-gray-400 uppercase">Property</p>
                    </div>
                    <div class="text-center">
                        <p class="text-xs font-black text-gray-700"><?php echo $p['listings_others']; ?></p>
                        <p class="text-[8px] font-bold text-gray-400 uppercase">Others</p>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<?php include __DIR__ . '/templates/footer.php'; ?>

