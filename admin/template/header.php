<?php
require_once '../includes/config.php';
require_once '../includes/session.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - <?php echo APP_NAME; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>
<body class="bg-gray-100">
    <!-- Navigation -->
    <nav class="bg-white shadow">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex">
                    <!-- Logo -->
                    <div class="flex-shrink-0 flex items-center">
                        <a href="/" class="text-xl font-bold text-gray-800">
                            <?php echo APP_NAME; ?>
                        </a>
                    </div>
                    <!-- Navigation Links -->
                    <div class="hidden sm:ml-6 sm:flex sm:space-x-8">
                        <a href="dashboard.php" class="<?php echo basename($_SERVER['PHP_SELF']) === 'dashboard.php' ? 'border-indigo-500 text-gray-900' : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700'; ?> inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">
                            Dashboard
                        </a>
                        <a href="users.php" class="<?php echo basename($_SERVER['PHP_SELF']) === 'users.php' ? 'border-indigo-500 text-gray-900' : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700'; ?> inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">
                            Users
                        </a>
                        <a href="transactions.php" class="<?php echo basename($_SERVER['PHP_SELF']) === 'transactions.php' ? 'border-indigo-500 text-gray-900' : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700'; ?> inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">
                            Transactions
                        </a>
                        <a href="activity_logs.php" class="<?php echo basename($_SERVER['PHP_SELF']) === 'activity_logs.php' ? 'border-indigo-500 text-gray-900' : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700'; ?> inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">
                            <i class="fas fa-history mr-1"></i> Activity Logs
                        </a>
                    </div>
                </div>
                <!-- User Menu -->
                <div class="hidden sm:ml-6 sm:flex sm:items-center">
                    <div class="ml-3 relative">
                        <div class="flex items-center">
                            <span class="text-gray-700 mr-4"><?php echo htmlspecialchars($_SESSION['user_name'] ?? 'Admin'); ?></span>
                            <a href="../auth/logout.php" class="text-gray-500 hover:text-gray-700">
                                <i class="fas fa-sign-out-alt"></i> Logout
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </nav>
