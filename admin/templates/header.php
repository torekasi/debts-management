<?php
if (!isset($_SESSION)) {
    session_start();
}

if (!isset($_SESSION['user_id']) || !isset($_SESSION['admin_id']) || $_SESSION['role'] !== 'admin') {
    $_SESSION['error_message'] = "Please login as admin to access this page.";
    header("Location: /auth/login.php");
    exit();
}

$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title . ' - ' . APP_NAME : APP_NAME; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        /* Custom scrollbar styles */
        ::-webkit-scrollbar {
            width: 5px;
            height: 5px;
        }
        ::-webkit-scrollbar-track {
            background: #f1f1f1;
        }
        ::-webkit-scrollbar-thumb {
            background: #888;
            border-radius: 5px;
        }
        ::-webkit-scrollbar-thumb:hover {
            background: #555;
        }
    </style>
</head>
<body class="bg-gray-100">
    <?php require_once 'sidebar.php'; ?>

    <!-- Main Content -->
    <div class="lg:pl-64 flex flex-col min-h-screen">
        <!-- Top bar -->
        <header class="sticky top-0 z-10 bg-white shadow-sm">
            <div class="flex items-center justify-between h-16 px-4 sm:px-6 lg:px-8">
                <div class="flex items-center">
                    <h1 class="text-2xl font-semibold text-gray-900"><?php echo $page_title; ?></h1>
                </div>
                <div class="flex items-center space-x-4">
                    <span class="text-gray-600">Welcome, <?php echo htmlspecialchars($_SESSION['full_name']); ?></span>
                    <a href="/auth/logout.php" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-red-600 hover:bg-red-700">
                        Logout
                    </a>
                </div>
            </div>
        </header>

        <!-- Flash Messages -->
        <?php if (isset($_SESSION['success_message'])): ?>
            <div class="mx-4 sm:mx-6 lg:mx-8 mt-4">
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative" role="alert">
                    <span class="block sm:inline"><?php echo $_SESSION['success_message']; ?></span>
                    <?php unset($_SESSION['success_message']); ?>
                </div>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['error_message'])): ?>
            <div class="mx-4 sm:mx-6 lg:mx-8 mt-4">
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative" role="alert">
                    <span class="block sm:inline"><?php echo $_SESSION['error_message']; ?></span>
                    <?php unset($_SESSION['error_message']); ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- Page Content -->
        <main class="flex-1 py-6 px-4 sm:px-6 lg:px-8">
