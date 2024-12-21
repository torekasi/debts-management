<?php
require_once '../includes/config.php';

if (!isset($_GET['user_id'])) {
    exit('User ID is required');
}

$user_id = $_GET['user_id'];

// Get recent transactions
$stmt = $conn->prepare("
    SELECT t.id, t.transaction_id, t.amount, t.description, t.image_path, t.created_at
    FROM transactions t
    WHERE t.user_id = ?
    ORDER BY t.created_at DESC
    LIMIT 10
");
$stmt->execute([$user_id]);
$recent_transactions = $stmt->fetchAll();
?>

<table class="min-w-full divide-y divide-gray-200">
    <thead class="bg-gray-50">
        <tr>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Transaction ID</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Details</th>
        </tr>
    </thead>
    <tbody class="bg-white divide-y divide-gray-200">
        <?php foreach ($recent_transactions as $transaction): ?>
            <tr>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                    <?php echo htmlspecialchars($transaction['transaction_id'] ?? ''); ?>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                    RM<?php echo number_format($transaction['amount'], 2); ?>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                    <?php echo date('d M Y | h:i A', strtotime($transaction['created_at'])); ?>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                    <button onclick="showNoteModal('<?php echo addslashes(htmlspecialchars($transaction['description'] ?? '')); ?>', '<?php echo addslashes(htmlspecialchars($transaction['image_path'] ?? '')); ?>')" 
                            class="text-blue-500 hover:text-blue-700">
                        View Details
                    </button>
                </td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>
