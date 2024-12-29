<?php
session_start();
require_once dirname(dirname(__FILE__)) . '/includes/config.php';

// Check if there's a temporary user in session
if (!isset($_SESSION['temp_user'])) {
    header('Location: login.php');
    exit();
}

$user = $_SESSION['temp_user'];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'verify') {
            // User confirmed their identity, proceed to password update
            header('Location: update_password.php');
            exit();
        } else if ($_POST['action'] === 'reject') {
            // User rejected their identity, clear session and return to login
            unset($_SESSION['temp_user']);
            $_SESSION['error_message'] = 'Please contact administrator to update your profile information.';
            header('Location: login.php');
            exit();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify Profile - <?php echo APP_NAME; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
        }
    </style>
</head>
<body class="bg-gray-100">
    <div class="min-h-screen flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-md w-full space-y-8">
            <div>
                <h2 class="mt-6 text-center text-3xl font-extrabold text-gray-900">
                    Verify Your Profile
                </h2>
                <p class="mt-2 text-center text-sm text-gray-600">
                    Please confirm if this is your account
                </p>
            </div>

            <div class="bg-white shadow overflow-hidden sm:rounded-lg">
                <div class="px-4 py-5 sm:p-6">
                    <dl class="grid grid-cols-1 gap-x-4 gap-y-6 sm:grid-cols-2">
                        <div class="sm:col-span-2">
                            <dt class="text-sm font-medium text-gray-500">Member ID</dt>
                            <dd class="mt-1 text-sm text-gray-900"><?php echo htmlspecialchars($user['member_id']); ?></dd>
                        </div>
                        <div class="sm:col-span-2">
                            <dt class="text-sm font-medium text-gray-500">Full Name</dt>
                            <dd class="mt-1 text-sm text-gray-900"><?php echo htmlspecialchars($user['full_name']); ?></dd>
                        </div>
                        <div class="sm:col-span-1">
                            <dt class="text-sm font-medium text-gray-500">Email</dt>
                            <dd class="mt-1 text-sm text-gray-900"><?php echo htmlspecialchars($user['email']); ?></dd>
                        </div>
                        <div class="sm:col-span-1">
                            <dt class="text-sm font-medium text-gray-500">Phone</dt>
                            <dd class="mt-1 text-sm text-gray-900"><?php echo htmlspecialchars($user['phone']); ?></dd>
                        </div>
                    </dl>
                </div>
            </div>

            <div class="flex space-x-4">
                <form method="POST" class="flex-1">
                    <input type="hidden" name="action" value="verify">
                    <button type="submit" class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                        Yes, this is my account
                    </button>
                </form>
                <form method="POST" class="flex-1">
                    <input type="hidden" name="action" value="reject">
                    <button type="submit" class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500">
                        No, this is not my account
                    </button>
                </form>
            </div>
        </div>
    </div>
</body>
</html>
