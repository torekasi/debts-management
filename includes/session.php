<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Function to check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

// Function to require user login
function requireUser() {
    if (!isLoggedIn()) {
        $_SESSION['error'] = "Please log in to access this page.";
        header('Location: /auth/login.php');
        exit();
    }
    
    // If user is admin, redirect to admin dashboard
    if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin') {
        header('Location: /admin/dashboard.php');
        exit();
    }
}

// Function to check if user is admin
function isAdmin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

// Function to require admin login
function requireAdmin() {
    if (!isLoggedIn()) {
        $_SESSION['error'] = "Please log in to access this page.";
        header('Location: /auth/login.php');
        exit();
    }
    
    if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
        $_SESSION['error'] = "You don't have permission to access this page.";
        header('Location: /user/member_dashboard.php');
        exit();
    }
}

// Function to check session and redirect
function checkSessionAndRedirect() {
    if (isLoggedIn()) {
        if ($_SESSION['role'] === 'admin') {
            header('Location: /admin/dashboard.php');
        } else {
            header('Location: /user/member_dashboard.php');
        }
        exit();
    }
}
?>
