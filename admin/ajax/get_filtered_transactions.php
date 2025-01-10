<?php
require_once '../../includes/config.php';
require_once '../../includes/session.php';
requireAdmin();

header('Content-Type: application/json');

try {
    if (!defined('DB_HOST')) {
        throw new Exception('Database configuration not loaded properly');
    }
    
    // Database connection
    $dsn = sprintf(
        "mysql:host=%s;port=%s;dbname=%s",
        DB_HOST,
        DB_PORT,
        DB_NAME
    );
    
    $pdo = new PDO($dsn, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $start_date = $_GET['start_date'] ?? date('Y-m-d H:i:s', strtotime('today midnight'));
    $end_date = $_GET['end_date'] ?? date('Y-m-d H:i:s', strtotime('tomorrow midnight') - 1);
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $items_per_page = 10;
    $offset = ($page - 1) * $items_per_page;

    // Set timezone to match your application's timezone
    date_default_timezone_set('Asia/Kuala_Lumpur');

    // Use a single query to get both count and data with LIMIT
    $sql = "
        SELECT 
            SQL_CALC_FOUND_ROWS
            t.id, 
            t.user_id,
            t.type,
            t.amount, 
            t.description, 
            t.date_transaction,
            t.image_path,
            u.full_name,
            DATE_FORMAT(t.date_transaction, '%d %b %Y | %h:%i %p') as formatted_date
        FROM transactions t
        JOIN users u ON t.user_id = u.id
        WHERE DATE(t.date_transaction) BETWEEN :start_date AND :end_date
        AND (t.type = 'expense' OR t.type = 'income')
        ORDER BY t.date_transaction DESC
        LIMIT :offset, :limit
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':start_date', $start_date);
    $stmt->bindValue(':end_date', $end_date);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->bindValue(':limit', $items_per_page, PDO::PARAM_INT);
    $stmt->execute();
    
    $transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get total count
    $stmt = $pdo->query("SELECT FOUND_ROWS()");
    $total_items = $stmt->fetchColumn();
    $total_pages = ceil($total_items / $items_per_page);

    // Generate HTML for transactions
    $html = '';
    if (empty($transactions)) {
        $html .= '<tr><td colspan="5" class="px-6 py-4 text-center text-sm text-gray-500">No transactions found for the selected date range.</td></tr>';
    } else {
        foreach ($transactions as $transaction) {
            $html .= '<tr class="hover:bg-gray-50">';
            
            // User column
            $html .= '<td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">';
            $html .= htmlspecialchars($transaction['full_name'] ?? 'N/A');
            $html .= '</td>';
            
            // Amount column with type indicator
            $html .= '<td class="px-6 py-4 whitespace-nowrap text-sm">';
            $amountClass = $transaction['type'] === 'expense' ? 'text-red-600' : 'text-green-600';
            $amountPrefix = $transaction['type'] === 'expense' ? '-' : '+';
            $html .= '<span class="' . $amountClass . ' font-medium">' . $amountPrefix . ' RM ' . number_format($transaction['amount'] ?? 0, 2) . '</span>';
            $html .= '</td>';
            
            // Description column
            $html .= '<td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">';
            $html .= htmlspecialchars($transaction['description'] ?? 'No description');
            $html .= '</td>';
            
            // Date column
            $html .= '<td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">';
            $html .= htmlspecialchars($transaction['formatted_date']);
            $html .= '</td>';
            
            // Actions column
            $html .= '<td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">';
            if ($transaction['image_path']) {
                $html .= '<button onclick="viewImage(\'' . htmlspecialchars($transaction['image_path']) . '\')" class="text-indigo-600 hover:text-indigo-900">View Receipt</button>';
            }
            $html .= '</td>';
            $html .= '</tr>';
        }
    }

    // Pagination logic
    $pagination = '';
    if ($total_pages > 1) {
        $pagination .= '<nav class="px-4 py-3 flex items-center justify-between border-t border-gray-200 sm:px-6">';
        $pagination .= '<div class="flex-1 flex justify-between sm:justify-end">';
        
        if ($page > 1) {
            $pagination .= '<a href="?page=' . ($page - 1) . '" class="text-sm text-gray-700 hover:text-gray-900">Previous</a>';
        }
        
        if ($page < $total_pages) {
            $pagination .= '<a href="?page=' . ($page + 1) . '" class="text-sm text-gray-700 hover:text-gray-900">Next</a>';
        }
        
        $pagination .= '</div>';
        $pagination .= '</nav>';
    }

    echo json_encode([
        'success' => true,
        'html' => $html,
        'pagination' => $pagination,
        'total_items' => $total_items,
        'current_page' => $page,
        'total_pages' => $total_pages
    ]);

} catch (Exception $e) {
    error_log("Error in get_filtered_transactions.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}