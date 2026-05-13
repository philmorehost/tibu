<?php
require_once __DIR__ . '/config/config.php';
if (session_status() === PHP_SESSION_NONE) session_start();

require_once __DIR__ . '/inc/functions.php';
require_once __DIR__ . '/inc/user_auth.php';
require_user();

if (isset($_GET['ad_id'])) {
    $ad_id = (int)$_GET['ad_id'];
    $user_id = $_SESSION['user_id'];

    // Check if ad belongs to user
    $stmt = $pdo->prepare("SELECT title FROM ads WHERE id = ? AND user_id = ?");
    $stmt->execute([$ad_id, $user_id]);
    $ad = $stmt->fetch();

    if (!$ad) {
        redirect('profile.php', 'Ad not found or not yours.');
    }
} else {
    redirect('profile.php');
}

// Get settings
$stmt = $pdo->query("SELECT setting_key, setting_value FROM settings WHERE setting_key IN ('paystack_public_key', 'flutterwave_public_key', 'bank_name', 'account_number', 'account_name', 'boost_price')");
$settings = [];
while ($row = $stmt->fetch()) {
    $settings[$row['setting_key']] = $row['setting_value'];
}

$tier = $_GET['tier'] ?? 'premium';
if (!in_array($tier, ['premium', 'vip', 'diamond'])) $tier = 'premium';

$packages = $pdo->query("SELECT * FROM packages ORDER BY price ASC")->fetchAll();
$current_pkg = null;
foreach ($packages as $p) {
    if ($p['tier'] === $tier) {
        $current_pkg = $p;
        break;
    }
}
$boost_price = (float)($current_pkg['price'] ?? 2000);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['bank_transfer'])) {
    $filename = process_image_upload($_FILES['proof']['tmp_name'], __DIR__ . '/uploads/proofs', 800, 0, 0, false);
    if ($filename) {
        $stmt = $pdo->prepare("INSERT INTO payments (user_id, ad_id, amount, method, reference, status, proof_image, ad_tier) VALUES (?, ?, ?, 'bank_transfer', ?, 'pending', ?, ?)");
        $reference = 'BT-'.time().'-'.rand(100, 999);
        $stmt->execute([$user_id, $ad_id, $boost_price, $reference, $filename, $tier]);
        redirect('profile.php', 'Payment proof submitted! Your ad will be boosted after manual verification.');
    }
}

include __DIR__ . '/templates/header.php';
?>

