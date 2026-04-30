<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/inc/functions.php';
require_once __DIR__ . '/inc/user_auth.php';
require_user();

$user_id = $_SESSION['user_id'];

// Get Received Proposals
$stmt = $pdo->prepare("SELECT sp.*, a_req.title as req_title, a_off.title as off_title, u_sender.full_name as sender_name
                     FROM swap_proposals sp
                     JOIN ads a_req ON sp.ad_id = a_req.id
                     JOIN ads a_off ON sp.offered_ad_id = a_off.id
                     JOIN users u_sender ON sp.sender_id = u_sender.id
                     WHERE sp.receiver_id = ?
                     ORDER BY sp.created_at DESC");
$stmt->execute([$user_id]);
$received = $stmt->fetchAll();

// Get Sent Proposals
$stmt = $pdo->prepare("SELECT sp.*, a_req.title as req_title, a_off.title as off_title, u_receiver.full_name as receiver_name
                     FROM swap_proposals sp
                     JOIN ads a_req ON sp.ad_id = a_req.id
                     JOIN ads a_off ON sp.offered_ad_id = a_off.id
                     JOIN users u_receiver ON sp.receiver_id = u_receiver.id
                     WHERE sp.sender_id = ?
                     ORDER BY sp.created_at DESC");
$stmt->execute([$user_id]);
$sent = $stmt->fetchAll();

include __DIR__ . '/templates/header.php';
?>

<div class="container mx-auto px-4 py-10">
    <div class="flex flex-col md:flex-row justify-between items-end mb-12 gap-6">
        <div>
            <h1 class="text-4xl font-black text-gray-800 uppercase tracking-tighter italic mb-2">My <span class="text-blue-600">Swap</span> Dashboard</h1>
            <p class="text-gray-400 font-bold text-sm uppercase tracking-widest">Manage your item exchange proposals</p>
        </div>
        <div class="flex gap-2">
             <div class="bg-blue-50 px-6 py-3 rounded-2xl border border-blue-100">
                <p class="text-[9px] text-blue-400 font-black uppercase mb-1">Total Received</p>
                <p class="text-xl font-black text-blue-700"><?php echo count($received); ?></p>
             </div>
             <div class="bg-primary-50 px-6 py-3 rounded-2xl border border-primary-100">
                <p class="text-[9px] text-primary-400 font-black uppercase mb-1">Total Sent</p>
                <p class="text-xl font-black text-primary-700"><?php echo count($sent); ?></p>
             </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-10">
        <!-- Received Section -->
        <div>
            <h3 class="text-xl font-black text-gray-800 mb-6 flex items-center gap-3">
                <span class="w-2 h-6 bg-blue-600 rounded-full"></span>
                PROPOSALS RECEIVED
            </h3>
            <div class="space-y-4">
                <?php foreach ($received as $prop): ?>
                    <div class="bg-white p-6 rounded-3xl shadow-sm border border-gray-100 hover:shadow-md transition group">
                        <div class="flex justify-between items-start mb-4">
                            <div>
                                <p class="text-[10px] text-gray-400 font-bold uppercase tracking-widest mb-1">From <?php echo h($prop['sender_name']); ?></p>
                                <h4 class="font-black text-gray-800">Offered: <span class="text-blue-600"><?php echo h($prop['off_title']); ?></span></h4>
                                <p class="text-[10px] text-gray-400 font-bold uppercase mt-1">For your: <span class="text-gray-600"><?php echo h($prop['req_title']); ?></span></p>
                            </div>
                            <span class="text-[9px] font-black px-3 py-1 rounded-full uppercase <?php
                                echo $prop['status'] == 'pending' ? 'bg-yellow-100 text-yellow-700' : ($prop['status'] == 'accepted' ? 'bg-primary-100 text-primary-700' : 'bg-red-100 text-red-700');
                            ?>"><?php echo $prop['status']; ?></span>
                        </div>

                        <?php if ($prop['cash_topup'] > 0): ?>
                            <div class="bg-primary-50 p-3 rounded-xl mb-4 border border-primary-100 flex items-center justify-between">
                                <span class="text-[10px] font-black text-primary-700 uppercase tracking-widest">Cash Top-up Offered</span>
                                <span class="text-sm font-black text-primary-600">+ ₦<?php echo number_format($prop['cash_topup']); ?></span>
                            </div>
                        <?php endif; ?>

                        <p class="text-xs text-gray-500 italic mb-6 line-clamp-2">"<?php echo h($prop['message']); ?>"</p>

                        <?php if ($prop['status'] == 'pending'): ?>
                            <div class="flex gap-2">
                                <button onclick="handleProposal(<?php echo $prop['id']; ?>, 'accepted')" class="flex-1 bg-primary-600 text-white py-3 rounded-xl text-[10px] font-black uppercase tracking-widest hover:bg-primary-700 transition shadow-lg">Accept Swap</button>
                                <button onclick="handleProposal(<?php echo $prop['id']; ?>, 'declined')" class="flex-1 bg-white text-red-500 border-2 border-red-500 py-3 rounded-xl text-[10px] font-black uppercase tracking-widest hover:bg-red-50 transition">Decline</button>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
                <?php if (empty($received)): ?>
                    <div class="bg-gray-50 p-10 rounded-3xl border-2 border-dashed border-gray-200 text-center">
                        <i class="fas fa-inbox text-gray-200 text-4xl mb-4"></i>
                        <p class="text-gray-400 font-bold uppercase text-xs tracking-widest">No proposals received yet</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Sent Section -->
        <div>
            <h3 class="text-xl font-black text-gray-800 mb-6 flex items-center gap-3">
                <span class="w-2 h-6 bg-primary-600 rounded-full"></span>
                PROPOSALS SENT
            </h3>
            <div class="space-y-4">
                <?php foreach ($sent as $prop): ?>
                    <div class="bg-white p-6 rounded-3xl shadow-sm border border-gray-100 opacity-80 hover:opacity-100 transition">
                        <div class="flex justify-between items-start mb-4">
                            <div>
                                <p class="text-[10px] text-gray-400 font-bold uppercase tracking-widest mb-1">To <?php echo h($prop['receiver_name']); ?></p>
                                <h4 class="font-black text-gray-800 italic">Requested: <span class="text-primary-600"><?php echo h($prop['req_title']); ?></span></h4>
                                <p class="text-[10px] text-gray-400 font-bold uppercase mt-1">Offering: <span class="text-gray-600"><?php echo h($prop['off_title']); ?></span></p>
                            </div>
                             <span class="text-[9px] font-black px-3 py-1 rounded-full uppercase <?php
                                echo $prop['status'] == 'pending' ? 'bg-yellow-100 text-yellow-700' : ($prop['status'] == 'accepted' ? 'bg-primary-100 text-primary-700' : 'bg-red-100 text-red-700');
                            ?>"><?php echo $prop['status']; ?></span>
                        </div>

                         <?php if ($prop['cash_topup'] > 0): ?>
                            <div class="bg-blue-50 p-3 rounded-xl mb-4 border border-blue-100 flex items-center justify-between">
                                <span class="text-[10px] font-black text-blue-700 uppercase tracking-widest">Your Cash Top-up</span>
                                <span class="text-sm font-black text-blue-600">+ ₦<?php echo number_format($prop['cash_topup']); ?></span>
                            </div>
                        <?php endif; ?>

                        <div class="text-[10px] text-gray-400 font-bold uppercase tracking-widest">
                            Sent on: <?php echo date('d M, Y', strtotime($prop['created_at'])); ?>
                        </div>
                    </div>
                <?php endforeach; ?>
                 <?php if (empty($sent)): ?>
                    <div class="bg-gray-50 p-10 rounded-3xl border-2 border-dashed border-gray-200 text-center">
                        <i class="fas fa-paper-plane text-gray-200 text-4xl mb-4"></i>
                        <p class="text-gray-400 font-bold uppercase text-xs tracking-widest">You haven't sent any proposals yet</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
function handleProposal(id, status) {
    if (!confirm(`Are you sure you want to mark this proposal as ${status}?`)) return;

    fetch('api/swap_status.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `id=${id}&status=${status}`
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert(data.error || 'Failed to update proposal status');
        }
    });
}
</script>

<?php include __DIR__ . '/templates/footer.php'; ?>
