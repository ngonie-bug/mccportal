<?php
session_start();
include_once '../includes/config.php';
$message = '';

// Check for success message from registration
if (isset($_GET['success']) && $_GET['success'] == 1) {
    $message = "Admin account created successfully! You can now log in.";
}

// Check if a user is already logged in and redirect them to their dashboard
if (isset($_SESSION['user_role'])) {
    if ($_SESSION['user_role'] === 'admin' || $_SESSION['user_role'] === 'auditor') {
        // Corrected path to dashboard.php which is inside the admin folder
        header('Location: admin/dashboard.php');
        exit();
    } elseif ($_SESSION['user_role'] === 'staff' || $_SESSION['user_role'] === 'it_help_desk') {
        header('Location: staff/dashboard.php');
        exit();
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $identifier = trim($_POST['identifier']);
    $password = $_POST['password'];

    // Prepare a statement to prevent SQL injection
    $stmt = $conn->prepare("SELECT user_id, full_name, username, role, password_hash, is_active FROM users WHERE username = ? OR email = ?");
    $stmt->bind_param("ss", $identifier, $identifier);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();

        // Check if the user account is active
        if ($user['is_active'] == 0) {
            $message = "Your account has been deactivated. Please contact your administrator.";
        }
        
        // Use password_verify() to check the plain-text password against the stored hash
        else if (password_verify($password, $user['password_hash'])) {
            // Check if the user has an admin or staff role
            if ($user['role'] === 'admin' || $user['role'] === 'staff' || $user['role'] === 'it_help_desk' || $user['role'] === 'auditor') {
                // Login successful, set session variables
                $_SESSION['user_id'] = $user['user_id'];
                $_SESSION['full_name'] = $user['full_name'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['user_role'] = $user['role'];
                
                // Update last login timestamp
                $stmt_update_login = $conn->prepare("UPDATE users SET last_login = CURRENT_TIMESTAMP WHERE user_id = ?");
                $stmt_update_login->bind_param("i", $user['user_id']);
                $stmt_update_login->execute();
                $stmt_update_login->close();

                // Redirect to the appropriate dashboard
                if ($user['role'] === 'admin' || $user['role'] === 'auditor') {
                    // This is the corrected redirect path
                    header('Location: admin/dashboard.php');
                } else {
                    header('Location: staff/dashboard.php');
                }
                exit();
            } else {
                // A citizen trying to log in via the staff login page
                $message = "Invalid role for this login portal.";
            }
        } else {
            // Password did not match the hash
            $message = "Invalid credentials. Please try again.";
        }
    } else {
        // No user found with that username or email
        $message = "Invalid credentials. Please try again.";
    }
    $stmt->close();
}
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Staff & Admin Login</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap');
        body { font-family: 'Inter', sans-serif; }
    </style>
</head>
<body class="bg-gray-100 flex items-center justify-center min-h-screen">

<div class="bg-white p-8 rounded-lg shadow-md w-full max-w-md">
    <h2 class="text-3xl font-bold text-center text-gray-800 mb-6">Staff & Admin Login</h2>
    <?php if ($message): ?>
        <div class="p-4 mb-4 text-sm text-red-700 bg-red-100 rounded-lg" role="alert">
            <?= htmlspecialchars($message) ?>
        </div>
    <?php endif; ?>
    <form action="admin_login.php" method="POST" class="space-y-4">
        <div>
            <label for="identifier" class="block text-gray-700 font-medium mb-2">Username or Email</label>
            <input type="text" id="identifier" name="identifier" required class="w-full p-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
        </div>
        <div>
            <label for="password" class="block text-gray-700 font-medium mb-2">Password</label>
            <input type="password" id="password" name="password" required class="w-full p-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
        </div>
        <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold py-3 px-4 rounded-lg transition-colors duration-200">
            <i class="fas fa-sign-in-alt mr-2"></i>Login
        </button>
    </form>
    <div class="mt-4 text-center">
        <a href="admin_register.php" class="text-blue-600 hover:text-blue-800 text-sm">Don't have an admin account? Register here.</a>
    </div>
</div>

</body>
</html>
