<?php
session_start();
include_once '../includes/config.php';
include_once 'staff_header.php';

// Check if the user is logged in and has a staff-level role
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'], ['staff', 'it_help_desk', 'auditor', 'admin'])) {
    header("Location: ../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['user_role'];
$department_id = null;
$department_name = 'N/A';

// Fetch the staff member's department_id and department name
// This is critical for filtering all the analytics data.
$sql_get_dept = "SELECT s.department_id, d.department_name FROM staff s JOIN departments d ON s.department_id = d.department_id WHERE s.user_id = ?";
$stmt_get_dept = $conn->prepare($sql_get_dept);
$stmt_get_dept->bind_param("i", $user_id);
$stmt_get_dept->execute();
$result_dept = $stmt_get_dept->get_result();

if ($result_dept->num_rows > 0) {
    $department_row = $result_dept->fetch_assoc();
    $department_id = $department_row['department_id'];
    $department_name = $department_row['department_name'];
} else {
    // If department is not found, log out for security
    session_destroy();
    header("Location: ../login.php");
    exit();
}
$stmt_get_dept->close();

// --- PHP database queries for department-specific analytics ---
$analytics_data = [
    'total_tickets' => 0,
    'closed_tickets' => 0,
    'open_tickets' => 0,
    'avg_resolution_time' => 'N/A',
    'tickets_by_category' => [],
    'recent_tickets' => []
];

if ($department_id) {
    // 1. Total, Open, and Closed tickets for the department
    $stmt_total = $conn->prepare("SELECT COUNT(*) FROM reports WHERE assigned_to_department_id = ?");
    $stmt_open = $conn->prepare("SELECT COUNT(*) FROM reports WHERE assigned_to_department_id = ? AND status IN ('submitted', 'in_progress', 'unresolved')");
    $stmt_closed = $conn->prepare("SELECT COUNT(*) FROM reports WHERE assigned_to_department_id = ? AND status = 'resolved'");

    $stmt_total->bind_param("i", $department_id);
    $stmt_open->bind_param("i", $department_id);
    $stmt_closed->bind_param("i", $department_id);

    $stmt_total->execute();
    $stmt_total->bind_result($analytics_data['total_tickets']);
    $stmt_total->fetch();
    $stmt_total->close();

    $stmt_open->execute();
    $stmt_open->bind_result($analytics_data['open_tickets']);
    $stmt_open->fetch();
    $stmt_open->close();
    
    $stmt_closed->execute();
    $stmt_closed->bind_result($analytics_data['closed_tickets']);
    $stmt_closed->fetch();
    $stmt_closed->close();
    
    // 2. Average resolution time for the department
    $stmt_avg_time = $conn->prepare("SELECT AVG(TIMESTAMPDIFF(MINUTE, created_at, closed_at)) FROM reports WHERE assigned_to_department_id = ? AND status = 'resolved' AND closed_at IS NOT NULL");
    $stmt_avg_time->bind_param("i", $department_id);
    $stmt_avg_time->execute();
    $stmt_avg_time->bind_result($avg_minutes);
    $stmt_avg_time->fetch();
    $stmt_avg_time->close();
    
    if ($avg_minutes !== null) {
        $hours = floor($avg_minutes / 60);
        $minutes = $avg_minutes % 60;
        $analytics_data['avg_resolution_time'] = "{$hours}h {$minutes}m";
    }

    // 3. Tickets by category for the department
    $stmt_by_category = $conn->prepare("SELECT category, COUNT(*) as count FROM reports WHERE assigned_to_department_id = ? GROUP BY category ORDER BY count DESC");
    $stmt_by_category->bind_param("i", $department_id);
    $stmt_by_category->execute();
    $result_by_category = $stmt_by_category->get_result();
    $analytics_data['tickets_by_category'] = $result_by_category->fetch_all(MYSQLI_ASSOC);
    $stmt_by_category->close();

    // 4. Recent tickets for the department
    $stmt_recent = $conn->prepare("SELECT report_id AS id, description AS title, status FROM reports WHERE assigned_to_department_id = ? ORDER BY created_at DESC LIMIT 10");
    $stmt_recent->bind_param("i", $department_id);
    $stmt_recent->execute();
    $result_recent = $stmt_recent->get_result();
    $analytics_data['recent_tickets'] = $result_recent->fetch_all(MYSQLI_ASSOC);
    $stmt_recent->close();
}
$conn->close();

// Prepare data for the chart
$chart_labels = json_encode(array_column($analytics_data['tickets_by_category'], 'category'));
$chart_data = json_encode(array_column($analytics_data['tickets_by_category'], 'count'));
?>

<div class="container mx-auto p-6 bg-gray-100 min-h-screen">
    <div class="bg-white rounded-lg shadow-lg p-6 mb-8">
        <h1 class="text-3xl font-bold text-gray-800">Department Dashboard</h1>
        <p class="text-gray-600 mt-2">
            Welcome, <?= htmlspecialchars($_SESSION['full_name'] ?? '') ?>! Here are the key metrics for your department.
        </p>
    </div>

    <!-- Analytics Cards Section -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <div class="bg-white rounded-lg shadow p-6 border-l-4 border-blue-500">
            <div class="text-gray-500 text-sm font-medium uppercase">Total Tickets</div>
            <div class="mt-1 text-3xl font-bold text-gray-900"><?= htmlspecialchars($analytics_data['total_tickets']) ?></div>
        </div>
        <div class="bg-white rounded-lg shadow p-6 border-l-4 border-green-500">
            <div class="text-gray-500 text-sm font-medium uppercase">Closed Tickets</div>
            <div class="mt-1 text-3xl font-bold text-gray-900"><?= htmlspecialchars($analytics_data['closed_tickets']) ?></div>
        </div>
        <div class="bg-white rounded-lg shadow p-6 border-l-4 border-yellow-500">
            <div class="text-gray-500 text-sm font-medium uppercase">Open Tickets</div>
            <div class="mt-1 text-3xl font-bold text-gray-900"><?= htmlspecialchars($analytics_data['open_tickets']) ?></div>
        </div>
        <div class="bg-white rounded-lg shadow p-6 border-l-4 border-purple-500">
            <div class="text-gray-500 text-sm font-medium uppercase">Avg. Resolution Time</div>
            <div class="mt-1 text-3xl font-bold text-gray-900"><?= htmlspecialchars($analytics_data['avg_resolution_time']) ?></div>
        </div>
    </div>

    <!-- Charts and Recent Activity Section -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Tickets by Category Chart -->
        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-xl font-semibold text-gray-800 mb-4">Tickets by Category</h2>
            <canvas id="ticketsByCategoryChart"></canvas>
        </div>

        <!-- Recent Tickets Table -->
        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-xl font-semibold text-gray-800 mb-4">Recent Tickets</h2>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Ticket ID
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Title
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Status
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($analytics_data['recent_tickets'] as $ticket): ?>
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-blue-600">
                                    <a href="manage_chat.php?ticket_id=<?= htmlspecialchars($ticket['id']) ?>"><?= htmlspecialchars($ticket['id']) ?></a>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    <?= htmlspecialchars($ticket['title']) ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <?= htmlspecialchars($ticket['status']) ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const categories = <?= $chart_labels; ?>;
        const ticketCounts = <?= $chart_data; ?>;

        const ctx = document.getElementById('ticketsByCategoryChart').getContext('2d');
        const ticketsByCategoryChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: categories,
                datasets: [{
                    label: 'Number of Tickets',
                    data: ticketCounts,
                    backgroundColor: [
                        'rgba(59, 130, 246, 0.5)', // blue
                        'rgba(16, 185, 129, 0.5)', // green
                        'rgba(245, 158, 11, 0.5)', // yellow
                        'rgba(139, 92, 246, 0.5)', // purple
                        'rgba(239, 68, 68, 0.5)', // red
                    ],
                    borderColor: [
                        'rgba(59, 130, 246, 1)',
                        'rgba(16, 185, 129, 1)',
                        'rgba(245, 158, 11, 1)',
                        'rgba(139, 92, 246, 1)',
                        'rgba(239, 68, 68, 1)',
                    ],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            precision: 0
                        }
                    }
                }
            }
        });
    });
</script>

<?php include_once 'staff_footer.php'; ?>
