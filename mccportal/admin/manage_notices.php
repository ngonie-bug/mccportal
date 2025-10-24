<?php
session_start();
include_once '../includes/config.php';
include_once 'admin_header.php';

// Check if user is logged in and is staff/admin
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'], ['staff', 'admin'])) {
    header('Location: ../login.php');
    exit();
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add_notice':
                $title = trim($_POST['title']);
                $content = trim($_POST['content']);
                $status = $_POST['status'];
                
                if (!empty($title) && !empty($content)) {
                    $stmt = $conn->prepare("INSERT INTO notices (title, content, status, created_by) VALUES (?, ?, ?, ?)");
                    $stmt->bind_param("sssi", $title, $content, $status, $_SESSION['user_id']);
                    
                    if ($stmt->execute()) {
                        $success_message = "Notice added successfully!";
                    } else {
                        $error_message = "Error adding notice: " . $conn->error;
                    }
                    $stmt->close();
                }
                break;
                
            case 'update_status':
                $notice_id = (int)$_POST['notice_id'];
                $new_status = $_POST['new_status'];
                
                $stmt = $conn->prepare("UPDATE notices SET status = ? WHERE notice_id = ?");
                $stmt->bind_param("si", $new_status, $notice_id);
                
                if ($stmt->execute()) {
                    $success_message = "Notice status updated successfully!";
                } else {
                    $error_message = "Error updating notice: " . $conn->error;
                }
                $stmt->close();
                break;
                
            case 'delete_notice':
                $notice_id = (int)$_POST['notice_id'];
                
                $stmt = $conn->prepare("DELETE FROM notices WHERE notice_id = ?");
                $stmt->bind_param("i", $notice_id);
                
                if ($stmt->execute()) {
                    $success_message = "Notice deleted successfully!";
                } else {
                    $error_message = "Error deleting notice: " . $conn->error;
                }
                $stmt->close();
                break;
        }
    }
}

// Fetch all notices with the correct column name
$notices_query = "SELECT n.notice_id, n.title, n.content, n.status, n.created_at, n.is_urgent, u.full_name as created_by_name 
                  FROM notices n 
                  LEFT JOIN users u ON n.created_by = u.user_id 
                  ORDER BY n.created_at DESC";
$notices_result = $conn->query($notices_query);
?>

<div class="container mx-auto mt-8 p-6 bg-white rounded-lg shadow-xl">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-4xl font-extrabold text-gray-800 border-b-2 border-blue-500 pb-2">Manage Notices</h1>
        <button onclick="openAddModal()" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
            <i class="fas fa-plus mr-2"></i>Add New Notice
        </button>
    </div>

    <!-- Success/Error Messages -->
    <?php if (isset($success_message)): ?>
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
            <?php echo htmlspecialchars($success_message); ?>
        </div>
    <?php endif; ?>

    <?php if (isset($error_message)): ?>
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
            <?php echo htmlspecialchars($error_message); ?>
        </div>
    <?php endif; ?>

    <!-- Notices Table -->
    <div class="overflow-x-auto">
        <table class="min-w-full bg-white border border-gray-200">
            <thead>
                <tr class="bg-gray-50">
                    <th class="border-b-2 border-gray-200 px-4 py-2 text-left">ID</th>
                    <th class="border-b-2 border-gray-200 px-4 py-2 text-left">Title</th>
                    <th class="border-b-2 border-gray-200 px-4 py-2 text-left">Content</th>
                    <th class="border-b-2 border-gray-200 px-4 py-2 text-left">Status</th>
                    <th class="border-b-2 border-gray-200 px-4 py-2 text-left">Created By</th>
                    <th class="border-b-2 border-gray-200 px-4 py-2 text-left">Created At</th>
                    <th class="border-b-2 border-gray-200 px-4 py-2 text-left">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($notices_result && $notices_result->num_rows > 0): ?>
                    <?php while ($notice = $notices_result->fetch_assoc()): ?>
                        <tr>
                            <!-- Corrected column name: notice_id instead of id -->
                            <td class="border-b border-gray-200 px-4 py-2"><?php echo htmlspecialchars($notice['notice_id']); ?></td>
                            <td class="border-b border-gray-200 px-4 py-2"><?php echo htmlspecialchars($notice['title']); ?></td>
                            <td class="border-b border-gray-200 px-4 py-2"><?php echo htmlspecialchars(substr($notice['content'], 0, 100)) . '...'; ?></td>
                            <td class="border-b border-gray-200 px-4 py-2">
                                <span class="px-2 py-1 text-xs rounded <?php echo $notice['status'] === 'active' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'; ?>">
                                    <?php echo ucfirst($notice['status']); ?>
                                </span>
                            </td>
                            <td class="border-b border-gray-200 px-4 py-2"><?php echo htmlspecialchars($notice['created_by_name'] ?? 'Unknown'); ?></td>
                            <td class="border-b border-gray-200 px-4 py-2"><?php echo date('M j, Y g:i A', strtotime($notice['created_at'])); ?></td>
                            <td class="border-b border-gray-200 px-4 py-2">
                                <button onclick="toggleStatus(<?php echo htmlspecialchars($notice['notice_id']); ?>, '<?php echo htmlspecialchars($notice['status']); ?>')" 
                                        class="bg-blue-500 hover:bg-blue-700 text-white text-xs px-2 py-1 rounded mr-1">
                                    <i class="fas fa-toggle-<?php echo $notice['status'] === 'active' ? 'on' : 'off'; ?>"></i>
                                </button>
                                <button onclick="deleteNotice(<?php echo htmlspecialchars($notice['notice_id']); ?>)" 
                                        class="bg-red-500 hover:bg-red-700 text-white text-xs px-2 py-1 rounded">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="7" class="border-b border-gray-200 px-4 py-8 text-center text-gray-500">No notices found.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Add Notice Modal -->
