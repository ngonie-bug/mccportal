<?php
session_start();
include_once '../includes/config.php';

// Check if a user is logged in and is an admin or auditor
if (!isset($_SESSION['user_role']) || ($_SESSION['user_role'] !== 'admin' && $_SESSION['user_role'] !== 'auditor')) {
    header('Location: ../login.php');
    exit();
}

// Set the default time period to 'monthly'
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
        $date_format = '%Y-%u'; // %u is for ISO 8601 week number
        $trend_title = 'Weekly Report Trend';
        break;
    case 'yearly':
        $date_format = '%Y';
        $trend_title = 'Yearly Report Trend';
        break;
    case 'monthly':
    default:
        // Default values are already set
        break;
}

// Prepare CSV output
header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="analytics_report.csv"');
$output = fopen('php://output', 'w');

// Write the header row
fputcsv($output, ['Report Type', 'Count']);

// Total number of reports
$sql_total_reports = "SELECT COUNT(*) AS total_reports FROM reports";
$result_total_reports = $conn->query($sql_total_reports);
if ($result_total_reports) {
    $total_reports = $result_total_reports->fetch_assoc()['total_reports'];
    fputcsv($output, ['Total Reports', $total_reports]);
}

// Reports by status
$sql_reports_by_status = "SELECT status, COUNT(*) AS count FROM reports GROUP BY status";
$result_reports_by_status = $conn->query($sql_reports_by_status);
if ($result_reports_by_status) {
    while ($row = $result_reports_by_status->fetch_assoc()) {
        fputcsv($output, ['Reports by Status: ' . $row['status'], $row['count']]);
    }
}

// Total users
$sql_total_users = "SELECT COUNT(*) AS total_users FROM users";
$result_total_users = $conn->query($sql_total_users);
if ($result_total_users) {
    $total_users = $result_total_users->fetch_assoc()['total_users'];
    fputcsv($output, ['Total Users', $total_users]);
}

// Reports by category
$sql_reports_by_category = "SELECT category, COUNT(*) AS count FROM reports GROUP BY category ORDER BY count DESC";
$result_reports_by_category = $conn->query($sql_reports_by_category);
if ($result_reports_by_category) {
    while ($row = $result_reports_by_category->fetch_assoc()) {
        fputcsv($output, ['Reports by Category: ' . $row['category'], $row['count']]);
    }
}

// Reports by department
$sql_reports_by_department = "SELECT department, COUNT(*) AS count FROM reports GROUP BY department ORDER BY count DESC";
$result_reports_by_department = $conn->query($sql_reports_by_department);
if ($result_reports_by_department) {
    while ($row = $result_reports_by_department->fetch_assoc()) {
        fputcsv($output, ['Reports by Department: ' . $row['department'], $row['count']]);
    }
}

// Trend data
$sql_reports_by_time_period = "SELECT DATE_FORMAT(created_at, '$date_format') as time_period, COUNT(*) as count FROM reports GROUP BY time_period ORDER BY time_period ASC";
$result_reports_by_time_period = $conn->query($sql_reports_by_time_period);
if ($result_reports_by_time_period) {
    while ($row = $result_reports_by_time_period->fetch_assoc()) {
        fputcsv($output, ['Reports Trend for ' . $trend_title . ': ' . $row['time_period'], $row['count']]);
    }
}

// Close output
fclose($output);
exit();
?>