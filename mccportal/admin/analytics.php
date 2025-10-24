<?php
session_start();
include_once '../includes/config.php'; // Ensure this file is correctly included
include_once 'admin_header.php'; // Ensure this file is correctly included

// Check if a user is logged in and is an admin
if (!isset($_SESSION['user_role']) || ($_SESSION['user_role'] !== 'admin' && $_SESSION['user_role'] !== 'auditor')) {
    header('Location: ../login.php');
    exit();
}

// Set the default time period
$selected_period = $_GET['period'] ?? 'monthly';
$date_format = '%Y-%m';
$trend_title = 'Monthly Report Trend';

// Adjust the SQL and title based on the selected period
switch ($selected_period) {
    case 'daily':
        $date_format = '%Y-%m-%d';
        $trend_title = 'Daily Report Trend';
        break;
    case 'weekly':
        $date_format = '%Y-%u';
        $trend_title = 'Weekly Report Trend';
        break;
    case 'yearly':
        $date_format = '%Y';
        $trend_title = 'Yearly Report Trend';
        break;
}

// Fetch total reports count
$sql_total_reports = "SELECT COUNT(*) AS total_reports FROM reports";
$result_total_reports = $conn->query($sql_total_reports);
$total_reports = $result_total_reports ? $result_total_reports->fetch_assoc()['total_reports'] : 0;

// Fetch total users count
$sql_total_users = "SELECT COUNT(*) AS total_users FROM users"; // Ensure the 'users' table exists
$result_total_users = $conn->query($sql_total_users);
$total_users = $result_total_users ? $result_total_users->fetch_assoc()['total_users'] : 0;

// Fetch reports by status
$sql_reports_by_status = "SELECT status, COUNT(*) AS count FROM reports GROUP BY status";
$result_reports_by_status = $conn->query($sql_reports_by_status);
$reports_by_status = [];
if ($result_reports_by_status) {
    while ($row = $result_reports_by_status->fetch_assoc()) {
        $reports_by_status[] = $row;
    }
}

// Fetch reports by category
$sql_reports_by_category = "SELECT category, COUNT(*) AS count FROM reports GROUP BY category ORDER BY count DESC";
$result_reports_by_category = $conn->query($sql_reports_by_category);
$reports_by_category = [];
if ($result_reports_by_category) {
    while ($row = $result_reports_by_category->fetch_assoc()) {
        $reports_by_category[] = $row;
    }
}

// Fetch reports by department
$sql_reports_by_department = "SELECT d.department_name AS department, COUNT(r.report_id) AS count 
                               FROM reports r 
                               JOIN departments d ON r.assigned_to_department_id = d.department_id 
                               GROUP BY d.department_name ORDER BY count DESC";
$result_reports_by_department = $conn->query($sql_reports_by_department);
$reports_by_department = [];
if ($result_reports_by_department) {
    while ($row = $result_reports_by_department->fetch_assoc()) {
        $reports_by_department[] = $row;
    }
}

// Fetch reports over time by category
$sql_reports_by_time_period = "SELECT DATE_FORMAT(created_at, '$date_format') AS time_period, category, COUNT(*) AS count 
                                FROM reports 
                                GROUP BY time_period, category ORDER BY time_period ASC";
$result_reports_by_time_period = $conn->query($sql_reports_by_time_period);
$reports_by_time_period = [];
while ($row = $result_reports_by_time_period->fetch_assoc()) {
    $reports_by_time_period[] = $row;
}

$conn->close();

// Prepare data for the charts
$category_chart_labels = json_encode(array_column($reports_by_category, 'category'));
$category_chart_data = json_encode(array_column($reports_by_category, 'count'));

$status_chart_labels = json_encode(array_column($reports_by_status, 'status'));
$status_chart_data = json_encode(array_column($reports_by_status, 'count'));

$department_chart_labels = json_encode(array_column($reports_by_department, 'department'));
$department_chart_data = json_encode(array_column($reports_by_department, 'count'));

// Prepare trend chart data
$trend_data = [];
foreach ($reports_by_time_period as $item) {
    $trend_data[$item['time_period']][$item['category']] = $item['count'];
}

