/**
 * Jiji-Inspired-1.0 Common JavaScript
 */

function toggleSave(adId, btn) {
    fetch(`/api/save_ad.php?ad_id=${adId}`)
        .then(res => res.json())
        .then(data => {
            if (!data.success) {
                if (data.message.includes('login')) {
                    window.location.href = '/login';
                } else {
                    alert(data.message);
                }
                return;
            }

            const icons = document.querySelectorAll(`.save-icon-${adId}`);
            icons.forEach(icon => {
                const button = icon.parentElement;
                if (data.saved) {
                    icon.classList.replace('far', 'fas');
                    button.classList.add('text-red-500', 'bg-red-50');
                    button.classList.remove('text-gray-400');
                } else {
                    icon.classList.replace('fas', 'far');
                    button.classList.remove('text-red-500', 'bg-red-50');
                    button.classList.add('text-gray-400');
                }
            });

            // Update mobile counter
            const counter = document.getElementById('savedCounter');
            if (counter) {
                counter.textContent = data.count;
                counter.classList.toggle('hidden', parseInt(data.count) === 0);
            }

            // If on saved_ads page and item was removed, remove the element
            if (window.location.pathname.includes('saved_ads') && !data.saved) {
                const card = btn.closest('.group');
                if (card) {
                    card.remove();
                    if (document.querySelectorAll('.group').length === 0) {
                        location.reload();
                    }
                }
            }
        });
}

// PWA Install Logic
let deferredPrompt;
const pwaBtn = document.getElementById('pwaInstall');

window.addEventListener('beforeinstallprompt', (e) => {
    e.preventDefault();
    deferredPrompt = e;
    if (pwaBtn) pwaBtn.style.display = 'block';
});

if (pwaBtn) {
    pwaBtn.addEventListener('click', async () => {
        if (deferredPrompt) {
            deferredPrompt.prompt();
            const { outcome } = await deferredPrompt.userChoice;
            deferredPrompt = null;
        }
    });
}
