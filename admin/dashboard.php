<?php
require_once '../includes/config.php';
require_once '../includes/session.php';

// Require admin authentication
requireAdmin();

// Pagination settings
$items_per_page = 10;
$current_page_transactions = isset($_GET['trans_page']) ? (int)$_GET['trans_page'] : 1;
$current_page_activities = isset($_GET['act_page']) ? (int)$_GET['act_page'] : 1;
$current_page_payments = isset($_GET['pay_page']) ? (int)$_GET['pay_page'] : 1;
$offset_transactions = ($current_page_transactions - 1) * $items_per_page;
$offset_activities = ($current_page_activities - 1) * $items_per_page;
$offset_payments = ($current_page_payments - 1) * $items_per_page;

// Date filter settings
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-d H:i:s', strtotime('today midnight'));
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d H:i:s', strtotime('tomorrow midnight') - 1);

// Database connection
try {
    // For Aiven MySQL, we need to use SSL connection
    $options = array(
        // PDO::MYSQL_ATTR_SSL_CA => __DIR__ . '/../config/ca.pem',  // Path to CA certificate
        // PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT => false
    );

    $dsn = sprintf(
        "mysql:host=%s;port=%s;dbname=%s",
        DB_HOST,
        DB_PORT,
        DB_NAME
    );

    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// Get dashboard statistics
$stats = [
    'total_users' => 0,
    'total_transactions' => 0,
    'total_payments' => 0,
    'outstanding_balance' => 0
];

try {
    // Get total users
    $stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'user'");
    $stats['total_users'] = $stmt->fetchColumn();

    // Get total transactions
    $stmt = $pdo->query("SELECT COUNT(*) FROM transactions");
    $stats['total_transactions'] = $stmt->fetchColumn();

    // Get total payments
    $stmt = $pdo->query("SELECT COALESCE(SUM(amount), 0) FROM payments");
    $stats['total_payments'] = $stmt->fetchColumn();

    // Calculate outstanding balance
    $stats['outstanding_balance'] = $stats['total_transactions'] - $stats['total_payments']; // Added semicolon here

    // Get monthly transactions and payments for the last 12 months
    $stmt = $pdo->query("
        SELECT 
            DATE_FORMAT(created_at, '%Y-%m') as month,
            COUNT(*) as count,
            COALESCE(SUM(amount), 0) as total_amount
        FROM transactions
        WHERE created_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
        GROUP BY DATE_FORMAT(created_at, '%Y-%m')
        ORDER BY month ASC
    ");
    $monthly_transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $stmt = $pdo->query("
        SELECT 
            DATE_FORMAT(payment_date, '%Y-%m') as month,
            COUNT(*) as count,
            COALESCE(SUM(amount), 0) as total_amount
        FROM payments
        WHERE payment_date >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
        GROUP BY DATE_FORMAT(payment_date, '%Y-%m')
        ORDER BY month ASC
    ");
    $monthly_payments = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Format data for Chart.js
    $chart_labels = [];
    $transaction_amounts = [];
    $payment_amounts = [];
    
    // Get all unique months
    $all_months = [];
    
    // Get the last 12 months
    for ($i = 11; $i >= 0; $i--) {
        $month = date('Y-m', strtotime("-$i months"));
        $all_months[$month] = true;
    }
    
    // Fill in the data arrays
    foreach (array_keys($all_months) as $month) {
        // Format month to show abbreviated month name
        $chart_labels[] = date('M Y', strtotime($month . '-01'));
        
        // Find transaction amount for this month
        $trans_amount = 0;
        foreach ($monthly_transactions as $row) {
            if ($row['month'] === $month) {
                $trans_amount = floatval($row['total_amount']);
                break;
            }
        }
        $transaction_amounts[] = $trans_amount;
        
        // Find payment amount for this month
        $pay_amount = 0;
        foreach ($monthly_payments as $row) {
            if ($row['month'] === $month) {
                $pay_amount = floatval($row['total_amount']);
                break;
            }
        }
        $payment_amounts[] = $pay_amount;
    }

    // Get total number of transactions for pagination
    $stmt = $pdo->query("SELECT COUNT(*) FROM transactions");
    $total_transactions = $stmt->fetchColumn();
    $total_pages_transactions = ceil($total_transactions / $items_per_page);

    // Get recent transactions with pagination
    $stmt = $pdo->prepare("
        SELECT t.id, t.amount, t.description, t.image_path, t.date_transaction, u.full_name, u.id as user_id
        FROM transactions t
        JOIN users u ON t.user_id = u.id
        WHERE t.type != 'debt'
        ORDER BY t.date_transaction DESC
        LIMIT :offset, :limit
    ");
    $stmt->bindValue(':offset', $offset_transactions, PDO::PARAM_INT);
    $stmt->bindValue(':limit', $items_per_page, PDO::PARAM_INT);
    if ($date_filter === 'custom' && $custom_start && $custom_end) {
        $stmt->bindParam(':custom_start', $custom_start);
        $stmt->bindParam(':custom_end', $custom_end);
    }
    $stmt->execute();
    $recent_transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get total number of activities for pagination
    $stmt = $pdo->query("SELECT COUNT(*) FROM activity_logs");
    $total_activities = $stmt->fetchColumn();
    $total_pages_activities = ceil($total_activities / $items_per_page);

    // Get recent activity logs with pagination
    $stmt = $pdo->prepare("
        SELECT l.id, l.action, l.description, l.ip_address, l.created_at, u.full_name
        FROM activity_logs l
        LEFT JOIN users u ON l.user_id = u.id
        ORDER BY l.created_at DESC
        LIMIT :offset, :limit
    ");
    $stmt->bindValue(':offset', $offset_activities, PDO::PARAM_INT);
    $stmt->bindValue(':limit', $items_per_page, PDO::PARAM_INT);
    $stmt->execute();
    $recent_activities = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get total number of payments for pagination
    $stmt = $pdo->query("SELECT COUNT(*) FROM payments");
    $total_payments = $stmt->fetchColumn();
    $total_pages_payments = ceil($total_payments / $items_per_page);

    // Get recent payments with pagination
    $stmt = $pdo->prepare("
        SELECT p.id, p.transaction_id, p.user_id, p.amount, p.payment_date, p.created_at, u.full_name
        FROM payments p
        JOIN users u ON p.user_id = u.id
        ORDER BY p.created_at DESC
        LIMIT :offset, :limit
    ");
    $stmt->bindValue(':offset', $offset_payments, PDO::PARAM_INT);
    $stmt->bindValue(':limit', $items_per_page, PDO::PARAM_INT);
    $stmt->execute();
    $recent_payments = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("Query failed: " . $e->getMessage());
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - <?php echo APP_NAME; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/momentjs/latest/moment.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.min.js"></script>
    <link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.css" />
    <style>
        .collapsed {
            display: none;
        }
        .description-cell {
            max-width: 300px;
            white-space: normal;
            word-wrap: break-word;
        }
        .filter-container {
            background-color: #f9fafb;
            border: 1px solid #e5e7eb;
            border-radius: 0.5rem;
            padding: 1rem;
            margin-bottom: 1rem;
        }
        .filter-label {
            font-size: 0.875rem;
            font-weight: 500;
            color: #374151;
            margin-bottom: 0.5rem;
            display: block;
        }
        .filter-select {
            min-width: 200px;
            height: 48px;
            padding-left: 12px;
            border-width: 2px;
            border-color: #D1D5DB;
            border-radius: 0.5rem;
            font-size: 0.875rem;
            line-height: 1.25rem;
        }
        .filter-select:focus {
            outline: none;
            border-color: #6366F1;
            box-shadow: 0 0 0 2px rgba(99, 102, 241, 0.2);
        }
        .date-inputs {
            display: flex;
            gap: 1rem;
            align-items: center;
        }
        .date-inputs label {
            font-size: 0.875rem;
            color: #6b7280;
        }
        .date-input {
            height: 48px;
            padding-left: 12px;
            border-width: 2px;
            border-color: #D1D5DB;
            border-radius: 0.5rem;
            font-size: 0.875rem;
            line-height: 1.25rem;
            width: 100%;
        }
        .date-input:focus {
            outline: none;
            border-color: #6366F1;
            box-shadow: 0 0 0 2px rgba(99, 102, 241, 0.2);
        }
    </style>
</head>
<body class="bg-gray-100">
    <div class="min-h-screen">
        <!-- Navigation -->
        <?php require_once 'template/header.php'; ?>
        
        <!-- Main Content -->
        <main class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
            <!-- Statistics Cards -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-8">
                <!-- Total Payments recieved -->
                <div class="bg-white overflow-hidden shadow rounded-lg">
                    <div class="p-5">
                        <div class="flex items-center">
                            <div class="flex-shrink-0 bg-indigo-500 rounded-md p-3">
                                <i class="fas fa-hand-holding-usd text-white text-2xl"></i>
                            </div>
                            <div class="ml-5 w-0 flex-1">
                                <dl>
                                    <dt class="text-sm font-medium text-gray-500 truncate">Total Payments Recieved</dt>
                                    <dd class="text-lg font-semibold text-gray-900">RM <?php echo number_format($stats['total_payments'], 2); ?></dd>
                                </dl>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Total Amount -->
                <div class="bg-white overflow-hidden shadow rounded-lg">
                    <div class="p-5">
                        <div class="flex items-center">
                            <div class="flex-shrink-0 bg-red-500 rounded-md p-3">
                                <i class="fas fa-hand-holding-usd text-white text-2xl"></i>
                            </div>
                            <div class="ml-5 w-0 flex-1">
                                <dl>
                                    <dt class="text-sm font-medium text-gray-500 truncate">Total Transaction Amount</dt>
                                    <dd class="text-lg font-semibold text-gray-900">RM <?php echo number_format($stats['total_transactions'], 2); ?></dd>
                                </dl>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Outstanding Balance -->
                <div class="bg-green-100 overflow-hidden shadow rounded-lg">
                    <div class="p-5">
                        <div class="flex items-center">
                            <div class="flex-shrink-0 bg-green-500 rounded-md p-3">
                                <i class="fas fa-balance-scale text-white text-2xl"></i>
                            </div>
                            <div class="ml-5 w-0 flex-1">
                                <dl>
                                    <dt class="text-sm font-medium text-green-700 truncate">Outstanding Balance</dt>
                                    <dd class="text-lg font-semibold text-green-900">RM <?php echo number_format($stats['outstanding_balance'], 2); ?></dd>
                                </dl>
                            </div>
                        </div>
                    </div>
                </div>



            </div>

            <!-- Monthly Transactions vs Payments Chart -->
            <div class="bg-white shadow rounded-lg mb-8 p-6">
                <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">Monthly Transactions vs Payments</h3>
                <div class="w-full" style="height: 350px;">
                    <canvas id="monthlyChart"></canvas>
                </div>
                <div class="flex justify-end gap-4 mt-4">
                    <a href="add_transaction.php" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        Add Transaction
                    </a>
                    <a href="add_payment.php" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        Add Payment
                    </a>
                </div>
            </div>

            <!-- Recent Transactions -->
            <div class="bg-white shadow rounded-lg mb-8">
                <div class="px-4 py-5 sm:px-6 cursor-pointer" onclick="toggleSection('transactions')">
                    <div class="flex justify-between items-center">
                        <h3 class="text-lg leading-6 font-medium text-gray-900">Recent Transactions</h3>
                        <svg id="transactions-icon" class="h-5 w-5 transform transition-transform duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                        </svg>
                    </div>
                </div>
                <div id="transactions-content" class="flex flex-col">
                    <div class="-my-2 overflow-x-auto sm:-mx-6 lg:-mx-8">
                        <div class="py-2 align-middle inline-block min-w-full sm:px-6 lg:px-8">
                            <!-- Date Filter -->
                            <div class="filter-container mb-4">
                                <form class="flex flex-wrap items-end gap-4" method="GET">
                                    <div class="flex-1 min-w-[200px]">
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Filter Transactions By:</label>
                                        <select name="date_filter" class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md">
                                            <option value="today" <?php echo $date_filter === 'today' ? 'selected' : ''; ?>>Today</option>
                                            <option value="this_week" <?php echo $date_filter === 'this_week' ? 'selected' : ''; ?>>This Week</option>
                                            <option value="this_month" <?php echo $date_filter === 'this_month' ? 'selected' : ''; ?>>This Month</option>
                                            <option value="last_month" <?php echo $date_filter === 'last_month' ? 'selected' : ''; ?>>Last Month</option>
                                            <option value="custom" <?php echo $date_filter === 'custom' ? 'selected' : ''; ?>>Custom Range</option>
                                        </select>
                                    </div>
                                    
                                    <div id="custom-date-range" class="flex gap-4 flex-1 min-w-[400px]" style="<?php echo $date_filter === 'custom' ? '' : 'display: none;'; ?>">
                                        <div class="flex-1">
                                            <label class="block text-sm font-medium text-gray-700 mb-1">Start Date</label>
                                            <input type="date" name="custom_start" value="<?php echo $custom_start; ?>" 
                                                class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md">
                                        </div>
                                        <div class="flex-1">
                                            <label class="block text-sm font-medium text-gray-700 mb-1">End Date</label>
                                            <input type="date" name="custom_end" value="<?php echo $custom_end; ?>" 
                                                class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md">
                                        </div>
                                    </div>

                                    <div class="flex items-end">
                                        <input type="hidden" name="trans_page" value="<?php echo $current_page_transactions; ?>">
                                        <input type="hidden" name="act_page" value="<?php echo $current_page_activities; ?>">
                                        <input type="hidden" name="pay_page" value="<?php echo $current_page_payments; ?>">
                                        <button type="submit" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                            Apply Filter
                                        </button>
                                    </div>
                                </form>
                            </div>
                            <div class="shadow overflow-hidden border-b border-gray-200 sm:rounded-lg">
                                <div class="px-4 py-3 bg-gray-50 border-b border-gray-200">
                                    <div class="flex flex-col sm:flex-row gap-4 items-start sm:items-end">
                                        <!-- Date Range Input -->
                                        <div class="w-full sm:w-64">
                                            <label for="date_range" class="block text-sm font-medium text-gray-700 mb-1">Date Range</label>
                                            <input type="text" id="date_range" name="date_range" class="focus:ring-indigo-500 focus:border-indigo-500 block w-full pl-12 h-12 sm:text-sm border-2 border-gray-300 rounded-lg" style="width: 400px;">
                                        </div>
                                        
                                        <!-- Quick Filter Buttons 
                                        <div class="flex flex-wrap gap-2">
                                            <button onclick="setDateRange('today')" class="inline-flex items-center px-3 py-2 border border-gray-300 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-colors">
                                                Today
                                            </button>
                                            <button onclick="setDateRange('this_week')" class="inline-flex items-center px-3 py-2 border border-gray-300 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-colors">
                                                This Week
                                            </button>
                                            <button onclick="setDateRange('this_month')" class="inline-flex items-center px-3 py-2 border border-gray-300 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-colors">
                                                This Month
                                            </button>
                                            <button onclick="setDateRange('last_month')" class="inline-flex items-center px-3 py-2 border border-gray-300 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-colors">
                                                Last Month
                                            </button>
                                        </div>
                                        
                                         Apply Filter Button
                                        <button onclick="applyDateFilter()" class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-colors">
                                            Apply Filter
                                        </button> -->
                                    </div>
                                </div>
                                <table class="min-w-full divide-y divide-gray-200">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">User</th>
                                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
                                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Description</th>
                                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Image</th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white divide-y divide-gray-200">
                                        <?php foreach ($recent_transactions as $transaction): ?>
                                        <tr class="hover:bg-green-100">
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                                <?php echo htmlspecialchars($transaction['full_name'] ?? ''); ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                RM <?php echo number_format($transaction['amount'] ?? 0, 2); ?>
                                            </td>
                                            <td class="px-6 py-4 text-sm text-gray-500 max-w-xs">
                                                <div class="break-words whitespace-pre-wrap">
                                                    <?php echo htmlspecialchars($transaction['description'] ?? ''); ?>
                                                </div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                <?php echo date('d M Y | h:i A', strtotime($transaction['created_at'] ?? '')); ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                <?php if ($transaction['image_path']): ?>
                                                    <button onclick="showImageModal('<?php echo htmlspecialchars($transaction['image_path']); ?>')" class="text-blue-500">View Image</button>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            <!-- Transactions Pagination -->
                            <?php if ($total_pages_transactions > 1): ?>
                            <div class="bg-white px-4 py-3 flex items-center justify-between border-t border-gray-200 sm:px-6">
                                <div class="flex-1 flex justify-between sm:hidden">
                                    <?php if ($current_page_transactions > 1): ?>
                                        <a href="?trans_page=<?php echo ($current_page_transactions - 1); ?>&act_page=<?php echo $current_page_activities; ?>&pay_page=<?php echo $current_page_payments; ?>" class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">Previous</a>
                                    <?php endif; ?>
                                    <?php if ($current_page_transactions < $total_pages_transactions): ?>
                                        <a href="?trans_page=<?php echo ($current_page_transactions + 1); ?>&act_page=<?php echo $current_page_activities; ?>&pay_page=<?php echo $current_page_payments; ?>" class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">Next</a>
                                    <?php endif; ?>
                                </div>
                                <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
                                    <div>
                                        <p class="text-sm text-gray-700">
                                            Showing
                                            <span class="font-medium"><?php echo $offset_transactions + 1; ?></span>
                                            to
                                            <span class="font-medium"><?php echo min($offset_transactions + $items_per_page, $total_transactions); ?></span>
                                            of
                                            <span class="font-medium"><?php echo $total_transactions; ?></span>
                                            results
                                        </p>
                                    </div>
                                    <div>
                                        <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px" aria-label="Pagination">
                                            <?php if ($current_page_transactions > 1): ?>
                                                <a href="?trans_page=<?php echo ($current_page_transactions - 1); ?>&date_filter=<?php echo $date_filter; ?><?php echo $date_filter === 'custom' ? '&custom_start=' . $custom_start . '&custom_end=' . $custom_end : ''; ?>" 
                                                   class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
                                                    <span class="sr-only">Previous</span>
                                                    <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                                        <path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd" />
                                                    </svg>
                                                </a>
                                            <?php endif; ?>
                                            <?php for($i = 1; $i <= $total_pages_transactions; $i++): ?>
                                                <a href="?trans_page=<?php echo $i; ?>&date_filter=<?php echo $date_filter; ?><?php echo $date_filter === 'custom' ? '&custom_start=' . $custom_start . '&custom_end=' . $custom_end : ''; ?>" 
                                                   class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium <?php echo $i === $current_page_transactions ? 'text-indigo-600 bg-indigo-50' : 'text-gray-700 hover:bg-gray-50'; ?>">
                                                    <?php echo $i; ?>
                                                </a>
                                            <?php endfor; ?>
                                            <?php if ($current_page_transactions < $total_pages_transactions): ?>
                                                <a href="?trans_page=<?php echo ($current_page_transactions + 1); ?>&date_filter=<?php echo $date_filter; ?><?php echo $date_filter === 'custom' ? '&custom_start=' . $custom_start . '&custom_end=' . $custom_end : ''; ?>" 
                                                   class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
                                                    <span class="sr-only">Next</span>
                                                    <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                                        <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
                                                    </svg>
                                                </a>
                                            <?php endif; ?>
                                        </nav>
                                    </div>
                                </div>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Image Modal -->
            <div id="imageModal" class="fixed z-10 inset-0 overflow-y-auto hidden" onclick="closeImageModal()">
                <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                    <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true"></div>
                    <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
                    <div class="inline-block align-bottom bg-white rounded-lg px-4 pt-5 pb-4 text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full" onclick="event.stopPropagation()">
                        <div>
                            <img id="modalImage" src="" alt="Transaction Image" class="w-full h-auto">
                        </div>
                        <div class="mt-5 sm:mt-6">
                            <button type="button" class="inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-red-600 text-base font-medium text-white hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 sm:text-sm" onclick="closeImageModal()">
                                Close
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <script>
                $(document).ready(function() {
                    // Initialize date range picker
                    $('#date_range').daterangepicker({
                        startDate: '<?php echo $start_date; ?>',
                        endDate: '<?php echo $end_date; ?>',
                        locale: {
                            format: 'YYYY-MM-DD HH:mm:ss'
                        },
                        ranges: {
                            'Today': [moment(), moment()],
                            'This Week': [moment().startOf('week'), moment().endOf('week')],
                            'This Month': [moment().startOf('month'), moment().endOf('month')],
                            'Last Month': [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')]
                        }
                    }, function(start, end) {
                        window.location.href = '?start_date=' + start.format('YYYY-MM-DD HH:mm:ss') + '&end_date=' + end.format('YYYY-MM-DD HH:mm:ss');
                    });
                });

                function setDateRange(range) {
                    const picker = $('#date_range').data('daterangepicker');
                    let start, end;
                    
                    switch(range) {
                        case 'today':
                            start = moment();
                            end = moment();
                            break;
                        case 'this_week':
                            start = moment().startOf('week');
                            end = moment().endOf('week');
                            break;
                        case 'this_month':
                            start = moment().startOf('month');
                            end = moment().endOf('month');
                            break;
                        case 'last_month':
                            start = moment().subtract(1, 'month').startOf('month');
                            end = moment().subtract(1, 'month').endOf('month');
                            break;
                    }
                    
                    if (start && end) {
                        window.location.href = '?start_date=' + start.format('YYYY-MM-DD HH:mm:ss') + '&end_date=' + end.format('YYYY-MM-DD HH:mm:ss');
                    }
                }

                function showImageModal(imagePath) {
                    document.getElementById('modalImage').src = imagePath;
                    document.getElementById('imageModal').classList.remove('hidden');
                }

                function closeImageModal() {
                    document.getElementById('imageModal').classList.add('hidden');
                }

                function toggleSection(section) {
                    const content = document.getElementById(section + '-content');
                    const icon = document.getElementById(section + '-icon');
                    
                    if (content.style.display === 'none') {
                        content.style.display = 'block';
                        icon.style.transform = 'rotate(180deg)';
                    } else {
                        content.style.display = 'none';
                        icon.style.transform = 'rotate(0)';
                    }
                }
            </script>

            <!-- Recent Payments -->
            <div class="bg-white shadow rounded-lg mb-8">
                <div class="px-4 py-5 sm:px-6 cursor-pointer" onclick="toggleSection('payments')">
                    <div class="flex justify-between items-center">
                        <h3 class="text-lg leading-6 font-medium text-gray-900">Recent Payments</h3>
                        <svg id="payments-icon" class="h-5 w-5 transform transition-transform duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                        </svg>
                    </div>
                </div>
                <div id="payments-content" class="flex flex-col" style="display: none;">
                    <div class="-my-2 overflow-x-auto sm:-mx-6 lg:-mx-8">
                        <div class="py-2 align-middle inline-block min-w-full sm:px-6 lg:px-8">
                            <div class="shadow overflow-hidden border-b border-gray-200 sm:rounded-lg">
                                <table class="min-w-full divide-y divide-gray-200">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">User</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Payment Date</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Created At</th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white divide-y divide-gray-200">
                                        <?php foreach ($recent_payments as $payment): ?>
                                        <tr class="hover:bg-green-100">
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                                <?php echo htmlspecialchars($payment['full_name'] ?? ''); ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                RM <?php echo number_format($payment['amount'] ?? 0, 2); ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                <?php echo date('j M Y | g:i A', strtotime($payment['payment_date'] ?? '')); ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                <?php echo date('j M Y | g:i A', strtotime($payment['created_at'] ?? '')); ?>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            <!-- Payments Pagination -->
                            <?php if ($total_pages_payments > 1): ?>
                            <div class="px-4 py-3 flex items-center justify-between border-t border-gray-200 sm:px-6">
                                <div class="flex-1 flex justify-between items-center">
                                    <?php if ($current_page_payments > 1): ?>
                                        <a href="?pay_page=<?php echo ($current_page_payments - 1); ?>&trans_page=<?php echo $current_page_transactions; ?>&act_page=<?php echo $current_page_activities; ?>" class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">Previous</a>
                                    <?php endif; ?>
                                    <span class="text-sm text-gray-700">
                                        Page <?php echo $current_page_payments; ?> of <?php echo $total_pages_payments; ?>
                                    </span>
                                    <?php if ($current_page_payments < $total_pages_payments): ?>
                                        <a href="?pay_page=<?php echo ($current_page_payments + 1); ?>&trans_page=<?php echo $current_page_transactions; ?>&act_page=<?php echo $current_page_activities; ?>" class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">Next</a>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Activities -->
            <div class="bg-white shadow rounded-lg">
                <div class="px-4 py-5 sm:px-6 cursor-pointer" onclick="toggleSection('activities')">
                    <div class="flex justify-between items-center">
                        <h3 class="text-lg leading-6 font-medium text-gray-900">Recent Activities</h3>
                        <svg id="activities-icon" class="h-5 w-5 transform transition-transform duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                        </svg>
                    </div>
                </div>
                <div id="activities-content" class="flex flex-col" style="display: none;">
                    <div class="-my-2 overflow-x-auto sm:-mx-6 lg:-mx-8">
                        <div class="py-2 align-middle inline-block min-w-full sm:px-6 lg:px-8">
                            <div class="shadow overflow-hidden border-b border-gray-200 sm:rounded-lg">
                                <table class="min-w-full divide-y divide-gray-200">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">User</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Action</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">IP Address</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white divide-y divide-gray-200">
                                        <?php foreach ($recent_activities as $activity): ?>
                                        <tr class="hover:bg-green-100">
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                                <?php echo htmlspecialchars($activity['full_name'] ?? 'System'); ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                <?php echo htmlspecialchars($activity['action'] ?? ''); ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                <?php echo htmlspecialchars($activity['ip_address'] ?? ''); ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                <?php echo date('j M Y | g:i A', strtotime($activity['created_at'] ?? '')); ?>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            <!-- Activities Pagination -->
                            <?php if ($total_pages_activities > 1): ?>
                            <div class="px-4 py-3 flex items-center justify-between border-t border-gray-200 sm:px-6">
                                <div class="flex-1 flex justify-between items-center">
                                    <?php if ($current_page_activities > 1): ?>
                                        <a href="?act_page=<?php echo ($current_page_activities - 1); ?>&trans_page=<?php echo $current_page_transactions; ?>&pay_page=<?php echo $current_page_payments; ?>" class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">Previous</a>
                                    <?php endif; ?>
                                    <span class="text-sm text-gray-700">
                                        Page <?php echo $current_page_activities; ?> of <?php echo $total_pages_activities; ?>
                                    </span>
                                    <?php if ($current_page_activities < $total_pages_activities): ?>
                                        <a href="?act_page=<?php echo ($current_page_activities + 1); ?>&trans_page=<?php echo $current_page_transactions; ?>&pay_page=<?php echo $current_page_payments; ?>" class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">Next</a>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- Modal for Notes -->
    <div id="noteModal" class="fixed z-10 inset-0 overflow-y-auto hidden" onclick="closeModal()">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true"></div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
            <div class="inline-block align-bottom bg-white rounded-lg px-4 pt-5 pb-4 text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full" onclick="event.stopPropagation()">
                <div>
                    <div class="mt-3 text-center sm:mt-5">
                        
                        <div class="mt-2 bg-gray-100 p-4 rounded">
                            <p class="text-sm text-gray-700" id="noteContent">
                                <!-- Note content will be injected here -->
                            </p>
                        </div>
                    </div>
                </div>
                <div class="mt-5 sm:mt-6">
                    <button type="button" class="inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-red-600 text-base font-medium text-white hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 sm:text-sm" onclick="closeModal()">
                        Close
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Chart initialization
        const ctx = document.getElementById('monthlyChart').getContext('2d');
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: <?php echo json_encode($chart_labels); ?>,
                datasets: [
                    {
                        label: 'Transactions',
                        data: <?php echo json_encode($transaction_amounts); ?>,
                        backgroundColor: 'rgba(59, 130, 246, 0.5)', // Blue
                        borderColor: 'rgb(59, 130, 246)',
                        borderWidth: 1
                    },
                    {
                        label: 'Payments',
                        data: <?php echo json_encode($payment_amounts); ?>,
                        backgroundColor: 'rgba(16, 185, 129, 0.5)', // Green
                        borderColor: 'rgb(16, 185, 129)',
                        borderWidth: 1
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    x: {
                        ticks: {
                            maxRotation: 0,
                            minRotation: 0
                        }
                    },
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return 'RM ' + value.toLocaleString();
                            }
                        }
                    }
                },
                plugins: {
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return context.dataset.label + ': RM ' + context.raw.toLocaleString();
                            }
                        }
                    }
                }
            }
        });

        // Function to set initial state based on localStorage
        function initializeSections() {
            const sections = ['transactions', 'activities', 'payments'];
            sections.forEach(section => {
                const content = document.getElementById(section + '-content');
                const icon = document.getElementById(section + '-icon');
                const isExpanded = localStorage.getItem(section + '_expanded');
                
                if (section === 'transactions' && isExpanded === null) {
                    // Default state for transactions: expanded
                    content.style.display = 'block';
                    icon.style.transform = 'rotate(180deg)';
                    localStorage.setItem(section + '_expanded', 'true');
                } else if (section === 'activities' && isExpanded === null) {
                    // Default state for activities: collapsed
                    content.style.display = 'none';
                    icon.style.transform = 'rotate(0)';
                    localStorage.setItem(section + '_expanded', 'false');
                } else if (section === 'payments' && isExpanded === null) {
                    // Default state for payments: collapsed
                    content.style.display = 'none';
                    icon.style.transform = 'rotate(0)';
                    localStorage.setItem(section + '_expanded', 'false');
                } else {
                    // Use saved state
                    content.style.display = isExpanded === 'true' ? 'block' : 'none';
                    icon.style.transform = isExpanded === 'true' ? 'rotate(180deg)' : 'rotate(0)';
                }
            });
        }

        function toggleSection(section) {
            const content = document.getElementById(section + '-content');
            const icon = document.getElementById(section + '-icon');
            
            if (content.style.display === 'none') {
                content.style.display = 'block';
                icon.style.transform = 'rotate(180deg)';
                localStorage.setItem(section + '_expanded', 'true');
            } else {
                content.style.display = 'none';
                icon.style.transform = 'rotate(0)';
                localStorage.setItem(section + '_expanded', 'false');
            }
        }

        function showModal(note) {
            document.getElementById('noteContent').textContent = note;
            document.getElementById('noteModal').classList.remove('hidden');
        }

        function closeModal() {
            document.getElementById('noteModal').classList.add('hidden');
        }

        // Initialize sections when page loads
        document.addEventListener('DOMContentLoaded', initializeSections);
    </script>
    <script>
        // Date filter custom range toggle
        document.querySelector('select[name="date_filter"]').addEventListener('change', function() {
            const customDateRange = document.getElementById('custom-date-range');
            if (this.value === 'custom') {
                customDateRange.style.display = 'flex';
            } else {
                customDateRange.style.display = 'none';
            }
        });
    </script>
</body>
</html>