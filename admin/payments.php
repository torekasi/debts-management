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
    <div class="rounded-sm border border-stroke bg-white shadow-default dark:border-strokedark dark:bg-boxdark mb-6">
        <div class="border-b border-stroke px-6.5 py-4 dark:border-strokedark">
            <h3 class="font-medium text-black dark:text-white">Search User</h3>
        </div>
        <div class="p-6.5">
            <form action="" method="GET" class="flex gap-4">
                <div class="w-full">
                    <label class="mb-2.5 block text-black dark:text-white">Member ID</label>
                    <div class="relative">
                        <input type="text" name="member_id" id="member_id" 
                               value="<?php echo htmlspecialchars($member_id); ?>"
                               class="w-full rounded border-[1.5px] border-stroke bg-transparent px-5 py-3 font-medium outline-none transition focus:border-primary active:border-primary disabled:cursor-default disabled:bg-whiter dark:border-form-strokedark dark:bg-form-input dark:focus:border-primary" 
                               placeholder="Enter Member ID">
                    </div>
                </div>
                <div class="flex items-end">
                    <button type="submit" 
                            class="inline-flex items-center justify-center rounded-md bg-primary px-10 py-3 text-center font-medium text-white hover:bg-opacity-90">
                        Search
                    </button>
                </div>
            </form>
        </div>
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
        <div class="rounded-sm border border-stroke bg-white shadow-default dark:border-strokedark dark:bg-boxdark mb-6">
            <div class="border-b border-stroke px-6.5 py-4 dark:border-strokedark">
                <h3 class="font-medium text-black dark:text-white">User Information</h3>
            </div>
            <div class="p-6.5">
                <div class="grid grid-cols-1 gap-6 sm:grid-cols-3">
                    <div class="flex flex-col gap-2">
                        <span class="text-sm font-medium text-black dark:text-white">Member ID</span>
                        <span class="text-sm text-body"><?php echo htmlspecialchars($user_data['member_id']); ?></span>
                    </div>
                    <div class="flex flex-col gap-2">
                        <span class="text-sm font-medium text-black dark:text-white">Full Name</span>
                        <span class="text-sm text-body"><?php echo htmlspecialchars($user_data['full_name']); ?></span>
                    </div>
                    <div class="flex flex-col gap-2">
                        <span class="text-sm font-medium text-black dark:text-white">Total Debt</span>
                        <span class="text-sm text-meta-1">RM<?php echo number_format($total_debt, 2); ?></span>
                    </div>
                    <div class="flex flex-col gap-2">
                        <span class="text-sm font-medium text-black dark:text-white">Total Paid</span>
                        <span class="text-sm text-meta-3">RM<?php echo number_format($total_paid, 2); ?></span>
                    </div>
                    <div class="flex flex-col gap-2">
                        <span class="text-sm font-medium text-black dark:text-white">Outstanding Balance</span>
                        <span class="text-sm font-bold text-meta-5">RM<?php echo number_format($total_debt - $total_paid, 2); ?></span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Payment Form -->
        <div class="rounded-sm border border-stroke bg-white shadow-default dark:border-strokedark dark:bg-boxdark mb-6">
            <div class="border-b border-stroke px-6.5 py-4 dark:border-strokedark">
                <h3 class="font-medium text-black dark:text-white">Process New Payment</h3>
            </div>
            <div class="p-6.5">
                <form action="" method="POST" class="space-y-6">
                    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                        <div>
                            <label class="mb-2.5 block text-black dark:text-white">Payment Amount</label>
                            <div class="relative">
                                <span class="absolute left-4 top-4 text-body">RM</span>
                                <input type="number" step="0.01" min="0" 
                                       name="amount" id="amount" 
                                       class="w-full rounded border-[1.5px] border-stroke bg-transparent pl-12 pr-4 py-3 font-medium outline-none transition focus:border-primary active:border-primary disabled:cursor-default disabled:bg-whiter dark:border-form-strokedark dark:bg-form-input dark:focus:border-primary" 
                                       required>
                            </div>
                        </div>

                        <div>
                            <label class="mb-2.5 block text-black dark:text-white">Payment Date</label>
                            <div class="relative">
                                <input type="date" 
                                       name="payment_date" id="payment_date" 
                                       value="<?php echo date('Y-m-d'); ?>"
                                       class="w-full rounded border-[1.5px] border-stroke bg-transparent px-5 py-3 font-medium outline-none transition focus:border-primary active:border-primary disabled:cursor-default disabled:bg-whiter dark:border-form-strokedark dark:bg-form-input dark:focus:border-primary" 
                                       required>
                            </div>
                        </div>

                        <div>
                            <label class="mb-2.5 block text-black dark:text-white">Payment Method</label>
                            <div class="relative">
                                <select name="payment_method" id="payment_method" 
                                        class="w-full rounded border-[1.5px] border-stroke bg-transparent px-5 py-3 font-medium outline-none transition focus:border-primary active:border-primary disabled:cursor-default disabled:bg-whiter dark:border-form-strokedark dark:bg-form-input dark:focus:border-primary appearance-none" 
                                        required>
                                    <option value="">Select a payment method</option>
                                    <option value="cash">Cash</option>
                                    <option value="bank_transfer">Bank Transfer</option>
                                    <option value="check">Check</option>
                                    <option value="credit_card">Credit Card</option>
                                </select>
                                <span class="absolute right-4 top-4">
                                    <svg class="fill-current" width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <path fill-rule="evenodd" clip-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" fill="#64748B"></path>
                                    </svg>
                                </span>
                            </div>
                        </div>

                        <div>
                            <label class="mb-2.5 block text-black dark:text-white">Notes</label>
                            <textarea name="notes" id="notes" rows="3" 
                                      class="w-full rounded border-[1.5px] border-stroke bg-transparent px-5 py-3 font-medium outline-none transition focus:border-primary active:border-primary disabled:cursor-default disabled:bg-whiter dark:border-form-strokedark dark:bg-form-input dark:focus:border-primary"
                                      placeholder="Add any additional notes here..."></textarea>
                        </div>
                    </div>

                    <div class="flex justify-end">
                        <button type="submit" name="submit_payment" 
                                class="inline-flex items-center justify-center rounded-md bg-primary px-10 py-4 text-center font-medium text-white hover:bg-opacity-90">
                            Process Payment
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Transaction History -->
        <div class="rounded-sm border border-stroke bg-white shadow-default dark:border-strokedark dark:bg-boxdark">
            <div class="border-b border-stroke px-6.5 py-4 dark:border-strokedark">
                <h3 class="font-medium text-black dark:text-white">Transaction History</h3>
            </div>
            <div class="p-6.5">
                <div class="max-w-full overflow-x-auto">
                    <table class="w-full table-auto">
                        <thead>
                            <tr class="bg-gray-2 text-left dark:bg-meta-4">
                                <th class="py-4 px-4 font-medium text-black dark:text-white">Date</th>
                                <th class="py-4 px-4 font-medium text-black dark:text-white">Type</th>
                                <th class="py-4 px-4 font-medium text-black dark:text-white">Amount</th>
                                <th class="py-4 px-4 font-medium text-black dark:text-white">Method</th>
                                <th class="py-4 px-4 font-medium text-black dark:text-white">Notes</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($transactions as $transaction): ?>
                                <tr>
                                    <td class="border-b border-[#eee] py-4 px-4 dark:border-strokedark">
                                        <?php echo date('Y-m-d', strtotime($transaction['created_at'])); ?>
                                    </td>
                                    <td class="border-b border-[#eee] py-4 px-4 dark:border-strokedark">
                                        <span class="inline-flex rounded-full <?php echo $transaction['type'] === 'loan' ? 'bg-meta-1/10 text-meta-1' : 'bg-meta-3/10 text-meta-3'; ?> py-1 px-3 text-sm font-medium">
                                            <?php echo ucfirst($transaction['type']); ?>
                                        </span>
                                    </td>
                                    <td class="border-b border-[#eee] py-4 px-4 dark:border-strokedark">
                                        RM<?php echo number_format(abs($transaction['amount_with_sign']), 2); ?>
                                    </td>
                                    <td class="border-b border-[#eee] py-4 px-4 dark:border-strokedark">
                                        <?php echo ucfirst($transaction['payment_method'] ?? '-'); ?>
                                    </td>
                                    <td class="border-b border-[#eee] py-4 px-4 dark:border-strokedark">
                                        <?php echo htmlspecialchars($transaction['notes'] ?? ''); ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (empty($transactions)): ?>
                                <tr>
                                    <td colspan="5" class="border-b border-[#eee] py-4 px-4 text-center dark:border-strokedark">
                                        No transactions found
                                    </td>
                                </tr>
                            <?php endif; ?>
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
