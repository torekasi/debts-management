<?php
require_once '../includes/config.php';
require_once '../includes/session.php';
requireUser();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $amount = $_POST['amount'];
    $description = $_POST['description'];
    $type = $_POST['type'];
    $transaction_id = $_POST['transaction_id'];
    $transaction_date = $_POST['transaction_date'];

    // Format the datetime for MySQL
    $formatted_date = date('Y-m-d H:i:s', strtotime($transaction_date));

    // Handle file upload if present
    $image_url = '';
    if (isset($_FILES['receipt_image']) && $_FILES['receipt_image']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = '../uploads/receipts/';
        
        // Create directory if it doesn't exist
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        $temp_name = $_FILES['receipt_image']['tmp_name'];
        $original_name = $_FILES['receipt_image']['name'];
        $file_extension = strtolower(pathinfo($original_name, PATHINFO_EXTENSION));
        
        // Generate unique filename using transaction_id
        $new_filename = $transaction_id . '.' . $file_extension;
        $destination = $upload_dir . $new_filename;
        
        // Validate file type
        $allowed_types = ['jpg', 'jpeg', 'png', 'gif'];
        if (!in_array($file_extension, $allowed_types)) {
            $_SESSION['error'] = "Invalid file type. Only JPG, PNG, and GIF files are allowed.";
            header('Location: member_dashboard.php');
            exit;
        }

        // Validate file size (10MB max)
        if ($_FILES['receipt_image']['size'] > 10 * 1024 * 1024) {
            $_SESSION['error'] = "File is too large. Maximum size is 10MB.";
            header('Location: member_dashboard.php');
            exit;
        }

        if (move_uploaded_file($temp_name, $destination)) {
            // Generate full URL for the image
            $image_url = 'http://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . '/uploads/receipts/' . $new_filename;
        } else {
            $_SESSION['error'] = "Failed to upload file.";
            header('Location: member_dashboard.php');
            exit;
        }
    }

    try {
        // Insert transaction into database
        $sql = "INSERT INTO transactions (transaction_id, amount, description, type, receipt_url, transaction_date, user_id) 
                VALUES (?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $conn->prepare($sql);
        $stmt->execute([
            $transaction_id,
            $amount,
            $description,
            $type,
            $image_url,
            $formatted_date,
            $_SESSION['user_id']
        ]);

        $_SESSION['success'] = "Transaction added successfully!";
    } catch (PDOException $e) {
        $_SESSION['error'] = "Failed to add transaction: " . $e->getMessage();
    }

    header('Location: member_dashboard.php');
    exit;
}

// If not POST request, redirect to dashboard
header('Location: member_dashboard.php');
exit;
