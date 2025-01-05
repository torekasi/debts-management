<?php
require_once '../includes/config.php';
require_once '../includes/session.php';
requireUser();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    header('Content-Type: application/json');
    $response = ['status' => 'error', 'message' => ''];

    try {
        // Get form data
        $transaction_id = $_POST['transaction_id'];
        $user_id = $_SESSION['user_id'];
        $type = $_POST['type'];
        $amount = floatval($_POST['amount']);
        $description = $_POST['description'];
        $date_transaction = date('Y-m-d H:i:s', strtotime($_POST['transaction_date']));
        $image_path = '';

        // Quick validation
        if (!$transaction_id || !$type || !$amount || !$description || !$date_transaction) {
            throw new Exception("All fields are required");
        }

        // Start transaction
        $conn->beginTransaction();

        // Insert into database first
        $sql = "INSERT INTO transactions (transaction_id, user_id, type, amount, description, date_transaction, created_at, updated_at) 
                VALUES (?, ?, ?, ?, ?, ?, NOW(), NOW())";
        
        $stmt = $conn->prepare($sql);
        $result = $stmt->execute([
            $transaction_id,
            $user_id,
            $type,
            $amount,
            $description,
            $date_transaction
        ]);

        if (!$result) {
            throw new Exception("Failed to insert transaction into database");
        }

        // Handle file upload if present
        if (isset($_FILES['receipt_image']) && $_FILES['receipt_image']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = '../uploads/receipts/';
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }

            $file_extension = strtolower(pathinfo($_FILES['receipt_image']['name'], PATHINFO_EXTENSION));
            if (!in_array($file_extension, ['jpg', 'jpeg', 'png'])) {
                throw new Exception("Invalid file type. Only JPG and PNG are allowed.");
            }

            $new_filename = $transaction_id . '.' . $file_extension;
            $upload_path = $upload_dir . $new_filename;
            
            // Move the file first (already compressed on client side)
            if (move_uploaded_file($_FILES['receipt_image']['tmp_name'], $upload_path)) {
                $image_path = 'uploads/receipts/' . $new_filename;
                
                // Update the transaction with the image path
                $sql = "UPDATE transactions SET image_path = ? WHERE transaction_id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->execute([$image_path, $transaction_id]);
            }
        }

        // Commit transaction
        $conn->commit();

        $response['status'] = 'success';
        $response['message'] = 'Transaction added successfully!';
        $response['transaction_id'] = $transaction_id;
        echo json_encode($response);
        exit;

    } catch (Exception $e) {
        // Rollback transaction on error
        if ($conn->inTransaction()) {
            $conn->rollBack();
        }
        $response['message'] = $e->getMessage();
        error_log("Transaction Error: " . $e->getMessage());
        echo json_encode($response);
        exit;
    }
}

// If not POST request, redirect back
header('Location: member_dashboard.php');
exit;
