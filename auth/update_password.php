<?php
session_start();
require_once dirname(dirname(__FILE__)) . '/includes/config.php';

// Check if there's a temporary user in session
if (!isset($_SESSION['temp_user'])) {
    header('Location: login.php');
    exit();
}

$user = $_SESSION['temp_user'];
$error = '';
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $new_password = trim($_POST['new_password'] ?? '');
    $confirm_password = trim($_POST['confirm_password'] ?? '');

    if (empty($new_password) || empty($confirm_password)) {
        $error = 'Please fill in all fields.';
    } else if ($new_password !== $confirm_password) {
        $error = 'Passwords do not match.';
    } else if (strlen($new_password) < 8) {
        $error = 'Password must be at least 8 characters long.';
    } else {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=utf8mb4";
            $pdo = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
            ]);

            // Start transaction
            $pdo->beginTransaction();

            // Update password and activate account
            $stmt = $pdo->prepare("
                UPDATE users 
                SET password = ?, status = 'active' 
                WHERE id = ?
            ");
            $stmt->execute([
                password_hash($new_password, PASSWORD_DEFAULT),
                $user['id']
            ]);

            // Log the password update
            $stmt = $pdo->prepare("
                INSERT INTO activity_logs (user_id, action, description, ip_address) 
                VALUES (?, 'update_password', ?, ?)
            ");
            $stmt->execute([
                $user['id'],
                "Password updated and account activated",
                $_SERVER['REMOTE_ADDR']
            ]);

            $pdo->commit();

            // Set session variables for login
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['member_id'] = $user['member_id'];
            $_SESSION['full_name'] = $user['full_name'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['status'] = 'active';

            // Clear temporary user
            unset($_SESSION['temp_user']);

            // Redirect based on role
            if ($user['role'] === 'admin') {
                header('Location: ../admin/dashboard.php');
            } else {
                header('Location: ../member/member_dashboard.php');
            }
            exit();

        } catch (PDOException $e) {
            $pdo->rollBack();
            $error = "Database error: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Update Password - <?php echo APP_NAME; ?></title>
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
                    Update Your Password
                </h2>
                <p class="mt-2 text-center text-sm text-gray-600">
                    Please set a new password for your account
                </p>
            </div>

            <?php if ($error): ?>
                <div class="rounded-md bg-red-50 p-4">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-red-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                            </svg>
                        </div>
                        <div class="ml-3">
                            <h3 class="text-sm font-medium text-red-800"><?php echo htmlspecialchars($error); ?></h3>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <form class="mt-8 space-y-6" action="update_password.php" method="POST">
                <div class="rounded-md shadow-sm -space-y-px">
                    <div>
                        <label for="new_password" class="block text-sm font-medium text-gray-700 mb-2">
                            New Password
                        </label>
                        <div class="relative">
                            <input id="new_password" 
                                   name="new_password" 
                                   type="password" 
                                   required 
                                   class="focus:ring-indigo-500 focus:border-indigo-500 block w-full pl-4 pr-12 py-3 sm:text-sm border-2 border-gray-300 rounded-lg transition duration-150 ease-in-out hover:border-gray-400" 
                                   placeholder="Enter new password">
                            <button type="button" 
                                    class="absolute inset-y-0 right-0 pr-3 flex items-center" 
                                    onclick="togglePassword('new_password')">
                                <svg class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                </svg>
                            </button>
                        </div>
                    </div>
                    <div class="mt-4">
                        <label for="confirm_password" class="block text-sm font-medium text-gray-700 mb-2">
                            Confirm Password
                        </label>
                        <div class="relative">
                            <input id="confirm_password" 
                                   name="confirm_password" 
                                   type="password" 
                                   required 
                                   class="focus:ring-indigo-500 focus:border-indigo-500 block w-full pl-4 pr-12 py-3 sm:text-sm border-2 border-gray-300 rounded-lg transition duration-150 ease-in-out hover:border-gray-400" 
                                   placeholder="Confirm new password">
                            <button type="button" 
                                    class="absolute inset-y-0 right-0 pr-3 flex items-center" 
                                    onclick="togglePassword('confirm_password')">
                                <svg class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>

                <div>
                    <button type="submit" class="group relative w-full flex justify-center py-3 px-4 border border-transparent text-sm font-medium rounded-lg text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition duration-150 ease-in-out">
                        Update Password and Activate Account
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function togglePassword(inputId) {
            const input = document.getElementById(inputId);
            input.type = input.type === 'password' ? 'text' : 'password';
        }
    </script>
</body>
</html>