<div id="addNoticeModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden flex items-center justify-center">
    <div class="bg-white p-6 rounded-lg shadow-xl max-w-md w-full mx-4">
        <form method="POST">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-semibold">Add New Notice</h3>
                <button type="button" onclick="closeAddModal()" class="text-gray-400 hover:text-gray-600">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <input type="hidden" name="action" value="add_notice">
            
            <div class="mb-4">
                <label for="title" class="block text-sm font-medium text-gray-700 mb-2">Title</label>
                <input type="text" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" 
                       id="title" name="title" required>
            </div>
            
            <div class="mb-4">
                <label for="content" class="block text-sm font-medium text-gray-700 mb-2">Content</label>
                <textarea class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" 
                          id="content" name="content" rows="4" required></textarea>
            </div>
            
            <div class="mb-6">
                <label for="status" class="block text-sm font-medium text-gray-700 mb-2">Status</label>
                <select class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" 
                        id="status" name="status" required>
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                </select>
            </div>
            
            <div class="flex justify-end space-x-3">
                <button type="button" onclick="closeAddModal()" 
                        class="px-4 py-2 text-gray-600 border border-gray-300 rounded-md hover:bg-gray-50">
                    Cancel
                </button>
                <button type="submit" 
                        class="px-4 py-2 bg-blue-500 text-white rounded-md hover:bg-blue-600">
                    Add Notice
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Hidden forms for actions -->
<form id="statusForm" method="POST" style="display: none;">
    <input type="hidden" name="action" value="update_status">
    <input type="hidden" name="notice_id" id="statusNoticeId">
    <input type="hidden" name="new_status" id="newStatus">
</form>

<form id="deleteForm" method="POST" style="display: none;">
    <input type="hidden" name="action" value="delete_notice">
    <input type="hidden" name="notice_id" id="deleteNoticeId">
</form>

<script>
    function openAddModal() {
        document.getElementById('addNoticeModal').classList.remove('hidden');
    }

    function closeAddModal() {
        document.getElementById('addNoticeModal').classList.add('hidden');
    }

    function toggleStatus(noticeId, currentStatus) {
        const newStatus = currentStatus === 'active' ? 'inactive' : 'active';
        
        // Replace alert with a simple modal or message
        // For now, let's submit directly as alert/confirm are not ideal in the web environment
        document.getElementById('statusNoticeId').value = noticeId;
        document.getElementById('newStatus').value = newStatus;
        document.getElementById('statusForm').submit();
    }

    function deleteNotice(noticeId) {
        // Replace alert with a simple modal or message
        // For now, let's submit directly
        document.getElementById('deleteNoticeId').value = noticeId;
        document.getElementById('deleteForm').submit();
    }
</script>

<?php
include_once 'admin_footer.php';
?>
