<?php
require_once __DIR__ . '/../inc/auth.php';
require_once __DIR__ . '/../inc/functions.php';
require_once __DIR__ . '/../inc/security.php';
require_admin();

include __DIR__ . '/../templates/admin_header.php';

// Fetch stats
$ad_count = $pdo->query("SELECT COUNT(*) FROM ads")->fetchColumn();
$pending_ad_count = $pdo->query("SELECT COUNT(*) FROM ads WHERE status = 'pending'")->fetchColumn();
$user_count = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$total_revenue = $pdo->query("SELECT SUM(amount) FROM payments WHERE status = 'successful'")->fetchColumn() ?: 0;
?>

<!-- Quick Actions Section -->
<div class="mb-10">
    <h3 class="text-[10px] font-black text-gray-400 uppercase tracking-[3px] mb-6">Quick Admin Actions</h3>
    <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
        <a href="ads.php" class="bg-white p-4 rounded-2xl shadow-sm border border-gray-100 flex items-center gap-4 hover:shadow-lg hover:border-yellow-400 transition group">
            <div class="w-10 h-10 rounded-xl bg-yellow-50 flex items-center justify-center text-yellow-600 group-hover:bg-yellow-400 group-hover:text-white transition">
                <i class="fas fa-tasks text-sm"></i>
            </div>
            <div>
                <p class="text-[10px] font-black text-gray-800 uppercase">Ads</p>
                <p class="text-[9px] text-gray-400 font-bold"><?php echo $pending_ad_count; ?> New</p>
            </div>
        </a>
        <a href="users.php" class="bg-white p-4 rounded-2xl shadow-sm border border-gray-100 flex items-center gap-4 hover:shadow-lg hover:border-indigo-400 transition group">
            <div class="w-10 h-10 rounded-xl bg-indigo-50 flex items-center justify-center text-indigo-600 group-hover:bg-indigo-600 group-hover:text-white transition">
                <i class="fas fa-users text-sm"></i>
            </div>
            <div>
                <p class="text-[10px] font-black text-gray-800 uppercase">Users</p>
                <p class="text-[9px] text-gray-400 font-bold">Manager</p>
            </div>
        </a>
        <a href="categories.php" class="bg-white p-4 rounded-2xl shadow-sm border border-gray-100 flex items-center gap-4 hover:shadow-lg hover:border-green-400 transition group">
            <div class="w-10 h-10 rounded-xl bg-green-50 flex items-center justify-center text-green-600 group-hover:bg-green-600 group-hover:text-white transition">
                <i class="fas fa-list-alt text-sm"></i>
            </div>
            <div>
                <p class="text-[10px] font-black text-gray-800 uppercase">Category</p>
                <p class="text-[9px] text-gray-400 font-bold">Structure</p>
            </div>
        </a>
        <a href="payments.php" class="bg-white p-4 rounded-2xl shadow-sm border border-gray-100 flex items-center gap-4 hover:shadow-lg hover:border-blue-400 transition group">
            <div class="w-10 h-10 rounded-xl bg-blue-50 flex items-center justify-center text-blue-600 group-hover:bg-blue-600 group-hover:text-white transition">
                <i class="fas fa-credit-card text-sm"></i>
            </div>
            <div>
                <p class="text-[10px] font-black text-gray-800 uppercase">Revenue</p>
                <p class="text-[9px] text-gray-400 font-bold">Payouts</p>
            </div>
        </a>
        <a href="security.php" class="bg-white p-4 rounded-2xl shadow-sm border border-gray-100 flex items-center gap-4 hover:shadow-lg hover:border-red-400 transition group">
            <div class="w-10 h-10 rounded-xl bg-red-50 flex items-center justify-center text-red-600 group-hover:bg-red-600 group-hover:text-white transition">
                <i class="fas fa-user-shield text-sm"></i>
            </div>
            <div>
                <p class="text-[10px] font-black text-gray-800 uppercase">Security</p>
                <p class="text-[9px] text-gray-400 font-bold">Firewall</p>
            </div>
        </a>
    </div>
</div>

