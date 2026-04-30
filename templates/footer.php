<footer class="bg-gray-800 text-white mt-auto py-10">
    <div class="container mx-auto px-4 grid grid-cols-1 md:grid-cols-4 gap-8">
        <div>
            <h4 class="font-bold text-lg mb-4 text-primary-500"><?php echo h($settings['site_name'] ?? 'Classifieds'); ?></h4>
            <p class="text-sm text-gray-400">The safest and best classifieds platform in Nigeria.</p>
        </div>
        <div>
            <h4 class="font-bold text-lg mb-4 text-primary-500">Company</h4>
            <ul class="space-y-2 text-sm text-gray-400">
                <li><a href="/blog" class="hover:text-white transition">Marketplace Blog</a></li>
                <li><a href="/contact" class="hover:text-white transition">Contact Us</a></li>
            </ul>
        </div>
        <div>
            <h4 class="font-bold text-lg mb-4 text-primary-500">Links</h4>
            <ul class="space-y-2 text-sm text-gray-400">
                <li><a href="/p/terms" class="hover:text-white transition">Terms & Conditions</a></li>
                <li><a href="/p/privacy" class="hover:text-white transition">Privacy Policy</a></li>
                <li><a href="/p/billing" class="hover:text-white transition">Billing Policy</a></li>
            </ul>
        </div>
        <div>
            <h4 class="font-bold text-lg mb-4 text-primary-500">Support</h4>
            <ul class="space-y-2 text-sm text-gray-400">
                <li><a href="contact.php" class="hover:text-white transition">Contact Us</a></li>
                <li><a href="/p/faq" class="hover:text-white transition">FAQ</a></li>
                <li><a href="/p/safety" class="hover:text-white transition">Safety Tips</a></li>
            </ul>
        </div>
        <div>
            <h4 class="font-bold text-lg mb-4 text-primary-500">App</h4>
            <p class="text-xs text-gray-400 mb-2">Install our PWA for the best mobile experience.</p>
            <button id="pwaInstall" class="bg-primary-600 text-white px-4 py-2 rounded font-bold hover:bg-primary-700 transition w-full">Install App</button>
        </div>
    </div>
    <div class="text-center mt-10 border-t border-gray-700 pt-6 text-sm text-gray-500">
        &copy; <?php echo date('Y'); ?> <?php echo h($settings['site_name'] ?? 'Classifieds'); ?> - All Rights Reserved.
    </div>
</footer>

<?php include __DIR__ . '/mobile_footer.php'; ?>

<script src="/assets/js/app.js"></script>
</body>
</html>
