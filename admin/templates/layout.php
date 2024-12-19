<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../auth/auth_check.php';

// Get the current page for active menu highlighting
$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title . ' - ' : ''; ?><?php echo APP_NAME; ?> Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        primary: '#1e40af',
                        secondary: '#1e3a8a'
                    }
                }
            }
        }
    </script>
</head>
<body class="bg-gray-100 dark:bg-gray-900 transition-colors duration-200">
    <div class="min-h-screen flex">
        <!-- Sidebar -->
        <div id="sidebar" class="bg-primary text-white w-64 space-y-6 py-7 px-2 absolute inset-y-0 left-0 transform -translate-x-full md:relative md:translate-x-0 transition duration-200 ease-in-out z-20">
            <!-- Logo -->
            <div class="flex items-center space-x-2 px-4">
                <i class="fas fa-money-bill-wave text-2xl"></i>
                <span class="text-2xl"><?php echo APP_NAME; ?></span>
            </div>

            <!-- Navigation -->
            <nav class="mt-8">
                <a href="dashboard.php" 
                   class="flex items-center py-2.5 px-4 rounded transition duration-200 <?php echo $current_page == 'dashboard.php' ? 'bg-secondary' : 'hover:bg-blue-700'; ?>">
                    <i class="fas fa-home w-6"></i>
                    <span>Dashboard</span>
                </a>
                <a href="users.php" 
                   class="flex items-center py-2.5 px-4 rounded transition duration-200 <?php echo $current_page == 'users.php' ? 'bg-secondary' : 'hover:bg-blue-700'; ?>">
                    <i class="fas fa-users w-6"></i>
                    <span>Manage Users</span>
                </a>
                <a href="loans.php" 
                   class="flex items-center py-2.5 px-4 rounded transition duration-200 <?php echo $current_page == 'loans.php' ? 'bg-secondary' : 'hover:bg-blue-700'; ?>">
                    <i class="fas fa-hand-holding-usd w-6"></i>
                    <span>Loans</span>
                </a>
                <a href="payments.php" 
                   class="flex items-center py-2.5 px-4 rounded transition duration-200 <?php echo $current_page == 'payments.php' ? 'bg-secondary' : 'hover:bg-blue-700'; ?>">
                    <i class="fas fa-money-bill-wave w-6"></i>
                    <span>Payments</span>
                </a>
                <a href="reports.php" 
                   class="flex items-center py-2.5 px-4 rounded transition duration-200 <?php echo $current_page == 'reports.php' ? 'bg-secondary' : 'hover:bg-blue-700'; ?>">
                    <i class="fas fa-chart-bar w-6"></i>
                    <span>Reports</span>
                </a>
                <a href="settings.php" 
                   class="flex items-center py-2.5 px-4 rounded transition duration-200 <?php echo $current_page == 'settings.php' ? 'bg-secondary' : 'hover:bg-blue-700'; ?>">
                    <i class="fas fa-cog w-6"></i>
                    <span>Settings</span>
                </a>
            </nav>

            <!-- Profile section -->
            <div class="border-t border-blue-700 pt-4 mt-auto">
                <div class="flex items-center px-4 space-x-2 text-sm">
                    <i class="fas fa-user-circle text-2xl"></i>
                    <div>
                        <p class="font-semibold"><?php echo htmlspecialchars($_SESSION['full_name']); ?></p>
                        <p class="text-blue-200 text-xs">Administrator</p>
                    </div>
                </div>
                <a href="../auth/logout.php" class="block py-2.5 px-4 rounded transition duration-200 hover:bg-blue-700 mt-2">
                    <i class="fas fa-sign-out-alt w-6"></i>
                    Logout
                </a>
            </div>
        </div>

        <!-- Main Content -->
        <div class="flex-1 flex flex-col min-h-screen">
            <!-- Top Navigation -->
            <div class="bg-white dark:bg-gray-800 shadow-sm">
                <div class="flex items-center justify-between h-16 px-4 md:px-8">
                    <!-- Mobile menu button -->
                    <button class="md:hidden text-gray-500 hover:text-gray-600 dark:text-gray-400 dark:hover:text-gray-300" id="mobile-menu-button">
                        <i class="fas fa-bars text-2xl"></i>
                    </button>
                    
                    <!-- Page Title -->
                    <h1 class="text-xl md:text-2xl font-semibold text-gray-800 dark:text-white"><?php echo isset($page_title) ? $page_title : 'Dashboard'; ?></h1>
                    
                    <!-- Quick Actions -->
                    <div class="flex items-center space-x-4">
                        <button class="text-gray-500 hover:text-gray-600 dark:text-gray-400 dark:hover:text-gray-300">
                            <i class="fas fa-bell text-xl"></i>
                        </button>
                        <button id="theme-toggle" class="text-gray-500 hover:text-gray-600 dark:text-gray-400 dark:hover:text-gray-300">
                            <i class="fas fa-moon text-xl dark:hidden"></i>
                            <i class="fas fa-sun hidden text-xl dark:block"></i>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Page Content -->
            <div class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-100 dark:bg-gray-900 p-4 md:p-6">
                <?php if (isset($error)): ?>
                    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
                        <span class="block sm:inline"><?php echo htmlspecialchars($error); ?></span>
                    </div>
                <?php endif; ?>
                
                <?php echo $content; ?>
            </div>
        </div>
    </div>

    <script>
        // Mobile menu toggle
        document.getElementById('mobile-menu-button').addEventListener('click', function() {
            const sidebar = document.getElementById('sidebar');
            sidebar.classList.toggle('-translate-x-full');
        });

        // Dark mode toggle
        document.getElementById('theme-toggle').addEventListener('click', function() {
            document.documentElement.classList.toggle('dark');
            localStorage.setItem('theme', document.documentElement.classList.contains('dark') ? 'dark' : 'light');
        });

        // Check for saved theme preference
        if (localStorage.theme === 'dark' || (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            document.documentElement.classList.add('dark');
        } else {
            document.documentElement.classList.remove('dark');
        }
    </script>
</body>
</html>
