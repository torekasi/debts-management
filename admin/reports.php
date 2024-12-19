<?php
require_once '../config/config.php';
require_once '../config/database.php';
require_once '../auth/auth_check.php';
require_once '../includes/reports.php';

checkAdminAuth();

$reports = new ReportingSystem($conn);

$report_type = $_GET['type'] ?? 'outstanding';
$year = $_GET['year'] ?? date('Y');
$month = $_GET['month'] ?? date('m');
$export = isset($_GET['export']);

// Get report data based on type
switch ($report_type) {
    case 'monthly':
        $data = $reports->generateMonthlyReport($year, $month);
        $title = "Monthly Report - " . date('F Y', strtotime("$year-$month-01"));
        break;
    
    case 'outstanding':
    default:
        $data = $reports->generateOutstandingReport();
        $title = "Outstanding Balances Report";
        break;
}

// Export to CSV if requested
if ($export && !empty($data)) {
    $filename = strtolower(str_replace(' ', '_', $title)) . '_' . date('Y-m-d') . '.csv';
    $reports->exportToCSV($data, $filename);
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports - <?php echo APP_NAME; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body class="bg-gray-100">
    <div class="min-h-screen">
        <nav class="bg-indigo-600 p-4">
            <div class="max-w-7xl mx-auto flex justify-between items-center">
                <h1 class="text-white text-xl font-bold">Reports</h1>
                <div class="flex items-center space-x-4">
                    <a href="dashboard.php" class="text-white hover:text-indigo-100">Dashboard</a>
                    <a href="../auth/logout.php" class="text-white hover:text-indigo-100">Logout</a>
                </div>
            </div>
        </nav>

        <main class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
            <!-- Report Type Selection -->
            <div class="mb-8 bg-white shadow rounded-lg p-4">
                <div class="flex justify-between items-center">
                    <div class="space-x-4">
                        <a href="?type=outstanding" 
                           class="<?php echo $report_type === 'outstanding' ? 'bg-indigo-600 text-white' : 'bg-gray-100 text-gray-700'; ?> px-4 py-2 rounded-md">
                            Outstanding Balances
                        </a>
                        <a href="?type=monthly" 
                           class="<?php echo $report_type === 'monthly' ? 'bg-indigo-600 text-white' : 'bg-gray-100 text-gray-700'; ?> px-4 py-2 rounded-md">
                            Monthly Report
                        </a>
                    </div>
                    
                    <?php if (!empty($data)): ?>
                        <a href="?type=<?php echo $report_type; ?>&export=1<?php echo $report_type === 'monthly' ? "&year=$year&month=$month" : ''; ?>" 
                           class="bg-green-600 text-white px-4 py-2 rounded-md hover:bg-green-700">
                            Export to CSV
                        </a>
                    <?php endif; ?>
                </div>

                <?php if ($report_type === 'monthly'): ?>
                    <div class="mt-4">
                        <form method="GET" class="flex space-x-4">
                            <input type="hidden" name="type" value="monthly">
                            <div>
                                <label for="year" class="block text-sm font-medium text-gray-700">Year</label>
                                <select name="year" id="year" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                                    <?php for ($y = date('Y'); $y >= date('Y') - 5; $y--): ?>
                                        <option value="<?php echo $y; ?>" <?php echo $y == $year ? 'selected' : ''; ?>>
                                            <?php echo $y; ?>
                                        </option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                            <div>
                                <label for="month" class="block text-sm font-medium text-gray-700">Month</label>
                                <select name="month" id="month" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                                    <?php for ($m = 1; $m <= 12; $m++): ?>
                                        <option value="<?php echo str_pad($m, 2, '0', STR_PAD_LEFT); ?>" 
                                                <?php echo $m == $month ? 'selected' : ''; ?>>
                                            <?php echo date('F', strtotime("2024-$m-01")); ?>
                                        </option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                            <div class="flex items-end">
                                <button type="submit" class="bg-indigo-600 text-white px-4 py-2 rounded-md hover:bg-indigo-700">
                                    Generate Report
                                </button>
                            </div>
                        </form>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Report Content -->
            <div class="bg-white shadow rounded-lg">
                <div class="px-4 py-5 sm:p-6">
                    <h2 class="text-lg font-medium text-gray-900 mb-4"><?php echo $title; ?></h2>
                    
                    <?php if ($report_type === 'outstanding'): ?>
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Member ID</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total Debt</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total Paid</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Outstanding</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Last Transaction</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <?php foreach ($data as $row): ?>
                                        <tr>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?php echo htmlspecialchars($row['member_id']); ?></td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?php echo htmlspecialchars($row['full_name']); ?></td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">$<?php echo number_format($row['total_debt'], 2); ?></td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">$<?php echo number_format($row['total_paid'], 2); ?></td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-red-600">$<?php echo number_format($row['outstanding_balance'], 2); ?></td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?php echo date('Y-m-d', strtotime($row['last_transaction'])); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <!-- Monthly Report Chart -->
                        <div class="h-96">
                            <canvas id="monthlyChart"></canvas>
                        </div>
                        <script>
                            const ctx = document.getElementById('monthlyChart').getContext('2d');
                            new Chart(ctx, {
                                type: 'line',
                                data: {
                                    labels: <?php echo json_encode(array_map(function($row) { 
                                        return date('d M', strtotime($row['date'])); 
                                    }, $data)); ?>,
                                    datasets: [{
                                        label: 'Daily Debt Amount',
                                        data: <?php echo json_encode(array_map(function($row) { 
                                            return $row['total_debt']; 
                                        }, $data)); ?>,
                                        borderColor: 'rgb(99, 102, 241)',
                                        tension: 0.1
                                    }, {
                                        label: 'Daily Payment Amount',
                                        data: <?php echo json_encode(array_map(function($row) { 
                                            return $row['total_payments']; 
                                        }, $data)); ?>,
                                        borderColor: 'rgb(34, 197, 94)',
                                        tension: 0.1
                                    }]
                                },
                                options: {
                                    responsive: true,
                                    maintainAspectRatio: false,
                                    scales: {
                                        y: {
                                            beginAtZero: true
                                        }
                                    }
                                }
                            });
                        </script>

                        <!-- Monthly Report Summary -->
                        <div class="mt-8 grid grid-cols-1 gap-4 sm:grid-cols-4">
                            <?php
                            $total_transactions = array_sum(array_column($data, 'transactions_count'));
                            $total_debt = array_sum(array_column($data, 'total_debt'));
                            $total_payments = array_sum(array_column($data, 'total_payments'));
                            $payment_rate = $total_debt > 0 ? ($total_payments / $total_debt) * 100 : 0;
                            ?>
                            <div class="bg-gray-50 p-4 rounded-lg">
                                <dt class="text-sm font-medium text-gray-500">Total Transactions</dt>
                                <dd class="mt-1 text-2xl font-semibold text-gray-900"><?php echo $total_transactions; ?></dd>
                            </div>
                            <div class="bg-gray-50 p-4 rounded-lg">
                                <dt class="text-sm font-medium text-gray-500">Total Debt</dt>
                                <dd class="mt-1 text-2xl font-semibold text-gray-900">$<?php echo number_format($total_debt, 2); ?></dd>
                            </div>
                            <div class="bg-gray-50 p-4 rounded-lg">
                                <dt class="text-sm font-medium text-gray-500">Total Payments</dt>
                                <dd class="mt-1 text-2xl font-semibold text-gray-900">$<?php echo number_format($total_payments, 2); ?></dd>
                            </div>
                            <div class="bg-gray-50 p-4 rounded-lg">
                                <dt class="text-sm font-medium text-gray-500">Payment Rate</dt>
                                <dd class="mt-1 text-2xl font-semibold text-gray-900"><?php echo number_format($payment_rate, 1); ?>%</dd>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>
</body>
</html>
