<?php
require_once '../includes/config.php';
require_once '../includes/session.php';
requireAdmin();

$user_id = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0;

// Get user info
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

if (!$user) {
    header("Location: users.php");
    exit;
}

// Get all transactions grouped by month
$stmt = $conn->prepare("
    SELECT 
        t.*,
        u.member_id,
        u.full_name,
        DATE_FORMAT(t.date_transaction, '%Y-%m') as month_group
    FROM transactions t
    LEFT JOIN users u ON t.user_id = u.id
    WHERE t.user_id = ? 
    ORDER BY t.date_transaction DESC, t.id DESC
");
$stmt->execute([$user_id]);
$all_transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Group transactions by month
$transactions_by_month = [];
$monthly_totals = [];
foreach ($all_transactions as $transaction) {
    $month = $transaction['month_group'];
    $transactions_by_month[$month][] = $transaction;
    
    if (!isset($monthly_totals[$month])) {
        $monthly_totals[$month] = 0;
    }
    $monthly_totals[$month] += $transaction['amount'];
}

// Get total amount of all transactions
$total_amount = array_sum($monthly_totals);

// Get total payments from payments table
$stmt = $conn->prepare("
    SELECT COALESCE(SUM(amount), 0) as total_payments
    FROM payments 
    WHERE user_id = ?
");
$stmt->execute([$user_id]);
$total_payments = $stmt->fetchColumn();

// Calculate outstanding balance (transactions - payments)
$outstanding_balance = $total_amount - $total_payments;

// Get payments for this user
$stmt = $conn->prepare("SELECT * FROM payments WHERE user_id = ? ORDER BY payment_date DESC");
$stmt->execute([$user_id]);
$payments = $stmt->fetchAll();

include 'template/header.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Dashboard - <?php echo htmlspecialchars($user['full_name']); ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body class="bg-gray-100">
    <div class="min-h-screen">
        <!-- Main Content -->
        <main class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
            <!-- User Information -->
            <div class="bg-white shadow rounded-lg mb-8 p-6">
                <h2 class="text-2xl font-bold mb-4">User Information</h2>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <p class="text-gray-600">Full Name:</p>
                        <p class="font-semibold"><?php echo htmlspecialchars($user['full_name']); ?></p>
                    </div>
                    <div>
                        <p class="text-gray-600">Member ID:</p>
                        <p class="font-semibold"><?php echo htmlspecialchars($user['member_id']); ?></p>
                    </div>
                    <div>
                        <p class="text-gray-600">Email:</p>
                        <p class="font-semibold"><?php echo htmlspecialchars($user['email']); ?></p>
                    </div>
                    <div>
                        <p class="text-gray-600">Phone:</p>
                        <p class="font-semibold"><?php echo htmlspecialchars($user['phone']); ?></p>
                    </div>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="flex justify-end space-x-4 mb-8">
                <a href="/admin/add_transaction.php?user_id=<?php echo $user_id; ?>" class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                    <svg class="-ml-1 mr-2 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                    </svg>
                    Add Transaction
                </a>
                <a href="/admin/add_payment.php?user_id=<?php echo $user_id; ?>" class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                    <svg class="-ml-1 mr-2 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>
                    </svg>
                    Add Payment
                </a>
            </div>

            <!-- Statistics Cards -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-8">
                <div class="bg-white overflow-hidden shadow rounded-lg">
                    <div class="p-5">
                        <div class="flex items-center">
                            <div class="flex-shrink-0 bg-indigo-500 rounded-md p-3">
                                <svg class="h-6 w-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                            </div>
                            <div class="ml-5 w-0 flex-1">
                                <dl>
                                    <dt class="text-sm font-medium text-gray-500 truncate">Total Transactions</dt>
                                    <dd class="text-lg font-medium text-gray-900">RM <?php echo number_format($total_amount, 2); ?></dd>
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
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                            </div>
                            <div class="ml-5 w-0 flex-1">
                                <dl>
                                    <dt class="text-sm font-medium text-gray-500 truncate">Total Payments</dt>
                                    <dd class="text-lg font-medium text-gray-900">RM <?php echo number_format($total_payments, 2); ?></dd>
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
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                            </div>
                            <div class="ml-5 w-0 flex-1">
                                <dl>
                                    <dt class="text-sm font-medium text-gray-500 truncate">Outstanding Balance</dt>
                                    <dd class="text-lg font-medium <?php echo $outstanding_balance > 0 ? 'text-red-600' : 'text-green-600'; ?>">
                                        RM <?php echo number_format($outstanding_balance, 2); ?>
                                    </dd>
                                </dl>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Transactions by Month -->
            <?php foreach ($transactions_by_month as $month => $month_transactions): ?>
            <div class="bg-white shadow rounded-lg mb-8">
                <div class="px-4 py-5 sm:px-6 flex justify-between items-center bg-blue-50 cursor-pointer" onclick="toggleMonth('<?php echo $month; ?>')">
                    <div class="flex items-center">
                        <svg class="h-5 w-5 transform transition-transform duration-200" id="<?php echo $month; ?>-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                        </svg>
                        <h3 class="text-lg leading-6 font-medium text-gray-900 ml-2">
                            <?php echo date('F Y', strtotime($month . '-01')); ?>
                        </h3>
                    </div>
                    <div class="text-blue-600 font-medium">Total: RM <?php echo number_format($monthly_totals[$month], 2); ?></div>
                </div>
                <div id="<?php echo $month; ?>-content" class="flex flex-col" style="display: none;">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Transaction ID</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Description</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Transaction Date</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Image</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php foreach ($month_transactions as $transaction): ?>
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-blue-600 hover:text-blue-800">
                                        <?php echo htmlspecialchars($transaction['transaction_id']); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <?php echo htmlspecialchars($transaction['type']); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        RM <?php echo number_format($transaction['amount'], 2); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <?php echo htmlspecialchars($transaction['description']); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <?php echo date('d M Y | h:i A', strtotime($transaction['date_transaction'])); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <?php if ($transaction['image_path']): ?>
                                            <button onclick="showImageModal('<?php echo htmlspecialchars($transaction['image_path']); ?>')" class="text-blue-500 hover:text-blue-700">View Image</button>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>

            <!-- Recent Payments -->
            <div class="bg-white shadow rounded-lg mb-8">
                <div class="px-4 py-5 sm:px-6 flex justify-between items-center bg-green-50">
                    <h3 class="text-lg leading-6 font-medium text-gray-900">Recent Payments</h3>
                </div>
                <div class="flex flex-col">
                    <div class="-my-2 overflow-x-auto sm:-mx-6 lg:-mx-8">
                        <div class="py-2 align-middle inline-block min-w-full sm:px-6 lg:px-8">
                            <div class="shadow overflow-hidden border-b border-gray-200 sm:rounded-lg">
                                <table class="min-w-full divide-y divide-gray-200">
                                    <thead class="bg-green-50">
                                        <tr>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Payment Date</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Payment Method</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Payment ID</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Notes</th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white divide-y divide-gray-200">
                                        <?php foreach ($payments as $payment): ?>
                                        <tr class="hover:bg-gray-50">
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                <?php echo date('d M Y | h:i A', strtotime($payment['payment_date'])); ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                RM <?php echo number_format($payment['amount'], 2); ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                <?php echo htmlspecialchars($payment['payment_method']); ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                <?php echo htmlspecialchars($payment['id']); ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                <?php echo htmlspecialchars($payment['notes']); ?>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- Image Modal -->
    <div id="imageModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="bg-white rounded-lg max-w-3xl w-full">
                <div class="flex justify-between items-center p-4 border-b">
                    <h3 class="text-lg font-semibold">Transaction Image</h3>
                    <button onclick="closeImageModal()" class="text-gray-500 hover:text-gray-700">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
                <div class="p-4">
                    <img id="modalImage" src="" alt="Transaction Image" class="max-w-full h-auto mx-auto">
                </div>
            </div>
        </div>
    </div>

    <script>
        function toggleMonth(month) {
            const content = document.getElementById(`${month}-content`);
            const icon = document.getElementById(`${month}-icon`);
            
            if (content.style.display === 'none') {
                content.style.display = 'block';
                icon.style.transform = 'rotate(180deg)';
            } else {
                content.style.display = 'none';
                icon.style.transform = 'rotate(0)';
            }
        }
        
        // Show the current month's transactions by default
        document.addEventListener('DOMContentLoaded', function() {
            const currentMonth = '<?php echo date('Y-m'); ?>';
            toggleMonth(currentMonth);
        });

        function showImageModal(imagePath) {
            const modal = document.getElementById('imageModal');
            const modalImg = document.getElementById('modalImage');
            
            // Set image source
            modalImg.src = imagePath;
            
            // Show modal
            modal.classList.remove('hidden');
            
            // Handle click outside
            modal.addEventListener('click', function(e) {
                if (e.target === modal) {
                    closeImageModal();
                }
            });
            
            // Handle escape key
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape') {
                    closeImageModal();
                }
            });
        }

        function closeImageModal() {
            const modal = document.getElementById('imageModal');
            modal.classList.add('hidden');
            const modalImg = document.getElementById('modalImage');
            modalImg.src = '';
            
            // Remove event listeners
            modal.removeEventListener('click', null);
            document.removeEventListener('keydown', null);
        }
    </script>
</body>
</html>

<?php include 'template/footer.php'; ?>
