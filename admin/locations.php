<?php
require_once __DIR__ . '/../inc/auth.php';
require_once __DIR__ . '/../inc/functions.php';
require_once __DIR__ . '/../inc/security.php';
require_admin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_state'])) {
        $name = $_POST['name'];
        $stmt = $pdo->prepare("INSERT INTO states (name) VALUES (?)");
        $stmt->execute([$name]);
        redirect('locations.php', 'State added.');
    }
    if (isset($_POST['add_lga'])) {
        $state_id = (int)$_POST['state_id'];
        $name = $_POST['name'];
        $stmt = $pdo->prepare("INSERT INTO lgas (state_id, name) VALUES (?, ?)");
        $stmt->execute([$state_id, $name]);
        redirect('locations.php', 'LGA added.');
    }
}

include __DIR__ . '/../templates/admin_header.php';
?>

<div class="grid grid-cols-1 md:grid-cols-2 gap-8">
    <div class="bg-white p-6 rounded-lg shadow-sm border border-primary-50">
        <h2 class="text-xl font-bold mb-6 text-gray-800 border-b pb-2"><i class="fas fa-map mr-2 text-primary-600"></i> Nigerian States</h2>
        <form method="POST" class="mb-6 flex gap-2">
            <input type="text" name="name" placeholder="New State Name" class="flex-1 p-2 border rounded" required>
            <button type="submit" name="add_state" class="bg-primary-600 text-white px-4 py-2 rounded font-bold hover:bg-primary-700 transition">Add State</button>
        </form>
        <div class="h-96 overflow-y-auto space-y-2 p-2 bg-gray-50 rounded-lg">
            <?php
            $states = $pdo->query("SELECT * FROM states ORDER BY name ASC")->fetchAll();
            foreach ($states as $state):
            ?>
            <div class="flex justify-between items-center p-3 bg-white border rounded shadow-sm">
                <span class="font-bold text-gray-700"><?php echo h($state['name']); ?></span>
                <span class="text-[10px] text-gray-400 font-bold uppercase tracking-widest">ID: <?php echo $state['id']; ?></span>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="bg-white p-6 rounded-lg shadow-sm border border-blue-50">
        <h2 class="text-xl font-bold mb-6 text-gray-800 border-b pb-2"><i class="fas fa-city mr-2 text-blue-600"></i> Local Government Areas</h2>
        <form method="POST" class="mb-6 space-y-3">
            <select name="state_id" class="w-full p-2 border rounded" required>
                <option value="">Select State</option>
                <?php foreach ($states as $state): ?>
                    <option value="<?php echo $state['id']; ?>"><?php echo h($state['name']); ?></option>
                <?php endforeach; ?>
            </select>
            <div class="flex gap-2">
                <input type="text" name="name" placeholder="New LGA Name" class="flex-1 p-2 border rounded" required>
                <button type="submit" name="add_lga" class="bg-blue-600 text-white px-4 py-2 rounded font-bold hover:bg-blue-700 transition">Add LGA</button>
            </div>
        </form>
        <div class="h-96 overflow-y-auto space-y-2 p-2 bg-gray-50 rounded-lg">
            <?php
            $lgas = $pdo->query("SELECT l.*, s.name as state_name FROM lgas l JOIN states s ON l.state_id = s.id ORDER BY s.name ASC, l.name ASC LIMIT 200")->fetchAll();
            foreach ($lgas as $lga):
            ?>
            <div class="flex justify-between items-center p-3 bg-white border rounded shadow-sm">
                <div>
                    <span class="font-bold text-gray-700"><?php echo h($lga['name']); ?></span>
                    <span class="text-[10px] text-blue-500 font-bold ml-2">(<?php echo h($lga['state_name']); ?>)</span>
                </div>
                <span class="text-[10px] text-gray-400 font-bold uppercase tracking-widest">ID: <?php echo $lga['id']; ?></span>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../templates/admin_footer.php'; ?>
