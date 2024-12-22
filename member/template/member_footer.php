<nav class="fixed bottom-0 left-0 right-0 bg-white shadow-lg">
    <div class="container mx-auto px-4 py-2 flex justify-around items-center max-w-full md:max-w-[650px]">
        <a href="member_dashboard.php" class="flex flex-col items-center px-3 py-2 text-sm font-medium <?php echo basename($_SERVER['PHP_SELF']) == 'member_dashboard.php' ? 'text-blue-600' : 'text-gray-500 hover:text-gray-700'; ?>">
            <div class="w-6 h-6 flex items-center justify-center mb-1">
                <i class="fas fa-home text-xl drop-shadow-md"></i>
            </div>
            <span class="text-xs">Home</span>
        </a>
        <a href="member_transactions.php" class="flex flex-col items-center px-3 py-2 text-sm font-medium <?php echo basename($_SERVER['PHP_SELF']) == 'member_transactions.php' ? 'text-blue-600' : 'text-gray-500 hover:text-gray-700'; ?>">
            <div class="w-6 h-6 flex items-center justify-center mb-1">
                <i class="fas fa-receipt text-xl drop-shadow-md"></i>
            </div>
            <span class="text-xs">History</span>
        </a>
        <a href="member_payment.php" class="flex flex-col items-center px-3 py-2 text-sm font-medium <?php echo basename($_SERVER['PHP_SELF']) == 'member_payment.php' ? 'text-blue-600' : 'text-gray-500 hover:text-gray-700'; ?>">
            <div class="w-6 h-6 flex items-center justify-center mb-1">
                <i class="fas fa-credit-card text-xl drop-shadow-md"></i>
            </div>
            <span class="text-xs">Payment</span>
        </a>
        <a href="#" class="flex flex-col items-center px-3 py-2 text-sm font-medium text-gray-500 hover:text-gray-700">
            <div class="w-6 h-6 flex items-center justify-center mb-1">
                <i class="fas fa-cog text-xl drop-shadow-md"></i>
            </div>
            <span class="text-xs">Setting</span>
        </a>
        <a href="/auth/logout.php" class="flex flex-col items-center px-3 py-2 text-sm font-medium text-gray-500 hover:text-gray-700">
            <div class="w-6 h-6 flex items-center justify-center mb-1">
                <i class="fas fa-sign-out-alt text-xl drop-shadow-md"></i>
            </div>
            <span class="text-xs">Logout</span>
        </a>
    </div>
</nav>
