<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/inc/functions.php';
require_once __DIR__ . '/inc/user_auth.php';

$q = $_GET['q'] ?? '';
$cat_id = (int)($_GET['cat_id'] ?? 0);
$state_id = (int)($_GET['state_id'] ?? 0);
$lga_id = (int)($_GET['lga_id'] ?? 0);
$min_price = (float)($_GET['min_price'] ?? 0);
$max_price = (float)($_GET['max_price'] ?? 0);
$type = $_GET['type'] ?? 'all';
$extra = $_GET['extra'] ?? [];

$query = "SELECT a.*, (SELECT image_path FROM ad_images WHERE ad_id = a.id AND is_main = 1 LIMIT 1) as image, s.name as state_name, c.name as cat_name
          FROM ads a
          JOIN states s ON a.state_id = s.id
          JOIN categories c ON a.cat_id = c.id
          JOIN users u ON a.user_id = u.id
          WHERE a.status = 'active' AND u.is_suspended = 0";

$params = [];

// Fetch categories for sidebar
$stmt = $pdo->query("SELECT c.*,
    (SELECT COUNT(*) FROM ads a
     JOIN users u ON a.user_id = u.id
     WHERE (a.cat_id = c.id OR a.cat_id IN (SELECT id FROM categories WHERE parent_id = c.id))
     AND a.status = 'active' AND u.is_suspended = 0) as ad_count
    FROM categories c WHERE parent_id = 0 ORDER BY sort_order ASC, name ASC");
$main_categories = $stmt->fetchAll();

// Get States for filter
$states = $pdo->query("SELECT * FROM states ORDER BY name ASC")->fetchAll();

if ($q) {
    $query .= " AND (a.title LIKE ? OR a.description LIKE ?)";
    $params[] = "%$q%";
    $params[] = "%$q%";
}
if ($cat_id) {
    $query .= " AND a.cat_id = ?";
    $params[] = $cat_id;
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
    // For search, we might not have a category name easily, so we whitelist by checking all possible filter keys
    $all_possible_keys = ['make', 'year', 'condition', 'transmission', 'mileage', 'registered', 'body_type', 'second_condition', 'color', 'engine_size', 'powertrain', 'fuel_type', 'exchange', 'brand', 'screen_size', 'storage', 'property_type', 'transaction_type', 'bedrooms', 'size', 'verified_seller', 'trusted_agent', 'discount', 'ram', 'network'];

    foreach ($extra as $key => $value) {
        if (empty($value)) continue;

        if (in_array($key, $all_possible_keys)) {
            if ($key === 'verified_seller' && $value === 'Verified sellers only') {
                $query .= " AND u.is_verified = 1";
            } else {
                $query .= " AND JSON_UNQUOTE(JSON_EXTRACT(a.ad_data, '$.\"$key\"')) = ?";
                $params[] = $value;
            }
        } elseif (strpos($key, 'min_') === 0 || strpos($key, 'max_') === 0) {
            $base_key = substr($key, 4);
            if (in_array($base_key, $all_possible_keys)) {
                $op = (strpos($key, 'min_') === 0) ? '>=' : '<=';
                $query .= " AND CAST(JSON_UNQUOTE(JSON_EXTRACT(a.ad_data, '$.\"$base_key\"')) AS DECIMAL(15,2)) $op ?";
                $params[] = $value;
            }
        }
    }
}
if ($type === 'sale') {
    $query .= " AND (a.listing_type = 'for_sale' OR a.listing_type = 'for_sale_or_swap')";
} elseif ($type === 'swap') {
    $query .= " AND (a.listing_type = 'for_swap' OR a.listing_type = 'for_sale_or_swap')";
}

$query .= " ORDER BY a.is_featured DESC, a.bumped_at DESC";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$ads = $stmt->fetchAll();

// Track Search History for Recommendations
if (is_user_logged_in() && !empty($ads)) {
    $user_id = $_SESSION['user_id'];
    $stmt_history = $pdo->prepare("INSERT INTO search_history (user_id, keyword, cat_id) VALUES (?, ?, ?)");
    $stmt_history->execute([$user_id, $q ?: null, $cat_id ?: null]);

    // Trigger automated marketing email (simulated)
    send_recommendations_email($user_id, $pdo, $settings);
}

include __DIR__ . '/templates/header.php';
?>

<div class="container mx-auto px-4 py-8">
    <div class="flex flex-col md:flex-row gap-8">
        <!-- Sidebar Navigation & Filters (Jiji Style) -->
        <aside class="hidden md:block w-72 flex-shrink-0">
            <!-- Category Navigation -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden mb-6">
                <div class="p-4 bg-gray-50 border-b border-gray-100">
                    <span class="text-[10px] font-black text-gray-400 uppercase tracking-widest">All Categories</span>
                </div>
                <div class="py-2">
                    <?php foreach ($main_categories as $mcat): ?>
                        <div class="px-2">
                            <a href="search.php?q=<?php echo h($q); ?>&cat_id=<?php echo $mcat['id']; ?>&state_id=<?php echo $state_id; ?>&type=<?php echo $type; ?>" class="flex items-center justify-between p-3 rounded-lg hover:bg-primary-50 transition-all <?php echo $mcat['id'] == $cat_id ? 'bg-primary-50 text-primary-600' : 'text-gray-700'; ?>">
                                <div class="flex items-center gap-3">
                                    <i class="fas <?php echo h($mcat['icon_class']); ?> text-sm opacity-50 <?php echo $mcat['id'] == $cat_id ? 'text-primary-600 opacity-100' : ''; ?>"></i>
                                    <span class="text-sm font-bold"><?php echo h($mcat['name']); ?></span>
                                </div>
                                <span class="text-[10px] font-bold opacity-50"><?php echo number_format($mcat['ad_count']); ?></span>
                            </a>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Filters -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 sticky top-24">
                <h3 class="text-xs font-black text-gray-800 uppercase tracking-widest mb-6 pb-2 border-b">Refine Results</h3>
                <form action="search.php" method="GET" class="space-y-6">
                    <input type="hidden" name="q" value="<?php echo h($q); ?>">
                    <input type="hidden" name="cat_id" value="<?php echo h($cat_id); ?>">

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

                    <div>
                        <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-3">Listing Type</label>
                        <select name="type" onchange="this.form.submit()" class="w-full p-3 bg-gray-50 border-none rounded-xl text-sm font-bold text-gray-700 focus:ring-2 focus:ring-primary-500 transition">
                            <option value="all" <?php echo $type == 'all' ? 'selected' : ''; ?>>All Types</option>
                            <option value="sale" <?php echo $type == 'sale' ? 'selected' : ''; ?>>For Sale</option>
                            <option value="swap" <?php echo $type == 'swap' ? 'selected' : ''; ?>>For Swap</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-3">Price Range (₦)</label>
                        <div class="grid grid-cols-2 gap-2">
                            <input type="number" name="min_price" value="<?php echo $min_price ?: ''; ?>" placeholder="Min" class="w-full p-3 bg-gray-50 border-none rounded-xl text-sm font-bold text-gray-700 focus:ring-2 focus:ring-primary-500 transition">
                            <input type="number" name="max_price" value="<?php echo $max_price ?: ''; ?>" placeholder="Max" class="w-full p-3 bg-gray-50 border-none rounded-xl text-sm font-bold text-gray-700 focus:ring-2 focus:ring-primary-500 transition">
                        </div>
                    </div>

                    <div id="dynamic_filters" class="space-y-4 pt-4 border-t border-gray-50">
                        <!-- Filters here -->
                    </div>

                    <button type="submit" class="w-full bg-primary-600 text-white py-4 rounded-xl font-black text-xs uppercase tracking-widest hover:bg-primary-700 transition shadow-lg shadow-primary-100">Apply Filters</button>

                    <?php if ($cat_id || $state_id || $min_price || $max_price || $q || $extra): ?>
                        <a href="search.php" class="block text-center text-[10px] font-black text-red-400 uppercase tracking-widest mt-4 hover:text-red-600 transition">Clear All Filters</a>
                    <?php endif; ?>
                </form>
            </div>
        </aside>

        <!-- Search Results (Right) -->
        <div class="flex-1">
            <div class="bg-white rounded-3xl p-8 mb-8 shadow-sm border border-gray-50">
                <h2 class="text-2xl font-black text-gray-800 uppercase tracking-tighter">
                    <?php echo $q ? "Search Results for \"".h($q)."\"" : "Marketplace Browser"; ?>
                    <span class="text-xs text-gray-400 ml-4 font-black bg-gray-100 px-3 py-1 rounded-full"><?php echo count($ads); ?> Listings Found</span>
                </h2>
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

            <?php if (empty($ads)): ?>
                <div class="bg-white p-20 rounded-[3rem] text-center border-2 border-dashed border-gray-100">
                    <div class="w-24 h-24 bg-gray-50 rounded-full flex items-center justify-center mx-auto mb-8">
                        <i class="fas fa-search-minus text-gray-200 text-4xl"></i>
                    </div>
                    <h2 class="text-2xl font-black text-gray-800 mb-2 tracking-tighter">No matching results</h2>
                    <p class="text-gray-400 font-bold">We couldn't find anything matching your search. Try different keywords or filters.</p>
                    <a href="search.php" class="bg-primary-600 text-white px-10 py-5 rounded-2xl font-black hover:bg-primary-700 transition uppercase shadow-2xl inline-block mt-10 tracking-widest text-xs">VIEW ALL LISTINGS</a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
`)
        .then(res => res.json())
        .then(data => {
            if (!data.success) {
                if (data.message.includes('login')) {
                    window.location.href = '/login';
                } else {
                    alert(data.message);
                }
                return;
            }

            const icons = document.querySelectorAll(`.save-icon-${adId}`);
            icons.forEach(icon => {
                const button = icon.parentElement;
                if (data.saved) {
                    icon.classList.replace('far', 'fas');
                    button.classList.add('text-red-500', 'bg-red-50');
                    button.classList.remove('text-gray-400');
                } else {
                    icon.classList.replace('fas', 'far');
                    button.classList.remove('text-red-500', 'bg-red-50');
                    button.classList.add('text-gray-400');
                }
            });

            const counter = document.getElementById('savedCounter');
            if (counter) {
                counter.textContent = data.count;
                counter.classList.toggle('hidden', parseInt(data.count) === 0);
            }
        });
}

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
                html += '<div>';
                html += `<label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2">${f.label}</label>`;

                const val = currentExtra[key] || '';

                if (f.type === 'select') {
                    html += `<select name="extra[${key}]" onchange="this.form.submit()" class="w-full p-3 bg-gray-50 border-none rounded-xl text-xs font-bold text-gray-700 focus:ring-2 focus:ring-primary-500 transition">`;
                    html += '<option value="">All</option>';
                    f.options.forEach(opt => {
                        const sel = (val == opt) ? 'selected' : '';
                        html += `<option value="${opt}" ${sel}>${opt}</option>`;
                    });
                    html += '</select>';
                } else if (f.type === 'number') {
                    html += `<input type="number" name="extra[${key}]" value="${val}" placeholder="Value" class="w-full p-3 bg-gray-50 border-none rounded-xl text-xs font-bold text-gray-700 focus:ring-2 focus:ring-primary-500 transition">`;
                } else if (f.type === 'range' || f.type === 'number_range') {
                    const min_val = currentExtra['min_' + key] || '';
                    const max_val = currentExtra['max_' + key] || '';

                    html += `<div class="grid grid-cols-2 gap-2 mb-3">
                        <input type="number" name="extra[min_${key}]" value="${min_val}" placeholder="Min" class="w-full p-3 bg-gray-50 border-none rounded-xl text-xs font-bold text-gray-700 focus:ring-2 focus:ring-primary-500 transition">
                        <input type="number" name="extra[max_${key}]" value="${max_val}" placeholder="Max" class="w-full p-3 bg-gray-50 border-none rounded-xl text-xs font-bold text-gray-700 focus:ring-2 focus:ring-primary-500 transition">
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
    const minInput = document.querySelector(`input[name="extra[min_${key}]"]`);
    const maxInput = document.querySelector(`input[name="extra[max_${key}]"]`);
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

// Load filters on page load if category is selected
<?php if ($cat_id): ?>
document.addEventListener('DOMContentLoaded', () => loadFilters(<?php echo $cat_id; ?>));
<?php endif; ?>
</script>

<?php include __DIR__ . '/templates/footer.php'; ?>
