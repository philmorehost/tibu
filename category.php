<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/inc/functions.php';
require_once __DIR__ . '/inc/user_auth.php';

$slug = $_GET['slug'] ?? '';
$stmt = $pdo->prepare("SELECT id, name FROM categories WHERE slug = ?");
$stmt->execute([$slug]);
$category = $stmt->fetch();

if (!$category) {
    redirect('index.php', 'Category not found.');
}

$cat_id = $category['id'];
$type = $_GET['type'] ?? 'all';
$state_id = (int)($_GET['state_id'] ?? 0);
$lga_id = (int)($_GET['lga_id'] ?? 0);
$min_price = (float)($_GET['min_price'] ?? 0);
$max_price = (float)($_GET['max_price'] ?? 0);
$extra = $_GET['extra'] ?? [];

// Get main categories for sidebar
$stmt = $pdo->query("SELECT c.*,
    (SELECT COUNT(*) FROM ads a
     JOIN users u ON a.user_id = u.id
     WHERE (a.cat_id = c.id OR a.cat_id IN (SELECT id FROM categories WHERE parent_id = c.id))
     AND a.status = 'active' AND u.is_suspended = 0) as ad_count
    FROM categories c WHERE parent_id = 0 ORDER BY sort_order ASC, name ASC");
$main_categories = $stmt->fetchAll();

// Get subcategories of current category
$stmt = $pdo->prepare("SELECT c.*,
    (SELECT COUNT(*) FROM ads a
     JOIN users u ON a.user_id = u.id
     WHERE (a.cat_id = c.id OR a.cat_id IN (SELECT id FROM categories WHERE parent_id = c.id))
     AND a.status = 'active' AND u.is_suspended = 0) as ad_count
    FROM categories c WHERE parent_id = ? ORDER BY name ASC");
$stmt->execute([$cat_id]);
$subcategories = $stmt->fetchAll();

// Get States for filter
$states = $pdo->query("SELECT * FROM states ORDER BY name ASC")->fetchAll();

// SEO Meta Data
$meta = generate_meta_tags($category['name'], "Browse the best deals in " . $category['name'] . " on our marketplace. Buy and sell " . $category['name'] . " items at best prices in Nigeria.", "buy, sell, nigeria, deals");
$page_title = $meta['title'] . " - " . ($settings['site_name'] ?? 'Classifieds');
$page_desc = $meta['description'];
$page_keywords = $meta['keywords'];

// Get ads in this category
$query = "SELECT a.*, (SELECT image_path FROM ad_images WHERE ad_id = a.id AND is_main = 1 LIMIT 1) as image, s.name as state_name, c.name as cat_name
         FROM ads a
         JOIN states s ON a.state_id = s.id
         JOIN categories c ON a.cat_id = c.id
         JOIN users u ON a.user_id = u.id
         WHERE (a.cat_id = ? OR a.cat_id IN (SELECT id FROM categories WHERE parent_id = ?))
         AND a.status = 'active' AND u.is_suspended = 0";

$params = [$cat_id, $cat_id];

if ($type === 'sale') {
    $query .= " AND (a.listing_type = 'for_sale' OR a.listing_type = 'for_sale_or_swap')";
} elseif ($type === 'swap') {
    $query .= " AND (a.listing_type = 'for_swap' OR a.listing_type = 'for_sale_or_swap')";
}

if ($state_id) {
    $query .= " AND a.state_id = ?";
    $params[] = $state_id;
}
if ($lga_id) {
    $query .= " AND a.lga_id = ?";
    $params[] = $lga_id;
}
if ($min_price) {
    $query .= " AND a.price >= ?";
    $params[] = $min_price;
}
if ($max_price) {
    $query .= " AND a.price <= ?";
    $params[] = $max_price;
}

if ($extra) {
    require_once __DIR__ . '/inc/filters_config.php';
    $valid_filters = get_category_filters($category['name']);
    foreach ($extra as $key => $value) {
        if (empty($value)) continue;

        if (isset($valid_filters[$key])) {
            if ($key === 'verified_seller') {
                if ($value === 'Verified sellers only') {
                    $query .= " AND (u.is_verified = 1 OR u.verification_tier IN ('nin_verified', 'business_verified'))";
                }
            } elseif ($key === 'trusted_agent') {
                if ($value === 'Yes') {
                    $query .= " AND (u.is_verified = 1 OR u.verification_tier IN ('nin_verified', 'business_verified'))";
                }
            } elseif ($key === 'discount') {
                if ($value === 'With discount') {
                    $query .= " AND JSON_UNQUOTE(JSON_EXTRACT(a.ad_data, '$.\"discount\"')) = '1'";
                }
            } else {
                if (is_array($value)) {
                    $json_placeholders = implode(',', array_fill(0, count($value), '?'));
                    $query .= " AND JSON_UNQUOTE(JSON_EXTRACT(a.ad_data, '$.\"$key\"')) IN ($json_placeholders)";
                    foreach ($value as $v) {
                        $params[] = $v;
                    }
                } else {
                    $query .= " AND JSON_UNQUOTE(JSON_EXTRACT(a.ad_data, '$.\"$key\"')) = ?";
                    $params[] = $value;
                }
            }
        } elseif (strpos($key, 'min_') === 0 || strpos($key, 'max_') === 0) {
            $base_key = substr($key, 4);
            if (isset($valid_filters[$base_key])) {
                $op = (strpos($key, 'min_') === 0) ? '>=' : '<=';
                $query .= " AND CAST(JSON_UNQUOTE(JSON_EXTRACT(a.ad_data, '$.\"$base_key\"')) AS DECIMAL(15,2)) $op ?";
                $params[] = (float)$value;
            }
        }
    }
}

$query .= " ORDER BY a.is_featured DESC, a.bumped_at DESC";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$ads = $stmt->fetchAll();

include __DIR__ . '/templates/header.php';
?>

<div class="container mx-auto px-4 py-8">
    <div class="flex flex-col md:flex-row gap-8">
        <!-- Sidebar Navigation & Filters (Jiji Style) -->
        <aside class="hidden md:block w-72 flex-shrink-0">
            <!-- Category Navigation (Jiji Focused Style) -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden mb-6">
                <div class="p-4 bg-gray-50 border-b border-gray-100 flex items-center justify-between">
                    <a href="/" class="text-[10px] font-black text-gray-400 uppercase tracking-widest hover:text-primary-600 transition">All Categories</a>
                    <i class="fas fa-chevron-right text-[8px] text-gray-300"></i>
                </div>
                <div class="py-2">
                    <?php
                    // Find if current is a main category or a subcategory
                    $current_main_id = null;
                    $stmt_parent = $pdo->prepare("SELECT parent_id FROM categories WHERE id = ?");
                    $stmt_parent->execute([$cat_id]);
                    $p_id = $stmt_parent->fetchColumn();

                    if ($p_id == 0) {
                        $current_main_id = $cat_id;
                    } else {
                        $current_main_id = $p_id;
                    }

                    foreach ($main_categories as $mcat):
                        // Only show the active main category tree to save space for filters, exactly like Jiji
                        if ($mcat['id'] != $current_main_id) continue;
                    ?>
                        <div class="px-2">
                            <a href="/category/<?php echo $mcat['slug']; ?>" class="flex items-center justify-between p-3 rounded-lg bg-primary-50 text-primary-600 transition-all">
                                <div class="flex items-center gap-3">
                                    <i class="fas <?php echo h($mcat['icon_class']); ?> text-sm text-primary-600"></i>
                                    <span class="text-sm font-bold"><?php echo h($mcat['name']); ?></span>
                                </div>
                                <span class="text-[10px] font-bold opacity-50"><?php echo number_format($mcat['ad_count']); ?></span>
                            </a>

                            <?php if ($subcategories || ($p_id != 0)): ?>
                                <div class="ml-8 mt-2 space-y-1 pb-2">
                                    <?php
                                    $display_subs = $subcategories;
                                    if ($p_id != 0) {
                                        // We are in a subcategory, show siblings
                                        $stmt_subs = $pdo->prepare("SELECT c.*, (SELECT COUNT(*) FROM ads a WHERE a.cat_id = c.id AND a.status = 'active') as ad_count FROM categories c WHERE parent_id = ? ORDER BY name ASC");
                                        $stmt_subs->execute([$p_id]);
                                        $display_subs = $stmt_subs->fetchAll();
                                    }

                                    foreach ($display_subs as $sub):
                                        $is_active_sub = ($sub['id'] == $cat_id);
                                    ?>
                                        <a href="/category/<?php echo $sub['slug']; ?>" class="block py-1.5 text-xs font-bold <?php echo $is_active_sub ? 'text-primary-600' : 'text-gray-500 hover:text-primary-600'; ?> transition">
                                            <?php echo h($sub['name']); ?> <span class="text-[9px] opacity-40">(<?php echo $sub['ad_count']; ?>)</span>
                                        </a>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Filters -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 sticky top-24">
                <h3 class="text-xs font-black text-gray-800 uppercase tracking-widest mb-6 pb-2 border-b">Refine Results</h3>
                <form action="" method="GET" class="space-y-6">
                    <div class="mb-6">
                        <label class="flex items-center gap-3 cursor-pointer group">
                            <input type="checkbox" name="extra[verified_seller]" value="Verified sellers only" <?php echo ($extra["verified_seller"] ?? "") === "Verified sellers only" ? "checked" : ""; ?> onchange="this.form.submit()" class="w-5 h-5 rounded border-gray-300 text-primary-600 focus:ring-primary-500">
                            <span class="text-xs font-black text-gray-700 uppercase tracking-widest group-hover:text-primary-600 transition">NIN Verified Sellers Only</span>
                        </label>
                    </div>
                    <input type="hidden" name="type" value="<?php echo h($type); ?>">

                    <div>
                        <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-3">State</label>
                        <select name="state_id" onchange="loadLGAs(this.value); this.form.submit()" class="w-full p-3 bg-gray-50 border-none rounded-xl text-sm font-bold text-gray-700 focus:ring-2 focus:ring-primary-500 transition">
                            <option value="">All Nigeria</option>
                            <?php foreach ($states as $s): ?>
                                <option value="<?php echo $s['id']; ?>" <?php echo $state_id == $s['id'] ? 'selected' : ''; ?>><?php echo h($s['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div id="lga_filter_container" class="<?php echo !$state_id ? 'hidden' : ''; ?>">
                        <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-3">City / LGA</label>
                        <select name="lga_id" id="lga_filter" onchange="this.form.submit()" class="w-full p-3 bg-gray-50 border-none rounded-xl text-sm font-bold text-gray-700 focus:ring-2 focus:ring-primary-500 transition">
                            <option value="">All Cities</option>
                            <?php
                            if ($state_id) {
                                $stmt_lgas = $pdo->prepare("SELECT id, name FROM lgas WHERE state_id = ? ORDER BY name ASC");
                                $stmt_lgas->execute([$state_id]);
                                while($l = $stmt_lgas->fetch()) {
                                    $sel = ($lga_id == $l['id']) ? 'selected' : '';
                                    echo "<option value='{$l['id']}' $sel>".h($l['name'])."</option>";
                                }
                            }
                            ?>
                        </select>
                    </div>

                    <div id="price_range_container" class="hidden">
                        <!-- Replaced by dynamic filters to avoid duplication -->
                    </div>

                    <div id="dynamic_filters" class="space-y-6 pt-6 border-t border-gray-100">
                        <!-- Filters here -->
                    </div>

                    <button type="submit" class="w-full bg-primary-600 text-white py-4 rounded-xl font-black text-xs uppercase tracking-widest hover:bg-primary-700 transition shadow-lg shadow-primary-100">Apply Filters</button>

                    <?php if ($state_id || $min_price || $max_price || $extra): ?>
                        <a href="/category/<?php echo $slug; ?>" class="block text-center text-[10px] font-black text-red-400 uppercase tracking-widest mt-4 hover:text-red-600 transition">Clear All Filters</a>
                    <?php endif; ?>
                </form>
            </div>
        </aside>

        <!-- Main Content -->
        <div class="flex-1">
            <div class="bg-gradient-to-r from-primary-600 to-primary-700 rounded-3xl p-8 text-white mb-8 shadow-xl relative overflow-hidden">
                <div class="absolute top-0 right-0 w-32 h-32 bg-white/10 rounded-full -mr-16 -mt-16 blur-2xl"></div>
                <div class="relative z-10 flex flex-col md:flex-row items-center justify-between gap-6">
                    <div>
                        <h1 class="text-3xl font-black mb-2 uppercase tracking-tighter"><?php echo h($category['name']); ?></h1>
                        <p class="text-primary-100 font-bold opacity-80 text-sm">Showing verified listings in <?php echo h($category['name']); ?></p>
                    </div>
                    <a href="/post-ad?cat_id=<?php echo $cat_id; ?>" class="bg-yellow-500 text-white px-8 py-4 rounded-2xl font-black hover:bg-yellow-400 transition shadow-lg text-xs uppercase tracking-widest">SELL HERE</a>
                </div>
            </div>

            <!-- Horizontal Filter Bar (Type Selection) -->
            <div class="flex flex-wrap gap-3 mb-8">
                <a href="?type=all&state_id=<?php echo $state_id; ?>&min_price=<?php echo $min_price; ?>&max_price=<?php echo $max_price; ?>" class="px-6 py-3 rounded-2xl font-black text-[10px] uppercase tracking-widest transition <?php echo $type == 'all' ? 'bg-primary-600 text-white shadow-xl' : 'bg-white text-gray-500 hover:bg-primary-50'; ?> border border-gray-100">All Items</a>
                <a href="?type=sale&state_id=<?php echo $state_id; ?>&min_price=<?php echo $min_price; ?>&max_price=<?php echo $max_price; ?>" class="px-6 py-3 rounded-2xl font-black text-[10px] uppercase tracking-widest transition <?php echo $type == 'sale' ? 'bg-primary-600 text-white shadow-xl' : 'bg-white text-gray-500 hover:bg-primary-50'; ?> border border-gray-100">For Sale</a>
                <a href="?type=swap&state_id=<?php echo $state_id; ?>&min_price=<?php echo $min_price; ?>&max_price=<?php echo $max_price; ?>" class="px-6 py-3 rounded-2xl font-black text-[10px] uppercase tracking-widest transition <?php echo $type == 'swap' ? 'bg-blue-600 text-white shadow-xl' : 'bg-white text-gray-500 hover:bg-blue-50'; ?> border border-gray-100">Swap/Barter</a>
            </div>

            <div class="grid grid-cols-2 md:grid-cols-3 xl:grid-cols-4 gap-4 md:gap-6">
                <?php foreach ($ads as $ad): ?>
                <a href="<?php echo generate_ad_url($ad); ?>" class="bg-white rounded-2xl md:rounded-3xl shadow-sm overflow-hidden hover:shadow-2xl transition-all duration-500 border border-gray-100 group">
                    <?php $ad_img = $ad['image'] ? '/uploads/ads/'.$ad['image'] : 'https://placehold.co/400x300?text=No+Image'; ?>
                    <div class="relative h-40 md:h-48 overflow-hidden fit-to-frame" style="--bg-image: url('<?php echo $ad_img; ?>')">
                        <img src="<?php echo $ad_img; ?>" class="group-hover:scale-110 transition duration-700">
                        <?php if ($ad['is_featured']): ?>
                            <div class="absolute top-4 left-4 bg-yellow-400 text-yellow-900 text-[8px] font-black px-3 py-1 rounded-full uppercase shadow-xl border border-yellow-300">Premium</div>
                        <?php endif; ?>
                        <?php if ($ad['listing_type'] !== 'for_sale'): ?>
                            <div class="absolute top-4 right-4 bg-blue-600 text-white text-[8px] font-black px-3 py-1 rounded-full uppercase shadow-xl border border-blue-500"><i class="fas fa-sync-alt mr-1"></i> Swap</div>
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

                <?php if (empty($ads)): ?>
                    <div class="col-span-full bg-white p-20 rounded-[3rem] text-center border-2 border-dashed border-gray-100">
                        <div class="w-24 h-24 bg-gray-50 rounded-full flex items-center justify-center mx-auto mb-8">
                            <i class="fas fa-search text-gray-200 text-4xl"></i>
                        </div>
                        <h2 class="text-2xl font-black text-gray-800 mb-2 tracking-tighter">No results found</h2>
                        <p class="text-gray-400 font-bold">Try adjusting your filters or be the first to sell here!</p>
                        <a href="/post-ad?cat_id=<?php echo $cat_id; ?>" class="bg-primary-600 text-white px-10 py-5 rounded-2xl font-black hover:bg-primary-700 transition uppercase shadow-2xl inline-block mt-10 tracking-widest text-xs">POST AN AD NOW</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
function loadFilters(catId) {
    const filterContainer = document.getElementById('dynamic_filters');
    if (!catId) {
        filterContainer.innerHTML = '';
        return;
    }

    fetch('api/filters.php?cat_id=' + catId)
        .then(response => response.json())
        .then(filters => {
            let html = '';
            const currentExtra = <?php echo json_encode($extra); ?>;
            for (let key in filters) {
                const f = filters[key];
                html += '<div class="filter-group" data-filter-key="'+key+'">';
                html += `<label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2">${f.label}</label>`;

                const val = currentExtra[key] || '';

                if (key === 'price') {
                    if (f.quick_ranges) {
                        const priceQuick = document.getElementById('price_quick_ranges');
                        let phtml = '';
                        const min_val = '<?php echo $min_price ?: ''; ?>';
                        const max_val = '<?php echo $max_price ?: ''; ?>';
                        f.quick_ranges.forEach(range => {
                            const active = (min_val == range.min && max_val == range.max) ? 'bg-primary-600 text-white shadow-md' : 'bg-white text-gray-500 border border-gray-100 hover:bg-primary-50';
                            phtml += `<button type="button" onclick="setQuickRange('price', ${range.min}, ${range.max})" class="px-2 py-1.5 rounded-lg text-[9px] font-black uppercase tracking-tighter transition-all ${active}">${range.label}</button>`;
                        });
                        priceQuick.innerHTML = phtml;
                    }
                    continue;
                }

                if (f.type === 'select') {
                    if (f.searchable) {
                        html += `<div class="relative searchable-select group">
                            <div class="flex items-center bg-gray-50 rounded-xl px-3 focus-within:ring-2 focus-within:ring-primary-500 transition shadow-sm">
                                <i class="fas fa-search text-gray-300 text-[10px]"></i>
                                <input type="text" placeholder="Search ${f.label}..." onkeyup="filterSelectOptions(this)" class="w-full p-3 bg-transparent border-none text-xs font-bold text-gray-700 outline-none">
                                <button type="button" onclick="clearSearch(this)" class="hidden text-gray-300 hover:text-gray-500"><i class="fas fa-times-circle"></i></button>
                            </div>
                            <select name="extra[${key}]" onchange="this.form.submit()" size="5" class="w-full mt-2 p-2 bg-gray-50 border-none rounded-xl text-xs font-bold text-gray-700 focus:ring-2 focus:ring-primary-500 transition custom-scrollbar shadow-inner">
                                <option value="" ${val === '' ? 'selected' : ''} class="py-2 px-3">All ${f.label}</option>`;
                        f.options.forEach(opt => {
                            const sel = (val == opt) ? 'selected' : '';
                            html += `<option value="${opt}" ${sel} class="py-2 px-3 rounded-lg hover:bg-primary-100">${opt}</option>`;
                        });
                        html += `</select></div>`;
                    } else {
                        html += `<select name="extra[${key}]" onchange="this.form.submit()" class="w-full p-3 bg-gray-50 border-none rounded-xl text-xs font-bold text-gray-700 focus:ring-2 focus:ring-primary-500 transition">`;
                        html += '<option value="">All</option>';
                        f.options.forEach(opt => {
                            const sel = (val == opt) ? 'selected' : '';
                            html += `<option value="${opt}" ${sel}>${opt}</option>`;
                        });
                        html += '</select>';
                    }
                } else if (f.type === 'multi_select') {
                    html += `<div class="bg-gray-50 rounded-xl p-3 max-h-48 overflow-y-auto custom-scrollbar space-y-2">`;
                    if (f.searchable) {
                        html += `<div class="relative flex items-center mb-2">
                            <input type="text" placeholder="Search..." onkeyup="filterCheckboxes(this)" class="w-full p-2 bg-white border border-gray-100 rounded-lg text-[10px] font-bold text-gray-700 focus:ring-1 focus:ring-primary-500 outline-none pr-7">
                            <button type="button" onclick="clearCheckboxSearch(this)" class="hidden absolute right-2 text-gray-300 hover:text-gray-500 text-xs"><i class="fas fa-times-circle"></i></button>
                        </div>`;
                    }
                    f.options.forEach(opt => {
                        const isChecked = Array.isArray(val) ? val.includes(opt) : (val == opt);
                        html += `<label class="flex items-center gap-2 cursor-pointer group checkbox-item">
                            <input type="checkbox" name="extra[${key}][]" value="${opt}" ${isChecked ? 'checked' : ''} onchange="this.form.submit()" class="w-4 h-4 rounded border-gray-300 text-primary-600 focus:ring-primary-500">
                            <span class="text-[11px] font-bold text-gray-600 group-hover:text-primary-600 transition">${opt}</span>
                        </label>`;
                    });
                    html += `</div>`;
                } else if (f.type === 'checkbox') {
                    const isChecked = val == '1' ? 'checked' : '';
                    html += `<label class="flex items-center gap-2 cursor-pointer group">
                        <input type="checkbox" name="extra[${key}]" value="1" ${isChecked} onchange="this.form.submit()" class="w-4 h-4 rounded border-gray-300 text-primary-600 focus:ring-primary-500">
                        <span class="text-[11px] font-bold text-gray-600 group-hover:text-primary-600 transition">${f.label}</span>
                    </label>`;
                } else if (f.type === 'range' || f.type === 'number' || f.type === 'number_range') {
                    let min_val = (key === 'price') ? '<?php echo $min_price ?: ""; ?>' : (currentExtra['min_' + key] || '');
                    let max_val = (key === 'price') ? '<?php echo $max_price ?: ""; ?>' : (currentExtra['max_' + key] || '');
                    let min_name = (key === 'price') ? 'min_price' : `extra[min_${key}]`;
                    let max_name = (key === 'price') ? 'max_price' : `extra[max_${key}]`;

                    html += `<div class="grid grid-cols-2 gap-2 mb-3">
                        <input type="number" name="${min_name}" value="${min_val}" placeholder="Min" class="w-full p-3 bg-gray-50 border-none rounded-xl text-xs font-bold text-gray-700 focus:ring-2 focus:ring-primary-500 transition">
                        <input type="number" name="${max_name}" value="${max_val}" placeholder="Max" class="w-full p-3 bg-gray-50 border-none rounded-xl text-xs font-bold text-gray-700 focus:ring-2 focus:ring-primary-500 transition">
                    </div>`;

                    if (f.quick_ranges) {
                        html += '<div class="flex flex-wrap gap-1 mt-2">';
                        f.quick_ranges.forEach(range => {
                            const active = (min_val == range.min && max_val == range.max) ? 'bg-primary-600 text-white shadow-md' : 'bg-white text-gray-500 border border-gray-100 hover:bg-primary-50';
                            html += `<button type="button" onclick="setQuickRange('${key}', ${range.min}, ${range.max})" class="px-2 py-1.5 rounded-lg text-[9px] font-black uppercase tracking-tighter transition-all ${active}">${range.label}</button>`;
                        });
                        html += '</div>';
                    }
                }

                html += '</div>';
            }
            filterContainer.innerHTML = html;
        });
}

function setQuickRange(key, min, max) {
    let minInput, maxInput;
    if (key === 'price') {
        minInput = document.querySelector('input[name="min_price"]');
        maxInput = document.querySelector('input[name="max_price"]');
    } else {
        minInput = document.querySelector(`input[name="extra[min_${key}]"]`);
        maxInput = document.querySelector(`input[name="extra[max_${key}]"]`);
    }

    if (minInput && maxInput) {
        minInput.value = min;
        maxInput.value = max;
        minInput.form.submit();
    }
}

function loadLGAs(stateId) {
    const lgaSelect = document.getElementById('lga_filter');
    const lgaContainer = document.getElementById('lga_filter_container');

    if (!stateId) {
        lgaContainer.classList.add('hidden');
        lgaSelect.innerHTML = '<option value="">All Cities</option>';
        return;
    }

    fetch('/api/lgas.php?state_id=' + stateId)
        .then(response => response.json())
        .then(data => {
            lgaContainer.classList.remove('hidden');
            lgaSelect.innerHTML = '<option value="">All Cities</option>';
            data.forEach(lga => {
                const option = document.createElement('option');
                option.value = lga.id;
                option.textContent = lga.name;
                lgaSelect.appendChild(option);
            });
        });
}

function filterSelectOptions(input) {
    const filter = input.value.toLowerCase();
    const select = input.closest('.searchable-select').querySelector('select');
    const options = select.options;
    const clearBtn = input.nextElementSibling;

    if (filter.length > 0) {
        clearBtn.classList.remove('hidden');
    } else {
        clearBtn.classList.add('hidden');
    }

    for (let i = 0; i < options.length; i++) {
        const txt = options[i].text.toLowerCase();
        options[i].style.display = txt.includes(filter) || options[i].value === "" ? "" : "none";
    }
}

function clearSearch(btn) {
    const input = btn.previousElementSibling;
    input.value = '';
    btn.classList.add('hidden');
    filterSelectOptions(input);
}

function filterCheckboxes(input) {
    const filter = input.value.toLowerCase();
    const container = input.closest('.filter-group');
    const items = container.querySelectorAll('.checkbox-item');
    const clearBtn = input.nextElementSibling;

    if (filter.length > 0) {
        clearBtn.classList.remove('hidden');
    } else {
        clearBtn.classList.add('hidden');
    }

    items.forEach(item => {
        const txt = item.textContent.toLowerCase();
        item.style.display = txt.includes(filter) ? "" : "none";
    });
}

function clearCheckboxSearch(btn) {
    const input = btn.previousElementSibling;
    input.value = '';
    btn.classList.add('hidden');
    filterCheckboxes(input);
}

document.addEventListener('DOMContentLoaded', () => loadFilters(<?php echo $cat_id; ?>));
</script>

<style>
.custom-scrollbar::-webkit-scrollbar {
    width: 4px;
}
.custom-scrollbar::-webkit-scrollbar-track {
    background: transparent;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
    background: #e5e7eb;
    border-radius: 10px;
}
.custom-scrollbar::-webkit-scrollbar-thumb:hover {
    background: #d1d5db;
}
</style>

<?php include __DIR__ . '/templates/footer.php'; ?>
