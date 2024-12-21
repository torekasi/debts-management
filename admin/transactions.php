<?php
$page_title = 'Transactions';
require_once 'templates/layout.php';

try {
    // Database connection
    $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=utf8mb4";
    $pdo = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false
    ]);

    // Get filter parameters
    $user_id = $_GET['user_id'] ?? null;
    $type = $_GET['type'] ?? '';
    $status = $_GET['status'] ?? '';
    $date_from = $_GET['date_from'] ?? '';
    $date_to = $_GET['date_to'] ?? '';

    // Build the query
    $query = "
        SELECT t.*, u.member_id, u.full_name,
               CASE 
                   WHEN t.type = 'loan' THEN t.amount
                   ELSE -t.amount
               END as amount_with_sign
        FROM transactions t
        JOIN users u ON t.user_id = u.id
        WHERE 1=1
    ";
    $params = [];

    if ($user_id) {
        $query .= " AND t.user_id = ?";
        $params[] = $user_id;
    }
    if ($type) {
        $query .= " AND t.type = ?";
        $params[] = $type;
    }
    if ($status) {
        $query .= " AND t.status = ?";
        $params[] = $status;
    }
    if ($date_from) {
        $query .= " AND DATE(t.created_at) >= ?";
        $params[] = $date_from;
    }
    if ($date_to) {
        $query .= " AND DATE(t.created_at) <= ?";
        $params[] = $date_to;
    }

    $query .= " ORDER BY t.created_at DESC";

    // Execute query
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $transactions = $stmt->fetchAll();

    // Get summary statistics
    $summary_query = "
        SELECT 
            COUNT(*) as total_transactions,
            SUM(CASE WHEN type = 'loan' THEN amount ELSE 0 END) as total_loans,
            SUM(CASE WHEN type = 'payment' THEN amount ELSE 0 END) as total_payments,
            COUNT(DISTINCT user_id) as total_users
        FROM transactions
        WHERE status = 'approved'
    ";
    $summary = $pdo->query($summary_query)->fetch();

} catch (PDOException $e) {
    error_log("Transactions page error: " . $e->getMessage());
    $_SESSION['error_message'] = "An error occurred while loading the transactions.";
    header("Location: /admin/dashboard.php");
    exit();
}
?>

<!-- Main Content -->
<div class="space-y-6">
    <!-- Header -->
    <div class="flex justify-between items-center">
        <h1 class="text-2xl font-semibold text-gray-900">Transaction History</h1>
        <a href="add_transaction.php" 
           class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700">
            <i class="fas fa-plus mr-2"></i>
            New Transaction
        </a>
    </div>

    <!-- Summary Stats -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-indigo-100 text-indigo-600">
                    <i class="fas fa-exchange-alt text-2xl"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-500">Total Transactions</p>
                    <p class="text-2xl font-semibold text-gray-900"><?php echo number_format($summary['total_transactions']); ?></p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-red-100 text-red-600">
                    <i class="fas fa-hand-holding-usd text-2xl"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-500">Total Loans</p>
                    <p class="text-2xl font-semibold text-gray-900">RM<?php echo number_format($summary['total_loans'], 2); ?></p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-green-100 text-green-600">
                    <i class="fas fa-money-bill-wave text-2xl"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-500">Total Payments</p>
                    <p class="text-2xl font-semibold text-gray-900">RM<?php echo number_format($summary['total_payments'], 2); ?></p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-yellow-100 text-yellow-600">
                    <i class="fas fa-users text-2xl"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-500">Active Users</p>
                    <p class="text-2xl font-semibold text-gray-900"><?php echo number_format($summary['total_users']); ?></p>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="bg-white shadow rounded-lg p-6">
        <form action="" method="GET" class="space-y-4">
            <?php if ($user_id): ?>
                <input type="hidden" name="user_id" value="<?php echo htmlspecialchars($user_id); ?>">
            <?php endif; ?>
            
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div>
                    <label for="type" class="block text-sm font-medium text-gray-700">Transaction Type</label>
                    <select name="type" id="type" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                        <option value="">All Types</option>
                        <option value="loan" <?php echo $type === 'loan' ? 'selected' : ''; ?>>Loans</option>
                        <option value="payment" <?php echo $type === 'payment' ? 'selected' : ''; ?>>Payments</option>
                    </select>
                </div>

                <div>
                    <label for="status" class="block text-sm font-medium text-gray-700">Status</label>
                    <select name="status" id="status" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                        <option value="">All Status</option>
                        <option value="approved" <?php echo $status === 'approved' ? 'selected' : ''; ?>>Approved</option>
                        <option value="pending" <?php echo $status === 'pending' ? 'selected' : ''; ?>>Pending</option>
                        <option value="rejected" <?php echo $status === 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                    </select>
                </div>

                <div>
                    <label for="date_from" class="block text-sm font-medium text-gray-700">Date From</label>
                    <input type="date" name="date_from" id="date_from" 
                           value="<?php echo $date_from; ?>"
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                </div>

                <div>
                    <label for="date_to" class="block text-sm font-medium text-gray-700">Date To</label>
                    <input type="date" name="date_to" id="date_to" 
                           value="<?php echo $date_to; ?>"
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                </div>
            </div>

            <div class="flex justify-end">
                <button type="submit" class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700">
                    <i class="fas fa-filter mr-2"></i>
                    Apply Filters
                </button>
            </div>
        </form>
    </div>

    <!-- Transactions Table -->
    <div class="bg-white shadow rounded-lg">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead>
                    <tr>
                        <th class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                        <th class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Member ID</th>
                        <th class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                        <th class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                        <th class="px-6 py-3 bg-gray-50 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
                        <th class="px-6 py-3 bg-gray-50 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 bg-gray-50 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php foreach ($transactions as $transaction): ?>
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            <?php echo date('Y-m-d H:i', strtotime($transaction['created_at'])); ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                            <?php echo htmlspecialchars($transaction['member_id']); ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            <?php echo htmlspecialchars($transaction['full_name']); ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?php echo $transaction['type'] === 'loan' ? 'bg-yellow-100 text-yellow-800' : 'bg-green-100 text-green-800'; ?>">
                                <?php echo ucfirst($transaction['type']); ?>
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-right <?php echo $transaction['type'] === 'loan' ? 'text-red-600' : 'text-green-600'; ?>">
                            ₱<?php echo number_format(abs($transaction['amount_with_sign']), 2); ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-center">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                <?php echo $transaction['status'] === 'approved' ? 'bg-green-100 text-green-800' : 
                                    ($transaction['status'] === 'pending' ? 'bg-yellow-100 text-yellow-800' : 'bg-red-100 text-red-800'); ?>">
                                <?php echo ucfirst($transaction['status']); ?>
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-center text-sm">
                            <a href="/admin/payments.php?user_id=<?php echo $transaction['user_id']; ?>" 
                               class="text-indigo-600 hover:text-indigo-900">
                                <i class="fas fa-eye"></i>
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