$trend_chart_labels = json_encode(array_keys($trend_data));
$trend_chart_data = json_encode(array_map(function($category) use ($trend_data) {
    return array_map(function($date) use ($trend_data, $category) {
        return $trend_data[$date][$category] ?? 0;
    }, array_keys($trend_data));
}, array_column($reports_by_category, 'category')));

// Calculate percentages for each status
$status_percentages = [];
foreach ($reports_by_status as $status_item) {
    $percentage = ($total_reports > 0) ? round(($status_item['count'] / $total_reports) * 100, 1) : 0;
    $status_percentages[$status_item['status']] = $percentage;
}
?>

<style>
.dashboard-card {
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}
.dashboard-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
}
.stat-card {
    height: 100%;
}
.chart-container {
    position: relative;
    height: 280px;
    margin-bottom: 1rem;
}
.trend-chart-container {
    position: relative;
    height: 300px;
    margin-bottom: 1rem;
}
.filter-btn {
    transition: all 0.2s ease;
}
</style>

<div class="container-fluid mx-auto px-4 py-6">
    <h2 class="text-2xl font-bold text-gray-800 mb-4">Analytics Dashboard</h2>

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <div class="stat-card bg-white p-4 rounded-lg shadow-sm dashboard-card flex flex-col items-center text-center">
            <div class="p-2 bg-blue-100 rounded-full mb-2">
                <i class="fas fa-list-ul text-blue-600 text-xl"></i>
            </div>
            <p class="text-xs font-medium text-gray-500">Total Reports</p>
            <p class="text-2xl font-bold text-gray-900 mt-1"><?= htmlspecialchars($total_reports) ?></p>
        </div>

        <div class="stat-card bg-white p-4 rounded-lg shadow-sm dashboard-card flex flex-col items-center text-center">
            <div class="p-2 bg-green-100 rounded-full mb-2">
                <i class="fas fa-check-circle text-green-600 text-xl"></i>
            </div>
            <p class="text-xs font-medium text-gray-500">Resolved</p>
            <p class="text-2xl font-bold text-gray-900 mt-1"><?= htmlspecialchars($reports_by_status[0]['count'] ?? 0) ?></p>
            <p class="text-xs text-gray-400 mt-1"><?= htmlspecialchars($status_percentages['resolved'] ?? 0) ?>%</p>
        </div>

        <div class="stat-card bg-white p-4 rounded-lg shadow-sm dashboard-card flex flex-col items-center text-center">
            <div class="p-2 bg-blue-100 rounded-full mb-2">
                <i class="fas fa-hourglass-half text-blue-600 text-xl"></i>
            </div>
            <p class="text-xs font-medium text-gray-500">In Progress</p>
            <p class="text-2xl font-bold text-gray-900 mt-1"><?= htmlspecialchars($reports_by_status[2]['count'] ?? 0) ?></p>
            <p class="text-xs text-gray-400 mt-1"><?= htmlspecialchars($status_percentages['in_progress'] ?? 0) ?>%</p>
        </div>

        <div class="stat-card bg-white p-4 rounded-lg shadow-sm dashboard-card flex flex-col items-center text-center">
            <div class="p-2 bg-red-100 rounded-full mb-2">
                <i class="fas fa-exclamation-triangle text-red-600 text-xl"></i>
            </div>
            <p class="text-xs font-medium text-gray-500">Unresolved</p>
            <p class="text-2xl font-bold text-gray-900 mt-1"><?= htmlspecialchars($reports_by_status[3]['count'] ?? 0) ?></p>
            <p class="text-xs text-gray-400 mt-1"><?= htmlspecialchars($status_percentages['unresolved'] ?? 0) ?>%</p>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-5 mb-6">
        <div class="bg-white p-5 rounded-lg shadow-sm dashboard-card">
            <h3 class="text-lg font-semibold text-gray-800 mb-3">Reports by Category</h3>
            <p class="text-sm text-gray-600 mb-3">Distribution of reports across different categories</p>
            <div class="chart-container">
                <canvas id="reportsByCategoryChart"></canvas>
            </div>
        </div>

        <div class="bg-white p-5 rounded-lg shadow-sm dashboard-card">
            <h3 class="text-lg font-semibold text-gray-800 mb-3">Reports by Status</h3>
            <p class="text-sm text-gray-600 mb-3">Percentage of reports by their current status</p>
            <div class="chart-container">
                <canvas id="reportsByStatusChart"></canvas>
            </div>
        </div>
    </div>

    <div class="bg-white p-5 rounded-lg shadow-sm dashboard-card mb-6">
        <h3 class="text-lg font-semibold text-gray-800 mb-3"><?= $trend_title ?></h3>
        <p class="text-sm text-gray-600 mb-3">Trend of reports over time. Filter by time period.</p>
        <div class="flex flex-wrap gap-2 mb-4">
            <a href="?period=daily" class="px-3 py-1 rounded-full text-xs font-medium filter-btn <?= $selected_period === 'daily' ? 'bg-indigo-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' ?>">Daily</a>
            <a href="?period=weekly" class="px-3 py-1 rounded-full text-xs font-medium filter-btn <?= $selected_period === 'weekly' ? 'bg-indigo-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' ?>">Weekly</a>
            <a href="?period=monthly" class="px-3 py-1 rounded-full text-xs font-medium filter-btn <?= $selected_period === 'monthly' ? 'bg-indigo-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' ?>">Monthly</a>
            <a href="?period=yearly" class="px-3 py-1 rounded-full text-xs font-medium filter-btn <?= $selected_period === 'yearly' ? 'bg-indigo-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' ?>">Yearly</a>
        </div>
        <div class="trend-chart-container">
            <canvas id="reportsByTimePeriodChart"></canvas>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    document.addEventListener("DOMContentLoaded", function() {
        // Data for the Category Chart from PHP
        const categoryChartLabels = <?= $category_chart_labels ?>;
        const categoryChartData = <?= $category_chart_data ?>;

        const ctxCategory = document.getElementById('reportsByCategoryChart').getContext('2d');
        new Chart(ctxCategory, {
            type: 'bar',
            data: {
                labels: categoryChartLabels,
                datasets: [{
                    label: 'Reports',
                    data: categoryChartData,
                    backgroundColor: 'rgba(59, 130, 246, 0.6)',
                    borderColor: 'rgba(59, 130, 246, 1)',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                    }
                }
            }
        });

        // Data for the Status Chart from PHP
        const statusChartLabels = <?= $status_chart_labels ?>;
        const statusChartData = <?= $status_chart_data ?>;

        const ctxStatus = document.getElementById('reportsByStatusChart').getContext('2d');
        new Chart(ctxStatus, {
            type: 'doughnut',
            data: {
                labels: statusChartLabels,
                datasets: [{
                    label: 'Reports by Status',
                    data: statusChartData,
                    backgroundColor: [
                        'rgba(16, 185, 129, 0.6)', // resolved
                        'rgba(59, 130, 246, 0.6)', // in progress
                        'rgba(239, 68, 68, 0.6)', // unresolved
                    ],
                    borderColor: [
                        'rgba(16, 185, 129, 1)',
                        'rgba(59, 130, 246, 1)',
                        'rgba(239, 68, 68, 1)',
                    ],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                    },
                }
            }
        });

        // Data for the Trend Chart from PHP
        const trendChartLabels = <?= $trend_chart_labels ?>;
        const trendChartData = <?= $trend_chart_data ?>;

        const ctxTimeTrend = document.getElementById('reportsByTimePeriodChart').getContext('2d');
        const datasets = trendChartData.map((data, index) => ({
            label: categoryChartLabels[index],
            data: data,
            fill: false,
            borderColor: `hsl(${(index * 360 / trendChartData.length)}, 100%, 50%)`, // Different color for each category
            tension: 0.1,
        }));

        new Chart(ctxTimeTrend, {
            type: 'line',
            data: {
                labels: trendChartLabels,
                datasets: datasets
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                    }
                }
            }
        });
    });
</script>

<?php include_once 'admin_footer.php'; ?>