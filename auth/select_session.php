<?php
require_once('../includes/config.php');
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['selected_session'])) {
    $index = (int)$_POST['selected_session'];
    
    if (isset($_SESSION['active_sessions'][$index])) {
        $selected_session = $_SESSION['active_sessions'][$index];
        
        // Set current session data
        $_SESSION['user_id'] = $selected_session['user_id'];
        $_SESSION['member_id'] = $selected_session['member_id'];
        $_SESSION['full_name'] = $selected_session['full_name'];
        $_SESSION['role'] = $selected_session['role'];
        $_SESSION['logged_in'] = true;
        
        // Redirect based on role
        if ($selected_session['role'] === 'admin') {
            header('Location: /admin/dashboard.php');
        } else {
            header('Location: /member/member_dashboard.php');
        }
        exit();
    }
}

// If something went wrong, redirect back to login
header("Location: /auth/login.php");
exit();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Select Account - Debt Management System</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">
    <div class="min-h-screen flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-md w-full space-y-8">
            <div>
                <h2 class="mt-6 text-center text-3xl font-extrabold text-gray-900">
                    Select Account
                </h2>
                <p class="mt-2 text-center text-sm text-gray-600">
                    Choose which account you want to access
                </p>
            </div>
            
            <form class="mt-8 space-y-6" action="select_session.php" method="POST">
                <div class="rounded-md shadow-sm -space-y-px">
                    <?php foreach ($_SESSION['active_sessions'] as $index => $session): ?>
                        <div class="p-4 bg-white mb-4 rounded-lg shadow">
                            <label class="flex items-center space-x-4 cursor-pointer">
                                <input type="radio" 
                                       name="selected_session" 
                                       value="<?php echo $index; ?>" 
                                       class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300"
                                       <?php echo ($index === 0) ? 'checked' : ''; ?>>
                                <div class="flex-1">
                                    <div class="text-sm font-medium text-gray-900">
                                        <?php echo htmlspecialchars($session['full_name']); ?>
                                    </div>
                                    <div class="text-sm text-gray-500">
                                        Member ID: <?php echo htmlspecialchars($session['member_id']); ?>
                                    </div>
                                    <div class="text-xs text-gray-400">
                                        Role: <?php echo ucfirst(htmlspecialchars($session['role'])); ?>
                                    </div>
                                </div>
                            </label>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div>
                    <button type="submit" class="group relative w-full flex justify-center py-2 px-4 border border-transparent text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                        Continue with Selected Account
                    </button>
                </div>
                
                <div class="text-center">
                    <a href="login.php" class="font-medium text-indigo-600 hover:text-indigo-500">
                        Login with Different Account
                    </a>
                </div>
            </form>
        </div>
    </div>
</body>
</html>
