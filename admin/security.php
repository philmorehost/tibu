<?php
require_once __DIR__ . '/../inc/auth.php';
require_once __DIR__ . '/../inc/functions.php';
require_once __DIR__ . '/../inc/security.php';
require_admin();

// Get settings
$settings_res = $pdo->query("SELECT setting_key, setting_value FROM settings");
$settings = [];
while ($row = $settings_res->fetch()) {
    $settings[$row['setting_key']] = $row['setting_value'];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['save_security'])) {
        $keys = ['brute_force_period', 'max_failures_account', 'max_failures_ip', 'ip_block_duration'];
        $stmt = $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");
        foreach ($keys as $key) {
            $stmt->execute([$key, $_POST[$key]]);
        }
        redirect('security.php', 'Security settings updated.');
    }

    if (isset($_POST['blacklist_ip'])) {
        $ip = $_POST['ip'];
        $reason = $_POST['reason'];
        $stmt = $pdo->prepare("INSERT INTO ip_security (ip_address, status, reason) VALUES (?, 'blacklisted', ?) ON DUPLICATE KEY UPDATE status = 'blacklisted', reason = ?");
        $stmt->execute([$ip, $reason, $reason]);
        redirect('security.php', "IP $ip blacklisted.");
    }

    if (isset($_POST['whitelist_ip'])) {
        $ip = $_POST['ip'];
        $stmt = $pdo->prepare("INSERT INTO ip_security (ip_address, status) VALUES (?, 'whitelisted') ON DUPLICATE KEY UPDATE status = 'whitelisted'");
        $stmt->execute([$ip]);
        redirect('security.php', "IP $ip whitelisted.");
    }

    if (isset($_POST['country_action'])) {
        $country_id = $_POST['country_id'];
        $status = $_POST['status'];
        $stmt = $pdo->prepare("UPDATE countries SET status = ? WHERE id = ?");
        $stmt->execute([$status, $country_id]);
        redirect('security.php', 'Country security updated.');
    }
}

include __DIR__ . '/../templates/admin_header.php';
?>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
    <!-- Brute Force Settings -->
    <div class="bg-white p-6 rounded-lg shadow-sm">
        <h2 class="text-xl font-bold mb-6 text-gray-800 border-b pb-2"><i class="fas fa-lock mr-2 text-red-500"></i> Brute Force Protection</h2>
        <form method="POST">
            <div class="mb-4">
                <label class="block text-gray-700 font-bold mb-2">Protection Period (Minutes)</label>
                <input type="number" name="brute_force_period" value="<?php echo h($settings['brute_force_period'] ?? 15); ?>" class="w-full p-2 border rounded">
                <p class="text-xs text-gray-500 mt-1">Duration to track failed login attempts.</p>
            </div>
            <div class="mb-4">
                <label class="block text-gray-700 font-bold mb-2">Max Failures per Account</label>
                <input type="number" name="max_failures_account" value="<?php echo h($settings['max_failures_account'] ?? 5); ?>" class="w-full p-2 border rounded">
                <p class="text-xs text-gray-500 mt-1">Suspends account after these many failed attempts.</p>
            </div>
            <div class="mb-4">
                <label class="block text-gray-700 font-bold mb-2">Max Failures per IP</label>
                <input type="number" name="max_failures_ip" value="<?php echo h($settings['max_failures_ip'] ?? 10); ?>" class="w-full p-2 border rounded">
                <p class="text-xs text-gray-500 mt-1">Blacklists IP after these many failed attempts.</p>
            </div>
            <div class="mb-6">
                <label class="block text-gray-700 font-bold mb-2">IP Block Duration</label>
                <select name="ip_block_duration" class="w-full p-2 border rounded">
                    <option value="one_day" <?php echo ($settings['ip_block_duration'] ?? '') == 'one_day' ? 'selected' : ''; ?>>One Day</option>
                    <option value="one_week" <?php echo ($settings['ip_block_duration'] ?? '') == 'one_week' ? 'selected' : ''; ?>>One Week</option>
                    <option value="one_month" <?php echo ($settings['ip_block_duration'] ?? '') == 'one_month' ? 'selected' : ''; ?>>One Month</option>
                </select>
            </div>
            <button type="submit" name="save_security" class="bg-primary-600 text-white px-6 py-2 rounded font-bold hover:bg-primary-700 transition w-full">Save Protection Settings</button>
        </form>
    </div>

    <!-- IP Management -->
    <div class="bg-white p-6 rounded-lg shadow-sm">
        <h2 class="text-xl font-bold mb-6 text-gray-800 border-b pb-2"><i class="fas fa-network-wired mr-2 text-blue-500"></i> IP Blacklist / Whitelist</h2>

        <form method="POST" class="mb-6 flex gap-2">
            <input type="text" name="ip" placeholder="Enter IP Address" class="flex-1 p-2 border rounded" required>
            <button type="submit" name="blacklist_ip" class="bg-red-600 text-white px-4 py-2 rounded font-bold hover:bg-red-700 transition">Blacklist</button>
            <button type="submit" name="whitelist_ip" class="bg-primary-600 text-white px-4 py-2 rounded font-bold hover:bg-primary-700 transition">Whitelist</button>
        </form>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="bg-gray-100 text-gray-600 font-bold uppercase text-xs">
                    <tr>
                        <th class="p-3">IP Address</th>
                        <th class="p-3">Status</th>
                        <th class="p-3">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <?php
                    $ips = $pdo->query("SELECT * FROM ip_security ORDER BY created_at DESC LIMIT 10")->fetchAll();
                    foreach ($ips as $ip_row):
                    ?>
                    <tr>
                        <td class="p-3 font-mono"><?php echo h($ip_row['ip_address']); ?></td>
                        <td class="p-3">
                            <?php if ($ip_row['status'] == 'whitelisted'): ?>
                                <span class="text-primary-600 font-bold">Whitelisted <i class="fas fa-crown"></i></span>
                            <?php else: ?>
                                <span class="text-red-600 font-bold">Blacklisted</span>
                            <?php endif; ?>
                        </td>
                        <td class="p-3">
                            <form method="POST" class="inline">
                                <input type="hidden" name="ip" value="<?php echo h($ip_row['ip_address']); ?>">
                                <button type="submit" name="delete_ip" class="text-gray-400 hover:text-red-600"><i class="fas fa-trash"></i></button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Country Filtering -->
