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
$department_id = null;
$department_name = 'N/A';

// Fetch the staff's department ID and name
$stmt = $conn->prepare("SELECT d.department_id, d.department_name FROM staff s JOIN departments d ON s.department_id = d.department_id WHERE s.user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($department_id, $department_name);
$stmt->fetch();
$stmt->close();

// Check if a department is assigned
if (!$department_id) {
    echo "<div class='text-center py-8'><p class='text-red-500 text-lg font-semibold'>Error: No department assigned to this user. Please contact an administrator.</p></div>";
    exit();
}

// Sanitize and get the status filter from the URL
$status_filter = isset($_GET['status']) ? htmlspecialchars($_GET['status']) : 'all';

// Prepare SQL query based on the filter and department assignment
$reports = [];
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

// Get report counts for dashboard stats
$total_reports = 0;
$submitted_reports = 0;
$in_progress_reports = 0;
$resolved_reports = 0;
$unresolved_reports = 0;

// Use prepared statements for each count to prevent SQL injection
$stmt_total = $conn->prepare("SELECT COUNT(*) FROM reports WHERE assigned_to_department_id = ?");
$stmt_submitted = $conn->prepare("SELECT COUNT(*) FROM reports WHERE assigned_to_department_id = ? AND status = 'submitted'");
$stmt_in_progress = $conn->prepare("SELECT COUNT(*) FROM reports WHERE assigned_to_department_id = ? AND status = 'in_progress'");
$stmt_resolved = $conn->prepare("SELECT COUNT(*) FROM reports WHERE assigned_to_department_id = ? AND status = 'resolved'");
$stmt_unresolved = $conn->prepare("SELECT COUNT(*) FROM reports WHERE assigned_to_department_id = ? AND status = 'unresolved'");

if ($department_id) {
    $stmt_total->bind_param("i", $department_id);
    $stmt_submitted->bind_param("i", $department_id);
    $stmt_in_progress->bind_param("i", $department_id);
    $stmt_resolved->bind_param("i", $department_id);
    $stmt_unresolved->bind_param("i", $department_id);

    $stmt_total->execute();
    $stmt_total->bind_result($total_reports);
    $stmt_total->fetch();
    $stmt_total->close();

    $stmt_submitted->execute();
    $stmt_submitted->bind_result($submitted_reports);
    $stmt_submitted->fetch();
    $stmt_submitted->close();

    $stmt_in_progress->execute();
    $stmt_in_progress->bind_result($in_progress_reports);
    $stmt_in_progress->fetch();
    $stmt_in_progress->close();

    $stmt_resolved->execute();
    $stmt_resolved->bind_result($resolved_reports);
    $stmt_resolved->fetch();
    $stmt_resolved->close();

    $stmt_unresolved->execute();
    $stmt_unresolved->bind_result($unresolved_reports);
    $stmt_unresolved->fetch();
    $stmt_unresolved->close();
}

$conn->close();
?>

<!-- Add Chart.js library via CDN -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<div class="container mx-auto p-4 sm:p-6 lg:p-8">
    <h1 class="text-4xl font-extrabold text-gray-900 mb-2">Staff Dashboard</h1>
    <p class="text-xl text-gray-600 mb-6">
        <span class="font-bold">Department:</span> <?= htmlspecialchars($department_name); ?>
    </p>

    <!-- Dashboard Stats -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-6 mb-8">
        <div class="bg-blue-600 text-white rounded-xl shadow-lg p-6 flex flex-col items-center justify-center transition-transform transform hover:scale-105">
            <h5 class="text-xl font-semibold opacity-90">Total Reports</h5>
            <h3 class="text-5xl font-bold mt-2"><?= $total_reports; ?></h3>
        </div>
        <div class="bg-blue-400 text-white rounded-xl shadow-lg p-6 flex flex-col items-center justify-center transition-transform transform hover:scale-105">
            <h5 class="text-xl font-semibold opacity-90">Submitted</h5>
            <h3 class="text-5xl font-bold mt-2"><?= $submitted_reports; ?></h3>
        </div>
        <div class="bg-yellow-500 text-white rounded-xl shadow-lg p-6 flex flex-col items-center justify-center transition-transform transform hover:scale-105">
            <h5 class="text-xl font-semibold opacity-90">In Progress</h5>
            <h3 class="text-5xl font-bold mt-2"><?= $in_progress_reports; ?></h3>
        </div>
        <div class="bg-green-600 text-white rounded-xl shadow-lg p-6 flex flex-col items-center justify-center transition-transform transform hover:scale-105">
            <h5 class="text-xl font-semibold opacity-90">Resolved</h5>
            <h3 class="text-5xl font-bold mt-2"><?= $resolved_reports; ?></h3>
        </div>
        <div class="bg-red-500 text-white rounded-xl shadow-lg p-6 flex flex-col items-center justify-center transition-transform transform hover:scale-105">
            <h5 class="text-xl font-semibold opacity-90">Unresolved</h5>
            <h3 class="text-5xl font-bold mt-2"><?= $unresolved_reports; ?></h3>
        </div>
    </div>

    <!-- New Analytics Section with a Pie Chart -->
    <div class="bg-white rounded-xl shadow-lg p-6 mb-8">
        <h2 class="text-2xl font-bold text-gray-800 mb-4">Reports Status Breakdown</h2>
        <div class="relative w-full max-w-xl mx-auto h-96">
            <!-- This is the canvas where the chart will be drawn -->
            <canvas id="reportsPieChart"></canvas>
        </div>
    </div>

    <div class="mb-4 flex items-center justify-between">
        <h2 class="text-2xl font-bold text-gray-800">Assigned Reports</h2>
        <div class="flex items-center space-x-2">
            <label for="status-filter" class="font-medium text-gray-700">Filter by Status:</label>
            <select id="status-filter" onchange="window.location.href = 'staff_dashboard.php?status=' + this.value"
                class="p-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                <option value="all" <?= $status_filter === 'all' ? 'selected' : '' ?>>All Reports</option>
                <option value="submitted" <?= $status_filter === 'submitted' ? 'selected' : '' ?>>Submitted</option>
                <option value="in_progress" <?= $status_filter === 'in_progress' ? 'selected' : '' ?>>In Progress</option>
                <option value="resolved" <?= $status_filter === 'resolved' ? 'selected' : '' ?>>Resolved</option>
                <option value="unresolved" <?= $status_filter === 'unresolved' ? 'selected' : '' ?>>Unresolved</option>
            </select>
        </div>
    </div>

    <!-- Reports Table -->
    <div class="bg-white rounded-xl shadow-lg overflow-hidden border border-gray-200">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-100">
                    <tr>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Report ID</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Category</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Description</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Status</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Submitted</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php if (!empty($reports)): ?>
                        <?php foreach ($reports as $report): ?>
                            <tr class="hover:bg-gray-50 transition-colors duration-150">
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900"><?= htmlspecialchars($report['report_id']) ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700"><?= htmlspecialchars($report['category']) ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700 max-w-xs truncate"><?= htmlspecialchars($report['description']) ?></td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <?php
                                        $status_class = 'bg-gray-200 text-gray-800';
                                        switch ($report['status']) {
                                            case 'submitted': $status_class = 'bg-blue-100 text-blue-800'; break;
                                            case 'in_progress': $status_class = 'bg-yellow-100 text-yellow-800'; break;
                                            case 'resolved': $status_class = 'bg-green-100 text-green-800'; break;
                                            case 'unresolved': $status_class = 'bg-red-100 text-red-800'; break;
                                        }
                                    ?>
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?= $status_class ?>">
                                        <?= ucwords(str_replace('_', ' ', $report['status'])) ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?= date('Y-m-d H:i', strtotime($report['created_at'])) ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    <a href="view_report.php?id=<?= htmlspecialchars($report['report_id']) ?>" class="text-blue-600 hover:text-blue-900 transition-colors duration-200 font-semibold">View Details</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" class="px-6 py-4 text-center text-sm text-gray-500">No reports found for this status.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
    // PHP variables are converted to a JavaScript object
    const reportData = {
        submitted: <?= $submitted_reports; ?>,
        in_progress: <?= $in_progress_reports; ?>,
        resolved: <?= $resolved_reports; ?>,
        unresolved: <?= $unresolved_reports; ?>
    };

    // Get the canvas element
    const ctx = document.getElementById('reportsPieChart').getContext('2d');

    // Create the pie chart
    new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: ['Submitted', 'In Progress', 'Resolved', 'Unresolved'],
            datasets: [{
                data: [
                    reportData.submitted,
                    reportData.in_progress,
                    reportData.resolved,
                    reportData.unresolved
                ],
                backgroundColor: [
                    '#3B82F6', // Blue for Submitted
                    '#F59E0B', // Yellow for In Progress
                    '#10B981', // Green for Resolved
                    '#EF4444'  // Red for Unresolved
                ],
                hoverOffset: 4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'top',
                },
                title: {
                    display: true,
                    text: 'Report Status Distribution',
                    font: {
                        size: 10
                    }
                }
            }
        },
    });
</script>

<?php include_once 'staff_footer.php'; ?>
