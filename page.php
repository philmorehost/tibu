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
            <?php if ($slug === 'faq'): ?>
                <div class="space-y-4">
                    <?php
                    $faq_json = str_replace('JSON: ', '', $page_data['content']);
                    $faqs = json_decode($faq_json, true);
                    if ($faqs): foreach ($faqs as $i => $faq):
                    ?>
                        <div class="border border-gray-100 rounded-2xl overflow-hidden shadow-sm">
                            <button onclick="toggleAccordion(<?php echo $i; ?>)" class="w-full flex justify-between items-center p-6 text-left bg-gray-50/50 hover:bg-primary-50 transition">
                                <span class="font-black text-gray-800 text-sm uppercase tracking-tight"><?php echo h($faq['q']); ?></span>
                                <i id="icon-<?php echo $i; ?>" class="fas fa-plus text-primary-600 transition-transform"></i>
                            </button>
                            <div id="content-<?php echo $i; ?>" class="hidden p-6 bg-white border-t border-gray-50">
                                <p class="text-sm font-medium text-gray-600 leading-relaxed"><?php echo h($faq['a']); ?></p>
                            </div>
                        </div>
                    <?php endforeach; endif; ?>
                </div>
                <script>
                    function toggleAccordion(id) {
                        const content = document.getElementById('content-' + id);
                        const icon = document.getElementById('icon-' + id);
                        const isHidden = content.classList.contains('hidden');

                        // Close all
                        document.querySelectorAll('[id^="content-"]').forEach(el => el.classList.add('hidden'));
                        document.querySelectorAll('[id^="icon-"]').forEach(el => el.classList.replace('fa-minus', 'fa-plus'));

                        if (isHidden) {
                            content.classList.remove('hidden');
                            icon.classList.replace('fa-plus', 'fa-minus');
                        }
                    }
                </script>
            <?php else: ?>
                <?php echo nl2br($page_data['content']); ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include __DIR__ . '/templates/footer.php'; ?>
