<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit();
}

// Check if user has admin role for admin pages
if (strpos($_SERVER['REQUEST_URI'], '/admin/') !== false && $_SESSION['role'] !== 'admin') {
    header('Location: ../user/dashboard.php');
    exit();
}
?>
