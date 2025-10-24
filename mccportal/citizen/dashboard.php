<?php
// This file is the main dashboard for the logged-in citizen.
session_start();
include_once '../includes/config.php';

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    // Note: The correct path for the login page is relative to this file.
    // Assuming login.php is one level up from the 'citizen' folder.
    header("Location: ../login.php");
    exit();
}

// Fetch user's full name from the database to personalize the greeting
$user_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT full_name FROM users WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

$stmt = $conn->prepare("SELECT COUNT(*) as unread_count FROM notifications WHERE user_id = ? AND is_read = FALSE");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$notification_result = $stmt->get_result();
$notification_data = $notification_result->fetch_assoc();
$unread_notifications = $notification_data['unread_count'];
$stmt->close();

// This line has been corrected to use 'user_id'
$stmt = $conn->prepare("SELECT COUNT(*) as outstanding_bills FROM bills WHERE user_id = ? AND status != 'paid'");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$bills_result = $stmt->get_result();
$bills_data = $bills_result->fetch_assoc();
$outstanding_bills = $bills_data['outstanding_bills'];
$stmt->close();

$conn->close();

$full_name = isset($user['full_name']) ? htmlspecialchars($user['full_name']) : 'Citizen';

// Include the header
include_once '../includes/header.php';
?>

<main class="flex-grow">
    <div class="max-w-4xl mx-auto px-4 py-8">
        <div class="bg-white p-8 rounded-lg shadow-md w-full max-w-4xl mx-auto">
            <div class="flex flex-col md:flex-row items-center justify-between mb-8">
                <div class="text-center md:text-left mb-4 md:mb-0">
                    <h2 class="text-4xl font-extrabold text-gray-800">Welcome, <?= $full_name ?></h2>
                    <p class="text-gray-600 mt-2">Your central hub for Masvingo City Council services.</p>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">
                <!-- Report an Issue Card -->
                <a href="report_issue.php" class="bg-purple-500 hover:bg-purple-600 text-white p-6 rounded-lg shadow-md transition-transform transform hover:scale-105 flex flex-col items-center text-center">
                    <i class="fas fa-exclamation-triangle text-4xl mb-4"></i>
                    <h3 class="font-semibold text-xl">Report an Issue</h3>
                    <p class="text-sm mt-1 opacity-90">Report a problem in your community.</p>
                </a>

                <!-- Submit Meter Reading Card -->
                <a href="meter_reading.php" class="bg-blue-500 hover:bg-blue-600 text-white p-6 rounded-lg shadow-md transition-transform transform hover:scale-105 flex flex-col items-center text-center">
                    <i class="fas fa-tachometer-alt text-4xl mb-4"></i>
                    <h3 class="font-semibold text-xl">Submit Reading</h3>
                    <p class="text-sm mt-1 opacity-90">Submit your monthly meter reading.</p>
                </a>

                <!-- Payment Center Card with outstanding bills indicator -->
                <a href="payments.php" class="bg-emerald-500 hover:bg-emerald-600 text-white p-6 rounded-lg shadow-md transition-transform transform hover:scale-105 flex flex-col items-center text-center relative">
                    <?php if ($outstanding_bills > 0): ?>
                        <span class="absolute -top-2 -right-2 bg-red-500 text-white text-xs rounded-full px-2 py-1 min-w-[1.5rem] h-6 flex items-center justify-center font-bold">
                            <?= $outstanding_bills ?>
                        </span>
                    <?php endif; ?>
                    <i class="fas fa-credit-card text-4xl mb-4"></i>
                    <h3 class="font-semibold text-xl">Payment Center</h3>
                    <p class="text-sm mt-1 opacity-90">Pay bills and view payment history.</p>
                </a>

                <!-- Notifications Card with unread count -->
                <a href="notifications.php" class="bg-indigo-500 hover:bg-indigo-600 text-white p-6 rounded-lg shadow-md transition-transform transform hover:scale-105 flex flex-col items-center text-center relative">
                    <?php if ($unread_notifications > 0): ?>
                        <span class="absolute -top-2 -right-2 bg-red-500 text-white text-xs rounded-full px-2 py-1 min-w-[1.5rem] h-6 flex items-center justify-center font-bold notification-badge">
                            <?= $unread_notifications ?>
                        </span>
                    <?php endif; ?>
                    <i class="fas fa-bell text-4xl mb-4"></i>
                    <h3 class="font-semibold text-xl">Notifications</h3>
                    <p class="text-sm mt-1 opacity-90">View your notifications and updates.</p>
                </a>

                <!-- My Reports Card -->
                <a href="my_reports.php" class="bg-green-500 hover:bg-green-600 text-white p-6 rounded-lg shadow-md transition-transform transform hover:scale-105 flex flex-col items-center text-center">
                    <i class="fas fa-history text-4xl mb-4"></i>
                    <h3 class="font-semibold text-xl">My History</h3>
                    <p class="text-sm mt-1 opacity-90">Track your past reports and readings.</p>
                </a>

                <!-- My Profile Card -->
                <a href="profile.php" class="bg-yellow-500 hover:bg-yellow-600 text-white p-6 rounded-lg shadow-md transition-transform transform hover:scale-105 flex flex-col items-center text-center">
                    <i class="fas fa-user-circle text-4xl mb-4"></i>
                    <h3 class="font-semibold text-xl">My Profile</h3>
                    <p class="text-sm mt-1 opacity-90">View and update your personal details.</p>
                </a>
                
                <!-- ASK IT -->
                <a href="chat.php" class="inline-block bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2 px-4 rounded-lg shadow transition-colors duration-200">
                    <i class="fas fa-comments mr-2"></i> Ask IT / Chat with Admin
                </a>
            </div>

            <!-- Quick Stats Section -->
            <div class="mt-8 grid grid-cols-1 md:grid-cols-3 gap-6">
                <div class="bg-gradient-to-r from-blue-50 to-blue-100 p-6 rounded-lg border border-blue-200">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <i class="fas fa-bell text-blue-600 text-2xl"></i>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-blue-600">Unread Notifications</p>
                            <p class="text-2xl font-bold text-blue-900"><?= $unread_notifications ?></p>
                        </div>
                    </div>
                </div>

                <div class="bg-gradient-to-r from-emerald-50 to-emerald-100 p-6 rounded-lg border border-emerald-200">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <i class="fas fa-file-invoice-dollar text-emerald-600 text-2xl"></i>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-emerald-600">Outstanding Bills</p>
                            <p class="text-2xl font-bold text-emerald-900"><?= $outstanding_bills ?></p>
                        </div>
                    </div>
                </div>

                <div class="bg-gradient-to-r from-purple-50 to-purple-100 p-6 rounded-lg border border-purple-200">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <i class="fas fa-headset text-purple-600 text-2xl"></i>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-purple-600">Need Help?</p>
                            <a href="chat.php" class="text-lg font-semibold text-purple-900 hover:text-purple-700">Live Chat</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<?php include_once '../includes/footer.php'; ?>
