<?php
session_start();
include_once 'admin_header.php';
include_once '../includes/config.php';

$report = null;
$citizen = null;
$departments = [];
$message = '';
$message_class = '';

// Check if a user is logged in and is an admin or auditor
if (!isset($_SESSION['user_role']) || ($_SESSION['user_role'] !== 'admin' && $_SESSION['user_role'] !== 'auditor')) {
    header('Location: ../login.php');
    exit();
}

// Fetch all departments for the dropdown
$sql_departments = "SELECT department_id, department_name FROM departments ORDER BY department_name";
$result_departments = $conn->query($sql_departments);
if ($result_departments) {
    while ($row = $result_departments->fetch_assoc()) {
        $departments[] = $row;
    }
}

// Handle form submission to update status or assign department
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $report_id_to_update = $_POST['report_id'];

    // Handle status update
    if (isset($_POST['update_status'])) {
        $new_status = $_POST['status'];
        $sql = "UPDATE reports SET status = ? WHERE report_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("si", $new_status, $report_id_to_update);
        if ($stmt->execute()) {
            $message = 'Report status updated successfully!';
            $message_class = 'bg-green-100 text-green-800';
        } else {
            $message = 'Failed to update report status.';
            $message_class = 'bg-red-100 text-red-800';
        }
        $stmt->close();
        // Redirect to prevent form resubmission
        header("Location: view_report.php?id=" . $report_id_to_update);
        exit();
    }

    // Handle department assignment
    if (isset($_POST['assign_department'])) {
        $assigned_department_id = $_POST['assigned_department_id'];
        $sql = "UPDATE reports SET department_id = ? WHERE report_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ii", $assigned_department_id, $report_id_to_update);
        if ($stmt->execute()) {
            $message = 'Report assigned to department successfully!';
            $message_class = 'bg-green-100 text-green-800';
        } else {
            $message = 'Failed to assign report to department.';
            $message_class = 'bg-red-100 text-red-800';
        }
        $stmt->close();
        // Redirect to prevent form resubmission
        header("Location: view_report.php?id=" . $report_id_to_update);
        exit();
    }
}

// Handle report filtering
$status_filter = isset($_GET['status']) ? $_GET['status'] : 'all';

// Fetch reports based on the filter
if ($status_filter === 'all') {
    $sql_reports = "SELECT r.*, d.department_name FROM reports r LEFT JOIN departments d ON r.department_id = d.department_id ORDER BY r.created_at DESC";
} else {
    $sql_reports = "SELECT r.*, d.department_name FROM reports r LEFT JOIN departments d ON r.department_id = d.department_id WHERE r.status = ? ORDER BY r.created_at DESC";
}

$stmt_reports = $conn->prepare($sql_reports);
if ($status_filter !== 'all') {
    $stmt_reports->bind_param("s", $status_filter);
}
$stmt_reports->execute();
$result_reports = $stmt_reports->get_result();
$reports = $result_reports->fetch_all(MYSQLI_ASSOC);
$stmt_reports->close();

// Fetch citizen details if report ID is set in the URL
if (isset($_GET['id'])) {
    $report_id = $_GET['id'];
    
    // Fetch report details, including assigned department
    $sql = "SELECT r.*, d.department_name FROM reports r LEFT JOIN departments d ON r.department_id = d.department_id WHERE report_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $report_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $report = $result->fetch_assoc();
    $stmt->close();

    // Fetch citizen details
    if ($report) {
        $sql = "SELECT full_name, username, email, phone FROM users WHERE user_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $report['citizen_id']);
        $stmt->execute();
        $result = $stmt->get_result();
        $citizen = $result->fetch_assoc();
        $stmt->close();
    }
}

// Ensure the connection is closed after all queries
$conn->close();
?>

<h1 class="text-3xl font-bold text-gray-800 mb-6">Report Details</h1>

<!-- Filter Form -->
<div class="mb-4">
    <form action="view_report.php" method="GET" class="flex items-center space-x-4">
        <label for="status" class="font-medium text-gray-700">Filter by Status:</label>
        <select name="status" id="status" class="p-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
            <option value="all" <?= $status_filter === 'all' ? 'selected' : '' ?>>All</option>
            <option value="submitted" <?= $status_filter === 'submitted' ? 'selected' : '' ?>>Submitted</option>
            <option value="in_progress" <?= $status_filter === 'in_progress' ? 'selected' : '' ?>>In Progress</option>
            <option value="resolved" <?= $status_filter === 'resolved' ? 'selected' : '' ?>>Resolved</option>
            <option value="unresolved" <?= $status_filter === 'unresolved' ? 'selected' : '' ?>>Unresolved</option>
        </select>
        <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-lg">Filter</button>
    </form>
</div>

<!-- Download Reports Button -->
<div class="mt-4">
    <form action="download_reports.php" method="GET" class="flex items-center space-x-4">
        <input type="hidden" name="status" value="<?= htmlspecialchars($status_filter) ?>">
        <button type="submit" class="bg-green-600 text-white px-4 py-2 rounded-lg">Download Reports</button>
    </form>
</div>

