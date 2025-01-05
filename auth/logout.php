<?php
require_once '../includes/config.php';
require_once '../includes/session.php';

session_start();

// Remove current user from active sessions
if (isset($_SESSION['user_id'])) {
    if (isset($_SESSION['active_sessions'])) {
        foreach ($_SESSION['active_sessions'] as $index => $session) {
            if ($session['user_id'] === $_SESSION['user_id']) {
                unset($_SESSION['active_sessions'][$index]);
                // Re-index the array
                $_SESSION['active_sessions'] = array_values($_SESSION['active_sessions']);
                break;
            }
        }
    }
}

// Clear current session data
unset($_SESSION['user_id']);
unset($_SESSION['member_id']);
unset($_SESSION['full_name']);
unset($_SESSION['role']);
unset($_SESSION['logged_in']);

// Redirect to login page
header("Location: /auth/login.php");
exit();
