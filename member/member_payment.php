<?php
require_once '../includes/config.php';
require_once '../includes/session.php';
requireUser();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

// Get total payments
$stmt = $conn->prepare("SELECT COUNT(*) FROM payments WHERE user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$totalPayments = $stmt->fetchColumn();
$totalPages = ceil($totalPayments / $limit);

// Get total payment amount
$stmt = $conn->prepare("SELECT COALESCE(SUM(amount), 0) as total_payment FROM payments WHERE user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$totalPaymentAmount = $stmt->fetchColumn();

// Get total transaction amount
$stmt = $conn->prepare("SELECT COALESCE(SUM(amount), 0) as total_transaction FROM transactions WHERE user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$totalTransactionAmount = $stmt->fetchColumn();

// Calculate balance
$balance = $totalTransactionAmount - $totalPaymentAmount;

// Get payments with pagination
$stmt = $conn->prepare("
    SELECT * FROM payments 
    WHERE user_id = ?
    ORDER BY payment_date DESC 
    LIMIT ? OFFSET ?
");
$stmt->execute([$_SESSION['user_id'], $limit, $offset]);
$payments = $stmt->fetchAll();

// Group payments by month
$grouped_payments = [];
foreach ($payments as $payment) {
    $month = date('F Y', strtotime($payment['payment_date']));
    if (!isset($grouped_payments[$month])) {
        $grouped_payments[$month] = [];
    }
    $grouped_payments[$month][] = $payment;
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
<body class="bg-gray-100">
    <?php include 'template/member_header.php'; ?>

    <main class="content-container">
        <!-- Summary Cards -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-8">
            <!-- Total Transactions Card -->
            <div class="bg-white rounded-lg shadow-sm p-4 md:p-6">
                <div class="flex items-center">
                    <div class="flex-shrink-0 bg-blue-500 rounded-md p-3">
                        <svg class="h-6 w-6 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 truncate">Total Transactions</dt>
                            <dd class="text-lg font-semibold text-gray-900">RM <?php echo number_format($totalTransactionAmount, 2); ?></dd>
                        </dl>
                    </div>
                </div>
            </div>

            <!-- Total Payments Card -->
            <div class="bg-white rounded-lg shadow-sm p-4 md:p-6">
                <div class="flex items-center">
                    <div class="flex-shrink-0 bg-green-500 rounded-md p-3">
                        <svg class="h-6 w-6 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
                        </svg>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 truncate">Total Payments</dt>
                            <dd class="text-lg font-semibold text-gray-900">RM <?php echo number_format($totalPaymentAmount, 2); ?></dd>
                        </dl>
                    </div>
                </div>
            </div>

            <!-- Balance Card -->
            <div class="bg-green-50 rounded-lg shadow-sm p-4 md:p-6">
                <div class="flex items-center">
                    <div class="flex-shrink-0 bg-red-500 rounded-md p-3">
                        <svg class="h-6 w-6 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 6l3 1m0 0l-3 9a5.002 5.002 0 006.001 0M6 7l3 9M6 7l6-2m6 2l3-1m-3 1l-3 9a5.002 5.002 0 006.001 0M18 7l3 9m-3-9l-6-2m0-2v2m0 16V5m0 16H9m3 0h3" />
                        </svg>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 truncate">Balance</dt>
                            <dd class="text-lg font-semibold text-<?php echo $balance > 0 ? 'red' : 'green'; ?>-600">
                                RM <?php echo number_format(abs($balance), 2); ?>
                                <span class="text-sm font-normal"><?php echo $balance > 0 ? '(Outstanding)' : '(Paid)'; ?></span>
                            </dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>

        <!-- Payment History -->
        <div class="bg-gradient-to-r from-blue-500 to-blue-600 text-white p-4 rounded-lg shadow-md mb-6">
            <h2 class="text-2xl font-bold">Payment History</h2>
        </div>

        <?php foreach ($grouped_payments as $month => $payments) {
            $total_month = array_reduce($payments, function($carry, $payment) {
                return $carry + $payment['amount'];
            }, 0);
        ?>
            <div class="bg-white rounded-lg shadow-sm mb-6">
                <!-- Month Header -->
                <div class="bg-gray-50 p-4 border-b border-gray-200 rounded-t-lg">
                    <div class="flex justify-between items-center">
                        <h3 class="text-lg font-semibold text-gray-900"><?php echo $month; ?></h3>
                        <span class="text-green-600 font-semibold">Total: RM <?php echo number_format($total_month, 2); ?></span>
                    </div>
                </div>

                <!-- Month's Payments -->
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
                                <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Details</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php foreach ($payments as $payment) { ?>
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        <?php echo date('d M Y', strtotime($payment['payment_date'])); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-green-600">
                                        RM <?php echo number_format($payment['amount'], 2); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm text-gray-900">
                                        <?php
                                            // Prepare payment data
                                            $modalData = [
                                                'id' => $payment['id'],
                                                'payment_date' => $payment['payment_date'],
                                                'amount' => $payment['amount'],
                                                'payment_method' => $payment['payment_method'],
                                                'reference_number' => $payment['reference_number'],
                                                'transaction_id' => $payment['transaction_id'],
                                                'notes' => $payment['notes'] ?? ''
                                            ];
                                            $paymentData = htmlspecialchars(json_encode($modalData, JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_TAG | JSON_HEX_AMP), ENT_QUOTES, 'UTF-8');
                                        ?>
                                        <button onclick='showPaymentDetails(<?php echo $paymentData; ?>)' 
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
        <div class="bg-white px-4 py-3 flex items-center justify-between border-t border-gray-200 sm:px-6 rounded-lg">
            <div class="flex-1 flex justify-between items-center">
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

        <!-- Payment Details Modal -->
        <div id="paymentDetailsModal" class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity hidden z-50 overflow-y-auto">
            <div class="flex min-h-screen items-center justify-center p-4">
                <div class="relative w-full max-w-2xl my-8">
                    <div class="relative bg-white rounded-xl shadow-2xl transform transition-all">
                        <!-- Modal Header -->
                        <div class="px-6 py-4 border-b border-gray-200 bg-gradient-to-r from-green-500 to-green-600 rounded-t-xl">
                            <h3 class="text-xl font-bold text-white">
                                Payment Details
                            </h3>
                        </div>

                        <!-- Modal Content -->
                        <div class="px-6 py-4">
                            <div class="space-y-4">
                                <!-- Reference Number -->
                                <div class="bg-gray-50 p-4 rounded-lg">
                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center">
                                            <i class="fas fa-hashtag text-green-500 mr-2"></i>
                                            <p class="text-sm font-medium text-gray-500">Reference Number</p>
                                        </div>
                                        <p id="modalReferenceNumber" class="text-base text-gray-900 font-semibold"></p>
                                    </div>
                                </div>

                                <!-- Payment Date -->
                                <div class="bg-gray-50 p-4 rounded-lg">
                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center">
                                            <i class="fas fa-calendar text-green-500 mr-2"></i>
                                            <p class="text-sm font-medium text-gray-500">Payment Date</p>
                                        </div>
                                        <p id="modalPaymentDate" class="text-base text-gray-900 font-semibold"></p>
                                    </div>
                                </div>

                                <!-- Amount -->
                                <div class="bg-gray-50 p-4 rounded-lg">
                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center">
                                            <i class="fas fa-money-bill text-green-500 mr-2"></i>
                                            <p class="text-sm font-medium text-gray-500">Amount</p>
                                        </div>
                                        <p id="modalPaymentAmount" class="text-xl text-green-600 font-bold"></p>
                                    </div>
                                </div>

                                <!-- Payment Method -->
                                <div class="bg-gray-50 p-4 rounded-lg">
                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center">
                                            <i class="fas fa-credit-card text-green-500 mr-2"></i>
                                            <p class="text-sm font-medium text-gray-500">Payment Method</p>
                                        </div>
                                        <p id="modalPaymentMethod" class="text-base text-gray-900 font-semibold"></p>
                                    </div>
                                </div>

                                <!-- Notes -->
                                <div class="bg-gray-50 p-4 rounded-lg">
                                    <div class="flex items-center mb-2">
                                        <i class="fas fa-sticky-note text-green-500 mr-2"></i>
                                        <p class="text-sm font-medium text-gray-500">Notes</p>
                                    </div>
                                    <p id="modalNotes" class="text-base text-gray-900"></p>
                                </div>
                            </div>
                        </div>

                        <!-- Modal Footer -->
                        <div class="px-6 py-4 bg-gray-50 border-t border-gray-200 rounded-b-xl">
                            <button onclick="closePaymentModal()" class="w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 sm:w-auto">
                                Close
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <script>
            function showPaymentDetails(payment) {
                try {
                    // Update modal content
                    document.getElementById('modalReferenceNumber').textContent = payment.reference_number || 'N/A';
                    document.getElementById('modalPaymentDate').textContent = payment.payment_date ? 
                        new Date(payment.payment_date).toLocaleString('en-MY', {
                            year: 'numeric',
                            month: 'long',
                            day: 'numeric',
                            hour: '2-digit',
                            minute: '2-digit',
                            hour12: true
                        }) : '';
                    document.getElementById('modalPaymentAmount').textContent = payment.amount ? 
                        'RM ' + parseFloat(payment.amount).toFixed(2) : '';
                    document.getElementById('modalPaymentMethod').textContent = payment.payment_method || 'N/A';
                    document.getElementById('modalNotes').textContent = payment.notes || 'No notes provided';

                    // Show modal
                    document.getElementById('paymentDetailsModal').classList.remove('hidden');
                    document.body.style.overflow = 'hidden';
                } catch (error) {
                    console.error('Error showing payment details:', error);
                }
            }

            function closePaymentModal() {
                document.getElementById('paymentDetailsModal').classList.add('hidden');
                document.body.style.overflow = 'auto';
            }

            // Close modal when clicking outside
            document.getElementById('paymentDetailsModal').addEventListener('click', function(e) {
                if (e.target === this) {
                    closePaymentModal();
                }
            });

            // Close modal on escape key
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape') {
                    closePaymentModal();
                }
            });
        </script>
    </main>

    <?php include 'template/member_footer.php'; ?>
</body>
</html>
