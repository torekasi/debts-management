<?php
require_once '../includes/config.php';
require_once '../includes/session.php';

// Require admin authentication
requireAdmin();

if (isset($_POST['user_id'])) {
    try {
        $dsn = sprintf("mysql:host=%s;port=%s;dbname=%s", DB_HOST, DB_PORT, DB_NAME);
        $pdo = new PDO($dsn, DB_USER, DB_PASS);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Check if user exists and is not an admin
        $stmt = $pdo->prepare("SELECT role FROM users WHERE id = ?");
        $stmt->execute([$_POST['user_id']]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && $user['role'] !== 'admin') {
            // Begin transaction
            $pdo->beginTransaction();

            // Delete related records first
            $stmt = $pdo->prepare("DELETE FROM payments WHERE user_id = ?");
            $stmt->execute([$_POST['user_id']]);

            $stmt = $pdo->prepare("DELETE FROM transactions WHERE user_id = ?");
            $stmt->execute([$_POST['user_id']]);

            // Finally delete the user
            $stmt = $pdo->prepare("DELETE FROM users WHERE id = ? AND role != 'admin'");
            $stmt->execute([$_POST['user_id']]);

            // Log the activity
            $stmt = $pdo->prepare("
                INSERT INTO activity_logs (user_id, action, description, ip_address) 
                VALUES (?, 'delete_user', ?, ?)
            ");
            $stmt->execute([$_SESSION['user_id'], "User deleted", $_SERVER['REMOTE_ADDR']]);

            // Commit transaction
            $pdo->commit();

            $_SESSION['success_message'] = "User has been deleted successfully.";
        }
    } catch (PDOException $e) {
        // Rollback transaction on error
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        $_SESSION['error_message'] = "Error deleting user: " . $e->getMessage();
    }
}

// Redirect back to users page
header('Location: users.php');
exit();
