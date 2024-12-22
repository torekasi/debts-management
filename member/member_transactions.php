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
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Transaction History - Debt Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="bg-gray-100">
    <?php include 'template/member_header.php'; ?>

    <main class="content-container p-4 md:p-6">
        <!-- Summary Cards -->
        <div class="grid grid-cols-2 gap-4 mb-8">
            <!-- Total Spending Card -->
            <div class="bg-white rounded-lg shadow-sm p-4 md:p-6">
                <div class="flex items-center">
                    <div class="p-2 md:p-3 rounded-full bg-blue-100 text-blue-500">
                        <i class="fas fa-wallet text-lg md:text-2xl"></i>
                    </div>
                    <div class="ml-3 md:ml-4">
                        <p class="text-xs md:text-sm font-medium text-gray-500">Total Spending</p>
                        <h3 class="text-base md:text-2xl font-bold text-gray-900">RM <?php echo number_format($totalSpending, 2); ?></h3>
                    </div>
                </div>
            </div>

            <!-- This Month's Spending Card -->
            <div class="bg-white rounded-lg shadow-sm p-4 md:p-6">
                <div class="flex items-center">
                    <div class="p-2 md:p-3 rounded-full bg-green-100 text-green-500">
                        <i class="fas fa-calendar-alt text-lg md:text-2xl"></i>
                    </div>
                    <div class="ml-3 md:ml-4">
                        <p class="text-xs md:text-sm font-medium text-gray-500">This Month's Spending</p>
                        <h3 class="text-base md:text-2xl font-bold text-gray-900">RM <?php echo number_format($monthSpending, 2); ?></h3>
                    </div>
                </div>
            </div>
        </div>

        <!-- Transaction History -->
        <div class="bg-white rounded-lg shadow-sm">
            <div class="p-4 md:p-6 border-b border-gray-200">
                <h2 class="text-lg md:text-xl font-bold text-gray-900">Transaction History</h2>
            </div>
            
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 md:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                            <th class="px-4 md:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
                            <th class="px-4 md:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Action</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($transactions as $transaction): ?>
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 md:px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                <?php echo date('d M Y H:i', strtotime($transaction['date_transaction'])); ?>
                            </td>
                            <td class="px-4 md:px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                RM <?php echo number_format($transaction['amount'], 2); ?>
                            </td>
                            <td class="px-4 md:px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                <button onclick="showTransactionDetails(<?php echo htmlspecialchars(json_encode($transaction)); ?>)" 
                                        class="text-blue-600 hover:text-blue-900">
                                    <i class="fas fa-info-circle"></i> Details
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

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
        </div>

        <!-- Transaction Details Modal -->
        <div id="transactionModal" class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity hidden z-50">
            <div class="fixed inset-0 overflow-y-auto">
                <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
                    <div class="relative transform overflow-hidden rounded-lg bg-white text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-lg">
                        <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                            <div class="sm:flex sm:items-start">
                                <div class="mt-3 text-center sm:mt-0 sm:text-left w-full">
                                    <h3 class="text-lg font-medium leading-6 text-gray-900 mb-4" id="modalTitle">
                                        Transaction Details
                                    </h3>
                                    <div class="mt-2 space-y-3">
                                        <p class="text-sm text-gray-500">
                                            <span class="font-medium">Transaction ID:</span>
                                            <span id="modalTransactionId"></span>
                                        </p>
                                        <p class="text-sm text-gray-500">
                                            <span class="font-medium">Date:</span>
                                            <span id="modalDate"></span>
                                        </p>
                                        <p class="text-sm text-gray-500">
                                            <span class="font-medium">Type:</span>
                                            <span id="modalType"></span>
                                        </p>
                                        <p class="text-sm text-gray-500">
                                            <span class="font-medium">Amount:</span>
                                            <span id="modalAmount"></span>
                                        </p>
                                        <p class="text-sm text-gray-500">
                                            <span class="font-medium">Description:</span>
                                            <span id="modalDescription"></span>
                                        </p>
                                        <div id="modalImageContainer" class="mt-4 hidden">
                                            <p class="text-sm font-medium text-gray-500 mb-2">Receipt:</p>
                                            <img id="modalImage" src="" alt="Receipt" class="max-w-full h-auto rounded-lg">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="bg-gray-50 px-4 py-3 sm:flex sm:flex-row-reverse sm:px-6">
                            <button type="button" onclick="hideTransactionDetails()" 
                                    class="mt-3 inline-flex w-full justify-center rounded-md border border-gray-300 bg-white px-4 py-2 text-base font-medium text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                                Close
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <?php include 'template/member_footer.php'; ?>

    <script>
        function showTransactionDetails(transaction) {
            document.getElementById('modalTransactionId').textContent = transaction.transaction_id;
            document.getElementById('modalDate').textContent = new Date(transaction.date_transaction).toLocaleString();
            document.getElementById('modalType').textContent = transaction.type;
            document.getElementById('modalAmount').textContent = 'RM ' + parseFloat(transaction.amount).toFixed(2);
            document.getElementById('modalDescription').textContent = transaction.description;

            const imageContainer = document.getElementById('modalImageContainer');
            const modalImage = document.getElementById('modalImage');
            
            if (transaction.image_path) {
                modalImage.src = '../' + transaction.image_path;
                imageContainer.classList.remove('hidden');
            } else {
                imageContainer.classList.add('hidden');
            }

            document.getElementById('transactionModal').classList.remove('hidden');
            document.body.style.overflow = 'hidden';
        }

        function hideTransactionDetails() {
            document.getElementById('transactionModal').classList.add('hidden');
            document.body.style.overflow = 'auto';
        }

        // Close modal when clicking outside
        document.getElementById('transactionModal').addEventListener('click', function(e) {
            if (e.target === this) {
                hideTransactionDetails();
            }
        });

        // Close modal on escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                hideTransactionDetails();
            }
        });
    </script>
</body>
</html>
