<?php
session_start();
include_once '../includes/config.php';

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Fetch all departments for the dropdown
$departments = [];
$sql_departments = "SELECT department_id, department_name FROM departments ORDER BY department_name ASC";
$result_departments = $conn->query($sql_departments);
if ($result_departments) {
    while ($row = $result_departments->fetch_assoc()) {
        $departments[] = $row;
    }
}

// Handle new message submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['message'])) {
    $message = trim($_POST['message']);
    // Note: The variable name is 'report_id' because it references the ID from the reports table,
    // but the database column in chat_messages is 'ticket_id'.
    $report_id = isset($_POST['report_id']) ? intval($_POST['report_id']) : null;
    $is_new_chat = isset($_POST['is_new_chat']) && $_POST['is_new_chat'] === 'true';

    if ($message) {
        if ($is_new_chat) {
            $assigned_department_id = isset($_POST['department_id']) ? intval($_POST['department_id']) : null;
            $category = isset($_POST['category']) ? trim($_POST['category']) : 'General Enquiry';
            $description = isset($_POST['description']) ? trim($_POST['description']) : $message;

            if ($assigned_department_id === null) {
                header("Location: chat.php?error=no_department_selected");
                exit();
            }

            // Create a new report for chat
            $stmt = $conn->prepare("INSERT INTO reports (citizen_id, category, description, status, assigned_to_department_id) VALUES (?, ?, ?, 'pending', ?)");
            $stmt->bind_param("issi", $user_id, $category, $description, $assigned_department_id);
            $stmt->execute();
            $report_id = $stmt->insert_id;
            $stmt->close();
        }

        if ($report_id) {
            // FIX: Changed 'report_id' to 'ticket_id' to match the database column name.
            $stmt = $conn->prepare("INSERT INTO chat_messages (ticket_id, sender_id, message) VALUES (?, ?, ?)");
            $stmt->bind_param("iis", $report_id, $user_id, $message);
            $stmt->execute();
            $stmt->close();

            // Create notification for staff
            $stmt_notify = $conn->prepare("INSERT INTO notifications (user_id, title, message, type, is_read) VALUES (?, ?, ?, 'message', 0)");
            $notification_title = "New message on Report #" . $report_id;
            $notification_message = "Citizen sent: " . substr($message, 0, 50) . "...";
            
            // Get staff members in the assigned department
            $stmt_staff = $conn->prepare("SELECT user_id FROM staff WHERE department_id = ?");
            $stmt_staff->bind_param("i", $assigned_department_id);
            $stmt_staff->execute();
            $result_staff = $stmt_staff->get_result();
            
            while ($staff_row = $result_staff->fetch_assoc()) {
                $stmt_notify->bind_param("iss", $staff_row['user_id'], $notification_title, $notification_message);
                $stmt_notify->execute();
            }
            $stmt_notify->close();
            $stmt_staff->close();

            // Redirect to prevent form resubmission
            header("Location: chat.php?report_id=" . $report_id);
            exit();
        }
    }
}

// Fetch chat history
$report_id = isset($_GET['report_id']) ? intval($_GET['report_id']) : null;
$chat_messages = [];
$current_report_category = '';

if ($report_id) {
    // Check if the user is authorized to view this report
    $auth_sql = "SELECT * FROM reports WHERE report_id = ? AND citizen_id = ?";
    $auth_stmt = $conn->prepare($auth_sql);
    $auth_stmt->bind_param("ii", $report_id, $user_id);
    $auth_stmt->execute();
    $auth_result = $auth_stmt->get_result();

    if ($auth_result->num_rows > 0) {
        $report_data = $auth_result->fetch_assoc();
        $current_report_category = $report_data['category'];

        // FIX: Changed 'cm.report_id' to 'cm.ticket_id' to match the database column name.
        $sql = "SELECT cm.*, u.full_name
                FROM chat_messages cm
                JOIN users u ON cm.sender_id = u.user_id
                WHERE cm.ticket_id = ?
                ORDER BY cm.created_at ASC";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $report_id);
        $stmt->execute();
        $result = $stmt->get_result();

        while ($row = $result->fetch_assoc()) {
            $chat_messages[] = $row;
        }
        $stmt->close();
    } else {
        $report_id = null; // Unauthorized access
    }
}

// Fetch all reports with messages for the sidebar
$reports = [];
// FIX: Changed 'WHERE report_id = r.report_id' to use the correct table alias 'cm' and column 'ticket_id'
$sql = "SELECT r.*, 
        (SELECT COUNT(*) FROM chat_messages WHERE ticket_id = r.report_id) as message_count
        FROM reports r 
        WHERE r.citizen_id = ? AND 
        (SELECT COUNT(*) FROM chat_messages WHERE ticket_id = r.report_id) > 0
        ORDER BY r.created_at DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $reports[] = $row;
}
$stmt->close();

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chat | Masvingo Connect</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css">
    <style>
        body {
            background-color: #f3f4f6;
        }
    </style>
</head>
<body class="flex h-screen overflow-hidden">

