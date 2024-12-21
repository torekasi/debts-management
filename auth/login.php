<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start session
session_start();

// Set base path and include required files
$base_path = dirname(dirname(__FILE__));
require_once $base_path . '/includes/config.php';

// Function to check if the session is active and redirect accordingly
function checkSessionAndRedirect() {
    if (isset($_SESSION['user_id'])) {
        if ($_SESSION['role'] === 'admin') {
            header('Location: /admin/dashboard.php');
        } else {
            header('Location: /user/dashboard.php');
        }
        exit();
    }
}

// Check session status on page load
checkSessionAndRedirect();

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

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $member_id = $_POST['member_id'] ?? '';
    $password = $_POST['password'] ?? '';

    try {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE member_id = ? AND status = 'active'");
        $stmt->execute([$member_id]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['member_id'] = $user['member_id'];
            $_SESSION['full_name'] = $user['full_name'];
            $_SESSION['role'] = $user['role'];
            
            // Only log activity if the file exists and after successful login
            if (file_exists($base_path . '/includes/activity_log.php')) {
                try {
                    require_once $base_path . '/includes/activity_log.php';
                    $logger = new ActivityLogger($pdo);
                    $logger->log($user['id'], 'login', 'User logged in successfully', $_SERVER['REMOTE_ADDR'], $_SERVER['HTTP_USER_AGENT']);
                } catch (Exception $e) {
                    error_log("Activity logging error: " . $e->getMessage());
                    // Continue even if logging fails
                }
            }

            if ($user['role'] === 'admin') {
                $_SESSION['admin_id'] = $user['id'];
                $_SESSION['success_message'] = "Login successful! Redirecting to admin dashboard...";
                header('Location: /admin/dashboard.php');
            } else {
                $_SESSION['success_message'] = "Login successful! Redirecting to user dashboard...";
                header('Location: /user/dashboard.php');
            }
            exit();
        } else {
            $error = "Invalid member ID or password";
        }
    } catch (PDOException $e) {
        error_log("Login error: " . $e->getMessage());
        $error = "A system error occurred. Please try again later.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - <?php echo APP_NAME; ?></title>
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
    <div class="min-h-screen flex items-center justify-center bg-gray-100 py-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-md w-full space-y-8">
            <?php if (isset($_SESSION['success_message'])): ?>
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative" role="alert">
                    <span class="block sm:inline"><?php echo $_SESSION['success_message']; ?></span>
                    <?php unset($_SESSION['success_message']); ?>
                </div>
            <?php endif; ?>
            
            <?php if (isset($error)): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative" role="alert">
                    <span class="block sm:inline"><?php echo $error; ?></span>
                </div>
            <?php endif; ?>
            <div>
                <h2 class="mt-6 text-center text-3xl font-extrabold text-gray-900">
                    Welcome Back!
                </h2>
                <p class="mt-2 text-center text-sm text-gray-600">
                    Please sign in to your account
                </p>
            </div>
            <form class="mt-8 space-y-6" method="POST">
                <input type="hidden" name="remember" value="true">
                <div class="rounded-md shadow-sm -space-y-px">
                    <div>
                        <label for="member_id" class="sr-only">Member ID</label>
                        <input id="member_id" name="member_id" type="text" required class="appearance-none rounded-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-t-md focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 focus:z-10 sm:text-sm" placeholder="Member ID" value="<?php echo isset($_POST['member_id']) ? htmlspecialchars($_POST['member_id']) : ''; ?>">
                    </div>
                    <div>
                        <label for="password" class="sr-only">Password</label>
                        <input id="password" name="password" type="password" required class="appearance-none rounded-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-b-md focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 focus:z-10 sm:text-sm" placeholder="Password">
                    </div>
                </div>

                <div class="flex items-center justify-between">
                    <div class="flex items-center">
                        <input id="remember-me" name="remember-me" type="checkbox" class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded">
                        <label for="remember-me" class="ml-2 block text-sm text-gray-900">
                            Remember me
                        </label>
                    </div>

                    <div class="text-sm">
                        <a href="#" class="font-medium text-indigo-600 hover:text-indigo-500">
                            Forgot your password?
                        </a>
                    </div>
                </div>

                <div>
                    <button type="submit" class="group relative w-full flex justify-center py-2 px-4 border border-transparent text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                        Sign in
                    </button>
                </div>
            </form>
            <div class="text-center">
                <p class="mt-2 text-sm text-gray-600">
                    Need help? Contact your administrator
                </p>
            </div>
        </div>
    </div>
</body>
</html>
