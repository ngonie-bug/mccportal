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
$message_type = '';

// Handle the deletion request
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['user_id'])) {
    $user_id = $_POST['user_id'];
    
    // Start a transaction to ensure both deletions are successful or none are
    $conn->begin_transaction();
    
    try {
        // First, delete from the staff table (due to foreign key constraint)
        $stmt_staff = $conn->prepare("DELETE FROM staff WHERE user_id = ?");
        $stmt_staff->bind_param("i", $user_id);
        if (!$stmt_staff->execute()) {
            throw new Exception("Error deleting staff details: " . $stmt_staff->error);
        }
        $stmt_staff->close();

        // Then, delete from the users table
        $stmt_user = $conn->prepare("DELETE FROM users WHERE user_id = ?");
        $stmt_user->bind_param("i", $user_id);
        if (!$stmt_user->execute()) {
            throw new Exception("Error deleting user: " . $stmt_user->error);
        }
        $stmt_user->close();
        
        // Commit the transaction
        $conn->commit();
        $message = "Staff member deleted successfully!";
        $message_type = "success";
        
    } catch (Exception $e) {
        $conn->rollback();
        $message = "Failed to delete staff member: " . $e->getMessage();
        $message_type = "error";
    }
}

// Fetch all staff members for display
$staff_members = [];
$sql = "SELECT u.user_id, u.full_name, u.email, u.role, s.employee_id, d.department_name
        FROM users u
        JOIN staff s ON u.user_id = s.user_id
        JOIN departments d ON s.department_id = d.department_id
        ORDER BY u.full_name ASC";
$result = $conn->query($sql);
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $staff_members[] = $row;
    }
}
?>

<div class="container mx-auto px-4 py-8 max-w-4xl">
    <h2 class="text-3xl font-bold text-gray-800 mb-6 text-center">Manage Staff Members</h2>

    <?php if ($message): ?>
        <div class="mb-4 p-4 rounded-lg
            <?php echo ($message_type === 'success') ? 'bg-green-100 text-green-700 border border-green-400' : 'bg-red-100 text-red-700 border border-red-400'; ?>"
            role="alert">
            <?= htmlspecialchars($message) ?>
        </div>
    <?php endif; ?>

    <div class="bg-white p-6 rounded-lg shadow-md overflow-x-auto">
        <?php if (empty($staff_members)): ?>
            <div class="text-center text-gray-500 py-8">
                <p>No staff members found.</p>
                <p class="mt-2">Use the "Add New Staff Member" page to add staff.</p>
            </div>
        <?php else: ?>
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Full Name</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Employee ID</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Department</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Role</th>
                        <th scope="col" class="relative px-6 py-3"><span class="sr-only">Delete</span></th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php foreach ($staff_members as $staff): ?>
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900"><?= htmlspecialchars($staff['full_name']) ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?= htmlspecialchars($staff['email']) ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?= htmlspecialchars($staff['employee_id']) ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?= htmlspecialchars($staff['department_name']) ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?= htmlspecialchars($staff['role']) ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <form action="<?= htmlspecialchars($_SERVER["PHP_SELF"]) ?>" method="POST" onsubmit="return confirm('Are you sure you want to delete this staff member? This action cannot be undone.');">
                                    <input type="hidden" name="user_id" value="<?= htmlspecialchars($staff['user_id']) ?>">
                                    <button type="submit" class="text-red-600 hover:text-red-900 transition-colors duration-200">
                                        Delete
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>

<?php include_once 'admin_footer.php'; ?>
