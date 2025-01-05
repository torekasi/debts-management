<?php
require_once '../includes/config.php';

if (!isset($_GET['user_id'])) {
    exit('User ID is required');
}

$user_id = $_GET['user_id'];

// Get recent payments
$stmt = $conn->prepare("
    SELECT p.id, p.reference_number, p.amount, p.payment_method, p.payment_date, p.notes
    FROM payments p
    WHERE p.user_id = ?
    ORDER BY p.payment_date DESC
    LIMIT 10
");
$stmt->execute([$user_id]);
$recent_payments = $stmt->fetchAll();
?>

<table class="min-w-full divide-y divide-gray-200">
    <thead class="bg-gray-50">
        <tr>
            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Reference</th>
            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Method</th>
            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Notes</th>
        </tr>
    </thead>
    <tbody class="bg-white divide-y divide-gray-200">
        <?php foreach ($recent_payments as $payment): ?>
            <tr>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                    <?php echo htmlspecialchars($payment['reference_number']); ?>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                    RM<?php echo number_format($payment['amount'], 2); ?>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                    <?php echo htmlspecialchars($payment['payment_method']); ?>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                    <?php echo date('d/m/Y', strtotime($payment['payment_date'])); ?>
                </td>
                <td class="px-6 py-4 text-sm text-gray-500">
                    <?php echo htmlspecialchars($payment['notes']); ?>
                </td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>
