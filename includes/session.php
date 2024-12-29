<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function requireAdmin() {
    if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
        header("Location: ../auth/login.php");
        exit;
    }
    
    // Set default admin name if not set
    if (!isset($_SESSION['admin_name'])) {
        $_SESSION['admin_name'] = 'Admin';
    }
}

function isAdmin() {
    return isset($_SESSION['user_id']) && $_SESSION['role'] === 'admin';
}
?>
