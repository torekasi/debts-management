<?php
class ReportingSystem {
    private $conn;
    
    public function __construct($conn) {
        $this->conn = $conn;
    }
    
    public function generateUserReport($user_id, $start_date = null, $end_date = null) {
        $where_clause = "WHERE u.id = ?";
        $params = [$user_id];
        $types = "i";
        
        if ($start_date && $end_date) {
            $where_clause .= " AND t.created_at BETWEEN ? AND ?";
            $params[] = $start_date;
            $params[] = $end_date;
            $types .= "ss";
        }
        
        $sql = "
            SELECT 
                u.member_id,
                u.full_name,
                COUNT(DISTINCT t.id) as total_transactions,
                COALESCE(SUM(t.amount), 0) as total_debt,
                COALESCE(SUM(p.amount), 0) as total_paid,
                (COALESCE(SUM(t.amount), 0) - COALESCE(SUM(p.amount), 0)) as outstanding_balance,
                MIN(t.created_at) as first_transaction,
                MAX(t.created_at) as last_transaction
            FROM users u
            LEFT JOIN transactions t ON u.id = t.user_id
            LEFT JOIN payments p ON u.id = p.user_id
            $where_clause
            GROUP BY u.id
        ";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }
    
    public function generateMonthlyReport($year = null, $month = null) {
        if (!$year) $year = date('Y');
        if (!$month) $month = date('m');
        
        $sql = "
            SELECT 
                DATE(t.created_at) as date,
                COUNT(DISTINCT t.id) as transactions_count,
                COALESCE(SUM(t.amount), 0) as total_debt,
                COUNT(DISTINCT p.id) as payments_count,
                COALESCE(SUM(p.amount), 0) as total_payments
            FROM transactions t
            LEFT JOIN payments p ON DATE(t.created_at) = DATE(p.payment_date)
            WHERE YEAR(t.created_at) = ? AND MONTH(t.created_at) = ?
            GROUP BY DATE(t.created_at)
            ORDER BY DATE(t.created_at)
        ";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("ii", $year, $month);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
    
    public function generateOutstandingReport() {
        $sql = "
            SELECT 
                u.member_id,
                u.full_name,
                COALESCE(SUM(t.amount), 0) as total_debt,
                COALESCE(SUM(p.amount), 0) as total_paid,
                (COALESCE(SUM(t.amount), 0) - COALESCE(SUM(p.amount), 0)) as outstanding_balance,
                MAX(t.created_at) as last_transaction,
                MAX(p.payment_date) as last_payment
            FROM users u
            LEFT JOIN transactions t ON u.id = t.user_id
            LEFT JOIN payments p ON u.id = p.user_id
            WHERE u.role = 'customer'
            GROUP BY u.id
            HAVING outstanding_balance > 0
            ORDER BY outstanding_balance DESC
        ";
        
        return $this->conn->query($sql)->fetch_all(MYSQLI_ASSOC);
    }
    
    public function exportToCSV($data, $filename) {
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        
        $output = fopen('php://output', 'w');
        
        // Add headers
        if (!empty($data)) {
            fputcsv($output, array_keys($data[0]));
        }
        
        // Add data
        foreach ($data as $row) {
            fputcsv($output, $row);
        }
        
        fclose($output);
    }
}
?>
