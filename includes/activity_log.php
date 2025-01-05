<?php
class ActivityLogger {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
        
        // Create activity_logs table if not exists
        $sql = "CREATE TABLE IF NOT EXISTS activity_logs (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT,
            action VARCHAR(255) NOT NULL,
            description TEXT,
            ip_address VARCHAR(45),
            user_agent TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id)
        )";
        $this->pdo->exec($sql);
    }
    
    public function log($user_id, $action, $description = '', $ip_address = null, $user_agent = null) {
        $ip_address = $ip_address ?? $_SERVER['REMOTE_ADDR'] ?? null;
        $user_agent = $user_agent ?? $_SERVER['HTTP_USER_AGENT'] ?? null;
        
        $stmt = $this->pdo->prepare("
            INSERT INTO activity_logs (user_id, action, description, ip_address, user_agent)
            VALUES (?, ?, ?, ?, ?)
        ");
        return $stmt->execute([$user_id, $action, $description, $ip_address, $user_agent]);
    }
    
    public function getUserActivity($user_id, $limit = 50) {
        $stmt = $this->pdo->prepare("
            SELECT * FROM activity_logs 
            WHERE user_id = ? 
            ORDER BY created_at DESC 
            LIMIT ?
        ");
        $stmt->execute([$user_id, $limit]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function getRecentActivity($limit = 100) {
        $stmt = $this->pdo->prepare("
            SELECT 
                al.*,
                u.member_id,
                u.full_name
            FROM activity_logs al
            LEFT JOIN users u ON al.user_id = u.id
            ORDER BY al.created_at DESC
            LIMIT ?
        ");
        $stmt->execute([$limit]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function searchActivity($search, $limit = 50) {
        $searchTerm = "%$search%";
        $stmt = $this->pdo->prepare("
            SELECT 
                al.*,
                u.member_id,
                u.full_name
            FROM activity_logs al
            LEFT JOIN users u ON al.user_id = u.id
            WHERE 
                al.action LIKE ? OR
                al.description LIKE ? OR
                u.member_id LIKE ? OR
                u.full_name LIKE ?
            ORDER BY al.created_at DESC
            LIMIT ?
        ");
        $stmt->execute([$searchTerm, $searchTerm, $searchTerm, $searchTerm, $limit]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>
