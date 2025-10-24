<?php
session_start();
include_once '../includes/config.php';
include_once 'staff_header.php';

// Check if the user is logged in and has a staff-level role
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'], ['staff', 'it_help_desk', 'auditor'])) {
    header("Location: ../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Check if the assigned department ID is set
if (!isset($_SESSION['assigned_to_department_id'])) {
    echo "<p class='text-red-500'>Error: No department assigned to this user.</p>";
    exit();
}

$department_id = $_SESSION['assigned_to_department_id'];

// Sanitize and get the status filter from the URL
$status_filter = isset($_GET['status']) ? $_GET['status'] : 'all';

// Prepare SQL query based on the filter and department assignment
if ($status_filter === 'all') {
    $sql = "SELECT report_id, citizen_id, category, description, status, created_at FROM reports WHERE assigned_to_department_id = ? ORDER BY created_at DESC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $department_id);
} else {
    $sql = "SELECT report_id, citizen_id, category, description, status, created_at FROM reports WHERE assigned_to_department_id = ? AND status = ? ORDER BY created_at DESC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("is", $department_id, $status_filter);
}

$stmt->execute();
$result = $stmt->get_result();
$reports = $result->fetch_all(MYSQLI_ASSOC);

$stmt->close();
$conn->close();
?>

<h1 class="text-3xl font-bold text-gray-800 mb-6">Assigned Reports</h1>

<div class="mb-4">
    <label for="status-filter" class="font-medium text-gray-700">Filter by Status:</label>
    <select id="status-filter" onchange="window.location.href = 'manage_assigned_reports.php?status=' + this.value"
            class="ml-2 p-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
        <option value="all" <?= $status_filter === 'all' ? 'selected' : '' ?>>All Reports</option>
        <option value="submitted" <?= $status_filter === 'submitted' ? 'selected' : '' ?>>Submitted</option>
        <option value="in_progress" <?= $status_filter === 'in_progress' ? 'selected' : '' ?>>In Progress</option>
        <option value="resolved" <?= $status_filter === 'resolved' ? 'selected' : '' ?>>Resolved</option>
        <option value="unresolved" <?= $status_filter === 'unresolved' ? 'selected' : '' ?>>Unresolved</option>
    </select>
</div>

<div class="bg-white rounded-lg shadow-md overflow-hidden">
    <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
            <tr>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Report ID</th>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Category</th>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Description</th>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Submitted</th>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
            </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200">
            <?php if (!empty($reports)): ?>
                <?php foreach ($reports as $report): ?>
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900"><?= htmlspecialchars($report['report_id']) ?></td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?= htmlspecialchars($report['category']) ?></td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 max-w-xs overflow-hidden text-overflow-ellipsis"><?= htmlspecialchars(substr($report['description'], 0, 50)) . (strlen($report['description']) > 50 ? '...' : '') ?></td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full
                                <?= $report['status'] === 'submitted' ? 'bg-blue-100 text-blue-800' : '' ?>
                                <?= $report['status'] === 'in_progress' ? 'bg-yellow-100 text-yellow-800' : '' ?>
                                <?= $report['status'] === 'resolved' ? 'bg-green-100 text-green-800' : '' ?>
                                <?= $report['status'] === 'unresolved' ? 'bg-red-100 text-red-800' : '' ?>">
                                <?= ucwords(str_replace('_', ' ', $report['status'])) ?>
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?= date('Y-m-d', strtotime($report['created_at'])) ?></td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                            <a href="view_report.php?id=<?= htmlspecialchars($report['report_id']) ?>" class="text-blue-600 hover:text-blue-900 transition-colors duration-200">View Details</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="6" class="px-6 py-4 whitespace-nowrap text-center text-sm text-gray-500">No reports found for this status.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php include_once 'staff_footer.php'; ?>