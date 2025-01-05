<?php
function handleImageUpload($file) {
    $target_dir = __DIR__ . "/../uploads/";
    if (!file_exists($target_dir)) {
        mkdir($target_dir, 0777, true);
    }

    $file_extension = strtolower(pathinfo($file["name"], PATHINFO_EXTENSION));
    $new_filename = uniqid() . '.' . $file_extension;
    $target_file = $target_dir . $new_filename;

    // Check if image file is actual image
    $check = getimagesize($file["tmp_name"]);
    if ($check === false) {
        throw new Exception("File is not an image.");
    }

    // Check file size (limit to 5MB)
    if ($file["size"] > 5000000) {
        throw new Exception("File is too large. Maximum size is 5MB.");
    }

    // Allow certain file formats
    $allowed_types = ["jpg", "jpeg", "png", "gif"];
    if (!in_array($file_extension, $allowed_types)) {
        throw new Exception("Only JPG, JPEG, PNG & GIF files are allowed.");
    }

    // Upload file
    if (!move_uploaded_file($file["tmp_name"], $target_file)) {
        throw new Exception("Failed to upload file.");
    }

    return "uploads/" . $new_filename;
}
?>
