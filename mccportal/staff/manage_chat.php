<?php
session_start();
include_once '../includes/config.php';
include_once 'staff_header.php';

// Check if the user is logged in and has a staff-level role
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'], ['staff', 'it_help_desk', 'auditor'])) {
    header("Location: ../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$selected_ticket_id = isset($_GET['ticket_id']) ? intval($_GET['ticket_id']) : null;
$messages = [];
$citizen_info = null;

// Fetch the staff member's department_id
$sql_get_dept = "SELECT department_id FROM staff WHERE user_id = ?";
$stmt_get_dept = $conn->prepare($sql_get_dept);
$stmt_get_dept->bind_param("i", $user_id);
$stmt_get_dept->execute();
$result_dept = $stmt_get_dept->get_result();
$department_id = null;

if ($result_dept->num_rows > 0) {
    $department_row = $result_dept->fetch_assoc();
    $department_id = $department_row['department_id'];
} else {
    session_destroy();
    header("Location: ../login.php");
    exit();
}
$stmt_get_dept->close();

// Handle message submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_message'])) {
    $new_message = trim($_POST['message']);
    $ticket_id_to_chat = $_POST['ticket_id'];
    $sender_id = $_SESSION['user_id'];

    if (!empty($new_message) && $ticket_id_to_chat) {
        $sql_insert = "INSERT INTO chat_messages (ticket_id, sender_id, message, is_read) VALUES (?, ?, ?, 0)";
        $stmt_insert = $conn->prepare($sql_insert);
        $stmt_insert->bind_param("iis", $ticket_id_to_chat, $sender_id, $new_message);
        if (!$stmt_insert->execute()) {
            echo "<div class='error'>Error: " . htmlspecialchars($stmt_insert->error) . "</div>";
        }
        $stmt_insert->close();
        
        // Redirect to prevent form resubmission on refresh
        header("Location: manage_chat.php?ticket_id=" . $ticket_id_to_chat);
        exit();
    }
}

// Fetch tickets
$sql_tickets = "SELECT r.report_id, r.category, r.created_at, u.full_name AS citizen_name,
                 (SELECT COUNT(*) FROM chat_messages WHERE ticket_id = r.report_id AND sender_id != ? AND is_read = 0) as unread_count
                 FROM reports r
                 JOIN users u ON r.citizen_id = u.user_id
                 WHERE r.assigned_to_department_id = ?
                 ORDER BY r.created_at DESC";
$stmt_tickets = $conn->prepare($sql_tickets);
$stmt_tickets->bind_param("ii", $user_id, $department_id);
$stmt_tickets->execute();
$result_tickets = $stmt_tickets->get_result();
$tickets = $result_tickets->fetch_all(MYSQLI_ASSOC);
$stmt_tickets->close();

// Fetch messages and citizen info for the selected ticket
if ($selected_ticket_id) {
    // Mark messages as read
    $sql_mark_read = "UPDATE chat_messages SET is_read = 1 WHERE ticket_id = ? AND sender_id != ?";
    $stmt_mark_read = $conn->prepare($sql_mark_read);
    $stmt_mark_read->bind_param("ii", $selected_ticket_id, $user_id);
    $stmt_mark_read->execute();
    $stmt_mark_read->close();
    
    // Fetch messages
    $sql_messages = "SELECT c.*, u.full_name, u.role
                     FROM chat_messages c
                     JOIN users u ON c.sender_id = u.user_id
                     WHERE c.ticket_id = ?
                     ORDER BY c.created_at ASC";
    $stmt_messages = $conn->prepare($sql_messages);
    $stmt_messages->bind_param("i", $selected_ticket_id);
    $stmt_messages->execute();
    $result_messages = $stmt_messages->get_result();
    $messages = $result_messages->fetch_all(MYSQLI_ASSOC);
    $stmt_messages->close();

    // Fetch citizen information
    $sql_citizen = "SELECT u.full_name, u.email, u.phone FROM users u JOIN reports r ON u.user_id = r.citizen_id WHERE r.report_id = ?";
    $stmt_citizen = $conn->prepare($sql_citizen);
    $stmt_citizen->bind_param("i", $selected_ticket_id);
    $stmt_citizen->execute();
    $result_citizen = $stmt_citizen->get_result();
    $citizen_info = $result_citizen->fetch_assoc();
    $stmt_citizen->close();
}

$conn->close();
?>

