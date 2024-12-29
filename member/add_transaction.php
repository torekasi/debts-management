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

        // Validate required fields
        if (empty($transaction_id) || empty($type) || empty($amount) || empty($description) || empty($date_transaction)) {
            throw new Exception("All fields are required");
        }

        // Handle file upload if present
        if (isset($_FILES['receipt_image']) && $_FILES['receipt_image']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = '../uploads/receipts/';
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }

            $file_extension = strtolower(pathinfo($_FILES['receipt_image']['name'], PATHINFO_EXTENSION));
            $new_filename = $transaction_id . '.' . $file_extension;
            $upload_path = $upload_dir . $new_filename;
            $temp_path = $_FILES['receipt_image']['tmp_name'];

            // Validate file type
            $allowed_types = ['jpg', 'jpeg', 'png'];
            if (!in_array($file_extension, $allowed_types)) {
                throw new Exception("Invalid file type. Only JPG and PNG are allowed.");
            }

            // Get image info
            list($width, $height, $type) = getimagesize($temp_path);
            
            // Calculate new dimensions (max width: 400px)
            $max_width = 400;
            $new_width = $width;
            $new_height = $height;
            
            if ($width > $max_width) {
                $ratio = $max_width / $width;
                $new_width = $max_width;
                $new_height = $height * $ratio;
            }

            // Create new image
            $new_image = imagecreatetruecolor($new_width, $new_height);
            
            // Handle transparency for PNG
            if ($file_extension === 'png') {
                imagealphablending($new_image, false);
                imagesavealpha($new_image, true);
                $source = imagecreatefrompng($temp_path);
            } else {
                $source = imagecreatefromjpeg($temp_path);
            }

            // Resize image
            imagecopyresampled(
                $new_image, 
                $source, 
                0, 0, 0, 0, 
                $new_width, 
                $new_height, 
                $width, 
                $height
            );

            // Save the resized image
            if ($file_extension === 'png') {
                // For PNG, use maximum compression (9)
                imagepng($new_image, $upload_path, 9);
            } else {
                // For JPEG, use quality 60 for compression (0-100)
                imagejpeg($new_image, $upload_path, 60);
            }

            // Clean up
            imagedestroy($new_image);
            imagedestroy($source);

            // Verify file size after compression
            if (filesize($upload_path) > 500 * 1024) { // 500KB
                unlink($upload_path); // Delete the file if it's still too large
                throw new Exception("Image file size is too large after compression. Please use a smaller image.");
            }

            $image_path = 'uploads/receipts/' . $new_filename;
        }

        // Insert into database
        $sql = "INSERT INTO transactions (transaction_id, user_id, type, amount, description, date_transaction, image_path, created_at, updated_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), NOW())";
        
        $stmt = $conn->prepare($sql);
        $result = $stmt->execute([
            $transaction_id,
            $user_id,
            $type,
            $amount,
            $description,
            $date_transaction,
            $image_path
        ]);

        if (!$result) {
            throw new Exception("Failed to insert transaction into database");
        }

        $response['status'] = 'success';
        $response['message'] = 'Transaction added successfully!';
        echo json_encode($response);
        exit;

    } catch (Exception $e) {
        $response['message'] = $e->getMessage();
        error_log("Transaction Error: " . $e->getMessage());
        echo json_encode($response);
        exit;
    }
}

// If not POST request, redirect back
header('Location: member_dashboard.php');
exit;
?>