<div class="bg-white p-6 rounded-lg shadow-sm mt-8">
    <h2 class="text-xl font-bold mb-6 text-gray-800 border-b pb-2"><i class="fas fa-globe mr-2 text-purple-500"></i> Country Access Control</h2>
    <div class="mb-4">
        <input type="text" id="countrySearch" placeholder="Search Country..." class="w-full p-2 border rounded" onkeyup="filterCountries()">
    </div>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4" id="countryList">
        <?php
        $countries = $pdo->query("SELECT * FROM countries ORDER BY name ASC")->fetchAll();
        foreach ($countries as $country):
        ?>
        <div class="country-item border p-3 rounded flex justify-between items-center" data-name="<?php echo strtolower(h($country['name'])); ?>">
            <span class="font-bold text-sm"><?php echo h($country['name']); ?></span>
            <form method="POST" class="flex gap-1">
                <input type="hidden" name="country_id" value="<?php echo $country['id']; ?>">
                <select name="status" onchange="this.form.submit()" class="text-xs p-1 border rounded <?php
                    echo $country['status'] == 'blacklisted' ? 'bg-red-50 text-red-700' : ($country['status'] == 'whitelisted' ? 'bg-primary-50 text-primary-700' : '');
                ?>">
                    <option name="country_action" value="not_specified" <?php echo $country['status'] == 'not_specified' ? 'selected' : ''; ?>>Not Specified</option>
                    <option name="country_action" value="whitelisted" <?php echo $country['status'] == 'whitelisted' ? 'selected' : ''; ?>>Whitelisted</option>
                    <option name="country_action" value="blacklisted" <?php echo $country['status'] == 'blacklisted' ? 'selected' : ''; ?>>Blacklisted</option>
                </select>
                <input type="hidden" name="country_action" value="1">
            </form>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<script>
function filterCountries() {
    let input = document.getElementById('countrySearch').value.toLowerCase();
    let items = document.getElementsByClassName('country-item');
    for (let i = 0; i < items.length; i++) {
        if (items[i].getAttribute('data-name').includes(input)) {
            items[i].style.display = "";
        } else {
            items[i].style.display = "none";
        }
    }
}
</script>

<?php include __DIR__ . '/../templates/admin_footer.php'; ?>
