<?php
require_once __DIR__ . '/../inc/auth.php';
require_once __DIR__ . '/../inc/functions.php';
require_once __DIR__ . '/../inc/security.php';
require_admin();

$stmt = $pdo->query("SELECT setting_key, setting_value FROM settings");
$settings = [];
while ($row = $stmt->fetch()) {
    $settings[$row['setting_key']] = $row['setting_value'];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_package'])) {
    $tier = $_POST['tier'];
    $sql = "UPDATE packages SET
            name = ?, price = ?, duration_days = ?, cashback = ?, power_up = ?,
            listings_cars = ?, listings_property = ?, listings_others = ?,
            promo_ads_count = ?, auto_renew_hours = ?, has_social_links = ?,
            has_personal_manager = ?, has_insights_report = ?, has_feedback_tool = ?,
            has_pro_sales = ?, has_email_promo = ?
            WHERE tier = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        $_POST['name'], $_POST['price'], $_POST['duration_days'], $_POST['cashback'], $_POST['power_up'],
        $_POST['listings_cars'], $_POST['listings_property'], $_POST['listings_others'],
        $_POST['promo_ads_count'], $_POST['auto_renew_hours'], isset($_POST['has_social_links']) ? 1 : 0,
        isset($_POST['has_personal_manager']) ? 1 : 0, isset($_POST['has_insights_report']) ? 1 : 0,
        isset($_POST['has_feedback_tool']) ? 1 : 0, isset($_POST['has_pro_sales']) ? 1 : 0,
        isset($_POST['has_email_promo']) ? 1 : 0,
        $tier
    ]);
    redirect('packages.php', 'Package ' . ucfirst($tier) . ' updated successfully.');
}

$packages = $pdo->query("SELECT * FROM packages ORDER BY price ASC")->fetchAll();

include __DIR__ . '/../templates/admin_header.php';
?>

<div class="bg-white p-8 rounded-lg shadow-sm">
    <h2 class="text-2xl font-bold mb-8 text-gray-800 border-b pb-4">Manage Package Plans</h2>

    <div class="grid grid-cols-1 gap-10">
        <?php foreach ($packages as $pkg): ?>
        <div class="border rounded-xl p-6 bg-gray-50">
            <form method="POST" class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-4 gap-6">
                <input type="hidden" name="tier" value="<?php echo $pkg['tier']; ?>">

                <div>
                    <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Package Name</label>
                    <input type="text" name="name" value="<?php echo h($pkg['name']); ?>" class="w-full p-2 border rounded">
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Price (₦)</label>
                    <input type="number" name="price" value="<?php echo h($pkg['price']); ?>" class="w-full p-2 border rounded">
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Duration (Days)</label>
                    <input type="number" name="duration_days" value="<?php echo h($pkg['duration_days']); ?>" class="w-full p-2 border rounded">
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Cashback (₦)</label>
                    <input type="number" name="cashback" value="<?php echo h($pkg['cashback']); ?>" class="w-full p-2 border rounded">
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Power-up Effect</label>
                    <input type="text" name="power_up" value="<?php echo h($pkg['power_up']); ?>" class="w-full p-2 border rounded" placeholder="e.g. 5x more clients">
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Listings in Cars</label>
                    <input type="number" name="listings_cars" value="<?php echo h($pkg['listings_cars']); ?>" class="w-full p-2 border rounded">
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Listings in Property</label>
                    <input type="number" name="listings_property" value="<?php echo h($pkg['listings_property']); ?>" class="w-full p-2 border rounded">
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Listings in Others</label>
                    <input type="number" name="listings_others" value="<?php echo h($pkg['listings_others']); ?>" class="w-full p-2 border rounded">
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Promo Ads Count</label>
                    <input type="number" name="promo_ads_count" value="<?php echo h($pkg['promo_ads_count']); ?>" class="w-full p-2 border rounded">
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Auto-renew (Hours)</label>
                    <input type="number" name="auto_renew_hours" value="<?php echo h($pkg['auto_renew_hours']); ?>" class="w-full p-2 border rounded" placeholder="0 for disabled">
                </div>

                <div class="md:col-span-3 lg:col-span-4 grid grid-cols-2 md:grid-cols-5 gap-4 mt-4">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" name="has_social_links" <?php echo $pkg['has_social_links'] ? 'checked' : ''; ?> class="w-4 h-4 text-primary-600">
                        <span class="text-xs font-bold text-gray-700">Social Links</span>
                    </label>
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" name="has_personal_manager" <?php echo $pkg['has_personal_manager'] ? 'checked' : ''; ?> class="w-4 h-4 text-primary-600">
                        <span class="text-xs font-bold text-gray-700">Personal Manager</span>
                    </label>
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" name="has_insights_report" <?php echo $pkg['has_insights_report'] ? 'checked' : ''; ?> class="w-4 h-4 text-primary-600">
                        <span class="text-xs font-bold text-gray-700">Insights Report</span>
                    </label>
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" name="has_feedback_tool" <?php echo $pkg['has_feedback_tool'] ? 'checked' : ''; ?> class="w-4 h-4 text-primary-600">
                        <span class="text-xs font-bold text-gray-700">Feedback Tool</span>
                    </label>
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" name="has_pro_sales" <?php echo $pkg['has_pro_sales'] ? 'checked' : ''; ?> class="w-4 h-4 text-primary-600">
                        <span class="text-xs font-bold text-gray-700">Pro Sales</span>
                    </label>
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" name="has_email_promo" <?php echo $pkg['has_email_promo'] ? 'checked' : ''; ?> class="w-4 h-4 text-primary-600">
                        <span class="text-xs font-bold text-gray-700">Email/Social Promo</span>
                    </label>
                </div>

                <div class="md:col-span-3 lg:col-span-4 flex justify-end mt-4">
                    <button type="submit" name="save_package" class="bg-primary-600 text-white px-6 py-2 rounded-lg font-bold hover:bg-primary-700 transition shadow-sm">Save Changes</button>
                </div>
            </form>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<?php include __DIR__ . '/../templates/admin_footer.php'; ?>
