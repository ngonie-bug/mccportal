<?php
session_start();
include_once 'includes/config.php';

$message = '';

/**
 * Redirects the user to the appropriate dashboard based on their role.
 * This function consolidates the redirection logic to avoid code duplication.
 * @param string $role The user's role.
 */
function redirect_by_role($role) {
    if ($role === 'admin' || $role === 'auditor') {
        header('Location: admin/dashboard.php');
    } elseif ($role === 'staff' || $role === 'it_help_desk') {
        header('Location: staff/staff_dashboard.php');
    } else {
        // Fallback for any other unexpected roles, sends them to the homepage
        header('Location: index.php');
    }
    exit();
}

// Check if a user is already logged in and redirect them to their dashboard
if (isset($_SESSION['user_role'])) {
    redirect_by_role($_SESSION['user_role']);
}

// Handle form submission when the request method is POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize and trim input data
    $identifier = trim($_POST['identifier']);
    $password = $_POST['password'];

    // Check if the input fields are not empty
    if (empty($identifier) || empty($password)) {
        $message = "Please enter both a username/email and a password.";
    } else {
        // Prepare a statement to prevent SQL injection
        // The query looks for a user by either their username or email
        $stmt = $conn->prepare("SELECT user_id, full_name, username, role, password_hash FROM users WHERE username = ? OR email = ?");
        $stmt->bind_param("ss", $identifier, $identifier);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();

            // Verify the provided password against the stored hash
            if (password_verify($password, $user['password_hash'])) {
                // Check if the user has a valid role for this login portal
                if (in_array($user['role'], ['admin', 'staff', 'it_help_desk', 'auditor'])) {
                    // Password is correct, set session variables
                    $_SESSION['user_id'] = $user['user_id'];
                    $_SESSION['full_name'] = $user['full_name'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['user_role'] = $user['role'];

                    // Redirect based on the user's role using the helper function
                    redirect_by_role($user['role']);
                } else {
                    // This is a citizen user trying to log in via the staff/admin page
                    $message = "You do not have access to this portal.";
                }
            } else {
                $message = "Invalid password.";
            }
        } else {
            $message = "No account found with that username or email.";
        }
        $stmt->close();
    }
}

// Close the database connection
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | Masvingo Connect</title>
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
        <form action="login.php" method="POST" class="space-y-4">
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
    </div>
</body>
</html>
