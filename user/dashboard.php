<?php
require_once '../config/database.php';
require_once '../auth/auth_check.php';

checkAuth();

$user_id = $_SESSION['user_id'];

// Get user's total debt
$stmt = $conn->prepare("
    SELECT COALESCE(SUM(amount), 0) as total_debt 
    FROM transactions 
    WHERE user_id = ?
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$total_debt = $stmt->get_result()->fetch_assoc()['total_debt'];

// Get user's total payments
$stmt = $conn->prepare("
    SELECT COALESCE(SUM(amount), 0) as total_paid 
    FROM payments 
    WHERE user_id = ?
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$total_paid = $stmt->get_result()->fetch_assoc()['total_paid'];

// Calculate outstanding balance
$outstanding_balance = $total_debt - $total_paid;

// Get current month's spending
$stmt = $conn->prepare("
    SELECT COALESCE(SUM(amount), 0) as month_spending 
    FROM transactions 
    WHERE user_id = ? AND MONTH(created_at) = MONTH(CURRENT_DATE()) 
    AND YEAR(created_at) = YEAR(CURRENT_DATE())
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$month_spending = $stmt->get_result()->fetch_assoc()['month_spending'];

// Get transaction history
$stmt = $conn->prepare("
    SELECT * FROM transactions 
    WHERE user_id = ? 
    ORDER BY created_at DESC 
    LIMIT 10
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$transactions = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Dashboard - Debt Management System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/html5-qrcode"></script>
</head>
<body class="bg-gray-100">
    <div class="min-h-screen">
        <nav class="bg-indigo-600 p-4">
            <div class="max-w-7xl mx-auto flex justify-between items-center">
                <h1 class="text-white text-xl font-bold">User Dashboard</h1>
                <div class="flex items-center space-x-4">
                    <button onclick="openQRScanner()" class="text-white hover:text-indigo-100">Scan QR Code</button>
                    <a href="../auth/logout.php" class="text-white hover:text-indigo-100">Logout</a>
                </div>
            </div>
        </nav>

        <main class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
            <!-- Overview Cards -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                <div class="bg-white overflow-hidden shadow rounded-lg">
                    <div class="px-4 py-5 sm:p-6">
                        <dt class="text-sm font-medium text-gray-500 truncate">Outstanding Balance</dt>
                        <dd class="mt-1 text-3xl font-semibold text-gray-900">$<?php echo number_format($outstanding_balance, 2); ?></dd>
                    </div>
                </div>
                <div class="bg-white overflow-hidden shadow rounded-lg">
                    <div class="px-4 py-5 sm:p-6">
                        <dt class="text-sm font-medium text-gray-500 truncate">This Month's Spending</dt>
                        <dd class="mt-1 text-3xl font-semibold text-gray-900">$<?php echo number_format($month_spending, 2); ?></dd>
                    </div>
                </div>
                <div class="bg-white overflow-hidden shadow rounded-lg">
                    <div class="px-4 py-5 sm:p-6">
                        <dt class="text-sm font-medium text-gray-500 truncate">Total Paid</dt>
                        <dd class="mt-1 text-3xl font-semibold text-gray-900">$<?php echo number_format($total_paid, 2); ?></dd>
                    </div>
                </div>
            </div>

            <!-- QR Scanner Modal -->
            <div id="qrScannerModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full">
                <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
                    <div class="mt-3 text-center">
                        <h3 class="text-lg leading-6 font-medium text-gray-900">Scan QR Code</h3>
                        <div class="mt-2">
                            <div id="qr-reader" class="w-full"></div>
                        </div>
                        <div class="mt-4">
                            <button onclick="closeQRScanner()" class="px-4 py-2 bg-red-500 text-white rounded hover:bg-red-700">Close</button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Transaction History -->
            <div class="bg-white shadow rounded-lg">
                <div class="px-4 py-5 sm:p-6">
                    <h2 class="text-lg font-medium text-gray-900 mb-4">Recent Transactions</h2>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Description</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Image</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php foreach ($transactions as $transaction): ?>
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            <?php echo date('Y-m-d H:i', strtotime($transaction['created_at'])); ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            $<?php echo number_format($transaction['amount'], 2); ?>
                                        </td>
                                        <td class="px-6 py-4 text-sm text-gray-900">
                                            <?php echo htmlspecialchars($transaction['description']); ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            <?php if ($transaction['image_path']): ?>
                                                <a href="../<?php echo htmlspecialchars($transaction['image_path']); ?>" 
                                                   target="_blank" 
                                                   class="text-indigo-600 hover:text-indigo-900">
                                                    View Image
                                                </a>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script>
        function openQRScanner() {
            document.getElementById('qrScannerModal').classList.remove('hidden');
            const html5QrcodeScanner = new Html5QrcodeScanner(
                "qr-reader", { fps: 10, qrbox: 250 });
            
            html5QrcodeScanner.render((decodedText) => {
                // Handle the scanned code
                window.location.href = decodedText;
                html5QrcodeScanner.clear();
                closeQRScanner();
            });
        }

        function closeQRScanner() {
            document.getElementById('qrScannerModal').classList.add('hidden');
        }
    </script>
</body>
</html>
