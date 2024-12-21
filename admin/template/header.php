<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/session.php';

// Require admin authentication
requireAdmin();

// Get current page for active menu highlighting
$current_page = basename($_SERVER['PHP_SELF']);
?>
<nav class="bg-white shadow-lg">
    <div class="max-w-7xl mx-auto px-4">
        <div class="flex justify-between h-16">
            <div class="flex">
                <div class="flex-shrink-0 flex items-center">
                    <h1 class="text-xl font-bold"><?php echo APP_NAME; ?></h1>
                </div>
                <div class="hidden sm:ml-6 sm:flex sm:space-x-8">
                    <a href="dashboard.php" 
                       class="<?php echo $current_page === 'dashboard.php' ? 'border-indigo-500 text-gray-900' : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700'; ?> inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">
                        Dashboard
                    </a>
                    <a href="users.php"
                       class="<?php echo $current_page === 'users.php' ? 'border-indigo-500 text-gray-900' : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700'; ?> inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">
                        Users
                    </a>
                    <a href="transactions.php"
                       class="<?php echo $current_page === 'transactions.php' ? 'border-indigo-500 text-gray-900' : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700'; ?> inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">
                        Transactions
                    </a>
                </div>
            </div>
            <div class="flex items-center">
                <span class="text-gray-700 mr-4">Welcome, <?php echo htmlspecialchars($_SESSION['full_name']); ?></span>
                <a href="logout.php" class="bg-red-500 text-white px-4 py-2 rounded-md text-sm font-medium hover:bg-red-600 transition-colors">Logout</a>
            </div>
        </div>
    </div>
</nav>