<div class="flex h-full min-h-screen -mt-8">
    <!-- Chat Sidebar -->
    <div class="w-1/4 bg-gray-200 p-4 overflow-y-auto rounded-lg shadow-md">
        <h2 class="text-xl font-bold text-gray-800 mb-4">Assigned Chats</h2>
        <div class="space-y-2">
            <?php if (empty($tickets)): ?>
                <div class="text-center text-gray-500 text-sm py-4">No chats found.</div>
            <?php else: ?>
                <?php foreach ($tickets as $ticket): ?>
                    <a href="?ticket_id=<?= htmlspecialchars($ticket['report_id']) ?>"
                       class="block p-3 rounded-lg transition-colors duration-200
                              <?= $selected_ticket_id == $ticket['report_id'] ? 'bg-blue-600 text-white shadow-md' : 'bg-white hover:bg-gray-100 text-gray-800' ?>">
                        <div class="flex items-center justify-between">
                            <h3 class="font-semibold text-sm">#<?= htmlspecialchars($ticket['report_id']) ?> - <?= htmlspecialchars($ticket['citizen_name']) ?></h3>
                            <?php if ($ticket['unread_count'] > 0): ?>
                                <span class="bg-red-500 text-white text-xs font-bold px-2 py-1 rounded-full"><?= htmlspecialchars($ticket['unread_count']) ?></span>
                            <?php endif; ?>
                        </div>
                        <p class="text-xs mt-1 opacity-80"><?= htmlspecialchars($ticket['category']) ?></p>
                    </a>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- Main Chat Window -->
    <div class="flex-1 flex flex-col p-4 bg-white rounded-lg shadow-md ml-4">
        <?php if ($selected_ticket_id): ?>
            <div class="border-b pb-4 mb-4">
                <h2 class="text-xl font-bold text-gray-800">
                    Chat for Report #<?= htmlspecialchars($selected_ticket_id) ?>
                </h2>
                <?php if ($citizen_info): ?>
                    <p class="text-gray-600">
                        Citizen: <?= htmlspecialchars($citizen_info['full_name']) ?>
                        (<?= htmlspecialchars($citizen_info['email']) ?>)
                    </p>
                <?php endif; ?>
            </div>

            <!-- Message history -->
            <div id="chat-messages" class="flex-1 overflow-y-auto space-y-4 mb-4">
                <?php if (empty($messages)): ?>
                    <div class="text-center text-gray-500 py-8">No messages yet.</div>
                <?php else: ?>
                    <?php foreach ($messages as $msg): ?>
                        <?php 
                            $is_staff = in_array($msg['role'], ['staff', 'it_help_desk', 'auditor', 'admin']);
                            $message_bg = $is_staff ? 'bg-blue-500 text-white' : 'bg-gray-200 text-gray-800';
                            $message_align = $is_staff ? 'self-end' : 'self-start';
                            $name_align = $is_staff ? 'text-right' : 'text-left';
                        ?>
                        <div class="flex flex-col <?= $message_align ?>" data-message-id="<?= htmlspecialchars($msg['message_id']) ?>" data-timestamp="<?= htmlspecialchars($msg['created_at']) ?>">
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
            <form action="manage_chat.php?ticket_id=<?= htmlspecialchars($selected_ticket_id) ?>" method="POST" class="flex space-x-2">
                <input type="hidden" name="ticket_id" value="<?= htmlspecialchars($selected_ticket_id) ?>">
                <textarea name="message" rows="2" class="flex-1 p-3 border border-gray-300 rounded-lg resize-none focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Type your message..."></textarea>
                <button type="submit" name="send_message" class="bg-blue-600 hover:bg-blue-700 text-white font-semibold py-3 px-6 rounded-lg transition-colors duration-200 self-end">
                    <i class="fas fa-paper-plane mr-1"></i> Send
                </button>
            </form>
        <?php else: ?>
            <div class="flex flex-1 flex-col items-center justify-center text-center text-gray-500">
                <i class="fas fa-comment-dots text-6xl mb-4"></i>
                <p class="text-lg font-medium">Select a chat from the left to start a conversation.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
    // Get the staff user's ID from a PHP variable
    const staffId = <?= json_encode($_SESSION['user_id']) ?>;
    const chatContainer = document.getElementById('chat-messages');

    // Function to fetch new messages and append them to the chat
    async function fetchNewMessages() {
        if (!chatContainer) {
            return;
        }

        const selectedTicketId = <?= json_encode($selected_ticket_id) ?>;
        if (!selectedTicketId) {
            return;
        }

        // Find the timestamp of the last message to avoid fetching old messages
        const lastMessage = chatContainer.lastElementChild;
        const lastTimestamp = lastMessage ? lastMessage.dataset.timestamp : '';
        
        try {
            const response = await fetch(`fetch_messages.php?ticket_id=${selectedTicketId}&last_timestamp=${lastTimestamp}`);
            const data = await response.json();

            if (data.messages && data.messages.length > 0) {
                data.messages.forEach(msg => {
                    const isStaff = msg.sender_id === staffId;
                    const messageBg = isStaff ? 'bg-blue-500 text-white' : 'bg-gray-200 text-gray-800';
                    const messageAlign = isStaff ? 'self-end' : 'self-start';
                    const nameAlign = isStaff ? 'text-right' : 'text-left';

                    const msgElement = document.createElement('div');
                    msgElement.className = `flex flex-col ${messageAlign}`;
                    msgElement.dataset.messageId = msg.message_id;
                    msgElement.dataset.timestamp = msg.created_at;

                    msgElement.innerHTML = `
                        <div class="text-xs text-gray-500 font-medium ${nameAlign} mb-1">${msg.full_name}</div>
                        <div class="rounded-lg p-3 max-w-xs bg-gray-200 text-gray-800 shadow ${messageBg}">${msg.message}</div>
                        <div class="text-xs text-gray-400 mt-1 ${nameAlign}">${new Date(msg.created_at).toLocaleString()}</div>
                    `;
                    chatContainer.appendChild(msgElement);
                });
                // Scroll to the bottom after adding new messages
                chatContainer.scrollTop = chatContainer.scrollHeight;
            }
        } catch (error) {
            console.error('Error fetching messages:', error);
        }
    }
    
    // Call the function once when page loads if a ticket is selected
    if (<?= json_encode($selected_ticket_id) ?>) {
        fetchNewMessages();
    }

    const messageForm = document.querySelector('form[action*="manage_chat.php"]');
    if (messageForm) {
        messageForm.addEventListener('submit', function() {
            // Small delay to allow the message to be processed on the server
            setTimeout(() => {
                fetchNewMessages();
            }, 1000);
        });
    }

    // Initial scroll to the bottom on page load
    window.onload = function() {
        if (chatContainer) {
            chatContainer.scrollTop = chatContainer.scrollHeight;
        }
    };
</script>

<?php include_once 'staff_footer.php'; ?>
