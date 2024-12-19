<?php
require_once 'config/database.php';

try {
    echo "Testing database connection...\n";
    
    // Test basic connection
    $pdo->query("SELECT 1");
    echo "✓ Basic connection successful!\n\n";
    
    // Get server info
    echo "Server Info:\n";
    echo "- Version: " . $pdo->getAttribute(PDO::ATTR_SERVER_VERSION) . "\n";
    echo "- Connection: " . $pdo->getAttribute(PDO::ATTR_CONNECTION_STATUS) . "\n\n";
    
    // Check for required tables
    echo "Checking tables:\n";
    $required_tables = ['users', 'transactions', 'payments', 'notifications', 'activity_logs'];
    
    foreach ($required_tables as $table) {
        $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
        if ($stmt->rowCount() > 0) {
            echo "✓ Table '$table' exists\n";
            
            // For users table, check if we have the admin user
            if ($table === 'users') {
                $stmt = $pdo->query("SELECT COUNT(*) as count FROM users WHERE role = 'admin'");
                $count = $stmt->fetch()['count'];
                echo "  - Found $count admin users\n";
            }
        } else {
            echo "✗ Table '$table' does not exist\n";
        }
    }
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "DSN: " . preg_replace('/password=([^;]*)/', 'password=***', $dsn) . "\n";
}
