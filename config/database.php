<?php
require_once __DIR__ . '/config.php';

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Database configuration
$db_config = [
    'host' => DB_HOST,
    'dbname' => DB_NAME,
    'username' => DB_USER,
    'password' => DB_PASS,
    'charset' => 'utf8mb4',
    'port' => 23853  // Updated port number
];

try {
    // Log connection attempt
    error_log("Connecting to MySQL at {$db_config['host']}:{$db_config['port']} as {$db_config['username']}");
    
    $dsn = "mysql:host={$db_config['host']};port={$db_config['port']};dbname={$db_config['dbname']};charset={$db_config['charset']}";
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
        PDO::MYSQL_ATTR_SSL_CA => __DIR__ . '/ca.pem',
        PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT => false
    ];
    
    // Now connect with database
    $pdo = new PDO($dsn, $db_config['username'], $db_config['password'], $options);
    error_log("Full database connection successful");
    
} catch (PDOException $e) {
    $error_message = "Database Connection Error: " . $e->getMessage() . "\n";
    $error_message .= "DSN: " . $dsn . "\n";
    error_log($error_message);
    die($error_message);
}

// Create database if not exists
$sql = "CREATE DATABASE IF NOT EXISTS " . $db_config['dbname'];
$pdo->exec($sql);

// Create users table
$sql = "CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    member_id VARCHAR(50) UNIQUE NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'customer') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";
$pdo->exec($sql);

// Drop and recreate transactions table
$pdo->exec("DROP TABLE IF EXISTS transactions");

// Create transactions table
$sql = "CREATE TABLE IF NOT EXISTS transactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    type ENUM('loan', 'payment') NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    payment_method VARCHAR(50),
    notes TEXT,
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'approved',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
)";
$pdo->exec($sql);

// Create payments table
$sql = "CREATE TABLE IF NOT EXISTS payments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    payment_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
)";
$pdo->exec($sql);
?>
