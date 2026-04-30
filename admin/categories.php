<?php
require_once __DIR__ . '/../inc/auth.php';
require_once __DIR__ . '/../inc/functions.php';
require_once __DIR__ . '/../inc/security.php';
require_once __DIR__ . '/../inc/seeding_functions.php';
require_admin();

$error = null;
$success = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['seed_defaults'])) {
        seed_categories($pdo);
        $success = "Default categories have been restored successfully.";
    }

    if (isset($_POST['add_cat'])) {
        $parent_id = (int)($_POST['parent_id'] ?? 0);
        $names_raw = $_POST['name'];

        if ($parent_id > 0) {
            // Bulk subcategories
            $names = explode("\n", str_replace("\r", "", $names_raw));
            $count = 0;
            foreach ($names as $name) {
                $name = trim($name);
                if (empty($name)) continue;

                $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $name)));

                // Avoid duplicate slugs by appending a random string if necessary
                $check = $pdo->prepare("SELECT id FROM categories WHERE slug = ?");
                $check->execute([$slug]);
                if ($check->fetch()) {
                    $slug .= '-' . bin2hex(random_bytes(2));
                }

                $stmt = $pdo->prepare("INSERT INTO categories (name, slug, icon_class, is_top, sort_order, parent_id) VALUES (?, ?, '', 0, 0, ?)");
                $stmt->execute([$name, $slug, $parent_id]);
                $count++;
            }
            $success = "$count subcategories added.";
        } else {
            // Single main category
            $name = trim($names_raw);
            $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $name)));
            $icon = $_POST['icon'];
            $is_top = isset($_POST['is_top']) ? 1 : 0;
            $sort_order = (int)($_POST['sort_order'] ?? 0);

            if ($is_top) {
                $top_count = $pdo->query("SELECT COUNT(*) FROM categories WHERE is_top = 1")->fetchColumn();
                if ($top_count >= 4) {
                    $error = "Maximum of 4 categories can be shown in the top grid. Please unstar another category first.";
                    $is_top = 0;
                }
            }

            if (!$error) {
                $stmt = $pdo->prepare("INSERT INTO categories (name, slug, icon_class, is_top, sort_order, parent_id) VALUES (?, ?, ?, ?, ?, 0)");
                $stmt->execute([$name, $slug, $icon, $is_top, $sort_order]);
                $success = "Main category added.";
            }
        }
    }

    if (isset($_POST['edit_cat'])) {
        $id = (int)$_POST['cat_id'];
        $name = $_POST['name'];
        $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $name)));
        $icon = $_POST['icon'];
        $sort_order = (int)($_POST['sort_order'] ?? 0);
        $parent_id = (int)($_POST['parent_id'] ?? 0);

        $stmt = $pdo->prepare("UPDATE categories SET name = ?, slug = ?, icon_class = ?, sort_order = ?, parent_id = ? WHERE id = ?");
        $stmt->execute([$name, $slug, $icon, $sort_order, $parent_id, $id]);
        $success = "Category updated.";
    }

    if (isset($_POST['delete_cat'])) {
        $id = (int)$_POST['cat_id'];
        $pdo->prepare("DELETE FROM categories WHERE id = ? OR parent_id = ?")->execute([$id, $id]);
        $success = "Category and its subcategories deleted.";
    }
}

if (isset($_GET['toggle_top'])) {
    $id = (int)$_GET['toggle_top'];
    $current = $pdo->prepare("SELECT is_top FROM categories WHERE id = ?");
    $current->execute([$id]);
    $is_now_top = $current->fetchColumn();

    if (!$is_now_top) { // Trying to set it to top
        $top_count = $pdo->query("SELECT COUNT(*) FROM categories WHERE is_top = 1")->fetchColumn();
        if ($top_count >= 4) {
            header("Location: categories.php?error=" . urlencode("Maximum of 4 categories can be shown in the top grid."));
            exit;
        }
    }

    $pdo->prepare("UPDATE categories SET is_top = 1 - is_top WHERE id = ?")->execute([$id]);
    header("Location: categories.php?success=" . urlencode("Top status updated."));
    exit;
}

$success = $_GET['success'] ?? $success;
$error = $_GET['error'] ?? $error;

