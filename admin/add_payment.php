<?php
require_once '../includes/config.php';
require_once '../includes/session.php';

// Require admin authentication
requireAdmin();

$page_title = 'Add Payment';

try {
    $dsn = sprintf(
        "mysql:host=%s;port=%s;dbname=%s",
        DB_HOST,
        DB_PORT,
        DB_NAME
    );
    
    $pdo = new PDO($dsn, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Get only active users for dropdown
    $users_query = "SELECT id, full_name, member_id 
                   FROM users 
                   WHERE role = 'user' 
                   AND status = 'active' 
                   ORDER BY full_name";
    $users = $pdo->query($users_query)->fetchAll();

    // Get user_id from URL if present
    $selected_user_id = isset($_GET['user_id']) ? $_GET['user_id'] : null;
    
    // If user_id is provided, verify it exists
    $selected_user = null;
    if ($selected_user_id) {
        foreach ($users as $user) {
            if ($user['id'] == $selected_user_id) {
                $selected_user = $user;
                break;
            }
        }
    }

    // Handle form submission
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        try {
            // Validate required fields
            $required_fields = ['user_id', 'amount', 'date_payment', 'description'];
            $missing_fields = [];
            
            foreach ($required_fields as $field) {
                if (empty($_POST[$field])) {
                    $missing_fields[] = $field;
                }
            }
            
            if (!empty($missing_fields)) {
                throw new Exception("Missing required fields: " . implode(', ', $missing_fields));
            }

            // Start transaction
            $pdo->beginTransaction();

            try {
                // Format amount
                $amount = number_format((float)$_POST['amount'], 2, '.', '');

                // Insert payment
                $stmt = $pdo->prepare("
                    INSERT INTO payments (
                        user_id, amount, payment_method, reference_number, 
                        transaction_id, notes, created_at, updated_at, payment_date
                    ) VALUES (
                        :user_id, :amount, :payment_method, :reference_number,
                        :transaction_id, :description, NOW(), NOW(), :payment_date
                    )
                ");

                $result = $stmt->execute([
                    ':user_id' => $_POST['user_id'],
                    ':amount' => $amount,
                    ':payment_method' => $_POST['payment_type'] ?? null,
                    ':reference_number' => $_POST['reference_number'] ?? null,
                    ':transaction_id' => $_POST['transaction_id'] ?? null,
                    ':description' => $_POST['description'],
                    ':payment_date' => $_POST['date_payment']
                ]);

                if (!$result) {
                    throw new Exception("Failed to insert payment");
                }

                // Commit transaction
                $pdo->commit();
                $_SESSION['success_message'] = "Payment has been saved successfully!";
                header("Location: users.php");
                exit;

            } catch (Exception $e) {
                $pdo->rollBack();
                throw $e;
            }

        } catch (Exception $e) {
            $_SESSION['error_message'] = "Error adding payment: " . $e->getMessage();
        }
    }
} catch (Exception $e) {
    $_SESSION['error_message'] = "Database connection error: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - Admin Dashboard</title>
    <link href="https://cdn.tailwindcss.com" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <style>
        /* Base form input styles */
        .form-input, 
        .select2-container--default .select2-selection--single,
        select {
            @apply focus:ring-indigo-500 focus:border-indigo-500 block w-full h-12 sm:text-sm border-2 border-gray-300 rounded-lg px-4;
        }

        /* Select2 Custom Styles */
        .select2-container--default .select2-selection--single {
            height: 48px !important;
            padding: 10px 16px !important;
            border-width: 2px !important;
            border-color: #d1d5db !important;
            border-radius: 0.5rem !important;
            background-color: #fff !important;
        }

        .select2-container--default .select2-selection--single .select2-selection__rendered {
            line-height: 28px !important;
            padding-left: 0 !important;
            color: #111827 !important;
        }

        .select2-container--default .select2-selection--single .select2-selection__arrow {
            height: 46px !important;
            right: 12px !important;
        }

        /* Input with icon styles */
        .input-group .relative input {
            padding-left: 3rem;
        }

        .input-group .absolute {
            left: 0;
            padding-left: 1rem;
        }

        /* Textarea styles */
        textarea.form-input {
            @apply p-4;
            min-height: 100px;
        }

        /* File input wrapper styles */
        .file-input-wrapper {
            @apply relative border-2 border-gray-300 border-dashed rounded-lg p-8 mt-1 hover:border-indigo-300 transition-colors duration-200;
        }

        /* Submit button styles */
        .submit-button {
            @apply w-full bg-indigo-600 text-white px-6 py-4 rounded-lg font-medium hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-colors duration-200;
        }
    </style>
</head>
<body class="bg-gray-100">
    <div class="min-h-screen">
        <!-- Navigation -->
        <?php require_once 'template/header.php'; ?>
        
        <!-- Main Content -->
        <main class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
            <div class="container mx-auto px-4 py-8 max-w-5xl">
                <!-- Header with Back Button -->
                <div class="flex justify-between items-center mb-8">
                    <h1 class="text-2xl font-bold text-gray-900">Add New Payment</h1>
                    <a href="users.php" class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                        <i class="fas fa-arrow-left mr-2"></i>
                        Back to Users
                    </a>
                </div>

                <!-- Main Form -->
                <div class="bg-white shadow rounded-lg overflow-hidden">
                    <div class="p-8">
                        <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="POST" enctype="multipart/form-data" id="paymentForm">
                            <div class="grid grid-cols-2 gap-x-8 gap-y-6">
                                <!-- Left Column -->
                                <div class="space-y-6">
                                    <!-- User Selection -->
                                    <div class="input-group">
                                        <label for="user_id" class="block text-sm font-medium text-gray-700 mb-2">Select User</label>
                                        <select id="user_id" name="user_id" required
                                                class="select2 focus:ring-indigo-500 focus:border-indigo-500 block w-full h-12 sm:text-sm border-2 border-gray-300 rounded-lg px-4"
                                                autocomplete="off">
                                            <option value="">Select a user</option>
                                            <?php foreach ($users as $user): ?>
                                                <option value="<?php echo $user['id']; ?>" 
                                                        <?php echo ($selected_user_id == $user['id']) ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($user['full_name']) . ' (' . htmlspecialchars($user['member_id']) . ')'; ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>

                                    <!-- Amount -->
                                    <div class="input-group">
                                        <label for="amount" class="block text-sm font-medium text-gray-700 mb-2">Amount (RM)</label>
                                        <div class="relative">
                                            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                                <span class="text-gray-500 sm:text-sm">RM</span>
                                            </div>
                                            <input type="number" 
                                                   step="0.01" 
                                                   name="amount" 
                                                   id="amount" 
                                                   required
                                                   class="focus:ring-indigo-500 focus:border-indigo-500 block w-full pl-12 pr-4 h-12 sm:text-sm border-2 border-gray-300 rounded-lg"
                                                   placeholder="0.00">
                                        </div>
                                    </div>
                                </div>

                                <!-- Right Column -->
                                <div class="space-y-6">
                                    <!-- Payment Type -->
                                    <div class="input-group">
                                        <label for="payment_type" class="block text-sm font-medium text-gray-700 mb-2">Payment Type</label>
                                        <select id="payment_type" name="payment_type" required
                                                class="focus:ring-indigo-500 focus:border-indigo-500 block w-full h-12 sm:text-sm border-2 border-gray-300 rounded-lg px-4">
                                            <option value="">Select payment type</option>
                                            <option value="cash">Cash</option>
                                            <option value="QR">QR</option>
                                            <option value="cards">Card Payment</option>
                                            <option value="transfer">Transfer</option>
                                        </select>
                                    </div>

                                    <!-- Date -->
                                    <div class="input-group">
                                        <label for="date_payment_display" class="block text-sm font-medium text-gray-700 mb-2">Payment Date</label>
                                        <div class="relative">
                                            <input type="text" 
                                                   id="date_payment_display" 
                                                   class="focus:ring-indigo-500 focus:border-indigo-500 block w-full h-12 sm:text-sm border-2 border-gray-300 rounded-lg px-4"
                                                   placeholder="Select date and time">
                                            <input type="hidden" 
                                                   name="date_payment" 
                                                   id="date_payment" 
                                                   required>
                                        </div>
                                    </div>
                                </div>

                                <!-- Full Width Fields -->
                                <div class="col-span-2 space-y-6">
                                    <!-- Description -->
                                    <div class="input-group">
                                        <label for="description" class="block text-sm font-medium text-gray-700 mb-2">Description</label>
                                        <textarea id="description" 
                                                  name="description" 
                                                  required
                                                  rows="3" 
                                                  class="focus:ring-indigo-500 focus:border-indigo-500 block w-full sm:text-sm border-2 border-gray-300 rounded-lg p-4"
                                                  placeholder="Enter payment description"></textarea>
                                    </div>

                                    <!-- Receipt Image -->
                                    <div class="input-group">
                                        <label for="image" class="block text-sm font-medium text-gray-700 mb-2">Upload Image (Optional)</label>
                                        <div class="mt-1 flex justify-center px-6 pt-5 pb-6 border-2 border-gray-300 border-dashed rounded-lg">
                                            <div class="space-y-1 text-center">
                                                <i class="fas fa-cloud-upload-alt text-gray-400 text-3xl mb-3"></i>
                                                <div class="flex text-sm text-gray-600">
                                                    <label for="image" class="relative cursor-pointer bg-white rounded-md font-medium text-indigo-600 hover:text-indigo-500 focus-within:outline-none focus-within:ring-2 focus-within:ring-offset-2 focus-within:ring-indigo-500">
                                                        <span>Choose File</span>
                                                        <input type="file" 
                                                               id="image" 
                                                               name="image" 
                                                               accept="image/*"
                                                               class="sr-only">
                                                    </label>
                                                    <p class="pl-1">No file chosen</p>
                                                </div>
                                                <p class="text-xs text-gray-500">PNG, JPG, GIF up to 10MB</p>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Submit Button -->
                                    <div class="w-full">
                                        <button type="submit" 
                                                class="w-full px-8 py-3 bg-green-600 text-white text-sm font-medium rounded-md hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 transition-colors duration-150">
                                            Save Payment
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
    $(document).ready(function() {
        // Initialize Select2
        $('.select2').select2({
            placeholder: "Search for a user...",
            allowClear: true,
            dropdownParent: $('body'),
            minimumResultsForSearch: 0,
            width: '100%'
        });

        <?php if ($selected_user_id): ?>
        // If user is pre-selected, initialize Select2 with the value and focus on amount
        $('#user_id').val('<?php echo $selected_user_id; ?>').trigger('change');
        setTimeout(function() {
            $('#amount').focus();
        }, 100);
        <?php endif; ?>

        // Focus search field immediately when dropdown opens
        $(document).on('select2:open', () => {
            document.querySelector('.select2-container--open .select2-search__field').focus();
        });

        // Initialize Flatpickr for date input
        const fp = flatpickr("#date_payment_display", {
            enableTime: true,
            dateFormat: "d M Y h:i K",
            defaultDate: new Date(),
            onChange: function(selectedDates, dateStr) {
                // Update hidden input with MySQL formatted date
                const mysqlDate = selectedDates[0].toISOString().slice(0, 19).replace('T', ' ');
                document.getElementById('date_payment').value = mysqlDate;
            }
        });
        
        // Trigger initial date set
        const initialDate = new Date();
        document.getElementById('date_payment').value = initialDate.toISOString().slice(0, 19).replace('T', ' ');

        // Handle form submission
        $('#paymentForm').on('submit', function(e) {
            e.preventDefault();

            // Format amount
            const amount = parseFloat($('#amount').val()).toFixed(2);
            $('#amount').val(amount);

            // Submit form
            this.submit();
        });

        // Success message handler
        <?php if (isset($_SESSION['success_message'])): ?>
            const successMessage = '<?php echo addslashes($_SESSION['success_message']); ?>';
            Swal.fire({
                title: 'Success!',
                text: successMessage,
                icon: 'success',
                confirmButtonText: 'OK'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = 'users.php';
                }
            });
            <?php unset($_SESSION['success_message']); ?>
        <?php endif; ?>

        // Error message handler
        <?php if (isset($_SESSION['error_message'])): ?>
            const errorMessage = '<?php echo addslashes($_SESSION['error_message']); ?>';
            Swal.fire({
                title: 'Error!',
                text: errorMessage,
                icon: 'error',
                confirmButtonText: 'OK'
            });
            <?php unset($_SESSION['error_message']); ?>
        <?php endif; ?>
    });
    </script>
</body>
</html>
