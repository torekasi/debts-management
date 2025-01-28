<?php
require_once '../includes/config.php';
require_once '../includes/session.php';
requireUser();

// Get user information
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

// Get user transactions
$stmt = $conn->prepare("
    SELECT * FROM transactions 
    WHERE user_id = ? 
    ORDER BY created_at DESC 
    LIMIT 10
");
$stmt->execute([$_SESSION['user_id']]);
$transactions = $stmt->fetchAll();

// Calculate total debt
$stmt = $conn->prepare("
    SELECT SUM(amount) as total_debt 
    FROM transactions 
    WHERE user_id = ?
");
$stmt->execute([$_SESSION['user_id']]);
$totalDebt = $stmt->fetch()['total_debt'] ?? 0;

// Calculate total payments made
$stmt = $conn->prepare("
    SELECT SUM(amount) as total_payments 
    FROM payments 
    WHERE user_id = ?
");
$stmt->execute([$_SESSION['user_id']]);
$totalPayments = $stmt->fetch()['total_payments'] ?? 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Member Dashboard - <?php echo APP_NAME; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        body {
            padding-top: 4rem;
            padding-bottom: 4rem;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        .content-container {
            flex: 1;
            max-width: 650px;
            margin: 0 auto;
            padding: 1rem;
            width: 100%;
        }
        @media (max-width: 768px) {
            .content-container {
                max-width: 100%;
            }
        }
    </style>
</head>
<body class="bg-gray-100">
    <?php include 'template/member_header.php'; ?>
    <main class="content-container">
        <!-- Financial Overview Cards -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
            <div class="bg-white rounded-lg shadow-md p-4 text-center">
                <div class="flex items-center justify-center mb-2">
                    <i class="fas fa-shopping-cart text-blue-500 mr-2 text-base"></i>
                    <h3 class="text-xs font-medium text-blue-500">Your Shopping</h3>
                </div>
                <p class="text-xl font-bold text-gray-900">RM<?php echo number_format($totalDebt, 2); ?></p>
            </div>
            <div class="bg-white rounded-lg shadow-md p-4 text-center">
                <div class="flex items-center justify-center mb-2">
                    <i class="fas fa-credit-card text-green-500 mr-2 text-base"></i>
                    <h3 class="text-xs font-medium text-green-500">Payments Made</h3>
                </div>
                <p class="text-xl font-bold text-gray-900">RM<?php echo number_format($totalPayments, 2); ?></p>
            </div>
            <div class="bg-<?php echo ($totalDebt - $totalPayments) > 0 ? 'red' : 'green'; ?>-50 rounded-lg shadow-md p-4 text-center">
                <div class="flex items-center justify-center mb-2">
                    <i class="fas fa-balance-scale text-<?php echo ($totalDebt - $totalPayments) > 0 ? 'red' : 'green'; ?>-500 mr-2 text-base"></i>
                    <h3 class="text-xs font-medium text-<?php echo ($totalDebt - $totalPayments) > 0 ? 'red' : 'green'; ?>-500">Outstanding Balance</h3>
                </div>
                <p class="text-xl font-bold text-<?php echo ($totalDebt - $totalPayments) > 0 ? 'red' : 'green'; ?>-500">
                    RM<?php echo number_format($totalDebt - $totalPayments, 2); ?>
                </p>
                <p class="text-[10px] text-gray-500">
                    <?php echo ($totalDebt - $totalPayments) > 0 ? 'Due Balance' : 'Fully Paid'; ?>
                </p>
            </div>
        </div>

        <!-- Add Shopping Form -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-6">
            <div class="flex items-center mb-6">
                <i class="fas fa-cart-plus text-blue-500 text-2xl mr-3"></i>
                <h2 class="text-xl font-bold text-gray-800">Add Shopping for <?php echo htmlspecialchars($user['full_name']); ?> </h2>
            </div>
            <form id="transactionForm" action="add_transaction.php" method="POST" enctype="multipart/form-data" class="space-y-4">
                <div class="grid grid-cols-2 gap-4">
                    <div class="relative">
                        <label for="transaction_id" class="block text-sm font-medium text-gray-700 mb-1">Transaction ID</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i class="fas fa-hashtag text-gray-400 text-sm"></i>
                            </div>
                            <input type="text" name="transaction_id" id="transaction_id" 
                                class="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-gray-900 bg-gray-100 text-xs"
                                value="<?php echo 'TRX'.date('Ymd-His').'-'.str_pad(rand(1,999),3,'0',STR_PAD_LEFT); ?>"
                                readonly>
                        </div>
                    </div>
                    <div class="relative">
                        <label for="transaction_date" class="block text-sm font-medium text-gray-700 mb-1">Transaction Date</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i class="fas fa-calendar text-gray-400 text-sm"></i>
                            </div>
                            <input type="datetime-local" name="transaction_date" id="transaction_date" 
                                class="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-gray-900 text-xs"
                                value="<?php 
                                    $date = new DateTime('now', new DateTimeZone('Asia/Kuala_Lumpur'));
                                    echo $date->format('Y-m-d\TH:i');
                                ?>"
                                required>
                        </div>
                    </div>
                </div>

                <div class="relative">
                    <label for="amount" class="block text-sm font-medium text-gray-700 mb-1">Amount (RM)</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="fas fa-money-bill-wave text-gray-400"></i>
                        </div>
                        <input type="number" 
                            step="0.01" 
                            name="amount" 
                            id="amount" 
                            autocomplete="off"
                            class="block w-full pl-10 pr-3 py-2.5 border border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-gray-900 placeholder-gray-400 transition duration-150"
                            placeholder="0.00"
                            required>
                    </div>
                </div>

                <div class="relative">
                    <label for="type" class="block text-sm font-medium text-gray-700 mb-1">Type</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="fas fa-credit-card text-gray-400"></i>
                        </div>
                        <select name="type" 
                            id="type" 
                            class="block w-full pl-10 pr-10 py-2.5 border border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-gray-900 appearance-none cursor-pointer bg-white">
                            <option value="Purchase" selected>Purchase</option>
                            <option value="Cash">Cash</option>
                            <option value="QR">QR</option>
                            <option value="Transfer">Transfer</option>
                        </select>
                        <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
                            <i class="fas fa-chevron-down text-gray-400"></i>
                        </div>
                    </div>
                </div>

                <div class="relative">
                    <label for="description" class="block text-sm font-medium text-gray-700 mb-1">Notes</label>
                    <div class="relative">
                        <div class="absolute top-3 left-0 pl-3 flex items-start pointer-events-none">
                            <i class="fas fa-align-left text-gray-400"></i>
                        </div>
                        <textarea 
                            name="description" 
                            id="description" 
                            rows="2"
                            class="block w-full pl-10 pr-3 py-2.5 border border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-gray-900 placeholder-gray-400 transition duration-150 resize-none"
                            placeholder="Note here: Sugar, Apple, Fish..."
                            required>-</textarea>
                    </div>
                </div>

                <div class="relative">
                    <label for="receipt_image" class="block text-sm font-medium text-gray-700 mb-1">Take Photo</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="fas fa-camera text-gray-400"></i>
                        </div>
                        <input type="file" 
                            name="receipt_image" 
                            id="receipt_image" 
                            accept="image/*"
                            capture="environment"
                            class="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-gray-900 text-xs"
                            onchange="previewImage(this)">
                        <button type="button" 
                            onclick="document.getElementById('receipt_image').click()"
                            class="absolute inset-y-0 right-0 px-3 flex items-center bg-blue-500 text-white rounded-r-lg hover:bg-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <i class="fas fa-camera mr-1"></i>
                            Capture
                        </button>
                    </div>
                    <div id="imagePreview" class="mt-2 hidden">
                        <img id="preview" src="#" alt="Preview" class="max-w-full h-auto rounded-lg shadow-sm">
                        <div class="mt-1 text-sm text-gray-500">
                            <span id="imageDimensions"></span> • 
                            <span id="imageSize"></span>
                        </div>
                        <button type="button" 
                            onclick="retakePhoto()"
                            class="mt-2 inline-flex items-center px-3 py-1.5 border border-gray-300 shadow-sm text-sm font-medium rounded text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                            <i class="fas fa-redo mr-1"></i>
                            Retake Photo
                        </button>
                    </div>
                </div>

                <div class="flex justify-end">
                    <button type="submit" class="w-full bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-colors duration-200">
                        <i class="fas fa-plus-circle mr-2"></i>
                        Add Transaction
                    </button>
                </div>
            </form>

            <script>
                async function compressImage(file) {
                    return new Promise((resolve) => {
                        const maxWidth = 800; // Maximum width for the image
                        const reader = new FileReader();
                        reader.readAsDataURL(file);
                        reader.onload = function(e) {
                            const img = new Image();
                            img.src = e.target.result;
                            img.onload = function() {
                                const canvas = document.createElement('canvas');
                                let width = img.width;
                                let height = img.height;

                                // Calculate new dimensions
                                if (width > maxWidth) {
                                    height = Math.round((height * maxWidth) / width);
                                    width = maxWidth;
                                }

                                canvas.width = width;
                                canvas.height = height;
                                const ctx = canvas.getContext('2d');
                                ctx.drawImage(img, 0, 0, width, height);

                                // Convert to Blob with compression
                                canvas.toBlob((blob) => {
                                    resolve(blob);
                                }, file.type, 0.7); // 0.7 is the quality (70%)
                            };
                        };
                    });
                }

                async function previewImage(input) {
                    const preview = document.getElementById('preview');
                    const imagePreview = document.getElementById('imagePreview');
                    const dimensionsSpan = document.getElementById('imageDimensions');
                    const sizeSpan = document.getElementById('imageSize');

                    if (input.files && input.files[0]) {
                        const file = input.files[0];
                        
                        // Show original file size
                        const originalSize = (file.size / 1024).toFixed(2);
                        
                        // Compress the image
                        const compressedBlob = await compressImage(file);
                        const compressedSize = (compressedBlob.size / 1024).toFixed(2);
                        
                        // Create a new File object from the compressed blob
                        const compressedFile = new File([compressedBlob], file.name, {
                            type: file.type,
                            lastModified: new Date().getTime()
                        });
                        
                        // Replace the file input's file with the compressed one
                        const dataTransfer = new DataTransfer();
                        dataTransfer.items.add(compressedFile);
                        input.files = dataTransfer.files;

                        // Preview the compressed image
                        const reader = new FileReader();
                        reader.onload = function(e) {
                            preview.src = e.target.result;
                            imagePreview.classList.remove('hidden');
                            
                            // Get image dimensions
                            const img = new Image();
                            img.onload = function() {
                                dimensionsSpan.textContent = this.width + 'x' + this.height + 'px';
                                sizeSpan.textContent = `${compressedSize} KB (reduced from ${originalSize} KB)`;
                            }
                            img.src = e.target.result;
                        }
                        reader.readAsDataURL(compressedFile);
                    } else {
                        imagePreview.classList.add('hidden');
                        preview.src = '#';
                        dimensionsSpan.textContent = '';
                        sizeSpan.textContent = '';
                    }
                }

                function retakePhoto() {
                    document.getElementById('receipt_image').value = '';
                    document.getElementById('imagePreview').classList.add('hidden');
                    document.getElementById('preview').src = '#';
                    document.getElementById('imageDimensions').textContent = '';
                    document.getElementById('imageSize').textContent = '';
                    document.getElementById('receipt_image').click();
                }

                // Add form submit handler to show loading state
                document.getElementById('transactionForm').addEventListener('submit', async function(e) {
                    e.preventDefault();
                    
                    const submitButton = this.querySelector('button[type="submit"]');
                    submitButton.disabled = true;
                    submitButton.innerHTML = `
                        <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        Processing...
                    `;

                    try {
                        const formData = new FormData(this);
                        const response = await fetch('add_transaction.php', {
                            method: 'POST',
                            body: formData
                        });

                        const result = await response.json();
                        
                        if (result.status === 'success') {
                            Swal.fire({
                                title: 'Success!',
                                text: result.message,
                                icon: 'success',
                                showConfirmButton: true,
                                confirmButtonText: 'OK'
                            }).then(() => {
                                window.location.href = 'member_transactions.php';
                            });
                        } else {
                            throw new Error(result.message);
                        }
                    } catch (error) {
                        Swal.fire({
                            title: 'Error!',
                            text: error.message || 'Something went wrong',
                            icon: 'error'
                        });
                    } finally {
                        submitButton.disabled = false;
                        submitButton.innerHTML = `
                            <i class="fas fa-plus-circle mr-2"></i>
                            Add Transaction
                        `;
                    }
                });
            </script>

            <!-- Success Toast Notification -->
            <div id="successToast" class="fixed top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 bg-white rounded-lg shadow-xl p-6 max-w-sm w-full scale-0 opacity-0 transition-all duration-300 z-50">
                <div class="text-center">
                    <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-green-100 mb-4">
                        <svg class="h-6 w-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                    </div>
                    <h3 class="text-lg font-medium text-gray-900 mb-2">Transaction Successful!</h3>
                    <p class="text-sm text-gray-500">Show this transaction to Cashier for confirmation</p>
                    <div class="mt-4">
                        <button type="button" onclick="hideSuccessToast()" class="inline-flex justify-center w-full rounded-md border border-transparent shadow-sm px-4 py-2 bg-green-600 text-base font-medium text-white hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 sm:text-sm">
                            Close
                        </button>
                    </div>
                </div>
            </div>

            <!-- Overlay Background -->
            <div id="overlay" class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity opacity-0 pointer-events-none z-40"></div>

            <script>
                document.addEventListener('DOMContentLoaded', function() {
                    const form = document.getElementById('transactionForm');
                    const submitButton = document.getElementById('submitButton');
                    const successToast = document.getElementById('successToast');
                    const overlay = document.getElementById('overlay');
                    let isSubmitting = false;

                    function showLoadingState() {
                        submitButton.disabled = true;
                        submitButton.querySelector('.normal-state').classList.add('hidden');
                        submitButton.querySelector('.loading-state').classList.remove('hidden');
                    }

                    function hideLoadingState() {
                        submitButton.disabled = false;
                        submitButton.querySelector('.normal-state').classList.remove('hidden');
                        submitButton.querySelector('.loading-state').classList.add('hidden');
                    }

                    function showSuccessToast() {
                        // Show overlay
                        overlay.classList.remove('opacity-0', 'pointer-events-none');
                        // Show toast with scale animation
                        successToast.classList.remove('scale-0', 'opacity-0');
                        successToast.classList.add('scale-100', 'opacity-100');
                    }

                    window.hideSuccessToast = function() {
                        // Hide overlay
                        overlay.classList.add('opacity-0', 'pointer-events-none');
                        // Hide toast with scale animation
                        successToast.classList.remove('scale-100', 'opacity-100');
                        successToast.classList.add('scale-0', 'opacity-0');
                        // Reload page after animation
                        setTimeout(() => {
                            window.location.reload();
                        }, 300);
                    }

                    form.addEventListener('submit', async function(e) {
                        e.preventDefault();
                        
                        if (isSubmitting) return;
                        isSubmitting = true;
                        showLoadingState();

                        try {
                            const formData = new FormData(form);
                            const response = await fetch('add_transaction.php', {
                                method: 'POST',
                                body: formData
                            });

                            const result = await response.json();
                            
                            if (result.status === 'success') {
                                Swal.fire({
                                    title: 'Success!',
                                    text: result.message,
                                    icon: 'success',
                                    confirmButtonText: 'OK'
                                }).then((result) => {
                                    window.location.href = 'member_transactions.php';
                                });
                            } else {
                                throw new Error(result.message || 'Transaction failed');
                            }
                        } catch (error) {
                            console.error('Error:', error);
                            alert(error.message || 'Failed to add transaction. Please try again.');
                        } finally {
                            hideLoadingState();
                            isSubmitting = false;
                        }
                    });
                });
            </script>

            <script>
                // Focus amount field on page load
                document.addEventListener('DOMContentLoaded', function() {
                    document.getElementById('amount').focus();
                });

                // Camera and image handling functionality
                const captureButton = document.getElementById('capture_button');
                const fileInput = document.getElementById('receipt_image');
                const preview = document.getElementById('image_preview');
                const previewImage = preview.querySelector('img');
                const retakeButton = document.getElementById('retake_button');

                captureButton.addEventListener('click', function() {
                    fileInput.click();
                });

                fileInput.addEventListener('change', function(e) {
                    const file = e.target.files[0];
                    if (file) {
                        const reader = new FileReader();

                        reader.onload = function(e) {
                            previewImage.src = e.target.result;
                            preview.classList.remove('hidden');
                            captureButton.textContent = 'Change Photo';
                        }

                        reader.readAsDataURL(file);
                    }
                });

                retakeButton.addEventListener('click', function() {
                    preview.classList.add('hidden');
                    previewImage.src = '';
                    fileInput.value = '';
                    captureButton.textContent = 'Take Photo';
                });

                // Drag and drop functionality
                const dropZone = document.querySelector('.border-dashed');
                
                ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
                    dropZone.addEventListener(eventName, preventDefaults, false);
                });

                function preventDefaults (e) {
                    e.preventDefault();
                    e.stopPropagation();
                }

                ['dragenter', 'dragover'].forEach(eventName => {
                    dropZone.addEventListener(eventName, highlight, false);
                });

                ['dragleave', 'drop'].forEach(eventName => {
                    dropZone.addEventListener(eventName, unhighlight, false);
                });

                function highlight(e) {
                    dropZone.classList.add('border-blue-400', 'bg-blue-50');
                }

                function unhighlight(e) {
                    dropZone.classList.remove('border-blue-400', 'bg-blue-50');
                }

                dropZone.addEventListener('drop', handleDrop, false);

                function handleDrop(e) {
                    const dt = e.dataTransfer;
                    const files = dt.files;
                    
                    fileInput.files = files;
                    // Trigger change event manually
                    const event = new Event('change', { bubbles: true });
                    fileInput.dispatchEvent(event);
                }
            </script>
        </div>

        <?php include 'template/member_footer.php'; ?>
    </main>
</body>
</html>