include __DIR__ . '/../templates/admin_header.php';
?>

<div class="container mx-auto px-4 py-8">
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-10 gap-4">
        <div>
            <h2 class="text-3xl font-black text-gray-800 uppercase tracking-tighter italic">Manage <span class="text-primary-600">Categories</span></h2>
            <div class="flex items-center gap-4 mt-1">
                <p class="text-gray-400 font-bold text-sm">Organize your marketplace hierarchy and mobile grid.</p>
                <form method="POST" onsubmit="return confirm('This will reset ALL categories to defaults. Proceed?')">
                    <button type="submit" name="seed_defaults" class="text-[10px] font-black text-primary-600 uppercase tracking-widest bg-primary-50 px-3 py-1 rounded-full hover:bg-primary-600 hover:text-white transition">
                        <i class="fas fa-magic mr-1"></i> Auto-Add Defaults
                    </button>
                </form>
            </div>
        </div>
        <div class="bg-yellow-50 border border-yellow-200 px-6 py-3 rounded-2xl flex items-center gap-3">
            <div class="w-10 h-10 bg-yellow-400 rounded-full flex items-center justify-center text-white shadow-lg">
                <i class="fas fa-star"></i>
            </div>
            <div>
                <?php $top_count = $pdo->query("SELECT COUNT(*) FROM categories WHERE is_top = 1")->fetchColumn(); ?>
                <p class="text-[10px] font-black text-yellow-700 uppercase tracking-widest">Mobile Grid Slots</p>
                <p class="text-xl font-black text-yellow-900"><?php echo $top_count; ?> / 4 used</p>
            </div>
        </div>
    </div>

    <?php if ($success): ?>
        <div class="bg-primary-100 text-primary-700 p-4 rounded-2xl mb-8 font-bold flex items-center gap-3">
            <i class="fas fa-check-circle"></i> <?php echo h($success); ?>
        </div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="bg-red-100 text-red-700 p-4 rounded-2xl mb-8 font-bold flex items-center gap-3">
            <i class="fas fa-exclamation-circle"></i> <?php echo h($error); ?>
        </div>
    <?php endif; ?>

    <!-- Category Explorer Card -->
    <div class="bg-white rounded-[2.5rem] shadow-sm border border-gray-100 mb-12 overflow-hidden">
        <div class="p-8 border-b border-gray-100 bg-gray-50/50">
            <div class="flex flex-col md:flex-row justify-between items-center gap-6">
                <div class="w-full md:w-1/2">
                    <label class="block text-[10px] font-black text-gray-500 uppercase mb-3 ml-2 tracking-widest">Select Main Category to Manage Subcategories</label>
                    <div class="relative">
                        <select id="mainCatSelector" class="w-full p-5 bg-white border-2 border-gray-100 rounded-2xl font-black text-gray-800 focus:ring-4 focus:ring-primary-500/10 focus:border-primary-500 transition appearance-none cursor-pointer pr-12">
                            <option value="">-- Choose a category --</option>
                            <?php
                            $main_cats = $pdo->query("SELECT * FROM categories WHERE parent_id = 0 ORDER BY sort_order ASC, name ASC")->fetchAll();
                            foreach ($main_cats as $mc):
                            ?>
                            <option value="<?php echo $mc['id']; ?>" data-icon="<?php echo h($mc['icon_class']); ?>" data-order="<?php echo $mc['sort_order']; ?>" data-top="<?php echo $mc['is_top']; ?>">
                                <?php echo h($mc['name']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="absolute right-5 top-1/2 -translate-y-1/2 pointer-events-none text-gray-400">
                            <i class="fas fa-chevron-down"></i>
                        </div>
                    </div>
                </div>
                <div class="flex gap-3">
                    <button onclick="openAddModal(0)" class="bg-primary-600 text-white px-8 py-5 rounded-2xl font-black text-xs uppercase tracking-widest hover:bg-primary-700 transition shadow-xl shadow-primary-100 flex items-center gap-2">
                        <i class="fas fa-plus"></i> New Main Category
                    </button>
                </div>
            </div>
        </div>

        <!-- Dynamic Subcategory List -->
        <div id="subContainer" class="p-8 hidden">
            <div class="flex justify-between items-center mb-8">
                <div>
                    <h3 id="selectedCatName" class="text-2xl font-black text-gray-800 uppercase tracking-tighter italic">Subcategories</h3>
                    <div class="flex items-center gap-4 mt-1">
                        <span id="catStats" class="text-[10px] font-black text-gray-400 uppercase tracking-widest">0 items</span>
                        <div id="mainCatActions" class="flex gap-2 ml-4">
                            <!-- Injected main category buttons -->
                        </div>
                    </div>
                </div>
                <button id="addSubBtn" class="bg-white border-2 border-primary-600 text-primary-600 px-6 py-3 rounded-xl font-black text-[10px] uppercase tracking-widest hover:bg-primary-600 hover:text-white transition flex items-center gap-2">
                    <i class="fas fa-plus"></i> Add Subcategory
                </button>
            </div>

            <div id="subGrid" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
                <!-- Subcategories injected here -->
            </div>

            <div id="noSubsMsg" class="hidden text-center py-12 bg-gray-50 rounded-3xl border-2 border-dashed border-gray-200">
                <i class="fas fa-layer-group text-4xl text-gray-200 mb-4"></i>
                <p class="text-gray-400 font-bold">No subcategories found for this category.</p>
            </div>
        </div>

        <!-- Initial Placeholder -->
        <div id="explorerPlaceholder" class="p-20 text-center">
            <div class="w-20 h-20 bg-primary-50 rounded-full flex items-center justify-center mx-auto mb-6 text-primary-600 text-3xl">
                <i class="fas fa-sitemap"></i>
            </div>
            <h4 class="text-xl font-black text-gray-800 uppercase tracking-tighter mb-2">Category Explorer</h4>
            <p class="text-gray-400 font-bold max-w-sm mx-auto">Select a main category from the dropdown above to view and manage its subcategories.</p>
        </div>
    </div>
</div>

<!-- Add/Edit Modal -->
<div id="catModal" class="fixed inset-0 bg-black/60 z-[100] hidden items-center justify-center backdrop-blur-sm p-4">
    <div class="bg-white w-full max-w-lg rounded-[2.5rem] p-10 shadow-2xl animate-slide-up" onclick="event.stopPropagation()">
        <div class="flex justify-between items-center mb-8">
            <h3 id="modalTitle" class="text-2xl font-black text-gray-800 uppercase tracking-tighter italic">Add <span class="text-primary-600">Category</span></h3>
            <button onclick="closeModal()" class="w-10 h-10 bg-gray-50 rounded-full flex items-center justify-center text-gray-400"><i class="fas fa-times"></i></button>
        </div>
        <form method="POST" class="space-y-6">
            <input type="hidden" name="cat_id" id="form_id">
            <input type="hidden" name="parent_id" id="form_parent">
            <div>
                <label id="nameLabel" class="block text-[10px] font-black text-gray-500 uppercase mb-2 ml-2">Name</label>
                <textarea name="name" id="form_name" class="w-full p-4 bg-gray-50 border-none rounded-2xl font-bold text-gray-700 focus:ring-2 focus:ring-primary-500 transition" required rows="1"></textarea>
            </div>
            <div id="iconAndOrder" class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-[10px] font-black text-gray-500 uppercase mb-2 ml-2">Icon (FontAwesome)</label>
                    <input type="text" name="icon" id="form_icon" class="w-full p-4 bg-gray-50 border-none rounded-2xl font-bold text-gray-700 focus:ring-2 focus:ring-primary-500 transition" placeholder="fa-tv">
                </div>
                <div>
                    <label class="block text-[10px] font-black text-gray-500 uppercase mb-2 ml-2">Sort Order</label>
                    <input type="number" name="sort_order" id="form_order" class="w-full p-4 bg-gray-50 border-none rounded-2xl font-bold text-gray-700 focus:ring-2 focus:ring-primary-500 transition" value="0">
                </div>
            </div>
            <div id="parentDisplayBox" class="p-4 bg-blue-50 rounded-2xl border border-blue-100 hidden">
                <p class="text-[10px] font-black text-blue-400 uppercase tracking-widest mb-1">Adding subcategory to:</p>
                <p id="parentNameDisplay" class="font-bold text-blue-900"></p>
            </div>

            <button type="submit" name="add_cat" id="submitBtn" class="w-full bg-primary-600 text-white p-4 rounded-2xl font-black text-xs uppercase tracking-widest hover:bg-primary-700 transition shadow-xl shadow-primary-100">Create Category</button>
        </form>
    </div>
</div>

<script>
const mainCatSelector = document.getElementById('mainCatSelector');
const subContainer = document.getElementById('subContainer');
const explorerPlaceholder = document.getElementById('explorerPlaceholder');
const subGrid = document.getElementById('subGrid');
const selectedCatName = document.getElementById('selectedCatName');
const catStats = document.getElementById('catStats');
const noSubsMsg = document.getElementById('noSubsMsg');
const mainCatActions = document.getElementById('mainCatActions');

mainCatSelector.addEventListener('change', function() {
    const parentId = this.value;
    if (!parentId) {
        subContainer.classList.add('hidden');
        explorerPlaceholder.classList.remove('hidden');
        return;
    }

    explorerPlaceholder.classList.add('hidden');
    subContainer.classList.remove('hidden');
    loadSubcategories(parentId);
});

async function loadSubcategories(parentId) {
    subGrid.innerHTML = '<div class="col-span-full text-center py-10 text-gray-400 font-bold"><i class="fas fa-spinner fa-spin mr-2"></i> Loading...</div>';

    const option = mainCatSelector.options[mainCatSelector.selectedIndex];
    selectedCatName.innerHTML = `Subcategories for <span class="text-primary-600">${option.text}</span>`;

    // Update main cat actions
    mainCatActions.innerHTML = `
        <button onclick="editMainCat(${parentId})" class="text-[10px] font-black text-blue-600 hover:text-blue-800 uppercase tracking-widest">Edit</button>
        <span class="text-gray-200">|</span>
        <a href="categories.php?toggle_top=${parentId}" class="text-[10px] font-black ${option.dataset.top == 1 ? 'text-yellow-500' : 'text-gray-400'} hover:text-yellow-600 uppercase tracking-widest">
            <i class="fas fa-star mr-1"></i> ${option.dataset.top == 1 ? 'Featured' : 'Feature'}
        </a>
    `;

    document.getElementById('addSubBtn').onclick = () => openAddModal(parentId);

    try {
        const response = await fetch(`../api/admin_subcategories.php?parent_id=${parentId}`);
        const subs = await response.json();

        subGrid.innerHTML = '';
        if (subs.length === 0) {
            noSubsMsg.classList.remove('hidden');
            catStats.innerText = "0 Subcategories";
        } else {
            noSubsMsg.classList.add('hidden');
            catStats.innerText = `${subs.length} Subcategories`;

            subs.forEach(sub => {
                const card = document.createElement('div');
                card.className = "group bg-white p-5 rounded-3xl border-2 border-gray-50 hover:border-primary-100 hover:shadow-lg hover:shadow-primary-500/5 transition-all flex items-center justify-between";
                card.innerHTML = `
                    <div class="overflow-hidden">
                        <p class="font-black text-gray-800 truncate text-sm uppercase tracking-tighter">${sub.name}</p>
                        <p class="text-[9px] text-gray-400 font-bold uppercase tracking-widest mt-0.5">Order: ${sub.sort_order}</p>
                    </div>
                    <div class="flex gap-2 opacity-0 group-hover:opacity-100 transition-opacity">
                        <button onclick='openEditModal(${JSON.stringify(sub)})' class="w-8 h-8 bg-blue-50 text-blue-600 rounded-lg flex items-center justify-center hover:bg-blue-600 hover:text-white transition">
                            <i class="fas fa-edit text-[10px]"></i>
                        </button>
                        <form method="POST" onsubmit="return confirm('Delete this subcategory?')">
                            <input type="hidden" name="cat_id" value="${sub.id}">
                            <button type="submit" name="delete_cat" class="w-8 h-8 bg-red-50 text-red-400 rounded-lg flex items-center justify-center hover:bg-red-500 hover:text-white transition">
                                <i class="fas fa-trash text-[10px]"></i>
                            </button>
                        </form>
                    </div>
                `;
                subGrid.appendChild(card);
            });
        }
    } catch (e) {
        subGrid.innerHTML = '<div class="col-span-full text-center py-10 text-red-500 font-bold">Error loading subcategories.</div>';
    }
}

function editMainCat(id) {
    const option = mainCatSelector.options[mainCatSelector.selectedIndex];
    openEditModal({
        id: id,
        name: option.text.trim(),
        icon_class: option.dataset.icon,
        sort_order: option.dataset.order,
        parent_id: 0
    });
}

function openAddModal(parentId) {
    resetForm();
    document.getElementById('form_parent').value = parentId;
    document.getElementById('modalTitle').innerHTML = parentId == 0 ? 'New <span class="text-primary-600">Main Category</span>' : 'New <span class="text-primary-600">Subcategories</span>';
    document.getElementById('submitBtn').name = 'add_cat';
    document.getElementById('submitBtn').innerText = parentId == 0 ? 'Create Category' : 'Create Subcategories';

    if (parentId > 0) {
        document.getElementById('iconAndOrder').classList.add('hidden');
        document.getElementById('parentDisplayBox').classList.remove('hidden');
        document.getElementById('parentNameDisplay').innerText = mainCatSelector.options[mainCatSelector.selectedIndex].text;
        document.getElementById('form_name').rows = 5;
        document.getElementById('form_name').placeholder = "Enter subcategories, one per line...";
        document.getElementById('nameLabel').innerText = "Subcategory Names (One per line)";
    } else {
        document.getElementById('iconAndOrder').classList.remove('hidden');
        document.getElementById('parentDisplayBox').classList.add('hidden');
        document.getElementById('form_name').rows = 1;
        document.getElementById('form_name').placeholder = "";
        document.getElementById('nameLabel').innerText = "Name";
    }

    showModal();
}

function openEditModal(cat) {
    resetForm();
    document.getElementById('form_id').value = cat.id;
    document.getElementById('form_name').value = cat.name;
    document.getElementById('form_icon').value = cat.icon_class || '';
    document.getElementById('form_order').value = cat.sort_order;
    document.getElementById('form_parent').value = cat.parent_id;

    document.getElementById('modalTitle').innerHTML = 'Edit <span class="text-blue-600">Category</span>';
    document.getElementById('submitBtn').name = 'edit_cat';
    document.getElementById('submitBtn').innerText = 'Update Category';
    document.getElementById('submitBtn').className = "w-full bg-blue-600 text-white p-4 rounded-2xl font-black text-xs uppercase tracking-widest hover:bg-blue-700 transition shadow-xl shadow-blue-100";

    document.getElementById('form_name').rows = 1;
    document.getElementById('nameLabel').innerText = "Name";

    if (cat.parent_id > 0) {
        document.getElementById('iconAndOrder').classList.add('hidden');
        document.getElementById('parentDisplayBox').classList.remove('hidden');
        document.getElementById('parentNameDisplay').innerText = "Changing subcategory";
    } else {
        document.getElementById('iconAndOrder').classList.remove('hidden');
        document.getElementById('parentDisplayBox').classList.add('hidden');
    }

    showModal();
}

function resetForm() {
    document.getElementById('form_id').value = '';
    document.getElementById('form_parent').value = '0';
    document.getElementById('form_name').value = '';
    document.getElementById('form_icon').value = '';
    document.getElementById('form_order').value = '0';
    document.getElementById('form_name').rows = 1;
    document.getElementById('submitBtn').className = "w-full bg-primary-600 text-white p-4 rounded-2xl font-black text-xs uppercase tracking-widest hover:bg-primary-700 transition shadow-xl shadow-primary-100";
}

function showModal() {
    const modal = document.getElementById('catModal');
    modal.classList.remove('hidden');
    modal.classList.add('flex');
}

function closeModal() {
    const modal = document.getElementById('catModal');
    modal.classList.add('hidden');
    modal.classList.remove('flex');
}

// Close modal on escape
window.addEventListener('keydown', (e) => { if(e.key === 'Escape') closeModal(); });
</script>

<?php include __DIR__ . '/../templates/admin_footer.php'; ?>
