<?php
session_start();
include_once '../includes/config.php';
include_once 'admin_header.php';

// Check if a user is logged in and is an admin
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}

$message = '';
$message_class = '';
$user_data = null;
$departments = [];

// Fetch a list of departments for the dropdown
$sql_departments = "SELECT department_id, department_name FROM departments ORDER BY department_name";
$result_departments = $conn->query($sql_departments);
if ($result_departments) {
    while ($row = $result_departments->fetch_assoc()) {
        $departments[] = $row;
    }
}

// --- Handle form submission for updating a user ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['user_id'])) {
    $user_id = intval($_POST['user_id']);
    $full_name = trim($_POST['full_name']);
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $role = $_POST['role'];
    $department_id = intval($_POST['department_id']);
    $employee_id = trim($_POST['employee_id']);

    $conn->begin_transaction();
    try {
        // Update users table
        $stmt_users = $conn->prepare("UPDATE users SET full_name = ?, username = ?, email = ?, role = ? WHERE user_id = ?");
        $stmt_users->bind_param("ssssi", $full_name, $username, $email, $role, $user_id);
        $stmt_users->execute();

        // Update staff table
        $stmt_staff = $conn->prepare("UPDATE staff SET department_id = ?, employee_id = ? WHERE user_id = ?");
        $stmt_staff->bind_param("isi", $department_id, $employee_id, $user_id);
        $stmt_staff->execute();
        
        $conn->commit();
        $message = "Staff member updated successfully!";
        $message_class = 'bg-green-100 text-green-700';
    } catch (Exception $e) {
        $conn->rollback();
        $message = "Error updating staff member: " . $e->getMessage();
        $message_class = 'bg-red-100 text-red-700';
    }
}

// --- Fetch user data for pre-populating the form ---
if (isset($_GET['user_id'])) {
    $user_id_to_edit = intval($_GET['user_id']);
    $stmt = $conn->prepare("
        SELECT 
            u.user_id, 
            u.full_name, 
            u.username, 
            u.email, 
            u.role,
            s.employee_id,
            s.department_id
        FROM users u
        JOIN staff s ON u.user_id = s.user_id
        WHERE u.user_id = ?
    ");
    $stmt->bind_param("i", $user_id_to_edit);
    $stmt->execute();
    $result = $stmt->get_result();
    $user_data = $result->fetch_assoc();
    $stmt->close();

    if (!$user_data) {
        $message = "Staff member not found.";
        $message_class = 'bg-red-100 text-red-700';
    }
} else {
    $message = "No staff member specified for editing.";
    $message_class = 'bg-red-100 text-red-700';
}
$conn->close();
?>

<div class="container-fluid mx-auto px-4 py-8">
    <h2 class="text-3xl font-bold text-gray-800 mb-6">Edit Staff Member</h2>

    <?php if ($message): ?>
        <div class="p-4 mb-4 text-sm rounded-lg <?= htmlspecialchars($message_class) ?>" role="alert">
            <?= htmlspecialchars($message) ?>
        </div>
    <?php endif; ?>

    <?php if ($user_data): ?>
    <div class="bg-white p-6 rounded-lg shadow-md">
        <form action="edit_staff.php" method="POST" class="space-y-6">
            <input type="hidden" name="user_id" value="<?= htmlspecialchars($user_data['user_id']) ?>">
            <div>
                <label for="full_name" class="block text-gray-700 font-medium mb-2">Full Name</label>
                <input type="text" id="full_name" name="full_name" value="<?= htmlspecialchars($user_data['full_name']) ?>" required class="w-full p-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            <div>
                <label for="username" class="block text-gray-700 font-medium mb-2">Username</label>
                <input type="text" id="username" name="username" value="<?= htmlspecialchars($user_data['username']) ?>" required class="w-full p-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            <div>
                <label for="employee_id" class="block text-gray-700 font-medium mb-2">Employee ID</label>
                <input type="text" id="employee_id" name="employee_id" value="<?= htmlspecialchars($user_data['employee_id']) ?>" required class="w-full p-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            <div>
                <label for="email" class="block text-gray-700 font-medium mb-2">Email</label>
                <input type="email" id="email" name="email" value="<?= htmlspecialchars($user_data['email']) ?>" required class="w-full p-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            <div>
                <label for="role" class="block text-gray-700 font-medium mb-2">Role</label>
                <select id="role" name="role" required class="w-full p-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="staff" <?= $user_data['role'] == 'staff' ? 'selected' : '' ?>>Staff</option>
                    <option value="it_help_desk" <?= $user_data['role'] == 'it_help_desk' ? 'selected' : '' ?>>IT Help Desk</option>
                    <option value="auditor" <?= $user_data['role'] == 'auditor' ? 'selected' : '' ?>>Auditor</option>
                    <option value="admin" <?= $user_data['role'] == 'admin' ? 'selected' : '' ?>>Administrator</option>
                </select>
            </div>
            <div>
                <label for="department_id" class="block text-gray-700 font-medium mb-2">Department</label>
                <select id="department_id" name="department_id" required class="w-full p-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <?php foreach ($departments as $department): ?>
                        <option value="<?= htmlspecialchars($department['department_id']) ?>" <?= $user_data['department_id'] == $department['department_id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($department['department_name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button type="submit" class="w-full bg-green-600 hover:bg-green-700 text-white font-semibold py-3 px-4 rounded-lg transition-colors duration-200">
                <i class="fas fa-save mr-2"></i>Update Staff Member
            </button>
        </form>
    </div>
    <?php endif; ?>
</div>

<?php include_once 'admin_footer.php'; ?>
