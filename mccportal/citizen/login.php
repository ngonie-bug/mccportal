<?php
session_start();
include_once '../includes/config.php';

$message = '';
$message_class = '';

// Check if the user is already logged in
if (isset($_SESSION['user_id'])) {
    // Redirect based on role
    switch ($_SESSION['role']) {
        case 'admin':
            header('Location: /mccportal/admin/dashboard.php');
            exit();
        case 'staff':
            header('Location: /mccportal/staff/dashboard.php');
            exit();
        case 'citizen':
            header('Location: /mccportal/citizen/dashboard.php');
            exit();
        default:
            header('Location: /mccportal/index.php');
            exit();
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $identifier = trim($_POST['identifier']);
    $password = $_POST['password'];

    // Check against email, username, account number, or stand number
    $stmt = $conn->prepare("SELECT user_id, password_hash, full_name, role FROM users WHERE email = ? OR username = ? OR account_number = ? OR stand_number = ?");
    $stmt->bind_param("ssss", $identifier, $identifier, $identifier, $identifier);
    $stmt->execute();
    $stmt->store_result();
    $stmt->bind_result($user_id, $hashed_password, $full_name, $user_role);
    $stmt->fetch();

    if ($stmt->num_rows > 0 && password_verify($password, $hashed_password)) {
        $_SESSION['user_id'] = $user_id;
        $_SESSION['full_name'] = $full_name;
        $_SESSION['role'] = $user_role;

        // Redirect based on role
        switch ($user_role) {
            case 'admin':
                header('Location: /mccportal/admin/dashboard.php');
                break;
            case 'staff':
                header('Location: /mccportal/staff/dashboard.php');
                break;
            case 'citizen':
                header('Location: /mccportal/citizen/login.php');
                break;
            default:
                header('Location: /mccportal/index.php');
                break;
        }
        exit();
    } else {
        $message = "Invalid credentials. Please try again.";
        $message_class = 'bg-red-100 text-red-700';
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
    <title>Login | Masvingo Connect</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap');
        body { font-family: 'Inter', sans-serif; background-color: #f3f4f6; }
    </style>
</head>
<body class="bg-gray-100 min-h-screen flex flex-col justify-center items-center py-12">
<div class="bg-white p-8 rounded-lg shadow-md w-full max-w-md">
    <h2 class="text-3xl font-bold text-center text-gray-800 mb-6">Log In</h2>
    <?php if ($message): ?>
        <div class="p-4 mb-4 text-sm rounded-lg <?= htmlspecialchars($message_class) ?>" role="alert">
            <?= htmlspecialchars($message) ?>
        </div>
    <?php endif; ?>
    <form action="login.php" method="POST" class="space-y-4">
        <div>
            <label for="identifier" class="block text-gray-700 font-medium mb-2">Username, Email, Account Number, or Stand Number</label>
            <input type="text" id="identifier" name="identifier" required class="w-full p-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
        </div>
        <div>
            <label for="password" class="block text-gray-700 font-medium mb-2">Password</label>
            <input type="password" id="password" name="password" required class="w-full p-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
        </div>
        <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold py-3 px-4 rounded-lg transition-colors duration-200">
            <i class="fas fa-sign-in-alt mr-2"></i> Log In
        </button>
    </form>
    <div class="mt-4 text-center">
        <a href="forgot_password.php" class="text-blue-600 hover:underline text-sm">Forgot your password?</a>
    </div>
    <div class="mt-6 text-center">
        <p class="text-gray-600">Don't have an account? <a href="register.php" class="text-blue-600 hover:underline font-medium">Register here</a></p>
    </div>
</div>
</body>
</html>