<?php
session_start();
include_once '../includes/config.php';

$success_message = '';
$error_message = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $recovery_id = $_POST['recovery_id'] ?? '';

    if (empty($recovery_id)) {
        $error_message = "Please enter your username, email, or phone number.";
    } else {
        // Prepare the SQL query to check for the user in the database.
        // Changed 'id' to 'user_id'
        $sql = "SELECT user_id FROM users WHERE username = ? OR email = ? OR phone = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sss", $recovery_id, $recovery_id, $recovery_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $user = $result->fetch_assoc();
            // Redirect directly to the password reset page with the user's ID.
            header("Location: reset.php?id=" . $user['user_id']);
            exit();
        } else {
            // User not found. Give a generic message to prevent revealing account status.
            $success_message = "If an account with that information exists, you will be redirected to reset your password.";
        }
        
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password | Masvingo Connect</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap');
        body { font-family: 'Inter', sans-serif; background-color: #f3f4f6; }
        .form-input { border: 1px solid #d1d5db; padding: 0.75rem; border-radius: 0.5rem; width: 100%; transition: all 0.2s; }
        .form-input:focus { outline: none; border-color: #3b82f6; box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.25); }
    </style>
</head>
<body class="flex items-center justify-center min-h-screen">

<div class="bg-white p-8 rounded-lg shadow-md w-full max-w-sm">
    <h2 class="text-3xl font-bold text-center text-gray-800 mb-4">Forgot Password</h2>
    <p class="text-gray-600 text-center mb-6">Enter your username or registered email/phone number to reset your password.</p>

    <!-- Display messages here -->
    <?php if ($success_message): ?>
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4" role="alert">
            <span class="block sm:inline"><?php echo htmlspecialchars($success_message); ?></span>
        </div>
    <?php endif; ?>
    <?php if ($error_message): ?>
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
            <span class="block sm:inline"><?php echo htmlspecialchars($error_message); ?></span>
        </div>
    <?php endif; ?>

    <form method="POST" action="forgot_password.php" class="space-y-4">
        <div>
            <label for="recovery_id" class="block text-gray-700 font-medium mb-2">Username, Email or Phone</label>
            <input type="text" id="recovery_id" name="recovery_id" required class="form-input" placeholder="e.g. username@example.com">
        </div>
        <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold py-3 px-4 rounded-lg transition-colors duration-200">
            <i class="fas fa-lock-open mr-2"></i> Reset Password
        </button>
    </form>
    
    <div class="mt-6 text-center">
        <a href="login.php" class="text-blue-600 hover:underline font-medium">
            <i class="fas fa-arrow-left mr-1"></i> Back to Login
        </a>
    </div>
</div>

</body>
</html>
