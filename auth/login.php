<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start session
session_start();

// Initialize active sessions array if not exists
if (!isset($_SESSION['active_sessions'])) {
    $_SESSION['active_sessions'] = [];
}

// Set base path and include required files
$base_path = dirname(dirname(__FILE__));
require_once $base_path . '/includes/config.php';

try {
    $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=utf8mb4";
    $pdo = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false
    ]);
} catch (PDOException $e) {
    error_log("Database connection error: " . $e->getMessage());
    die("Connection failed. Please try again later.");
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $identifier = trim($_POST['identifier'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (empty($identifier) || empty($password)) {
        $error = 'Please enter all fields.';
    } else {
        try {
            // Check if the identifier matches any of member_id, email, or phone
            $stmt = $pdo->prepare("
                SELECT * FROM users 
                WHERE member_id = ? 
                OR email = ? 
                OR phone = ?
            ");
            $stmt->execute([$identifier, $identifier, $identifier]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user && password_verify($password, $user['password'])) {
                // Check account status
                if ($user['status'] === 'inactive') {
                    $_SESSION['temp_user'] = $user;
                    header('Location: verify_profile.php');
                    exit();
                }

                // Check if this account is already in active sessions
                $existing_session_index = -1;
                foreach ($_SESSION['active_sessions'] as $index => $session) {
                    if ($session['user_id'] === $user['id']) {
                        $existing_session_index = $index;
                        break;
                    }
                }

                // Create session data
                $session_data = [
                    'user_id' => $user['id'],
                    'member_id' => $user['member_id'],
                    'full_name' => $user['full_name'],
                    'role' => $user['role'],
                    'status' => $user['status'],
                    'login_time' => time()
                ];

                // If user already has a session, update it
                if ($existing_session_index >= 0) {
                    $_SESSION['active_sessions'][$existing_session_index] = $session_data;
                    $selected_index = $existing_session_index;
                } else {
                    // Add new session
                    $_SESSION['active_sessions'][] = $session_data;
                    $selected_index = count($_SESSION['active_sessions']) - 1;
                }

                // Set current session
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['member_id'] = $user['member_id'];
                $_SESSION['full_name'] = $user['full_name'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['logged_in'] = true;

                // Log the successful login
                $stmt = $pdo->prepare("
                    INSERT INTO activity_logs (user_id, action, description, ip_address) 
                    VALUES (?, 'login', ?, ?)
                ");
                $stmt->execute([
                    $user['id'],
                    "User logged in successfully",
                    $_SERVER['REMOTE_ADDR']
                ]);

                // Redirect based on role
                if ($user['role'] === 'admin') {
                    header('Location: /admin/dashboard.php');
                } else {
                    header('Location: /member/member_dashboard.php');
                }
                exit();
            } else {
                $error = 'Invalid credentials. Please try again.';
            }
        } catch (PDOException $e) {
            $error = "Database error: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en" class="h-full bg-gray-50">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo APP_NAME; ?> - Login</title>
    
    <!-- PWA Meta Tags -->
    <meta name="description" content="Manage and track debts efficiently">
    <meta name="theme-color" content="#4f46e5">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black">
    <meta name="apple-mobile-web-app-title" content="DebtMS">
    
    <!-- PWA Links -->
    <link rel="manifest" href="/manifest.json">
    <link rel="apple-touch-icon" href="/icons/icon-192x192.png">
    
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="h-full">
    <div class="min-h-screen flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-md w-full space-y-8">
            <!-- Language Selection -->
            <div class="text-center mb-8">
                <p class="text-sm text-gray-600 mb-3">Select Your Language:</p>
                <div class="flex justify-center items-center space-x-4">
                    <button onclick="translateTo('en')" class="w-10 h-10 rounded-full overflow-hidden hover:opacity-80 transition-opacity focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 border-2 border-gray-200">
                        <img src="https://flagcdn.com/w40/gb.png" alt="English" class="w-full h-full object-cover">
                    </button>
                    <button onclick="translateTo('hi')" class="w-10 h-10 rounded-full overflow-hidden hover:opacity-80 transition-opacity focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 border-2 border-gray-200">
                        <img src="https://flagcdn.com/w40/in.png" alt="Hindi" class="w-full h-full object-cover">
                    </button>
                    <button onclick="translateTo('ne')" class="w-10 h-10 rounded-full overflow-hidden hover:opacity-80 transition-opacity focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 border-2 border-gray-200">
                        <img src="https://flagcdn.com/w40/np.png" alt="Nepali" class="w-full h-full object-cover">
                    </button>
                    <button onclick="translateTo('my')" class="w-10 h-10 rounded-full overflow-hidden hover:opacity-80 transition-opacity focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 border-2 border-gray-200">
                        <img src="https://flagcdn.com/w40/mm.png" alt="Burmese" class="w-full h-full object-cover">
                    </button>
                </div>
            </div>

            <!-- Title -->
            <div>
                <h2 class="mt-6 text-center text-3xl font-extrabold text-gray-900">
                    Sign in to <?php echo APP_NAME; ?>
                </h2>
            </div>

            <!-- Error Messages -->
            <?php if (!empty($error)): ?>
                <div class="rounded-md bg-red-50 p-4">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-red-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                            </svg>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm font-medium text-red-800"><?php echo htmlspecialchars($error); ?></p>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Active Sessions -->
            <?php if (isset($_SESSION['active_sessions']) && count($_SESSION['active_sessions']) > 0): ?>
            <div class="bg-white shadow-lg rounded-lg overflow-hidden border-2 border-indigo-200">
                <div class="px-4 py-5 sm:p-6">
                    <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4 border-b-2 border-indigo-100 pb-2 text-center">
                        Continue with existing account
                    </h3>
                    <div class="space-y-3">
                        <?php foreach ($_SESSION['active_sessions'] as $index => $session): ?>
                        <button onclick="switchAccount(<?php echo $index; ?>)" 
                                class="w-full flex items-center p-4 bg-gray-50 rounded-lg hover:bg-indigo-50 transition-colors duration-200 border-2 border-gray-200 hover:border-indigo-300 shadow-sm">
                            <div class="flex-1">
                                <div class="font-medium text-gray-900">
                                    <?php echo htmlspecialchars($session['full_name']); ?> (<?php echo htmlspecialchars($session['member_id']); ?>)
                                </div>
                            </div>
                        </button>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <div class="relative">
                <div class="absolute inset-0 flex items-center">
                    <div class="w-full border-t border-gray-300"></div>
                </div>
                <div class="relative flex justify-center text-sm">
                    <span class="px-2 bg-gray-50 text-gray-500">Or login with different account</span>
                </div>
            </div>
            <?php endif; ?>

            <!-- Login Form -->
            <form class="mt-8 space-y-6" action="" method="POST">
                <input type="hidden" name="remember" value="true">
                <div class="rounded-md shadow-sm -space-y-px">
                    <div>
                        <label for="identifier" class="sr-only">Username or Member ID</label>
                        <input id="identifier" 
                               name="identifier" 
                               type="text" 
                               required 
                               class="appearance-none rounded-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-t-md focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 focus:z-10 sm:text-sm" 
                               placeholder="Username or Member ID">
                    </div>
                    <div>
                        <label for="password" class="sr-only">Password</label>
                        <input id="password" 
                               name="password" 
                               type="password" 
                               required 
                               class="appearance-none rounded-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-b-md focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 focus:z-10 sm:text-sm" 
                               placeholder="Password">
                    </div>
                </div>

                <div>
                    <button type="submit" class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                        Sign in
                    </button>
                    
                    <div class="text-center mt-2">
                        <p class="text-xs text-gray-500">Version 1.2.11</p>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- PWA Install Prompt -->
    <div id="pwa-install-prompt" class="fixed bottom-0 left-0 right-0 bg-indigo-600 text-white p-4 flex justify-between items-center transform translate-y-full transition-transform duration-300 ease-in-out">
        <div class="flex-1">
            <p class="font-medium">Install App for Better Experience</p>
            <p class="text-sm text-indigo-100">Access <?php echo APP_NAME; ?> directly from your home screen</p>
        </div>
        <div class="flex space-x-2 ml-4">
            <button onclick="installPWA()" class="px-4 py-2 bg-white text-indigo-600 rounded-lg font-medium hover:bg-indigo-50 focus:outline-none focus:ring-2 focus:ring-white focus:ring-offset-2 focus:ring-offset-indigo-600">
                Install
            </button>
            <button onclick="dismissInstallPrompt()" class="px-4 py-2 border border-white rounded-lg font-medium hover:bg-indigo-500 focus:outline-none focus:ring-2 focus:ring-white focus:ring-offset-2 focus:ring-offset-indigo-600">
                Later
            </button>
        </div>
    </div>

    <!-- Hidden Google Translate Element -->
    <div id="google_translate_element" class="hidden"></div>

    <!-- PWA and Translation Scripts -->
    <script type="text/javascript">
        // PWA Installation
        let deferredPrompt;
        const installPrompt = document.getElementById('pwa-install-prompt');
        
        window.addEventListener('beforeinstallprompt', (e) => {
            e.preventDefault();
            deferredPrompt = e;
            
            // Show the install prompt if not previously dismissed
            if (!localStorage.getItem('pwa-prompt-dismissed')) {
                showInstallPrompt();
            }
        });

        function showInstallPrompt() {
            installPrompt.style.transform = 'translateY(0)';
        }

        function dismissInstallPrompt() {
            installPrompt.style.transform = 'translateY(100%)';
            localStorage.setItem('pwa-prompt-dismissed', 'true');
        }

        async function installPWA() {
            if (!deferredPrompt) return;
            
            deferredPrompt.prompt();
            const { outcome } = await deferredPrompt.userChoice;
            
            if (outcome === 'accepted') {
                console.log('PWA installed successfully');
            }
            
            deferredPrompt = null;
            dismissInstallPrompt();
        }

        // Service Worker Registration
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => {
                navigator.serviceWorker.register('/sw.js')
                    .then(registration => {
                        console.log('ServiceWorker registration successful');
                    })
                    .catch(err => {
                        console.error('ServiceWorker registration failed: ', err);
                    });
            });
        }

        // Google Translate Functions
        function googleTranslateElementInit() {
            new google.translate.TranslateElement({
                pageLanguage: 'en',
                includedLanguages: 'en,hi,ne,my',
                layout: google.translate.TranslateElement.InlineLayout.SIMPLE,
                autoDisplay: false
            }, 'google_translate_element');

            // Hide Google Translate elements
            const style = document.createElement('style');
            style.textContent = `
                .skiptranslate,
                .goog-te-banner-frame {
                    display: none !important;
                }
                body {
                    top: 0px !important;
                }
                .VIpgJd-ZVi9od-l4eHX-hSRGPd,
                .goog-te-gadget {
                    display: none !important;
                }
                iframe[name="google_translation_frame"] {
                    display: none !important;
                }
            `;
            document.head.appendChild(style);
        }

        function translateTo(lang) {
            const hostname = window.location.hostname;
            const domain = hostname.split('.').slice(-2).join('.');
            const domains = [hostname, '.' + hostname, domain, '.' + domain];
            
            domains.forEach(dom => {
                document.cookie = `googtrans=/en/${lang}; path=/; domain=${dom}`;
                document.cookie = `googtrans=/en/${lang}; path=/;`;
            });

            window.location.reload();
        }

        // Account Switching Function
        function switchAccount(index) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = 'select_session.php';
            
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'selected_session';
            input.value = index;
            
            form.appendChild(input);
            document.body.appendChild(form);
            form.submit();
        }

        // Load Google Translate
        window.addEventListener('load', function() {
            const script = document.createElement('script');
            script.src = '//translate.google.com/translate_a/element.js?cb=googleTranslateElementInit';
            script.async = true;
            document.body.appendChild(script);
        });
    </script>
</body>
</html>
