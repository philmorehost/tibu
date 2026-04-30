<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/inc/functions.php';
require_once __DIR__ . '/inc/user_auth.php';
require_user();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $user_id = $_SESSION["user_id"];

        // Classifieds Feature 01: Verification Tiers & Limits
        $stmt = $pdo->prepare("SELECT verification_tier FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $user_tier = $stmt->fetchColumn();

        $stmt = $pdo->prepare("SELECT COUNT(*) FROM ads WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $post_count = $stmt->fetchColumn();

        if ($user_tier === "phone_verified" && $post_count >= 20) {
            throw new Exception("You have reached the limit of 20 free listings for Phone Verified accounts. Complete NIN verification to unlock unlimited postings.");
        }

        $title = $_POST['title'];
        $description = $_POST['description'];
        $listing_type = $_POST["listing_type"] ?? "for_sale";

        // Classifieds Feature 02: Swap restricted to NIN Verified
        if ($listing_type !== "for_sale" && $user_tier === "phone_verified") {
            throw new Exception("Swap listings are only available for NIN Verified sellers. Please verify your identity to continue.");
        }

        // Classifieds Feature 03: Velocity Check (Duplicate Listing)
        if (is_duplicate_listing($pdo, $user_id, $title, $description)) {
            throw new Exception("This listing appears to be a duplicate of another item you have already posted. Please check your inventory.");
        }

        $cat_id = (int)$_POST['cat_id'] ?: null;
        $state_id = (int)$_POST['state_id'] ?: null;
        $lga_id = (int)$_POST['lga_id'] ?: null;
        $price = (float)$_POST['price'];
        $estimated_value = !empty($_POST['estimated_value']) ? (float)$_POST['estimated_value'] : null;
        $swap_preference = $_POST['swap_preference'] ?? null;
        $allow_cash_topup = isset($_POST['allow_cash_topup']) ? 1 : 0;
        $property_role = $_POST['property_role'] ?? null;

        // Handle category-specific data
        $ad_data = null;
        if (isset($_POST['extra'])) {
            $ad_data = json_encode($_POST['extra']);
        }

        // Get free ad duration
        $stmt = $pdo->query("SELECT setting_value FROM settings WHERE setting_key = 'free_ad_duration'");
        $duration = (int)($stmt->fetchColumn() ?: 15);
        $expires_at = date('Y-m-d H:i:s', strtotime("+$duration days"));

        $stmt = $pdo->prepare("INSERT INTO ads (user_id, cat_id, state_id, lga_id, title, price, listing_type, estimated_value, swap_preference, allow_cash_topup, description, ad_data, status, expires_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', ?)");
        $stmt->execute([$user_id, $cat_id, $state_id, $lga_id, $title, $price, $listing_type, $estimated_value, $swap_preference, $allow_cash_topup, $description, $ad_data, $expires_at]);
        $ad_id = $pdo->lastInsertId();

        if ($property_role) {
            $pdo->prepare("INSERT INTO property_declarations (ad_id, user_id, declared_role) VALUES (?, ?, ?)")->execute([$ad_id, $user_id, $property_role]);
        }

        // Calculate initial Safety Score
        $safety_score = calculate_safety_score(['verification_tier' => $user_tier, 'is_verified' => 1, 'created_at' => date('Y-m-d')], ['description' => $description]);
        $pdo->prepare("UPDATE ads SET safety_score = ? WHERE id = ?")->execute([$safety_score, $ad_id]);

        // Process Images
        if (!empty($_FILES['images']['name'][0])) {
            foreach ($_FILES['images']['tmp_name'] as $key => $tmp_name) {
                if ($key >= 10) break; // Max 10 photos
                $filename = process_image_upload($tmp_name, __DIR__ . '/uploads/ads', 800, $user_id, $ad_id);
                if ($filename === "DUPLICATE") {
                    continue; // Skip duplicate photos
                }
                if ($filename) {
                    $is_main = ($key === 0) ? 1 : 0;
                    $stmt = $pdo->prepare("INSERT INTO ad_images (ad_id, image_path, is_main) VALUES (?, ?, ?)");
                    $stmt->execute([$ad_id, $filename, $is_main]);
                }
            }
        }

        redirect('profile.php', 'Ad posted successfully! It will be live after moderation.');
    } catch (Exception $e) {
        error_log("Post Ad Error: " . $e->getMessage());
        $error = "An error occurred while posting your ad. " . $e->getMessage();
    }
}

