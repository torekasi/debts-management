<?php
class NotificationSystem {
    private $conn;
    
    public function __construct($conn) {
        $this->conn = $conn;
        
        // Create notifications table if not exists
        $sql = "CREATE TABLE IF NOT EXISTS notifications (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            title VARCHAR(255) NOT NULL,
            message TEXT NOT NULL,
            type ENUM('payment_due', 'payment_received', 'system', 'reminder') NOT NULL,
            is_read BOOLEAN DEFAULT FALSE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id)
        )";
        $this->conn->query($sql);
    }
    
    public function createNotification($user_id, $title, $message, $type) {
        $stmt = $this->conn->prepare("INSERT INTO notifications (user_id, title, message, type) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("isss", $user_id, $title, $message, $type);
        return $stmt->execute();
    }
    
    public function getUnreadNotifications($user_id) {
        $stmt = $this->conn->prepare("
            SELECT * FROM notifications 
            WHERE user_id = ? AND is_read = 0 
            ORDER BY created_at DESC
        ");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
    
    public function getAllNotifications($user_id, $limit = 20) {
        $stmt = $this->conn->prepare("
            SELECT * FROM notifications 
            WHERE user_id = ? 
            ORDER BY created_at DESC 
            LIMIT ?
        ");
        $stmt->bind_param("ii", $user_id, $limit);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
    
    public function markAsRead($notification_id, $user_id) {
        $stmt = $this->conn->prepare("
            UPDATE notifications 
            SET is_read = 1 
            WHERE id = ? AND user_id = ?
        ");
        $stmt->bind_param("ii", $notification_id, $user_id);
        return $stmt->execute();
    }
    
    public function markAllAsRead($user_id) {
        $stmt = $this->conn->prepare("
            UPDATE notifications 
            SET is_read = 1 
            WHERE user_id = ?
        ");
        $stmt->bind_param("i", $user_id);
        return $stmt->execute();
    }
    
    public function checkPaymentDue() {
        // Get users with outstanding balances
        $result = $this->conn->query("
            SELECT u.id, u.full_name, 
                   COALESCE(SUM(t.amount), 0) as total_debt,
                   COALESCE(SUM(p.amount), 0) as total_paid
            FROM users u
            LEFT JOIN transactions t ON u.id = t.user_id
            LEFT JOIN payments p ON u.id = p.user_id
            WHERE u.role = 'customer'
            GROUP BY u.id
            HAVING total_debt > total_paid
        ");
        
        while ($row = $result->fetch_assoc()) {
            $outstanding = $row['total_debt'] - $row['total_paid'];
            $this->createNotification(
                $row['id'],
                "Payment Reminder",
                "You have an outstanding balance of $" . number_format($outstanding, 2),
                "payment_due"
            );
        }
    }
}
?>
