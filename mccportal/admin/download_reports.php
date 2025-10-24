<?php
session_start();
include_once '../includes/config.php';

// Check if a user is logged in and is an admin or auditor
if (!isset($_SESSION['user_role']) || ($_SESSION['user_role'] !== 'admin' && $_SESSION['user_role'] !== 'auditor')) {
    header('Location: ../login.php');
    exit();
}

// Get the status filter from the query string
$status_filter = isset($_GET['status']) ? $_GET['status'] : 'all';

// Prepare SQL query based on the filter
if ($status_filter === 'all') {
    $sql = "SELECT r.*, d.department_name FROM reports r LEFT JOIN departments d ON r.department_id = d.department_id";
} else {
    $sql = "SELECT r.*, d.department_name FROM reports r LEFT JOIN departments d ON r.department_id = d.department_id WHERE r.status = ?";
}

$stmt = $conn->prepare($sql);
if ($status_filter !== 'all') {
    $stmt->bind_param("s", $status_filter);
}
$stmt->execute();
$result = $stmt->get_result();

// Prepare CSV file
header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="reports.csv"');

$output = fopen('php://output', 'w');
fputcsv($output, ['Report ID', 'Category', 'Status', 'Submitted On', 'Assigned Department']); // Header row

while ($row = $result->fetch_assoc()) {
    fputcsv($output, [$row['report_id'], $row['category'], $row['status'], $row['created_at'], $row['department_name']]);
}

fclose($output);
exit();
?>