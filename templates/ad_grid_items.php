<?php if (!empty($ads)): ?>
    <?php foreach ($ads as $ad):
        $tier_info = get_tier_info($ad['ad_tier'] ?? 'free');
    ?>
    <a href="<?php echo generate_ad_url($ad); ?>" class="bg-white rounded-2xl md:rounded-3xl shadow-sm fade-in-up overflow-hidden hover:shadow-2xl transition-all duration-500 border border-gray-100 group relative <?php echo $tier_info['border'] ?? ''; ?>">
        <?php if ($tier_info['shimmer'] ?? false): ?>
            <div class="absolute inset-0 shimmer-effect z-10 pointer-events-none"></div>
        <?php endif; ?>
        <?php $ad_img = $ad['image'] ? '/uploads/ads/'.$ad['image'] : 'https://placehold.co/400x300?text=No+Image'; ?>
        <div class="relative h-40 md:h-48 overflow-hidden fit-to-frame" style="--bg-image: url('<?php echo $ad_img; ?>')">
            <img src="<?php echo $ad_img; ?>" class="group-hover:scale-110 transition duration-700">
            <?php if ($tier_info): ?>
                <div class="absolute top-4 left-4 <?php echo $tier_info['badge']; ?> text-white text-[8px] font-black px-3 py-1 rounded-full uppercase shadow-xl z-10"><?php echo $tier_info['label']; ?></div>
            <?php endif; ?>
            <?php if ($ad['listing_type'] !== 'for_sale'): ?>
                <div class="absolute top-4 right-4 bg-blue-600 text-white text-[8px] font-black px-3 py-1 rounded-full uppercase shadow-xl border border-blue-500 z-10"><i class="fas fa-sync-alt mr-1"></i> Swap</div>
            <?php endif; ?>
            <div class="absolute bottom-4 left-4">
                <span class="bg-black/50 backdrop-blur-md text-white text-[9px] font-black px-3 py-1 rounded-full uppercase"><?php echo h($ad['cat_name']); ?></span>
            </div>
        </div>
        <div class="p-4 md:p-5">
            <h4 class="text-xs md:text-sm font-black text-gray-800 line-clamp-2 h-8 md:h-10 mb-2 md:mb-4 group-hover:text-primary-600 transition"><?php echo h($ad['title']); ?></h4>
            <div class="flex justify-between items-end">
                <div>
                    <p class="text-primary-600 font-black text-base md:text-xl">₦<?php echo number_format($ad['price']); ?></p>
                    <p class="text-[8px] md:text-[10px] text-gray-400 font-bold uppercase tracking-widest mt-1"><i class="fas fa-map-marker-alt text-primary-500 mr-1"></i> <?php echo h($ad['state_name']); ?></p>
                </div>
                <div class="w-10 h-10 rounded-2xl bg-gray-50 flex items-center justify-center text-gray-400 group-hover:bg-red-50 group-hover:text-red-500 transition-colors duration-300">
                    <i class="far fa-heart text-sm"></i>
                </div>
            </div>
        </div>
    </a>
    <?php endforeach; ?>
<?php else: ?>
    <div class="col-span-full bg-white p-20 rounded-[3rem] text-center border-2 border-dashed border-gray-100">
        <div class="w-24 h-24 bg-gray-50 rounded-full flex items-center justify-center mx-auto mb-8">
            <i class="fas fa-search text-gray-200 text-4xl"></i>
        </div>
        <h2 class="text-2xl font-black text-gray-800 mb-2 tracking-tighter">No results found</h2>
        <p class="text-gray-400 font-bold">Try adjusting your filters to find what you're looking for.</p>
    </div>
<?php endif; ?>
