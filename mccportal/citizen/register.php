<?php
session_start();
include_once '../includes/config.php';

$message = '';
$message_class = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim($_POST['full_name']);
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $password = $_POST['password'];
    $account_number = trim($_POST['account_number']);
    $stand_number = trim($_POST['stand_number']);
    $location = trim($_POST['location']); // New location field
    $role = 'citizen';

    // Check for required fields
    if (empty($full_name) || empty($username) || empty($email) || empty($password) || empty($phone) || empty($account_number) || empty($stand_number) || empty($location)) {
        $message = "All fields are required.";
        $message_class = 'bg-red-100 text-red-700';
    } else {
        // Hash the password securely
        $password_hash = password_hash($password, PASSWORD_DEFAULT);

        // Start transaction
        $conn->begin_transaction();
        
        try {
            // Check if username, email, phone, account number, or stand number already exist
            $stmt = $conn->prepare("SELECT user_id FROM users WHERE username = ? OR email = ? OR phone = ? OR account_number = ? OR stand_number = ?");
            $stmt->bind_param("sssss", $username, $email, $phone, $account_number, $stand_number);
            $stmt->execute();
            $stmt->store_result();
            if ($stmt->num_rows > 0) {
                $message = "A user with that username, email, phone number, account number, or stand number already exists.";
                $message_class = 'bg-red-100 text-red-700';
                $conn->rollback();
            } else {
                // Insert into users table, including location
                $stmt = $conn->prepare("INSERT INTO users (full_name, username, email, phone, password_hash, account_number, stand_number, location, role) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("sssssssss", $full_name, $username, $email, $phone, $password_hash, $account_number, $stand_number, $location, $role);
                $stmt->execute();
                
                $conn->commit();
                $message = "Registration successful! You can now log in.";
                $message_class = 'bg-green-100 text-green-700';
            }
            $stmt->close();

        } catch (Exception $e) {
            $conn->rollback();
            $message = "Error during registration: " . $e->getMessage();
            $message_class = 'bg-red-100 text-red-700';
        }
    }
}
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Citizen Registration | Masvingo Connect</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap');
        body { font-family: 'Inter', sans-serif; }
    </style>
</head>
<body class="bg-gray-100 min-h-screen flex flex-col justify-center items-center py-12">
    <div class="bg-white p-8 rounded-lg shadow-md w-full max-w-md">
        <h2 class="text-3xl font-bold text-center text-gray-800 mb-6">Citizen Registration</h2>

        <?php if ($message): ?>
            <div class="p-4 mb-4 text-sm rounded-lg <?= htmlspecialchars($message_class) ?>" role="alert">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>

        <form action="register.php" method="POST" class="space-y-4">
            <div>
                <label for="full_name" class="block text-gray-700 font-medium mb-2">Full Name</label>
                <input type="text" id="full_name" name="full_name" required class="w-full p-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            <div>
                <label for="username" class="block text-gray-700 font-medium mb-2">Username</label>
                <input type="text" id="username" name="username" required class="w-full p-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            <div>
                <label for="email" class="block text-gray-700 font-medium mb-2">Email</label>
                <input type="email" id="email" name="email" required class="w-full p-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            <div>
                <label for="phone" class="block text-gray-700 font-medium mb-2">Phone</label>
                <input type="tel" id="phone" name="phone" required class="w-full p-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            <div>
                <label for="password" class="block text-gray-700 font-medium mb-2">Password</label>
                <input type="password" id="password" name="password" required class="w-full p-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            <div>
                <label for="account_number" class="block text-gray-700 font-medium mb-2">Account Number</label>
                <input type="text" id="account_number" name="account_number" required class="w-full p-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            <div>
                <label for="stand_number" class="block text-gray-700 font-medium mb-2">Stand Number</label>
                <input type="text" id="stand_number" name="stand_number" required class="w-full p-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            <div>
                <label for="location" class="block text-gray-700 font-medium mb-2">Location</label>
                <input type="text" id="location" name="location" required class="w-full p-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold py-3 px-4 rounded-lg transition-colors duration-200">
                <i class="fas fa-user-plus mr-2"></i>Register
            </button>
        </form>
        <div class="mt-6 text-center">
            <p class="text-gray-600">Already have an account? <a href="login.php" class="text-blue-600 hover:underline font-medium">Log in here</a></p>
        </div>
    </div>
</body>
</html>