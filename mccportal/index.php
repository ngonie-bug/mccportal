<?php
require_once 'includes/config.php';

// Fetch the latest notices.
$notices_query = "SELECT * FROM notices ORDER BY created_at DESC LIMIT 3";
$notices_result = $conn->query($notices_query);
$notices = [];
if ($notices_result && $notices_result->num_rows > 0) {
    while ($row = $notices_result->fetch_assoc()) {
        $notices[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Masvingo City Council - Citizen Portal</title>
    <!-- Inter Font -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#2563eb',
                        'primary-dark': '#1d4ed8',
                        secondary: '#10b981',
                        accent: '#8b5cf6',
                        warning: '#f59e0b',
                        dark: '#1e293b',
                        light: '#3c77b2ff'
                    }
                }
            }
        }
    </script>
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #6fc7e4ff 0%, #3c5964ff 100%);
            color: #3e66a8ff;
            min-height: 100vh;
        }
        
        /* Enhanced card hover effect */
        .card-hover {
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        
        .card-hover:hover {
            transform: translateY(-8px);
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
        }
        
        .card-hover::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.4), transparent);
            transition: left 0.7s ease;
        }
        
        .card-hover:hover::before {
            left: 100%;
        }
        
        /* Logo animation */
        .logo-container {
            transition: all 0.5s cubic-bezier(0.68, -0.55, 0.27, 1.55);
        }
        
        .logo-container:hover {
            transform: rotate(10deg) scale(1.1);
        }
        
        /* Feature icon pulse animation */
        .feature-icon {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            background: rgba(37, 99, 235, 0.1);
            color: #2563eb;
            transition: all 0.3s ease;
        }
        
        .pulse {
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0% {
                box-shadow: 0 0 0 0 rgba(37, 99, 235, 0.4);
            }
            70% {
                box-shadow: 0 0 0 10px rgba(37, 99, 235, 0);
            }
            100% {
                box-shadow: 0 0 0 0 rgba(37, 99, 235, 0);
            }
        }
        
        /* Typewriter effect */
        .typewriter {
            overflow: hidden;
            border-right: .15em solid #2563eb;
            white-space: nowrap;
            margin: 0 auto;
            letter-spacing: .05em;
            animation: typing 3.5s steps(40, end), blink-caret .75s step-end infinite;
        }
        
        @keyframes typing {
            from { width: 0 }
            to { width: 100% }
        }
        
        @keyframes blink-caret {
            from, to { border-color: transparent }
            50% { border-color: #2563eb }
        }
        
        /* News card expansion */
        .news-card {
            transition: all 0.4s ease;
            cursor: pointer;
            max-height: 120px;
            overflow: hidden;
        }
        
        .news-card.expanded {
            max-height: 500px;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
        }
        
        /* Stats counter */
        .stat-number {
            font-size: 1.5rem;
            font-weight: 700;
            color: #2563eb;
        }
        
        /* Button hover effect */
        .btn-hover {
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
            z-index: 1;
        }
        
        .btn-hover:after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: inherit;
            border-radius: inherit;
            z-index: -2;
        }
        
        .btn-hover:before {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 0%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
            border-radius: inherit;
            z-index: -1;
        }
        
        .btn-hover:hover:before {
            width: 100%;
        }
        
        /* Tooltip styling */
        .tooltip {
            position: relative;
        }
        
        .tooltip .tooltiptext {
            visibility: hidden;
            width: 120px;
            background-color: #1e293b;
            color: #ffffffff;
            text-align: center;
            border-radius: 6px;
            padding: 5px;
            position: absolute;
            z-index: 1;
            bottom: 125%;
            left: 50%;
            margin-left: -60px;
            opacity: 0;
            transition: opacity 0.3s;
            font-size: 0.875rem;
        }
        
        .tooltip .tooltiptext::after {
            content: "";
            position: absolute;
            top: 100%;
            left: 50%;
            margin-left: -5px;
            border-width: 5px;
            border-style: solid;
            border-color: #1e293b transparent transparent transparent;
        }
        
        .tooltip:hover .tooltiptext {
            visibility: visible;
            opacity: 1;
        }
        
        /* Floating elements */
        .floating {
            animation: float 6s ease-in-out infinite;
        }
        
        @keyframes float {
            0% { transform: translateY(0px); }
            50% { transform: translateY(-10px); }
            100% { transform: translateY(0px); }
        }
        
        /* Dark mode toggle */
        .dark-mode-toggle {
            position: fixed;
            bottom: 20px;
            right: 20px;
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: #1e293b;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            z-index: 100;
            transition: all 0.3s ease;
        }
        
        body.dark-mode {
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
            color: #f8fafc;
        }
        
        body.dark-mode .bg-white {
            background-color: #1e293b;
        }
        
        body.dark-mode .text-gray-600 {
            color: #cbd5e1;
        }
        
        body.dark-mode .text-gray-900 {
            color: #f8fafc;
        }
        
        body.dark-mode .bg-gray-50 {
            background-color: #334155;
        }
        
        body.dark-mode .border-gray-100 {
            border-color: #475569;
        }
        
        body.dark-mode .bg-blue-50 {
            background-color: rgba(59, 130, 246, 0.1);
        }
        
        body.dark-mode .bg-green-50 {
            background-color: rgba(16, 185, 129, 0.1);
        }
        
        body.dark-mode .bg-purple-50 {
            background-color: rgba(139, 92, 246, 0.1);
        }
        
        /* Mobile menu */
        .mobile-menu-btn {
            display: none;
        }
        
        @media (max-width: 768px) {
            .mobile-menu-btn {
                display: block;
            }
            
            .header-links {
                display: none;
                position: absolute;
                top: 100%;
                right: 0;
                width: 100%;
                background: white;
                padding: 1rem;
                box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            }
            
            .header-links.active {
                display: block;
            }
            
            body.dark-mode .header-links {
                background: #1e293b;
            }
        }
    </style>
