<?php
require_once 'config/database.php';

try {
    // Check users table structure
    echo "Checking users table structure:\n";
    $stmt = $pdo->query("DESCRIBE users");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($columns as $column) {
        echo "- {$column['Field']}: {$column['Type']}\n";
    }
    
    // Check all users in the database
    echo "\nListing all users:\n";
    $stmt = $pdo->query("SELECT id, member_id, full_name, role, password FROM users");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($users as $user) {
        echo "\nUser ID: {$user['id']}\n";
        echo "Member ID: {$user['member_id']}\n";
        echo "Full Name: {$user['full_name']}\n";
        echo "Role: {$user['role']}\n";
        echo "Password Hash: " . substr($user['password'], 0, 20) . "...\n";
    }
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
