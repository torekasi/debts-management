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
    'outstanding_balance' => 0,
    'today_transactions' => 0
];

try {
    // Get total users
    $stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'user'");
    $stats['total_users'] = $stmt->fetchColumn();

    // Get total transactions amount
    $stmt = $pdo->query("SELECT COALESCE(SUM(amount), 0) FROM transactions");
    $stats['total_transactions'] = $stmt->fetchColumn();

    // Get total payments
    $stmt = $pdo->query("SELECT COALESCE(SUM(amount), 0) FROM payments");
    $stats['total_payments'] = $stmt->fetchColumn();

    // Calculate outstanding balance
    $stats['outstanding_balance'] = $stats['total_transactions'] - $stats['total_payments'];


    // Get today's transactions
    $stmt = $pdo->query("SELECT COALESCE(SUM(amount), 0) FROM transactions WHERE DATE(date_transaction) = CURDATE()");
    $stats['today_transactions'] = $stmt->fetchColumn();



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

    // Get recent transactions with pagination
    $date_filter = isset($_GET['date_filter']) ? $_GET['date_filter'] : 'today';
    $custom_start = isset($_GET['custom_start']) ? $_GET['custom_start'] : '';
    $custom_end = isset($_GET['custom_end']) ? $_GET['custom_end'] : '';
    
    $date_condition = '';
    switch ($date_filter) {
        case 'today':
            $date_condition = "AND DATE(t.date_transaction) = CURDATE()";
            break;
        case 'this_week':
            $date_condition = "AND YEARWEEK(t.date_transaction) = YEARWEEK(CURDATE())";
            break;
        case 'this_month':
            $date_condition = "AND YEAR(t.date_transaction) = YEAR(CURDATE()) AND MONTH(t.date_transaction) = MONTH(CURDATE())";
            break;
        case 'last_month':
            $date_condition = "AND t.date_transaction >= DATE_SUB(DATE_FORMAT(CURDATE() ,'%Y-%m-01'), INTERVAL 1 MONTH) 
                              AND t.date_transaction < DATE_FORMAT(CURDATE() ,'%Y-%m-01')";
            break;
        case 'custom':
            if ($custom_start && $custom_end) {
                $date_condition = "AND DATE(t.date_transaction) BETWEEN :custom_start AND :custom_end";
            }
            break;
    }

    // Get total number of transactions for pagination with date filter
    $count_sql = "SELECT COUNT(*) FROM transactions t 
                  JOIN users u ON t.user_id = u.id 
                  WHERE t.type != 'debt' $date_condition";
    if ($date_filter === 'custom' && $custom_start && $custom_end) {
        $stmt = $pdo->prepare($count_sql);
        $stmt->bindParam(':custom_start', $custom_start);
        $stmt->bindParam(':custom_end', $custom_end);
    } else {
        $stmt = $pdo->query($count_sql);
    }
    $total_transactions = $stmt->fetchColumn();
    $total_pages_transactions = ceil($total_transactions / $items_per_page);

    // Get recent transactions with pagination and date filter
    $sql = "SELECT t.id, t.amount, t.description, t.image_path, t.date_transaction, u.full_name, u.id as user_id
            FROM transactions t
            JOIN users u ON t.user_id = u.id
            WHERE t.type != 'debt' $date_condition
            ORDER BY t.date_transaction DESC
            LIMIT :offset, :limit";

    $stmt = $pdo->prepare($sql);
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
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-8">
                <!-- Total Users -->
                <div class="bg-white overflow-hidden shadow rounded-lg">
                    <div class="p-5">
                        <div class="flex items-center">
                            <div class="flex-shrink-0 bg-indigo-500 rounded-md p-3">
                                <svg class="h-6 w-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                                </svg>
                            </div>
                            <div class="ml-5 w-0 flex-1">
                                <dl>
                                    <dt class="text-sm font-medium text-gray-500 truncate">Total Users</dt>
                                    <dd class="text-lg font-semibold text-gray-900"><?php echo number_format($stats['total_users']); ?></dd>
                                </dl>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Total Transactions -->
                <div class="bg-white overflow-hidden shadow rounded-lg">
                    <div class="p-5">
                        <div class="flex items-center">
                            <div class="flex-shrink-0 bg-green-500 rounded-md p-3">
                                <svg class="h-6 w-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                            <div class="ml-5 w-0 flex-1">
                                <dl>
                                    <dt class="text-sm font-medium text-gray-500 truncate">Total Transactions</dt>
                                    <dd class="text-lg font-semibold text-gray-900">RM <?php echo number_format($stats['total_transactions'], 2); ?></dd>
                                </dl>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Outstanding Balance -->
                <div class="bg-white overflow-hidden shadow rounded-lg">
                    <div class="p-5">
                        <div class="flex items-center">
                            <div class="flex-shrink-0 bg-red-500 rounded-md p-3">
                                <svg class="h-6 w-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
                                </svg>
                            </div>
                            <div class="ml-5 w-0 flex-1">
                                <dl>
                                    <dt class="text-sm font-medium text-gray-500 truncate">Outstanding Balance</dt>
                                    <dd class="text-lg font-semibold text-gray-900">RM <?php echo number_format($stats['outstanding_balance'], 2); ?></dd>
                                </dl>
                            </div>
                        </div>
                    </div>
                </div>


                <!-- Today's Transactions -->
                <div class="bg-white overflow-hidden shadow rounded-lg">
                    <div class="p-5">
                        <div class="flex items-center">
                            <div class="flex-shrink-0 bg-yellow-500 rounded-md p-3">
                                <svg class="h-6 w-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                                </svg>
                            </div>
                            <div class="ml-5 w-0 flex-1">
                                <dl>
                                    <dt class="text-sm font-medium text-gray-500 truncate">Today's Transactions</dt>
                                    <dd class="text-lg font-semibold text-gray-900">RM <?php echo number_format($stats['today_transactions'], 2); ?></dd>
                                </dl>
                            </div>
                        </div>
                    </div>
                </div>



            </div>

            <!-- Monthly Chart -->
            <div class="bg-white shadow rounded-lg p-6 mb-8">
                <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">Monthly Overview</h3>
                <div class="relative" style="height: 300px;">
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
                            <div class="filter-container">
                                <form class="space-y-4" method="GET">
                                    <div>
                                        <label class="filter-label">Filter Transactions By:</label>
                                        <div class="flex items-end space-x-4">
                                            <div class="flex-1">
                                                <div class="relative">
                                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                                        <svg class="h-8 w-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                                        </svg>
                                                    </div>
                                                    <select name="date_filter" class="filter-select focus:ring-indigo-500 focus:border-indigo-500 block w-full pl-14 h-12 sm:text-sm border-2 border-gray-300 rounded-lg">
                                                        <option value="all" <?php echo $date_filter === 'all' ? 'selected' : ''; ?>>All Time</option>
                                                        <option value="today" <?php echo $date_filter === 'today' ? 'selected' : ''; ?>>Today</option>
                                                        <option value="this_week" <?php echo $date_filter === 'this_week' ? 'selected' : ''; ?>>This Week</option>
                                                        <option value="this_month" <?php echo $date_filter === 'this_month' ? 'selected' : ''; ?>>This Month</option>
                                                        <option value="last_month" <?php echo $date_filter === 'last_month' ? 'selected' : ''; ?>>Last Month</option>
                                                        <option value="custom" <?php echo $date_filter === 'custom' ? 'selected' : ''; ?>>Custom Range</option>
                                                    </select>
                                                </div>
                                            </div>
                                            
                                            <div id="custom-date-range" class="flex space-x-4" style="display: <?php echo $date_filter === 'custom' ? 'flex' : 'none'; ?>">
                                                <div class="relative">
                                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                                        <svg class="h-8 w-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                                        </svg>
                                                    </div>
                                                    <input type="date" name="custom_start" value="<?php echo $custom_start; ?>" 
                                                           class="date-input focus:ring-indigo-500 focus:border-indigo-500 block w-full pl-14 h-12 sm:text-sm border-2 border-gray-300 rounded-lg">
                                                </div>
                                                <div class="relative">
                                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                                        <svg class="h-8 w-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                                        </svg>
                                                    </div>
                                                    <input type="date" name="custom_end" value="<?php echo $custom_end; ?>" 
                                                           class="date-input focus:ring-indigo-500 focus:border-indigo-500 block w-full pl-14 h-12 sm:text-sm border-2 border-gray-300 rounded-lg">
                                                </div>
                                            </div>

                                            <div>
                                                <input type="hidden" name="trans_page" value="<?php echo $current_page_transactions; ?>">
                                                <input type="hidden" name="act_page" value="<?php echo $current_page_activities; ?>">
                                                <input type="hidden" name="pay_page" value="<?php echo $current_page_payments; ?>">
                                                <button type="submit" 
                                                        class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 h-12" onclick="event.preventDefault(); this.form.submit()">
                                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/>
                                                    </svg>
                                                    Apply Filter
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </form>
                            </div>
                            <div class="shadow overflow-hidden border-b border-gray-200 sm:rounded-lg">
                                <table class="min-w-full divide-y divide-gray-200">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">User</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Description</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date Transaction</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Image</th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white divide-y divide-gray-200">
                                        <?php foreach ($recent_transactions as $transaction): ?>
                                        <tr class="hover:bg-green-100">
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                                <a href="/admin/user_dashboard.php?user_id=<?php echo $transaction['user_id']; ?>" class="text-blue-600 hover:text-blue-800">
                                                    <?php echo htmlspecialchars($transaction['full_name'] ?? ''); ?>
                                                </a>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                RM <?php echo number_format($transaction['amount'] ?? 0, 2); ?>
                                            </td>
                                            <td class="px-6 py-4 text-sm text-gray-500 description-cell">
                                                <?php echo htmlspecialchars($transaction['description'] ?? ''); ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                <?php echo date('d M Y | h:i A', strtotime($transaction['date_transaction'] ?? '')); ?>
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
                            <div class="px-4 py-3 flex items-center justify-between border-t border-gray-200 sm:px-6">
                                <div class="flex-1 flex justify-between items-center">
                                    <?php if ($current_page_transactions > 1): ?>
                                        <a href="?trans_page=<?php echo ($current_page_transactions - 1); ?>&act_page=<?php echo $current_page_activities; ?>&pay_page=<?php echo $current_page_payments; ?>" class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">Previous</a>
                                    <?php endif; ?>
                                    <span class="text-sm text-gray-700">
                                        Page <?php echo $current_page_transactions; ?> of <?php echo $total_pages_transactions; ?>
                                    </span>
                                    <?php if ($current_page_transactions < $total_pages_transactions): ?>
                                        <a href="?trans_page=<?php echo ($current_page_transactions + 1); ?>&act_page=<?php echo $current_page_activities; ?>&pay_page=<?php echo $current_page_payments; ?>" class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">Next</a>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Image Modal -->
            <div id="imageModal" class="fixed z-10 inset-0 overflow-y-auto hidden" onclick="closeImageModal()">
                <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
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
                function showImageModal(imagePath) {
                    document.getElementById('modalImage').src = imagePath;
                    document.getElementById('imageModal').classList.remove('hidden');
                }

                function closeImageModal() {
                    document.getElementById('imageModal').classList.add('hidden');
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