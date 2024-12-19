<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/notifications.php';
require_once __DIR__ . '/../includes/activity_log.php';

// This script should be run daily via cron job
// Example cron entry:
// 0 0 * * * php /path/to/check_payments.php

$notifications = new NotificationSystem($conn);

// Get upcoming payments due in the next few days
$stmt = $pdo->prepare("
    SELECT p.*, u.name 
    FROM payments p 
    JOIN users u ON p.user_id = u.id 
    WHERE p.status = 'pending' 
    AND p.due_date BETWEEN CURDATE() 
    AND DATE_ADD(CURDATE(), INTERVAL ? DAY)
");
$stmt->execute([PAYMENT_REMINDER_DAYS]);
$upcoming_payments = $stmt->fetchAll();

// Create notifications for upcoming payments
foreach ($upcoming_payments as $payment) {
    $notification = new Notification();
    $notification->create([
        'user_id' => $payment['user_id'],
        'type' => 'payment_reminder',
        'message' => sprintf(
            'Payment of $%.2f is due on %s',
            $payment['amount'],
            date('F j, Y', strtotime($payment['due_date']))
        ),
        'data' => json_encode([
            'payment_id' => $payment['id'],
            'amount' => $payment['amount'],
            'due_date' => $payment['due_date']
        ])
    ]);
}

// Get overdue payments
$stmt = $pdo->prepare("
    SELECT p.*, u.name 
    FROM payments p 
    JOIN users u ON p.user_id = u.id 
    WHERE p.status = 'pending' 
    AND p.due_date < CURDATE()
    AND DATE(p.due_date) = DATE_SUB(CURDATE(), INTERVAL ? DAY)
");
$stmt->execute([PAYMENT_OVERDUE_DAYS]);
$overdue_payments = $stmt->fetchAll();

// Create notifications for overdue payments
foreach ($overdue_payments as $payment) {
    // Create overdue notification
    $notification = new Notification();
    $notification->create([
        'user_id' => $payment['user_id'],
        'type' => 'payment_overdue',
        'message' => sprintf(
            'Payment of $%.2f was due on %s and is now overdue',
            $payment['amount'],
            date('F j, Y', strtotime($payment['due_date']))
        ),
        'data' => json_encode([
            'payment_id' => $payment['id'],
            'amount' => $payment['amount'],
            'due_date' => $payment['due_date']
        ])
    ]);
    
    // Log the overdue payment
    $activity_log = new ActivityLog();
    $activity_log->log(
        $payment['user_id'],
        'payment_overdue',
        sprintf(
            'Payment #%d of $%.2f is overdue (due date: %s)',
            $payment['id'],
            $payment['amount'],
            date('Y-m-d', strtotime($payment['due_date']))
        )
    );
}

// Check for payment dues
$notifications->checkPaymentDue();

// Log execution
$log_file = __DIR__ . '/../logs/cron.log';
$message = date('Y-m-d H:i:s') . " - Payment check completed\n";
file_put_contents($log_file, $message, FILE_APPEND);

// Log activity
$activity_log = new ActivityLog();
$activity_log->log(
    0, // System user ID
    'cron_job',
    sprintf(
        'Payment check completed. Created %d reminders and processed %d overdue notifications.',
        count($upcoming_payments),
        count($overdue_payments)
    )
);
