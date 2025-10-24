<?php
session_start();
include_once '../includes/config.php';
include_once 'admin_header.php';

// Check if the user is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

// --- Fetch user data for editing ---
if (!isset($_GET['id']) || empty($_GET['id'])) {
    // Redirect back to the manage users page if no ID is provided
    header("Location: manage_users.php");
    exit();
}

$user_id = $_GET['id'];
$user_data = null;
$message = '';
$message_type = '';

// Fetch the user's current data
$sql = "SELECT user_id, full_name, username, email, phone, account_number, stand_number, role FROM users WHERE user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $user_data = $result->fetch_assoc();
} else {
    // User not found, redirect to manage users
    header("Location: manage_users.php");
    exit();
}
$stmt->close();

// --- Handle form submission for updating user ---
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Sanitize and validate input
    $full_name = htmlspecialchars(trim($_POST['full_name']));
    $username = htmlspecialchars(trim($_POST['username']));
    $email = htmlspecialchars(trim($_POST['email']));
    $phone = htmlspecialchars(trim($_POST['phone']));
    $account_number = htmlspecialchars(trim($_POST['account_number']));
    $stand_number = htmlspecialchars(trim($_POST['stand_number']));
    $role = htmlspecialchars(trim($_POST['role']));

    // Check for required fields
    if (empty($full_name) || empty($username) || empty($email) || empty($role)) {
        $message = "Please fill in all required fields.";
        $message_type = "error";
    } else {
        // Prepare the UPDATE statement
        $update_sql = "UPDATE users SET full_name = ?, username = ?, email = ?, phone = ?, account_number = ?, stand_number = ?, role = ? WHERE user_id = ?";
        $update_stmt = $conn->prepare($update_sql);
        $update_stmt->bind_param("sssssssi", $full_name, $username, $email, $phone, $account_number, $stand_number, $role, $user_id);

        if ($update_stmt->execute()) {
            $message = "User updated successfully!";
            $message_type = "success";
            // Re-fetch the updated data to display it on the form
            $sql = "SELECT user_id, full_name, username, email, phone, account_number, stand_number, role FROM users WHERE user_id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $user_data = $result->fetch_assoc();
            $stmt->close();

        } else {
            $message = "Error updating user: " . $conn->error;
            $message_type = "error";
        }
        $update_stmt->close();
    }
}
$conn->close();
?>

<h1 class="text-3xl font-bold text-gray-800 mb-6">Edit User: <?= htmlspecialchars($user_data['full_name']) ?></h1>

<div class="bg-white rounded-lg shadow-md p-8 max-w-2xl mx-auto">
    
    <?php if (!empty($message)): ?>
    <div class="p-4 mb-4 rounded-md <?= $message_type === 'success' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' ?>">
        <?= htmlspecialchars($message) ?>
    </div>
    <?php endif; ?>

    <form action="edit_user.php?id=<?= htmlspecialchars($user_data['user_id']) ?>" method="POST">
        
        <div class="mb-4">
            <label for="full_name" class="block text-gray-700 text-sm font-bold mb-2">Full Name</label>
            <input type="text" id="full_name" name="full_name" value="<?= htmlspecialchars($user_data['full_name']) ?>" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" required>
        </div>

        <div class="mb-4">
            <label for="username" class="block text-gray-700 text-sm font-bold mb-2">Username</label>
            <input type="text" id="username" name="username" value="<?= htmlspecialchars($user_data['username']) ?>" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" required>
        </div>

        <div class="mb-4">
            <label for="email" class="block text-gray-700 text-sm font-bold mb-2">Email</label>
            <input type="email" id="email" name="email" value="<?= htmlspecialchars($user_data['email']) ?>" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" required>
        </div>

        <div class="mb-4">
            <label for="phone" class="block text-gray-700 text-sm font-bold mb-2">Phone</label>
            <input type="text" id="phone" name="phone" value="<?= htmlspecialchars($user_data['phone']) ?>" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
        </div>

        <div class="mb-4">
            <label for="account_number" class="block text-gray-700 text-sm font-bold mb-2">Account #</label>
            <input type="text" id="account_number" name="account_number" value="<?= htmlspecialchars($user_data['account_number']) ?>" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
        </div>

        <div class="mb-4">
            <label for="stand_number" class="block text-gray-700 text-sm font-bold mb-2">Stand #</label>
            <input type="text" id="stand_number" name="stand_number" value="<?= htmlspecialchars($user_data['stand_number']) ?>" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
        </div>

        <div class="mb-6">
            <label for="role" class="block text-gray-700 text-sm font-bold mb-2">Role</label>
            <div class="relative">
                <select id="role" name="role" class="block appearance-none w-full bg-white border border-gray-400 hover:border-gray-500 px-4 py-2 pr-8 rounded shadow leading-tight focus:outline-none focus:shadow-outline" required>
                    <option value="admin" <?= $user_data['role'] === 'admin' ? 'selected' : '' ?>>Admin</option>
                    <option value="staff" <?= $user_data['role'] === 'staff' ? 'selected' : '' ?>>Staff</option>
                    <option value="it_help_desk" <?= $user_data['role'] === 'it_help_desk' ? 'selected' : '' ?>>IT Help Desk</option>
                    <option value="auditor" <?= $user_data['role'] === 'auditor' ? 'selected' : '' ?>>Auditor</option>
                    <option value="citizen" <?= $user_data['role'] === 'citizen' ? 'selected' : '' ?>>Citizen</option>
                </select>
                <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-2 text-gray-700">
                    <svg class="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"><path d="M9.293 12.95l-.707.707L13.5 18l4.207-4.207-.707-.707L13.5 16.586zM10.707 7.05L11.414 6.343 16.5 11.5l-5.086 5.086z"/></svg>
                </div>
            </div>
        </div>

        <div class="flex items-center justify-between">
            <button type="submit" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline transition-colors duration-200">
                Update User
            </button>
            <a href="manage_users.php" class="inline-block align-baseline font-bold text-sm text-blue-500 hover:text-blue-800">
                Cancel
            </a>
        </div>
    </form>
</div>

<?php
include_once 'admin_footer.php';
?>
