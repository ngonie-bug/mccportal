<?php
session_start();
include_once '../includes/config.php';
include_once 'admin_header.php';

// Check if a user is logged in and is an admin
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}

// Fetch all staff members, including their department name
$sql = "
    SELECT 
        u.user_id, 
        u.full_name, 
        u.username, 
        u.email, 
        u.role,
        u.department_id as user_department_id,
        s.employee_id,
        d.department_name
    FROM users u
    JOIN staff s ON u.user_id = s.user_id
    LEFT JOIN departments d ON u.department_id = d.department_id
    WHERE u.role IN ('staff', 'it_help_desk', 'auditor')
    ORDER BY u.full_name ASC
";
$result = $conn->query($sql);
$staff_members = [];
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $staff_members[] = $row;
    }
}
$conn->close();
?>

<div class="container-fluid mx-auto px-4 py-8">
    <div class="flex justify-between items-center mb-6">
        <h2 class="text-3xl font-bold text-gray-800">Manage Staff</h2>
        <a href="add_staff.php" class="bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2 px-4 rounded-lg transition-colors duration-200 shadow-md">
            <i class="fas fa-plus mr-2"></i> Add New Staff
        </a>
    </div>

    <div class="bg-white p-6 rounded-lg shadow-md overflow-x-auto">
        <?php if (empty($staff_members)): ?>
            <div class="p-4 text-center text-gray-500">
                <p>No staff members found.</p>
            </div>
        <?php else: ?>
            <table class="min-w-full leading-normal">
                <thead>
                    <tr>
                        <th class="px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                            Full Name
                        </th>
                        <th class="px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                            Username
                        </th>
                        <th class="px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                            Employee ID
                        </th>
                        <th class="px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                            Department
                        </th>
                        <th class="px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                            Role
                        </th>
                        <th class="px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-center text-xs font-semibold text-gray-600 uppercase tracking-wider">
                            Actions
                        </th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($staff_members as $staff): ?>
                    <tr class="hover:bg-gray-50">
                        <td class="px-5 py-5 border-b border-gray-200 bg-white text-sm">
                            <p class="text-gray-900 whitespace-no-wrap"><?= htmlspecialchars($staff['full_name']) ?></p>
                        </td>
                        <td class="px-5 py-5 border-b border-gray-200 bg-white text-sm">
                            <p class="text-gray-900 whitespace-no-wrap"><?= htmlspecialchars($staff['username']) ?></p>
                        </td>
                        <td class="px-5 py-5 border-b border-gray-200 bg-white text-sm">
                            <p class="text-gray-900 whitespace-no-wrap"><?= htmlspecialchars($staff['employee_id']) ?></p>
                        </td>
                        <td class="px-5 py-5 border-b border-gray-200 bg-white text-sm">
                            <p class="text-gray-900 whitespace-no-wrap"><?= htmlspecialchars($staff['department_name'] ?? 'N/A') ?></p>
                        </td>
                        <td class="px-5 py-5 border-b border-gray-200 bg-white text-sm">
                            <p class="text-gray-900 whitespace-no-wrap"><?= htmlspecialchars(ucfirst($staff['role'])) ?></p>
                        </td>
                        <td class="px-5 py-5 border-b border-gray-200 bg-white text-sm text-center">
                            <div class="flex justify-center space-x-2">
                                <a href="edit_staff.php?user_id=<?= htmlspecialchars($staff['user_id']) ?>" class="text-blue-600 hover:text-blue-900" title="Edit Staff">
                                    <i class="fas fa-edit fa-lg"></i>
                                </a>
                                <a href="delete_staff.php?user_id=<?= htmlspecialchars($staff['user_id']) ?>" class="text-red-600 hover:text-red-900" title="Delete Staff" onclick="return confirm('Are you sure you want to delete this staff member?');">
                                    <i class="fas fa-trash-alt fa-lg"></i>
                                </a>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>

<?php include_once 'admin_footer.php'; ?>
