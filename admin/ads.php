<?php
require_once __DIR__ . '/../inc/auth.php';
require_once __DIR__ . '/../inc/functions.php';
require_once __DIR__ . '/../inc/security.php';
require_admin();

if (isset($_GET['action']) && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $action = $_GET['action'];

    if ($action == 'approve') {
        $stmt = $pdo->prepare("UPDATE ads SET status = 'active' WHERE id = ?");
        $stmt->execute([$id]);
        redirect('ads.php', 'Ad approved successfully.');
    } elseif ($action == 'decline') {
        $reason = $_POST['reason'] ?? 'Ad does not meet guidelines.';
        $stmt = $pdo->prepare("UPDATE ads SET status = 'declined', decline_reason = ? WHERE id = ?");
        $stmt->execute([$reason, $id]);
        redirect('ads.php', 'Ad declined.');
    } elseif ($action == 'delete') {
        $stmt = $pdo->prepare("DELETE FROM ads WHERE id = ?");
        $stmt->execute([$id]);
        redirect('ads.php', 'Ad deleted.');
    }
}

include __DIR__ . '/../templates/admin_header.php';
?>

<div class="bg-white p-6 rounded-lg shadow-sm">
    <h2 class="text-2xl font-bold mb-6 text-gray-800">Content Moderation Queue</h2>

    <div class="overflow-x-auto">
        <table class="w-full text-left text-sm">
            <thead class="bg-gray-100 text-gray-600 font-bold uppercase text-xs">
                <tr>
                    <th class="p-4">Title</th>
                    <th class="p-4">User</th>
                    <th class="p-4">Category</th>
                    <th class="p-4">Location</th>
                    <th class="p-4">Price</th>
                    <th class="p-4">Status</th>
                    <th class="p-4">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                <?php
                $stmt = $pdo->query("SELECT a.*, u.full_name as user_name, c.name as cat_name, s.name as state_name
                                     FROM ads a
                                     JOIN users u ON a.user_id = u.id
                                     JOIN categories c ON a.cat_id = c.id
                                     JOIN states s ON a.state_id = s.id
                                     ORDER BY a.created_at DESC");
                while ($ad = $stmt->fetch()):
                ?>
                <tr>
                    <td class="p-4 font-bold text-blue-600 hover:underline"><a href="../ad.php?id=<?php echo $ad['id']; ?>" target="_blank"><?php echo h($ad['title']); ?></a></td>
                    <td class="p-4"><?php echo h($ad['user_name']); ?></td>
                    <td class="p-4"><?php echo h($ad['cat_name']); ?></td>
                    <td class="p-4"><?php echo h($ad['state_name']); ?></td>
                    <td class="p-4">₦<?php echo number_format($ad['price'], 2); ?></td>
                    <td class="p-4">
                        <span class="px-2 py-1 rounded text-xs font-bold uppercase <?php
                            echo $ad['status'] == 'active' ? 'bg-primary-100 text-primary-700' : ($ad['status'] == 'pending' ? 'bg-yellow-100 text-yellow-700' : 'bg-red-100 text-red-700');
                        ?>">
                            <?php echo $ad['status']; ?>
                        </span>
                    </td>
                    <td class="p-4 space-x-2">
                        <?php if ($ad['status'] == 'pending'): ?>
                            <a href="ads.php?action=approve&id=<?php echo $ad['id']; ?>" class="bg-primary-500 text-white px-2 py-1 rounded text-xs hover:bg-primary-600 transition">Approve</a>
                            <button onclick="openDeclineModal(<?php echo $ad['id']; ?>)" class="bg-yellow-500 text-white px-2 py-1 rounded text-xs hover:bg-yellow-600 transition">Decline</button>
                        <?php endif; ?>
                        <a href="ads.php?action=delete&id=<?php echo $ad['id']; ?>" class="text-red-500 hover:text-red-700 transition" onclick="return confirm('Permanently delete this ad?')"><i class="fas fa-trash"></i></a>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Decline Modal -->
<div id="declineModal" class="fixed inset-0 bg-black/50 hidden items-center justify-center z-50 p-4">
    <div class="bg-white rounded-xl shadow-2xl w-full max-w-md p-8">
        <h3 class="text-xl font-bold text-gray-800 mb-6">Decline Ad Rejection Reason</h3>
        <form method="POST" id="declineForm">
            <input type="hidden" name="ad_id" id="modalAdId">
            <textarea name="reason" rows="5" class="w-full p-4 border rounded-xl focus:border-red-500 outline-none mb-6" placeholder="Tell the seller why their ad was rejected..." required></textarea>
            <div class="flex gap-4">
                <button type="button" onclick="closeDeclineModal()" class="flex-1 bg-gray-100 text-gray-600 py-3 rounded-xl font-bold hover:bg-gray-200 transition">Cancel</button>
                <button type="submit" name="decline_submit" class="flex-1 bg-red-600 text-white py-3 rounded-xl font-bold hover:bg-red-700 transition shadow-lg uppercase">Confirm Rejection</button>
            </div>
        </form>
    </div>
</div>

<script>
function openDeclineModal(adId) {
    document.getElementById('modalAdId').value = adId;
    document.getElementById('declineForm').action = 'ads.php?action=decline&id=' + adId;
    document.getElementById('declineModal').classList.remove('hidden');
    document.getElementById('declineModal').classList.add('flex');
}

function closeDeclineModal() {
    document.getElementById('declineModal').classList.add('hidden');
    document.getElementById('declineModal').classList.remove('flex');
}
</script>

<?php include __DIR__ . '/../templates/admin_footer.php'; ?>
