<?php
require_once '../includes/config.php';
require_once '../includes/session.php';

// Require admin authentication
requireAdmin();

$page_title = 'Add Transaction';

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
            $required_fields = ['user_id', 'type', 'amount', 'date_transaction', 'description'];
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
                // Generate transaction ID
                $date = date('Ymd');
                $stmt = $pdo->query("SELECT MAX(transaction_id) as max_id FROM transactions WHERE transaction_id LIKE 'TR-$date-%' FOR UPDATE");
                $result = $stmt->fetch();
                $max_id = $result['max_id'];
                
                if ($max_id) {
                    $num = intval(substr($max_id, -4)) + 1;
                } else {
                    $num = 1;
                }
                $transaction_id = sprintf("TR-%s-%04d", $date, $num);

                // Generate new ID
                $stmt = $pdo->query("SELECT MAX(id) as max_id FROM transactions");
                $result = $stmt->fetch();
                $new_id = ($result['max_id'] ?? 0) + 1;

                // Handle image upload
                $image_path = null;
                if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                    $upload_dir = '../uploads/receipts/';
                    if (!file_exists($upload_dir)) {
                        mkdir($upload_dir, 0777, true);
                    }

                    $file_extension = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
                    $new_filename = $transaction_id . '.' . $file_extension;
                    $target_path = $upload_dir . $new_filename;

                    if (move_uploaded_file($_FILES['image']['tmp_name'], $target_path)) {
                        $image_path = 'transactions/' . $new_filename;
                    }
                }

                // Format amount
                $amount = number_format((float)$_POST['amount'], 2, '.', '');

                // Insert transaction
                $stmt = $pdo->prepare("
                    INSERT INTO transactions (
                        id, transaction_id, user_id, type, amount, description, 
                        date_transaction, image_path, created_at, updated_at
                    ) VALUES (
                        :id, :transaction_id, :user_id, :type, :amount, :description, 
                        :date_transaction, :image_path, NOW(), NOW()
                    )
                ");

                $result = $stmt->execute([
                    ':id' => $new_id,
                    ':transaction_id' => $transaction_id,
                    ':user_id' => $_POST['user_id'],
                    ':type' => $_POST['type'],
                    ':amount' => $amount,
                    ':description' => $_POST['description'],
                    ':date_transaction' => $_POST['date_transaction'],
                    ':image_path' => $image_path
                ]);

                if (!$result) {
                    throw new Exception("Failed to insert transaction");
                }

                // Commit transaction
                $pdo->commit();
                $_SESSION['success_message'] = "Transaction has been saved successfully!";

            } catch (Exception $e) {
                $pdo->rollBack();
                throw $e;
            }

        } catch (Exception $e) {
            $_SESSION['error_message'] = "Error adding transaction: " . $e->getMessage();
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
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <style>
        .flatpickr-calendar {
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        /* Select2 Custom Styles */
        .select2-container--default .select2-selection--single {
            height: 42px !important;
            padding: 6px 12px !important;
            border: 1px solid #d1d5db !important;
            border-radius: 0.375rem !important;
            background-color: #fff !important;
        }

        .select2-container--default .select2-selection--single .select2-selection__rendered {
            line-height: 28px !important;
            color: #111827 !important;
        }

        .select2-container--default .select2-selection--single .select2-selection__arrow {
            height: 40px !important;
            right: 8px !important;
        }

        .select2-dropdown {
            border: 1px solid #d1d5db !important;
            border-radius: 0.375rem !important;
            margin-top: 1px !important;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06) !important;
        }

        .select2-container--default .select2-search--dropdown .select2-search__field {
            border: 1px solid #d1d5db !important;
            border-radius: 0.375rem !important;
            padding: 8px !important;
            margin: 8px !important;
            width: calc(100% - 16px) !important;
        }

        .select2-container--default .select2-results__option--highlighted[aria-selected] {
            background-color: #4f46e5 !important;
        }

        .select2-results__option {
            padding: 8px 12px !important;
        }

        .select2-container--default .select2-selection--single:focus,
        .select2-container--default.select2-container--open .select2-selection--single {
            border-color: #4f46e5 !important;
            box-shadow: 0 0 0 1px #4f46e5 !important;
            outline: none !important;
        }
    </style>
</head>

<body class="bg-gray-100">
    <div class="min-h-screen">
        <!-- Navigation -->
        <?php require_once 'template/header.php'; ?>
        
        <!-- Main Content -->
        <main class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
            <!-- Header -->
            <div class="md:flex md:items-center md:justify-between mb-8">
                <div class="flex-1 min-w-0">
                    <h2 class="text-2xl font-bold leading-7 text-gray-900 sm:text-3xl sm:truncate">
                        Add New Transaction
                    </h2>
                </div>
                <div class="mt-4 flex md:mt-0 md:ml-4">
                    <a href="transactions.php" class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                        <i class="fas fa-arrow-left mr-2"></i>
                        Back to Transactions
                    </a>
                </div>
            </div>

            <!-- Form Card -->
            <div class="bg-white shadow rounded-lg">
                <div class="px-4 py-5 sm:p-6">
                    <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="POST" enctype="multipart/form-data" class="space-y-6" id="transactionForm">
                        <div class="grid grid-cols-2 gap-6">
                            <!-- User Selection -->
                            <div>
                                <label for="user_id" class="block text-sm font-medium text-gray-700">Select User</label>
                                <select id="user_id" name="user_id" required
                                        class="mt-1 block w-full py-2 px-3 border border-gray-300 bg-white rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                                    <option value="">Select a user</option>
                                    <?php foreach ($users as $user): ?>
                                        <option value="<?php echo $user['id']; ?>" 
                                                <?php echo ($selected_user_id == $user['id']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($user['member_id'] . ' - ' . $user['full_name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <!-- Type -->
                            <div>
                                <label for="type" class="block text-sm font-medium text-gray-700">Transaction Type</label>
                                <select id="type" name="type" required
                                        class="focus:ring-indigo-500 focus:border-indigo-500 block w-full h-12 sm:text-sm border-2 border-gray-300 rounded-lg"
                                        autocomplete="off">
                                    <option value="">Select type</option>
                                    <option value="Purchase" selected>Purchase</option>
                                    <option value="Cash">Cash</option>
                                    <option value="QR">QR</option>
                                    <option value="Transfer">Transfer</option>
                                    <option value="Loan">Loan</option>
                                </select>
                            </div>

                            <!-- Amount -->
                            <div>
                                <label for="amount" class="block text-sm font-medium text-gray-700">Amount (RM)</label>
                                <div class="relative rounded-md shadow-sm">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <span class="text-gray-500 sm:text-sm">RM</span>
                                    </div>
                                    <input type="text" 
                                           name="amount" 
                                           id="amount" 
                                           required
                                           inputmode="numeric"
                                           autocomplete="off"
                                           class="focus:ring-indigo-500 focus:border-indigo-500 block w-full pl-12 h-12 sm:text-sm border-2 border-gray-300 rounded-lg"
                                           placeholder="Enter amount">
                                </div>
                            </div>

                            <!-- Date -->
                            <div>
                                <label for="date_transaction_display" class="block text-sm font-medium text-gray-700">Transaction Date</label>
                                <input type="text" 
                                       id="date_transaction_display" 
                                       required
                                       autocomplete="off"
                                       class="flatpickr focus:ring-indigo-500 focus:border-indigo-500 block w-full pl-4 pr-12 h-12 sm:text-sm border-2 border-gray-300 rounded-lg"
                                       placeholder="Select date and time">
                                <input type="hidden" id="date_transaction" name="date_transaction" autocomplete="off">
                            </div>

                            <!-- Description -->
                            <div class="col-span-2">
                                <label for="description" class="block text-sm font-medium text-gray-700">Description</label>
                                <textarea id="description" 
                                          name="description" 
                                          required
                                          autocomplete="off"
                                          class="focus:ring-indigo-500 focus:border-indigo-500 block w-full sm:text-sm border-2 border-gray-300 rounded-lg p-4"
                                          rows="3"
                                          placeholder="Enter transaction description"></textarea>
                            </div>

                            <!-- Image Upload -->
                            <div class="col-span-2">
                                <label for="image" class="block text-sm font-medium text-gray-700">Upload Image (Optional)</label>
                                <input type="file" 
                                       id="image" 
                                       name="image" 
                                       accept="image/*"
                                       autocomplete="off"
                                       class="focus:ring-indigo-500 focus:border-indigo-500 block w-full sm:text-sm border-2 border-gray-300 rounded-lg p-2">
                            </div>

                            <!-- Submit Button -->
                            <div class="col-span-2">
                                <button type="submit"
                                        class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                    Save Transaction
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </main>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
    $(document).ready(function() {
        // Success message handler with redirect
        <?php if (isset($_SESSION['success_message'])): ?>
            const successMessage = '<?php echo addslashes($_SESSION['success_message']); ?>';
            Swal.fire({
                icon: 'success',
                title: 'Success!',
                text: successMessage,
                confirmButtonText: 'OK',
                confirmButtonColor: '#4f46e5',
                allowOutsideClick: false,
                allowEscapeKey: false,
                focusConfirm: true,
                willClose: () => {
                    window.location.href = 'transactions.php';
                }
            });
            <?php unset($_SESSION['success_message']); ?>
        <?php endif; ?>

        // Initialize Select2
        $('#user_id').select2({
            placeholder: 'Select a user',
            allowClear: true
        });

        <?php if ($selected_user_id): ?>
        // If user is pre-selected, initialize Select2 with the value and focus on amount
        $('#user_id').val('<?php echo $selected_user_id; ?>').trigger('change');
        setTimeout(function() {
            $('#amount').focus();
        }, 100);
        <?php endif; ?>

        // Initialize Flatpickr for date input
        const fp = flatpickr("#date_transaction_display", {
            enableTime: true,
            dateFormat: "d M Y h:i K",
            defaultDate: new Date(),
            allowInput: true,
            time_24hr: false,
            onChange: function(selectedDates, dateStr, instance) {
                if (selectedDates[0]) {
                    // Format for MySQL (YYYY-MM-DD HH:mm:ss)
                    const mysqlDate = selectedDates[0].toISOString().slice(0, 19).replace('T', ' ');
                    document.getElementById('date_transaction').value = mysqlDate;
                }
            }
        });

        // Set initial date value
        const now = new Date();
        fp.setDate(now);
        document.getElementById('date_transaction').value = now.toISOString().slice(0, 19).replace('T', ' ');

        // Amount handling
        const amountInput = document.getElementById('amount');
        
        // Allow only numbers and one decimal point
        amountInput.addEventListener('keypress', function(e) {
            if (e.key === '.' && this.value.includes('.')) {
                e.preventDefault();
                return;
            }
            
            if (!/[\d.]/.test(e.key)) {
                e.preventDefault();
                return;
            }
        });

        // Clean input as user types
        amountInput.addEventListener('input', function(e) {
            let value = this.value;
            value = value.replace(/[^\d.]/g, '');
            
            const parts = value.split('.');
            if (parts.length > 2) {
                value = parts[0] + '.' + parts.slice(1).join('');
            }
            
            this.value = value;
        });

        // Format on blur
        amountInput.addEventListener('blur', function() {
            if (this.value) {
                const num = parseFloat(this.value);
                if (!isNaN(num)) {
                    this.value = num.toFixed(2);
                }
            }
        });

        // Form submission handler
        $('#transactionForm').on('submit', function(e) {
            e.preventDefault();

            // Format amount
            const amount = amountInput.value;
            if (amount) {
                const num = parseFloat(amount);
                if (!isNaN(num)) {
                    amountInput.value = num.toFixed(2);
                }
            }

            // Get form data
            const formData = new FormData(this);

            // Validate required fields
            const requiredFields = ['user_id', 'type', 'amount', 'date_transaction', 'description'];
            let isValid = true;
            let missingFields = [];
            
            requiredFields.forEach(field => {
                const element = document.getElementById(field);
                if (!element || !element.value.trim()) {
                    isValid = false;
                    missingFields.push(field);
                    if (element) {
                        element.classList.add('border-red-500');
                    }
                } else {
                    if (element) {
                        element.classList.remove('border-red-500');
                    }
                }
            });

            if (!isValid) {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Please fill in all required fields: ' + missingFields.join(', ')
                });
                return;
            }

            // Show loading state
            Swal.fire({
                title: 'Saving Transaction...',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            // Submit the form
            this.submit();
        });

        // Error message handler
        <?php if (isset($_SESSION['error_message'])): ?>
            Swal.fire({
                icon: 'error',
                title: 'Error!',
                text: '<?php echo $_SESSION['error_message']; ?>'
            });
            <?php unset($_SESSION['error_message']); ?>
        <?php endif; ?>
    });
</script>

<?php if (isset($_SESSION['error_message'])): ?>
    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
        <span class="block sm:inline"><?php echo $_SESSION['error_message']; ?></span>
    </div>
    <?php unset($_SESSION['error_message']); ?>
<?php endif; ?>

<?php require_once 'template/footer.php'; ?>