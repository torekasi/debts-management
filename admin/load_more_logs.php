<?php
require_once '../includes/config.php';
require_once '../includes/session.php';
requireAdmin();

header('Content-Type: application/json');

$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$items_per_load = 20;
$offset = ($page - 1) * $items_per_load;

try {
    // Get total count of logs
    $stmt = $conn->query("SELECT COUNT(*) FROM activity_logs");
    $total_logs = $stmt->fetchColumn();

    // Get logs for current page
    $stmt = $conn->prepare("
        SELECT al.*, u.full_name, u.member_id
        FROM activity_logs al
        LEFT JOIN users u ON al.user_id = u.id
        ORDER BY al.created_at DESC
        LIMIT ? OFFSET ?
    ");
    $stmt->execute([$items_per_load, $offset]);
    $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Calculate if this is the last page
    $isLastPage = ($offset + $items_per_load) >= $total_logs;

    // Format dates for each log
    foreach ($logs as &$log) {
        $log['created_at'] = date('Y-m-d H:i:s', strtotime($log['created_at']));
        // Ensure all text is properly encoded for JSON
        $log['description'] = htmlspecialchars($log['description']);
        $log['action'] = htmlspecialchars($log['action']);
        $log['ip_address'] = htmlspecialchars($log['ip_address']);
    }

    echo json_encode([
        'logs' => $logs,
        'isLastPage' => $isLastPage,
        'currentPage' => $page,
        'totalLogs' => $total_logs
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Database error: ' . $e->getMessage()
    ]);
} 