<div class="container mx-auto px-4 py-10 flex justify-center">
    <div class="bg-white p-8 rounded-xl shadow-lg w-full max-w-4xl">
        <h1 class="text-2xl font-bold mb-8 text-primary-600 border-b pb-4"><i class="fas fa-rocket mr-2"></i> Select Package for "<?php echo h($ad['title']); ?>"</h1>

        <!-- Tier Selection -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-12">
            <?php
            $colors = ['premium' => 'blue', 'vip' => 'yellow', 'diamond' => 'purple'];
            foreach ($packages as $p):
                if ($p['tier'] === 'free') continue;
                $color = $colors[$p['tier']] ?? 'primary';
                $price_key = $p['tier'] . '_ad_price';
                $duration_key = $p['tier'] . '_ad_duration';
                $p_price = isset($settings[$price_key]) ? (float)$settings[$price_key] : (float)$p['price'];
                $p_duration = isset($settings[$duration_key]) ? (int)$settings[$duration_key] : (int)$p['duration_days'];

                if ($p['tier'] === 'premium') {
                    $p_price = isset($settings['premium_ad_price']) ? (float)$settings['premium_ad_price'] : (isset($settings['boost_price']) ? (float)$settings['boost_price'] : $p_price);
                    $p_duration = isset($settings['premium_ad_duration']) ? (int)$settings['premium_ad_duration'] : $p_duration;
                }
            ?>
                <div class="flex flex-col border-2 rounded-2xl overflow-hidden transition <?php echo $tier === $p['tier'] ? "border-{$color}-600 shadow-xl" : "border-gray-100 opacity-60 grayscale hover:opacity-100 hover:grayscale-0"; ?>">
                    <div class="p-4 bg-<?php echo $color; ?>-50 border-b flex justify-between items-center">
                        <span class="text-sm font-black uppercase text-<?php echo $color; ?>-900"><?php echo h($p['name']); ?></span>
                        <i class="fas fa-check-circle text-<?php echo $color; ?>-600 <?php echo $tier === $p['tier'] ? '' : 'hidden'; ?>"></i>
                    </div>

                    <div class="p-6 flex-1 bg-white">
                        <div class="mb-6">
                            <p class="text-3xl font-black text-gray-800">₦<?php echo number_format($p_price); ?></p>
                            <p class="text-xs font-bold text-gray-400">per <?php echo $p_duration; ?> days</p>
                        </div>

                        <ul class="space-y-3 text-xs font-bold text-gray-600">
                            <li class="flex justify-between"><span>Power-up:</span> <span class="text-<?php echo $color; ?>-600"><?php echo $p['power_up']; ?></span></li>
                            <li class="flex justify-between"><span>Promo ads:</span> <span class="text-<?php echo $color; ?>-600"><?php echo $p['promo_ads_count']; ?> TOP+</span></li>
                            <li class="flex justify-between"><span>Auto-renew:</span> <span class="text-<?php echo $color; ?>-600"><?php echo $p['auto_renew_hours'] ? 'Every ' . $p['auto_renew_hours'] . 'h' : 'Manual'; ?></span></li>
                            <li class="flex justify-between"><span>Cashback:</span> <span class="text-green-600">₦<?php echo number_format($p['cashback']); ?></span></li>
                            <?php if ($p['has_social_links']): ?> <li class="flex items-center gap-2"><i class="fas fa-check text-green-500"></i> Website/Social links</li> <?php endif; ?>
                            <?php if ($p['has_personal_manager']): ?> <li class="flex items-center gap-2"><i class="fas fa-check text-green-500"></i> Personal Manager</li> <?php endif; ?>
                            <?php if ($p['has_insights_report']): ?> <li class="flex items-center gap-2"><i class="fas fa-check text-green-500"></i> Insights Report</li> <?php endif; ?>
                            <?php if ($p['has_pro_sales']): ?> <li class="flex items-center gap-2"><i class="fas fa-check text-green-500"></i> Access to Pro Sales</li> <?php endif; ?>
                        </ul>
                    </div>

                    <a href="?ad_id=<?php echo $ad_id; ?>&tier=<?php echo $p['tier']; ?>" class="block p-4 text-center font-black uppercase text-xs <?php echo $tier === $p['tier'] ? "bg-{$color}-600 text-white" : "bg-gray-50 text-gray-400 hover:bg-gray-100"; ?>">
                        <?php echo $tier === $p['tier'] ? 'Selected' : 'Select Plan'; ?>
                    </a>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-10">

        <div class="space-y-6">
            <!-- Online Payment -->
            <div class="bg-gray-50 p-6 rounded-xl border-2 border-primary-100">
                <h2 class="font-bold text-lg mb-4 text-gray-800"><i class="fas fa-credit-card mr-2 text-primary-600"></i> Pay Online</h2>
                <div class="grid grid-cols-2 gap-4">
                    <button onclick="payWithPaystack()" class="bg-yellow-500 text-white py-3 rounded-lg font-bold hover:bg-yellow-600 transition shadow-md uppercase text-sm">Paystack</button>
                    <button onclick="payWithFlutterwave()" class="bg-blue-600 text-white py-3 rounded-lg font-bold hover:bg-blue-700 transition shadow-md uppercase text-sm">Flutterwave</button>
                </div>
                <p class="text-center text-xs text-gray-400 mt-4 font-bold uppercase tracking-widest">Instant Activation</p>
            </div>

            <!-- Manual Bank Transfer -->
            <div class="bg-gray-50 p-6 rounded-xl border-2 border-blue-100">
                <h2 class="font-bold text-lg mb-4 text-gray-800"><i class="fas fa-university mr-2 text-blue-600"></i> Bank Transfer</h2>
                <div class="bg-white p-4 rounded-lg mb-4 text-sm font-mono text-blue-900 border border-blue-100">
                    <p>Bank: <?php echo h($settings['bank_name'] ?? 'N/A'); ?></p>
                    <p>Account: <?php echo h($settings['account_number'] ?? 'N/A'); ?></p>
                    <p>Name: <?php echo h($settings['account_name'] ?? 'N/A'); ?></p>
                    <p class="mt-2 font-bold">Amount: ₦<?php echo number_format($boost_price, 2); ?></p>
                </div>
                <form method="POST" enctype="multipart/form-data" class="space-y-4">
                    <input type="hidden" name="bank_transfer" value="1">
                    <div>
                        <label class="block text-xs font-bold text-gray-500 mb-2 uppercase">Upload Proof (Screenshot)</label>
                        <input type="file" name="proof" accept="image/*" class="w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100" required>
                    </div>
                    <button type="submit" class="w-full bg-blue-600 text-white py-3 rounded-lg font-bold hover:bg-blue-700 transition shadow-md uppercase text-sm">Submit Proof</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Payment Gateway Scripts -->
<script src="https://js.paystack.co/v1/inline.js"></script>
<script src="https://checkout.flutterwave.com/v3.js"></script>
<script>
function payWithPaystack() {
    const handler = PaystackPop.setup({
        key: '<?php echo $settings['paystack_public_key'] ?? ''; ?>',
        email: 'user@example.com',
        amount: <?php echo ($boost_price * 100); ?>, // In kobo
        currency: 'NGN',
        callback: function(response) {
            window.location.href = 'api/payment_verify.php?method=paystack&ref=' + response.reference + '&ad_id=<?php echo $ad_id; ?>&tier=<?php echo $tier; ?>';
        }
    });
    handler.openIframe();
}

function payWithFlutterwave() {
    FlutterwaveCheckout({
        public_key: '<?php echo $settings['flutterwave_public_key'] ?? ''; ?>',
        tx_ref: 'FLW-' + Date.now(),
        amount: <?php echo $boost_price; ?>,
        currency: 'NGN',
        payment_options: 'card, banktransfer, ussd',
        callback: function (data) {
            window.location.href = 'api/payment_verify.php?method=flutterwave&ref=' + data.transaction_id + '&ad_id=<?php echo $ad_id; ?>&tier=<?php echo $tier; ?>';
        }
    });
}
</script>

<?php include __DIR__ . '/templates/footer.php'; ?>

