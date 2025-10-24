<?php
include_once 'staff_header.php';
include_once '../includes/config.php';

// Check if the user is logged in and has a staff-level role
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'], ['staff', 'it_help_desk', 'auditor'])) {
    header("Location: ../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$department_id = null;
$department_name = 'N/A';

// Fetch the staff's department ID
$stmt = $conn->prepare("SELECT department_id FROM staff WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($department_id);
$stmt->fetch();
$stmt->close();

$reports = [];
$status_filter = isset($_GET['status']) ? $_GET['status'] : 'all';

// Prepare the base SQL query
$sql = "SELECT report_id, report_type, description, location, status, date_submitted FROM reports WHERE assigned_to_department_id = ?";
$params = ["i", $department_id];

// Append the status filter if it's not 'all'
if ($status_filter !== 'all') {
    $sql .= " AND status = ?";
    $params[0] .= "s";
    $params[] = $status_filter;
}

$sql .= " ORDER BY date_submitted DESC";

$stmt = $conn->prepare($sql);
if ($stmt) {
    // Dynamically bind parameters based on the number of parameters
    $stmt->bind_param(...$params);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $reports[] = $row;
    }
    $stmt->close();
}
$conn->close();

$title_map = [
    'all' => 'All Reports',
    'submitted' => 'Submitted Reports',
    'in_progress' => 'In Progress Reports',
    'resolved' => 'Resolved Reports',
    'unresolved' => 'Unresolved Reports'
];

$page_title = $title_map[$status_filter] ?? 'Reports';
?>

<div class="container mx-auto p-4 sm:p-6 lg:p-8">
    <h1 class="text-3xl sm:text-4xl font-extrabold text-gray-900 mb-6">
        <?= htmlspecialchars($page_title) ?>
    </h1>

    <?php if (empty($reports)) : ?>
        <div class="bg-white rounded-xl shadow-lg p-6 text-center">
            <p class="text-gray-600 text-lg">No reports found for this department.</p>
        </div>
    <?php else : ?>
        <div class="bg-white rounded-xl shadow-lg overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Report ID
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Type
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Description
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Location
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Status
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Date Submitted
                            </th>
                            <th scope="col" class="relative px-6 py-3">
                                <span class="sr-only">View</span>
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($reports as $report) : ?>
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                    <?= htmlspecialchars($report['report_id']) ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <?= htmlspecialchars($report['report_type']) ?>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-500 max-w-sm truncate">
                                    <?= htmlspecialchars($report['description']) ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <?= htmlspecialchars($report['location']) ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full
                                    <?php
                                    switch ($report['status']) {
                                        case 'submitted':
                                            echo 'bg-blue-100 text-blue-800';
                                            break;
                                        case 'in_progress':
                                            echo 'bg-yellow-100 text-yellow-800';
                                            break;
                                        case 'resolved':
                                            echo 'bg-green-100 text-green-800';
                                            break;
                                        case 'unresolved':
                                            echo 'bg-red-100 text-red-800';
                                            break;
                                        default:
                                            echo 'bg-gray-100 text-gray-800';
                                    }
                                    ?>">
                                        <?= htmlspecialchars(ucfirst(str_replace('_', ' ', $report['status']))) ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <?= htmlspecialchars(date("Y-m-d", strtotime($report['date_submitted']))) ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <a href="view_report.php?id=<?= htmlspecialchars($report['report_id']) ?>" class="text-blue-600 hover:text-blue-900">View</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php include_once 'staff_footer.php'; ?>
