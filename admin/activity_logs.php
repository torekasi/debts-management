<?php
require_once '../includes/config.php';
require_once '../includes/session.php';
requireAdmin();

// Initial load - first 20 records
$items_per_load = 20;
$page = 1;

// Get total count of logs
$stmt = $conn->query("SELECT COUNT(*) FROM activity_logs");
$total_logs = $stmt->fetchColumn();

// Get initial logs
$stmt = $conn->prepare("
    SELECT al.*, u.full_name, u.member_id
    FROM activity_logs al
    LEFT JOIN users u ON al.user_id = u.id
    ORDER BY al.created_at DESC
    LIMIT ?
");
$stmt->execute([$items_per_load]);
$logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

include 'template/header.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Activity Logs - <?php echo APP_NAME; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .log-description {
            max-width: 800px;
            white-space: normal;
            word-wrap: break-word;
        }
        .admin-action {
            color: #4F46E5;
            font-weight: 500;
        }
    </style>
</head>
<body class="bg-gray-100">
    <div class="min-h-screen">
        <main class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
            <!-- Page header -->
            <div class="mb-6">
                <h1 class="text-2xl font-semibold text-gray-900">Activity Logs</h1>
                <p class="mt-2 text-sm text-gray-700">Showing system activity logs and user actions</p>
            </div>

            <!-- Activity Logs Table -->
            <div class="bg-white shadow rounded-lg">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date & Time</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Action</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Description</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">IP Address</th>
                            </tr>
                        </thead>
                        <tbody id="logsTableBody" class="bg-white divide-y divide-gray-200">
                            <?php foreach ($logs as $log): ?>
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <?php echo date('d M Y | h:i A', strtotime($log['created_at'])); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                    <?php echo htmlspecialchars($log['action']); ?>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-500 log-description">
                                    <?php 
                                    $description = htmlspecialchars($log['description']);
                                    // Highlight admin actions in the description
                                    if (preg_match('/\[Action by Admin:.*?\]/', $description, $matches)) {
                                        $adminAction = $matches[0];
                                        $description = str_replace(
                                            $adminAction,
                                            '<span class="admin-action">' . $adminAction . '</span>',
                                            $description
                                        );
                                    }
                                    echo $description;
                                    ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <?php echo htmlspecialchars($log['ip_address']); ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Load More Button -->
                <?php if (count($logs) < $total_logs): ?>
                <div class="px-6 py-4 border-t border-gray-200">
                    <button id="loadMoreBtn" 
                            class="w-full px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                            data-page="1"
                            onclick="loadMoreLogs()">
                        Load More
                    </button>
                </div>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <script>
        let isLoading = false;

        function loadMoreLogs() {
            if (isLoading) return;
            isLoading = true;

            const button = document.getElementById('loadMoreBtn');
            const currentPage = parseInt(button.dataset.page) + 1;
            
            // Show loading state
            button.innerHTML = 'Loading...';
            button.disabled = true;

            // Make AJAX request
            fetch(`load_more_logs.php?page=${currentPage}`)
                .then(response => response.json())
                .then(data => {
                    if (data.logs.length > 0) {
                        // Append new logs to the table
                        const tableBody = document.getElementById('logsTableBody');
                        data.logs.forEach(log => {
                            const row = createLogRow(log);
                            tableBody.insertAdjacentHTML('beforeend', row);
                        });

                        // Update button state
                        button.dataset.page = currentPage;
                        button.innerHTML = 'Load More';
                        button.disabled = false;

                        // Hide button if no more logs
                        if (data.isLastPage) {
                            button.parentElement.style.display = 'none';
                        }
                    } else {
                        button.parentElement.style.display = 'none';
                    }
                    isLoading = false;
                })
                .catch(error => {
                    console.error('Error:', error);
                    button.innerHTML = 'Error loading more logs. Click to try again.';
                    button.disabled = false;
                    isLoading = false;
                });
        }

        function createLogRow(log) {
            const date = new Date(log.created_at).toLocaleDateString('en-US', {
                day: '2-digit',
                month: 'short',
                year: 'numeric',
                hour: '2-digit',
                minute: '2-digit',
                hour12: true
            });

            let description = log.description;
            // Highlight admin actions in the description
            const adminActionMatch = description.match(/\[Action by Admin:.*?\]/);
            if (adminActionMatch) {
                description = description.replace(
                    adminActionMatch[0],
                    `<span class="admin-action">${adminActionMatch[0]}</span>`
                );
            }

            return `
                <tr class="hover:bg-gray-50">
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                        ${date}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                        ${log.action}
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-500 log-description">
                        ${description}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                        ${log.ip_address}
                    </td>
                </tr>
            `;
        }
    </script>
</body>
</html>

<?php include 'template/footer.php'; ?> 