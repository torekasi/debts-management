<?php
session_start();
$base_path = dirname(dirname(__FILE__));
require_once $base_path . '/config/config.php';

$page_title = 'User Management';

try {
    $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=utf8mb4";
    $pdo = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false
    ]);

    // Handle user creation
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
        if ($_POST['action'] === 'create') {
            $member_id = $_POST['member_id'];
            $full_name = $_POST['full_name'];
            $email = $_POST['email'];
            $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
            $role = $_POST['role'];

            $stmt = $pdo->prepare("INSERT INTO users (member_id, full_name, email, password, role) VALUES (?, ?, ?, ?, ?)");
            if ($stmt->execute([$member_id, $full_name, $email, $password, $role])) {
                $_SESSION['success_message'] = "User created successfully!";
            } else {
                $_SESSION['error_message'] = "Failed to create user.";
            }
            header("Location: /admin/users.php");
            exit();
        }
    }

    // Fetch all users
    $users = $pdo->query("
        SELECT u.*, 
               COALESCE(SUM(CASE WHEN d.status = 'unpaid' THEN d.amount ELSE 0 END), 0) as total_debt,
               COUNT(DISTINCT d.id) as total_transactions,
               MAX(p.payment_date) as last_payment
        FROM users u
        LEFT JOIN debts d ON u.id = d.user_id
        LEFT JOIN payments p ON u.id = p.user_id
        WHERE u.role = 'user'
        GROUP BY u.id
        ORDER BY u.full_name ASC
    ")->fetchAll();

} catch (PDOException $e) {
    error_log("Users page error: " . $e->getMessage());
    $_SESSION['error_message'] = "An error occurred while loading the users page.";
    header("Location: /admin/dashboard.php");
    exit();
}

require_once 'templates/header.php';
?>

<!-- Create User Modal -->
<div id="createUserModal" class="fixed inset-0 bg-gray-500 bg-opacity-75 flex items-center justify-center <?php echo isset($_GET['action']) && $_GET['action'] === 'create' ? '' : 'hidden'; ?>">
    <div class="bg-white rounded-lg shadow-xl max-w-md w-full">
        <div class="px-6 py-4 border-b border-gray-200 flex justify-between items-center">
            <h3 class="text-lg font-medium text-gray-900">Create New User</h3>
            <button onclick="closeModal()" class="text-gray-400 hover:text-gray-500">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <form action="/admin/users.php" method="POST" class="p-6">
            <input type="hidden" name="action" value="create">
            
            <div class="space-y-4">
                <div>
                    <label for="member_id" class="block text-sm font-medium text-gray-700">Member ID</label>
                    <input type="text" name="member_id" id="member_id" required
                           class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                </div>

                <div>
                    <label for="full_name" class="block text-sm font-medium text-gray-700">Full Name</label>
                    <input type="text" name="full_name" id="full_name" required
                           class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                </div>

                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700">Email</label>
                    <input type="email" name="email" id="email" required
                           class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                </div>

                <div>
                    <label for="password" class="block text-sm font-medium text-gray-700">Password</label>
                    <input type="password" name="password" id="password" required
                           class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                </div>

                <div>
                    <label for="role" class="block text-sm font-medium text-gray-700">Role</label>
                    <select name="role" id="role" required
                            class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                        <option value="user">User</option>
                        <option value="admin">Admin</option>
                    </select>
                </div>
            </div>

            <div class="mt-6 flex justify-end space-x-3">
                <button type="button" onclick="closeModal()"
                        class="px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                    Cancel
                </button>
                <button type="submit"
                        class="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700">
                    Create User
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Main Content -->
<div class="space-y-6">
    <!-- Header -->
    <div class="flex justify-between items-center">
        <h1 class="text-2xl font-semibold text-gray-900">User Management</h1>
        <a href="/admin/users.php?action=create" 
           class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700">
            <i class="fas fa-user-plus mr-2"></i>
            Add New User
        </a>
    </div>

    <!-- Users Table -->
    <div class="bg-white shadow rounded-lg">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead>
                    <tr>
                        <th class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Member ID</th>
                        <th class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                        <th class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
                        <th class="px-6 py-3 bg-gray-50 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Outstanding</th>
                        <th class="px-6 py-3 bg-gray-50 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Transactions</th>
                        <th class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Last Payment</th>
                        <th class="px-6 py-3 bg-gray-50 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php foreach ($users as $user): ?>
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                            <?php echo htmlspecialchars($user['member_id']); ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            <?php echo htmlspecialchars($user['full_name']); ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            <?php echo htmlspecialchars($user['email']); ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-right text-gray-900">
                            ₱<?php echo number_format($user['total_debt'], 2); ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-center text-gray-500">
                            <?php echo $user['total_transactions']; ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            <?php echo $user['last_payment'] ? date('M d, Y', strtotime($user['last_payment'])) : 'No payments'; ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-center">
                            <a href="/admin/payments.php?user_id=<?php echo $user['id']; ?>" class="text-indigo-600 hover:text-indigo-900 mr-3">
                                <i class="fas fa-money-check-alt"></i>
                            </a>
                            <a href="/admin/transactions.php?user_id=<?php echo $user['id']; ?>" class="text-gray-600 hover:text-gray-900">
                                <i class="fas fa-history"></i>
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
function closeModal() {
    document.getElementById('createUserModal').classList.add('hidden');
    // Update URL without refreshing
    history.pushState({}, '', '/admin/users.php');
}
</script>

<?php require_once 'templates/footer.php'; ?>
