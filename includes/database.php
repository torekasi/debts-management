<?php
require_once 'config.php';

try {
    // First check if we can connect without specifying the database
    try {
        $testDsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT;
        $testPdo = new PDO($testDsn, DB_USER, DB_PASS);
        
        // If we get here, the connection was successful
        // Now check if the database exists
        $query = "SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = '" . DB_NAME . "'";
        $stmt = $testPdo->query($query);
        
        if (!$stmt->fetch()) {
            // Database doesn't exist, create it
            $testPdo->exec("CREATE DATABASE IF NOT EXISTS `" . DB_NAME . "` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        }
        
        // Close the test connection
        $testPdo = null;
    } catch (PDOException $e) {
        die("Connection failed: " . $e->getMessage() . "<br>Please check your MySQL username and password.");
    }
    
    // Now connect to the specific database
    $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=utf8mb4";
    $pdo = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false
    ]);
    
    // Create tables if they don't exist
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            member_id VARCHAR(50) UNIQUE,
            full_name VARCHAR(100) NOT NULL,
            email VARCHAR(100) UNIQUE,
            password VARCHAR(255) NOT NULL,
            role ENUM('admin', 'user') DEFAULT 'user',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        
        CREATE TABLE IF NOT EXISTS transactions (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            type ENUM('loan', 'payment') NOT NULL,
            amount DECIMAL(10,2) NOT NULL,
            status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
            notes TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");
    
    // Check if admin user exists, if not create one
    $stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'admin'");
    if ($stmt->fetchColumn() == 0) {
        $adminPassword = password_hash('admin123', PASSWORD_DEFAULT);
        $pdo->exec("
            INSERT INTO users (member_id, full_name, email, password, role)
            VALUES ('ADMIN001', 'System Administrator', 'admin@example.com', '$adminPassword', 'admin')
        ");
    }
    
} catch (PDOException $e) {
    if (DISPLAY_ERRORS) {
        echo "Database error: " . $e->getMessage() . "<br>";
    }
    error_log("Database error: " . $e->getMessage());
    die("Could not connect to or set up the database. Please check your configuration.");
}
?>