<!-- Download Analytics Button -->
<div class="mt-4">
    <form action="download_analytics.php" method="GET" class="flex items-center space-x-4">
        <input type="hidden" name="period" value="monthly"> <!-- Default period for analytics -->
        <button type="submit" class="bg-green-600 text-white px-4 py-2 rounded-lg">Download Analytics</button>
    </form>
</div>

<?php if ($message): ?>
    <div class="p-4 mb-4 text-sm <?= $message_class ?> rounded-lg" role="alert">
        <?= htmlspecialchars($message) ?>
    </div>
<?php endif; ?>

<!-- Display Report Details -->
<?php if ($report): ?>
    <div class="bg-white rounded-lg shadow-md p-6 mb-6">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <h2 class="text-xl font-semibold text-gray-800 mb-2">Report #<?= htmlspecialchars($report['report_id']) ?></h2>
                <p class="text-gray-600 mb-1"><strong>Category:</strong> <span class="text-blue-600 font-medium"><?= htmlspecialchars($report['category']) ?></span></p>
                <p class="text-gray-600 mb-1"><strong>Status:</strong>
                    <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full
                        <?= $report['status'] === 'submitted' ? 'bg-blue-100 text-blue-800' : '' ?>
                        <?= $report['status'] === 'in_progress' ? 'bg-yellow-100 text-yellow-800' : '' ?>
                        <?= $report['status'] === 'resolved' ? 'bg-green-100 text-green-800' : '' ?>
                        <?= $report['status'] === 'unresolved' ? 'bg-red-100 text-red-800' : '' ?>">
                        <?= ucwords(str_replace('_', ' ', $report['status'])) ?>
                    </span>
                </p>
                <p class="text-gray-600 mb-1"><strong>Submitted On:</strong> <?= date('Y-m-d H:i', strtotime($report['created_at'])) ?></p>
                <p class="text-gray-600"><strong>Department:</strong> <?= htmlspecialchars($report['department_name'] ?? 'Not Assigned') ?></p>
            </div>
            
            <?php if ($citizen): ?>
            <div>
                <h2 class="text-xl font-semibold text-gray-800 mb-2">Citizen Details</h2>
                <p class="text-gray-600 mb-1"><strong>Name:</strong> <?= htmlspecialchars($citizen['full_name']) ?></p>
                <p class="text-gray-600 mb-1"><strong>Username:</strong> <?= htmlspecialchars($citizen['username']) ?></p>
                <p class="text-gray-600 mb-1"><strong>Email:</strong> <?= htmlspecialchars($citizen['email']) ?></p>
                <p class="text-gray-600"><strong>Phone:</strong> <?= htmlspecialchars($citizen['phone']) ?></p>
            </div>
            <?php endif; ?>
        </div>

        <div class="mt-6">
            <h2 class="text-xl font-semibold text-gray-800 mb-2">Description</h2>
            <p class="text-gray-700 leading-relaxed"><?= nl2br(htmlspecialchars($report['description'])) ?></p>
        </div>

        <div class="mt-6">
            <h2 class="text-xl font-semibold text-gray-800 mb-2">Update Report</h2>
            <div class="flex flex-col md:flex-row space-y-4 md:space-y-0 md:space-x-4">
                <!-- Status Update Form -->
                <div class="flex-1">
                    <form action="view_report.php?id=<?= htmlspecialchars($report['report_id']) ?>" method="POST" class="flex items-center space-x-4">
                        <input type="hidden" name="report_id" value="<?= htmlspecialchars($report['report_id']) ?>">
                        <select name="status" class="flex-grow p-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="submitted" <?= $report['status'] === 'submitted' ? 'selected' : '' ?>>Submitted</option>
                            <option value="in_progress" <?= $report['status'] === 'in_progress' ? 'selected' : '' ?>>In Progress</option>
                            <option value="resolved" <?= $report['status'] === 'resolved' ? 'selected' : '' ?>>Resolved</option>
                            <option value="unresolved" <?= $report['status'] === 'unresolved' ? 'selected' : '' ?>>Unresolved</option>
                        </select>
                        <button type="submit" name="update_status" class="bg-blue-600 text-white px-4 py-2 rounded-lg shadow hover:bg-blue-700 transition-colors duration-200">
                            Update Status
                        </button>
                    </form>
                </div>
                <!-- Department Assignment Form -->
                <div class="flex-1">
                    <form action="view_report.php?id=<?= htmlspecialchars($report['report_id']) ?>" method="POST" class="flex items-center space-x-4">
                        <input type="hidden" name="report_id" value="<?= htmlspecialchars($report['report_id']) ?>">
                        <select name="assigned_department_id" class="flex-grow p-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="">Select Department</option>
                            <?php foreach ($departments as $department): ?>
                                <option value="<?= htmlspecialchars($department['department_id']) ?>"
                                    <?= ($report['department_id'] == $department['department_id']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($department['department_name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <button type="submit" name="assign_department" class="bg-indigo-600 text-white px-4 py-2 rounded-lg shadow hover:bg-indigo-700 transition-colors duration-200">
                            Assign Department
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
<?php else: ?>
    <div class="bg-white p-6 rounded-lg shadow-md text-center">
        <p class="text-lg text-gray-500">No report found with the provided ID.</p>
    </div>
<?php endif; ?>

<?php include_once 'admin_footer.php'; ?>