<!-- Sidebar for report list -->
<aside class="w-1/4 bg-gray-800 text-white flex flex-col p-4 rounded-r-lg shadow-lg">
    <div class="flex items-center justify-between mb-4">
        <h2 class="text-xl font-bold">Chats</h2>
        <a href="dashboard.php" class="text-gray-300 hover:text-white transition-colors duration-200" title="Back to Dashboard">
            <i class="fas fa-arrow-left"></i>
        </a>
    </div>
    <div class="mb-4">
        <a href="chat.php?new_chat=true" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-4 rounded-lg text-center block transition-colors duration-200">
            <i class="fas fa-plus mr-2"></i> Start a new Chat
        </a>
    </div>
    <ul class="flex-1 overflow-y-auto space-y-2">
        <?php if (empty($reports)): ?>
            <li class="p-3 text-gray-400 text-center">No chats to display.</li>
        <?php else: ?>
            <?php foreach ($reports as $report): ?>
                <a href="chat.php?report_id=<?= htmlspecialchars($report['report_id']) ?>" class="block">
                    <li class="p-3 rounded-lg hover:bg-gray-700 transition-colors duration-200 <?= $report_id === intval($report['report_id']) ? 'bg-gray-700' : 'bg-gray-900' ?>">
                        <div class="font-semibold">
                            Report #<?= htmlspecialchars($report['report_id']) ?>
                        </div>
                        <div class="text-sm text-gray-400 truncate">
                            <?= htmlspecialchars($report['category']) ?>
                        </div>
                        <div class="text-xs text-gray-500 mt-1">
                            Status: <span class="capitalize"><?= htmlspecialchars($report['status']) ?></span>
                            • <?= htmlspecialchars($report['message_count']) ?> messages
                        </div>
                    </li>
                </a>
            <?php endforeach; ?>
        <?php endif; ?>
    </ul>
</aside>

<!-- Main chat area -->
<main class="flex-1 flex flex-col p-6 bg-white rounded-l-lg shadow-lg">
    <?php if (isset($_GET['new_chat'])): ?>
        <div class="bg-gray-100 p-8 rounded-lg shadow-md w-full max-w-md mx-auto">
            <h1 class="text-2xl font-bold text-gray-800 mb-4">Start a new chat</h1>
            <form action="chat.php" method="POST" class="flex flex-col space-y-4">
                <input type="hidden" name="is_new_chat" value="true">
                <div>
                    <label for="department_id" class="block text-left text-sm font-medium text-gray-700">Select a Department</label>
                    <select name="department_id" id="department_id" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 p-2">
                        <option value="">-- Please select --</option>
                        <?php foreach ($departments as $dept): ?>
                            <option value="<?= htmlspecialchars($dept['department_id']) ?>"><?= htmlspecialchars($dept['department_name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label for="category" class="block text-left text-sm font-medium text-gray-700">Category</label>
                    <input type="text" name="category" id="category" placeholder="e.g., Water bill enquiry" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 p-2">
                </div>
                <div>
                    <label for="description" class="block text-left text-sm font-medium text-gray-700">Brief Description</label>
                    <input type="text" name="description" id="description" placeholder="Brief description of your issue" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 p-2">
                </div>
                <div>
                    <label for="message" class="block text-left text-sm font-medium text-gray-700">Your first message</label>
                    <textarea name="message" id="message" rows="3" required class="mt-1 block w-full p-3 border border-gray-300 rounded-lg resize-none focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Type your message..."></textarea>
                </div>
                <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2 px-4 rounded-lg transition-colors duration-200">
                    <i class="fas fa-paper-plane"></i> Send
                </button>
            </form>
        </div>
    <?php elseif ($report_id): ?>
        <header class="pb-4 border-b border-gray-200 mb-4 flex justify-between items-center">
            <h1 class="text-2xl font-bold text-gray-800">
                <i class="fas fa-comments text-blue-500 mr-2"></i>
                <?= htmlspecialchars($current_report_category) ?>
            </h1>
            <div class="flex items-center space-x-2">
                <span class="text-sm font-medium text-gray-500">Report #<?= htmlspecialchars($report_id) ?></span>
            </div>
        </header>
        <div id="chat-messages" class="flex-1 overflow-y-auto mb-4 p-4 border rounded-lg bg-gray-50">
            <?php if (empty($chat_messages)): ?>
                <p class="text-gray-500 text-center text-sm">No messages yet. Start the conversation!</p>
            <?php endif; ?>
            <?php foreach ($chat_messages as $msg): ?>
                <?php
                $is_sender = $msg['sender_id'] == $user_id;
                $message_class = $is_sender ? 'bg-blue-500 text-white self-end' : 'bg-gray-200 text-gray-800 self-start';
                ?>
                <div class="flex flex-col mb-4 <?= $is_sender ? 'items-end' : 'items-start' ?>">
                    <div class="font-semibold text-sm mb-1"><?= htmlspecialchars($msg['full_name']) ?></div>
                    <div class="rounded-lg p-3 max-w-md break-words <?= $message_class ?>">
                        <?= nl2br(htmlspecialchars($msg['message'])) ?>
                    </div>
                    <div class="text-xs text-gray-400 mt-1 <?= $is_sender ? 'text-right' : 'text-left' ?>">
                        <?= date('M j, Y, g:i A', strtotime($msg['created_at'])) ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <form action="chat.php" method="POST" class="flex space-x-2">
            <input type="hidden" name="report_id" value="<?= htmlspecialchars($report_id) ?>">
            <textarea name="message" rows="1" class="flex-1 p-2 border border-gray-300 rounded-lg resize-none focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Type your message..."></textarea>
            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2 px-4 rounded-lg transition-colors duration-200">
                <i class="fas fa-paper-plane"></i> Send
            </button>
        </form>
    <?php else: ?>
        <div class="flex flex-1 flex-col items-center justify-center text-center text-gray-500">
            <i class="fas fa-arrow-left text-6xl mb-4"></i>
            <p class="text-lg font-medium">Please select a chat from the sidebar to view the conversation.</p>
        </div>
    <?php endif; ?>
</main>

<script>
// Auto-scroll to bottom of chat
document.addEventListener('DOMContentLoaded', function() {
    const chatMessages = document.getElementById('chat-messages');
    if (chatMessages) {
        chatMessages.scrollTop = chatMessages.scrollHeight;
    }
});
</script>

</body>
</html>
