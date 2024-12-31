<header class="fixed top-0 left-0 right-0 bg-white shadow-md z-50">
    <div class="container mx-auto px-4 py-3 max-w-md md:max-w-[650px]">
        <div class="flex justify-between items-center">
            <a href="/index.php" class="text-gray-800 hover:text-gray-600">
                <h1 class="text-2xl font-bold text-gray-800">
                <?php echo APP_NAME; ?>
                </h1>
            </a>
 
        </div>
    </div>
</header>

<!-- Hidden Google Translate Element -->
<div id="google_translate_element" class="hidden"></div>

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <meta name="googlebot" content="noindex, nofollow">
    <meta name="bingbot" content="noindex, nofollow">
    <title>Debt Management System</title>
    
    <!-- PWA Meta Tags -->
    <meta name="description" content="Debt Management System for tracking and managing debts">
    <meta name="theme-color" content="#4f46e5">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black">
    <meta name="apple-mobile-web-app-title" content="DebtMS">
    
    <!-- PWA Manifest -->
    <link rel="manifest" href="/manifest.json">
    
    <!-- PWA Icons -->
    <link rel="icon" type="image/png" sizes="192x192" href="/icons/icon-192x192.png">
    <link rel="apple-touch-icon" href="/icons/icon-192x192.png">
</head>

<style>
    /* Hide Google Translate elements */
    .goog-te-banner-frame,
    .skiptranslate,
    .goog-te-spinner-pos,
    .goog-tooltip,
    .goog-tooltip:hover,
    .goog-text-highlight {
        display: none !important;
    }
    
    body {
        top: 0 !important;
        position: static !important;
    }

    /* Hide Google branding */
    .goog-logo-link {
        display: none !important;
    }
    .goog-te-gadget {
        color: transparent !important;
    }
    
    /* Hide the default Google Translate dropdown */
    #google_translate_element {
        position: absolute;
        top: -9999px;
        left: -9999px;
        visibility: hidden;
    }
</style>

<script src="https://cdn.tailwindcss.com"></script>

<!-- PWA Installation Script -->
<script>
    if ('serviceWorker' in navigator) {
        window.addEventListener('load', () => {
            navigator.serviceWorker.register('/sw.js')
                .then(registration => {
                    console.log('ServiceWorker registration successful');
                })
                .catch(err => {
                    console.log('ServiceWorker registration failed: ', err);
                });
        });
    }

    // PWA Installation prompt
    let deferredPrompt;
    
    window.addEventListener('beforeinstallprompt', (e) => {
        e.preventDefault();
        deferredPrompt = e;
        
        // Show installation prompt if not installed
        if (!localStorage.getItem('pwa-installed')) {
            showInstallPrompt();
        }
    });

    function showInstallPrompt() {
        if (deferredPrompt) {
            const promptDiv = document.createElement('div');
            promptDiv.className = 'fixed bottom-0 left-0 right-0 bg-indigo-600 text-white p-4 flex justify-between items-center z-50';
            promptDiv.innerHTML = `
                <div>Install this app on your device for better experience</div>
                <div class="flex space-x-2">
                    <button onclick="installPWA()" class="px-4 py-2 bg-white text-indigo-600 rounded">Install</button>
                    <button onclick="closeInstallPrompt(this.parentElement.parentElement)" class="px-4 py-2 border border-white rounded">Later</button>
                </div>
            `;
            document.body.appendChild(promptDiv);
        }
    }

    function closeInstallPrompt(element) {
        element.remove();
    }

    async function installPWA() {
        if (deferredPrompt) {
            deferredPrompt.prompt();
            const { outcome } = await deferredPrompt.userChoice;
            if (outcome === 'accepted') {
                localStorage.setItem('pwa-installed', 'true');
                console.log('User accepted the installation prompt');
            }
            deferredPrompt = null;
        }
    }
</script>

<script type="text/javascript">
    function googleTranslateElementInit() {
        new google.translate.TranslateElement({
            pageLanguage: 'en',
            includedLanguages: 'en,ms,ne,my',
            layout: google.translate.TranslateElement.InlineLayout.SIMPLE,
            autoDisplay: false
        }, 'google_translate_element');
    }

    function translateTo(lang) {
        // Set cookies in all possible domains
        const hostname = window.location.hostname;
        const domain = hostname.split('.').slice(-2).join('.');
        const domains = [hostname, '.' + hostname, domain, '.' + domain];
        
        domains.forEach(dom => {
            document.cookie = `googtrans=/en/${lang}; path=/; domain=${dom}`;
            document.cookie = `googtrans=/en/${lang}; path=/;`;
        });

        // Force translation update
        const googleFrame = document.getElementsByClassName('goog-te-menu-frame')[0];
        if (googleFrame) {
            const innerDoc = googleFrame.contentDocument || googleFrame.contentWindow.document;
            const element = innerDoc.getElementsByTagName('button');
            for (let i = 0; i < element.length; i++) {
                if (element[i].innerHTML.includes(lang)) {
                    element[i].click();
                    break;
                }
            }
        } else {
            // If frame not found, try direct method
            const select = document.querySelector('.goog-te-combo');
            if (select) {
                select.value = lang;
                select.dispatchEvent(new Event('change'));
            }
        }

        // Remove banner and fix page position
        removeBanner();
    }

    function removeBanner() {
        const elements = document.getElementsByClassName('skiptranslate');
        for (let element of elements) {
            if (element.tagName === 'IFRAME') {
                element.style.display = 'none';
            }
        }
        document.body.style.top = '0px';
        document.body.style.position = 'static';
    }

    // Initialize
    window.addEventListener('load', function() {
        // Load Google Translate script
        const script = document.createElement('script');
        script.src = '//translate.google.com/translate_a/element.js?cb=googleTranslateElementInit';
        script.async = true;
        document.body.appendChild(script);

        // Wait for Google Translate to initialize
        let attempts = 0;
        const checkGoogleTranslate = setInterval(function() {
            if (document.querySelector('.goog-te-combo') || attempts > 10) {
                clearInterval(checkGoogleTranslate);
                if (document.querySelector('.goog-te-combo')) {
                    // Check for saved language
                    const match = document.cookie.match(/googtrans=\/en\/([^;]+)/);
                    if (match && match[1]) {
                        translateTo(match[1]);
                    }
                }
            }
            attempts++;
        }, 1000);

        // Continuously remove banner
        setInterval(removeBanner, 100);
    });
</script>
