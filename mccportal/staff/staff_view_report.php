<?php
include_once 'staff_header.php';
include_once '../includes/config.php';

$report = null;
$citizen = null;
$messages = [];
$message = '';
$message_class = '';

// Check if a user is logged in and is staff or IT help desk
if (!isset($_SESSION['user_role']) || ($_SESSION['user_role'] !== 'staff' && $_SESSION['user_role'] !== 'it_help_desk')) {
    header('Location: ../login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$department_id = $_SESSION['department_id'];

if (isset($_GET['id'])) {
    $report_id = $_GET['id'];

    // Fetch report details, including assigned department, and ensure it's for the correct department
    $sql = "SELECT * FROM reports WHERE report_id = ? AND assigned_department_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $report_id, $department_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $report = $result->fetch_assoc();
    $stmt->close();
    
    if (!$report) {
        // Redirect to dashboard if the report is not found or not for this department
        header('Location: dashboard.php');
        exit();
    }

    // Fetch citizen details
    $sql = "SELECT full_name, username, email, phone FROM users WHERE user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $report['citizen_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    $citizen = $result->fetch_assoc();
    $stmt->close();
    
    // Fetch chat messages
    $sql_chat = "SELECT cm.*, u.full_name AS sender_name, u.role AS sender_role FROM chat_messages cm JOIN users u ON cm.sender_id = u.user_id WHERE cm.ticket_id = ? ORDER BY cm.created_at ASC";
    $stmt_chat = $conn->prepare($sql_chat);
    $stmt_chat->bind_param("i", $report_id);
    $stmt_chat->execute();
    $result_chat = $stmt_chat->get_result();
    $messages = $result_chat->fetch_all(MYSQLI_ASSOC);
    $stmt_chat->close();
} else {
    // Redirect if no report ID is provided
    header('Location: dashboard.php');
    exit();
}

// Handle form submission to update status or send a new chat message
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
            header("Refresh:0"); // Refresh the page to show the update
        } else {
            $message = 'Failed to update report status.';
            $message_class = 'bg-red-100 text-red-800';
        }
        $stmt->close();
    }
    
    // Handle new message submission
    if (isset($_POST['message'])) {
        $new_message = trim($_POST['message']);
        if (!empty($new_message)) {
            $sql_insert = "INSERT INTO chat_messages (ticket_id, sender_id, message) VALUES (?, ?, ?)";
            $stmt_insert = $conn->prepare($sql_insert);
            $stmt_insert->bind_param("iis", $report_id_to_update, $user_id, $new_message);
            if ($stmt_insert->execute()) {
                $message = 'Message sent successfully!';
                $message_class = 'bg-green-100 text-green-800';
            } else {
                $message = 'Failed to send message.';
                $message_class = 'bg-red-100 text-red-800';
            }
            $stmt_insert->close();
            header("Refresh:0"); // Refresh the page to show the new message
        }
    }
}

$conn->close();
?>

<h1 class="text-3xl font-bold text-gray-800 mb-6">Report #<?= htmlspecialchars($report['report_id']) ?></h1>

<?php if ($message): ?>
    <div class="p-4 mb-4 text-sm <?= $message_class ?> rounded-lg" role="alert">
        <?= htmlspecialchars($message) ?>
    </div>
<?php endif; ?>

<div class="bg-white rounded-lg shadow-md p-6 mb-6">
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
            <h2 class="text-xl font-semibold text-gray-800 mb-2">Report Details</h2>
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
            <p class="text-gray-600"><strong>Submitted On:</strong> <?= date('Y-m-d H:i', strtotime($report['created_at'])) ?></p>
        </div>
        
        <?php if ($citizen): ?>
        <div>
            <h2 class="text-xl font-semibold text-gray-800 mb-2">Citizen Details</h2>
            <p class="text-gray-600 mb-1"><strong>Name:</strong> <?= htmlspecialchars($citizen['full_name']) ?></p>
            <p class="text-gray-600 mb-1"><strong>Username:</strong> <?= htmlspecialchars($citizen['username']) ?></p>
        </div>
        <?php endif; ?>
    </div>
    
    <div class="mt-6">
        <h2 class="text-xl font-semibold text-gray-800 mb-2">Description</h2>
        <p class="text-gray-700 leading-relaxed"><?= nl2br(htmlspecialchars($report['description'])) ?></p>
    </div>

    <div class="mt-6">
        <h2 class="text-xl font-semibold text-gray-800 mb-2">Update Report Status</h2>
        <form action="view_report.php?id=<?= $report['report_id'] ?>" method="POST" class="flex items-center space-x-4">
            <input type="hidden" name="report_id" value="<?= htmlspecialchars($report['report_id']) ?>">
            <select name="status" class="flex-grow p-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                <option value="in_progress" <?= $report['status'] === 'in_progress' ? 'selected' : '' ?>>In Progress</option>
                <option value="resolved" <?= $report['status'] === 'resolved' ? 'selected' : '' ?>>Resolved</option>
            </select>
            <button type="submit" name="update_status" class="bg-blue-600 text-white px-4 py-2 rounded-lg shadow hover:bg-blue-700 transition-colors duration-200">
                Update Status
            </button>
        </form>
    </div>

    <!-- Chat Section -->
    <div class="mt-6">
        <h2 class="text-xl font-semibold text-gray-800 mb-4">Conversation</h2>
        <div class="bg-gray-50 p-4 rounded-lg shadow-inner h-96 overflow-y-auto flex flex-col space-y-4 mb-4">
            <?php if (empty($messages)): ?>
                <div class="flex-1 flex items-center justify-center text-gray-500">
                    <p>No messages yet. Start the conversation!</p>
                </div>
            <?php else: ?>
                <?php foreach ($messages as $msg): ?>
                    <?php
                    $is_staff_message = $msg['sender_role'] === 'staff' || $msg['sender_role'] === 'it_help_desk';
                    $message_alignment = $is_staff_message ? 'self-end' : 'self-start';
                    $message_bg = $is_staff_message ? 'bg-blue-500 text-white' : 'bg-gray-200 text-gray-800';
                    ?>
                    <div class="max-w-xs md:max-w-md p-3 rounded-lg <?= $message_alignment ?> <?= $message_bg ?>">
                        <div class="text-sm font-semibold mb-1">
                            <?= htmlspecialchars($msg['sender_name']) ?>
                        </div>
                        <p class="text-sm"><?= nl2br(htmlspecialchars($msg['message'])) ?></p>
                        <div class="text-xs mt-1 text-right <?= $is_staff_message ? 'text-gray-200' : 'text-gray-500' ?>">
                            <?= date('M j, Y, g:i A', strtotime($msg['created_at'])) ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        
        <!-- Message input form -->
        <form action="view_report.php?id=<?= $report['report_id'] ?>" method="POST" class="flex space-x-2">
            <input type="hidden" name="report_id" value="<?= htmlspecialchars($report['report_id']) ?>">
            <textarea name="message" rows="1" class="flex-1 p-2 border border-gray-300 rounded-lg resize-none focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Type your message..." required></textarea>
            <button type="submit" name="send_message" class="bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2 px-4 rounded-lg transition-colors duration-200">
                <i class="fas fa-paper-plane"></i> Send
            </button>
        </form>
    </div>
</div>

<?php include_once 'staff_footer.php'; ?>
