<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/inc/functions.php';

$slug = $_GET['slug'] ?? '';
$stmt = $pdo->prepare("SELECT * FROM blog_posts WHERE slug = ?");
$stmt->execute([$slug]);
$post = $stmt->fetch();

if (!$post) {
    header("Location: /blog");
    exit;
}

$page_title = $post['title'] . " - Blog";
$page_desc = $post['meta_desc'];
$page_keywords = $post['meta_keys'];

include __DIR__ . '/templates/header.php';
?>

<div class="container mx-auto px-4 py-16 flex-1">
    <div class="max-w-4xl mx-auto">
        <div class="mb-10">
            <a href="/blog" class="text-xs font-black text-gray-400 hover:text-primary-600 transition uppercase tracking-widest flex items-center gap-2 mb-6"><i class="fas fa-arrow-left"></i> Back to Blog</a>
            <h1 class="text-5xl font-black text-gray-800 leading-tight mb-6"><?php echo h($post['title']); ?></h1>
            <div class="flex items-center gap-6 text-[10px] font-black text-gray-400 uppercase tracking-widest">
                <span class="bg-gray-100 px-3 py-1 rounded-full text-gray-600">Journalist</span>
                <span>Published on <?php echo date('F d, Y', strtotime($post['created_at'])); ?></span>
            </div>
        </div>

        <?php if ($post['image']): ?>
            <img src="/uploads/blog/<?php echo $post['image']; ?>" class="w-full h-auto rounded-[3rem] shadow-2xl mb-16 border-8 border-white">
        <?php endif; ?>

        <div class="prose prose-xl prose-green max-w-none text-gray-700 leading-relaxed font-serif">
            <?php echo nl2br($post['content']); ?>
        </div>

        <div class="mt-20 pt-10 border-t border-gray-100 flex justify-center">
            <div class="bg-gray-50 p-10 rounded-[3rem] text-center w-full">
                <h3 class="font-black text-2xl text-gray-800 mb-2">Did you find this helpful?</h3>
                <p class="text-gray-400 font-bold mb-6">Share this article with your fellow sellers</p>
                <div class="flex justify-center gap-4">
                    <a href="#" class="w-12 h-12 bg-white rounded-2xl flex items-center justify-center text-gray-400 hover:text-blue-600 shadow-sm transition"><i class="fab fa-facebook-f"></i></a>
                    <a href="#" class="w-12 h-12 bg-white rounded-2xl flex items-center justify-center text-gray-400 hover:text-black shadow-sm transition"><i class="fab fa-x-twitter"></i></a>
                    <a href="#" class="w-12 h-12 bg-white rounded-2xl flex items-center justify-center text-gray-400 hover:text-primary-600 shadow-sm transition"><i class="fab fa-whatsapp"></i></a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/templates/footer.php'; ?>
