<?php
require_once '../includes/config.php';
require_once '../includes/session.php';

// Require admin authentication
requireAdmin();

try {
    $dsn = sprintf("mysql:host=%s;port=%s;dbname=%s", DB_HOST, DB_PORT, DB_NAME);
    $pdo = new PDO($dsn, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Get monthly transaction data for the graph (last 12 months)
    $monthly_query = "
        WITH RECURSIVE months AS (
            SELECT CAST(DATE_FORMAT(CURRENT_DATE - INTERVAL 11 MONTH, '%Y-%m-01') AS DATE) as month_date,
                   DATE_FORMAT(CURRENT_DATE - INTERVAL 11 MONTH, '%Y-%m') as month
            UNION ALL
            SELECT CAST(DATE_FORMAT(month_date + INTERVAL 1 MONTH, '%Y-%m-01') AS DATE),
                   DATE_FORMAT(month_date + INTERVAL 1 MONTH, '%Y-%m')
            FROM months
            WHERE month < DATE_FORMAT(CURRENT_DATE, '%Y-%m')
        )
        SELECT 
            m.month,
            COALESCE(SUM(t.amount), 0) as total_amount
        FROM months m
        LEFT JOIN transactions t ON DATE_FORMAT(t.date_transaction, '%Y-%m') = m.month
        GROUP BY m.month
        ORDER BY m.month ASC
        LIMIT 12
    ";
    $monthly_data = $pdo->query($monthly_query)->fetchAll();

    // Get summary statistics
    $summary_query = "
        SELECT 
            (SELECT COALESCE(SUM(amount), 0) FROM payments) as total_payments_made,
            (SELECT COALESCE(SUM(amount), 0) FROM transactions) as total_amount,
            (SELECT 
                COALESCE(
                    (SELECT SUM(amount) FROM transactions) - 
                    (SELECT COALESCE(SUM(amount), 0) FROM payments),
                    0
                )
            ) as outstanding_balance
    ";
    $summary = $pdo->query($summary_query)->fetch();

    // Get recent transactions grouped by month
    $transactions_query = "
        SELECT 
            t.*,
            u.full_name,
            u.member_id,
            COALESCE(t.description, '-') as description,
            DATE_FORMAT(t.date_transaction, '%Y-%m') as month_group,
            DATE_FORMAT(t.date_transaction, '%M %Y') as month_name,
            DATE_FORMAT(t.date_transaction, '%d %M %Y') as formatted_date
        FROM transactions t
        JOIN users u ON t.user_id = u.id
        ORDER BY t.date_transaction DESC
    ";
    $transactions = $pdo->query($transactions_query)->fetchAll();

    // Group transactions by month
    $grouped_transactions = [];
    foreach ($transactions as $transaction) {
        $month = $transaction['month_group'];
        if (!isset($grouped_transactions[$month])) {
            $grouped_transactions[$month] = [
                'month_name' => $transaction['month_name'],
                'transactions' => [],
                'total' => 0
            ];
        }
        $grouped_transactions[$month]['transactions'][] = $transaction;
        $grouped_transactions[$month]['total'] += $transaction['amount'];
    }

} catch (PDOException $e) {
    error_log("Transactions page error: " . $e->getMessage());
    $_SESSION['error_message'] = "An error occurred while loading the transactions.";
    header("Location: /admin/dashboard.php");
    exit();
}

require_once 'template/header.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Transactions - <?php echo APP_NAME; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2"></script>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        .fa-chevron-right {
            display: inline-block;
            transition: transform 0.2s ease-in-out;
        }
        
        .month-content {
            transition: max-height 0.3s ease-in-out;
            overflow: hidden;
        }
    </style>
