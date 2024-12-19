<?php
$page_title = 'Process Payment';

try {
    require_once '../config/database.php';
    
    // Initialize variables
    $member_id = $_GET['member_id'] ?? '';
    $user_data = null;
    $transactions = [];
    $total_debt = 0;
    $total_paid = 0;
    $errors = [];

    if (!empty($member_id)) {
        // Get user data
        $stmt = $pdo->prepare("
            SELECT id, member_id, full_name 
            FROM users 
            WHERE member_id = ? AND role = 'user'
        ");
        $stmt->execute([$member_id]);
        $user_data = $stmt->fetch();

        if ($user_data) {
            // Get transactions
            $stmt = $pdo->prepare("
                SELECT 
                    t.*,
                    CASE 
                        WHEN t.type = 'loan' THEN t.amount
                        ELSE -t.amount
                    END as amount_with_sign
                FROM transactions t
                WHERE t.user_id = ? AND t.status = 'approved'
                ORDER BY t.created_at DESC
            ");
            $stmt->execute([$user_data['id']]);
            $transactions = $stmt->fetchAll();

            // Calculate totals
            $stmt = $pdo->prepare("
                SELECT 
                    SUM(CASE WHEN type = 'loan' THEN amount ELSE 0 END) as total_loans,
                    SUM(CASE WHEN type = 'payment' THEN amount ELSE 0 END) as total_payments
                FROM transactions 
                WHERE user_id = ? AND status = 'approved'
            ");
            $stmt->execute([$user_data['id']]);
            $totals = $stmt->fetch();
            
            $total_debt = $totals['total_loans'] ?? 0;
            $total_paid = $totals['total_payments'] ?? 0;
        }
    }

    // Process payment submission
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_payment'])) {
        $payment_amount = filter_input(INPUT_POST, 'amount', FILTER_VALIDATE_FLOAT);
        $payment_date = htmlspecialchars(trim($_POST['payment_date'] ?? ''), ENT_QUOTES, 'UTF-8');
        $payment_method = htmlspecialchars(trim($_POST['payment_method'] ?? ''), ENT_QUOTES, 'UTF-8');
        $notes = htmlspecialchars(trim($_POST['notes'] ?? ''), ENT_QUOTES, 'UTF-8');

        // Validate inputs
        if (!$payment_amount || $payment_amount <= 0) {
            $errors[] = "Please enter a valid payment amount";
        }
        if (!$payment_date) {
            $errors[] = "Please enter a valid payment date";
        }
        if (!$payment_method) {
            $errors[] = "Please select a payment method";
        }

        if (empty($errors)) {
            try {
                $pdo->beginTransaction();

                // Insert into transactions table
                $stmt = $pdo->prepare("
                    INSERT INTO transactions (user_id, type, amount, payment_method, notes, status, created_at)
                    VALUES (?, 'payment', ?, ?, ?, 'approved', ?)
                ");
                $stmt->execute([
                    $user_data['id'],
                    $payment_amount,
                    $payment_method,
                    $notes,
                    $payment_date
                ]);

                // Log the activity
                $activity_message = "Payment processed: RM" . number_format($payment_amount, 2) . " for user " . $user_data['member_id'];
                $stmt = $pdo->prepare("
                    INSERT INTO activity_logs (user_id, action, description, ip_address)
                    VALUES (?, 'payment', ?, ?)
                ");
                $stmt->execute([
                    $_SESSION['user_id'],
                    $activity_message,
                    $_SERVER['REMOTE_ADDR']
                ]);

                $pdo->commit();
                header("Location: payments.php?member_id=" . urlencode($member_id) . "&success=1");
                exit;
            } catch (Exception $e) {
                $pdo->rollBack();
                $errors[] = "Error processing payment: " . $e->getMessage();
            }
        }
    }
}
catch (Exception $e) {
    $errors[] = $e->getMessage();
}

ob_start();
?>

<!-- Main Content -->
<div class="mx-auto max-w-screen-2xl p-4 md:p-6 2xl:p-10">
    <!-- Breadcrumb -->
    <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <h2 class="text-title-md2 font-bold text-black dark:text-white">
            Payment Processing
        </h2>
        <nav>
            <ol class="flex items-center gap-2">
                <li><a class="font-medium" href="dashboard.php">Dashboard /</a></li>
                <li class="font-medium text-primary">Process Payment</li>
            </ol>
        </nav>
    </div>

    <!-- Search Form -->
    <div class="mb-6">
        <form action="" method="GET" class="flex flex-col sm:flex-row gap-3">
            <div class="flex-1">
                <label for="member_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Member ID</label>
                <input type="text" 
                       name="member_id" 
                       id="member_id" 
                       value="<?php echo htmlspecialchars($member_id); ?>" 
                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white sm:text-sm"
                       placeholder="Enter Member ID">
            </div>
            <div class="flex items-end">
                <button type="submit" 
                        class="inline-flex justify-center rounded-md border border-transparent bg-blue-600 py-2 px-4 text-sm font-medium text-white shadow-sm hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                    Search
                </button>
            </div>
        </form>
    </div>

    <?php if (!empty($errors)): ?>
        <div class="flex w-full border-l-6 border-[#F87171] bg-[#F87171] bg-opacity-[15%] px-7 py-3 shadow-md dark:bg-[#1B1B24] dark:bg-opacity-30 mb-6">
            <div class="w-full">
                <h5 class="mb-3 font-semibold text-[#B45454]">Error</h5>
                <ul class="list-disc list-inside">
                    <?php foreach ($errors as $error): ?>
                        <li class="leading-relaxed text-[#CD5D5D]">
                            <?php echo htmlspecialchars($error); ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
    <?php endif; ?>

    <?php if (isset($_GET['success'])): ?>
        <div class="flex w-full border-l-6 border-[#34D399] bg-[#34D399] bg-opacity-[15%] px-7 py-3 shadow-md dark:bg-[#1B1B24] dark:bg-opacity-30 mb-6">
            <div class="w-full">
                <h5 class="mb-3 font-semibold text-black dark:text-[#34D399]">Success</h5>
                <p class="text-sm leading-relaxed text-body">Payment processed successfully!</p>
            </div>
        </div>
    <?php endif; ?>

    <?php if ($user_data): ?>
        <!-- User Summary -->
        <div class="rounded-sm border border-stroke bg-white shadow-default dark:border-gray-700 dark:bg-gray-800 mb-6">
            <div class="border-b border-stroke px-6.5 py-4 dark:border-gray-700">
                <h3 class="font-medium text-black dark:text-white">User Information</h3>
            </div>
            <div class="p-6.5">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                    <div>
                        <p class="text-sm text-gray-600 dark:text-gray-400">Member ID</p>
                        <p class="mt-1 text-lg font-semibold text-gray-900 dark:text-white"><?php echo htmlspecialchars($user_data['member_id']); ?></p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-600 dark:text-gray-400">Full Name</p>
                        <p class="mt-1 text-lg font-semibold text-gray-900 dark:text-white"><?php echo htmlspecialchars($user_data['full_name']); ?></p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-600 dark:text-gray-400">Total Debt</p>
                        <p class="mt-1 text-lg font-semibold text-red-600 dark:text-red-400">RM<?php echo number_format($total_debt, 2); ?></p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-600 dark:text-gray-400">Total Paid</p>
                        <p class="mt-1 text-lg font-semibold text-green-600 dark:text-green-400">RM<?php echo number_format($total_paid, 2); ?></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Payment Form -->
        <div class="rounded-sm border border-stroke bg-white shadow-default dark:border-gray-700 dark:bg-gray-800 mb-6">
            <div class="border-b border-stroke px-6.5 py-4 dark:border-gray-700">
                <h3 class="font-medium text-black dark:text-white">Process New Payment</h3>
            </div>
            <div class="p-6.5">
                <form action="" method="POST" class="space-y-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label for="amount" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Payment Amount (RM)</label>
                            <div class="mt-1 relative rounded-md shadow-sm">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <span class="text-gray-500 dark:text-gray-400 sm:text-sm">RM</span>
                                </div>
                                <input type="number" 
                                       name="amount" 
                                       id="amount" 
                                       step="0.01" 
                                       required 
                                       class="pl-12 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white sm:text-sm"
                                       placeholder="0.00">
                            </div>
                        </div>
                        
                        <div>
                            <label for="payment_date" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Payment Date</label>
                            <input type="date" 
                                   name="payment_date" 
                                   id="payment_date" 
                                   required 
                                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white sm:text-sm"
                                   value="<?php echo date('Y-m-d'); ?>">
                        </div>
                        
                        <div>
                            <label for="payment_method" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Payment Method</label>
                            <select name="payment_method" 
                                    id="payment_method" 
                                    required 
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white sm:text-sm">
                                <option value="">Select Method</option>
                                <option value="cash">Cash</option>
                                <option value="bank_transfer">Bank Transfer</option>
                                <option value="credit_card">Credit Card</option>
                                <option value="debit_card">Debit Card</option>
                            </select>
                        </div>
                        
                        <div>
                            <label for="notes" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Notes</label>
                            <textarea name="notes" 
                                      id="notes" 
                                      class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white sm:text-sm" 
                                      rows="3"
                                      placeholder="Add any additional notes here"></textarea>
                        </div>
                    </div>

                    <div class="flex justify-end">
                        <button type="submit" 
                                name="submit_payment" 
                                class="inline-flex justify-center rounded-md border border-transparent bg-blue-600 py-2 px-4 text-sm font-medium text-white shadow-sm hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                            Process Payment
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Transaction History -->
        <div class="rounded-sm border border-stroke bg-white shadow-default dark:border-gray-700 dark:bg-gray-800">
            <div class="border-b border-stroke px-6.5 py-4 dark:border-gray-700">
                <h3 class="font-medium text-black dark:text-white">Transaction History</h3>
            </div>
            <div class="p-6.5">
                <div class="overflow-x-auto">
                    <table class="w-full text-left">
                        <thead>
                            <tr class="border-b border-gray-200 dark:border-gray-700">
                                <th class="py-4 px-4 font-medium text-black dark:text-white">Date</th>
                                <th class="py-4 px-4 font-medium text-black dark:text-white">Type</th>
                                <th class="py-4 px-4 font-medium text-black dark:text-white">Amount</th>
                                <th class="py-4 px-4 font-medium text-black dark:text-white">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($transactions as $transaction): ?>
                                <tr class="border-b border-gray-200 dark:border-gray-700 last:border-b-0">
                                    <td class="py-4 px-4 text-black dark:text-gray-300">
                                        <?php echo date('Y-m-d', strtotime($transaction['created_at'])); ?>
                                    </td>
                                    <td class="py-4 px-4">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?php echo $transaction['type'] === 'loan' ? 'bg-yellow-100 text-yellow-800 dark:bg-yellow-800 dark:text-yellow-100' : 'bg-green-100 text-green-800 dark:bg-green-800 dark:text-green-100'; ?>">
                                            <?php echo ucfirst($transaction['type']); ?>
                                        </span>
                                    </td>
                                    <td class="py-4 px-4 text-black dark:text-gray-300">
                                        RM<?php echo number_format(abs($transaction['amount_with_sign']), 2); ?>
                                    </td>
                                    <td class="py-4 px-4">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?php echo $transaction['status'] === 'approved' ? 'bg-green-100 text-green-800 dark:bg-green-800 dark:text-green-100' : 'bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-100'; ?>">
                                            <?php echo ucfirst($transaction['status']); ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    <?php elseif (!empty($member_id)): ?>
        <div class="flex w-full border-l-6 border-warning bg-warning bg-opacity-[15%] px-7 py-3 shadow-md dark:bg-[#1B1B24] dark:bg-opacity-30">
            <div class="w-full">
                <h5 class="mb-3 font-semibold text-warning">Notice</h5>
                <p class="text-sm leading-relaxed text-body">No user found with the specified Member ID.</p>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php
$content = ob_get_clean();
require_once 'templates/layout.php';
?>
