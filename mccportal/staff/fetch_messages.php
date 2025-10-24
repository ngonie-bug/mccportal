<?php
session_start();
include_once '../includes/config.php';

if (!isset($_SESSION['user_id']) || !isset($_GET['ticket_id'])) {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

$ticket_id = intval($_GET['ticket_id']);

$sql_messages = "SELECT c.*, u.full_name
                 FROM chat_messages c
                 JOIN users u ON c.sender_id = u.user_id
                 WHERE c.ticket_id = ?
                 ORDER BY c.created_at ASC";

$stmt_messages = $conn->prepare($sql_messages);
$stmt_messages->bind_param("i", $ticket_id);
$stmt_messages->execute();
$result_messages = $stmt_messages->get_result();
$messages = $result_messages->fetch_all(MYSQLI_ASSOC);
$stmt_messages->close();

echo json_encode(['messages' => $messages]);
?>