<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
    <div class="bg-white p-6 rounded-lg shadow-sm border-l-4 border-blue-500">
        <h2 class="text-gray-500 font-bold uppercase text-xs mb-1">Total Ads</h2>
        <p class="text-3xl font-bold text-gray-800"><?php echo number_format($ad_count); ?></p>
    </div>
    <div class="bg-white p-6 rounded-lg shadow-sm border-l-4 border-yellow-500">
        <h2 class="text-gray-500 font-bold uppercase text-xs mb-1">Pending Moderation</h2>
        <p class="text-3xl font-bold text-gray-800"><?php echo number_format($pending_ad_count); ?></p>
    </div>
    <div class="bg-white p-6 rounded-lg shadow-sm border-l-4 border-green-500">
        <h2 class="text-gray-500 font-bold uppercase text-xs mb-1">Total Users</h2>
        <p class="text-3xl font-bold text-gray-800"><?php echo number_format($user_count); ?></p>
    </div>
    <div class="bg-white p-6 rounded-lg shadow-sm border-l-4 border-purple-500">
        <h2 class="text-gray-500 font-bold uppercase text-xs mb-1">Total Revenue</h2>
        <p class="text-3xl font-bold text-gray-800">₦<?php echo number_format($total_revenue, 2); ?></p>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
    <!-- Security Monitoring Section -->
    <div class="bg-white rounded-3xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="bg-gray-50/50 p-6 border-b border-gray-100 flex justify-between items-center">
            <h2 class="font-black text-gray-800 uppercase text-[10px] tracking-[2px]"><i class="fas fa-shield-alt mr-2 text-red-500"></i> Security Monitor</h2>
            <a href="security.php" class="text-[10px] font-black text-blue-600 uppercase tracking-widest hover:underline">Full Logs</a>
        </div>
        <div class="p-0 overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-gray-50 text-gray-400 uppercase text-[9px] font-black">
                    <tr>
                        <th class="px-6 py-4">Target IP</th>
                        <th class="px-6 py-4">Status</th>
                        <th class="px-6 py-4 text-right">Time</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    <?php
                    $logs = $pdo->query("SELECT * FROM login_logs ORDER BY attempted_at DESC LIMIT 6")->fetchAll();
                    foreach ($logs as $log):
                        $is_whitelisted = $pdo->prepare("SELECT status FROM ip_security WHERE ip_address = ?");
                        $is_whitelisted->execute([$log['ip_address']]);
                        $ip_status = $is_whitelisted->fetchColumn();
                    ?>
                    <tr class="hover:bg-gray-50/50 transition">
                        <td class="px-6 py-4">
                            <div class="font-bold text-gray-700"><?php echo h($log['ip_address']); ?></div>
                            <div class="text-[9px] text-gray-400 font-bold"><?php echo h($log['username'] ?: 'System'); ?></div>
                        </td>
                        <td class="px-6 py-4">
                            <?php if ($log['is_success']): ?>
                                <span class="text-green-600 font-black uppercase text-[9px]">Success <?php if($ip_status === 'whitelisted') echo '<i class="fas fa-crown text-yellow-500 ml-1"></i>'; ?></span>
                            <?php else: ?>
                                <span class="text-red-500 font-black uppercase text-[9px]">Failed</span>
                            <?php endif; ?>
                        </td>
                        <td class="px-6 py-4 text-right text-gray-400 font-bold">
                            <?php echo date('H:i', strtotime($log['attempted_at'])); ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Recent Ads Posted Section -->
    <div class="bg-white rounded-3xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="bg-gray-50/50 p-6 border-b border-gray-100 flex justify-between items-center">
            <h2 class="font-black text-gray-800 uppercase text-[10px] tracking-[2px]"><i class="fas fa-ad mr-2 text-green-600"></i> Recent Submissions</h2>
            <a href="ads.php" class="text-[10px] font-black text-green-600 uppercase tracking-widest hover:underline">Moderation Queue</a>
        </div>
        <div class="p-0 overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-gray-50 text-gray-400 uppercase text-[9px] font-black">
                    <tr>
                        <th class="px-6 py-4">Item Details</th>
                        <th class="px-6 py-4">Status</th>
                        <th class="px-6 py-4 text-right">Price</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    <?php
                    $recent_ads_admin = $pdo->query("SELECT a.*, c.name as cat_name FROM ads a JOIN categories c ON a.cat_id = c.id ORDER BY a.created_at DESC LIMIT 6")->fetchAll();
                    foreach ($recent_ads_admin as $radmin):
                    ?>
                    <tr class="hover:bg-gray-50/50 transition">
                        <td class="px-6 py-4">
                            <div class="font-bold text-gray-700 line-clamp-1"><?php echo h($radmin['title']); ?></div>
                            <div class="text-[9px] text-gray-400 font-bold uppercase"><?php echo h($radmin['cat_name']); ?></div>
                        </td>
                        <td class="px-6 py-4">
                            <span class="px-2 py-0.5 rounded text-[8px] font-black uppercase <?php
                                echo $radmin['status'] == 'active' ? 'bg-green-100 text-green-600' : ($radmin['status'] == 'pending' ? 'bg-yellow-100 text-yellow-600' : 'bg-red-100 text-red-600');
                            ?>">
                                <?php echo $radmin['status']; ?>
                            </span>
                        </td>
                        <td class="px-6 py-4 text-right font-black text-gray-700">
                            ₦<?php echo number_format($radmin['price']); ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../templates/admin_footer.php'; ?>
