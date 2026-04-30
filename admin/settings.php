<?php
require_once __DIR__ . '/../inc/auth.php';
require_once __DIR__ . '/../inc/functions.php';
require_once __DIR__ . '/../inc/security.php';
require_admin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['save_settings'])) {
        // Handle Logo Upload
        if (!empty($_FILES['site_logo']['name'])) {
            $target_dir = __DIR__ . "/../uploads/branding";
            $logo_name = process_image_upload($_FILES['site_logo']['tmp_name'], $target_dir, 400);
            if ($logo_name && $logo_name !== "DUPLICATE") {
                $_POST['s']['site_logo'] = $logo_name;
            }
        }

        $stmt = $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");
        foreach ($_POST['s'] as $key => $value) {
            $stmt->execute([$key, $value]);
        }
        redirect('settings.php', 'Global settings updated successfully.');
    }
}

// Get current settings
$stmt = $pdo->query("SELECT setting_key, setting_value FROM settings");
$settings = [];
while ($row = $stmt->fetch()) {
    $settings[$row['setting_key']] = $row['setting_value'];
}

include __DIR__ . '/../templates/admin_header.php';
?>

<div class="bg-white p-8 rounded-lg shadow-sm">
    <h2 class="text-2xl font-bold mb-8 text-gray-800 border-b pb-4">Global System Settings</h2>

    <form method="POST" enctype="multipart/form-data">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-10">
            <!-- General Settings -->
            <div>
                <h3 class="font-bold text-lg mb-4 text-primary-700">General Information</h3>
                <div class="space-y-4">
                    <div>
                        <label class="block text-gray-700 font-bold mb-2">Site Name</label>
                        <input type="text" name="s[site_name]" value="<?php echo h($settings['site_name'] ?? ''); ?>" class="w-full p-2 border rounded">
                    </div>

                    <div class="p-4 bg-gray-50 rounded-xl border border-gray-100">
                        <label class="block text-gray-700 font-bold mb-2 text-sm">Site Logo</label>
                        <?php if (!empty($settings['site_logo'])): ?>
                            <div class="mb-3">
                                <img src="/uploads/branding/<?php echo h($settings['site_logo']); ?>" class="h-24 w-auto object-contain bg-white p-2 border rounded-lg">
                            </div>
                        <?php endif; ?>
                        <input type="file" name="site_logo" class="text-xs">
                        <p class="text-[10px] text-gray-400 mt-2">Recommended: PNG or JPG with transparent background. Max 400px wide.</p>
                    </div>
                </div>

                <h3 class="font-bold text-lg mt-8 mb-4 text-primary-700">SEO & Meta Configuration</h3>
                <div class="space-y-4">
                    <div>
                        <label class="block text-gray-700 font-bold mb-2">Default Meta Description</label>
                        <textarea name="s[meta_description]" rows="3" class="w-full p-2 border rounded text-sm"><?php echo h($settings['meta_description'] ?? ''); ?></textarea>
                    </div>
                    <div>
                        <label class="block text-gray-700 font-bold mb-2">Default Meta Keywords</label>
                        <input type="text" name="s[meta_keywords]" value="<?php echo h($settings['meta_keywords'] ?? ''); ?>" class="w-full p-2 border rounded text-sm" placeholder="keyword1, keyword2, ...">
                    </div>
                </div>

                <h3 class="font-bold text-lg mt-8 mb-4 text-primary-700">Email (SMTP) Configuration</h3>
                <div class="space-y-4">
                    <div>
                        <label class="block text-gray-700 font-bold mb-2">SMTP Host</label>
                        <input type="text" name="s[smtp_host]" value="<?php echo h($settings['smtp_host'] ?? ''); ?>" class="w-full p-2 border rounded">
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-gray-700 font-bold mb-2">SMTP Port</label>
                            <input type="text" name="s[smtp_port]" value="<?php echo h($settings['smtp_port'] ?? ''); ?>" class="w-full p-2 border rounded">
                        </div>
                        <div>
                            <label class="block text-gray-700 font-bold mb-2">Encryption</label>
                            <select name="s[smtp_encryption]" class="w-full p-2 border rounded">
                                <option value="tls" <?php echo ($settings['smtp_encryption'] ?? '') == 'tls' ? 'selected' : ''; ?>>TLS</option>
                                <option value="ssl" <?php echo ($settings['smtp_encryption'] ?? '') == 'ssl' ? 'selected' : ''; ?>>SSL</option>
                            </select>
                        </div>
                    </div>
                    <div>
                        <label class="block text-gray-700 font-bold mb-2">SMTP Username</label>
                        <input type="text" name="s[smtp_user]" value="<?php echo h($settings['smtp_user'] ?? ''); ?>" class="w-full p-2 border rounded">
                    </div>
                    <div>
                        <label class="block text-gray-700 font-bold mb-2">SMTP Password</label>
                        <input type="password" name="s[smtp_pass]" value="<?php echo h($settings['smtp_pass'] ?? ''); ?>" class="w-full p-2 border rounded">
                    </div>
                </div>
            </div>

            <!-- Payment Settings -->
            <div>
                <h3 class="font-bold text-lg mb-4 text-primary-700">Ad Boosting & Payments</h3>
                <div class="space-y-4">
                    <div class="p-4 bg-primary-50 rounded border border-primary-200 mb-4">
                        <label class="block text-sm font-bold text-primary-800 mb-2">Boost Ad Price (₦)</label>
                        <input type="number" name="s[boost_price]" value="<?php echo h($settings['boost_price'] ?? '2000'); ?>" class="w-full p-2 border rounded font-bold text-primary-700" step="0.01">
                        <p class="text-[10px] text-primary-600 mt-1 uppercase font-bold tracking-widest">Amount users pay to feature their ads</p>
                    </div>

                    <div class="p-4 bg-purple-50 rounded border border-purple-200 mb-4">
                        <h4 class="font-bold text-purple-800 text-sm mb-3 uppercase tracking-widest">Ad Package Durations</h4>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-bold text-purple-600 mb-1">Free Ad (Days)</label>
                                <input type="number" name="s[free_ad_duration]" value="<?php echo h($settings['free_ad_duration'] ?? '15'); ?>" class="w-full p-2 border rounded text-sm">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-purple-600 mb-1">Premium Ad (Days)</label>
                                <input type="number" name="s[premium_ad_duration]" value="<?php echo h($settings['premium_ad_duration'] ?? '30'); ?>" class="w-full p-2 border rounded text-sm">
                            </div>
                        </div>
                    </div>

                    <div class="p-4 bg-blue-50 rounded border border-blue-200 mb-4">
                        <h4 class="font-bold text-blue-800 text-sm mb-3 uppercase tracking-widest">Bank Transfer Details</h4>
                        <div class="space-y-3">
                            <div>
                                <label class="block text-xs font-bold text-blue-600 mb-1">Bank Name</label>
                                <input type="text" name="s[bank_name]" value="<?php echo h($settings['bank_name'] ?? ''); ?>" class="w-full p-2 border rounded text-sm" placeholder="e.g. Access Bank">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-blue-600 mb-1">Account Number</label>
                                <input type="text" name="s[account_number]" value="<?php echo h($settings['account_number'] ?? ''); ?>" class="w-full p-2 border rounded text-sm" placeholder="0123456789">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-blue-600 mb-1">Account Name</label>
                                <input type="text" name="s[account_name]" value="<?php echo h($settings['account_name'] ?? ''); ?>" class="w-full p-2 border rounded text-sm" placeholder="<?php echo h($settings['site_name'] ?? 'Classifieds'); ?> Ventures">
                            </div>
                        </div>
                    </div>

                    <h4 class="font-bold text-sm text-gray-600 mb-2 uppercase tracking-widest">API Gateway Keys</h4>
                    <div class="p-4 bg-yellow-50 rounded border border-yellow-200 mb-4">
                        <p class="text-sm text-yellow-800 font-bold">Paystack Configuration</p>
                        <div class="mt-2 space-y-3">
                            <div>
                                <label class="block text-xs font-bold text-gray-600">Public Key</label>
                                <input type="text" name="s[paystack_public_key]" value="<?php echo h($settings['paystack_public_key'] ?? ''); ?>" class="w-full p-2 border rounded text-sm">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-gray-600">Secret Key</label>
                                <input type="password" name="s[paystack_secret_key]" value="<?php echo h($settings['paystack_secret_key'] ?? ''); ?>" class="w-full p-2 border rounded text-sm">
                            </div>
                        </div>
                    </div>

                    <div class="p-4 bg-blue-50 rounded border border-blue-200 mb-4">
                        <p class="text-sm text-blue-800 font-bold">Flutterwave Configuration</p>
                        <div class="mt-2 space-y-3">
                            <div>
                                <label class="block text-xs font-bold text-gray-600">Public Key</label>
                                <input type="text" name="s[flutterwave_public_key]" value="<?php echo h($settings['flutterwave_public_key'] ?? ''); ?>" class="w-full p-2 border rounded text-sm">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-gray-600">Secret Key</label>
                                <input type="password" name="s[flutterwave_secret_key]" value="<?php echo h($settings['flutterwave_secret_key'] ?? ''); ?>" class="w-full p-2 border rounded text-sm">
                            </div>
                        </div>
                    </div>
                </div>

                <h3 class="font-bold text-lg mt-8 mb-4 text-primary-700">Social Login (OAuth)</h3>
                <div class="space-y-6">
                    <div class="p-4 bg-red-50 rounded border border-red-200">
                        <p class="text-sm text-red-800 font-bold mb-3 flex items-center">
                            <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM9 9V5a1 1 0 112 0v4a1 1 0 11-2 0zm1 4a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd"></path></svg>
                            Google Login Configuration
                        </p>
                        <div class="space-y-3">
                            <div>
                                <label class="block text-xs font-bold text-gray-600">Client ID</label>
                                <input type="text" name="s[google_client_id]" value="<?php echo h($settings['google_client_id'] ?? ''); ?>" class="w-full p-2 border rounded text-sm">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-gray-600">Client Secret</label>
                                <input type="password" name="s[google_client_secret]" value="<?php echo h($settings['google_client_secret'] ?? ''); ?>" class="w-full p-2 border rounded text-sm">
                            </div>
                            <div class="mt-2 p-2 bg-white rounded border text-[10px] text-gray-500 font-mono">
                                <span class="font-bold text-red-600">Authorized Redirect URI:</span><br>
                                <?php echo (isset($_SERVER['HTTPS']) ? "https" : "http") . "://$_SERVER[HTTP_HOST]"; ?>/social.php?provider=google
                            </div>
                        </div>
                    </div>

                    <div class="p-4 bg-blue-50 rounded border border-blue-200">
                        <p class="text-sm text-blue-800 font-bold mb-3 flex items-center">
                            <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20"><path d="M2 10a8 8 0 018-8v8h8a8 8 0 11-16 0z"></path><path d="M12 2.252A8.001 8.001 0 0117.748 8H12V2.252z"></path></svg>
                            Facebook Login Configuration
                        </p>
                        <div class="space-y-3">
                            <div>
                                <label class="block text-xs font-bold text-gray-600">App ID</label>
                                <input type="text" name="s[facebook_app_id]" value="<?php echo h($settings['facebook_app_id'] ?? ''); ?>" class="w-full p-2 border rounded text-sm">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-gray-600">App Secret</label>
                                <input type="password" name="s[facebook_app_secret]" value="<?php echo h($settings['facebook_app_secret'] ?? ''); ?>" class="w-full p-2 border rounded text-sm">
                            </div>
                            <div class="mt-2 p-2 bg-white rounded border text-[10px] text-gray-500 font-mono">
                                <span class="font-bold text-blue-600">Valid OAuth Redirect URI:</span><br>
                                <?php echo (isset($_SERVER['HTTPS']) ? "https" : "http") . "://$_SERVER[HTTP_HOST]"; ?>/social.php?provider=facebook
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="mt-10 bg-gray-900 text-primary-400 p-6 rounded-xl font-mono text-xs overflow-x-auto">
            <p class="mb-2 font-bold uppercase text-gray-400">// Automation Cron Job Path</p>
            <p>php <?php echo realpath(__DIR__ . '/../inc/cron.php'); ?></p>
            <p class="mt-4 text-gray-500 italic">Set this to run every hour via your cPanel Cron Jobs</p>
        </div>

        <div class="mt-10 border-t pt-6 flex justify-end">
            <button type="submit" name="save_settings" class="bg-primary-600 text-white px-10 py-3 rounded-lg font-bold hover:bg-primary-700 transition shadow-lg">Save All Configurations</button>
        </div>
    </form>
</div>

<?php include __DIR__ . '/../templates/admin_footer.php'; ?>
