<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Function to check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Function to check if user is admin
function isAdmin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

// Function to require admin authentication
function requireAdmin() {
    if (!isLoggedIn() || !isAdmin()) {
        header('Location: login.php');
        exit();
    }
}
?>
