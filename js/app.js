// Registrar Service Worker
if ('serviceWorker' in navigator) {
    window.addEventListener('load', () => {
        navigator.serviceWorker.register('/sw.js')
            .then(registration => {
                console.log('ServiceWorker registrado com sucesso');
            })
            .catch(error => {
                console.log('Erro ao registrar ServiceWorker:', error);
            });
    });
}

// Lógica para instalar PWA
let deferredPrompt;
const installButton = document.getElementById('installButton');

window.addEventListener('beforeinstallprompt', (e) => {
    e.preventDefault();
    deferredPrompt = e;
    document.querySelector('.install-prompt').classList.add('show');
});

if (installButton) {
    installButton.addEventListener('click', async () => {
        if (deferredPrompt) {
            deferredPrompt.prompt();
            const { outcome } = await deferredPrompt.userChoice;
            if (outcome === 'accepted') {
                console.log('PWA instalado');
            }
            deferredPrompt = null;
            document.querySelector('.install-prompt').classList.remove('show');
        }
    });
}

// Máscaras de input
document.addEventListener('DOMContentLoaded', () => {
    const whatsappInput = document.getElementById('whatsapp');
    if (whatsappInput) {
        IMask(whatsappInput, {
            mask: '(00) 00000-0000'
        });
    }
}); 