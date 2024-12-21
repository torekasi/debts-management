<?php
require_once '../includes/config.php';
require_once '../includes/session.php';

// Ensure no output before setting headers
error_reporting(E_ALL);
ini_set('display_errors', 0);

// Set JSON header
header('Content-Type: application/json');

try {
    // Check session and admin status
    if (!isset($_SESSION['user_id'])) {
        throw new Exception('Not authenticated');
    }

    // Check if it's a POST request
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method');
    }

    // Validate input
    $required_fields = ['user_id', 'amount', 'payment_method', 'payment_date', 'reference_number'];
    foreach ($required_fields as $field) {
        if (empty($_POST[$field])) {
            throw new Exception("$field is required");
        }
    }

    $user_id = filter_input(INPUT_POST, 'user_id', FILTER_VALIDATE_INT);
    $amount = filter_input(INPUT_POST, 'amount', FILTER_VALIDATE_FLOAT);
    $payment_method = filter_input(INPUT_POST, 'payment_method', FILTER_SANITIZE_STRING);
    $payment_date = filter_input(INPUT_POST, 'payment_date', FILTER_SANITIZE_STRING);
    $reference_number = filter_input(INPUT_POST, 'reference_number', FILTER_SANITIZE_STRING);
    $notes = filter_input(INPUT_POST, 'notes', FILTER_SANITIZE_STRING) ?? '';

    if (!$user_id || !$amount || !$payment_method || !$payment_date || !$reference_number) {
        throw new Exception('Invalid input data');
    }

    // Validate payment date format
    $date = DateTime::createFromFormat('Y-m-d', $payment_date);
    if (!$date || $date->format('Y-m-d') !== $payment_date) {
        throw new Exception('Invalid payment date format');
    }

    // Validate amount is positive
    if ($amount <= 0) {
        throw new Exception('Amount must be greater than 0');
    }

    // Check if user exists
    $stmt = $conn->prepare("SELECT id FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    if (!$stmt->fetch()) {
        throw new Exception('User not found');
    }

    // Start transaction
    $conn->beginTransaction();

    try {
        // Check if reference number already exists
        $stmt = $conn->prepare("SELECT id FROM payments WHERE reference_number = ?");
        $stmt->execute([$reference_number]);
        if ($stmt->fetch()) {
            throw new Exception('Reference number already exists');
        }

        // Insert payment
        $stmt = $conn->prepare("
            INSERT INTO payments (user_id, amount, payment_method, payment_date, reference_number, notes, created_at)
            VALUES (?, ?, ?, ?, ?, ?, NOW())
        ");

        $stmt->execute([
            $user_id,
            $amount,
            $payment_method,
            $payment_date,
            $reference_number,
            $notes
        ]);

        // Log activity
        $admin_id = $_SESSION['user_id'];
        $description = sprintf(
            "Payment added for user ID %d: RM%.2f via %s (Ref: %s)",
            $user_id,
            $amount,
            $payment_method,
            $reference_number
        );

        $stmt = $conn->prepare("
            INSERT INTO activity_logs (user_id, action, description, ip_address, user_agent, created_at)
            VALUES (?, ?, ?, ?, ?, NOW())
        ");

        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        
        $stmt->execute([
            $admin_id,
            'payment_added',
            $description,
            $_SERVER['REMOTE_ADDR'],
            $user_agent
        ]);

        // Commit transaction
        $conn->commit();
        
        echo json_encode([
            'success' => true,
            'message' => 'Payment processed successfully',
            'data' => [
                'reference_number' => $reference_number,
                'amount' => $amount,
                'payment_method' => $payment_method
            ]
        ]);

    } catch (Exception $e) {
        $conn->rollBack();
        throw $e;
    }

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
