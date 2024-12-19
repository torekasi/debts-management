<?php
require_once '../includes/config.php';
session_start();

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit();
}

// Pagination settings
$items_per_page = 10;
$current_page_transactions = isset($_GET['trans_page']) ? (int)$_GET['trans_page'] : 1;
$current_page_activities = isset($_GET['act_page']) ? (int)$_GET['act_page'] : 1;
$offset_transactions = ($current_page_transactions - 1) * $items_per_page;
$offset_activities = ($current_page_activities - 1) * $items_per_page;

// Database connection
try {
    // For Aiven MySQL, we need to use SSL connection
    $options = array(
        PDO::MYSQL_ATTR_SSL_CA => __DIR__ . '/../config/ca.pem',  // Path to CA certificate
        PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT => false
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
    'total_debts' => 0,
    'total_transactions' => 0,
    'total_payments' => 0
];

try {
    // Get total users
    $stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'user'");
    $stats['total_users'] = $stmt->fetchColumn();

    // Get total debts amount
    $stmt = $pdo->query("SELECT COALESCE(SUM(amount), 0) FROM debts WHERE status = 'unpaid'");
    $stats['total_debts'] = $stmt->fetchColumn();

    // Get total transactions
    $stmt = $pdo->query("SELECT COUNT(*) FROM transactions");
    $stats['total_transactions'] = $stmt->fetchColumn();

    // Get total payments
    $stmt = $pdo->query("SELECT COALESCE(SUM(amount), 0) FROM payments");
    $stats['total_payments'] = $stmt->fetchColumn();

    // Get total number of transactions for pagination
    $stmt = $pdo->query("SELECT COUNT(*) FROM transactions");
    $total_transactions = $stmt->fetchColumn();
    $total_pages_transactions = ceil($total_transactions / $items_per_page);

    // Get recent transactions with pagination
    $stmt = $pdo->prepare("
        SELECT t.id, t.amount, t.description, t.image_path, t.created_at, u.full_name
        FROM transactions t
        JOIN users u ON t.user_id = u.id
        ORDER BY t.created_at DESC
        LIMIT :offset, :limit
    ");
    $stmt->bindValue(':offset', $offset_transactions, PDO::PARAM_INT);
    $stmt->bindValue(':limit', $items_per_page, PDO::PARAM_INT);
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
    <style>
        .collapsed {
            display: none;
        }
    </style>
</head>
<body class="bg-gray-100">
    <div class="min-h-screen">
        <!-- Navigation -->
        <nav class="bg-white shadow-lg">
            <div class="max-w-7xl mx-auto px-4">
                <div class="flex justify-between h-16">
                    <div class="flex">
                        <div class="flex-shrink-0 flex items-center">
                            <h1 class="text-xl font-bold"><?php echo APP_NAME; ?></h1>
                        </div>
                    </div>
                    <div class="flex items-center">
                        <span class="text-gray-700 mr-4">Welcome, <?php echo htmlspecialchars($_SESSION['full_name']); ?></span>
                        <a href="logout.php" class="bg-red-500 text-white px-4 py-2 rounded-md text-sm font-medium">Logout</a>
                    </div>
                </div>
            </div>
        </nav>

        <!-- Main Content -->
        <main class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
            <!-- Statistics Cards -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-8">
                <div class="bg-white overflow-hidden shadow rounded-lg">
                    <div class="p-5">
                        <div class="flex items-center">
                            <div class="flex-shrink-0 bg-indigo-500 rounded-md p-3">
                                <svg class="h-6 w-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                </svg>
                            </div>
                            <div class="ml-5 w-0 flex-1">
                                <dl>
                                    <dt class="text-sm font-medium text-gray-500 truncate">Total Users</dt>
                                    <dd class="text-lg font-medium text-gray-900"><?php echo number_format($stats['total_users']); ?></dd>
                                </dl>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="bg-white overflow-hidden shadow rounded-lg">
                    <div class="p-5">
                        <div class="flex items-center">
                            <div class="flex-shrink-0 bg-red-500 rounded-md p-3">
                                <svg class="h-6 w-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                            <div class="ml-5 w-0 flex-1">
                                <dl>
                                    <dt class="text-sm font-medium text-gray-500 truncate">Total Outstanding Debts</dt>
                                    <dd class="text-lg font-medium text-gray-900">RM <?php echo number_format($stats['total_debts'], 2); ?></dd>
                                </dl>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="bg-white overflow-hidden shadow rounded-lg">
                    <div class="p-5">
                        <div class="flex items-center">
                            <div class="flex-shrink-0 bg-green-500 rounded-md p-3">
                                <svg class="h-6 w-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                                </svg>
                            </div>
                            <div class="ml-5 w-0 flex-1">
                                <dl>
                                    <dt class="text-sm font-medium text-gray-500 truncate">Total Transactions</dt>
                                    <dd class="text-lg font-medium text-gray-900"><?php echo number_format($stats['total_transactions']); ?></dd>
                                </dl>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="bg-white overflow-hidden shadow rounded-lg">
                    <div class="p-5">
                        <div class="flex items-center">
                            <div class="flex-shrink-0 bg-yellow-500 rounded-md p-3">
                                <svg class="h-6 w-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
                                </svg>
                            </div>
                            <div class="ml-5 w-0 flex-1">
                                <dl>
                                    <dt class="text-sm font-medium text-gray-500 truncate">Total Payments</dt>
                                    <dd class="text-lg font-medium text-gray-900">RM <?php echo number_format($stats['total_payments'], 2); ?></dd>
                                </dl>
                            </div>
                        </div>
                    </div>
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
                            <div class="shadow overflow-hidden border-b border-gray-200 sm:rounded-lg">
                                <table class="min-w-full divide-y divide-gray-200">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">User</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Description</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Image</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white divide-y divide-gray-200">
                                        <?php foreach ($recent_transactions as $transaction): ?>
                                        <tr>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                                <?php echo htmlspecialchars($transaction['full_name'] ?? ''); ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                RM <?php echo number_format($transaction['amount'] ?? 0, 2); ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                <?php echo htmlspecialchars($transaction['description'] ?? ''); ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                <?php echo $transaction['image_path'] ? '<a href="' . htmlspecialchars($transaction['image_path']) . '" target="_blank" class="text-blue-600 hover:text-blue-800">View Image</a>' : ''; ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                <?php echo date('M d, Y H:i', strtotime($transaction['created_at'] ?? '')); ?>
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
                                        <a href="?trans_page=<?php echo ($current_page_transactions - 1); ?>&act_page=<?php echo $current_page_activities; ?>" class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">Previous</a>
                                    <?php endif; ?>
                                    <span class="text-sm text-gray-700">
                                        Page <?php echo $current_page_transactions; ?> of <?php echo $total_pages_transactions; ?>
                                    </span>
                                    <?php if ($current_page_transactions < $total_pages_transactions): ?>
                                        <a href="?trans_page=<?php echo ($current_page_transactions + 1); ?>&act_page=<?php echo $current_page_activities; ?>" class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">Next</a>
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
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Description</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">IP Address</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white divide-y divide-gray-200">
                                        <?php foreach ($recent_activities as $activity): ?>
                                        <tr>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                                <?php echo htmlspecialchars($activity['full_name'] ?? 'System'); ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                <?php echo htmlspecialchars($activity['action'] ?? 'Unknown'); ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                <?php echo htmlspecialchars($activity['description'] ?? 'No description'); ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                <?php echo htmlspecialchars($activity['ip_address'] ?? ''); ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                <?php echo date('M d, Y H:i', strtotime($activity['created_at'] ?? '')); ?>
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
                                        <a href="?act_page=<?php echo ($current_page_activities - 1); ?>&trans_page=<?php echo $current_page_transactions; ?>" class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">Previous</a>
                                    <?php endif; ?>
                                    <span class="text-sm text-gray-700">
                                        Page <?php echo $current_page_activities; ?> of <?php echo $total_pages_activities; ?>
                                    </span>
                                    <?php if ($current_page_activities < $total_pages_activities): ?>
                                        <a href="?act_page=<?php echo ($current_page_activities + 1); ?>&trans_page=<?php echo $current_page_transactions; ?>" class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">Next</a>
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

    <script>
        // Function to set initial state based on localStorage
        function initializeSections() {
            const sections = ['transactions', 'activities'];
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

        // Initialize sections when page loads
        document.addEventListener('DOMContentLoaded', initializeSections);
    </script>
</body>
</html>