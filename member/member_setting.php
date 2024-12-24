<?php
session_start();
include("../includes/config.php");
include("../includes/functions.php");

// Check if user is logged in
if (!isset($_SESSION['member_id'])) {
    header("Location: ../login.php");
    exit();
}

$member_id = $_SESSION['member_id'];

// Fetch user details
$query = "SELECT * FROM users WHERE member_id = :member_id";
$stmt = $conn->prepare($query);
$stmt->bindParam(':member_id', $member_id, PDO::PARAM_STR);
$stmt->execute();
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Handle password update
$password_message = "";
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_password'])) {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    // Verify current password
    if (password_verify($current_password, $user['password'])) {
        if ($new_password === $confirm_password) {
            if (strlen($new_password) >= 6) {
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                $update_query = "UPDATE users SET password = :password WHERE member_id = :member_id";
                $update_stmt = $conn->prepare($update_query);
                $update_stmt->bindParam(':password', $hashed_password, PDO::PARAM_STR);
                $update_stmt->bindParam(':member_id', $member_id, PDO::PARAM_STR);
                
                if ($update_stmt->execute()) {
                    $password_message = '<div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-4" role="alert">Password updated successfully!</div>';
                } else {
                    $password_message = '<div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-4" role="alert">Error updating password. Please try again.</div>';
                }
            } else {
                $password_message = '<div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-4" role="alert">New password must be at least 6 characters long.</div>';
            }
        } else {
            $password_message = '<div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-4" role="alert">New passwords do not match.</div>';
        }
    } else {
        $password_message = '<div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-4" role="alert">Current password is incorrect.</div>';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Account Settings - Debt Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">
    <?php include("template/member_header.php"); ?>

    <main class="content-container p-4 md:p-6" style="margin: 80px 0;">
        <div class="max-w-4xl mx-auto">
            <!-- Profile Section -->
            <div class="bg-white rounded-lg shadow-sm p-6 mb-6">
                <h2 class="text-2xl font-bold text-gray-800 mb-6 flex items-center">
                    <i class="fas fa-user-circle text-blue-500 mr-3"></i>
                    Profile Information
                </h2>
                <div class="grid grid-cols-2 gap-x-8 gap-y-4">
                    <div>
                        <div class="text-xs font-semibold text-gray-600 mb-1">Member ID</div>
                        <div class="text-sm font-bold text-gray-800"><?php echo htmlspecialchars($user['member_id']); ?></div>
                    </div>
                    <div>
                        <div class="text-xs font-semibold text-gray-600 mb-1">Status</div>
                        <div>
                            <span class="px-2 py-1 text-xs font-bold rounded-full <?php echo $user['status'] === 'active' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                                <?php echo ucfirst(htmlspecialchars($user['status'])); ?>
                            </span>
                        </div>
                    </div>
                    <div>
                        <div class="text-xs font-semibold text-gray-600 mb-1">Full Name</div>
                        <div class="text-sm font-bold text-gray-800"><?php echo htmlspecialchars($user['full_name']); ?></div>
                    </div>
                    <div>
                        <div class="text-xs font-semibold text-gray-600 mb-1">Email Address</div>
                        <div class="text-sm font-bold text-gray-800"><?php echo htmlspecialchars($user['email']); ?></div>
                    </div>
                    <div>
                        <div class="text-xs font-semibold text-gray-600 mb-1">Phone Number</div>
                        <div class="text-sm font-bold text-gray-800"><?php echo htmlspecialchars($user['phone']); ?></div>
                    </div>
                    <div>
                        <div class="text-xs font-semibold text-gray-600 mb-1">Member Since</div>
                        <div class="text-sm font-bold text-gray-800"><?php echo date('F j, Y', strtotime($user['created_at'])); ?></div>
                    </div>
                </div>
            </div>

            <!-- Password Section -->
            <div class="bg-white rounded-lg shadow-sm p-6">
                <h2 class="text-2xl font-bold text-gray-800 mb-6 flex items-center">
                    <i class="fas fa-lock text-blue-500 mr-3"></i>
                    Change Password
                </h2>
                <?php echo $password_message; ?>
                <form method="POST" action="" class="space-y-6">
                    <div>
                        <label for="current_password" class="block text-sm font-medium text-gray-700 mb-2">Current Password</label>
                        <input type="password" class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500" 
                               id="current_password" name="current_password" required>
                    </div>
                    <div>
                        <label for="new_password" class="block text-sm font-medium text-gray-700 mb-2">New Password</label>
                        <input type="password" class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500" 
                               id="new_password" name="new_password" required>
                        <p class="mt-1 text-sm text-gray-500">Password must be at least 6 characters long.</p>
                    </div>
                    <div>
                        <label for="confirm_password" class="block text-sm font-medium text-gray-700 mb-2">Confirm New Password</label>
                        <input type="password" class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500" 
                               id="confirm_password" name="confirm_password" required>
                    </div>
                    <button type="submit" name="update_password" class="w-full bg-blue-600 text-white px-6 py-3 rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-colors duration-200">
                        <i class="fas fa-save mr-2"></i>
                        Update Password
                    </button>
                </form>
            </div>
        </div>
    </main>

    <?php include 'template/member_footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>