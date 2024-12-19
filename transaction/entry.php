<?php
require_once '../config/database.php';
require_once '../auth/auth_check.php';
require_once '../includes/upload_handler.php';

checkAuth();

$member_id = $_GET['member_id'] ?? '';
$user_data = null;

if (!empty($member_id)) {
    $stmt = $conn->prepare("SELECT id, full_name FROM users WHERE member_id = ? AND role = 'customer'");
    $stmt->bind_param("s", $member_id);
    $stmt->execute();
    $user_data = $stmt->get_result()->fetch_assoc();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_POST['user_id'] ?? '';
    $amount = $_POST['amount'] ?? '';
    $description = $_POST['description'] ?? '';
    
    $errors = [];
    if (empty($user_id)) $errors[] = "User ID is required";
    if (empty($amount) || !is_numeric($amount)) $errors[] = "Valid amount is required";
    
    if (empty($errors)) {
        try {
            $image_path = null;
            if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                $image_path = handleImageUpload($_FILES['image']);
            }
            
            $stmt = $conn->prepare("INSERT INTO transactions (user_id, amount, description, image_path) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("idss", $user_id, $amount, $description, $image_path);
            
            if ($stmt->execute()) {
                $success = "Transaction recorded successfully";
                // Clear form data
                $member_id = '';
                $user_data = null;
            } else {
                $errors[] = "Error recording transaction: " . $conn->error;
            }
        } catch (Exception $e) {
            $errors[] = $e->getMessage();
        }
    }
}

// Generate QR Code URL for the current page
$qr_code_url = "https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=" . urlencode("http://" . $_SERVER['HTTP_HOST'] . $_SERVER['PHP_SELF']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Transaction Entry - Debt Management System</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">
    <div class="min-h-screen">
        <nav class="bg-indigo-600 p-4">
            <div class="max-w-7xl mx-auto flex justify-between items-center">
                <h1 class="text-white text-xl font-bold">Transaction Entry</h1>
                <a href="../admin/dashboard.php" class="text-white hover:text-indigo-100">Back to Dashboard</a>
            </div>
        </nav>

        <main class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
            <div class="bg-white shadow rounded-lg">
                <div class="px-4 py-5 sm:p-6">
                    <?php if (!empty($errors)): ?>
                        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                            <ul>
                                <?php foreach ($errors as $error): ?>
                                    <li><?php echo htmlspecialchars($error); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>

                    <?php if (isset($success)): ?>
                        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                            <?php echo htmlspecialchars($success); ?>
                        </div>
                    <?php endif; ?>

                    <!-- User Search Form -->
                    <?php if (empty($user_data)): ?>
                        <form method="GET" class="mb-8">
                            <div class="flex gap-4">
                                <div class="flex-1">
                                    <label for="member_id" class="block text-sm font-medium text-gray-700">Member ID</label>
                                    <input type="text" name="member_id" id="member_id" required
                                        value="<?php echo htmlspecialchars($member_id); ?>"
                                        class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
                                </div>
                                <div class="flex items-end">
                                    <button type="submit"
                                        class="bg-indigo-600 text-white px-4 py-2 rounded-md hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                        Search
                                    </button>
                                </div>
                            </div>
                        </form>
                    <?php endif; ?>

                    <!-- Transaction Form -->
                    <?php if ($user_data): ?>
                        <div class="mb-8">
                            <h2 class="text-lg font-medium text-gray-900">User: <?php echo htmlspecialchars($user_data['full_name']); ?></h2>
                        </div>

                        <form method="POST" enctype="multipart/form-data" class="space-y-6">
                            <input type="hidden" name="user_id" value="<?php echo $user_data['id']; ?>">
                            
                            <div>
                                <label for="amount" class="block text-sm font-medium text-gray-700">Amount</label>
                                <input type="number" step="0.01" name="amount" id="amount" required
                                    class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
                            </div>

                            <div>
                                <label for="description" class="block text-sm font-medium text-gray-700">Description</label>
                                <textarea name="description" id="description" rows="3"
                                    class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500"></textarea>
                            </div>

                            <div>
                                <label for="image" class="block text-sm font-medium text-gray-700">Upload Image</label>
                                <input type="file" name="image" id="image" accept="image/*" capture="camera"
                                    class="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100">
                            </div>

                            <div>
                                <button type="submit"
                                    class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                    Record Transaction
                                </button>
                            </div>
                        </form>
                    <?php endif; ?>

                    <!-- QR Code Section -->
                    <div class="mt-8 pt-8 border-t border-gray-200">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">QR Code for This Page</h3>
                        <img src="<?php echo $qr_code_url; ?>" alt="QR Code" class="mx-auto">
                        <p class="mt-2 text-sm text-gray-500 text-center">Scan this code to access this page directly</p>
                    </div>
                </div>
            </div>
        </main>
    </div>
</body>
</html>
