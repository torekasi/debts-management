<?php
require_once '../includes/config.php';
require_once '../includes/session.php';
requireUser();

// Get total spending
$stmt = $conn->prepare("
    SELECT SUM(amount) as total_spending 
    FROM transactions 
    WHERE user_id = ?
");
$stmt->execute([$_SESSION['user_id']]);
$totalSpending = $stmt->fetch()['total_spending'] ?? 0;

// Get current month spending
$stmt = $conn->prepare("
    SELECT SUM(amount) as month_spending 
    FROM transactions 
    WHERE user_id = ? 
    AND MONTH(date_transaction) = MONTH(CURRENT_DATE())
    AND YEAR(date_transaction) = YEAR(CURRENT_DATE())
");
$stmt->execute([$_SESSION['user_id']]);
$monthSpending = $stmt->fetch()['month_spending'] ?? 0;

// Get total transaction amount
$stmt = $conn->prepare("
    SELECT COALESCE(SUM(amount), 0) as total_amount 
    FROM transactions 
    WHERE user_id = ?
");
$stmt->execute([$_SESSION['user_id']]);
$totalTransactionAmount = $stmt->fetch()['total_amount'] ?? 0;

// Get total payment amount
$stmt = $conn->prepare("
    SELECT COALESCE(SUM(amount), 0) as total_amount 
    FROM payments 
    WHERE user_id = ?
");
$stmt->execute([$_SESSION['user_id']]);
$totalPaymentAmount = $stmt->fetch()['total_amount'] ?? 0;

// Calculate balance
$balance = $totalTransactionAmount - $totalPaymentAmount;

// Get transaction history with pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

$stmt = $conn->prepare("SELECT COUNT(*) as total FROM transactions WHERE user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$totalRecords = $stmt->fetch()['total'];
$totalPages = ceil($totalRecords / $limit);

$stmt = $conn->prepare("
    SELECT * FROM transactions 
    WHERE user_id = ? 
    ORDER BY date_transaction DESC 
    LIMIT ? OFFSET ?
");
$stmt->execute([$_SESSION['user_id'], $limit, $offset]);
$transactions = $stmt->fetchAll();

// Group transactions by month
$grouped_transactions = [];
foreach ($transactions as $transaction) {
    $month = date('F Y', strtotime($transaction['date_transaction']));
    if (!isset($grouped_transactions[$month])) {
        $grouped_transactions[$month] = [];
    }
    $grouped_transactions[$month][] = $transaction;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Member Dashboard - <?php echo APP_NAME; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        body {
            padding-top: 4rem;
            padding-bottom: 4rem;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        .content-container {
            flex: 1;
            max-width: 650px;
            margin: 0 auto;
            padding: 1rem;
            width: 100%;
        }
        @media (max-width: 768px) {
            .content-container {
                max-width: 100%;
            }
        }
    </style>
</head>
<body class="bg-gray-100" >
    <?php include 'template/member_header.php'; ?>

    <!-- Success Notification -->
    <?php if (isset($_GET['success'])): ?>
    <div class="fixed top-20 left-1/2 transform -translate-x-1/2 z-50 w-full max-w-md px-4 notification-container">
        <div class="bg-green-50 border border-green-200 rounded-lg p-4 shadow-lg flex justify-between items-center">
            <div class="flex items-center">
                <svg class="h-5 w-5 text-green-600 mr-2" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                </svg>
                <span class="text-green-800">Operation completed successfully</span>
            </div>
            <button onclick="closeNotification(this)" 
                    class="ml-4 px-3 py-1 bg-green-600 hover:bg-green-700 text-white rounded-md transition-colors"
                    aria-label="Close notification">
                OK
            </button>
        </div>
    </div>
    <?php endif; ?>

    <main class="content-container">
        <!-- Summary Cards -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
            <div class="bg-white rounded-lg shadow-md p-4 text-center">
                <div class="flex items-center justify-center mb-2">
                    <i class="fas fa-shopping-cart text-blue-500 mr-2 text-base"></i>
                    <h3 class="text-xs font-medium text-blue-500">Your Shopping</h3>
                </div>
                <p class="text-xl font-bold text-gray-900">RM <?php echo number_format($totalTransactionAmount, 2); ?></p>
            </div>

            <div class="bg-white rounded-lg shadow-md p-4 text-center">
                <div class="flex items-center justify-center mb-2">
                    <i class="fas fa-credit-card text-green-500 mr-2 text-base"></i>
                    <h3 class="text-xs font-medium text-green-500">Payments Made</h3>
                </div>
                <p class="text-xl font-bold text-gray-900">RM <?php echo number_format($totalPaymentAmount, 2); ?></p>
            </div>

            <div class="bg-<?php echo $balance > 0 ? 'red' : 'green'; ?>-50 rounded-lg shadow-md p-4 text-center">
                <div class="flex items-center justify-center mb-2">
                    <i class="fas fa-balance-scale text-<?php echo $balance > 0 ? 'red' : 'green'; ?>-500 mr-2 text-base"></i>
                    <h3 class="text-xs font-medium text-<?php echo $balance > 0 ? 'red' : 'green'; ?>-500">Balance</h3>
                </div>
                <p class="text-xl font-bold text-<?php echo $balance > 0 ? 'red' : 'green'; ?>-500">
                    RM <?php echo number_format(abs($balance), 2); ?>
                </p>
                <p class="text-[10px] text-gray-500">
                    <?php echo $balance > 0 ? 'Due Balance' : 'Fully Paid'; ?>
                </p>
            </div>
        </div>

        <!-- Transaction History -->
        <div class="bg-gradient-to-r from-blue-500 to-blue-600 text-white p-4 rounded-lg shadow-md mb-6">
            <h2 class="text-2xl font-bold">Transaction History</h2>
        </div>

        <?php foreach ($grouped_transactions as $month => $transactions) {
            $total_month = array_reduce($transactions, function($carry, $transaction) {
                return $carry + $transaction['amount'];
            }, 0);
        ?>
            <div class="bg-white rounded-lg shadow-sm mb-6">
                <!-- Month Header -->
                <div class="bg-gray-50 p-4 border-b border-gray-200 rounded-t-lg">
                    <div class="flex justify-between items-center">
                        <h3 class="text-lg font-semibold text-gray-900"><?php echo $month; ?></h3>
                        <span class="text-blue-600 font-semibold">Total: RM <?php echo number_format($total_month, 2); ?></span>
                    </div>
                </div>

                <!-- Month's Transactions -->
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Action</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php foreach ($transactions as $transaction) { ?>
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        <?php echo date('d M Y', strtotime($transaction['date_transaction'])); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium <?php echo $transaction['type'] === 'Payment' ? 'text-green-600' : 'text-red-600'; ?>">
                                        RM <?php echo number_format($transaction['amount'], 2); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        <?php
                                            // Prepare transaction data
                                            $modalData = [
                                                'transaction_id' => $transaction['transaction_id'],
                                                'date_transaction' => $transaction['date_transaction'],
                                                'type' => $transaction['type'],
                                                'amount' => $transaction['amount'],
                                                'description' => $transaction['description'] ?? '',
                                                'image_path' => $transaction['image_path'] ?? null
                                            ];
                                            $transactionData = htmlspecialchars(json_encode($modalData, JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_TAG | JSON_HEX_AMP), ENT_QUOTES, 'UTF-8');
                                        ?>
                                        <button onclick='showTransactionDetails(<?php echo $transactionData; ?>)' 
                                                class="text-blue-600 hover:text-blue-900">
                                            <i class="fas fa-info-circle"></i> Details
                                        </button>
                                    </td>
                                </tr>
                            <?php } ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php } ?>

        <!-- Pagination -->
        <?php if ($totalPages > 1): ?>
        <div class="px-4 md:px-6 py-4 border-t border-gray-200">
            <div class="flex justify-between items-center">
                <div class="text-sm text-gray-700">
                    Page <?php echo $page; ?> of <?php echo $totalPages; ?>
                </div>
                <div class="flex space-x-2">
                    <?php if ($page > 1): ?>
                        <a href="?page=<?php echo ($page - 1); ?>" 
                           class="px-3 py-1 border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50">
                            Previous
                        </a>
                    <?php endif; ?>
                    
                    <?php if ($page < $totalPages): ?>
                        <a href="?page=<?php echo ($page + 1); ?>" 
                           class="px-3 py-1 border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50">
                            Next
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Transaction Details Modal -->
        <div id="transactionDetailsModal" class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity hidden z-50 overflow-y-auto">
            <div class="flex min-h-screen items-center justify-center p-4">
                <div class="relative w-full max-w-2xl my-8">
                    <div class="relative bg-white rounded-xl shadow-2xl transform transition-all">
                        <!-- Modal Header -->
                        <div class="px-6 py-4 border-b border-gray-200 bg-gradient-to-r from-blue-500 to-blue-600 rounded-t-xl">
                            <h3 class="text-xl font-bold text-white">
                                Transaction Details
                            </h3>
                        </div>

                        <!-- Modal Content -->
                        <div class="px-6 py-4">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <!-- Left Column -->
                                <div class="space-y-4">
                                    <div class="bg-gray-50 p-4 rounded-lg">
                                        <div class="flex items-center mb-3">
                                            <i class="fas fa-hashtag text-blue-500 mr-2"></i>
                                            <p class="text-sm font-medium text-gray-500">Transaction ID</p>
                                        </div>
                                        <p id="modalTransactionId" class="text-base text-gray-900 font-semibold"></p>
                                    </div>

                                    <div class="bg-gray-50 p-4 rounded-lg">
                                        <div class="flex items-center mb-3">
                                            <i class="fas fa-calendar text-blue-500 mr-2"></i>
                                            <p class="text-sm font-medium text-gray-500">Date & Time</p>
                                        </div>
                                        <p id="modalTransactionDate" class="text-base text-gray-900 font-semibold"></p>
                                    </div>

                                    <div class="bg-gray-50 p-4 rounded-lg">
                                        <div class="flex items-center mb-3">
                                            <i class="fas fa-tag text-blue-500 mr-2"></i>
                                            <p class="text-sm font-medium text-gray-500">Payment Type</p>
                                        </div>
                                        <p id="modalTransactionType" class="text-base text-gray-900 font-semibold"></p>
                                    </div>
                                </div>

                                <!-- Right Column -->
                                <div class="space-y-4">
                                    <div class="bg-gray-50 p-4 rounded-lg">
                                        <div class="flex items-center mb-3">
                                            <i class="fas fa-money-bill text-blue-500 mr-2"></i>
                                            <p class="text-sm font-medium text-gray-500">Amount</p>
                                        </div>
                                        <p id="modalTransactionAmount" class="text-xl text-blue-600 font-bold"></p>
                                    </div>

                                    <div class="bg-gray-50 p-4 rounded-lg">
                                        <div class="flex items-center mb-3">
                                            <i class="fas fa-align-left text-blue-500 mr-2"></i>
                                            <p class="text-sm font-medium text-gray-500">Description</p>
                                        </div>
                                        <p id="modalTransactionDescription" class="text-base text-gray-900"></p>
                                    </div>
                                </div>
                            </div>

                            <!-- Receipt Image Section -->
                            <div id="modalTransactionImage" class="mt-6 hidden">
                                <div class="border-t border-gray-200 pt-6">
                                    <div class="flex items-center mb-4">
                                        <i class="fas fa-receipt text-blue-500 mr-2"></i>
                                        <h4 class="text-lg font-semibold text-gray-900">Receipt</h4>
                                    </div>
                                    <div class="bg-gray-50 p-4 rounded-lg">
                                        <img id="modalReceiptImage" src="" alt="Receipt" class="max-w-full h-auto rounded-lg shadow-sm mx-auto">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Modal Footer -->
                        <div class="px-6 py-4 bg-gray-50 rounded-b-xl">
                            <button type="button" onclick="hideTransactionModal()" 
                                    class="w-full inline-flex justify-center items-center px-4 py-3 border border-red-300 rounded-md shadow-sm text-sm font-medium text-white bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 transition-colors duration-200">
                                <i class="fas fa-times mr-2"></i>
                                Close
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    <script>
        // Close notification handler
        function closeNotification(button) {
            const notification = button.closest('.fixed');
            if (notification) {
                notification.remove();
            }
        }

        function showTransactionDetails(transaction) {
                try {
                    // Update modal content
                    document.getElementById('modalTransactionId').textContent = transaction.transaction_id || '';
                    document.getElementById('modalTransactionDate').textContent = transaction.date_transaction ? 
                        new Date(transaction.date_transaction).toLocaleString('en-MY', {
                            year: 'numeric',
                            month: 'long',
                            day: 'numeric',
                            hour: '2-digit',
                            minute: '2-digit',
                            hour12: true
                        }) : '';
                    document.getElementById('modalTransactionType').textContent = transaction.type || '';
                    document.getElementById('modalTransactionAmount').textContent = transaction.amount ? 
                        'RM ' + parseFloat(transaction.amount).toFixed(2) : '';
                    document.getElementById('modalTransactionDescription').textContent = transaction.description || 'No description provided';

                    // Handle receipt image
                    const imageContainer = document.getElementById('modalTransactionImage');
                    const modalImage = document.getElementById('modalReceiptImage');
                    
                    if (transaction.image_path) {
                        modalImage.src = '../' + transaction.image_path;
                        imageContainer.classList.remove('hidden');
                    } else {
                        imageContainer.classList.add('hidden');
                    }

                    // Show modal
                    document.getElementById('transactionDetailsModal').classList.remove('hidden');
                    document.body.style.overflow = 'hidden';
                } catch (error) {
                    console.error('Error showing transaction details:', error);
                }
            }

            function hideTransactionModal() {
                document.getElementById('transactionDetailsModal').classList.add('hidden');
                document.body.style.overflow = 'auto';
            }

            // Close modal when clicking outside
            document.getElementById('transactionDetailsModal').addEventListener('click', function(e) {
                if (e.target === this) {
                    hideTransactionModal();
                }
            });

            // Close modal on escape key
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape') {
                    hideTransactionModal();
                }
            });
        </script>
    </main>

    <?php include 'template/member_footer.php'; ?>
</body>
</html>
