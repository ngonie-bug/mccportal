<?php
session_start();
include_once '../includes/config.php';

// Check if user is already logged in and redirect to appropriate dashboard
if (isset($_SESSION['user_id']) && isset($_SESSION['user_role'])) {
    if ($_SESSION['user_role'] === 'admin' || $_SESSION['user_role'] === 'auditor') {
        header('Location: dashboard.php');
        exit();
    } elseif ($_SESSION['user_role'] === 'staff' || $_SESSION['user_role'] === 'it_help_desk') {
        header('Location: ../staff/dashboard.php');
        exit();
    }
}

// If not logged in, show welcome page with login option
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Portal | Masvingo Connect</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap');
        body { font-family: 'Inter', sans-serif; }
        .gradient-bg { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
    </style>
</head>
<body class="bg-gray-100 min-h-screen">
     Added hero section with welcome message and navigation 
    <div class="gradient-bg text-white">
        <div class="container mx-auto px-6 py-16">
            <div class="text-center">
                <h1 class="text-5xl font-bold mb-4">Masvingo Connect</h1>
                <p class="text-xl mb-8 opacity-90">Administrative Portal</p>
                <div class="flex justify-center space-x-4">
                    <a href="admin_login.php" class="bg-white text-blue-600 px-8 py-3 rounded-lg font-semibold hover:bg-gray-100 transition-colors duration-200 flex items-center">
                        <i class="fas fa-sign-in-alt mr-2"></i> Staff & Admin Login
                    </a>
                    <a href="../login.php" class="bg-transparent border-2 border-white text-white px-8 py-3 rounded-lg font-semibold hover:bg-white hover:text-blue-600 transition-colors duration-200 flex items-center">
                        <i class="fas fa-user mr-2"></i> Citizen Login
                    </a>
                </div>
            </div>
        </div>
    </div>

     Added features section showcasing admin capabilities 
    <div class="container mx-auto px-6 py-16">
        <div class="text-center mb-12">
            <h2 class="text-3xl font-bold text-gray-800 mb-4">Administrative Features</h2>
            <p class="text-gray-600 max-w-2xl mx-auto">Comprehensive tools for managing citizens, staff, reports, and municipal operations</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
             User Management 
            <div class="bg-white p-6 rounded-lg shadow-md hover:shadow-lg transition-shadow duration-200">
                <div class="text-blue-500 mb-4">
                    <i class="fas fa-users fa-3x"></i>
                </div>
                <h3 class="text-xl font-semibold text-gray-800 mb-2">User Management</h3>
                <p class="text-gray-600">Manage citizens, staff members, and administrative accounts with role-based access control.</p>
            </div>

             Report Management 
            <div class="bg-white p-6 rounded-lg shadow-md hover:shadow-lg transition-shadow duration-200">
                <div class="text-green-500 mb-4">
                    <i class="fas fa-clipboard-list fa-3x"></i>
                </div>
                <h3 class="text-xl font-semibold text-gray-800 mb-2">Report Management</h3>
                <p class="text-gray-600">Track, review, and manage citizen reports and municipal service requests efficiently.</p>
            </div>

             Analytics 
            <div class="bg-white p-6 rounded-lg shadow-md hover:shadow-lg transition-shadow duration-200">
                <div class="text-purple-500 mb-4">
                    <i class="fas fa-chart-line fa-3x"></i>
                </div>
                <h3 class="text-xl font-semibold text-gray-800 mb-2">Analytics & Insights</h3>
                <p class="text-gray-600">View comprehensive analytics and generate reports on municipal operations and citizen engagement.</p>
            </div>

             Department Management 
            <div class="bg-white p-6 rounded-lg shadow-md hover:shadow-lg transition-shadow duration-200">
                <div class="text-yellow-500 mb-4">
                    <i class="fas fa-building fa-3x"></i>
                </div>
                <h3 class="text-xl font-semibold text-gray-800 mb-2">Department Management</h3>
                <p class="text-gray-600">Organize and manage different municipal departments and their respective staff members.</p>
            </div>

             Security & Audit 
            <div class="bg-white p-6 rounded-lg shadow-md hover:shadow-lg transition-shadow duration-200">
                <div class="text-red-500 mb-4">
                    <i class="fas fa-shield-alt fa-3x"></i>
                </div>
                <h3 class="text-xl font-semibold text-gray-800 mb-2">Security & Audit</h3>
                <p class="text-gray-600">Monitor system security, track user activities, and maintain comprehensive audit trails.</p>
            </div>

             Communication 
            <div class="bg-white p-6 rounded-lg shadow-md hover:shadow-lg transition-shadow duration-200">
                <div class="text-indigo-500 mb-4">
                    <i class="fas fa-comments fa-3x"></i>
                </div>
                <h3 class="text-xl font-semibold text-gray-800 mb-2">Communication Hub</h3>
                <p class="text-gray-600">Facilitate communication between citizens, staff, and different municipal departments.</p>
            </div>
        </div>
    </div>

     Added footer with system information 
    <footer class="bg-gray-800 text-white py-8">
        <div class="container mx-auto px-6 text-center">
            <p class="mb-2">&copy; <?= date('Y') ?> Masvingo Connect - Municipal Management System</p>
            <p class="text-gray-400 text-sm">Secure • Efficient • Transparent</p>
        </div>
    </footer>
</body>
</html>
