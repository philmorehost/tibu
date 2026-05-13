<?php
require_once __DIR__ . '/config/config.php';
if (session_status() === PHP_SESSION_NONE) session_start();

require_once __DIR__ . '/inc/functions.php';

$page_title = "Tibu vs Jiji — Which Is Safer for Buying & Selling in Nigeria? | Tibu.ng";
$page_desc = "Jiji has 2M+ listings but zero seller verification, deletable reviews and rampant fraud. Tibu has NIN-verified sellers, immutable reviews and Nigeria's only swap marketplace.";
$page_keywords = "jiji alternative Nigeria, better than jiji, safe alternative jiji, jiji problems Nigeria, jiji scams Nigeria, Nigerian marketplace alternative, tibu vs jiji, NIN-verified sellers";

include __DIR__ . '/templates/header.php';
?>

<div class="container mx-auto px-4 py-16 flex-1">
    <div class="max-w-4xl mx-auto">
        <h1 class="text-4xl md:text-6xl font-black text-gray-800 leading-tight mb-8 text-center italic tracking-tighter">Tibu vs Jiji: Why Verification Changes Everything</h1>

        <div class="prose prose-xl max-w-none text-gray-700 leading-relaxed space-y-10">
            <p class="text-2xl font-bold text-primary-600 border-l-8 border-primary-600 pl-6 bg-primary-50 py-8 rounded-r-3xl">
                Nigeria's online marketplace landscape is changing. While platforms like Jiji offer millions of listings, they also present a playground for scammers. Tibu.ng was built to solve this problem once and for all.
            </p>

            <section>
                <h2 class="text-3xl font-black text-gray-800 mb-6 uppercase">1. NIN-Verified Sellers vs. Anonymity</h2>
                <p>On Jiji, anyone with a burner phone number can post an ad. On Tibu, we pioneered <strong>NIN-verified sellers</strong>. Every high-volume seller on Tibu must pass a real-time NIN identity check cross-referenced with a live selfie facial match. We don't just verify phone numbers; we verify human beings.</p>
            </section>

            <section class="bg-gray-50 p-10 rounded-[3rem] border border-gray-100">
                <h2 class="text-3xl font-black text-gray-800 mb-6 uppercase">2. Immutable Reviews</h2>
                <p>One of the biggest scams on Jiji is "review cleaning," where sellers can pay or use backdoors to delete negative feedback. At Tibu, reviews are <strong>immutable</strong>. If a seller scams someone, that 1-star review stays forever. This accountability makes Tibu the safest place for cars and electronics.</p>
            </section>

            <section>
                <h2 class="text-3xl font-black text-gray-800 mb-6 uppercase">3. The Swap Marketplace</h2>
                <p>Tibu is Nigeria's only dedicated <strong>Swap Marketplace</strong>. While Jiji is purely for cash-based sales, Tibu matches you with verified partners for item-for-item exchange. Our smart matching algorithm finds people nearby who have what you want and want what you have.</p>
            </section>

            <section class="bg-primary-600 text-white p-12 rounded-[4rem] shadow-2xl">
                <h2 class="text-4xl font-black mb-6 uppercase italic">The Verdict</h2>
                <p class="text-xl opacity-90 mb-8">If you want millions of junk listings and high risk, go to Jiji. If you want a <strong>safe, verified environment</strong> where you can buy, sell, and swap with total peace of mind, Tibu.ng is your only choice.</p>
                <a href="/register" class="inline-block bg-white text-primary-600 px-10 py-5 rounded-2xl font-black uppercase tracking-widest hover:bg-yellow-400 hover:text-white transition">Join the Verified Community</a>
            </section>
        </div>
    </div>
</div>

<?php include __DIR__ . '/templates/footer.php'; ?>