$categories = $pdo->query("SELECT * FROM categories WHERE parent_id = 0 ORDER BY name ASC")->fetchAll();
$states = $pdo->query("SELECT * FROM states ORDER BY name ASC")->fetchAll();

include __DIR__ . '/templates/header.php';
?>

<div class="container mx-auto px-4 py-10 flex justify-center">
    <div class="bg-white p-8 rounded-xl shadow-lg w-full max-w-2xl">
        <h1 class="text-2xl font-black mb-8 text-primary-600 border-b pb-4 uppercase tracking-tighter"><i class="fas fa-plus-circle mr-2"></i> Create Listing</h1>

        <?php if (isset($error)): ?>
            <div class="bg-red-100 text-red-700 p-4 rounded-lg mb-6 font-bold text-sm"><?php echo h($error); ?></div>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data" class="space-y-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-gray-700 font-bold mb-2 text-sm">Category</label>
                    <select id="parent_cat_id" class="w-full p-3 border rounded-lg focus:border-primary-500 outline-none" required onchange="loadSubcategories(this.value)">
                        <option value="">Select Category</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?php echo $cat['id']; ?>"><?php echo h($cat['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-gray-700 font-bold mb-2 text-sm">Subcategory</label>
                    <select name="cat_id" id="cat_id" class="w-full p-3 border rounded-lg focus:border-primary-500 outline-none" required onchange="loadFilters(this.value)">
                        <option value="">Select Subcategory</option>
                    </select>
                </div>
            </div>

            <div>
                <label class="block text-gray-700 font-bold mb-2 text-sm">Title</label>
                <input type="text" name="title" class="w-full p-3 border rounded-lg focus:border-primary-500 outline-none" placeholder="What are you selling?" required>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-gray-700 font-bold mb-2 text-sm">State</label>
                    <select name="state_id" id="state_id" class="w-full p-3 border rounded-lg focus:border-primary-500 outline-none" required onchange="loadLGAs(this.value)">
                        <option value="">Select State</option>
                        <?php foreach ($states as $state): ?>
                            <option value="<?php echo $state['id']; ?>"><?php echo h($state['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-gray-700 font-bold mb-2 text-sm">LGA (City)</label>
                    <select name="lga_id" id="lga_id" class="w-full p-3 border rounded-lg focus:border-primary-500 outline-none" required>
                        <option value="">Select LGA</option>
                    </select>
                </div>
            </div>

            <div class="p-6 bg-gray-50 rounded-2xl border border-gray-100 mb-6">
                <label class="block text-gray-700 font-black mb-4 text-xs uppercase tracking-widest">Listing Options</label>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                    <label class="relative flex flex-col p-4 bg-white rounded-xl border-2 border-transparent cursor-pointer hover:border-primary-200 has-[:checked]:border-primary-600 has-[:checked]:bg-primary-50 transition">
                        <input type="radio" name="listing_type" value="for_sale" checked class="absolute opacity-0" onchange="toggleSwapFields()">
                        <span class="text-xs font-bold text-gray-800">For Sale</span>
                        <span class="text-[9px] text-gray-400 mt-1">Direct monetary trade</span>
                    </label>
                    <label class="relative flex flex-col p-4 bg-white rounded-xl border-2 border-transparent cursor-pointer hover:border-primary-200 has-[:checked]:border-primary-600 has-[:checked]:bg-primary-50 transition">
                        <input type="radio" name="listing_type" value="for_swap" class="absolute opacity-0" onchange="toggleSwapFields()">
                        <span class="text-xs font-bold text-gray-800">For Swap</span>
                        <span class="text-[9px] text-gray-400 mt-1">Item-for-item exchange</span>
                    </label>
                    <label class="relative flex flex-col p-4 bg-white rounded-xl border-2 border-transparent cursor-pointer hover:border-primary-200 has-[:checked]:border-primary-600 has-[:checked]:bg-primary-50 transition">
                        <input type="radio" name="listing_type" value="for_sale_or_swap" class="absolute opacity-0" onchange="toggleSwapFields()">
                        <span class="text-xs font-bold text-gray-800">Sale or Swap</span>
                        <span class="text-[9px] text-gray-400 mt-1">Accept cash or items</span>
                    </label>
                </div>

                <div id="price_field">
                    <label class="block text-gray-700 font-bold mb-2 text-sm">Price (₦)</label>
                    <input type="number" name="price" step="0.01" class="w-full p-3 border rounded-lg focus:border-primary-500 outline-none" placeholder="10000">
                </div>

                <div id="swap_fields" class="hidden space-y-4">
                    <div>
                        <label class="block text-gray-700 font-bold mb-2 text-sm">Estimated Market Value (₦)</label>
                        <input type="number" name="estimated_value" step="0.01" class="w-full p-3 border rounded-lg focus:border-primary-500 outline-none" placeholder="e.g. 50000">
                    </div>
                    <div>
                        <label class="block text-gray-700 font-bold mb-2 text-sm">Swap Preference (What do you want in exchange?)</label>
                        <input type="text" name="swap_preference" class="w-full p-3 border rounded-lg focus:border-primary-500 outline-none" placeholder="e.g. iPhone 13 or equivalent laptop">
                    </div>
                    <label class="flex items-center gap-3 cursor-pointer">
                        <input type="checkbox" name="allow_cash_topup" value="1" class="w-5 h-5 accent-primary-600">
                        <span class="text-sm font-bold text-gray-700">Allow item + cash top-up</span>
                    </label>
                </div>
            </div>

            <div id="propertyDeclaration" class="hidden p-6 bg-blue-50 rounded-2xl border border-blue-100 mb-6">
                <label class="block text-blue-800 font-black mb-4 text-xs uppercase tracking-widest">Property Relationship</label>
                <div class="grid grid-cols-2 gap-4">
                    <label class="relative flex flex-col p-4 bg-white rounded-xl border-2 border-transparent cursor-pointer hover:border-blue-200 has-[:checked]:border-blue-600 has-[:checked]:bg-blue-50 transition">
                        <input type="radio" name="property_role" value="owner" class="absolute opacity-0">
                        <span class="text-xs font-bold text-gray-800">I am the Owner</span>
                        <span class="text-[9px] text-gray-400 mt-1">Direct property holder</span>
                    </label>
                    <label class="relative flex flex-col p-4 bg-white rounded-xl border-2 border-transparent cursor-pointer hover:border-blue-200 has-[:checked]:border-blue-600 has-[:checked]:bg-blue-50 transition">
                        <input type="radio" name="property_role" value="agent" class="absolute opacity-0">
                        <span class="text-xs font-bold text-gray-800">I am an Agent</span>
                        <span class="text-[9px] text-gray-400 mt-1">Authorized representative</span>
                    </label>
                </div>
            </div>

            <div id="dynamic_filters" class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-4">
                <!-- Filters injected here -->
            </div>

            <div class="mb-4">
                <label class="block text-gray-700 font-bold mb-2 text-sm">Description</label>
                <textarea name="description" rows="5" class="w-full p-3 border rounded-lg focus:border-primary-500 outline-none" placeholder="Provide details about the item..." required></textarea>
            </div>

            <div id="dropZone" class="mb-4 p-8 border-4 border-dashed border-gray-200 rounded-2xl bg-gray-50 hover:bg-white transition cursor-pointer relative group">
                <label class="block text-gray-700 font-bold mb-4 text-sm text-center">Upload Ad Photos (Maximum 10)</label>
                <input type="file" name="images[]" id="fileInput" multiple accept="image/*" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer" required>
                <div class="text-center">
                    <div class="w-20 h-20 bg-primary-50 rounded-full flex items-center justify-center mx-auto mb-4 group-hover:bg-primary-100 transition">
                        <i class="fas fa-camera text-3xl text-primary-500"></i>
                    </div>
                    <p class="text-sm font-bold text-gray-600 mb-1">Drag & drop up to 10 images</p>
                    <p class="text-xs text-gray-400 font-bold uppercase tracking-widest">or click to browse</p>
                </div>
            </div>

            <div id="imagePreviewContainer" class="grid grid-cols-5 gap-4 mb-6 hidden">
                <!-- Previews -->
            </div>

            <div class="pt-6">
                <button type="submit" class="w-full bg-primary-600 text-white py-5 rounded-2xl font-black hover:bg-primary-700 transition shadow-xl text-lg uppercase tracking-widest">Post My Ad</button>
            </div>
        </form>
    </div>
</div>

<script>
function loadSubcategories(parentId) {
    const subSelect = document.getElementById('cat_id');
    const filterContainer = document.getElementById('dynamic_filters');
    filterContainer.innerHTML = '';
    if (!parentId) { subSelect.innerHTML = '<option value="">Select Subcategory</option>'; return; }
    subSelect.innerHTML = '<option value="">Loading...</option>';
    fetch('api/subcategories.php?parent_id=' + parentId)
        .then(response => response.json())
        .then(data => {
            subSelect.innerHTML = '<option value="">Select Subcategory</option>';
            data.forEach(sub => {
                const option = document.createElement('option');
                option.value = sub.id;
                option.textContent = sub.name;
                subSelect.appendChild(option);
            });
        });
}

function loadFilters(catId) {
    const filterContainer = document.getElementById('dynamic_filters');
    if (!catId) { filterContainer.innerHTML = ''; return; }

    const propDecl = document.getElementById("propertyDeclaration");
    const catName = document.querySelector("#parent_cat_id option:checked")?.text || "";
    if (catName.toUpperCase().includes("PROPERTY") || catName.toUpperCase().includes("REAL ESTATE")) {
        propDecl.classList.remove("hidden");
    } else {
        propDecl.classList.add("hidden");
    }

    fetch('api/filters.php?cat_id=' + catId)
        .then(response => response.json())
        .then(filters => {
            let html = '';
            for (let key in filters) {
                const f = filters[key];
                if (f.search_only) continue;
                html += '<div>';
                html += `<label class="block text-gray-700 font-bold mb-2 text-sm">${f.label}</label>`;

                if (f.type === 'checkbox') {
                    html += `<label class="flex items-center gap-3 cursor-pointer py-2">
                        <input type="checkbox" name="extra[${key}]" value="1" class="w-5 h-5 accent-primary-600">
                        <span class="text-sm font-bold text-gray-700">${f.label}</span>
                    </label>`;
                } else if (f.type === 'select' || f.type === 'multi_select') {
                    if (f.searchable) {
                        html += `<div class="relative group">
                            <input type="text" placeholder="Search ${f.label}..." onkeyup="filterPostOptions(this)" class="w-full p-3 border rounded-t-lg focus:border-primary-500 outline-none mb-[1px]">
                            <select name="extra[${key}]" class="w-full p-3 border rounded-b-lg focus:border-primary-500 outline-none custom-select-list" size="5">
                                <option value="">Select ${f.label}</option>`;
                        f.options.forEach(opt => {
                            html += `<option value="${opt}">${opt}</option>`;
                        });
                        html += `</select></div>`;
                    } else {
                        html += `<select name="extra[${key}]" class="w-full p-3 border rounded-lg focus:border-primary-500 outline-none">`;
                        html += '<option value="">Select option</option>';
                        f.options.forEach(opt => {
                            html += `<option value="${opt}">${opt}</option>`;
                        });
                        html += '</select>';
                    }
                } else if (f.type === 'number' || f.type === 'number_range' || f.type === 'range') {
                    html += `<input type="number" name="extra[${key}]" class="w-full p-3 border rounded-lg focus:border-primary-500 outline-none" placeholder="Enter value">`;
                }
                html += '</div>';
            }
            filterContainer.innerHTML = html;
        });
}

function loadLGAs(stateId) {
    const lgaSelect = document.getElementById('lga_id');
    lgaSelect.innerHTML = '<option value="">Loading...</option>';
    if (!stateId) { lgaSelect.innerHTML = '<option value="">Select LGA</option>'; return; }
    fetch('api/lgas.php?state_id=' + stateId)
        .then(response => response.json())
        .then(data => {
            lgaSelect.innerHTML = '<option value="">Select LGA</option>';
            data.forEach(lga => {
                const option = document.createElement('option');
                option.value = lga.id;
                option.textContent = lga.name;
                lgaSelect.appendChild(option);
            });
        });
}

function filterPostOptions(input) {
    const filter = input.value.toLowerCase();
    const select = input.nextElementSibling;
    const options = select.options;
    for (let i = 0; i < options.length; i++) {
        const txt = options[i].text.toLowerCase();
        options[i].style.display = txt.includes(filter) || options[i].value === "" ? "" : "none";
    }
}

function toggleSwapFields() {
    const type = document.querySelector('input[name="listing_type"]:checked').value;
    const priceField = document.getElementById('price_field');
    const swapFields = document.getElementById('swap_fields');
    if (type === 'for_sale') {
        priceField.classList.remove('hidden');
        swapFields.classList.add('hidden');
    } else if (type === 'for_swap') {
        priceField.classList.add('hidden');
        swapFields.classList.remove('hidden');
    } else {
        priceField.classList.remove('hidden');
        swapFields.classList.remove('hidden');
    }
}

// Image handling
const dropZone = document.getElementById('dropZone');
const fileInput = document.getElementById('fileInput');
const previewContainer = document.getElementById('imagePreviewContainer');
let allFiles = new DataTransfer();

['dragover', 'dragleave', 'drop'].forEach(eventName => {
    dropZone.addEventListener(eventName, e => { e.preventDefault(); e.stopPropagation(); });
});
dropZone.addEventListener('dragover', () => dropZone.classList.add('bg-primary-50', 'border-primary-400'));
dropZone.addEventListener('dragleave', () => dropZone.classList.remove('bg-primary-50', 'border-primary-400'));
dropZone.addEventListener('drop', (e) => {
    dropZone.classList.remove('bg-primary-50', 'border-primary-400');
    if (e.dataTransfer.files.length > 0) addFiles(e.dataTransfer.files);
});
fileInput.addEventListener('change', () => addFiles(fileInput.files));

function addFiles(files) {
    for (let i = 0; i < files.length; i++) {
        if (allFiles.items.length < 10) allFiles.items.add(files[i]);
    }
    fileInput.files = allFiles.files;
    renderPreviews();
}

function removeFile(index) {
    const newDT = new DataTransfer();
    for (let i = 0; i < allFiles.files.length; i++) {
        if (i !== index) newDT.items.add(allFiles.files[i]);
    }
    allFiles = newDT;
    fileInput.files = allFiles.files;
    renderPreviews();
}

function renderPreviews() {
    previewContainer.innerHTML = '';
    if (allFiles.files.length === 0) { previewContainer.classList.add('hidden'); return; }
    previewContainer.classList.remove('hidden');
    Array.from(allFiles.files).forEach((file, i) => {
        const reader = new FileReader();
        reader.onload = (e) => {
            const previewDiv = document.createElement('div');
            previewDiv.className = 'relative group aspect-square rounded-xl overflow-hidden border-2 border-gray-100 shadow-sm';
            previewDiv.innerHTML = `
                <img src="${e.target.result}" class="w-full h-full object-cover">
                <div class="absolute inset-0 bg-black/40 opacity-0 group-hover:opacity-100 transition flex items-center justify-center">
                    <button type="button" onclick="removeFile(${i})" class="bg-red-500 text-white w-8 h-8 rounded-full flex items-center justify-center hover:bg-red-600 shadow-lg">
                        <i class="fas fa-trash-alt text-xs"></i>
                    </button>
                </div>
            `;
            previewContainer.appendChild(previewDiv);
        };
        reader.readAsDataURL(file);
    });
}
</script>

<?php include __DIR__ . '/templates/footer.php'; ?>
