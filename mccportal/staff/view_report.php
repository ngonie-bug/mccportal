<?php
// Always start the session at the very beginning of the script.
session_start();
include_once '../includes/config.php';
include_once 'staff_header.php';

// Check if the user is authenticated and has a valid staff-level role.
// The code will now exit immediately to prevent any further script execution if the check fails.
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role']) || !in_array($_SESSION['user_role'], ['staff', 'it_help_desk', 'auditor'])) {
    header("Location: ../login.php");
    exit();
}

$report = null;
$messages = [];
$message = '';
$message_class = '';
$report_id = isset($_GET['id']) ? (int)$_GET['id'] : null;

// Handle GET request messages for status updates or chat.
if (isset($_GET['message'])) {
    $message = htmlspecialchars($_GET['message']);
    $message_class = htmlspecialchars($_GET['class']);
}

// Get the user's department ID.
$user_id = $_SESSION['user_id'];
$department_id = null;
$stmt = $conn->prepare("SELECT department_id FROM staff WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($department_id);
$stmt->fetch();
$stmt->close();

if (!$department_id) {
    echo "<div class='container mx-auto mt-8 p-6 bg-white rounded-lg shadow-xl'>";
    echo "<div class='bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded'>";
    echo "Error: No department assigned to your account. Please contact administrator.";
    echo "</div></div>";
    include_once 'staff_footer.php';
    exit();
}

// Handle POST request for status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $new_status = $_POST['status'];
    $report_id_to_update = (int)$_POST['report_id'];

    // Update the report status, ensuring it belongs to the staff member's department
    $sql_update = "UPDATE reports SET status = ? WHERE report_id = ? AND assigned_to_department_id = ?";
    $stmt_update = $conn->prepare($sql_update);
    $stmt_update->bind_param("sii", $new_status, $report_id_to_update, $department_id);
    
    if ($stmt_update->execute()) {
        $status_message = 'Report status updated successfully!';
        $status_class = 'text-green-700 bg-green-100';
    } else {
        $status_message = 'Error updating status. Please try again.';
        $status_class = 'text-red-700 bg-red-100';
    }
    $stmt_update->close();

    // Redirect with a message
    header("Location: view_report.php?id=" . $report_id_to_update . "&message=" . urlencode($status_message) . "&class=" . urlencode($status_class));
    exit();
}

// Handle POST request for message submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_message'])) {
    $new_message = trim($_POST['message']);
    $report_id_to_chat = (int)$_POST['report_id'];
    $sender_id = $_SESSION['user_id'];

    if (!empty($new_message) && $report_id_to_chat) {
        $sql_insert = "INSERT INTO chat_messages (ticket_id, sender_id, message) VALUES (?, ?, ?)";
        $stmt_insert = $conn->prepare($sql_insert);
        $stmt_insert->bind_param("iis", $report_id_to_chat, $sender_id, $new_message);
        
        if ($stmt_insert->execute()) {
            // Fetch citizen's ID for notification
            $citizen_id = null;
            $stmt_citizen = $conn->prepare("SELECT citizen_id FROM reports WHERE report_id = ?");
            $stmt_citizen->bind_param("i", $report_id_to_chat);
            $stmt_citizen->execute();
            $stmt_citizen->bind_result($citizen_id);
            $stmt_citizen->fetch();
            $stmt_citizen->close();
            
            $staff_name = $_SESSION['user_full_name'] ?? 'Staff Member';

            if ($citizen_id) {
                // Insert notification for the citizen
                $notification_title = "New message on Report #" . $report_id_to_chat;
                $notification_message = $staff_name . " sent you a message: " . substr($new_message, 0, 100) . (strlen($new_message) > 100 ? "..." : "");
                
                $sql_notification = "INSERT INTO notifications (user_id, title, message, type, is_read, created_at) VALUES (?, ?, ?, 'message', 0, NOW())";
                $stmt_notification = $conn->prepare($sql_notification);
                $stmt_notification->bind_param("iss", $citizen_id, $notification_title, $notification_message);
                
                if ($stmt_notification->execute()) {
                    $notification_status = "Message sent and citizen will be notified on their dashboard!";
                    $notification_class = "text-green-700 bg-green-100";
                } else {
                    $notification_status = "Message sent but notification creation failed.";
                    $notification_class = "text-yellow-700 bg-yellow-100";
                }
                $stmt_notification->close();
            } else {
                 $notification_status = "Message sent but citizen could not be found for notification.";
                 $notification_class = "text-yellow-700 bg-yellow-100";
            }
        } else {
            $notification_status = "Failed to send message.";
            $notification_class = "text-red-700 bg-red-100";
        }
        $stmt_insert->close();
        
        // Redirect with a message
        header("Location: view_report.php?id=" . $report_id_to_chat . "&message=" . urlencode($notification_status) . "&class=" . urlencode($notification_class));
        exit();
    }
}

