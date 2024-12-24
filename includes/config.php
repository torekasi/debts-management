<?php
// Start session at the very beginning if not already started
if (session_status() === PHP_SESSION_NONE) {
    // Set session configuration before starting the session
    ini_set('session.gc_maxlifetime', 86400); // 24 hours
    ini_set('session.cookie_lifetime', 86400);
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_only_cookies', 1);
    if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
        ini_set('session.cookie_secure', 1);
    }
    session_start();
}


// DB_NAME: Your cPanel username followed by an underscore and the database name
define('DB_HOST', 'localhost'); // e.g., 'localhost' or your specific database host
define('DB_USER', 'your_cpanel_username_dbuser'); // e.g., 'cpaneluser_dbuser'
define('DB_PASS', 'your_db_password'); // e.g., 'yourpassword'
define('DB_NAME', 'your_cpanel_username_dbname'); // e.g., 'cpaneluser_dbname'
define('DB_PORT', '3306'); // Default MySQL port

// Establish database connection using PDO
try {
    $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=utf8mb4";
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
        PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT => false,
        PDO::MYSQL_ATTR_SSL_ENABLE => false
    ];
    $conn = new PDO($dsn, DB_USER, DB_PASS, $options);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// Application Configuration
define('APP_NAME', 'Mini Mart 3099');
define('APP_URL', 'http://localhost:8080'); // Change this to your domain
define('APP_VERSION', '1.0.0');

// Session Configuration
define('SESSION_LIFETIME', 86400); // 24 hours

// Upload Configuration
define('UPLOAD_MAX_SIZE', 10 * 1024 * 1024); // 10MB
define('ALLOWED_IMAGE_TYPES', ['image/jpeg', 'image/png', 'image/gif']);
define('UPLOAD_PATH', __DIR__ . '/../uploads');

// Notification Settings
define('PAYMENT_REMINDER_DAYS', 3); // Send reminder 3 days before due date
define('PAYMENT_OVERDUE_DAYS', 1); // Send overdue notice 1 day after due date

// Error Reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../logs/error.log');

// Create necessary directories
$directories = [
    __DIR__ . '/../uploads',
    __DIR__ . '/../logs'
];

foreach ($directories as $dir) {
    if (!file_exists($dir)) {
        mkdir($dir, 0777, true);
    }
}
