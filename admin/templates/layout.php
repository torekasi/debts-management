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
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <!-- Add Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="bg-gray-100">
    <div class="min-h-screen flex">
        <!-- Sidebar -->
        <div class="bg-blue-800 text-white w-64 space-y-6 py-7 px-2 absolute inset-y-0 left-0 transform -translate-x-full md:relative md:translate-x-0 transition duration-200 ease-in-out">
            <!-- Logo -->
            <div class="flex items-center space-x-2 px-4">
                <i class="fas fa-money-bill-wave text-2xl"></i>
                <span class="text-2xl"><?php echo APP_NAME; ?></span>
            </div>

            <!-- Navigation -->
            <nav class="mt-8">
                <a href="dashboard.php" 
                   class="block py-2.5 px-4 rounded transition duration-200 <?php echo $current_page == 'dashboard.php' ? 'bg-blue-900' : 'hover:bg-blue-700'; ?>">
                    <i class="fas fa-home w-6"></i>
                    Dashboard
                </a>
                <a href="users.php" 
                   class="block py-2.5 px-4 rounded transition duration-200 <?php echo $current_page == 'users.php' ? 'bg-blue-900' : 'hover:bg-blue-700'; ?>">
                    <i class="fas fa-users w-6"></i>
                    Manage Users
                </a>
                <a href="loans.php" 
                   class="block py-2.5 px-4 rounded transition duration-200 <?php echo $current_page == 'loans.php' ? 'bg-blue-900' : 'hover:bg-blue-700'; ?>">
                    <i class="fas fa-hand-holding-usd w-6"></i>
                    Loans
                </a>
                <a href="payments.php" 
                   class="block py-2.5 px-4 rounded transition duration-200 <?php echo $current_page == 'payments.php' ? 'bg-blue-900' : 'hover:bg-blue-700'; ?>">
                    <i class="fas fa-money-bill-wave w-6"></i>
                    Payments
                </a>
                <a href="reports.php" 
                   class="block py-2.5 px-4 rounded transition duration-200 <?php echo $current_page == 'reports.php' ? 'bg-blue-900' : 'hover:bg-blue-700'; ?>">
                    <i class="fas fa-chart-bar w-6"></i>
                    Reports
                </a>
                <a href="settings.php" 
                   class="block py-2.5 px-4 rounded transition duration-200 <?php echo $current_page == 'settings.php' ? 'bg-blue-900' : 'hover:bg-blue-700'; ?>">
                    <i class="fas fa-cog w-6"></i>
                    Settings
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
        <div class="flex-1 flex flex-col">
            <!-- Top Navigation -->
            <div class="bg-white shadow-sm">
                <div class="flex items-center justify-between h-16 px-8">
                    <!-- Mobile menu button -->
                    <button class="md:hidden" id="mobile-menu-button">
                        <i class="fas fa-bars text-gray-500 text-2xl"></i>
                    </button>
                    
                    <!-- Page Title -->
                    <h1 class="text-2xl font-semibold text-gray-800"><?php echo isset($page_title) ? $page_title : 'Dashboard'; ?></h1>
                    
                    <!-- Quick Actions -->
                    <div class="flex items-center space-x-4">
                        <button class="text-gray-500 hover:text-gray-600">
                            <i class="fas fa-bell text-xl"></i>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Page Content -->
            <main class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-100 p-6">
                <?php if (isset($error)): ?>
                    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
                        <span class="block sm:inline"><?php echo htmlspecialchars($error); ?></span>
                    </div>
                <?php endif; ?>
                
                <?php echo $content; ?>
            </main>
        </div>
    </div>

    <script>
        // Mobile menu toggle
        document.getElementById('mobile-menu-button').addEventListener('click', function() {
            const sidebar = document.querySelector('.bg-blue-800');
            sidebar.classList.toggle('-translate-x-full');
        });
    </script>
</body>
</html>
