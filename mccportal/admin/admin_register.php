<?php
session_start();
include_once '../includes/config.php';
$message = '';
$message_class = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim($_POST['full_name']);
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Validate form fields
    if (empty($full_name) || empty($username) || empty($email) || empty($password) || empty($confirm_password)) {
        $message = "All fields are required.";
        $message_class = "text-red-700 bg-red-100";
    } elseif ($password !== $confirm_password) {
        $message = "Passwords do not match.";
        $message_class = "text-red-700 bg-red-100";
    } else {
        // Check if username or email already exists
        $stmt_check = $conn->prepare("SELECT user_id FROM users WHERE username = ? OR email = ?");
        $stmt_check->bind_param("ss", $username, $email);
        $stmt_check->execute();
        $result_check = $stmt_check->get_result();
        
        if ($result_check->num_rows > 0) {
            $message = "Username or email already exists. Please choose another one.";
            $message_class = "text-red-700 bg-red-100";
        } else {
            // Hash the password
            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            
            // Insert the new admin user into the database
            $role = 'admin';
            $is_active = 1;
            $stmt_insert = $conn->prepare("INSERT INTO users (full_name, username, email, password_hash, role, is_active) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt_insert->bind_param("sssssi", $full_name, $username, $email, $password_hash, $role, $is_active);
            
            if ($stmt_insert->execute()) {
                $message = "Admin account created successfully! You can now log in.";
                $message_class = "text-green-700 bg-green-100";
                // Redirect to login page after a short delay or on a new page load
                header("Location: admin_login.php?success=1");
                exit();
            } else {
                $message = "Error creating account. Please try again.";
                $message_class = "text-red-700 bg-red-100";
            }
            $stmt_insert->close();
        }
        $stmt_check->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Registration</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { font-family: 'Inter', sans-serif; }
    </style>
</head>
<body class="bg-gray-100 flex items-center justify-center min-h-screen">

<div class="bg-white p-8 rounded-lg shadow-md w-full max-w-md">
    <h2 class="text-3xl font-bold text-center text-gray-800 mb-6">Admin Registration</h2>
    <?php if ($message): ?>
        <div class="p-4 mb-4 text-sm rounded-lg <?= htmlspecialchars($message_class) ?>" role="alert">
            <?= htmlspecialchars($message) ?>
        </div>
    <?php endif; ?>
    <form action="admin_register.php" method="POST" class="space-y-4">
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
            <label for="password" class="block text-gray-700 font-medium mb-2">Password</label>
            <input type="password" id="password" name="password" required class="w-full p-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
        </div>
        <div>
            <label for="confirm_password" class="block text-gray-700 font-medium mb-2">Confirm Password</label>
            <input type="password" id="confirm_password" name="confirm_password" required class="w-full p-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
        </div>
        <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold py-3 px-4 rounded-lg transition-colors duration-200">
            <i class="fas fa-user-plus mr-2"></i>Register
        </button>
    </form>
    <div class="mt-4 text-center">
        <a href="admin_login.php" class="text-blue-600 hover:text-blue-800 text-sm">Already have an account? Log in here.</a>
    </div>
</div>

</body>
</html>
