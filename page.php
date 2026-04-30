<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/inc/functions.php';

$slug = $_GET['slug'] ?? '';
$stmt = $pdo->prepare("SELECT * FROM pages WHERE slug = ?");
$stmt->execute([$slug]);
$page_data = $stmt->fetch();

if (!$page_data) {
    header("Location: /");
    exit;
}

$page_title = $page_data['title'] . " - " . ($settings['site_name'] ?? 'Classifieds');
$page_desc = $page_data['meta_desc'];
$page_keywords = $page_data['meta_keys'];

include __DIR__ . '/templates/header.php';
?>

<div class="container mx-auto px-4 py-16 flex-1">
    <div class="max-w-4xl mx-auto bg-white p-10 rounded-[2.5rem] shadow-sm border border-gray-100">
        <h1 class="text-4xl font-black text-gray-800 mb-10 border-l-8 border-primary-600 pl-6 uppercase tracking-tighter italic"><?php echo h($page_data['title']); ?></h1>

        <div class="prose prose-green max-w-none text-gray-600 leading-relaxed space-y-6">
            <?php echo nl2br($page_data['content']); ?>
        </div>
    </div>
</div>

<?php include __DIR__ . '/templates/footer.php'; ?>
