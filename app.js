let deferredPrompt;
const installButton = document.createElement('button');
installButton.style.display = 'none';
installButton.classList.add('install-button', 'fixed', 'bottom-4', 'right-4', 'bg-blue-600', 'text-white', 'px-4', 'py-2', 'rounded-lg', 'shadow-lg');
installButton.textContent = 'Install App';

// Check if app is installed
function isAppInstalled() {
    return window.matchMedia('(display-mode: standalone)').matches || 
           window.navigator.standalone === true;
}

// Redirect to PWA if accessed via browser when already installed
if (isAppInstalled() && !window.location.href.includes('/auth/login.php')) {
    window.location.href = '/auth/login.php';
}

window.addEventListener('beforeinstallprompt', (e) => {
    e.preventDefault();
    deferredPrompt = e;
    installButton.style.display = 'block';
    document.body.appendChild(installButton);
});

installButton.addEventListener('click', async () => {
    if (!deferredPrompt) return;
    deferredPrompt.prompt();
    const { outcome } = await deferredPrompt.userChoice;
    if (outcome === 'accepted') {
        console.log('PWA installed');
        installButton.style.display = 'none';
    }
    deferredPrompt = null;
});

window.addEventListener('appinstalled', () => {
    installButton.style.display = 'none';
    deferredPrompt = null;
    // Redirect to login page after installation
    window.location.href = '/auth/login.php';
});

// Register service worker
if ('serviceWorker' in navigator) {
    window.addEventListener('load', () => {
        navigator.serviceWorker.register('/service-worker.js')
            .then(registration => {
                console.log('ServiceWorker registration successful');
            })
            .catch(err => {
                console.log('ServiceWorker registration failed: ', err);
            });
    });
}