// Fetch report details, ensuring it belongs to the staff member's department
if ($report_id) {
    $sql = "SELECT r.*, d.department_name, u.full_name AS citizen_name, u.email AS citizen_email, u.phone AS citizen_phone
            FROM reports r
            JOIN departments d ON r.assigned_to_department_id = d.department_id
            JOIN users u ON r.citizen_id = u.user_id
            WHERE r.report_id = ? AND r.assigned_to_department_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $report_id, $department_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $report = $result->fetch_assoc();
    $stmt->close();

    // Fetch chat messages if the report is valid
    if ($report) {
        $sql_messages = "SELECT c.*, u.full_name, u.role
                        FROM chat_messages c
                        JOIN users u ON c.sender_id = u.user_id
                        WHERE c.ticket_id = ?
                        ORDER BY c.created_at ASC";
        $stmt_messages = $conn->prepare($sql_messages);
        $stmt_messages->bind_param("i", $report_id);
        $stmt_messages->execute();
        $result_messages = $stmt_messages->get_result();
        $messages = $result_messages->fetch_all(MYSQLI_ASSOC);
        $stmt_messages->close();
    }
}

$conn->close();
?>

<div class="container mx-auto mt-8 p-6 bg-white rounded-lg shadow-xl">
    <?php if ($report): ?>
        <div class="flex items-center justify-between mb-6">
            <h1 class="text-3xl font-bold text-gray-800">Report #<?= htmlspecialchars($report['report_id']) ?></h1>
            <span class="px-3 py-1 inline-flex text-sm leading-5 font-semibold rounded-full
                <?= $report['status'] === 'submitted' ? 'bg-blue-100 text-blue-800' : '' ?>
                <?= $report['status'] === 'in_progress' ? 'bg-yellow-100 text-yellow-800' : '' ?>
                <?= $report['status'] === 'resolved' ? 'bg-green-100 text-green-800' : '' ?>
                <?= $report['status'] === 'unresolved' ? 'bg-red-100 text-red-800' : '' ?>">
                <?= ucwords(str_replace('_', ' ', $report['status'])) ?>
            </span>
        </div>

        <?php if ($message): ?>
            <div class="p-4 mb-4 text-sm rounded-lg <?= htmlspecialchars($message_class) ?>" role="alert">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            <!-- Report Details and Actions -->
            <div class="space-y-6">
                <div>
                    <h2 class="text-xl font-semibold text-gray-800 mb-4">Report Details</h2>
                    <div class="space-y-4">
                        <p><strong>Category:</strong> <?= htmlspecialchars($report['category']) ?></p>
                        <p><strong>Submitted On:</strong> <?= date('Y-m-d H:i', strtotime($report['created_at'])) ?></p>
                        <p><strong>Description:</strong></p>
                        <div class="p-4 bg-gray-50 border border-gray-200 rounded-lg">
                            <?= nl2br(htmlspecialchars($report['description'])) ?>
                        </div>
                    </div>
                </div>

                <div>
                    <h2 class="text-xl font-semibold text-gray-800 mb-4">Citizen Details</h2>
                    <div class="space-y-4">
                        <p><strong>Full Name:</strong> <?= htmlspecialchars($report['citizen_name']) ?></p>
                        <p><strong>Email:</strong> <?= htmlspecialchars($report['citizen_email']) ?></p>
                        <p><strong>Phone:</strong> <?= htmlspecialchars($report['citizen_phone']) ?></p>
                    </div>
                </div>

                <div>
                    <h2 class="text-xl font-semibold text-gray-800 mb-4">Update Report Status</h2>
                    <form action="view_report.php" method="POST" class="flex items-center space-x-4">
                        <input type="hidden" name="report_id" value="<?= htmlspecialchars($report['report_id']) ?>">
                        <select name="status" class="flex-grow p-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="submitted" <?= $report['status'] === 'submitted' ? 'selected' : '' ?>>Submitted</option>
                            <option value="in_progress" <?= $report['status'] === 'in_progress' ? 'selected' : '' ?>>In Progress</option>
                            <option value="resolved" <?= $report['status'] === 'resolved' ? 'selected' : '' ?>>Resolved</option>
                            <option value="unresolved" <?= $report['status'] === 'unresolved' ? 'selected' : '' ?>>Unresolved</option>
                        </select>
                        <button type="submit" name="update_status" class="bg-blue-600 text-white px-4 py-2 rounded-lg shadow hover:bg-blue-700 transition-colors duration-200">
                            Update
                        </button>
                    </form>
                </div>
            </div>

            <!-- Chat Section -->
            <div class="bg-gray-50 rounded-lg shadow-inner flex flex-col p-4">
                <h2 class="text-xl font-semibold text-gray-800 mb-4">Chat with Citizen</h2>
                
                <!-- Message history -->
                <div class="flex-1 overflow-y-auto space-y-4 mb-4" id="chat-messages">
                    <?php if (empty($messages)): ?>
                        <div class="text-center text-gray-500">No messages yet.</div>
                    <?php else: ?>
                        <?php foreach ($messages as $msg): ?>
                            <?php 
                                $is_staff = in_array($msg['role'], ['staff', 'it_help_desk', 'auditor', 'admin']);
                                $message_bg = $is_staff ? 'bg-blue-500 text-white' : 'bg-gray-200 text-gray-800';
                                $message_align = $is_staff ? 'self-end text-right' : 'self-start text-left';
                                $name_align = $is_staff ? 'text-right' : 'text-left';
                            ?>
                            <div class="flex flex-col <?= $message_align ?>">
                                <div class="text-xs text-gray-500 font-medium <?= $name_align ?> mb-1">
                                    <?= htmlspecialchars($msg['full_name']) ?>
                                </div>
                                <div class="rounded-lg p-3 max-w-xs md:max-w-md lg:max-w-lg shadow <?= $message_bg ?>">
                                    <?= nl2br(htmlspecialchars($msg['message'])) ?>
                                </div>
                                <div class="text-xs text-gray-400 mt-1 <?= $name_align ?>">
                                    <?= date('M j, Y, g:i A', strtotime($msg['created_at'])) ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <!-- Message input form -->
                <form action="view_report.php" method="POST" class="flex space-x-2">
                    <input type="hidden" name="report_id" value="<?= htmlspecialchars($report['report_id']) ?>">
                    <textarea name="message" rows="2" class="flex-1 p-3 border border-gray-300 rounded-lg resize-none focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Type your message..."></textarea>
                    <button type="submit" name="send_message" class="bg-blue-600 hover:bg-blue-700 text-white font-semibold py-3 px-6 rounded-lg transition-colors duration-200 self-end">
                        <i class="fas fa-paper-plane mr-1"></i> Send
                    </button>
                </form>
            </div>
        </div>
        
    <?php else: ?>
        <div class="bg-white p-6 text-center rounded-lg shadow-md">
            <h2 class="text-2xl font-bold text-gray-800">Report Not Found</h2>
            <p class="mt-2 text-gray-600">The report you are looking for does not exist or you do not have permission to view it.</p>
            <a href="staff_dashboard.php" class="mt-4 inline-block bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors duration-200">Go to Dashboard</a>
        </div>
    <?php endif; ?>
</div>

<?php include_once 'staff_footer.php'; ?>
