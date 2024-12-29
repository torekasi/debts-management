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
        <div class="grid grid-cols-2 gap-4 mb-6">
            <div class="bg-white rounded-lg shadow-md p-4 text-center">
                <div class="flex items-center justify-center mb-2">
                    <i class="fas fa-shopping-cart text-blue-500 mr-2 text-base"></i>
                    <h3 class="text-xs font-medium text-blue-500">Total Shopping</h3>
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
        </div>

        <!-- Outstanding Balance Card -->
        <div class="bg-white rounded-lg shadow-md p-4 mb-6">
            <div class="flex items-center justify-between">
                <div class="flex items-center">
                    <div class="bg-<?php echo ($totalDebt - $totalPayments) > 0 ? 'red' : 'green'; ?>-100 rounded-full p-2 mr-3">
                        <i class="fas fa-balance-scale text-<?php echo ($totalDebt - $totalPayments) > 0 ? 'red' : 'green'; ?>-500 text-base"></i>
                    </div>
                    <div>
                        <h3 class="text-sm font-semibold text-gray-900">Outstanding Balance</h3>
                        <p class="text-xs text-gray-500">Current balance to be paid</p>
                    </div>
                </div>
                <div class="text-right">
                    <p class="text-xl font-bold text-<?php echo ($totalDebt - $totalPayments) > 0 ? 'red' : 'green'; ?>-500">
                        RM<?php echo number_format($totalDebt - $totalPayments, 2); ?>
                    </p>
                    <p class="text-xs text-gray-500">
                        <?php echo ($totalDebt - $totalPayments) > 0 ? 'Due Balance' : 'Fully Paid'; ?>
                    </p>
                </div>
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
                    <div class="mt-1 flex flex-col items-center px-4 pt-3 pb-4 border-2 border-gray-300 border-dashed rounded-lg hover:border-blue-400 transition-colors duration-150">
                        <div class="space-y-1 text-center">
                            <div class="flex flex-col items-center">
                                <i class="fas fa-camera text-gray-400 text-2xl mb-2"></i>
                                <div class="flex flex-col items-center text-sm text-gray-600">
                                    <button type="button" 
                                        id="capture_button"
                                        class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 mb-1">
                                        <i class="fas fa-camera mr-2"></i>
                                        Take Photo
                                    </button>
                                    <input type="file" 
                                        id="receipt_image" 
                                        name="receipt_image" 
                                        accept="image/*" 
                                        capture="environment"
                                        class="hidden">
                                </div>
                            </div>
                            <div id="image_preview" class="hidden mt-3 w-full max-w-sm mx-auto">
                                <div class="relative">
                                    <img src="" alt="Receipt preview" class="w-full rounded-lg shadow-sm">
                                    <button type="button" 
                                        id="retake_button"
                                        class="absolute top-2 right-2 bg-red-500 text-white p-2 rounded-full hover:bg-red-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500">
                                        <i class="fas fa-redo"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="flex justify-end">
                    <button type="submit" 
                        id="submitButton"
                        class="w-full bg-blue-500 hover:bg-blue-600 text-white font-semibold py-2 px-4 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-all duration-150 disabled:opacity-50 disabled:cursor-not-allowed">
                        <span class="inline-flex items-center">
                            <span class="normal-state">Add Transaction</span>
                            <span class="loading-state hidden">
                                <svg class="animate-spin ml-2 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                Processing...
                            </span>
                        </span>
                    </button>
                </div>
            </form>

            <!-- Success Toast Notification -->
            <div id="successToast" class="fixed top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 bg-white rounded-lg shadow-xl p-6 max-w-sm w-full scale-0 opacity-0 transition-all duration-300 z-50">
                <div class="text-center">
                    <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-green-100 mb-4">
                        <svg class="h-6 w-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                    </div>
                    <h3 class="text-lg font-medium text-gray-900 mb-2">Transaction Successful!</h3>
                    <p class="text-sm text-gray-500">Your transaction has been successfully recorded.</p>
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