<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/inc/functions.php';

$stmt = $pdo->query("SELECT * FROM blog_posts ORDER BY created_at DESC");
$posts = $stmt->fetchAll();

$page_title = "Blog - " . ($settings['site_name'] ?? 'Classifieds');
include __DIR__ . '/templates/header.php';
?>

<div class="container mx-auto px-4 py-16 flex-1">
    <div class="mb-16 text-center">
        <h1 class="text-5xl font-black text-gray-800 uppercase tracking-tighter italic mb-4">The Marketplace <span class="text-primary-600">Journal</span></h1>
        <p class="text-gray-400 font-bold tracking-widest uppercase text-xs">Insights, Tips, and News from Nigeria's #1 Community</p>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-10">
        <?php foreach ($posts as $post): ?>
        <a href="/blog/<?php echo $post['slug']; ?>" class="bg-white rounded-[2.5rem] overflow-hidden shadow-sm hover:shadow-2xl transition-all duration-500 border border-gray-100 group">
            <div class="h-64 overflow-hidden">
                <img src="<?php echo $post['image'] ? '/uploads/blog/'.$post['image'] : 'https://placehold.co/800x600?text=Blog+Post'; ?>" class="w-full h-full object-cover group-hover:scale-110 transition duration-700">
            </div>
            <div class="p-8">
                <div class="flex items-center gap-3 mb-4">
                    <span class="text-[10px] font-black text-primary-600 uppercase tracking-widest bg-primary-50 px-3 py-1 rounded-full">Articles</span>
                    <span class="text-[10px] font-bold text-gray-300 uppercase"><?php echo date('M d, Y', strtotime($post['created_at'])); ?></span>
                </div>
                <h2 class="text-xl font-black text-gray-800 mb-4 line-clamp-2 group-hover:text-primary-600 transition-colors"><?php echo h($post['title']); ?></h2>
                <p class="text-sm text-gray-500 line-clamp-3 leading-relaxed mb-6"><?php echo h($post['summary']); ?></p>
                <div class="flex items-center gap-2 text-xs font-black text-gray-800 uppercase tracking-widest group-hover:gap-4 transition-all">
                    Read More <i class="fas fa-arrow-right text-primary-600"></i>
                </div>
            </div>
        </a>
        <?php endforeach; ?>
    </div>

    <?php if (empty($posts)): ?>
    <div class="py-40 text-center">
        <i class="fas fa-newspaper text-8xl text-gray-100 mb-6"></i>
        <h3 class="text-2xl font-black text-gray-300 uppercase tracking-widest">No stories found</h3>
    </div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/templates/footer.php'; ?>
