<?php
require_once '../includes/config.php';
require_once '../includes/session.php';
requireUser();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $description = $_POST['description'] ?? '';
    $amount = $_POST['amount'] ?? 0;
    $type = $_POST['type'] ?? 'Purchase';
    $image_url = '';

    // Handle file upload
    if (isset($_FILES['receipt_image']) && $_FILES['receipt_image']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = '../uploads/receipts/';
        
        // Create directory if it doesn't exist
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        // Generate unique filename
        $file_extension = strtolower(pathinfo($_FILES['receipt_image']['name'], PATHINFO_EXTENSION));
        $unique_filename = uniqid('receipt_') . '.' . $file_extension;
        $upload_path = $upload_dir . $unique_filename;

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

        // Move uploaded file
        if (move_uploaded_file($_FILES['receipt_image']['tmp_name'], $upload_path)) {
            // Generate full URL for the image
            $image_url = 'http://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . '/uploads/receipts/' . $unique_filename;
        } else {
            $_SESSION['error'] = "Failed to upload file.";
            header('Location: member_dashboard.php');
            exit;
        }
    }

    try {
        // Insert transaction into database
        $stmt = $conn->prepare("
            INSERT INTO transactions (user_id, description, amount, type, receipt_url, created_at) 
            VALUES (?, ?, ?, ?, ?, NOW())
        ");
        
        $stmt->execute([
            $_SESSION['user_id'],
            $description,
            $amount,
            $type,
            $image_url
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
