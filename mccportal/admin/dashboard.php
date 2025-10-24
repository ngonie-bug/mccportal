<?php
session_start();
include_once '../includes/config.php';
include_once 'admin_header.php';

// Check if the user is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

// Fetch counts for the dashboard cards
$citizen_count = 0;
$staff_count = 0;
$report_count = 0;
$department_count = 0;

// Citizen count
$sql = "SELECT COUNT(*) AS count FROM users WHERE role = 'citizen'";
$result = $conn->query($sql);
if ($result && $result->num_rows > 0) {
    $citizen_count = $result->fetch_assoc()['count'];
}

// Staff count (including all non-citizen roles)
$sql = "SELECT COUNT(*) AS count FROM users WHERE role IN ('staff', 'it_help_desk', 'admin', 'auditor')";
$result = $conn->query($sql);
if ($result && $result->num_rows > 0) {
    $staff_count = $result->fetch_assoc()['count'];
}

// Report count
$sql = "SELECT COUNT(*) AS count FROM reports";
$result = $conn->query($sql);
if ($result && $result->num_rows > 0) {
    $report_count = $result->fetch_assoc()['count'];
}

// Department count
$sql = "SELECT COUNT(*) AS count FROM departments";
$result = $conn->query($sql);
if ($result && $result->num_rows > 0) {
    $department_count = $result->fetch_assoc()['count'];
}

// Fetch all reports with user full name and department name
$reports = [];
$sql = "SELECT 
            r.*, 
            u.full_name AS citizen_name,
            d.department_name
        FROM 
            reports r 
        LEFT JOIN 
            users u ON r.citizen_id = u.user_id 
        LEFT JOIN 
            departments d ON r.assigned_to_department_id = d.department_id
        ORDER BY 
            r.created_at DESC";
$result = $conn->query($sql);
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $reports[] = $row;
    }
}

$conn->close();
?>

