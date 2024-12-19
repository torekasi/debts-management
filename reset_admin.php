<?php
require_once 'config/database.php';

try {
    // Hash the password 'admin123'
    $password = 'admin123';
    $hash = password_hash($password, PASSWORD_DEFAULT);
    
    // Update the admin user's password
    $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE member_id = 'ADMIN001'");
    $result = $stmt->execute([$hash]);
    
    if ($result) {
        echo "Admin password has been reset successfully.\n";
        echo "Member ID: ADMIN001\n";
        echo "Password: admin123\n";
        
        // Verify the password was saved correctly
        $stmt = $pdo->prepare("SELECT password FROM users WHERE member_id = 'ADMIN001'");
        $stmt->execute();
        $user = $stmt->fetch();
        
        if ($user && password_verify('admin123', $user['password'])) {
            echo "Password verification successful!\n";
        } else {
            echo "Warning: Password verification failed!\n";
        }
    } else {
        echo "Failed to reset admin password.\n";
    }
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
