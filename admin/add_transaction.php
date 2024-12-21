<?php
$page_title = 'Add Transaction';
require_once 'templates/layout.php';

try {
    // Database connection
    $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=utf8mb4";
    $pdo = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false
    ]);

    // Get all users for the dropdown
    $stmt = $pdo->query("SELECT id, member_id, full_name FROM users WHERE role = 'user' ORDER BY full_name");
    $users = $stmt->fetchAll();

    // Handle form submission
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        try {
            // Generate transaction ID (Format: TR-YYYYMMDD-XXXX)
            $date = date('Ymd');
            $stmt = $pdo->query("SELECT MAX(transaction_id) as max_id FROM transactions WHERE transaction_id LIKE 'TR-$date-%'");
            $result = $stmt->fetch();
            $max_id = $result['max_id'];
            
            if ($max_id) {
                $num = intval(substr($max_id, -4)) + 1;
            } else {
                $num = 1;
            }
            $transaction_id = sprintf("TR-%s-%04d", $date, $num);

            // Handle image upload
            $image_path = null;
            if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                $upload_dir = $base_path . '/uploads/transactions/';
                if (!file_exists($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }

                $file_extension = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
                $new_filename = $transaction_id . '.' . $file_extension;
                $target_path = $upload_dir . $new_filename;

                if (move_uploaded_file($_FILES['image']['tmp_name'], $target_path)) {
                    $image_path = 'uploads/transactions/' . $new_filename;
                }
            }

            // Insert transaction
            $stmt = $pdo->prepare("
                INSERT INTO transactions (
                    user_id, type, amount, description, created_at, 
                    updated_at, image_path, transaction_id
                ) VALUES (
                    ?, ?, ?, ?, NOW(), NOW(), ?, ?
                )
            ");

            $stmt->execute([
                $_POST['user_id'],
                $_POST['type'],
                $_POST['amount'],
                $_POST['description'],
                $image_path,
                $transaction_id
            ]);

            $_SESSION['success_message'] = "Transaction added successfully!";
            header("Location: transactions.php");
            exit();

        } catch (Exception $e) {
            $_SESSION['error_message'] = "Error adding transaction: " . $e->getMessage();
        }
    }

} catch (PDOException $e) {
    error_log("Add transaction page error: " . $e->getMessage());
    $_SESSION['error_message'] = "An error occurred while loading the page.";
    header("Location: transactions.php");
    exit();
}
?>

<!-- Main Content -->
<div class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
    <div class="px-4 py-6 sm:px-0">
        <!-- Header -->
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-2xl font-semibold text-gray-900">Add New Transaction</h1>
            <a href="transactions.php" class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-gray-600 hover:bg-gray-700">
                <i class="fas fa-arrow-left mr-2"></i>
                Back to Transactions
            </a>
        </div>

        <!-- Form -->
        <div class="bg-white shadow rounded-lg">
            <div class="px-4 py-5 sm:p-6">
                <form action="" method="POST" enctype="multipart/form-data" class="space-y-6">
                    <!-- User Selection -->
                    <div>
                        <label for="user_id" class="block text-sm font-medium text-gray-700">Select User</label>
                        <select id="user_id" name="user_id" required
                                class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md">
                            <option value="">Select a user</option>
                            <?php foreach ($users as $user): ?>
                                <option value="<?php echo $user['id']; ?>">
                                    <?php echo htmlspecialchars($user['member_id'] . ' - ' . $user['full_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Transaction Type -->
                    <div>
                        <label for="type" class="block text-sm font-medium text-gray-700">Transaction Type</label>
                        <select id="type" name="type" required
                                class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md">
                            <option value="">Select type</option>
                            <option value="loan">Loan</option>
                            <option value="payment">Payment</option>
                        </select>
                    </div>

                    <!-- Amount -->
                    <div>
                        <label for="amount" class="block text-sm font-medium text-gray-700">Amount (RM)</label>
                        <div class="mt-1 relative rounded-md shadow-sm">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <span class="text-gray-500 sm:text-sm">RM</span>
                            </div>
                            <input type="number" step="0.01" min="0" 
                                   name="amount" id="amount" required
                                   class="mt-1 block w-full pl-12 pr-3 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md"
                                   placeholder="0.00">
                        </div>
                    </div>

                    <!-- Description -->
                    <div>
                        <label for="description" class="block text-sm font-medium text-gray-700">Description</label>
                        <textarea id="description" name="description" rows="3" required
                                  class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                                  placeholder="Enter transaction details"></textarea>
                    </div>

                    <!-- Image Upload -->
                    <div>
                        <label for="image" class="block text-sm font-medium text-gray-700">Upload Image (Optional)</label>
                        <div class="mt-1 flex justify-center px-6 pt-5 pb-6 border-2 border-gray-300 border-dashed rounded-md">
                            <div class="space-y-1 text-center">
                                <svg class="mx-auto h-12 w-12 text-gray-400" stroke="currentColor" fill="none" viewBox="0 0 48 48">
                                    <path d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                </svg>
                                <div class="flex text-sm text-gray-600">
                                    <label for="image" class="relative cursor-pointer bg-white rounded-md font-medium text-indigo-600 hover:text-indigo-500 focus-within:outline-none focus-within:ring-2 focus-within:ring-offset-2 focus-within:ring-indigo-500">
                                        <span>Upload a file</span>
                                        <input id="image" name="image" type="file" class="sr-only" accept="image/*">
                                    </label>
                                    <p class="pl-1">or drag and drop</p>
                                </div>
                                <p class="text-xs text-gray-500">PNG, JPG, GIF up to 10MB</p>
                            </div>
                        </div>
                    </div>

                    <!-- Submit Button -->
                    <div class="flex justify-end">
                        <button type="submit" class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                            <i class="fas fa-save mr-2"></i>
                            Save Transaction
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once 'templates/footer.php'; ?>
