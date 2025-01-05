<?php
require_once '../includes/config.php';
require_once '../includes/session.php';
requireAdmin();

header('Content-Type: application/json');

try {
    $user_id = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0;
    $type = isset($_GET['type']) ? $_GET['type'] : '';
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $items_per_page = 10;
    $offset = ($page - 1) * $items_per_page;

    if (!$user_id || !in_array($type, ['payments', 'transactions'])) {
        throw new Exception('Invalid parameters');
    }

    if ($type === 'payments') {
        // Get total payments count
        $stmt = $conn->prepare("SELECT COUNT(*) FROM payments WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $total_items = $stmt->fetchColumn();
        
        // Fetch payments
        $stmt = $conn->prepare("
            SELECT p.*, u.full_name as user_name 
            FROM payments p 
            LEFT JOIN users u ON p.user_id = u.id 
            WHERE p.user_id = ?
            ORDER BY p.created_at DESC 
            LIMIT ? OFFSET ?
        ");
        $stmt->execute([$user_id, $items_per_page, $offset]);
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Format the payments data
        $html = '';
        foreach ($items as $payment) {
            $html .= '<tr>';
            $html .= '<td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">' . date('Y-m-d H:i', strtotime($payment['payment_date'])) . '</td>';
            $html .= '<td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">' . htmlspecialchars($payment['reference_number']) . '</td>';
            $html .= '<td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">RM ' . number_format($payment['amount'], 2) . '</td>';
            $html .= '<td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">' . htmlspecialchars($payment['description']) . '</td>';
            $html .= '</tr>';
        }
    } else {
        // Get total transactions count
        $stmt = $conn->prepare("SELECT COUNT(*) FROM transactions WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $total_items = $stmt->fetchColumn();
        
        // Fetch transactions
        $stmt = $conn->prepare("
            SELECT t.id, t.transaction_id, t.amount, t.description, t.created_at, t.type, t.image_path
            FROM transactions t
            WHERE t.user_id = ?
            ORDER BY t.created_at DESC
            LIMIT ? OFFSET ?
        ");
        $stmt->execute([$user_id, $items_per_page, $offset]);
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Format the transactions data
        $html = '';
        foreach ($items as $transaction) {
            $html .= '<tr class="transaction-row-' . $transaction['id'] . '">';
            $html .= '<td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">' . date('Y-m-d H:i', strtotime($transaction['created_at'])) . '</td>';
            $html .= '<td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">' . htmlspecialchars($transaction['type']) . '</td>';
            $html .= '<td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">RM ' . number_format($transaction['amount'], 2) . '</td>';
            $html .= '<td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">' . htmlspecialchars($transaction['description']) . '</td>';
            $html .= '<td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">';
            $html .= '<button type="button" class="text-indigo-600 hover:text-indigo-900 transaction-details-btn" ';
            $html .= 'data-transaction=\'' . json_encode($transaction) . '\'>View Details</button>';
            $html .= '</td>';
            $html .= '</tr>';
        }
    }

    $total_pages = ceil($total_items / $items_per_page);

    echo json_encode([
        'success' => true,
        'html' => $html,
        'totalPages' => $total_pages,
        'currentPage' => $page
    ]);

} catch (Exception $e) {
    error_log("Error in get_paginated_data.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => "An error occurred while fetching data"
    ]);
}
