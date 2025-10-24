<?php
session_start();
include_once '../includes/config.php';
include_once 'admin_header.php';

// Check if a user is logged in and is an admin
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}

$errors = [];
$success_message = '';
$form_data = [
    'full_name' => '',
    'username' => '',
    'email' => '',
    'employee_id' => '',
    'assigned_to_department_id' => '', // Updated variable name
    'role' => 'staff' // Default role
];

// Fetch departments for the dropdown
$departments = [];
$sql_departments = "SELECT department_id, department_name FROM departments ORDER BY department_name ASC";
$result_departments = $conn->query($sql_departments);
if ($result_departments && $result_departments->num_rows > 0) {
    while ($row = $result_departments->fetch_assoc()) {
        $departments[] = $row;
    }
}

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $full_name = trim($_POST['full_name']);
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $employee_id = trim($_POST['employee_id']);
    $assigned_to_department_id = trim($_POST['assigned_to_department_id']); // Updated variable name
    $role = trim($_POST['role']);

    // Preserve form data on error
    $form_data = [
        'full_name' => $full_name,
        'username' => $username,
        'email' => $email,
        'employee_id' => $employee_id,
        'assigned_to_department_id' => $assigned_to_department_id, // Updated variable name
        'role' => $role
    ];

    // Basic validation
    if (empty($full_name)) { $errors[] = "Full Name is required."; }
    if (empty($username)) { $errors[] = "Username is required."; }
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) { $errors[] = "A valid Email is required."; }
    if (empty($password)) { $errors[] = "Password is required."; }
    if ($password !== $confirm_password) { $errors[] = "Passwords do not match."; }
    if (empty($employee_id)) { $errors[] = "Employee ID is required."; }
    if (empty($assigned_to_department_id)) { $errors[] = "Department is required."; } // Updated variable name
    if (empty($role)) { $errors[] = "Role is required."; }

    // Check for existing username or email
    $stmt = $conn->prepare("SELECT user_id FROM users WHERE username = ? OR email = ?");
    $stmt->bind_param("ss", $username, $email);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $errors[] = "Username or email already exists.";
    }
    $stmt->close();

    if (empty($errors)) {
        // Hash the password
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        // Start a transaction for atomicity
        $conn->begin_transaction();
        
        try {
            $stmt_user = $conn->prepare("INSERT INTO users (full_name, username, email, password_hash, role, department_id) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt_user->bind_param("sssssi", $full_name, $username, $email, $hashed_password, $role, $assigned_to_department_id);
            if (!$stmt_user->execute()) {
                throw new Exception("Error adding user: " . $stmt_user->error);
            }
            
            $user_id = $conn->insert_id;
            $stmt_user->close();
            
            $stmt_staff = $conn->prepare("INSERT INTO staff (user_id, employee_id, department_id) VALUES (?, ?, ?)");
            $stmt_staff->bind_param("isi", $user_id, $employee_id, $assigned_to_department_id);
            if (!$stmt_staff->execute()) {
                throw new Exception("Error adding staff details: " . $stmt_staff->error);
            }
            $stmt_staff->close();
            
            // Commit transaction
            $conn->commit();
            $success_message = "Staff member added successfully!";
            
            // Clear form data on successful submission
            $form_data = [
                'full_name' => '',
                'username' => '',
                'email' => '',
                'employee_id' => '',
                'assigned_to_department_id' => '', // Resetting updated variable
                'role' => 'staff'
            ];

        } catch (Exception $e) {
            $conn->rollback();
            $errors[] = "Failed to add staff member: " . $e->getMessage();
        }
    }
}
?>

<div class="container mx-auto px-4 py-8 max-w-2xl">
    <div class="bg-white p-8 rounded-lg shadow-md">
        <h2 class="text-3xl font-bold text-gray-800 mb-6 text-center">Add New Staff Member</h2>
        
        <?php if (!empty($errors)): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
                <strong class="font-bold">Error!</strong>
                <span class="block sm:inline"><?= implode(' ', $errors) ?></span>
            </div>
        <?php endif; ?>
        
        <?php if ($success_message): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4" role="alert">
                <strong class="font-bold">Success!</strong>
                <span class="block sm:inline"><?= htmlspecialchars($success_message) ?></span>
            </div>
        <?php endif; ?>
        
        <form action="<?= htmlspecialchars($_SERVER["PHP_SELF"]) ?>" method="POST" class="space-y-6">
            <div>
                <label for="full_name" class="block text-sm font-medium text-gray-700">Full Name</label>
                <input type="text" name="full_name" id="full_name" required value="<?= htmlspecialchars($form_data['full_name']) ?>" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
            </div>
            
            <div>
                <label for="username" class="block text-sm font-medium text-gray-700">Username</label>
                <input type="text" name="username" id="username" required value="<?= htmlspecialchars($form_data['username']) ?>" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
            </div>

            <div>
                <label for="email" class="block text-sm font-medium text-gray-700">Email Address</label>
                <input type="email" name="email" id="email" required value="<?= htmlspecialchars($form_data['email']) ?>" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
            </div>

            <div>
                <label for="password" class="block text-sm font-medium text-gray-700">Password</label>
                <input type="password" name="password" id="password" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
            </div>

            <div>
                <label for="confirm_password" class="block text-sm font-medium text-gray-700">Confirm Password</label>
                <input type="password" name="confirm_password" id="confirm_password" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
            </div>
            
            <div>
                <label for="employee_id" class="block text-sm font-medium text-gray-700">Employee ID</label>
                <input type="text" name="employee_id" id="employee_id" required value="<?= htmlspecialchars($form_data['employee_id']) ?>" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
            </div>
            
            <div>
                <label for="assigned_to_department_id" class="block text-sm font-medium text-gray-700">Department</label>
                <select id="assigned_to_department_id" name="assigned_to_department_id" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    <option value="">Select Department</option>
                    <?php foreach ($departments as $department): ?>
                        <option value="<?= htmlspecialchars($department['department_id']) ?>" <?= ($form_data['assigned_to_department_id'] == $department['department_id']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($department['department_name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div>
                <label for="role" class="block text-sm font-medium text-gray-700">Role</label>
                <select id="role" name="role" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    <option value="staff" <?= ($form_data['role'] == 'staff') ? 'selected' : '' ?>>Staff</option>
                    <option value="it_help_desk" <?= ($form_data['role'] == 'it_help_desk') ? 'selected' : '' ?>>IT Help Desk</option>
                    <option value="auditor" <?= ($form_data['role'] == 'auditor') ? 'selected' : '' ?>>Auditor</option>
                </select>
            </div>

            <div class="text-center">
                <button type="submit" class="w-full sm:w-auto bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-6 rounded-lg transition-colors duration-200 shadow-md">
                    Add Staff Member
                </button>
            </div>
        </form>
    </div>
</div>

<?php include_once 'admin_footer.php'; ?>
