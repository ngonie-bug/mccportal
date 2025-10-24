<?php
session_start();
include_once '../includes/config.php';

$message = '';
$show_form = false;
$token = $_GET['token'] ?? '';

// Check if the token is provided and valid
if (!empty($token)) {
    // In a real application, you would check this token against a 'password_resets' database table
    // For this example, we will just assume the token is present for the form to show.
    // Replace this with your actual database query to validate the token.
    $sql_check_token = "SELECT * FROM password_resets WHERE token = ? AND expires_at > NOW()";
    $stmt_check_token = $conn->prepare($sql_check_token);
    $stmt_check_token->bind_param("s", $token);
    $stmt_check_token->execute();
    $result_check_token = $stmt_check_token->get_result();

    if ($result_check_token->num_rows > 0) {
        $user_data = $result_check_token->fetch_assoc();
        $show_form = true;
    } else {
        $message = "Invalid or expired password reset token.";
    }
} else {
    $message = "No password reset token provided.";
}

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && $show_form) {
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if ($new_password !== $confirm_password) {
        $message = "Passwords do not match.";
    } elseif (strlen($new_password) < 6) {
        $message = "Password must be at least 6 characters long.";
    } else {
        // Hash the new password
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

        // Update the user's password in the database
        // In a real application, you would use the user's email from the token record
        // to identify the user. For this example, we'll use a placeholder.
        // Replace 'user_id' with the actual user identifier from the token table.
        $sql_update_password = "UPDATE users SET password = ? WHERE email = ?";
        $stmt_update_password = $conn->prepare($sql_update_password);
        $stmt_update_password->bind_param("ss", $hashed_password, $user_data['email']);

        if ($stmt_update_password->execute()) {
            // Delete the token to prevent reuse
            $sql_delete_token = "DELETE FROM password_resets WHERE token = ?";
            $stmt_delete_token = $conn->prepare($sql_delete_token);
            $stmt_delete_token->bind_param("s", $token);
            $stmt_delete_token->execute();

            $message = "Password has been successfully reset. You can now log in with your new password.";
            header("refresh:5; url=login.php"); // Redirect after 5 seconds
        } else {
            $message = "Failed to update password. Please try again.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password | Masvingo Connect</title>
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
    <h2 class="text-3xl font-bold text-center text-gray-800 mb-4">Reset Password</h2>

    <?php if ($message): ?>
        <div class="bg-<?php echo strpos($message, 'successfully') !== false ? 'green' : 'red'; ?>-100 border border-<?php echo strpos($message, 'successfully') !== false ? 'green' : 'red'; ?>-400 text-<?php echo strpos($message, 'successfully') !== false ? 'green' : 'red'; ?>-700 px-4 py-3 rounded relative mb-4" role="alert">
            <span class="block sm:inline"><?php echo htmlspecialchars($message); ?></span>
        </div>
    <?php endif; ?>

    <?php if ($show_form && !$message): ?>
        <p class="text-gray-600 text-center mb-6">Please enter your new password below.</p>
        <form method="POST" action="reset.php?token=<?php echo htmlspecialchars($token); ?>" class="space-y-4">
            <div>
                <label for="new_password" class="block text-gray-700 font-medium mb-2">New Password</label>
                <input type="password" id="new_password" name="new_password" required class="form-input" placeholder="Enter new password">
            </div>
            <div>
                <label for="confirm_password" class="block text-gray-700 font-medium mb-2">Confirm Password</label>
                <input type="password" id="confirm_password" name="confirm_password" required class="form-input" placeholder="Confirm new password">
            </div>
            <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold py-3 px-4 rounded-lg transition-colors duration-200">
                <i class="fas fa-key mr-2"></i> Update Password
            </button>
        </form>
    <?php endif; ?>
    
    <div class="mt-6 text-center">
        <a href="login.php" class="text-blue-600 hover:underline font-medium">
            <i class="fas fa-arrow-left mr-1"></i> Back to Login
        </a>
    </div>
</div>

</body>
</html>
