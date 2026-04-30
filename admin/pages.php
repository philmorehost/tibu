<?php
require_once __DIR__ . '/../inc/auth.php';
require_once __DIR__ . '/../inc/functions.php';
require_admin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int)($_POST['id'] ?? 0);
    $title = $_POST['title'];
    $slug = $_POST['slug'] ?: strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $title)));
    $content = $_POST['content'];
    $m_desc = $_POST['meta_desc'];
    $m_keys = $_POST['meta_keys'];

    if ($id) {
        $stmt = $pdo->prepare("UPDATE pages SET title = ?, slug = ?, content = ?, meta_desc = ?, meta_keys = ? WHERE id = ?");
        $stmt->execute([$title, $slug, $content, $m_desc, $m_keys, $id]);
    } else {
        $stmt = $pdo->prepare("INSERT INTO pages (title, slug, content, meta_desc, meta_keys) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$title, $slug, $content, $m_desc, $m_keys]);
    }
    redirect('pages.php', 'Page saved successfully.');
}

if (isset($_GET['delete'])) {
    $pdo->prepare("DELETE FROM pages WHERE id = ?")->execute([(int)$_GET['delete']]);
    redirect('pages.php', 'Page deleted.');
}

$edit_page = null;
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM pages WHERE id = ?");
    $stmt->execute([(int)$_GET['edit']]);
    $edit_page = $stmt->fetch();
}

include __DIR__ . '/../templates/admin_header.php';
?>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
    <div class="lg:col-span-2">
        <div class="bg-white p-8 rounded-3xl shadow-sm border border-gray-100">
            <h2 class="text-xl font-black text-gray-800 mb-8 uppercase tracking-tighter italic"><?php echo $edit_page ? 'Edit Page' : 'Create New Page'; ?></h2>
            <form method="POST" class="space-y-6">
                <input type="hidden" name="id" value="<?php echo $edit_page['id'] ?? ''; ?>">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-xs font-black text-gray-400 uppercase tracking-widest mb-2">Page Title</label>
                        <input type="text" name="title" value="<?php echo h($edit_page['title'] ?? ''); ?>" class="w-full p-3 border rounded-xl" required>
                    </div>
                    <div>
                        <label class="block text-xs font-black text-gray-400 uppercase tracking-widest mb-2">Slug (URL)</label>
                        <input type="text" name="slug" value="<?php echo h($edit_page['slug'] ?? ''); ?>" class="w-full p-3 border rounded-xl" placeholder="auto-generated-if-empty">
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-black text-gray-400 uppercase tracking-widest mb-2">Content (HTML allowed)</label>
                    <textarea name="content" rows="15" class="w-full p-4 border rounded-xl font-mono text-sm"><?php echo h($edit_page['content'] ?? ''); ?></textarea>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 p-6 bg-gray-50 rounded-2xl">
                    <div>
                        <label class="block text-xs font-black text-gray-400 uppercase tracking-widest mb-2">SEO Description</label>
                        <textarea name="meta_desc" rows="3" class="w-full p-3 border rounded-xl text-xs"><?php echo h($edit_page['meta_desc'] ?? ''); ?></textarea>
                    </div>
                    <div>
                        <label class="block text-xs font-black text-gray-400 uppercase tracking-widest mb-2">SEO Keywords</label>
                        <textarea name="meta_keys" rows="3" class="w-full p-3 border rounded-xl text-xs"><?php echo h($edit_page['meta_keys'] ?? ''); ?></textarea>
                    </div>
                </div>
                <button type="submit" class="bg-primary-600 text-white px-10 py-4 rounded-2xl font-black uppercase tracking-widest hover:bg-primary-700 transition shadow-xl">Save CMS Page</button>
            </form>
        </div>
    </div>

    <div>
        <div class="bg-white p-6 rounded-3xl shadow-sm border border-gray-100 sticky top-24">
            <h3 class="text-xs font-black text-gray-400 uppercase tracking-widest mb-6">Existing Pages</h3>
            <div class="space-y-3">
                <?php
                $pages = $pdo->query("SELECT id, title, slug FROM pages ORDER BY title ASC")->fetchAll();
                foreach ($pages as $p):
                ?>
                <div class="flex items-center justify-between p-4 bg-gray-50 rounded-2xl border border-transparent hover:border-primary-200 transition group">
                    <div>
                        <p class="text-sm font-black text-gray-700"><?php echo h($p['title']); ?></p>
                        <p class="text-[10px] text-gray-400 font-bold">/p/<?php echo $p['slug']; ?></p>
                    </div>
                    <div class="flex gap-2">
                        <a href="pages.php?edit=<?php echo $p['id']; ?>" class="text-blue-500 hover:text-blue-700"><i class="fas fa-edit"></i></a>
                        <a href="pages.php?delete=<?php echo $p['id']; ?>" class="text-red-400 hover:text-red-600" onclick="return confirm('Delete this page?')"><i class="fas fa-trash"></i></a>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php if ($edit_page): ?>
                <a href="pages.php" class="block text-center mt-6 text-[10px] font-black text-primary-600 uppercase tracking-widest underline">Create New Instead</a>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../templates/admin_footer.php'; ?>
