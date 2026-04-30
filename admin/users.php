<?php
require_once __DIR__ . '/../inc/auth.php';
require_once __DIR__ . '/../inc/functions.php';
require_once __DIR__ . '/../inc/security.php';
require_admin();

if (isset($_GET['action']) && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $action = $_GET['action'];

    if ($action == 'verify') {
        $stmt = $pdo->prepare("UPDATE users SET is_verified = 1 WHERE id = ?");
        $stmt->execute([$id]);
        redirect('users.php', 'User verified.');
    } elseif ($action == 'suspend') {
        $stmt = $pdo->prepare("UPDATE users SET is_suspended = 1 WHERE id = ?");
        $stmt->execute([$id]);
        // Instantly hide user's ads (application level filtering)
        redirect('users.php', 'User account suspended.');
    } elseif ($action == 'reactivate') {
        $stmt = $pdo->prepare("UPDATE users SET is_suspended = 0 WHERE id = ?");
        $stmt->execute([$id]);
        redirect('users.php', 'User account reactivated.');
    } elseif ($action == 'delete') {
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
        $stmt->execute([$id]);
        redirect('users.php', 'User permanently deleted.');
    }
}

include __DIR__ . '/../templates/admin_header.php';
?>

<div class="bg-white p-6 rounded-lg shadow-sm">
    <h2 class="text-2xl font-bold mb-6 text-gray-800">User Management & Oversight</h2>

    <div class="overflow-x-auto">
        <table class="w-full text-left text-sm">
            <thead class="bg-gray-100 text-gray-600 font-bold uppercase text-xs">
                <tr>
                    <th class="p-4">Name</th>
                    <th class="p-4">Email</th>
                    <th class="p-4">Phone</th>
                    <th class="p-4">Verified</th>
                    <th class="p-4">Status</th>
                    <th class="p-4">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                <?php
                $stmt = $pdo->query("SELECT * FROM users ORDER BY created_at DESC");
                while ($user = $stmt->fetch()):
                ?>
                <tr>
                    <td class="p-4 font-bold text-gray-800"><?php echo h($user['full_name']); ?></td>
                    <td class="p-4"><?php echo h($user['email']); ?></td>
                    <td class="p-4"><?php echo h($user['phone']); ?></td>
                    <td class="p-4">
                        <?php if ($user['is_verified']): ?>
                            <span class="text-primary-600 font-bold"><i class="fas fa-check-circle mr-1"></i> Yes</span>
                        <?php else: ?>
                            <a href="users.php?action=verify&id=<?php echo $user['id']; ?>" class="text-blue-500 hover:underline">Verify Now</a>
                        <?php endif; ?>
                    </td>
                    <td class="p-4">
                        <?php if ($user['is_suspended']): ?>
                            <span class="text-red-600 font-bold">Suspended</span>
                        <?php else: ?>
                            <span class="text-primary-600 font-bold">Active</span>
                        <?php endif; ?>
                    </td>
                    <td class="p-4 space-x-2">
                        <?php if ($user['is_suspended']): ?>
                            <a href="users.php?action=reactivate&id=<?php echo $user['id']; ?>" class="bg-primary-500 text-white px-2 py-1 rounded text-xs hover:bg-primary-600 transition">Reactivate</a>
                        <?php else: ?>
                            <a href="users.php?action=suspend&id=<?php echo $user['id']; ?>" class="bg-red-500 text-white px-2 py-1 rounded text-xs hover:bg-red-600 transition" onclick="return confirm('Suspend this user and hide all their ads?')">Suspend</a>
                        <?php endif; ?>
                        <a href="users.php?action=delete&id=<?php echo $user['id']; ?>" class="text-gray-400 hover:text-red-600 transition" onclick="return confirm('Permanently delete this user?')"><i class="fas fa-trash"></i></a>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include __DIR__ . '/../templates/admin_footer.php'; ?>
