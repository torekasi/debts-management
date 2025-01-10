<?php
// Application configuration
define('APP_NAME', 'Mini Mart 3099');
define('APP_VERSION', '1.0.0');

// Start session at the very beginning if not already started
if (session_status() === PHP_SESSION_NONE) {
    // Set session configuration before starting the session
    ini_set('session.gc_maxlifetime', 86400); // 24 hours
    ini_set('session.cookie_lifetime', 86400);
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_only_cookies', 1);
    session_start();
}

// Enable error logging
ini_set('log_errors', 1);
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('error_log', __DIR__ . '/../logs/error.log');

// Database configuration
define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
define('DB_USER', getenv('DB_USERNAME') ?: 'rc126893_mini3099');
define('DB_PASS', getenv('DB_PASSWORD') ?: 'Malaysia@2413');
define('DB_NAME', getenv('DB_DATABASE') ?: 'rc126893_mini3099');
define('DB_PORT', getenv('DB_PORT') ?: '3306');

// Create PDO connection
try {
    $dsn = sprintf("mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4", DB_HOST, DB_PORT, DB_NAME);
    $conn = new PDO($dsn, DB_USER, DB_PASS);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $conn->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
} catch (PDOException $e) {
    error_log("Database connection failed: " . $e->getMessage());
    die("Database connection failed. Please try again later.");
}

// Common functions
function sanitize_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

function format_date($date) {
    return date('Y-m-d H:i:s', strtotime($date));
}

function format_currency($amount) {
    return number_format($amount, 2, '.', ',');
}

// Error handling function
function handle_error($error_message) {
    error_log($error_message);
    return "An error occurred. Please try again later.";
}

// Success message function
function success_message($message) {
    return "<div class='alert alert-success'>" . htmlspecialchars($message) . "</div>";
}

// Error message function
function error_message($message) {
    return "<div class='alert alert-danger'>" . htmlspecialchars($message) . "</div>";
}
?>
