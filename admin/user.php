<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once '../includes/config.php';
require_once '../includes/session.php';

// Require admin authentication
requireAdmin();

// Get user ID from URL parameter
$user_id = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0;

if (!$user_id) {
    $_SESSION['error'] = "Invalid user ID";
    header('Location: users.php');
    exit();
}

try {
    // Fetch user details
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        $_SESSION['error'] = "User not found";
        header('Location: users.php');
        exit();
    }

    // Get financial summary
    $stmt = $pdo->prepare("
        SELECT 
            COALESCE(SUM(amount), 0) as total_debt
        FROM transactions 
        WHERE user_id = ?
    ");
    $stmt->execute([$user_id]);
    $debt_info = $stmt->fetch();

    $stmt = $pdo->prepare("
        SELECT 
            COALESCE(SUM(amount), 0) as total_paid
        FROM payments 
        WHERE user_id = ?
    ");
    $stmt->execute([$user_id]);
    $payment_info = $stmt->fetch();

    $balance = $debt_info['total_debt'] - $payment_info['total_paid'];

    // Get monthly data for the past 12 months
    $stmt = $pdo->prepare("
        SELECT 
            DATE_FORMAT(date_transaction, '%Y-%m') as month,
            SUM(amount) as total_amount
        FROM transactions
        WHERE user_id = ? 
        AND date_transaction >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
        GROUP BY DATE_FORMAT(date_transaction, '%Y-%m')
        ORDER BY month ASC
    ");
    $stmt->execute([$user_id]);
    $monthly_transactions = $stmt->fetchAll();

    $stmt = $pdo->prepare("
        SELECT 
            DATE_FORMAT(payment_date, '%Y-%m') as month,
            SUM(amount) as total_amount
        FROM payments
        WHERE user_id = ? 
        AND payment_date >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
        GROUP BY DATE_FORMAT(payment_date, '%Y-%m')
        ORDER BY month ASC
    ");
    $stmt->execute([$user_id]);
    $monthly_payments = $stmt->fetchAll();

    // Format data for the chart
    $months = [];
    $current_date = new DateTime();
    for ($i = 11; $i >= 0; $i--) {
        $date = clone $current_date;
        $date->modify("-$i months");
        $month_key = $date->format('Y-m');
        $months[$month_key] = [
            'transactions' => 0,
            'payments' => 0
        ];
    }

    // Fill in the actual data
    foreach ($monthly_transactions as $row) {
        if (isset($months[$row['month']])) {
            $months[$row['month']]['transactions'] = $row['total_amount'];
        }
    }

    foreach ($monthly_payments as $row) {
        if (isset($months[$row['month']])) {
            $months[$row['month']]['payments'] = $row['total_amount'];
        }
    }

    // Prepare final chart data
    $chart_labels = [];
    $transaction_data = [];
    $payment_data = [];
    foreach ($months as $month => $data) {
        $chart_labels[] = date('M Y', strtotime($month));
        $transaction_data[] = $data['transactions'];
        $payment_data[] = $data['payments'];
    }

    // Pagination settings
    $items_per_page = 10;

    // Payments pagination
    $current_page_payments = isset($_GET['pay_page']) ? (int)$_GET['pay_page'] : 1;
    $offset_payments = ($current_page_payments - 1) * $items_per_page;

    // Get total payments count
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM payments WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $total_payments = $stmt->fetchColumn();
    $total_pages_payments = ceil($total_payments / $items_per_page);

    // Fetch recent payments
    $stmt = $pdo->prepare("
        SELECT p.*, u.full_name as user_name 
        FROM payments p 
        LEFT JOIN users u ON p.user_id = u.id 
        WHERE p.user_id = ?
        ORDER BY p.payment_date DESC 
        LIMIT ? OFFSET ?
    ");
    $stmt->execute([$user_id, $items_per_page, $offset_payments]);
    $recent_payments = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Transactions pagination
    $current_page_transactions = isset($_GET['trans_page']) ? (int)$_GET['trans_page'] : 1;
    $offset_transactions = ($current_page_transactions - 1) * $items_per_page;

    // Get total transactions count
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM transactions WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $total_transactions = $stmt->fetchColumn();
    $total_pages_transactions = ceil($total_transactions / $items_per_page);

    // Fetch recent transactions
    $stmt = $pdo->prepare("
        SELECT t.*, DATE_FORMAT(t.date_transaction, '%Y-%m-%d %H:%i') as formatted_date
        FROM transactions t
        WHERE t.user_id = ?
        ORDER BY t.date_transaction DESC
        LIMIT ? OFFSET ?
    ");
    $stmt->execute([$user_id, $items_per_page, $offset_transactions]);
    $recent_transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    error_log("Error in user.php: " . $e->getMessage());
    $_SESSION['error'] = "An error occurred while loading user data.";
    header('Location: users.php');
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Profile - <?php echo htmlspecialchars($user['full_name']); ?> - <?php echo APP_NAME; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2.0.0"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body class="bg-gray-100">
    <div class="min-h-screen">
        <!-- Navigation -->
        <nav class="bg-white shadow-lg">
            <div class="max-w-7xl mx-auto px-4">
                <div class="flex justify-between h-16">
                    <div class="flex">
                        <div class="flex-shrink-0 flex items-center">
                            <a href="dashboard.php" class="text-2xl font-bold text-gray-800"><?php echo APP_NAME; ?></a>
                        </div>
                    </div>
                    <div class="flex items-center">
                        <a href="dashboard.php" class="text-gray-600 hover:text-gray-900 px-3 py-2 rounded-md text-sm font-medium">Dashboard</a>
                        <a href="auth/logout.php" class="text-gray-600 hover:text-gray-900 px-3 py-2 rounded-md text-sm font-medium">Logout</a>
                    </div>
                </div>
            </div>
        </nav>

        <main class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
            <div class="container mx-auto px-4 sm:px-6 lg:px-8 py-8">
                <!-- Profile Section -->
                <div class="bg-white overflow-hidden shadow rounded-lg mb-8">
                    <div class="grid md:grid-cols-2 gap-8 p-6">
                        <!-- Left Column - Basic Info -->
                        <div>
                            <h2 class="text-xl font-semibold text-gray-900 mb-4">Basic Information</h2>
                            <div class="space-y-4">
                                <div>
                                    <label class="text-sm font-medium text-gray-500">Full Name</label>
                                    <p class="mt-1 text-sm text-gray-900"><?php echo htmlspecialchars($user['full_name']); ?></p>
                                </div>
                                <div>
                                    <label class="text-sm font-medium text-gray-500">Email</label>
                                    <p class="mt-1 text-sm text-gray-900"><?php echo htmlspecialchars($user['email']); ?></p>
                                </div>
                            </div>
                        </div>
                        <!-- Right Column - Contact Info -->
                        <div>
                            <h2 class="text-xl font-semibold text-gray-900 mb-4">Contact Details</h2>
                            <div class="space-y-4">
                                <div>
                                    <label class="text-sm font-medium text-gray-500">Phone</label>
                                    <p class="mt-1 text-sm text-gray-900"><?php echo htmlspecialchars($user['phone']); ?></p>
                                </div>
                                <div>
                                    <label class="text-sm font-medium text-gray-500">Member Since</label>
                                    <p class="mt-1 text-sm text-gray-900"><?php echo date('d M Y', strtotime($user['created_at'])); ?></p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Financial Summary Cards -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-8">
                    <!-- Total Outstanding -->
                    <div class="bg-white overflow-hidden shadow rounded-lg">
                        <div class="p-5">
                            <div class="flex items-center">
                                <div class="flex-shrink-0 bg-red-100 rounded-md p-3">
                                    <svg class="h-6 w-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                </div>
                                <div class="ml-5 w-0 flex-1">
                                    <dl>
                                        <dt class="text-sm font-medium text-gray-500 truncate">Total Outstanding</dt>
                                        <dd class="text-lg font-medium text-red-600">RM<?php echo number_format($debt_info['total_debt'], 2); ?></dd>
                                    </dl>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- Total Paid -->
                    <div class="bg-white overflow-hidden shadow rounded-lg">
                        <div class="p-5">
                            <div class="flex items-center">
                                <div class="flex-shrink-0 bg-green-100 rounded-md p-3">
                                    <svg class="h-6 w-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                </div>
                                <div class="ml-5 w-0 flex-1">
                                    <dl>
                                        <dt class="text-sm font-medium text-gray-500 truncate">Total Payment</dt>
                                        <dd class="text-lg font-medium text-green-600">RM<?php echo number_format($payment_info['total_paid'], 2); ?></dd>
                                    </dl>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- Balance -->
                    <div class="bg-green-50 overflow-hidden shadow rounded-lg">
                        <div class="p-5">
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    <svg class="h-6 w-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 6l3 1m0 0l-3 9a5.002 5.002 0 006.001 0M6 7l3 9M6 7l6-2m6 2l3-1m-3 1l-3 9a5.002 5.002 0 006.001 0M18 7l3 9m-3-9l-6-2m0-2v2m0 16V5m0 16H9m3 0h3"/>
                                    </svg>
                                </div>
                                <div class="ml-5 w-0 flex-1">
                                    <dl>
                                        <dt class="text-sm font-medium text-gray-500 truncate">Balance</dt>
                                        <dd class="text-lg font-medium text-<?php echo $balance > 0 ? 'red' : 'green'; ?>-600">
                                            RM<?php echo number_format(abs($balance), 2); ?>
                                        </dd>
                                    </dl>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Chart Section -->
                <div class="bg-white overflow-hidden shadow rounded-lg mb-8">
                    <div class="p-6">
                        <div style="height: 300px;">
                            <canvas id="monthlyChart"></canvas>
                        </div>
                    </div>
                </div>

                <!-- Recent Payments Section -->
                <div class="bg-white shadow rounded-lg mb-8">
                    <div class="px-4 py-5 sm:px-6 cursor-pointer">
                        <div class="flex justify-between items-center">
                            <div class="flex items-center" onclick="toggleSection('payments')">
                                <h3 class="text-lg leading-6 font-medium text-gray-900">Recent Payments</h3>
                                <svg id="payments-icon" class="h-5 w-5 transform transition-transform duration-200 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                </svg>
                            </div>
                            <button onclick="showPaymentModal(<?php echo $user_id; ?>, '<?php echo htmlspecialchars($user['full_name']); ?>')" class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                <svg class="h-4 w-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                                </svg>
                                Make Payment
                            </button>
                        </div>
                    </div>
                    <div id="payments-content" class="flex flex-col">
                        <div class="-my-2 overflow-x-auto sm:-mx-6 lg:-mx-8">
                            <div class="py-2 align-middle inline-block min-w-full sm:px-6 lg:px-8">
                                <div class="shadow overflow-hidden border-b border-gray-200 sm:rounded-lg">
                                    <div id="paymentsTableContainer">
                                        <table class="min-w-full divide-y divide-gray-200">
                                            <thead class="bg-gray-50">
                                                <tr>
                                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                        Reference
                                                    </th>
                                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                        Amount
                                                    </th>
                                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                        Method
                                                    </th>
                                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                        Date
                                                    </th>
                                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                        Notes
                                                    </th>
                                                </tr>
                                            </thead>
                                            <tbody class="bg-white divide-y divide-gray-200">
                                                <?php foreach ($recent_payments as $payment): ?>
                                                    <tr>
                                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                            <?php echo htmlspecialchars($payment['reference_number']); ?>
                                                        </td>
                                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                            RM<?php echo number_format($payment['amount'], 2); ?>
                                                        </td>
                                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                            <?php echo htmlspecialchars($payment['payment_method']); ?>
                                                        </td>
                                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                            <?php echo date('d/m/Y', strtotime($payment['payment_date'])); ?>
                                                        </td>
                                                        <td class="px-6 py-4 text-sm text-gray-500">
                                                            <?php echo htmlspecialchars($payment['notes']); ?>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                    <!-- Payments Pagination -->
                                    <div class="px-4 py-3 flex items-center justify-between border-t border-gray-200 sm:px-6">
                                        <div class="flex-1 flex justify-between items-center">
                                            <?php if ($current_page_payments > 1): ?>
                                                <a href="?id=<?php echo $user_id; ?>&pay_page=<?php echo ($current_page_payments - 1); ?>&trans_page=<?php echo $current_page_transactions; ?>" 
                                                   class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">Previous</a>
                                            <?php endif; ?>
                                            <?php if ($current_page_payments < $total_pages_payments): ?>
                                                <a href="?id=<?php echo $user_id; ?>&pay_page=<?php echo ($current_page_payments + 1); ?>&trans_page=<?php echo $current_page_transactions; ?>" 
                                                   class="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">Next</a>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Recent Transactions Section -->
                <div class="bg-white shadow rounded-lg">
                    <div class="px-4 py-5 sm:px-6 cursor-pointer">
                        <div class="flex items-center" onclick="toggleSection('transactions')">
                            <h3 class="text-lg leading-6 font-medium text-gray-900">Recent Transactions</h3>
                            <svg id="transactions-icon" class="h-5 w-5 transform transition-transform duration-200 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                            </svg>
                        </div>
                    </div>
                    <div id="transactions-content" class="flex flex-col">
                        <div class="-my-2 overflow-x-auto sm:-mx-6 lg:-mx-8">
                            <div class="py-2 align-middle inline-block min-w-full sm:px-6 lg:px-8">
                                <div class="shadow overflow-hidden border-b border-gray-200 sm:rounded-lg">
                                    <div id="transactionsTableContainer">
                                        <table class="min-w-full divide-y divide-gray-200" id="userTransactionTable">
                                            <thead class="bg-gray-50">
                                                <tr>
                                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
                                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Description</th>
                                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Details</th>
                                                </tr>
                                            </thead>
                                            <tbody class="bg-white divide-y divide-gray-200">
                                                <?php foreach ($recent_transactions as $transaction): ?>
                                                    <tr class="transaction-row-<?php echo $transaction['id']; ?>">
                                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                            <?php echo $transaction['formatted_date']; ?>
                                                        </td>
                                                        <td class="px-6 py-4 whitespace-nowrap">
                                                            <?php if ($transaction['type'] == 'transaction'): ?>
                                                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">Transaction</span>
                                                            <?php else: ?>
                                                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">Payment</span>
                                                            <?php endif; ?>
                                                        </td>
                                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                            RM<?php echo number_format($transaction['amount'], 2); ?>
                                                        </td>
                                                        <td class="px-6 py-4 text-sm text-gray-900">
                                                            <?php echo htmlspecialchars($transaction['description'] ?? 'No description'); ?>
                                                        </td>
                                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                            <button 
                                                                type="button"
                                                                class="transaction-details-btn text-indigo-600 hover:text-indigo-900"
                                                                data-transaction='<?php 
                                                                    echo htmlspecialchars(json_encode([
                                                                        'amount' => number_format($transaction['amount'], 2),
                                                                        'date' => $transaction['formatted_date'],
                                                                        'transaction_id' => $transaction['transaction_id'] ?? 'N/A',
                                                                        'type' => $transaction['type'] ?? 'N/A',
                                                                        'description' => $transaction['description'] ?? 'No description',
                                                                        'image_path' => $transaction['image_path'] ?? '',
                                                                        'notes' => $transaction['notes'] ?? 'No notes'
                                                                    ]));
                                                                ?>'
                                                            >
                                                                View Details
                                                            </button>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>

                                        <script>
                                            document.addEventListener('DOMContentLoaded', function() {
                                                document.querySelectorAll('.transaction-details-btn').forEach(button => {
                                                    button.addEventListener('click', function() {
                                                        const transactionData = JSON.parse(this.dataset.transaction);
                                                        showTransactionDetailsModal(transactionData);
                                                    });
                                                });
                                            });

                                            function showTransactionDetailsModal(details) {
                                                const modalContent = `
                                                    <div class="transaction-details-content text-left bg-gray-50 rounded-lg">
                                                        <div class="flex flex-col">
                                                            <!-- Header with Amount -->
                                                            <div class="bg-white p-6 rounded-t-lg shadow-sm border-b border-gray-200">
                                                                <div class="text-center">
                                                                    <p class="text-3xl font-semibold text-gray-900 mb-1">
                                                                        RM${details.amount}
                                                                    </p>
                                                                    <p class="text-sm text-gray-600">
                                                                        ${details.date}
                                                                    </p>
                                                                </div>
                                                            </div>

                                                            <!-- Main Content -->
                                                            <div class="p-6 space-y-6">
                                                                <!-- Transaction Details -->
                                                                <div class="grid grid-cols-2 gap-4">
                                                                    <div class="col-span-2 sm:col-span-1">
                                                                        <div class="bg-white p-4 rounded-lg shadow-sm">
                                                                            <p class="text-sm text-gray-500 mb-1">Transaction ID</p>
                                                                            <p class="text-base font-medium text-gray-900">${details.transaction_id}</p>
                                                                        </div>
                                                                    </div>
                                                                    <div class="col-span-2 sm:col-span-1">
                                                                        <div class="bg-white p-4 rounded-lg shadow-sm">
                                                                            <p class="text-sm text-gray-500 mb-1">Payment Method</p>
                                                                            <p class="text-base font-medium text-gray-900">${details.type}</p>
                                                                        </div>
                                                                    </div>
                                                                </div>

                                                                <!-- Description -->
                                                                <div class="bg-white p-4 rounded-lg shadow-sm">
                                                                    <p class="text-sm text-gray-500 mb-2">Description</p>
                                                                    <p class="text-base text-gray-900">${details.description}</p>
                                                                </div>

                                                                <!-- Image if available -->
                                                                ${details.image_path ? `
                                                                    <div class="bg-white p-4 rounded-lg shadow-sm">
                                                                        <p class="text-sm text-gray-500 mb-3">Receipt Image</p>
                                                                        <div class="relative rounded-lg overflow-hidden bg-gray-100">
                                                                            <img src="${details.image_path}" 
                                                                                 alt="Transaction Receipt" 
                                                                                 class="w-full h-auto object-cover rounded-lg hover:opacity-95 transition-opacity"
                                                                                 style="max-height: 300px;">
                                                                        </div>
                                                                    </div>
                                                                ` : ''}

                                                                <!-- Notes -->
                                                                <div class="bg-white p-4 rounded-lg shadow-sm">
                                                                    <p class="text-sm text-gray-500 mb-2">Notes</p>
                                                                    <p class="text-base text-gray-900">${details.notes}</p>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                `;

                                                Swal.fire({
                                                    html: modalContent,
                                                    showConfirmButton: true,
                                                    confirmButtonColor: '#4f46e5',
                                                    customClass: {
                                                        container: 'transaction-details-popup',
                                                        popup: 'transaction-details-popup !max-w-2xl',
                                                        content: 'transaction-details-content',
                                                        confirmButton: 'px-6 py-2.5 bg-indigo-600 text-white font-bold text-base'
                                                    },
                                                    width: '800px',
                                                    padding: 0,
                                                    background: '#f9fafb'
                                                });
                                            }

                                            // Add some custom styles
                                            const style = document.createElement('style');
                                            style.textContent = `
                                                .transaction-details-popup {
                                                    font-family: 'Inter', system-ui, -apple-system, sans-serif;
                                                }
                                                .swal2-modal {
                                                    border-radius: 1rem !important;
                                                }
                                                .swal2-popup.transaction-details-popup {
                                                    padding: 0;
                                                }
                                                .transaction-details-content {
                                                    margin: 0;
                                                }
                                                .transaction-details-modal .swal2-actions {
                                                    margin-top: 1.5rem;
                                                    margin-bottom: 1.5rem;
                                                }
                                            `;
                                            document.head.appendChild(style);
                                        </script>
                                    </div>
                                    <!-- Transactions Pagination -->
                                    <div class="px-4 py-3 flex items-center justify-between border-t border-gray-200 sm:px-6">
                                        <div class="flex-1 flex justify-between items-center">
                                            <?php if ($current_page_transactions > 1): ?>
                                                <a href="?id=<?php echo $user_id; ?>&trans_page=<?php echo ($current_page_transactions - 1); ?>&pay_page=<?php echo $current_page_payments; ?>" 
                                                   class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">Previous</a>
                                            <?php endif; ?>
                                            <?php if ($current_page_transactions < $total_pages_transactions): ?>
                                                <a href="?id=<?php echo $user_id; ?>&trans_page=<?php echo ($current_page_transactions + 1); ?>&pay_page=<?php echo $current_page_payments; ?>" 
                                                   class="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">Next</a>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- Note Modal -->
    <div id="noteModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden">
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
            <div class="flex flex-col items-center">
                <h3 class="text-lg font-medium leading-6 text-gray-900 mb-4">Transaction Details</h3>
                <!-- Note Content -->
                <div class="mb-4 w-full">
                    <h4 class="text-md font-medium text-gray-700 mb-2">Note:</h4>
                    <p id="modalNoteContent" class="text-gray-600 whitespace-pre-wrap"></p>
                </div>
                <!-- Close Button -->
                <button onclick="closeNoteModal()" class="mt-4 bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                    Close
                </button>
            </div>
        </div>
    </div>

    <!-- Payment Details Modal -->
    <div id="paymentDetailsModal" class="fixed z-10 inset-0 overflow-y-auto hidden" onclick="closePaymentDetailsModal()">
        <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity"></div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
            <div class="inline-block align-bottom bg-white rounded-lg px-4 pt-5 pb-4 text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full" onclick="event.stopPropagation()">
                <div>
                    <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">Payment Details</h3>
                    <div id="paymentDetails" class="text-sm text-gray-500"></div>
                </div>
                <div class="mt-5 sm:mt-6">
                    <button type="button" onclick="closePaymentDetailsModal()" class="inline-flex justify-center w-full rounded-md border border-transparent shadow-sm px-4 py-2 bg-indigo-600 text-base font-medium text-white hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:text-sm">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Make Payment Modal -->
    <div id="makePaymentModal" class="hidden">
        <div class="bg-gray-50 rounded-lg overflow-hidden">
            <!-- Header -->
            <div class="bg-white px-6 py-4 border-b border-gray-200 relative">
                <button type="button" 
                        onclick="Swal.close()" 
                        class="absolute top-4 right-4 text-gray-400 hover:text-gray-500">
                    <i class="fas fa-times text-xl"></i>
                </button>
                <h3 class="text-2xl font-semibold text-gray-900 text-center">Make Payment</h3>
            </div>

            <!-- Form Content -->
            <div class="p-6 space-y-6">
                <form id="paymentForm" class="space-y-6" enctype="multipart/form-data">
                    <input type="hidden" name="user_id" id="payment_user_id">
                    
                    <!-- User Info -->
                    <div class="text-center mb-6">
                        <p class="text-2xl font-bold text-blue-600">
                            <span id="payment_user_name"></span>
                            <span class="text-sm font-normal text-gray-500" id="payment_user_id_display"></span>
                        </p>
                    </div>

                    <!-- Payment Date -->
                    <div class="space-y-2">
                        <label for="payment_date" class="block text-sm font-medium text-gray-700 text-center">
                            Payment Date
                        </label>
                        <input type="date" 
                               name="payment_date" 
                               id="payment_date" 
                               required
                               class="block w-full rounded-md border-gray-300 text-center py-3 focus:border-green-500 focus:ring-green-500 text-base">
                        <p id="formatted_date" class="text-center text-sm text-gray-500 mt-1"></p>
                    </div>
                    
                    <!-- Amount Input -->
                    <div class="space-y-2">
                        <label for="payment_amount" class="block text-sm font-medium text-gray-700 text-center">
                            Payment Amount
                        </label>
                        <input type="number" 
                               name="amount" 
                               id="payment_amount" 
                               step="0.01" 
                               required
                               class="block w-full rounded-md border-gray-300 py-3 text-center text-xl font-semibold focus:border-green-500 focus:ring-green-500"
                               placeholder="0.00">
                    </div>

                    <!-- Payment Method -->
                    <div class="space-y-2">
                        <label class="block text-sm font-medium text-gray-700 text-center">Payment Method</label>
                        <select name="payment_method" 
                                id="payment_method" 
                                class="block w-full rounded-md border-gray-300 text-center py-3 focus:border-green-500 focus:ring-green-500 text-base"
                                required>
                            <option value="Cash" selected>Cash</option>
                            <option value="Transfer">Transfer</option>
                            <option value="QR">QR</option>
                            <option value="Cards">Cards</option>
                        </select>
                    </div>

                    <!-- Notes -->
                    <div class="space-y-2">
                        <label for="payment_notes" class="block text-sm font-medium text-gray-700 text-center">Notes</label>
                        <textarea id="payment_notes" 
                                  name="notes" 
                                  rows="3" 
                                  class="block w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500 text-base p-4"
                                  placeholder="Add any additional notes here..."></textarea>
                    </div>

                    <!-- Reference Number -->
                    <div class="space-y-2">
                        <label for="reference_number" class="block text-sm font-medium text-gray-700 text-center">Reference Number</label>
                        <input type="text" 
                               id="reference_number" 
                               name="reference_number" 
                               readonly
                               required
                               class="block w-full rounded-md border-gray-300 bg-gray-100 text-center py-3 text-base font-medium select-none pointer-events-none">
                    </div>

                    <!-- Submit Button -->
                    <div class="text-center pt-4">
                        <button type="submit" 
                                id="submitPaymentBtn"
                                class="w-full px-6 py-3 text-base font-medium text-white bg-green-600 border border-transparent rounded-md shadow-sm hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                            <span id="submitBtnText">Submit Payment</span>
                            <span id="submitBtnSpinner" class="hidden">
                                <i class="fas fa-spinner fa-spin mr-2"></i>
                                Processing...
                            </span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        // Generate reference number
        function generateReferenceNumber() {
            const date = new Date();
            const year = date.getFullYear();
            const month = String(date.getMonth() + 1).padStart(2, '0');
            const day = String(date.getDate()).padStart(2, '0');
            const hours = String(date.getHours()).padStart(2, '0');
            const minutes = String(date.getMinutes()).padStart(2, '0');
            const seconds = String(date.getSeconds()).padStart(2, '0');
            const random = Math.floor(Math.random() * 1000).toString().padStart(3, '0');
            
            return `PAY-${year}${month}${day}-${hours}${minutes}${seconds}-${random}`;
        }

        // Show payment modal
        function showPaymentModal(userId, userName) {
            // Set default date to today
            const today = new Date().toISOString().split('T')[0];
            
            Swal.fire({
                html: document.getElementById('makePaymentModal').innerHTML,
                showConfirmButton: false,
                allowOutsideClick: true,
                customClass: {
                    container: 'payment-modal-container',
                    popup: 'payment-modal-popup !max-w-2xl',
                    content: 'payment-modal-content'
                },
                width: '800px',
                padding: 0,
                background: '#f9fafb',
                didOpen: () => {
                    const popup = Swal.getPopup();
                    const form = popup.querySelector('#paymentForm');
                    const dateInput = popup.querySelector('#payment_date');
                    const refInput = popup.querySelector('#reference_number');
                    
                    // Set user info
                    popup.querySelector('#payment_user_id').value = userId;
                    popup.querySelector('#payment_user_name').textContent = userName;
                    popup.querySelector('#payment_user_id_display').textContent = ` (ID: ${userId})`;
                    
                    // Set default date and format display
                    dateInput.value = today;
                    updateFormattedDate(dateInput.value);

                    // Generate and set reference number
                    const refNumber = generateReferenceNumber();
                    refInput.value = refNumber;

                    // Update formatted date when date changes
                    dateInput.addEventListener('change', function(e) {
                        updateFormattedDate(e.target.value);
                    });

                    // Handle form submission
                    form.addEventListener('submit', async function(e) {
                        e.preventDefault();
                        
                        // Get submit button elements
                        const submitBtn = form.querySelector('#submitPaymentBtn');
                        const submitBtnText = form.querySelector('#submitBtnText');
                        const submitBtnSpinner = form.querySelector('#submitBtnSpinner');
                        
                        // Ensure reference number is included
                        if (!refInput.value) {
                            refInput.value = generateReferenceNumber();
                        }
                        
                        // Disable submit button and show spinner
                        submitBtn.disabled = true;
                        submitBtn.classList.add('opacity-75', 'cursor-not-allowed');
                        submitBtnText.classList.add('hidden');
                        submitBtnSpinner.classList.remove('hidden');
                        
                        const formData = new FormData(form);
                        
                        try {
                            const response = await fetch('process_payment.php', {
                                method: 'POST',
                                body: formData
                            });
                            
                            const result = await response.json();
                            
                            if (result.success) {
                                Swal.fire({
                                    icon: 'success',
                                    title: 'Payment Successful',
                                    text: 'The payment has been recorded successfully.',
                                    showConfirmButton: false,
                                    timer: 1500
                                }).then(() => {
                                    // Refresh the page
                                    window.location.reload();
                                });
                            } else {
                                // Re-enable submit button on error
                                submitBtn.disabled = false;
                                submitBtn.classList.remove('opacity-75', 'cursor-not-allowed');
                                submitBtnText.classList.remove('hidden');
                                submitBtnSpinner.classList.add('hidden');
                                
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Error',
                                    text: result.message || 'An error occurred while processing the payment.'
                                });
                            }
                        } catch (error) {
                            // Re-enable submit button on error
                            submitBtn.disabled = false;
                            submitBtn.classList.remove('opacity-75', 'cursor-not-allowed');
                            submitBtnText.classList.remove('hidden');
                            submitBtnSpinner.classList.add('hidden');
                            
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: 'An error occurred while processing the payment.'
                            });
                        }
                    });
                }
            });
        }

        // Format date to DD MMM YYYY
        function updateFormattedDate(dateString) {
            const date = new Date(dateString);
            const options = { day: '2-digit', month: 'short', year: 'numeric' };
            const formattedDate = date.toLocaleDateString('en-GB', options);
            document.querySelector('#formatted_date').textContent = formattedDate;
        }

        // Add custom styles
        const paymentStyle = document.createElement('style');
        paymentStyle.textContent = `
            .payment-modal-popup {
                font-family: 'Inter', system-ui, -apple-system, sans-serif;
            }
            .payment-modal-popup .swal2-html-container {
                margin: 0;
                padding: 0;
            }
            .payment-modal-container .swal2-popup {
                padding: 0;
                border-radius: 1rem;
            }
            .payment-modal-content {
                margin: 0;
            }
            .payment-modal-container select option:checked {
                background-color: #10B981;
                color: white;
            }
        `;
        document.head.appendChild(paymentStyle);
    </script>

    <script>
        // Toggle section
        function toggleSection(section) {
            const content = document.getElementById(`${section}-content`);
            const icon = document.getElementById(`${section}-icon`);
            const isVisible = content.style.display !== 'none';
            
            // Save state to localStorage
            localStorage.setItem(`${section}_state`, isVisible ? 'closed' : 'open');
            
            // Toggle display
            content.style.display = isVisible ? 'none' : 'flex';
            icon.style.transform = isVisible ? 'rotate(0deg)' : 'rotate(180deg)';
        }

        // Initialize sections based on saved state
        function initializeSections() {
            ['payments', 'transactions'].forEach(section => {
                const savedState = localStorage.getItem(`${section}_state`);
                const content = document.getElementById(`${section}-content`);
                const icon = document.getElementById(`${section}-icon`);
                
                if (savedState === 'open') {
                    content.style.display = 'flex';
                    icon.style.transform = 'rotate(180deg)';
                } else {
                    content.style.display = 'none';
                    icon.style.transform = 'rotate(0deg)';
                }
            });
        }

        // Function to format number to compact format with RM
        function formatCompactRM(number) {
            if (number >= 1000000) {
                return 'RM' + (number / 1000000).toFixed(1) + 'M';
            } else if (number >= 1000) {
                return 'RM' + (number / 1000).toFixed(1) + 'k';
            } else {
                return 'RM' + number.toFixed(2);
            }
        }

        // Function to format full amount with RM
        function formatFullRM(number) {
            return 'RM' + number.toFixed(2);
        }

        // Function to format number with RM and commas
        function formatRM(number) {
            return 'RM' + number.toLocaleString('en-US', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            });
        }

        // Create the transaction chart
        function createTransactionChart(data) {
            const ctx = document.getElementById('monthlyChart').getContext('2d');
            
            new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: data.labels,
                    datasets: [
                        {
                            label: 'Transactions',
                            data: data.transactions,
                            backgroundColor: 'rgba(239, 68, 68, 0.8)',
                            borderColor: 'rgb(239, 68, 68)',
                            borderWidth: 1,
                            borderRadius: 4,
                            barPercentage: 0.7
                        },
                        {
                            label: 'Payments',
                            data: data.payments,
                            backgroundColor: 'rgba(34, 197, 94, 0.8)',
                            borderColor: 'rgb(34, 197, 94)',
                            borderWidth: 1,
                            borderRadius: 4,
                            barPercentage: 0.7
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    layout: {
                        padding: {
                            top: 50  // Add more padding at the top for rotated labels
                        }
                    },
                    plugins: {
                        title: {
                            display: true,
                            text: 'Monthly Transaction vs Payment',
                            font: {
                                size: 16,
                                weight: 'bold'
                            }
                        },
                        legend: {
                            position: 'bottom'
                        },
                        tooltip: {
                            mode: 'index',
                            intersect: false,
                            callbacks: {
                                label: function(context) {
                                    let label = context.dataset.label || '';
                                    if (label) {
                                        label += ': ';
                                    }
                                    if (context.parsed.y !== null) {
                                        label += formatRM(context.parsed.y);
                                    }
                                    return label;
                                }
                            }
                        },
                        datalabels: {
                            anchor: 'end',
                            align: 'end',
                            rotation: 90,
                            formatter: function(value) {
                                return formatRM(value);
                            },
                            color: function(context) {
                                return context.dataset.borderColor;
                            },
                            font: {
                                weight: 'normal',
                                size: 11
                            },
                            offset: 0,
                            padding: {
                                top: 5
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: function(value) {
                                    return formatRM(value);
                                },
                                color: '#000000',
                                font: {
                                    family: 'Arial',
                                    weight: 'normal'
                                }
                            },
                            grid: {
                                color: 'rgba(0, 0, 0, 0.1)'
                            }
                        },
                        x: {
                            ticks: {
                                color: '#000000',
                                font: {
                                    family: 'Arial',
                                    weight: 'normal'
                                }
                            },
                            grid: {
                                display: false
                            }
                        }
                    },
                    interaction: {
                        intersect: false,
                        mode: 'index'
                    }
                },
                plugins: [ChartDataLabels]
            });
        }

        // Initialize chart with data
        document.addEventListener('DOMContentLoaded', function() {
            const chartData = {
                labels: <?php echo json_encode($chart_labels); ?>,
                transactions: <?php echo json_encode($transaction_data); ?>,
                payments: <?php echo json_encode($payment_data); ?>
            };
            createTransactionChart(chartData);
        });

        // Initialize sections on page load
        document.addEventListener('DOMContentLoaded', initializeSections);

        // Transaction Details Modal Functions
        function showTransactionDetails(details) {
            let content = `
                <div class="text-left">
                    <p class="mb-2"><strong>Type:</strong> ${details.type.charAt(0).toUpperCase() + details.type.slice(1)}</p>
                    <p class="mb-2"><strong>Amount:</strong> RM${details.amount}</p>
                    <p class="mb-2"><strong>Date:</strong> ${details.date}</p>
                    <p class="mb-2"><strong>Reference:</strong> ${details.reference}</p>
                    ${details.type === 'payment' ? `<p class="mb-2"><strong>Payment Method:</strong> ${details.payment_method}</p>` : ''}
                    <p class="mb-2"><strong>Description:</strong> ${details.description}</p>
                </div>
            `;

            Swal.fire({
                title: 'Transaction Details',
                html: content,
                confirmButtonColor: '#4f46e5',
                customClass: {
                    container: 'transaction-details-popup'
                }
            });
        }
    </script>
</body>
</html>
