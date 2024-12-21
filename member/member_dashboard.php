<?php
$page_title = "Dashboard";
require_once '../includes/config.php';
require_once '../includes/session.php';
requireUser();

// Initialize database connection
try {
    $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=utf8mb4";
    $conn = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false
    ]);
} catch (PDOException $e) {
    error_log("Database connection error: " . $e->getMessage());
    die("Connection failed. Please try again later.");
}

// Get user's data
try {
    $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    // If user not found, redirect to login
    if (!$user) {
        $_SESSION['error'] = "User not found. Please login again.";
        header("Location: /auth/login.php");
        exit;
    }

    // Get user's total debt
    $stmt = $conn->prepare("SELECT COALESCE(SUM(amount), 0) as total_debt FROM debts WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $debt = $stmt->fetch(PDO::FETCH_ASSOC);
    $total_debt = $debt['total_debt'];

    // Get user's total payments
    $stmt = $conn->prepare("SELECT COALESCE(SUM(amount), 0) as total_paid FROM payments WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $payment = $stmt->fetch(PDO::FETCH_ASSOC);
    $total_paid = $payment['total_paid'];

    // Calculate remaining debt
    $remaining_debt = $total_debt - $total_paid;

    // Get recent payments
    $stmt = $conn->prepare("
        SELECT p.*, u.full_name 
        FROM payments p 
        LEFT JOIN users u ON p.user_id = u.id 
        WHERE p.user_id = ? 
        ORDER BY p.payment_date DESC 
        LIMIT 5
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $recent_payments = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    $_SESSION['error'] = "An error occurred while fetching your data. Please try again later.";
    $user = null;
    $total_debt = 0;
    $total_paid = 0;
    $remaining_debt = 0;
    $recent_payments = [];
}

// Start output buffering to capture the content
ob_start();

// Display any error messages
if (isset($_SESSION['error'])) {
    echo '<div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">';
    echo '<span class="block sm:inline">' . htmlspecialchars($_SESSION['error']) . '</span>';
    echo '</div>';
    unset($_SESSION['error']);
}
?>

<!-- Dashboard Content -->
<div class="bg-gray-50 min-h-screen pb-20">
    <!-- Welcome Section -->
    <div class="bg-white shadow-sm">
        <div class="p-4">
            <h1 class="text-xl font-bold text-gray-900">
                Welcome <?php echo $user ? htmlspecialchars($user['full_name']) : ''; ?>!
            </h1>
            <?php if ($user): ?>
            <p class="mt-1 text-sm text-gray-500">
                Member ID: <?php echo htmlspecialchars($user['member_id']); ?>
            </p>
            <?php endif; ?>
        </div>
    </div>

    <!-- Stats Grid -->
    <div class="p-4">
        <!-- Debt and Payment Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
            <!-- Total Debt -->
            <div class="bg-red-500 shadow-sm rounded-lg text-white">
                <div class="p-6">
                    <dl>
                        <dt class="text-sm font-medium text-white/90 truncate">Total Debt</dt>
                        <dd class="text-2xl font-bold mt-2">RM <?php echo number_format($total_debt, 2); ?></dd>
                    </dl>
                </div>
            </div>

            <!-- Total Paid -->
            <div class="bg-green-500 shadow-sm rounded-lg text-white">
                <div class="p-6">
                    <dl>
                        <dt class="text-sm font-medium text-white/90 truncate">Total Paid</dt>
                        <dd class="text-2xl font-bold mt-2">RM <?php echo number_format($total_paid, 2); ?></dd>
                    </dl>
                </div>
            </div>
        </div>

        <!-- Remaining Debt -->
        <div class="bg-white shadow-sm rounded-lg">
            <div class="p-4">
                <div class="flex items-center">
                    <div class="flex-shrink-0 rounded-md bg-blue-500 p-3">
                        <svg class="h-6 w-6 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                        </svg>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 truncate">Remaining Debt</dt>
                            <dd class="text-lg font-semibold text-gray-900">RM <?php echo number_format($remaining_debt, 2); ?></dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Payments -->
    <div class="bg-white shadow-sm mt-4">
        <div class="p-4 border-b border-gray-200">
            <h2 class="text-lg font-medium text-gray-900">Recent Payments</h2>
        </div>
        <?php if (empty($recent_payments)): ?>
            <div class="text-center py-4">
                <p class="text-gray-500">No recent payments found.</p>
            </div>
        <?php else: ?>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Amount</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($recent_payments as $payment): ?>
                            <tr>
                                <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-900">
                                    <?php echo date('M d, Y', strtotime($payment['payment_date'])); ?>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-900">
                                    RM <?php echo number_format($payment['amount'], 2); ?>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                        <?php echo ucfirst($payment['status']); ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

    <!-- Payment Progress -->
    <?php if ($total_debt > 0): ?>
    <div class="bg-white shadow-sm mt-4">
        <div class="p-4">
            <h2 class="text-lg font-medium text-gray-900 mb-4">Payment Progress</h2>
            <div class="w-full h-64">
                <canvas id="paymentChart"></canvas>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php if ($total_debt > 0): ?>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    // Payment progress chart
    const ctx = document.getElementById('paymentChart').getContext('2d');
    new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: ['Paid', 'Remaining'],
            datasets: [{
                data: [<?php echo $total_paid; ?>, <?php echo $remaining_debt; ?>],
                backgroundColor: [
                    'rgb(34, 197, 94)',
                    'rgb(234, 179, 8)'
                ],
                borderWidth: 0
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom'
                }
            },
            cutout: '70%'
        }
    });
</script>
<?php endif; ?>

<?php
$content = ob_get_clean();
require_once 'template/member_header.php';
?>