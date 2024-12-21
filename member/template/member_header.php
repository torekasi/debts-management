<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/session.php';
requireUser();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title . ' - ' . APP_NAME : APP_NAME; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body {
            margin: 0;
            padding: 0;
            background: #f0f0f0;
            min-height: 100vh;
        }

        /* Hide scrollbar for Chrome, Safari and Opera */
        .mobile-content::-webkit-scrollbar {
            display: none;
        }

        /* Hide scrollbar for IE, Edge and Firefox */
        .mobile-content {
            -ms-overflow-style: none;  /* IE and Edge */
            scrollbar-width: none;  /* Firefox */
        }

        .content-wrapper::-webkit-scrollbar {
            display: none;
        }

        .content-wrapper {
            -ms-overflow-style: none;
            scrollbar-width: none;
        }

        .top-header {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            z-index: 50;
            background: white;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }

        .app-title {
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.2);
            color: #1a1a1a;
            font-weight: bold;
            letter-spacing: 0.5px;
        }

        .content-wrapper {
            padding-top: 64px; /* Height of the top header */
            padding-bottom: 80px; /* Height of the bottom menu */
            height: 100%;
            overflow-y: auto;
        }

        .menu-icon {
            filter: drop-shadow(0px 2px 2px rgba(0, 0, 0, 0.1));
            transition: all 0.3s ease;
        }

        .menu-item {
            transition: all 0.3s ease;
        }

        .menu-item:hover {
            transform: translateY(-2px);
        }

        .menu-item.active {
            color: #4F46E5; /* Indigo-600 */
        }

        .menu-item.active .menu-icon {
            filter: drop-shadow(0px 3px 3px rgba(79, 70, 229, 0.3));
        }

        /* Professional color scheme for menu items */
        .menu-home.active {
            color: #4F46E5; /* Indigo-600 */
        }
        .menu-payments.active {
            color: #059669; /* Emerald-600 */
        }
        .menu-profile.active {
            color: #0284C7; /* Sky-600 */
        }
        .menu-logout {
            color: #DC2626; /* Red-600 */
        }

        /* Desktop styles */
        @media (min-width: 768px) {
            .desktop-wrapper {
                position: fixed;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                display: flex;
                align-items: center;
                justify-content: center;
                background: #f0f0f0;
                padding: 20px;
            }

            .mobile-frame {
                width: 375px;
                height: 812px;
                background: white;
                border-radius: 40px;
                position: relative;
                overflow: hidden;
                box-shadow: 0 0 0 11px #1a1a1a, 0 0 0 13px #000, 0 0 34px rgba(0, 0, 0, 0.1);
            }

            .mobile-frame:before {
                content: '';
                position: absolute;
                top: 0;
                left: 50%;
                transform: translateX(-50%);
                width: 150px;
                height: 30px;
                background: #1a1a1a;
                border-bottom-left-radius: 20px;
                border-bottom-right-radius: 20px;
                z-index: 10;
            }

            .mobile-content {
                height: 100%;
                position: relative;
                background: #f8fafc;
            }

            .bottom-menu {
                position: absolute;
                bottom: 0;
                left: 0;
                right: 0;
                background: white;
                padding: 0.75rem;
                border-radius: 0 0 40px 40px;
                box-shadow: 0 -1px 3px rgba(0, 0, 0, 0.1);
            }
        }

        /* Mobile styles */
        @media (max-width: 767px) {
            .desktop-wrapper {
                display: none;
            }

            .mobile-content {
                min-height: 100vh;
                position: relative;
                background: #f8fafc;
            }

            .bottom-menu {
                position: fixed;
                bottom: 0;
                left: 0;
                right: 0;
                background: white;
                padding: 0.75rem;
                box-shadow: 0 -2px 10px rgba(0, 0, 0, 0.1);
            }
        }
    </style>
</head>
<body>
    <!-- Desktop View with Mobile Frame -->
    <div class="desktop-wrapper hidden md:flex">
        <div class="mobile-frame">
            <div class="mobile-content">
                <!-- Top Header -->
                <header class="top-header">
                    <div class="px-4 py-4 flex items-center justify-center">
                        <h1 class="text-xl app-title"><?php echo APP_NAME; ?></h1>
                    </div>
                </header>

                <!-- Content -->
                <div class="content-wrapper">
                    <?php if (isset($content)): ?>
                        <?php echo $content; ?>
                    <?php endif; ?>
                </div>

                <!-- Bottom Menu -->
                <div class="bottom-menu">
                    <div class="grid grid-cols-4 gap-4">
                        <a href="/member/member_dashboard.php" class="menu-item menu-home flex flex-col items-center text-sm <?php echo basename($_SERVER['PHP_SELF']) === 'member_dashboard.php' ? 'active' : ''; ?>">
                            <svg class="h-6 w-6 menu-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                            </svg>
                            <span>Home</span>
                        </a>
                        <a href="/member/payments.php" class="menu-item menu-payments flex flex-col items-center text-sm <?php echo basename($_SERVER['PHP_SELF']) === 'payments.php' ? 'active' : ''; ?>">
                            <svg class="h-6 w-6 menu-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <span>Payments</span>
                        </a>
                        <a href="/member/profile.php" class="menu-item menu-profile flex flex-col items-center text-sm <?php echo basename($_SERVER['PHP_SELF']) === 'profile.php' ? 'active' : ''; ?>">
                            <svg class="h-6 w-6 menu-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                            </svg>
                            <span>Profile</span>
                        </a>
                        <a href="/auth/logout.php" class="menu-item menu-logout flex flex-col items-center text-sm">
                            <svg class="h-6 w-6 menu-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                            </svg>
                            <span>Logout</span>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Mobile View -->
    <div class="md:hidden">
        <div class="mobile-content">
            <!-- Top Header -->
            <header class="top-header">
                <div class="px-4 py-4 flex items-center justify-center">
                    <h1 class="text-xl app-title"><?php echo APP_NAME; ?></h1>
                </div>
            </header>

            <!-- Content -->
            <div class="content-wrapper">
                <?php if (isset($content)): ?>
                    <?php echo $content; ?>
                <?php endif; ?>
            </div>

            <!-- Bottom Menu -->
            <div class="bottom-menu">
                <div class="grid grid-cols-4 gap-4">
                    <a href="/member/member_dashboard.php" class="menu-item menu-home flex flex-col items-center text-sm <?php echo basename($_SERVER['PHP_SELF']) === 'member_dashboard.php' ? 'active' : ''; ?>">
                        <svg class="h-6 w-6 menu-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                        </svg>
                        <span>Home</span>
                    </a>
                    <a href="/member/payments.php" class="menu-item menu-payments flex flex-col items-center text-sm <?php echo basename($_SERVER['PHP_SELF']) === 'payments.php' ? 'active' : ''; ?>">
                        <svg class="h-6 w-6 menu-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <span>Payments</span>
                    </a>
                    <a href="/member/profile.php" class="menu-item menu-profile flex flex-col items-center text-sm <?php echo basename($_SERVER['PHP_SELF']) === 'profile.php' ? 'active' : ''; ?>">
                        <svg class="h-6 w-6 menu-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                        </svg>
                        <span>Profile</span>
                    </a>
                    <a href="/auth/logout.php" class="menu-item menu-logout flex flex-col items-center text-sm">
                        <svg class="h-6 w-6 menu-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                        </svg>
                        <span>Logout</span>
                    </a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
