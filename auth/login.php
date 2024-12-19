<?php
session_start();
require_once '../config/database.php';
require_once '../includes/activity_log.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $member_id = $_POST['member_id'];
    $password = $_POST['password'];

    try {
        // Debug information
        error_log("Login attempt - Member ID: " . $member_id);
        
        // Prepare and execute the query
        $stmt = $pdo->prepare("SELECT * FROM users WHERE member_id = ? AND status = 'active'");
        $stmt->execute([$member_id]);
        $user = $stmt->fetch();

        // Debug user data
        error_log("User found: " . ($user ? "Yes" : "No"));
        if ($user) {
            error_log("User role: " . $user['role']);
            error_log("Password verification: " . (password_verify($password, $user['password']) ? "Success" : "Failed"));
        }

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['member_id'] = $user['member_id'];
            $_SESSION['full_name'] = $user['full_name'];
            $_SESSION['role'] = $user['role'];

            // Log the successful login
            $logger = new ActivityLogger($pdo);
            $logger->log(
                $user['id'],
                'login',
                'User logged in successfully',
                $_SERVER['REMOTE_ADDR'],
                $_SERVER['HTTP_USER_AGENT']
            );

            error_log("Login successful - Member ID: " . $member_id);
            
            // Redirect based on role
            $redirect_url = $user['role'] === 'admin' ? '../admin/dashboard.php' : '../user/dashboard.php';
            error_log("Redirecting to: " . $redirect_url);
            header('Location: ' . $redirect_url);
            exit();
        } else {
            // For debugging, let's check if the password hash matches
            if ($user) {
                error_log("Password hash in DB: " . $user['password']);
                error_log("Attempted password: " . $password);
            }
            error_log("Login failed - Invalid credentials for Member ID: " . $member_id);
            $error = "Invalid member ID or password";
        }
    } catch (PDOException $e) {
        error_log("Login error: " . $e->getMessage());
        $error = "A system error occurred. Please try again later.";
    }
}

// Debug database connection
try {
    $test = $pdo->query("SELECT 1");
    error_log("Database connection test successful");
} catch (PDOException $e) {
    error_log("Database connection test failed: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - <?php echo APP_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100">
    <div class="min-h-screen flex items-center justify-center">
        <div class="max-w-md w-full bg-white rounded-lg shadow-md p-8">
            <h2 class="text-2xl font-bold text-center text-gray-800 mb-8">Login to <?php echo APP_NAME; ?></h2>
            
            <?php if (isset($error)): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
                    <span class="block sm:inline"><?php echo htmlspecialchars($error); ?></span>
                </div>
            <?php endif; ?>

            <form method="POST" action="">
                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-2" for="member_id">
                        Member ID--
                    </label>
                    <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"
                           id="member_id" 
                           name="member_id" 
                           type="text" 
                           value="<?php echo isset($_POST['member_id']) ? htmlspecialchars($_POST['member_id']) : ''; ?>"
                           required>
                </div>
                <div class="mb-6">
                    <label class="block text-gray-700 text-sm font-bold mb-2" for="password">
                        Password
                    </label>
                    <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 mb-3 leading-tight focus:outline-none focus:shadow-outline"
                           id="password" 
                           name="password" 
                           type="password" 
                           required>
                </div>
                <div class="flex items-center justify-between">
                    <button class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline"
                            type="submit">
                        Sign In
                    </button>
                    <a class="inline-block align-baseline font-bold text-sm text-blue-500 hover:text-blue-800"
                       href="../auth/forgot-password.php">
                        Forgot Password?
                    </a>
                </div>
            </form>
        </div>
    </div>
</body>
</html>
