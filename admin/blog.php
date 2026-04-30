<?php
require_once __DIR__ . '/../inc/auth.php';
require_once __DIR__ . '/../inc/functions.php';
require_admin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int)($_POST['id'] ?? 0);
    $title = $_POST['title'];
    $slug = $_POST['slug'] ?: strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $title)));
    $summary = $_POST['summary'];
    $content = $_POST['content'];
    $m_desc = $_POST['meta_desc'];
    $m_keys = $_POST['meta_keys'];

    // Handle Image
    $image = $_POST['current_image'] ?? null;
    if (!empty($_FILES['image']['tmp_name'])) {
        $image = process_image_upload($_FILES['image']['tmp_name'], __DIR__ . '/../uploads/blog', 1200);
    }

    if ($id) {
        $stmt = $pdo->prepare("UPDATE blog_posts SET title = ?, slug = ?, summary = ?, content = ?, image = ?, meta_desc = ?, meta_keys = ? WHERE id = ?");
        $stmt->execute([$title, $slug, $summary, $content, $image, $m_desc, $m_keys, $id]);
    } else {
        $stmt = $pdo->prepare("INSERT INTO blog_posts (title, slug, summary, content, image, meta_desc, meta_keys) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$title, $slug, $summary, $content, $image, $m_desc, $m_keys]);
    }
    redirect('blog.php', 'Post saved successfully.');
}

if (isset($_GET['delete'])) {
    $pdo->prepare("DELETE FROM blog_posts WHERE id = ?")->execute([(int)$_GET['delete']]);
    redirect('blog.php', 'Post deleted.');
}

$edit_post = null;
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM blog_posts WHERE id = ?");
    $stmt->execute([(int)$_GET['edit']]);
    $edit_post = $stmt->fetch();
}

include __DIR__ . '/../templates/admin_header.php';
?>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
    <div class="lg:col-span-2">
        <div class="bg-white p-8 rounded-3xl shadow-sm border border-gray-100">
            <h2 class="text-xl font-black text-gray-800 mb-8 uppercase tracking-tighter italic"><?php echo $edit_post ? 'Edit Post' : 'Write New Blog Post'; ?></h2>
            <form method="POST" enctype="multipart/form-data" class="space-y-6">
                <input type="hidden" name="id" value="<?php echo $edit_post['id'] ?? ''; ?>">
                <input type="hidden" name="current_image" value="<?php echo $edit_post['image'] ?? ''; ?>">

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-xs font-black text-gray-400 uppercase tracking-widest mb-2">Post Title</label>
                        <input type="text" name="title" value="<?php echo h($edit_post['title'] ?? ''); ?>" class="w-full p-3 border rounded-xl" required>
                    </div>
                    <div>
                        <label class="block text-xs font-black text-gray-400 uppercase tracking-widest mb-2">Slug (URL)</label>
                        <input type="text" name="slug" value="<?php echo h($edit_post['slug'] ?? ''); ?>" class="w-full p-3 border rounded-xl" placeholder="auto-generated">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-black text-gray-400 uppercase tracking-widest mb-2">Featured Image</label>
                    <input type="file" name="image" class="w-full text-xs text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-xs file:font-semibold file:bg-primary-50 file:text-primary-700 hover:file:bg-primary-100 transition">
                </div>

                <div>
                    <label class="block text-xs font-black text-gray-400 uppercase tracking-widest mb-2">Summary (Brief intro)</label>
                    <textarea name="summary" rows="3" class="w-full p-3 border rounded-xl text-sm"><?php echo h($edit_post['summary'] ?? ''); ?></textarea>
                </div>

                <div>
                    <label class="block text-xs font-black text-gray-400 uppercase tracking-widest mb-2">Full Content</label>
                    <textarea name="content" rows="15" class="w-full p-4 border rounded-xl font-serif text-lg leading-relaxed"><?php echo h($edit_post['content'] ?? ''); ?></textarea>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 p-6 bg-gray-50 rounded-2xl border border-gray-100">
                    <div>
                        <label class="block text-xs font-black text-gray-400 uppercase tracking-widest mb-2">SEO Description</label>
                        <textarea name="meta_desc" rows="3" class="w-full p-3 border rounded-xl text-xs"><?php echo h($edit_post['meta_desc'] ?? ''); ?></textarea>
                    </div>
                    <div>
                        <label class="block text-xs font-black text-gray-400 uppercase tracking-widest mb-2">SEO Keywords</label>
                        <textarea name="meta_keys" rows="3" class="w-full p-3 border rounded-xl text-xs"><?php echo h($edit_post['meta_keys'] ?? ''); ?></textarea>
                    </div>
                </div>

                <button type="submit" class="bg-primary-600 text-white px-10 py-4 rounded-2xl font-black uppercase tracking-widest hover:bg-primary-700 transition shadow-xl">Publish Post</button>
            </form>
        </div>
    </div>

    <div>
        <div class="bg-white p-6 rounded-3xl shadow-sm border border-gray-100 sticky top-24">
            <h3 class="text-xs font-black text-gray-400 uppercase tracking-widest mb-6">Recent Posts</h3>
            <div class="space-y-4">
                <?php
                $posts = $pdo->query("SELECT id, title, created_at FROM blog_posts ORDER BY created_at DESC")->fetchAll();
                foreach ($posts as $bp):
                ?>
                <div class="flex items-center justify-between p-4 bg-gray-50 rounded-2xl border border-transparent hover:border-primary-200 transition group">
                    <div class="flex-1 pr-4">
                        <p class="text-xs font-black text-gray-700 line-clamp-2"><?php echo h($bp['title']); ?></p>
                        <p class="text-[9px] text-gray-400 font-bold mt-1"><?php echo date('M d, Y', strtotime($bp['created_at'])); ?></p>
                    </div>
                    <div class="flex gap-2">
                        <a href="blog.php?edit=<?php echo $bp['id']; ?>" class="text-blue-500 hover:text-blue-700 transition"><i class="fas fa-edit"></i></a>
                        <a href="blog.php?delete=<?php echo $bp['id']; ?>" class="text-red-400 hover:text-red-600 transition" onclick="return confirm('Delete post?')"><i class="fas fa-trash"></i></a>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php if ($edit_post): ?>
                <a href="blog.php" class="block text-center mt-6 text-[10px] font-black text-primary-600 uppercase tracking-widest underline">Write New Instead</a>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../templates/admin_footer.php'; ?>
