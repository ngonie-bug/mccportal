<?php
session_start();
include_once '../includes/config.php';
include_once 'admin_header.php';

// Check if the user is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

// Get filter values from the URL, defaulting to 'all' if not set
$status_filter = $_GET['status'] ?? 'all';
$year_filter = $_GET['year'] ?? 'all';

// Build the SQL query dynamically based on the filters
$sql = "SELECT report_id, citizen_id, category, description, status, created_at FROM reports";
$where_clauses = [];
$bind_params = '';
$param_values = [];

if ($status_filter !== 'all') {
    $where_clauses[] = "status = ?";
    $bind_params .= "s";
    $param_values[] = $status_filter;
}

if ($year_filter !== 'all') {
    $where_clauses[] = "YEAR(created_at) = ?";
    $bind_params .= "s";
    $param_values[] = $year_filter;
}

if (!empty($where_clauses)) {
    $sql .= " WHERE " . implode(" AND ", $where_clauses);
}

$sql .= " ORDER BY created_at DESC";

// Prepare and execute the statement
$stmt = $conn->prepare($sql);
if ($stmt === false) {
    die('MySQL prepare error: ' . $conn->error);
}

if (!empty($param_values)) {
    $stmt->bind_param($bind_params, ...$param_values);
}

$stmt->execute();
$result = $stmt->get_result();
$reports = $result->fetch_all(MYSQLI_ASSOC);

$stmt->close();
$conn->close();

// Generate a list of years for the filter dropdown
$current_year = date('Y');
$years = range(2020, $current_year); // Adjusted year range
?>

<div class="container-fluid mx-auto px-4 py-8">
<h1 class="text-3xl font-bold text-gray-800 mb-6">Manage Reports</h1>

<div class="mb-4 flex flex-col sm:flex-row space-y-4 sm:space-y-0 sm:space-x-4">
    <!-- Status Filter -->
    <div class="flex-1">
        <label for="status-filter" class="font-medium text-gray-700">Filter by Status:</label>
        <select id="status-filter" onchange="applyFilters()"
            class="ml-2 p-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
            <option value="all" <?= $status_filter === 'all' ? 'selected' : '' ?>>All</option>
            <option value="submitted" <?= $status_filter === 'submitted' ? 'selected' : '' ?>>Submitted</option>
            <option value="in_progress" <?= $status_filter === 'in_progress' ? 'selected' : '' ?>>In Progress</option>
            <option value="resolved" <?= $status_filter === 'resolved' ? 'selected' : '' ?>>Resolved</option>
            <option value="unresolved" <?= $status_filter === 'unresolved' ? 'selected' : '' ?>>Unresolved</option>
        </select>
    </div>

    <!-- Year Filter -->
    <div class="flex-1">
        <label for="year-filter" class="font-medium text-gray-700">Filter by Year:</label>
        <select id="year-filter" onchange="applyFilters()"
            class="ml-2 p-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
            <option value="all" <?= $year_filter === 'all' ? 'selected' : '' ?>>All Years</option>
            <?php foreach ($years as $year): ?>
                <option value="<?= $year ?>" <?= ($year_filter == $year) ? 'selected' : '' ?>><?= $year ?></option>
            <?php endforeach; ?>
        </select>
    </div>
</div>

<div class="bg-white rounded-lg shadow-md overflow-hidden">
    <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
            <tr>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Report ID
                </th>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Category
                </th>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Description
                </th>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Status
                </th>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Date
                </th>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Actions
                </th>
            </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200">
            <?php if (!empty($reports)): ?>
                <?php foreach ($reports as $report): ?>
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                            <?= htmlspecialchars($report['report_id']) ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            <?= ucwords(str_replace('_', ' ', htmlspecialchars($report['category']))) ?>
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-500 max-w-xs overflow-hidden truncate" title="<?= htmlspecialchars($report['description']) ?>">
                            <?= htmlspecialchars($report['description']) ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full
                                <?= $report['status'] === 'submitted' ? 'bg-blue-100 text-blue-800' : '' ?>
                                <?= $report['status'] === 'in_progress' ? 'bg-yellow-100 text-yellow-800' : '' ?>
                                <?= $report['status'] === 'resolved' ? 'bg-green-100 text-green-800' : '' ?>
                                <?= $report['status'] === 'unresolved' ? 'bg-red-100 text-red-800' : '' ?>">
                                <?= ucwords(str_replace('_', ' ', $report['status'])) ?>
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            <?= date('Y-m-d', strtotime($report['created_at'])) ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                            <a href="view_report.php?id=<?= $report['report_id'] ?>" class="text-blue-600 hover:text-blue-900 transition-colors duration-200">View Details</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="6" class="px-6 py-4 whitespace-nowrap text-center text-sm text-gray-500">
                        No reports found matching the selected filters.
                    </td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

</div>

<script>
function applyFilters() {
    const statusFilter = document.getElementById('status-filter').value;
    const yearFilter = document.getElementById('year-filter').value;
    // Fix: Use template literals to correctly form the URL string
    window.location.href = `manage_reports.php?status=${statusFilter}&year=${yearFilter}`;
}
</script>

<?php include_once 'admin_footer.php'; ?>