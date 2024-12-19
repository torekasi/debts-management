<?php
require_once '../config/config.php';
require_once '../config/database.php';
require_once '../auth/auth_check.php';
require_once '../includes/notifications.php';

checkAuth();

$notifications = new NotificationSystem($conn);
$user_id = $_SESSION['user_id'];

// Mark notification as read if requested
if (isset($_POST['mark_read'])) {
    $notification_id = $_POST['notification_id'];
    $notifications->markAsRead($notification_id, $user_id);
}

// Mark all as read if requested
if (isset($_POST['mark_all_read'])) {
    $notifications->markAllAsRead($user_id);
}

// Get all notifications
$user_notifications = $notifications->getAllNotifications($user_id);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notifications - <?php echo APP_NAME; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">
    <div class="min-h-screen">
        <nav class="bg-indigo-600 p-4">
            <div class="max-w-7xl mx-auto flex justify-between items-center">
                <h1 class="text-white text-xl font-bold">Notifications</h1>
                <div class="flex items-center space-x-4">
                    <a href="dashboard.php" class="text-white hover:text-indigo-100">Dashboard</a>
                    <a href="../auth/logout.php" class="text-white hover:text-indigo-100">Logout</a>
                </div>
            </div>
        </nav>

        <main class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
            <div class="bg-white shadow rounded-lg">
                <div class="px-4 py-5 sm:p-6">
                    <?php if (!empty($user_notifications)): ?>
                        <div class="flex justify-end mb-4">
                            <form method="POST">
                                <button type="submit" name="mark_all_read" 
                                    class="bg-indigo-600 text-white px-4 py-2 rounded-md hover:bg-indigo-700">
                                    Mark All as Read
                                </button>
                            </form>
                        </div>

                        <div class="space-y-4">
                            <?php foreach ($user_notifications as $notification): ?>
                                <div class="flex items-start p-4 <?php echo $notification['is_read'] ? 'bg-gray-50' : 'bg-white border-l-4 border-indigo-500'; ?> rounded-lg shadow">
                                    <div class="flex-1">
                                        <div class="flex items-center justify-between">
                                            <h3 class="text-lg font-medium text-gray-900">
                                                <?php echo htmlspecialchars($notification['title']); ?>
                                            </h3>
                                            <span class="text-sm text-gray-500">
                                                <?php echo date('M d, Y H:i', strtotime($notification['created_at'])); ?>
                                            </span>
                                        </div>
                                        <p class="mt-1 text-gray-600">
                                            <?php echo htmlspecialchars($notification['message']); ?>
                                        </p>
                                        <?php if (!$notification['is_read']): ?>
                                            <form method="POST" class="mt-2">
                                                <input type="hidden" name="notification_id" value="<?php echo $notification['id']; ?>">
                                                <button type="submit" name="mark_read" 
                                                    class="text-sm text-indigo-600 hover:text-indigo-900">
                                                    Mark as Read
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-8">
                            <p class="text-gray-500">No notifications to display</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>
</body>
</html>
