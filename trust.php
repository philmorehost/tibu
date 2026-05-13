<?php
require_once __DIR__ . '/config/config.php'`r`nif (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/inc/functions.php';

$page_title = "How Tibu Verifies Sellers — NIN + Live Selfie Identity Check | Tibu.ng";
$page_desc = "Every Tibu seller verifies their identity with phone + email (20 listings) or NIN + live selfie facial match (unlimited listings). Here's exactly how the Deal Safety Score works.";
$page_keywords = "verified sellers Nigeria, NIN verification marketplace, safe buying Nigeria, identity verified classifieds, how tibu works, deal safety score, tibu verified badge, NIN-verified sellers";

include __DIR__ . '/templates/header.php';
?>

<div class="container mx-auto px-4 py-16 flex-1">
    <div class="max-w-4xl mx-auto">
        <div class="text-center mb-16">
            <div class="inline-flex items-center gap-3 bg-primary-50 text-primary-600 px-6 py-2 rounded-full font-black uppercase text-xs tracking-widest mb-6">
                <i class="fas fa-shield-check text-lg"></i> Trust & Safety
            </div>
            <h1 class="text-4xl md:text-6xl font-black text-gray-800 leading-tight tracking-tighter">The Gold Standard of <br><span class="text-primary-600 italic">Seller Verification</span></h1>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-8 mb-20">
            <div class="bg-white p-10 rounded-[3rem] shadow-xl border border-gray-100">
                <div class="w-16 h-16 bg-blue-50 rounded-2xl flex items-center justify-center text-blue-600 text-3xl mb-8">
                    <i class="fas fa-phone-alt"></i>
                </div>
                <h3 class="text-2xl font-black text-gray-800 mb-4 uppercase">Phone Verified</h3>
                <p class="text-gray-500 font-bold leading-relaxed mb-6">Standard verification required for all accounts. Allows up to 20 active listings. Perfect for casual sellers.</p>
                <span class="text-[10px] font-black text-blue-600 uppercase tracking-widest bg-blue-50 px-3 py-1 rounded-full">Basic Protection</span>
            </div>

            <div class="bg-primary-600 p-10 rounded-[3rem] shadow-2xl text-white transform hover:-translate-y-2 transition duration-500">
                <div class="w-16 h-16 bg-white/20 rounded-2xl flex items-center justify-center text-white text-3xl mb-8">
                    <i class="fas fa-id-card"></i>
                </div>
                <h3 class="text-2xl font-black mb-4 uppercase">NIN Verified</h3>
                <p class="opacity-90 font-bold leading-relaxed mb-6">The highest tier. Requires NIN submission + live facial biometric scan. Unlocks unlimited listings and the <strong>Verified Badge</strong>.</p>
                <span class="text-[10px] font-black text-primary-900 uppercase tracking-widest bg-yellow-400 px-3 py-1 rounded-full">Elite Protection</span>
            </div>
        </div>

        <div class="bg-gray-50 p-12 rounded-[4rem] border border-gray-100 mb-20">
            <h2 class="text-3xl font-black text-gray-800 mb-10 text-center uppercase">How It Works</h2>
            <div class="space-y-12">
                <div class="flex gap-8">
                    <div class="w-12 h-12 bg-white rounded-full shadow-lg flex items-center justify-center font-black text-primary-600 flex-shrink-0">1</div>
                    <div>
                        <h4 class="text-xl font-black text-gray-800 mb-2 uppercase">Biometric Match</h4>
                        <p class="text-gray-500 font-bold">Our system captures a live selfie and uses AI to compare it against the official NIMC database photograph. This ensures the person posting the ad is exactly who they claim to be.</p>
                    </div>
                </div>
                <div class="flex gap-8">
                    <div class="w-12 h-12 bg-white rounded-full shadow-lg flex items-center justify-center font-black text-primary-600 flex-shrink-0">2</div>
                    <div>
                        <h4 class="text-xl font-black text-gray-800 mb-2 uppercase">Deal Safety Score</h4>
                        <p class="text-gray-500 font-bold">Every listing is assigned a score based on account age, verification tier, and historical review data. Always look for listings with a score above 80.</p>
                    </div>
                </div>
                <div class="flex gap-8">
                    <div class="w-12 h-12 bg-white rounded-full shadow-lg flex items-center justify-center font-black text-primary-600 flex-shrink-0">3</div>
                    <div>
                        <h4 class="text-xl font-black text-gray-800 mb-2 uppercase">Verified Badge</h4>
                        <p class="text-gray-500 font-bold">Only NIN-verified sellers get the Blue Shield. This is your guarantee of a legitimate transaction on Nigeria's safest marketplace.</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="text-center">
            <h2 class="text-3xl font-black text-gray-800 mb-8 uppercase tracking-tighter italic">Ready to trade safely?</h2>
            <div class="flex flex-wrap justify-center gap-6">
                <a href="/register" class="bg-primary-600 text-white px-10 py-5 rounded-2xl font-black uppercase tracking-widest hover:bg-primary-700 transition shadow-2xl">Start Selling</a>
                <a href="/search.php" class="bg-white text-gray-800 px-10 py-5 rounded-2xl font-black uppercase tracking-widest hover:bg-gray-50 transition border border-gray-100">Browse Verified Ads</a>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/templates/footer.php'; ?>

