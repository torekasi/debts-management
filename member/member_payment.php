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
    <title>Payment History - Member Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="bg-gray-100">
    <?php include 'template/member_header.php'; ?>

    <main class="content-container p-4 md:p-6" style="margin: 80px 0;">
        <!-- Summary Cards -->
        <div class="grid grid-cols-2 gap-4 mb-8">
            <!-- Total Payments Card -->
            <div class="bg-white rounded-lg shadow-sm p-4 md:p-6">
                <div class="flex items-center">
                    <div class="p-2 md:p-3 rounded-full bg-green-100 text-green-500">
                        <i class="fas fa-money-bill-wave text-lg md:text-2xl"></i>
                    </div>
                    <div class="ml-3 md:ml-4">
                        <p class="text-xs md:text-sm font-medium text-gray-500">Total Payments</p>
                        <?php
                        $stmt = $conn->prepare("SELECT SUM(amount) FROM payments WHERE user_id = ?");
                        $stmt->execute([$_SESSION['user_id']]);
                        $totalAmount = $stmt->fetchColumn() ?? 0;
                        ?>
                        <h3 class="text-base md:text-2xl font-bold text-gray-900">RM<?php echo number_format($totalAmount, 2); ?></h3>
                    </div>
                </div>
            </div>

            <!-- Current Month Payments Card -->
            <div class="bg-white rounded-lg shadow-sm p-4 md:p-6">
                <div class="flex items-center">
                    <div class="p-2 md:p-3 rounded-full bg-blue-100 text-blue-500">
                        <i class="fas fa-calendar text-lg md:text-2xl"></i>
                    </div>
                    <div class="ml-3 md:ml-4">
                        <p class="text-xs md:text-sm font-medium text-gray-500">This Month Payment</p>
                        <?php
                        $stmt = $conn->prepare("
                            SELECT SUM(amount) 
                            FROM payments 
                            WHERE user_id = ? 
                            AND MONTH(payment_date) = MONTH(CURRENT_DATE())
                            AND YEAR(payment_date) = YEAR(CURRENT_DATE())
                        ");
                        $stmt->execute([$_SESSION['user_id']]);
                        $monthlyAmount = $stmt->fetchColumn() ?? 0;
                        ?>
                        <h3 class="text-base md:text-2xl font-bold text-gray-900">RM<?php echo number_format($monthlyAmount, 2); ?></h3>
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
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <!-- Left Column -->
                                <div class="space-y-4">
                                    <div class="bg-gray-50 p-4 rounded-lg">
                                        <div class="flex items-center mb-3">
                                            <i class="fas fa-hashtag text-green-500 mr-2"></i>
                                            <p class="text-sm font-medium text-gray-500">Reference Number</p>
                                        </div>
                                        <p id="modalReferenceNumber" class="text-base text-gray-900 font-semibold"></p>
                                    </div>

                                    <div class="bg-gray-50 p-4 rounded-lg">
                                        <div class="flex items-center mb-3">
                                            <i class="fas fa-calendar text-green-500 mr-2"></i>
                                            <p class="text-sm font-medium text-gray-500">Payment Date</p>
                                        </div>
                                        <p id="modalPaymentDate" class="text-base text-gray-900 font-semibold"></p>
                                    </div>
                                </div>

                                <!-- Right Column -->
                                <div class="space-y-4">
                                    <div class="bg-gray-50 p-4 rounded-lg">
                                        <div class="flex items-center mb-3">
                                            <i class="fas fa-money-bill text-green-500 mr-2"></i>
                                            <p class="text-sm font-medium text-gray-500">Amount</p>
                                        </div>
                                        <p id="modalPaymentAmount" class="text-xl text-green-600 font-bold"></p>
                                    </div>

                                    <div class="bg-gray-50 p-4 rounded-lg">
                                        <div class="flex items-center mb-3">
                                            <i class="fas fa-credit-card text-green-500 mr-2"></i>
                                            <p class="text-sm font-medium text-gray-500">Payment Method</p>
                                        </div>
                                        <p id="modalPaymentMethod" class="text-base text-gray-900"></p>
                                    </div>
                                </div>

                                <!-- Notes Section -->
                                <div class="col-span-2">
                                    <div class="bg-gray-50 p-4 rounded-lg">
                                        <div class="flex items-center mb-3">
                                            <i class="fas fa-sticky-note text-green-500 mr-2"></i>
                                            <p class="text-sm font-medium text-gray-500">Notes</p>
                                        </div>
                                        <p id="modalPaymentNotes" class="text-base text-gray-900"></p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Modal Footer -->
                        <div class="px-6 py-4 bg-gray-50 rounded-b-xl">
                            <button type="button" onclick="hidePaymentModal()" 
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
                    document.getElementById('modalPaymentNotes').textContent = payment.notes || 'No notes provided';

                    // Show modal
                    document.getElementById('paymentDetailsModal').classList.remove('hidden');
                    document.body.style.overflow = 'hidden';
                } catch (error) {
                    console.error('Error showing payment details:', error);
                }
            }

            function hidePaymentModal() {
                document.getElementById('paymentDetailsModal').classList.add('hidden');
                document.body.style.overflow = 'auto';
            }

            // Close modal when clicking outside
            document.getElementById('paymentDetailsModal').addEventListener('click', function(e) {
                if (e.target === this) {
                    hidePaymentModal();
                }
            });

            // Close modal on escape key
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape') {
                    hidePaymentModal();
                }
            });
        </script>
    </main>

    <?php include 'template/member_footer.php'; ?>
</body>
</html>
