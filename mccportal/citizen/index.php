<?php
// Note: This file should be included at the top of every citizen-facing page.
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Masvingo Connect</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap');
        body { font-family: 'Inter', sans-serif; background-color: #f3f4f6; }
    </style>
</head>
<body class="bg-gray-100 min-h-screen flex flex-col">

<header class="bg-white shadow-sm sticky top-0 z-50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between items-center h-16">
            <a href="../citizen/dashboard.php" class="flex-shrink-0 flex items-center">
                <span class="text-xl font-bold text-gray-800">MCC Portal</span>
            </a>
            <nav class="hidden md:flex space-x-8">
                <a href="../citizen/dashboard.php" class="text-gray-600 hover:text-gray-900 px-3 py-2 rounded-md font-medium transition-colors duration-200">Dashboard</a>
                <a href="../citizen/report_issue.php" class="text-gray-600 hover:text-gray-900 px-3 py-2 rounded-md font-medium transition-colors duration-200">Report Issue</a>
                <a href="../citizen/meter_reading.php" class="text-gray-600 hover:text-gray-900 px-3 py-2 rounded-md font-medium transition-colors duration-200">Meter Reading</a>
                <a href="../citizen/my_reports.php" class="text-gray-600 hover:text-gray-900 px-3 py-2 rounded-md font-medium transition-colors duration-200">My Reports</a>
                <a href="../citizen/profile.php" class="text-gray-600 hover:text-gray-900 px-3 py-2 rounded-md font-medium transition-colors duration-200">Profile</a>
            </nav>
            <div class="flex items-center space-x-4">
                <?php if (isset($_SESSION['user_id'])): ?>
                    <span class="text-gray-600 hidden md:block">Hello, <?= htmlspecialchars($_SESSION['full_name'] ?? 'User') ?></span>
                    <a href="../logout.php" class="text-red-600 hover:text-red-700 font-medium transition-colors duration-200">
                        <i class="fas fa-sign-out-alt mr-1"></i> Logout
                    </a>
                <?php else: ?>
                    <a href="../login.php" class="bg-blue-600 text-white px-4 py-2 rounded-lg font-medium hover:bg-blue-700 transition-colors duration-200 shadow-md">
                        Login / Register
                    </a>
                <?php endif; ?>
            </div>
            <div class="-mr-2 flex md:hidden">
                <button type="button" class="bg-white inline-flex items-center justify-center p-2 rounded-md text-gray-400 hover:text-gray-500 hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-blue-500" aria-expanded="false">
                    <span class="sr-only">Open main menu</span>
                    <svg class="h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                    </svg>
                </button>
            </div>
        </div>
    </div>
</header>

<main class="flex-grow">
    <div class="max-w-4xl mx-auto px-4 py-8">
