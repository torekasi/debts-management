<?php
$current_page = basename($_SERVER['PHP_SELF']);
?>
<!-- Sidebar -->
<aside class="fixed inset-y-0 left-0 bg-white shadow-lg max-h-screen w-64">
    <div class="flex flex-col justify-between h-full">
        <div class="flex-grow">
            <div class="px-4 py-6 text-center border-b">
                <h1 class="text-xl font-bold leading-none"><span class="text-indigo-700">Debt</span> Manager</h1>
            </div>
            <div class="p-4">
                <ul class="space-y-1">
                    <li>
                        <a href="/admin/dashboard.php"
                           class="flex items-center bg-light hover:bg-indigo-100 rounded-xl font-bold text-sm text-gray-900 py-3 px-4 <?php echo $current_page === 'dashboard.php' ? 'bg-indigo-100' : ''; ?>">
                            <i class="fas fa-home w-6"></i>
                            <span>Dashboard</span>
                        </a>
                    </li>
                    <li>
                        <a href="/admin/users.php"
                           class="flex items-center hover:bg-indigo-100 rounded-xl font-bold text-sm text-gray-900 py-3 px-4 <?php echo $current_page === 'users.php' ? 'bg-indigo-100' : ''; ?>">
                            <i class="fas fa-users w-6"></i>
                            <span>Users</span>
                        </a>
                    </li>
                    <li>
                        <a href="/admin/payments.php"
                           class="flex items-center hover:bg-indigo-100 rounded-xl font-bold text-sm text-gray-900 py-3 px-4 <?php echo $current_page === 'payments.php' ? 'bg-indigo-100' : ''; ?>">
                            <i class="fas fa-money-check-alt w-6"></i>
                            <span>Payments</span>
                        </a>
                    </li>
                    <li>
                        <a href="/admin/transactions.php"
                           class="flex items-center hover:bg-indigo-100 rounded-xl font-bold text-sm text-gray-900 py-3 px-4 <?php echo $current_page === 'transactions.php' ? 'bg-indigo-100' : ''; ?>">
                            <i class="fas fa-exchange-alt w-6"></i>
                            <span>Transactions</span>
                        </a>
                    </li>
                    <li>
                        <a href="/admin/reports.php"
                           class="flex items-center hover:bg-indigo-100 rounded-xl font-bold text-sm text-gray-900 py-3 px-4 <?php echo $current_page === 'reports.php' ? 'bg-indigo-100' : ''; ?>">
                            <i class="fas fa-chart-bar w-6"></i>
                            <span>Reports</span>
                        </a>
                    </li>
                </ul>
            </div>
        </div>
        <div class="p-4 border-t">
            <div class="flex items-center space-x-4">
                <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($_SESSION['full_name']); ?>" class="w-10 h-10 rounded-full">
                <div>
                    <p class="font-bold text-sm text-gray-900"><?php echo htmlspecialchars($_SESSION['full_name']); ?></p>
                    <p class="text-xs text-gray-600">Administrator</p>
                </div>
            </div>
            <div class="mt-4">
                <a href="/auth/logout.php" class="flex items-center text-red-500 hover:text-red-600 text-sm">
                    <i class="fas fa-sign-out-alt w-6"></i>
                    <span>Logout</span>
                </a>
            </div>
        </div>
    </div>
</aside>

<!-- Mobile sidebar backdrop -->
<div class="fixed inset-0 z-20 transition-opacity bg-black opacity-50 lg:hidden hidden" id="sidebar-backdrop"></div>

<!-- Mobile menu button -->
<div class="fixed z-30 flex items-center space-x-4 top-4 right-4 lg:hidden">
    <button class="p-1 text-indigo-400 transition-colors duration-200 rounded-md bg-indigo-50 hover:text-indigo-600 hover:bg-indigo-100 dark:hover:text-light dark:hover:bg-indigo-700 dark:bg-dark focus:outline-none focus:ring" id="sidebar-button">
        <span class="sr-only">Toggle sidebar</span>
        <i class="fas fa-bars w-6 h-6"></i>
    </button>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const sidebar = document.querySelector('aside');
    const backdrop = document.getElementById('sidebar-backdrop');
    const toggleButton = document.getElementById('sidebar-button');
    
    function toggleSidebar() {
        sidebar.classList.toggle('-translate-x-full');
        backdrop.classList.toggle('hidden');
    }
    
    toggleButton.addEventListener('click', toggleSidebar);
    backdrop.addEventListener('click', toggleSidebar);
    
    // Add responsive classes
    sidebar.classList.add('transition', 'transform', 'lg:translate-x-0', 'duration-200', 'ease-in-out', 'z-30');
    
    // Initially hide sidebar on mobile
    if (window.innerWidth < 1024) {
        sidebar.classList.add('-translate-x-full');
    }
});
</script>
