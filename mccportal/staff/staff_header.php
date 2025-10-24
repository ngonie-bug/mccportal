<?php
// Start the session at the very beginning of the file. This is crucial for accessing
// any session variables, like $_SESSION['user_id'] and $_SESSION['full_name'].
// Check if the user is logged in and has a staff-level role
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'], ['staff', 'it_help_desk', 'auditor'])) {
    header("Location: ../admin_login.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Staff Dashboard | Masvingo Connect</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap');
        body { font-family: 'Inter', sans-serif; background-color: #f3f4f6; }
    </style>
</head>
<body class="bg-gray-100">

<nav class="bg-blue-600 shadow-lg">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between items-center h-16">
            <div class="flex items-center">
                <a href="staff_dashboard.php" class="text-white text-2xl font-bold tracking-wide">
                    MCC Staff
                </a>
            </div>
            <div class="flex items-center space-x-4">
                <a href="staff_dashboard.php" class="text-white hover:bg-blue-700 px-3 py-2 rounded-md text-sm font-medium transition-colors duration-200">Dashboard</a>
                <a href="staff_reports.php" class="text-white hover:bg-blue-700 px-3 py-2 rounded-md text-sm font-medium transition-colors duration-200">View Reports</a>
                <a href="manage_chat.php" class="text-white hover:bg-blue-700 px-3 py-2 rounded-md text-sm font-medium transition-colors duration-200">Chat</a>
                <a href="staff_analytics.php" class="text-white hover:bg-blue-700 px-3 py-2 rounded-md text-sm font-medium transition-colors duration-200">Analytics</a>
            </div>
            <div class="flex items-center space-x-4">
                <span class="text-white text-sm font-medium">Hello, <?= htmlspecialchars($_SESSION['full_name'] ?? '') ?></span>
                <a href="../logout.php" class="bg-red-500 text-white px-3 py-1 rounded-md text-sm font-medium hover:bg-red-600 transition-colors duration-200">Logout</a>
            </div>
        </div>
    </div>
</nav>

<div class="max-w-7xl mx-auto mt-8 px-4 sm:px-6 lg:px-8">
<!-- Main content container -->