</head>
<body class="min-h-screen flex items-center justify-center p-4 transition-colors duration-300">
    <!-- Dark Mode Toggle -->
    <div class="dark-mode-toggle" id="darkModeToggle">
        <i class="fas fa-moon"></i>
    </div>
    
    <div class="container mx-auto max-w-6xl">
        <!-- Header -->
        <header class="flex justify-between items-center py-6 mb-8 relative">
            <div class="flex items-center">
                <div class="logo-container w-16 h-16 rounded-lg bg-white flex items-center justify-center mr-4 shadow-md floating">
                    <img src="images/download.jpg" alt="MCC Logo" class="h-10 w-10">
                </div>
                <h1 class="text-2xl font-bold text-dark dark:text-white">Masvingo City Council</h1>
            </div>
            
            <div class="header-links">
                <a href="#" class="tooltip text-primary hover:text-primary-dark font-medium">
                    Contact Us
                    <span class="tooltiptext">Get in touch with us</span>
                </a>
            </div>
            
            <div class="mobile-menu-btn text-dark dark:text-white">
                <i class="fas fa-bars text-2xl"></i>
            </div>
        </header>

        <!-- Main Content -->
        <div class="bg-white dark:bg-dark rounded-2xl shadow-2xl overflow-hidden transition-colors duration-300">
            <div class="md:flex">
                <!-- Left Column -->
                <div class="md:w-1/2 bg-gradient-to-br from-primary to-primary-dark p-8 md:p-12 text-white">
                    <h2 class="text-3xl md:text-4xl font-bold mb-6">Citizen Service Portal</h2>
                    <div class="typewriter text-xl font-semibold mb-8">Transparent. Efficient. Connected.</div>
                    
                    <div class="mb-10">
                        <h3 class="text-xl font-semibold mb-4">Why use our portal?</h3>
                        <ul class="space-y-3">
                            <li class="flex items-start">
                                <i class="fas fa-check-circle text-secondary mt-1 mr-3"></i>
                                <span>Quick reporting of community issues</span>
                            </li>
                            <li class="flex items-start">
                                <i class="fas fa-check-circle text-secondary mt-1 mr-3"></i>
                                <span>Real-time tracking of your requests</span>
                            </li>
                            <li class="flex items-start">
                                <i class="fas fa-check-circle text-secondary mt-1 mr-3"></i>
                                <span>Direct communication with city officials</span>
                            </li>
                            <li class="flex items-start">
                                <i class="fas fa-check-circle text-secondary mt-1 mr-3"></i>
                                <span>Convenient bill payments and meter readings</span>
                            </li>
                        </ul>
                    </div>
                </div>
                
                <!-- Right Column -->
                <div class="md:w-1/2 p-8 md:p-12">
                    <h1 class="text-3xl md:text-4xl font-extrabold text-gray-900 dark:text-white leading-tight mb-4 text-center md:text-left">
                        Welcome to the Citizen Portal
                    </h1>
                    <p class="text-lg text-gray-600 dark:text-gray-300 mb-8 text-center md:text-left">
                        Your gateway to seamless municipal services and community engagement.
                    </p>

                    <div class="grid gap-6 mb-10">
                        <!-- Citizen Card -->
                        <div class="bg-gray-50 dark:bg-gray-700 rounded-xl p-6 shadow-md card-hover border border-gray-100 dark:border-gray-600 hover:border-primary transition-all duration-300">
                            <div class="flex items-start">
                                <div class="feature-icon pulse">
                                    <i class="fas fa-users"></i>
                                </div>
                                <div class="ml-6">
                                    <h2 class="text-2xl font-bold text-primary mb-2">Citizen Login</h2>
                                    <p class="text-gray-600 dark:text-gray-300 mb-4">
                                        Report issues, track request status, submit meter readings, and manage your municipal account.
                                    </p>
                                    <a href="citizen/login.php" class="inline-flex items-center px-5 py-3 text-base font-medium text-white bg-primary hover:bg-primary-dark rounded-lg shadow-md btn-hover transition-colors duration-200">
                                        Login as Citizen
                                        <i class="fas fa-arrow-right ml-2 group-hover:translate-x-1 transition-transform"></i>
                                    </a>
                                </div>
                            </div>
                        </div>

                        <!-- Staff & Admin Card -->
                        <div class="bg-gray-50 dark:bg-gray-700 rounded-xl p-6 shadow-md card-hover border border-gray-100 dark:border-gray-600 hover:border-purple-500 transition-all duration-300">
                            <div class="flex items-start">
                                <div class="feature-icon bg-purple-100 text-purple-600 pulse" style="background: rgba(139, 92, 246, 0.1); color: #8b5cf6;">
                                    <i class="fas fa-user-shield"></i>
                                </div>
                                <div class="ml-6">
                                    <h2 class="text-2xl font-bold text-purple-600 mb-2">Staff & Admin</h2>
                                    <p class="text-gray-600 dark:text-gray-300 mb-4">
                                        Manage reports, assign tasks, monitor system activity, and oversee municipal operations.
                                    </p>
                                    <a href="login.php" class="inline-flex items-center px-5 py-3 text-base font-medium text-white bg-purple-500 hover:bg-purple-600 rounded-lg shadow-md btn-hover transition-colors duration-200">
                                        Staff Login
                                        <i class="fas fa-arrow-right ml-2 group-hover:translate-x-1 transition-transform"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Stats Section -->
                    <div class="grid grid-cols-3 gap-4 text-center mb-8">
                        <div class="bg-blue-50 dark:bg-blue-900/20 p-4 rounded-lg hover:bg-blue-100 dark:hover:bg-blue-900/30 transition-colors duration-300 cursor-pointer">
                            <div class="stat-number" id="issues-counter">0</div>
                            <div class="text-sm text-gray-600 dark:text-gray-400">Issues Resolved</div>
                        </div>
                        <div class="bg-green-50 dark:bg-green-900/20 p-4 rounded-lg hover:bg-green-100 dark:hover:bg-green-900/30 transition-colors duration-300 cursor-pointer">
                            <div class="stat-number" id="users-counter">0</div>
                            <div class="text-sm text-gray-600 dark:text-gray-400">Active Users</div>
                        </div>
                        <div class="bg-purple-50 dark:bg-purple-900/20 p-4 rounded-lg hover:bg-purple-100 dark:hover:bg-purple-900/30 transition-colors duration-300 cursor-pointer">
                            <div class="stat-number" id="satisfaction-counter">0%</div>
                            <div class="text-sm text-gray-600 dark:text-gray-400">Satisfaction Rate</div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- News Section -->
            <div class="border-t border-gray-200 dark:border-gray-700">
                <div class="p-8 md:p-12">
                    <h2 class="text-2xl font-bold text-dark dark:text-white mb-6">Latest Updates</h2>
                    <div class="grid md:grid-cols-3 gap-6" id="news-container">
                        <?php if (!empty($notices)): ?>
                            <?php foreach ($notices as $notice): ?>
                                <div class="news-card bg-gray-50 dark:bg-gray-700 rounded-lg p-5 border border-gray-100 dark:border-gray-600" onclick="toggleNewsCard(this)">
                                    <div class="text-sm text-primary font-semibold mb-2">
                                        <?php echo date('F j, Y', strtotime($notice['created_at'])); ?>
                                    </div>
                                    <h3 class="font-bold mb-2 dark:text-white"><?php echo htmlspecialchars($notice['title']); ?></h3>
                                    <div class="news-content">
                                        <p class="text-sm text-gray-600 dark:text-gray-300"><?php echo htmlspecialchars($notice['content']); ?></p>
                                    </div>
                                    <div class="text-right mt-2 text-primary text-xs">
                                        <i class="fas fa-chevron-down"></i> Click to expand
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <!-- Fallback content when no notices exist -->
                            <div class="news-card bg-gray-50 dark:bg-gray-700 rounded-lg p-5 border border-gray-100 dark:border-gray-600">
                                <div class="text-sm text-primary font-semibold mb-2">No Updates</div>
                                <h3 class="font-bold mb-2 dark:text-white">Stay Tuned</h3>
                                <p class="text-sm text-gray-600 dark:text-gray-300">Check back later for the latest updates from the city council.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- Footer -->
            <footer class="bg-dark text-white p-8">
                <div class="grid md:grid-cols-3 gap-8">
                    <div>
                        <h3 class="text-lg font-semibold mb-4">Masvingo City Council</h3>
                        <p class="text-gray-400">Serving the community with transparency and efficiency since 1980.</p>
                    </div>
                    <div>
                        <h3 class="text-lg font-semibold mb-4">Quick Links</h3>
                        <ul class="space-y-2">
                            <li><a href="#" class="text-gray-400 hover:text-white transition-colors">Services</a></li>
                            <li><a href="#" class="text-gray-400 hover:text-white transition-colors">Department Contacts</a></li>
                            <li><a href="#" class="text-gray-400 hover:text-white transition-colors">FAQ</a></li>
                        </ul>
                    </div>
                    <div>
                        <h3 class="text-lg font-semibold mb-4">Contact Us</h3>
                        <p class="text-gray-400">+263 123 456 789</p>
                        <p class="text-gray-400">info@masvingo.gov.zw</p>
                    </div>
                </div>
                <div class="border-t border-gray-700 mt-8 pt-6 text-center text-gray-400">
                    <p>&copy; 2025 Masvingo City Council. All rights reserved.</p>
                </div>
            </footer>
        </div>
    </div>

    <script>
        // Simple animation for stats counter
        document.addEventListener('DOMContentLoaded', function() {
            const counters = [
                { element: document.getElementById('issues-counter'), target: 2548, duration: 2000 },
                { element: document.getElementById('users-counter'), target: 15672, duration: 2500 },
                { element: document.getElementById('satisfaction-counter'), target: 92, duration: 1500, isPercentage: true }
            ];
            
            counters.forEach(counter => {
                let current = 0;
                const increment = counter.target / (counter.duration / 16);
                const timer = setInterval(() => {
                    current += increment;
                    if (current >= counter.target) {
                        clearInterval(timer);
                        counter.element.textContent = counter.isPercentage ? 
                            counter.target + '%' : 
                            Math.round(counter.target).toLocaleString();
                    } else {
                        counter.element.textContent = counter.isPercentage ? 
                            Math.round(current) + '%' : 
                            Math.round(current).toLocaleString();
                    }
                }, 16);
            });
            
            // Dark mode toggle functionality
            const darkModeToggle = document.getElementById('darkModeToggle');
            const body = document.body;
            
            // Check for saved dark mode preference
            if (localStorage.getItem('darkMode') === 'enabled') {
                body.classList.add('dark-mode');
                darkModeToggle.innerHTML = '<i class="fas fa-sun"></i>';
            }
            
            darkModeToggle.addEventListener('click', () => {
                body.classList.toggle('dark-mode');
                
                if (body.classList.contains('dark-mode')) {
                    localStorage.setItem('darkMode', 'enabled');
                    darkModeToggle.innerHTML = '<i class="fas fa-sun"></i>';
                } else {
                    localStorage.setItem('darkMode', 'disabled');
                    darkModeToggle.innerHTML = '<i class="fas fa-moon"></i>';
                }
            });
            
            // Mobile menu toggle
            const mobileMenuBtn = document.querySelector('.mobile-menu-btn');
            const headerLinks = document.querySelector('.header-links');
            
            mobileMenuBtn.addEventListener('click', () => {
                headerLinks.classList.toggle('active');
            });
        });
        
        // Toggle news card expansion
        function toggleNewsCard(card) {
            const isExpanded = card.classList.contains('expanded');
            document.querySelectorAll('.news-card.expanded').forEach(item => {
                if (item !== card) {
                    item.classList.remove('expanded');
                    item.querySelector('.fa-chevron-down').classList.remove('fa-chevron-up');
                    item.querySelector('.fa-chevron-down').classList.add('fa-chevron-down');
                }
            });
            
            if (!isExpanded) {
                card.classList.add('expanded');
                const icon = card.querySelector('.fa-chevron-down');
                icon.classList.remove('fa-chevron-down');
                icon.classList.add('fa-chevron-up');
            } else {
                card.classList.remove('expanded');
                const icon = card.querySelector('.fa-chevron-up');
                icon.classList.remove('fa-chevron-up');
                icon.classList.add('fa-chevron-down');
            }
        }
    </script>
</body>
</html>