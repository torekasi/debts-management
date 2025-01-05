<?php
require_once '../includes/config.php';
require_once '../includes/session.php';

// Require admin authentication
requireAdmin();

// Initialize variables
$error = '';
$success = '';
$user = null;

// Database connection
try {
    $dsn = sprintf("mysql:host=%s;port=%s;dbname=%s", DB_HOST, DB_PORT, DB_NAME);
    $pdo = new PDO($dsn, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    if (isset($_GET['user_id'])) {
        // Fetch user details
        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ? AND role != 'admin'");
        $stmt->execute([$_GET['user_id']]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            header('Location: users.php');
            exit();
        }
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Validate input
        $member_id = trim($_POST['member_id']);
        $full_name = trim($_POST['full_name']);
        $email = trim($_POST['email']);
        $phone = trim($_POST['phone']);
        $status = $_POST['status'];
        $role = $_POST['role'];
        $user_id = $_POST['user_id'];
        $new_password = trim($_POST['new_password'] ?? '');
        $confirm_password = trim($_POST['confirm_password'] ?? '');

        // Basic validation
        if (empty($member_id) || empty($full_name) || empty($email)) {
            $error = "Please fill in all required fields.";
        } else {
            try {
                // Check if member_id or email already exists for other users
                $stmt = $pdo->prepare("
                    SELECT COUNT(*) FROM users 
                    WHERE (member_id = ? OR email = ?) 
                    AND id != ? 
                    AND role != 'admin'
                ");
                $stmt->execute([$member_id, $email, $user_id]);
                $exists = $stmt->fetchColumn();

                if ($exists) {
                    $error = "Member ID or Email already exists.";
                } else {
                    // Validate new password
                    if (!empty($new_password)) {
                        if ($new_password !== $confirm_password) {
                            throw new Exception("New password and confirmation do not match.");
                        }
                    }

                    // Start transaction
                    $pdo->beginTransaction();

                    try {
                        // Update user basic info
                        $sql = "UPDATE users SET 
                                member_id = ?, 
                                full_name = ?, 
                                email = ?, 
                                phone = ?,
                                status = ?,
                                role = ?";
                        
                        $params = [$member_id, $full_name, $email, $phone, $status, $role];

                        // Add password update if provided
                        if (!empty($new_password)) {
                            $sql .= ", password = ?";
                            $params[] = password_hash($new_password, PASSWORD_DEFAULT);
                        }

                        $sql .= " WHERE id = ?";
                        $params[] = $user_id;

                        $stmt = $pdo->prepare($sql);
                        $stmt->execute($params);

                        // Log the activity
                        $action_desc = "Updated user: $full_name";
                        if (!empty($new_password)) {
                            $action_desc .= " (password changed)";
                        }

                        $stmt = $pdo->prepare("
                            INSERT INTO activity_logs (user_id, action, description, ip_address) 
                            VALUES (?, 'update_user', ?, ?)
                        ");
                        $stmt->execute([
                            $_SESSION['user_id'],
                            $action_desc,
                            $_SERVER['REMOTE_ADDR']
                        ]);

                        $pdo->commit();
                        $_SESSION['success_message'] = "User updated successfully.";
                        header('Location: users.php');
                        exit();

                    } catch (Exception $e) {
                        $pdo->rollBack();
                        throw $e;
                    }
                }
            } catch (PDOException $e) {
                $error = "Error updating user: " . $e->getMessage();
            }
        }
    }
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit User - <?php echo APP_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <style>
        /* Select2 Custom Styles */
        .select2-container--default .select2-selection--single {
            height: 42px !important;
            padding: 6px 12px !important;
            border: 1px solid #d1d5db !important;
            border-radius: 0.375rem !important;
            background-color: #fff !important;
        }

        .select2-container--default .select2-selection--single .select2-selection__rendered {
            line-height: 28px !important;
            color: #111827 !important;
        }

        .select2-container--default .select2-selection--single .select2-selection__arrow {
            height: 40px !important;
            right: 8px !important;
        }

        .select2-dropdown {
            border: 1px solid #d1d5db !important;
            border-radius: 0.375rem !important;
            margin-top: 1px !important;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06) !important;
        }

        .select2-container--default .select2-search--dropdown .select2-search__field {
            border: 1px solid #d1d5db !important;
            border-radius: 0.375rem !important;
            padding: 8px !important;
            margin: 8px !important;
            width: calc(100% - 16px) !important;
        }

        .select2-container--default .select2-results__option--highlighted[aria-selected] {
            background-color: #4f46e5 !important;
        }

        .select2-results__option {
            padding: 8px 12px !important;
        }

        .select2-container--default .select2-selection--single:focus,
        .select2-container--default.select2-container--open .select2-selection--single {
            border-color: #4f46e5 !important;
            box-shadow: 0 0 0 1px #4f46e5 !important;
            outline: none !important;
        }

        /* Form input styles */
        .form-input {
            width: 100%;
            padding: 0.5rem 0.75rem;
            border: 1px solid #d1d5db;
            border-radius: 0.375rem;
            font-size: 0.875rem;
            line-height: 1.25rem;
            transition: all 0.15s ease-in-out;
        }

        .form-input:focus {
            outline: none;
            border-color: #4f46e5;
            box-shadow: 0 0 0 1px #4f46e5;
        }

        .form-label {
            display: block;
            font-size: 0.875rem;
            font-weight: 500;
            color: #374151;
            margin-bottom: 0.5rem;
        }

        /* Role field warning styles */
        .role-warning {
            background-color: #FEF2F2;
            border: 1px solid #F87171;
            border-radius: 0.375rem;
            padding: 0.75rem;
            margin-top: 0.5rem;
        }

        .form-input-warning {
            border-color: #F87171 !important;
            background-color: #FEF2F2 !important;
        }

        .form-input-warning:focus {
            border-color: #F87171 !important;
            box-shadow: 0 0 0 1px #F87171 !important;
        }
    </style>
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
                        Edit User
                    </h2>
                </div>
                <div class="mt-4 flex md:mt-0 md:ml-4">
                    <a href="users.php" class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                        <i class="fas fa-arrow-left mr-2"></i>
                        Back to Users
                    </a>
                </div>
            </div>

            <?php if ($error): ?>
                <div class="rounded-md bg-red-50 p-4 mb-6">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <i class="fas fa-exclamation-circle text-red-400"></i>
                        </div>
                        <div class="ml-3">
                            <h3 class="text-sm font-medium text-red-800"><?php echo $error; ?></h3>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Edit User Form -->
            <div class="bg-white shadow rounded-lg">
                <div class="px-4 py-5 sm:p-6">
                    <form action="edit_user.php" method="POST" class="space-y-6">
                        <input type="hidden" name="user_id" value="<?php echo htmlspecialchars($user['id']); ?>">
                        
                        <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                            <div>
                                <label for="member_id" class="form-label">Member ID</label>
                                <input type="text" 
                                       name="member_id" 
                                       id="member_id" 
                                       value="<?php echo htmlspecialchars($user['member_id']); ?>"
                                       class="form-input"
                                       required>
                            </div>

                            <div>
                                <label for="full_name" class="form-label">Full Name</label>
                                <input type="text" 
                                       name="full_name" 
                                       id="full_name" 
                                       value="<?php echo htmlspecialchars($user['full_name']); ?>"
                                       class="form-input"
                                       required>
                            </div>

                            <div>
                                <label for="email" class="form-label">Email</label>
                                <input type="email" 
                                       name="email" 
                                       id="email" 
                                       value="<?php echo htmlspecialchars($user['email']); ?>"
                                       class="form-input"
                                       required>
                            </div>

                            <div>
                                <label for="phone" class="form-label">Phone</label>
                                <input type="text" 
                                       name="phone" 
                                       id="phone" 
                                       value="<?php echo htmlspecialchars($user['phone']); ?>"
                                       class="form-input">
                            </div>

                            <div>
                                <label for="status" class="form-label">Status</label>
                                <select name="status" 
                                        id="status" 
                                        class="form-input"
                                        required>
                                    <option value="active" <?php echo $user['status'] === 'active' ? 'selected' : ''; ?>>Active</option>
                                    <option value="inactive" <?php echo $user['status'] === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                                </select>
                            </div>

                            <div>
                                <label for="role" class="form-label text-red-600">Role <span class="text-red-500">*</span></label>
                                <select name="role" 
                                        id="role" 
                                        class="form-input form-input-warning"
                                        required>
                                    <option value="user" <?php echo $user['role'] === 'user' ? 'selected' : ''; ?>>User</option>
                                    <option value="admin" <?php echo $user['role'] === 'admin' ? 'selected' : ''; ?>>Admin</option>
                                </select>
                                <div class="role-warning mt-2">
                                    <div class="flex">
                                        <div class="flex-shrink-0">
                                            <i class="fas fa-exclamation-triangle text-red-400"></i>
                                        </div>
                                        <div class="ml-3">
                                            <p class="text-sm text-red-700">
                                                <strong>Warning:</strong> Changing the user role will impact their transaction data and system access permissions. Please be cautious when modifying this field.
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Password Change Section -->
                        <div class="mt-8 pt-8 border-t border-gray-200">
                            <h3 class="text-lg font-medium leading-6 text-gray-900 mb-4">Change Password</h3>
                            <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                                <div>
                                    <label for="new_password" class="form-label">New Password</label>
                                    <input type="password" 
                                           name="new_password" 
                                           id="new_password" 
                                           class="form-input"
                                           autocomplete="new-password">
                                    <p class="mt-1 text-sm text-gray-500">Leave blank to keep current password</p>
                                </div>

                                <div>
                                    <label for="confirm_password" class="form-label">Confirm New Password</label>
                                    <input type="password" 
                                           name="confirm_password" 
                                           id="confirm_password" 
                                           class="form-input"
                                           autocomplete="new-password">
                                    <p class="mt-1 hidden text-sm text-red-500" id="password-match-error">Passwords do not match</p>
                                </div>
                            </div>
                        </div>

                        <div class="pt-5">
                            <div class="flex justify-end">
                                <a href="users.php" 
                                   class="bg-white py-2 px-4 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 mr-3">
                                    Cancel
                                </a>
                                <button type="submit" 
                                        class="inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                    <i class="fas fa-save mr-2"></i>
                                    Update User
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </main>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
    $(document).ready(function() {
        // Initialize Select2
        $('#status').select2({
            minimumResultsForSearch: Infinity // Disable search for status dropdown
        });

        // Password validation
        const newPasswordInput = $('#new_password');
        const confirmPasswordInput = $('#confirm_password');
        const passwordMatchError = $('#password-match-error');
        const submitButton = $('button[type="submit"]');

        function validatePasswords() {
            const newPassword = newPasswordInput.val();
            const confirmPassword = confirmPasswordInput.val();

            if (newPassword || confirmPassword) {
                if (newPassword !== confirmPassword) {
                    passwordMatchError.removeClass('hidden');
                    submitButton.prop('disabled', true);
                    submitButton.addClass('opacity-50 cursor-not-allowed');
                    return false;
                }
            }
            
            passwordMatchError.addClass('hidden');
            submitButton.prop('disabled', false);
            submitButton.removeClass('opacity-50 cursor-not-allowed');
            return true;
        }

        // Add event listeners for password fields
        newPasswordInput.on('input', validatePasswords);
        confirmPasswordInput.on('input', validatePasswords);

        // Form submission validation
        $('form').on('submit', function(e) {
            if (!validatePasswords()) {
                e.preventDefault();
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Please make sure passwords match before submitting.'
                });
            }
        });

        <?php if (isset($_SESSION['success_message'])): ?>
            Swal.fire({
                icon: 'success',
                title: 'Success!',
                text: '<?php echo addslashes($_SESSION['success_message']); ?>',
                timer: 3000,
                showConfirmButton: false
            });
            <?php unset($_SESSION['success_message']); ?>
        <?php endif; ?>
    });
    </script>
</body>
</html>