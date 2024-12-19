<?php
// Application configuration
define('APP_NAME', 'Debt Manager');
define('APP_URL', 'http://localhost/debt-apps');
define('APP_VERSION', '1.0.0');

// Database configuration
define('DB_HOST', 'mysql-220bd3b5-nefizon.j.aivencloud.com');
define('DB_USER', 'avnadmin');
define('DB_PASS', 'AVNS_thBeU0OT2NZXwAVLoDM');
define('DB_NAME', 'defaultdb');
define('DB_PORT', '23853');

// Session configuration
define('SESSION_LIFETIME', 3600); // 1 hour
define('SESSION_NAME', 'debt_manager_session');

// Security configuration
define('HASH_COST', 10); // for password_hash()
define('AUTH_SALT', 'your_unique_salt_here');

// Error reporting
define('DISPLAY_ERRORS', true);
if (DISPLAY_ERRORS) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}

// Timezone
date_default_timezone_set('Asia/Manila');
?>
