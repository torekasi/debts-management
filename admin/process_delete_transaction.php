<?php
require_once '../includes/config.php';
require_once '../includes/session.php';
requireAdmin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('HTTP/1.1 405 Method Not Allowed');
    exit('Method not allowed');
}

$transaction_id = isset($_POST['transaction_id']) ? (int)$_POST['transaction_id'] : 0;
$password = isset($_POST['password']) ? $_POST['password'] : '';

try {
    // Verify admin password and get admin details
    $stmt = $conn->prepare("SELECT id, password, full_name FROM users WHERE id = ? AND role = 'admin'");
    $stmt->execute([$_SESSION['user_id']]);
    $admin = $stmt->fetch();

    if (!$admin || !password_verify($password, $admin['password'])) {
        $_SESSION['error'] = "Invalid password. Action cancelled.";
        header("Location: " . $_SERVER['HTTP_REFERER']);
        exit;
    }

    // Get transaction details before deletion for logging
    $stmt = $conn->prepare("
        SELECT t.*, u.full_name, u.member_id
        FROM transactions t 
        JOIN users u ON t.user_id = u.id 
        WHERE t.id = ?
    ");
    $stmt->execute([$transaction_id]);
    $transaction = $stmt->fetch();

    if (!$transaction) {
        $_SESSION['error'] = "Transaction not found.";
        header("Location: " . $_SERVER['HTTP_REFERER']);
        exit;
    }

    // Start transaction
    $conn->beginTransaction();

    try {
        // Delete the transaction
        $stmt = $conn->prepare("DELETE FROM transactions WHERE id = ?");
        if (!$stmt->execute([$transaction_id])) {
            throw new Exception("Failed to delete transaction");
        }

        if ($stmt->rowCount() === 0) {
            throw new Exception("No transaction was deleted");
        }

        // Log the activity with admin details in description
        $log_description = sprintf(
            "[Action by Admin: %s (ID: %d)] Deleted transaction (ID: %d) for member %s (ID: %s). Transaction Amount: RM %.2f",
            $admin['full_name'],
            $admin['id'],
            $transaction_id,
            $transaction['full_name'],
            $transaction['member_id'],
            $transaction['amount']
        );

        $stmt = $conn->prepare("
            INSERT INTO activity_logs (user_id, action, description, ip_address) 
            VALUES (?, 'DELETE_TRANSACTION', ?, ?)
        ");
        if (!$stmt->execute([
            $transaction['user_id'], // The user whose transaction was deleted
            $log_description,
            $_SERVER['REMOTE_ADDR']
        ])) {
            throw new Exception("Failed to log activity");
        }

        // Commit transaction
        $conn->commit();
        $_SESSION['success'] = "Transaction deleted successfully.";

    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollBack();
        $_SESSION['error'] = "Error: " . $e->getMessage();
    }

} catch (PDOException $e) {
    // Handle any PDO errors
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    $_SESSION['error'] = "Database error: " . $e->getMessage();
}

// Redirect back to the previous page
header("Location: " . $_SERVER['HTTP_REFERER']);
exit; 