<div class="container mx-auto mt-8 p-6 bg-white rounded-lg shadow-xl">
    <h1 class="text-4xl font-extrabold text-gray-800 mb-6 border-b-2 border-blue-500 pb-2">Admin Dashboard</h1>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <!-- Citizens Card -->
        <div class="bg-blue-100 p-6 rounded-lg shadow-md flex items-center justify-between transition-transform duration-200 transform hover:scale-105">
            <div>
                <h3 class="text-lg font-semibold text-blue-800">Total Citizens</h3>
                <p class="text-4xl font-bold text-blue-600 mt-1"><?= htmlspecialchars($citizen_count) ?></p>
            </div>
            <div class="text-blue-500">
                <i class="fas fa-users fa-3x"></i>
            </div>
        </div>

        <!-- Staff Card -->
        <div class="bg-green-100 p-6 rounded-lg shadow-md flex items-center justify-between transition-transform duration-200 transform hover:scale-105">
            <div>
                <h3 class="text-lg font-semibold text-green-800">Total Staff</h3>
                <p class="text-4xl font-bold text-green-600 mt-1"><?= htmlspecialchars($staff_count) ?></p>
            </div>
            <div class="text-green-500">
                <i class="fas fa-user-tie fa-3x"></i>
            </div>
        </div>

        <!-- Reports Card -->
        <div class="bg-yellow-100 p-6 rounded-lg shadow-md flex items-center justify-between transition-transform duration-200 transform hover:scale-105">
            <div>
                <h3 class="text-lg font-semibold text-yellow-800">Total Reports</h3>
                <p class="text-4xl font-bold text-yellow-600 mt-1"><?= htmlspecialchars($report_count) ?></p>
            </div>
            <div class="text-yellow-500">
                <i class="fas fa-clipboard-list fa-3x"></i>
            </div>
        </div>

        <!-- Departments Card -->
        <div class="bg-purple-100 p-6 rounded-lg shadow-md flex items-center justify-between transition-transform duration-200 transform hover:scale-105">
            <div>
                <h3 class="text-lg font-semibold text-purple-800">Departments</h3>
                <p class="text-4xl font-bold text-purple-600 mt-1"><?= htmlspecialchars($department_count) ?></p>
            </div>
            <div class="text-purple-500">
                <i class="fas fa-building fa-3x"></i>
            </div>
        </div>
    </div>

    <div class="mt-8">
        <h2 class="text-2xl font-bold text-gray-800 mb-4">Quick Actions</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            <a href="manage_reports.php" class="bg-gray-200 p-6 rounded-lg shadow-md hover:bg-gray-300 transition-colors duration-200 text-center flex items-center justify-center">
                <i class="fas fa-clipboard-list mr-2 text-xl"></i> Manage Reports
            </a>
            <a href="manage_staff.php" class="bg-gray-200 p-6 rounded-lg shadow-md hover:bg-gray-300 transition-colors duration-200 text-center flex items-center justify-center">
                <i class="fas fa-user-cog mr-2 text-xl"></i> Manage Staff
            </a>
            <a href="manage_users.php" class="bg-gray-200 p-6 rounded-lg shadow-md hover:bg-gray-300 transition-colors duration-200 text-center flex items-center justify-center">
                <i class="fas fa-users-cog mr-2 text-xl"></i> Manage All Users
            </a>
            <a href="manage_notices.php" class="bg-gray-200 p-6 rounded-lg shadow-md hover:bg-gray-300 transition-colors duration-200 text-center flex items-center justify-center">
                <i class="fas fa-bullhorn mr-2 text-xl"></i> Manage Notices
            </a>
            <a href="analytics.php" class="bg-gray-200 p-6 rounded-lg shadow-md hover:bg-gray-300 transition-colors duration-200 text-center flex items-center justify-center">
                <i class="fas fa-chart-line mr-2 text-xl"></i> View Analytics
            </a>
            <a href="manage_departments.php" class="bg-gray-200 p-6 rounded-lg shadow-md hover:bg-gray-300 transition-colors duration-200 text-center flex items-center justify-center">
                <i class="fas fa-sitemap mr-2 text-xl"></i> Manage Departments
            </a>
            <a href="settings.php" class="bg-gray-200 p-6 rounded-lg shadow-md hover:bg-gray-300 transition-colors duration-200 text-center flex items-center justify-center">
                <i class="fas fa-cogs mr-2 text-xl"></i> Settings
            </a>
        </div>
    </div>

    <!-- Reports Section -->
    <div class="mt-8">
        <h2 class="text-2xl font-bold text-gray-800 mb-4">All Reports</h2>
        <?php if (empty($reports)): ?>
            <div class="text-center text-gray-500 py-10">
                <p class="text-lg">No reports available at this time.</p>
            </div>
        <?php else: ?>
            <div class="overflow-x-auto">
                <table class="min-w-full bg-white border border-gray-200">
                    <thead>
                        <tr>
                            <th class="border-b-2 border-gray-200 px-4 py-2 text-left">Report ID</th>
                            <th class="border-b-2 border-gray-200 px-4 py-2 text-left">Citizen Name</th>
                            <th class="border-b-2 border-gray-200 px-4 py-2 text-left">Category</th>
                            <th class="border-b-2 border-gray-200 px-4 py-2 text-left">Department</th>
                            <th class="border-b-2 border-gray-200 px-4 py-2 text-left">Status</th>
                            <th class="border-b-2 border-gray-200 px-4 py-2 text-left">Created At</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($reports as $report): ?>
                            <tr>
                                <td class="border-b border-gray-200 px-4 py-2"><?= htmlspecialchars($report['report_id']) ?></td>
                                <td class="border-b border-gray-200 px-4 py-2"><?= htmlspecialchars($report['citizen_name']) ?></td>
                                <td class="border-b border-gray-200 px-4 py-2"><?= htmlspecialchars($report['category']) ?></td>
                                <td class="border-b border-gray-200 px-4 py-2">
                                    <?php 
                                        echo htmlspecialchars($report['department_name'] ?? 'Not Assigned');
                                    ?>
                                </td>
                                <td class="border-b border-gray-200 px-4 py-2"><?= htmlspecialchars(ucfirst($report['status'])) ?></td>
                                <td class="border-b border-gray-200 px-4 py-2"><?= date('F j, Y, g:i a', strtotime($report['created_at'])) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php
include_once 'admin_footer.php';
?>
