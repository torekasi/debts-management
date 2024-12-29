<header class="fixed top-0 left-0 right-0 bg-white shadow-md z-50">
    <div class="container mx-auto px-4 py-3 max-w-md md:max-w-[650px]">
        <div class="flex justify-between items-center">
            <h1 class="text-2xl font-bold text-gray-800">
                <?php echo APP_NAME; ?>
            </h1>
        </div>
    </div>
</header>

<!-- Hidden Google Translate Element -->
<div id="google_translate_element" class="hidden"></div>

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
