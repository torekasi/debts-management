<?php
require_once '../../includes/config.php';
require_once '../../includes/session.php';

// Require admin authentication
requireAdmin();

header('Content-Type: application/json');

$user_id = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$type = isset($_GET['type']) ? $_GET['type'] : 'transactions';
$items_per_page = 7;
$offset = ($page - 1) * $items_per_page;

try {
    if ($type === 'transactions') {
        // Get total count
        $count_sql = "SELECT COUNT(*) as total FROM transactions WHERE user_id = ?";
        $count_stmt = $conn->prepare($count_sql);
        $count_stmt->execute([$user_id]);
        $total_items = $count_stmt->fetch()['total'];
        
        // Get paginated transactions
        $sql = "
            SELECT 
                id,
                amount,
                description,
                DATE_FORMAT(date_transaction, '%Y-%m-%d') as date
            FROM transactions 
            WHERE user_id = ?
            ORDER BY date_transaction DESC
            LIMIT ? OFFSET ?";
        
        $stmt = $conn->prepare($sql);
        $stmt->execute([$user_id, $items_per_page, $offset]);
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
    } else {
        // Get total count
        $count_sql = "SELECT COUNT(*) as total FROM payments WHERE user_id = ?";
        $count_stmt = $conn->prepare($count_sql);
        $count_stmt->execute([$user_id]);
        $total_items = $count_stmt->fetch()['total'];
        
        // Get paginated payments
        $sql = "
            SELECT 
                id,
                amount,
                payment_method,
                reference_number,
                DATE_FORMAT(payment_date, '%Y-%m-%d') as date
            FROM payments 
            WHERE user_id = ?
            ORDER BY payment_date DESC
            LIMIT ? OFFSET ?";
        
        $stmt = $conn->prepare($sql);
        $stmt->execute([$user_id, $items_per_page, $offset]);
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    $total_pages = ceil($total_items / $items_per_page);

    echo json_encode([
        'success' => true,
        'data' => $items,
        'pagination' => [
            'current_page' => $page,
            'total_pages' => $total_pages,
            'items_per_page' => $items_per_page,
            'total_items' => $total_items
        ]
    ]);

} catch (PDOException $e) {
    error_log("Error in get_user_transactions.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => 'Database error occurred'
    ]);
}
?>
