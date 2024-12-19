<?php require_once 'config/config.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>404 - Page Not Found</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">
    <div class="min-h-screen flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-md w-full text-center">
            <h1 class="text-9xl font-bold text-indigo-600">404</h1>
            <h2 class="mt-4 text-3xl font-bold text-gray-900">Page Not Found</h2>
            <p class="mt-2 text-gray-600">The page you're looking for doesn't exist or has been moved.</p>
            <div class="mt-6">
                <a href="/" class="text-base font-medium text-indigo-600 hover:text-indigo-500">
                    Go back home<span aria-hidden="true"> &rarr;</span>
                </a>
            </div>
        </div>
    </div>
</body>
</html>
