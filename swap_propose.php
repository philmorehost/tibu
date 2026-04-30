<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/inc/functions.php';
require_once __DIR__ . '/inc/user_auth.php';
require_user();

$ad_id = (int)$_GET['ad_id'];
$user_id = $_SESSION['user_id'];

// Get requested ad
$stmt = $pdo->prepare("SELECT a.*, u.full_name FROM ads a JOIN users u ON a.user_id = u.id WHERE a.id = ? AND a.status = 'active'");
$stmt->execute([$ad_id]);
$ad = $stmt->fetch();

if (!$ad || $ad['user_id'] == $user_id) {
    redirect('index.php', 'Invalid ad selection.');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $offered_ad_id = (int)$_POST['offered_ad_id'];
    $cash_topup = (float)$_POST['cash_topup'];
    $message = $_POST['message'];

    // Verify offered ad ownership
    $stmt = $pdo->prepare("SELECT id FROM ads WHERE id = ? AND user_id = ?");
    $stmt->execute([$offered_ad_id, $user_id]);
    if ($stmt->fetch()) {
        $stmt = $pdo->prepare("INSERT INTO swap_proposals (ad_id, offered_ad_id, sender_id, receiver_id, cash_topup, message) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$ad_id, $offered_ad_id, $user_id, $ad['user_id'], $cash_topup, $message]);

        redirect('my_swaps.php', 'Swap proposal sent successfully!');
    } else {
        $error = "Please select one of your active ads to offer.";
    }
}

// Get user's active ads to offer
$stmt = $pdo->prepare("SELECT id, title FROM ads WHERE user_id = ? AND status = 'active'");
$stmt->execute([$user_id]);
$my_ads = $stmt->fetchAll();

include __DIR__ . '/templates/header.php';
?>

<div class="container mx-auto px-4 py-10 flex justify-center">
    <div class="bg-white p-8 rounded-3xl shadow-xl w-full max-w-xl border border-blue-100">
        <h1 class="text-2xl font-black mb-6 text-blue-800 uppercase tracking-tighter italic">Propose a <span class="text-primary-600">Swap</span></h1>

        <div class="bg-blue-50 p-4 rounded-2xl mb-8 flex items-center gap-4">
            <div class="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center text-blue-600">
                <i class="fas fa-exchange-alt"></i>
            </div>
            <div>
                <p class="text-[10px] text-blue-400 font-bold uppercase tracking-widest">You are requesting</p>
                <p class="text-sm font-black text-blue-900"><?php echo h($ad['title']); ?></p>
            </div>
        </div>

        <?php if (isset($error)): ?>
            <div class="bg-red-100 text-red-700 p-4 rounded-xl mb-6 font-bold text-sm"><?php echo h($error); ?></div>
        <?php endif; ?>

        <?php if (empty($my_ads)): ?>
            <div class="text-center py-10">
                <p class="text-gray-500 font-bold mb-6">You need to have an active ad to propose a swap.</p>
                <a href="post-ad.php" class="bg-primary-600 text-white px-8 py-3 rounded-xl font-bold hover:bg-primary-700 transition uppercase shadow-lg">Post an Ad Now</a>
            </div>
        <?php else: ?>
            <form method="POST" class="space-y-6">
                <div>
                    <label class="block text-gray-700 font-black mb-2 text-xs uppercase tracking-widest">Select Your Item to Offer</label>
                    <select name="offered_ad_id" class="w-full p-4 bg-gray-50 border-2 border-transparent focus:border-blue-500 rounded-2xl outline-none font-bold text-gray-700 transition" required>
                        <option value="">-- Select Ad --</option>
                        <?php foreach ($my_ads as $my_ad): ?>
                            <option value="<?php echo $my_ad['id']; ?>"><?php echo h($my_ad['title']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label class="block text-gray-700 font-black mb-2 text-xs uppercase tracking-widest">Cash Top-up (Optional)</label>
                    <div class="relative">
                        <span class="absolute left-4 top-1/2 -translate-y-1/2 font-black text-gray-400">₦</span>
                        <input type="number" name="cash_topup" step="0.01" class="w-full p-4 pl-10 bg-gray-50 border-2 border-transparent focus:border-blue-500 rounded-2xl outline-none font-bold text-gray-700 transition" placeholder="0.00">
                    </div>
                    <p class="text-[9px] text-gray-400 mt-2 font-bold uppercase italic">Add cash if your item value is lower than theirs</p>
                </div>

                <div>
                    <label class="block text-gray-700 font-black mb-2 text-xs uppercase tracking-widest">Message to Seller</label>
                    <textarea name="message" rows="4" class="w-full p-4 bg-gray-50 border-2 border-transparent focus:border-blue-500 rounded-2xl outline-none font-bold text-gray-700 transition" placeholder="Explain why this is a fair trade..."></textarea>
                </div>

                <div class="pt-4">
                    <button type="submit" class="w-full bg-blue-600 text-white py-4 rounded-2xl font-black hover:bg-blue-700 transition shadow-2xl text-lg uppercase tracking-tighter">Send Swap Proposal</button>
                    <p class="text-center text-[10px] text-gray-400 font-bold uppercase tracking-widest mt-6 italic">Secure meetup is recommended for all swaps</p>
                </div>
            </form>
        <?php endif; ?>
    </div>
</div>

<?php include __DIR__ . '/templates/footer.php'; ?>
