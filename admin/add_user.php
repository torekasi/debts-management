<?php
require_once '../includes/config.php';
require_once '../includes/session.php';

// Require admin authentication
requireAdmin();

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $member_id = trim($_POST['member_id'] ?? '');
    $full_name = trim($_POST['full_name'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $confirm_password = trim($_POST['confirm_password'] ?? '');

    // Validate input
    if (empty($member_id) || empty($full_name) || empty($password)) {
        $error = "All fields are required.";
    } elseif ($password !== $confirm_password) {
        $error = "Passwords do not match.";
    } else {
        try {
            // Database connection
            $options = array(
                PDO::MYSQL_ATTR_SSL_CA => __DIR__ . '/../config/ca.pem',
                PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT => false
            );

            $dsn = sprintf(
                "mysql:host=%s;port=%s;dbname=%s",
                DB_HOST,
                DB_PORT,
                DB_NAME
            );

            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            // Check if member_id already exists
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE member_id = ?");
            $stmt->execute([$member_id]);
            if ($stmt->fetchColumn() > 0) {
                $error = "Member ID already exists.";
            } else {
                // Insert new user
                $stmt = $pdo->prepare("
                    INSERT INTO users (member_id, full_name, password, role, status) 
                    VALUES (?, ?, ?, 'user', 'active')
                ");
                $stmt->execute([
                    $member_id,
                    $full_name,
                    password_hash($password, PASSWORD_DEFAULT)
                ]);

                // Log the activity
                $stmt = $pdo->prepare("
                    INSERT INTO activity_logs (user_id, action, description, ip_address) 
                    VALUES (?, 'add_user', ?, ?)
                ");
                $stmt->execute([
                    $_SESSION['user_id'],
                    "Added new user: $member_id",
                    $_SERVER['REMOTE_ADDR']
                ]);

                $success = "User added successfully!";
                
                // Clear form data
                $member_id = $full_name = '';
            }
        } catch (PDOException $e) {
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
    <title>Add New User - <?php echo APP_NAME; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdn.jsdelivr.net/npm/@sweetalert2/theme-minimal/minimal.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.js"></script>
</head>
<body class="bg-gray-100">
    <div class="min-h-screen">
        <!-- Navigation -->
        <?php require_once 'template/header.php'; ?>

        <!-- Main Content -->
        <main class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
            <!-- Page Header -->
            <div class="md:flex md:items-center md:justify-between mb-8">
                <div class="flex-1 min-w-0">
                    <h2 class="text-2xl font-bold leading-7 text-gray-900 sm:text-3xl sm:truncate">
                        Add New User
                    </h2>
                </div>
                <div class="mt-4 flex md:mt-0 md:ml-4">
                    <a href="users.php" class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                        <svg class="-ml-1 mr-2 h-5 w-5 text-gray-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 17l-5-5m0 0l5-5m-5 5h12" />
                        </svg>
                        Back to Users
                    </a>
                </div>
            </div>

            <!-- Add User Form -->
            <div class="bg-white shadow overflow-hidden sm:rounded-lg">
                <form method="POST" class="space-y-8 divide-y divide-gray-200 p-8">
                    <div class="space-y-6">
                        <?php if ($error): ?>
                            <div class="rounded-md bg-red-50 p-4">
                                <div class="flex">
                                    <div class="flex-shrink-0">
                                        <svg class="h-5 w-5 text-red-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                                        </svg>
                                    </div>
                                    <div class="ml-3">
                                        <h3 class="text-sm font-medium text-red-800"><?php echo $error; ?></h3>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>

                        <div class="grid grid-cols-1 gap-y-6 gap-x-4 sm:grid-cols-6">
                            <div class="sm:col-span-3">
                                <label for="member_id" class="block text-sm font-medium text-gray-700">
                                    Member ID
                                </label>
                                <div class="mt-1">
                                    <input type="text" name="member_id" id="member_id" 
                                           value="<?php echo htmlspecialchars($member_id ?? ''); ?>"
                                           class="shadow-sm focus:ring-indigo-500 focus:border-indigo-500 block w-full sm:text-sm border-gray-300 rounded-md">
                                </div>
                            </div>

                            <div class="sm:col-span-3">
                                <label for="full_name" class="block text-sm font-medium text-gray-700">
                                    Full Name
                                </label>
                                <div class="mt-1">
                                    <input type="text" name="full_name" id="full_name" 
                                           value="<?php echo htmlspecialchars($full_name ?? ''); ?>"
                                           class="shadow-sm focus:ring-indigo-500 focus:border-indigo-500 block w-full sm:text-sm border-gray-300 rounded-md">
                                </div>
                            </div>

                            <div class="sm:col-span-3">
                                <label for="password" class="block text-sm font-medium text-gray-700">
                                    Password
                                </label>
                                <div class="mt-1">
                                    <input type="password" name="password" id="password" 
                                           class="shadow-sm focus:ring-indigo-500 focus:border-indigo-500 block w-full sm:text-sm border-gray-300 rounded-md">
                                </div>
                            </div>

                            <div class="sm:col-span-3">
                                <label for="confirm_password" class="block text-sm font-medium text-gray-700">
                                    Confirm Password
                                </label>
                                <div class="mt-1">
                                    <input type="password" name="confirm_password" id="confirm_password" 
                                           class="shadow-sm focus:ring-indigo-500 focus:border-indigo-500 block w-full sm:text-sm border-gray-300 rounded-md">
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="pt-5">
                        <div class="flex justify-end">
                            <button type="submit" class="ml-3 inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                Add User
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </main>
    </div>

    <?php if ($success): ?>
    <script>
        Swal.fire({
            title: 'Success!',
            text: '<?php echo $success; ?>',
            icon: 'success',
            confirmButtonColor: '#4F46E5'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = 'users.php';
            }
        });
    </script>
    <?php endif; ?>
</body>
</html>
