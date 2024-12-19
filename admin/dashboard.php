<?php
$page_title = 'Dashboard';

try {
    require_once '../config/database.php';
    
    // Get total number of users
    $stmt = $pdo->query("SELECT COUNT(*) as total_users FROM users WHERE role = 'user'");
    $total_users = $stmt->fetch()['total_users'];

    // Get total outstanding balance
    $stmt = $pdo->query("
        SELECT 
            COALESCE(SUM(CASE WHEN type = 'loan' THEN amount ELSE -amount END), 0) as total_outstanding 
        FROM transactions 
        WHERE status = 'approved'
    ");
    $total_outstanding = $stmt->fetch()['total_outstanding'];

    // Get total payments last month
    $stmt = $pdo->query("
        SELECT COALESCE(SUM(amount), 0) as total_payments 
        FROM payments 
        WHERE payment_date >= DATE_SUB(NOW(), INTERVAL 1 MONTH)
    ");
    $total_payments = $stmt->fetch()['total_payments'];

    // Get users with outstanding debts
    $stmt = $pdo->query("
        SELECT 
            u.member_id, 
            u.full_name,
            COALESCE(SUM(CASE WHEN t.type = 'loan' THEN t.amount ELSE -t.amount END), 0) as outstanding_balance,
            MAX(t.created_at) as last_transaction
        FROM users u
        LEFT JOIN transactions t ON u.id = t.user_id AND t.status = 'approved'
        WHERE u.role = 'user'
        GROUP BY u.id, u.member_id, u.full_name
        HAVING outstanding_balance > 0
        ORDER BY outstanding_balance DESC
        LIMIT 10
    ");
    $outstanding_users = $stmt->fetchAll();

} catch (PDOException $e) {
    error_log("Dashboard Error: " . $e->getMessage());
    $error = "An error occurred while loading the dashboard.";
}

// Start output buffering
ob_start();
?>

<!-- Statistics Cards -->
<div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
    <div class="bg-white overflow-hidden shadow rounded-lg">
        <div class="p-5">
            <div class="flex items-center">
                <div class="flex-shrink-0 bg-blue-500 rounded-md p-3">
                    <i class="fas fa-users text-white text-2xl"></i>
                </div>
                <div class="ml-5 w-0 flex-1">
                    <dl>
                        <dt class="text-sm font-medium text-gray-500 truncate">Total Users</dt>
                        <dd class="text-2xl font-semibold text-gray-900"><?php echo number_format($total_users); ?></dd>
                    </dl>
                </div>
            </div>
        </div>
    </div>

    <div class="bg-white overflow-hidden shadow rounded-lg">
        <div class="p-5">
            <div class="flex items-center">
                <div class="flex-shrink-0 bg-green-500 rounded-md p-3">
                    <i class="fas fa-money-bill-wave text-white text-2xl"></i>
                </div>
                <div class="ml-5 w-0 flex-1">
                    <dl>
                        <dt class="text-sm font-medium text-gray-500 truncate">Outstanding Balance</dt>
                        <dd class="text-2xl font-semibold text-gray-900">$<?php echo number_format($total_outstanding, 2); ?></dd>
                    </dl>
                </div>
            </div>
        </div>
    </div>

    <div class="bg-white overflow-hidden shadow rounded-lg">
        <div class="p-5">
            <div class="flex items-center">
                <div class="flex-shrink-0 bg-indigo-500 rounded-md p-3">
                    <i class="fas fa-chart-line text-white text-2xl"></i>
                </div>
                <div class="ml-5 w-0 flex-1">
                    <dl>
                        <dt class="text-sm font-medium text-gray-500 truncate">Payments Last Month</dt>
                        <dd class="text-2xl font-semibold text-gray-900">$<?php echo number_format($total_payments, 2); ?></dd>
                    </dl>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Users with Outstanding Debts -->
<div class="bg-white shadow overflow-hidden sm:rounded-lg">
    <div class="px-4 py-5 border-b border-gray-200 sm:px-6">
        <div class="flex justify-between items-center">
            <h3 class="text-lg leading-6 font-medium text-gray-900">Users with Outstanding Debts</h3>
            <a href="users.php" class="text-blue-600 hover:text-blue-800 text-sm">View All Users →</a>
        </div>
    </div>
    <div class="bg-white">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Member ID</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Outstanding Balance</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Last Transaction</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php foreach ($outstanding_users as $user): ?>
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                            <?php echo htmlspecialchars($user['member_id']); ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            <?php echo htmlspecialchars($user['full_name']); ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            $<?php echo number_format($user['outstanding_balance'], 2); ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            <?php echo date('Y-m-d', strtotime($user['last_transaction'])); ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                            <a href="payments.php?member_id=<?php echo urlencode($user['member_id']); ?>" 
                               class="text-blue-600 hover:text-blue-900 mr-3">
                                <i class="fas fa-money-bill-wave"></i> Process Payment
                            </a>
                            <a href="user_details.php?member_id=<?php echo urlencode($user['member_id']); ?>" 
                               class="text-gray-600 hover:text-gray-900">
                                <i class="fas fa-eye"></i> View Details
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
require_once 'templates/layout.php';
?>