</head>
<body class="bg-gray-100">
    <div class="min-h-screen">
        <!-- Main Content -->
        <main class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
            <!-- Header -->
            <div class="md:flex md:items-center md:justify-between mb-8">
                <div class="flex-1 min-w-0">
                    <h2 class="text-2xl font-bold leading-7 text-gray-900 sm:text-3xl sm:truncate">
                        Transaction History
                    </h2>
                </div>
                <div class="mt-4 flex md:mt-0 md:ml-4">
                    <a href="/admin/add_transaction.php" class="ml-3 inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                        <i class="fas fa-plus mr-2"></i>
                        New Transaction
                    </a>
                </div>
            </div>

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
                                    <dd class="text-lg font-semibold text-gray-900">RM <?php echo number_format($summary['total_payments_made'], 2); ?></dd>
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
                                    <dd class="text-lg font-semibold text-gray-900">RM <?php echo number_format($summary['total_amount'], 2); ?></dd>
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
                                    <dd class="text-lg font-semibold text-green-900">RM <?php echo number_format($summary['outstanding_balance'], 2); ?></dd>
                                </dl>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Monthly Transactions Graph -->
            <div class="bg-white shadow rounded-lg p-6 mb-8">
                <div class="px-4 py-5 sm:px-6">
                    <h3 class="text-lg leading-6 font-medium text-gray-900">Monthly Transaction Overview</h3>
                </div>
                <div class="px-4 py-5 sm:p-6">
                    <div style="height: 300px;">
                        <canvas id="monthlyChart"></canvas>
                    </div>
                </div>
            </div>

            <!-- Recent Transactions -->
            <div class="bg-white shadow rounded-lg">
                <div class="px-4 py-5 sm:px-6">
                    <div class="flex justify-between items-center">
                        <h3 class="text-lg leading-6 font-medium text-gray-900">Recent Transactions</h3>
                    </div>
                </div>
                <div class="px-4 py-5 sm:p-6">
                    <?php foreach ($grouped_transactions as $month => $month_data): ?>
                        <div class="mb-8">
                            <!-- Month Header with Light Blue Background -->
                            <div class="bg-blue-100 p-4 rounded-t-lg border-b border-blue-200 cursor-pointer hover:bg-blue-150 transition-colors"
                                 onclick="toggleMonth('<?php echo $month; ?>')" id="header-<?php echo $month; ?>">
                                <div class="flex justify-between items-center">
                                    <h4 class="text-lg font-medium text-blue-800">
                                        <i class="fas fa-chevron-right transform transition-transform duration-200" id="chevron-<?php echo $month; ?>"></i>
                                        <span class="ml-2"><?php echo $month_data['month_name']; ?></span>
                                    </h4>
                                    <span class="text-blue-600">Total: RM <?php echo number_format($month_data['total'], 2); ?></span>
                                </div>
                            </div>

                            <div class="overflow-hidden transition-all duration-300 ease-in-out" style="max-height: 0;" id="content-<?php echo $month; ?>">
                                <div class="overflow-x-auto bg-white rounded-b-lg shadow">
                                    <table class="min-w-full" id="table-<?php echo $month; ?>">
                                        <thead class="bg-blue-50">
                                            <tr>
                                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-blue-800 uppercase tracking-wider">User</th>
                                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-blue-800 uppercase tracking-wider">Type</th>
                                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-blue-800 uppercase tracking-wider">Amount</th>
                                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-blue-800 uppercase tracking-wider">Description</th>
                                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-blue-800 uppercase tracking-wider">Transaction Date</th>
                                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-blue-800 uppercase tracking-wider">Image</th>
                                            </tr>
                                        </thead>
                                        <tbody class="bg-white divide-y divide-gray-200" id="tbody-<?php echo $month; ?>">
                                        </tbody>
                                    </table>

                                    <!-- Pagination -->
                                    <div class="bg-white px-4 py-3 flex items-center justify-between border-t border-gray-200">
                                        <div class="flex-1 flex items-center justify-between">
                                            <div>
                                                <p class="text-sm text-gray-700" id="pagination-info-<?php echo $month; ?>">
                                                </p>
                                            </div>
                                            <div>
                                                <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px" id="pagination-<?php echo $month; ?>">
                                                </nav>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </main>
    </div>

    <script>
    // Store all transactions data
    const transactionsData = <?php echo json_encode($grouped_transactions); ?>;
    const ITEMS_PER_PAGE = 10;
    const monthStates = {};

    // Initialize pagination for each month
    function initializeMonth(month) {
        if (!monthStates[month]) {
            monthStates[month] = {
                currentPage: 1,
                data: transactionsData[month].transactions
            };
            renderMonthData(month);
        }
    }

    // Render month data for current page
    function renderMonthData(month) {
        const state = monthStates[month];
        const tbody = document.getElementById(`tbody-${month}`);
        const startIndex = (state.currentPage - 1) * ITEMS_PER_PAGE;
        const endIndex = startIndex + ITEMS_PER_PAGE;
        const pageData = state.data.slice(startIndex, endIndex);

        // Clear current content
        tbody.innerHTML = '';

        // Add rows
        pageData.forEach(transaction => {
            const row = document.createElement('tr');
            row.innerHTML = `
                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                    ${transaction.full_name} (${transaction.member_id})
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                    ${transaction.type}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                    RM ${parseFloat(transaction.amount).toFixed(2)}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                    ${transaction.description}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                    ${transaction.formatted_date}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-blue-600">
                    ${transaction.image_path ? `<a href="javascript:void(0)" onclick="showImageModal('/${transaction.image_path}')" class="hover:text-blue-800">View Image</a>` : '-'}
                </td>
            `;
            tbody.appendChild(row);
        });

        // Update pagination
        updatePagination(month);
    }

    // Update pagination controls
    function updatePagination(month) {
        const state = monthStates[month];
        const totalPages = Math.ceil(state.data.length / ITEMS_PER_PAGE);
        const paginationInfo = document.getElementById(`pagination-info-${month}`);
        const pagination = document.getElementById(`pagination-${month}`);

        // Update info text
        const startItem = ((state.currentPage - 1) * ITEMS_PER_PAGE) + 1;
        const endItem = Math.min(state.currentPage * ITEMS_PER_PAGE, state.data.length);
        paginationInfo.textContent = `Showing ${startItem} to ${endItem} of ${state.data.length} entries`;

        // Create pagination buttons
        let paginationHTML = '';

        // Previous button
        paginationHTML += `
            <button onclick="changePage('${month}', ${state.currentPage - 1})" 
                    class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50 ${state.currentPage === 1 ? 'cursor-not-allowed opacity-50' : ''}"
                    ${state.currentPage === 1 ? 'disabled' : ''}>
            Previous
            </button>`;

        // Page numbers
        for (let i = 1; i <= totalPages; i++) {
            if (i === state.currentPage) {
                paginationHTML += `
                    <button class="relative inline-flex items-center px-4 py-2 border border-blue-500 bg-blue-50 text-sm font-medium text-blue-600">
                        ${i}
                    </button>`;
            } else {
                paginationHTML += `
                    <button onclick="changePage('${month}', ${i})" 
                            class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700 hover:bg-gray-50">
                        ${i}
                    </button>`;
            }
        }

        // Next button
        paginationHTML += `
            <button onclick="changePage('${month}', ${state.currentPage + 1})" 
                    class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50 ${state.currentPage === totalPages ? 'cursor-not-allowed opacity-50' : ''}"
                    ${state.currentPage === totalPages ? 'disabled' : ''}>
            Next
            </button>`;

        pagination.innerHTML = paginationHTML;
    }

    // Change page
    function changePage(month, page) {
        const state = monthStates[month];
        const totalPages = Math.ceil(state.data.length / ITEMS_PER_PAGE);
        
        if (page >= 1 && page <= totalPages) {
            state.currentPage = page;
            renderMonthData(month);
        }
    }

    // Store expanded state
    const expandedMonths = new Set();

    // Toggle month content
    function toggleMonth(month) {
        const content = document.getElementById(`content-${month}`);
        const chevron = document.getElementById(`chevron-${month}`);
        const header = document.getElementById(`header-${month}`);
        
        if (expandedMonths.has(month)) {
            // Collapse
            content.style.maxHeight = '0px';
            chevron.style.transform = 'rotate(0deg)';
            expandedMonths.delete(month);
        } else {
            // Expand
            expandedMonths.add(month);
            content.style.maxHeight = content.scrollHeight + 'px';
            chevron.style.transform = 'rotate(90deg)';
            
            // Initialize month data if not already done
            if (!monthStates[month]) {
                initializeMonth(month);
            }
        }
    }

    // Update max-height when content changes (e.g., pagination)
    function updateContentHeight(month) {
        if (expandedMonths.has(month)) {
            const content = document.getElementById(`content-${month}`);
            content.style.maxHeight = content.scrollHeight + 'px';
        }
    }

    // Modify renderMonthData to update height after rendering
    const originalRenderMonthData = renderMonthData;
    renderMonthData = function(month) {
        originalRenderMonthData(month);
        updateContentHeight(month);
    }

    // Initialize first month as expanded
    document.addEventListener('DOMContentLoaded', function() {
        const firstMonth = Object.keys(transactionsData)[0];
        if (firstMonth) {
            toggleMonth(firstMonth);
        }
        Object.keys(transactionsData).forEach(month => {
            initializeMonth(month);
        });
    });

    function showImageModal(imagePath) {
        Swal.fire({
            imageUrl: imagePath,
            imageAlt: 'Transaction Image',
            width: 'auto',
            padding: '1em',
            showCloseButton: true,
            showConfirmButton: false,
            customClass: {
                image: 'max-h-[80vh] max-w-[90vw]'
            }
        });
    }
    </script>

    <script>
    // Initialize the monthly transactions chart
    const ctx = document.getElementById('monthlyChart').getContext('2d');
    const monthlyData = <?php echo json_encode($monthly_data); ?>;

    const labels = monthlyData.map(item => {
        const date = new Date(item.month + '-01');
        return date.toLocaleDateString('en-US', { month: 'short', year: 'numeric' });
    });
    const transactionData = monthlyData.map(item => parseFloat(item.total_amount));

    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [
                {
                    label: 'Total Transaction Amount (RM)',
                    data: transactionData,
                    backgroundColor: 'rgba(59, 130, 246, 0.5)', // Light blue
                    borderColor: 'rgb(37, 99, 235)', // Darker blue
                    borderWidth: 1
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
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
                legend: {
                    position: 'top',
                },
                title: {
                    display: true,
                    text: 'Monthly Transaction Amount'
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return `Total Amount: RM ${context.parsed.y.toLocaleString()}`;
                        }
                    }
                },
                datalabels: {
                    anchor: 'end',
                    align: 'top',
                    formatter: function(value) {
                        return 'RM ' + value.toLocaleString();
                    },
                    color: '#666',
                    font: {
                        weight: 'bold'
                    }
                }
            }
        },
        plugins: [ChartDataLabels]
    });
    </script>
    <script>
        $(document).ready(function() {
            <?php if (isset($_SESSION['success_message'])): ?>
            Swal.fire({
                icon: 'success',
                title: 'Success!',
                text: '<?php echo $_SESSION['success_message']; ?>',
                timer: 2000,
                showConfirmButton: false
            });
            <?php unset($_SESSION['success_message']); ?>
            <?php endif; ?>
        });
    </script>
</body>
</html>

<?php require_once 'template/footer.php'; ?>