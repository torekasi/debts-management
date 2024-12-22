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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Member Dashboard - <?php echo APP_NAME; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
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
        <div class="grid grid-cols-3 gap-2 sm:gap-4 mb-6">
            <div class="bg-white rounded-lg shadow-md p-2 sm:p-4 text-center">
                <div class="flex items-center justify-center mb-1">
                    <i class="fas fa-shopping-cart text-blue-500 mr-1 sm:mr-2 text-xs sm:text-base"></i>
                    <h3 class="text-xs sm:text-sm font-medium text-blue-500"> Shoppings</h3>
                </div>
                <p class="text-lg sm:text-3xl font-bold text-gray-900 text-center">RM<?php echo number_format($totalDebt, 2); ?></p>
            </div>
            <div class="bg-white rounded-lg shadow-md p-2 sm:p-4 text-center">
                <div class="flex items-center justify-center mb-1">
                    <i class="fas fa-credit-card text-green-500 mr-1 sm:mr-2 text-xs sm:text-base"></i>
                    <h3 class="text-xs sm:text-sm font-medium text-green-500">Payments Made</h3>
                </div>
                <p class="text-lg sm:text-3xl font-bold text-gray-900 text-center">RM0.00</p>
            </div>
            <div class="bg-purple-50 rounded-lg shadow-md p-2 sm:p-4 text-center">
                <div class="flex items-center justify-center mb-1">
                    <i class="fas fa-balance-scale text-purple-500 mr-1 sm:mr-2 text-xs sm:text-base"></i>
                    <h3 class="text-xs sm:text-sm font-medium text-purple-500">Balance to Pay</h3>
                </div>
                <p class="text-lg sm:text-3xl font-bold text-gray-900 text-center">RM<?php echo number_format($totalDebt, 2); ?></p>
            </div>
        </div>

        <!-- Add Shopping Form -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-6">
            <div class="flex items-center mb-6">
                <i class="fas fa-cart-plus text-blue-500 text-2xl mr-3"></i>
                <h2 class="text-xl font-bold text-gray-800">Add Shopping for <?php echo htmlspecialchars($user['full_name']); ?> </h2>
            </div>
            <form action="add_transaction.php" method="POST" enctype="multipart/form-data" class="space-y-4">
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
                                value="<?php echo date('Y-m-d\TH:i'); ?>"
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
                            <option value="Cash">Cash Payment</option>
                            <option value="QR">QR Payment</option>
                            <option value="Transfer">Bank Transfer</option>
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
                            <i class="fas fa-file-alt text-gray-400"></i>
                        </div>
                        <textarea 
                            name="description" 
                            id="description" 
                            rows="2"
                            class="block w-full pl-10 pr-3 py-2.5 border border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-gray-900 placeholder-gray-400 transition duration-150 resize-none"
                            placeholder="Enter description"
                            required></textarea>
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

                <button type="submit" 
                    class="w-full inline-flex justify-center items-center px-6 py-3 border border-transparent text-base font-medium rounded-lg shadow-sm text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition duration-150">
                    <i class="fas fa-plus mr-2"></i>
                    Add Transaction
                </button>
            </form>
        </div>

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
    </main>

    <?php include 'template/member_footer.php'; ?>
</body>
</html>