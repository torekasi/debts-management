<?php
require_once 'config/database.php';

try {
    // Test connection
    echo "Testing database connection...\n";
    $pdo->query("SELECT 1");
    echo "Database connection successful!\n\n";

    // Check if tables exist
    echo "Checking tables...\n";
    $tables = ['users', 'transactions', 'payments', 'notifications', 'activity_logs'];
    
    foreach ($tables as $table) {
        $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
        if ($stmt->rowCount() > 0) {
            echo "Table '$table' exists\n";
            // Check if users table has any records
            if ($table === 'users') {
                $stmt = $pdo->query("SELECT COUNT(*) as count FROM users");
                $count = $stmt->fetch()['count'];
                echo "Users table has $count records\n";
            }
        } else {
            echo "Table '$table' does not exist\n";
        }
    }

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
