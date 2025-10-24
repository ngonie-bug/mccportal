<?php
session_start();
include_once '../includes/config.php';
include_once 'admin_header.php';

// Check if the user is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

// Handle status updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    // Explicitly cast the reading_id to an integer for security and validation
    $reading_id = isset($_POST['reading_id']) ? (int)$_POST['reading_id'] : 0;
    $new_status = $_POST['status'] ?? 'pending';
    $admin_notes = $_POST['admin_notes'] ?? '';
    
    // Check if reading_id is a valid number to prevent errors
    if ($reading_id > 0) {
        $stmt = $conn->prepare("UPDATE meter_readings SET status = ?, admin_notes = ? WHERE id = ?");
        $stmt->bind_param("ssi", $new_status, $admin_notes, $reading_id);
        
        if ($stmt->execute()) {
            // Create notification for citizen
            $reading_stmt = $conn->prepare("SELECT citizen_id, reading_value FROM meter_readings WHERE id = ?");
            $reading_stmt->bind_param("i", $reading_id);
            $reading_stmt->execute();
            $reading_result = $reading_stmt->get_result();
            $reading_data = $reading_result->fetch_assoc();
            
            $notification_title = "Meter Reading " . ucfirst($new_status);
            $notification_message = "Your meter reading of " . ($reading_data['reading_value'] ?? '') . " m³ has been " . $new_status . ".";
            if ($admin_notes) {
                $notification_message .= " Admin notes: " . $admin_notes;
            }
            
            $notification_stmt = $conn->prepare("INSERT INTO notifications (user_id, title, message, type, created_at) VALUES (?, ?, ?, 'info', NOW())");
            $notification_stmt->bind_param("iss", $reading_data['citizen_id'] ?? 0, $notification_title, $notification_message);
            $notification_stmt->execute();
            
            $success_message = "Reading status updated successfully!";
        } else {
            $error_message = "Error updating reading status.";
        }
    } else {
        $error_message = "Invalid reading ID.";
    }
}

// **CORRECTED LINE 56: Used 'submission_date' instead of 'created_at'**
$query = "SELECT mr.*, u.username, u.email, u.full_name
             FROM meter_readings mr 
             LEFT JOIN users u ON mr.citizen_id = u.user_id
             ORDER BY mr.submission_date DESC";
$result = $conn->query($query);
?>

<div class="container mx-auto mt-8 p-6 bg-white rounded-lg shadow-xl">
    <h1 class="text-4xl font-extrabold text-gray-800 mb-6 border-b-2 border-blue-500 pb-2">Manage Meter Readings</h1>

    <?php if (isset($success_message)): ?>
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
            <?= htmlspecialchars($success_message) ?>
        </div>
    <?php endif; ?>

    <?php if (isset($error_message)): ?>
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
            <?= htmlspecialchars($error_message) ?>
        </div>
    <?php endif; ?>

    <div class="bg-blue-100 p-4 rounded-md text-blue-800 mb-6">
        <i class="fas fa-info-circle mr-2"></i> Review and manage all meter readings submitted by citizens.
    </div>

    <div class="overflow-x-auto">
        <table class="min-w-full bg-white border border-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Account Number</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Citizen</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Reading Value</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Photo</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Location</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Submitted</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php if ($result && $result->num_rows > 0): ?>
                    <?php while ($row = $result->fetch_assoc()): ?>
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                #<?= htmlspecialchars($row['id'] ?? '') ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                <?= htmlspecialchars($row['account_number'] ?? 'N/A') ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium text-gray-900"><?= htmlspecialchars($row['full_name'] ?? $row['username'] ?? '') ?></div>
                                <div class="text-sm text-gray-500"><?= htmlspecialchars($row['email'] ?? '') ?></div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                <?= htmlspecialchars($row['reading_value'] ?? '') ?> m³
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <?php if (!empty($row['photo_url'])): ?>
                                    <a href="<?= htmlspecialchars($row['photo_url']) ?>" target="_blank" class="text-blue-600 hover:text-blue-800">
                                        <i class="fas fa-image"></i> View Photo
                                    </a>
                                <?php else: ?>
                                    <span class="text-gray-400">No photo</span>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <?php if (!empty($row['location_lat']) && !empty($row['location_lon'])): ?>
                                    <a href="https://maps.google.com/?q=<?= htmlspecialchars($row['location_lat']) ?>,<?= htmlspecialchars($row['location_lon']) ?>" target="_blank" class="text-blue-600 hover:text-blue-800">
                                        <i class="fas fa-map-marker-alt"></i> View Map
                                    </a>
                                <?php else: ?>
                                    <span class="text-gray-400">No location</span>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                    <?php 
                                    $status = $row['status'] ?? 'pending';
                                    switch($status) {
                                        case 'approved': echo 'bg-green-100 text-green-800'; break;
                                        case 'rejected': echo 'bg-red-100 text-red-800'; break;
                                        default: echo 'bg-yellow-100 text-yellow-800'; break;
                                    }
                                    ?>">
                                    <?= ucfirst(htmlspecialchars($status)) ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <?= date('M j, Y g:i A', strtotime($row['submission_date'] ?? 'now')) ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                <button onclick="openModal(<?= (int)($row['id'] ?? 0) ?>, '<?= htmlspecialchars($row['status'] ?? 'pending') ?>', '<?= htmlspecialchars($row['admin_notes'] ?? '') ?>')" 
                                        class="text-indigo-600 hover:text-indigo-900">
                                    <i class="fas fa-edit"></i> Update
                                </button>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="9" class="px-6 py-4 text-center text-gray-500">
                            No meter readings found.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div id="updateModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden overflow-y-auto h-full w-full z-50">
    <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
        <div class="mt-3">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Update Reading Status</h3>
            <form method="POST" action="">
                <input type="hidden" id="modal_reading_id" name="reading_id">
                <input type="hidden" name="update_status" value="1">
                
                <div class="mb-4">
                    <label for="modal_status" class="block text-sm font-medium text-gray-700 mb-2">Status</label>
                    <select id="modal_status" name="status" class="w-full p-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="pending">Pending</option>
                        <option value="approved">Approved</option>
                        <option value="rejected">Rejected</option>
                    </select>
                </div>
                
                <div class="mb-4">
                    <label for="modal_admin_notes" class="block text-sm font-medium text-gray-700 mb-2">Admin Notes</label>
                    <textarea id="modal_admin_notes" name="admin_notes" rows="3" 
                              class="w-full p-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                              placeholder="Optional notes for the citizen..."></textarea>
                </div>
                
                <div class="flex justify-end space-x-3">
                    <button type="button" onclick="closeModal()" 
                            class="px-4 py-2 bg-gray-300 text-gray-700 rounded-md hover:bg-gray-400">
                        Cancel
                    </button>
                    <button type="submit" 
                            class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
                        Update Status
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function openModal(readingId, currentStatus, currentNotes) {
    // Add a quick check to ensure the ID is valid before populating the modal
    if (readingId <= 0) {
        console.error("Attempted to open modal with an invalid reading ID.");
        return;
    }
    document.getElementById('modal_reading_id').value = readingId;
    document.getElementById('modal_status').value = currentStatus;
    document.getElementById('modal_admin_notes').value = currentNotes;
    document.getElementById('updateModal').classList.remove('hidden');
}

function closeModal() {
    document.getElementById('updateModal').classList.add('hidden');
}

// Close modal when clicking outside
document.getElementById('updateModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeModal();
    }
});
</script>

<?php
include_once 'admin_footer.php';
?>