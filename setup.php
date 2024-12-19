<?php
require_once 'config/config.php';
require_once 'config/database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $member_id = $_POST['member_id'] ?? '';
    $full_name = $_POST['full_name'] ?? '';
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    
    $errors = [];
    
    // Validate input
    if (empty($member_id)) $errors[] = "Member ID is required";
    if (empty($full_name)) $errors[] = "Full name is required";
    if (empty($password)) $errors[] = "Password is required";
    if (strlen($password) < 8) $errors[] = "Password must be at least 8 characters";
    
    if (empty($errors)) {
        // Check if admin already exists
        $result = $conn->query("SELECT COUNT(*) as count FROM users WHERE role = 'admin'");
        $admin_count = $result->fetch_assoc()['count'];
        
        if ($admin_count > 0) {
            $errors[] = "An admin user already exists. This setup can only be run once.";
        } else {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            $stmt = $conn->prepare("INSERT INTO users (member_id, full_name, email, password, role) VALUES (?, ?, ?, ?, 'admin')");
            $stmt->bind_param("ssss", $member_id, $full_name, $email, $hashed_password);
            
            if ($stmt->execute()) {
                $success = "Admin user created successfully. You can now log in.";
                // Create a file to indicate setup is complete
                file_put_contents(__DIR__ . '/setup_complete', date('Y-m-d H:i:s'));
            } else {
                $errors[] = "Error creating admin user: " . $conn->error;
            }
        }
    }
}

// Check if setup is already complete
if (file_exists(__DIR__ . '/setup_complete')) {
    die("Setup has already been completed. Please delete the setup.php file for security.");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Initial Setup - <?php echo APP_NAME; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">
    <div class="min-h-screen flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-md w-full space-y-8">
            <div>
                <h2 class="mt-6 text-center text-3xl font-extrabold text-gray-900">
                    Initial Setup
                </h2>
                <p class="mt-2 text-center text-sm text-gray-600">
                    Create the first admin user
                </p>
            </div>

            <?php if (!empty($errors)): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
                    <ul class="list-disc list-inside">
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo htmlspecialchars($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <?php if (isset($success)): ?>
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded">
                    <?php echo htmlspecialchars($success); ?>
                    <p class="mt-2">
                        <a href="auth/login.php" class="font-medium text-green-600 hover:text-green-500">
                            Click here to login
                        </a>
                    </p>
                </div>
            <?php else: ?>
                <form class="mt-8 space-y-6" method="POST">
                    <div class="rounded-md shadow-sm -space-y-px">
                        <div>
                            <label for="member_id" class="sr-only">Member ID</label>
                            <input id="member_id" name="member_id" type="text" required
                                class="appearance-none rounded-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-t-md focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 focus:z-10 sm:text-sm"
                                placeholder="Member ID">
                        </div>
                        <div>
                            <label for="full_name" class="sr-only">Full Name</label>
                            <input id="full_name" name="full_name" type="text" required
                                class="appearance-none rounded-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 focus:z-10 sm:text-sm"
                                placeholder="Full Name">
                        </div>
                        <div>
                            <label for="email" class="sr-only">Email</label>
                            <input id="email" name="email" type="email"
                                class="appearance-none rounded-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 focus:z-10 sm:text-sm"
                                placeholder="Email (Optional)">
                        </div>
                        <div>
                            <label for="password" class="sr-only">Password</label>
                            <input id="password" name="password" type="password" required
                                class="appearance-none rounded-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-b-md focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 focus:z-10 sm:text-sm"
                                placeholder="Password">
                        </div>
                    </div>

                    <div>
                        <button type="submit"
                            class="group relative w-full flex justify-center py-2 px-4 border border-transparent text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                            Create Admin User
                        </button>
                    </div>
                </form>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
