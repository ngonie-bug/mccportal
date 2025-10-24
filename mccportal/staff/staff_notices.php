<?php
include_once 'staff_header.php';
include_once '../includes/config.php';

// Check if the user is logged in and has a staff-level role
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'], ['staff', 'it_help_desk', 'auditor'])) {
    header("Location: ../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$department_id = null;

// Fetch the staff's department ID
$stmt = $conn->prepare("SELECT department_id FROM staff WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($department_id);
$stmt->fetch();
$stmt->close();

if (!$department_id) {
    echo "<div class='text-center py-8'><p class='text-red-500 text-lg font-semibold'>Error: No department assigned to this user. Please contact an administrator.</p></div>";
    exit();
}

// Handle form submission to add a new notice
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_notice'])) {
    $title = htmlspecialchars(trim($_POST['title']));
    $content = htmlspecialchars(trim($_POST['content']));

    if (!empty($title) && !empty($content)) {
        $stmt = $conn->prepare("INSERT INTO notices (staff_id, department_id, title, content) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("iiss", $user_id, $department_id, $title, $content);
        if ($stmt->execute()) {
            $_SESSION['message'] = "Notice added successfully!";
        } else {
            $_SESSION['error'] = "Failed to add notice: " . $stmt->error;
        }
        $stmt->close();
    } else {
        $_SESSION['error'] = "Title and content cannot be empty.";
    }
}

// Fetch all notices for the user's department
$notices = [];
$stmt = $conn->prepare("SELECT notice_id, title, content, created_at FROM notices WHERE department_id = ? ORDER BY created_at DESC");
$stmt->bind_param("i", $department_id);
$stmt->execute();
$result = $stmt->get_result();
$notices = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();
$conn->close();
?>

<div class="container mx-auto p-4 sm:p-6 lg:p-8">
    <h1 class="text-4xl font-extrabold text-gray-900 mb-6">Manage Notices</h1>

    <?php if (isset($_SESSION['message'])): ?>
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-lg relative mb-4" role="alert">
            <span class="block sm:inline"><?= htmlspecialchars($_SESSION['message']); ?></span>
        </div>
        <?php unset($_SESSION['message']); ?>
    <?php endif; ?>

    <?php if (isset($_SESSION['error'])): ?>
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg relative mb-4" role="alert">
            <span class="block sm:inline"><?= htmlspecialchars($_SESSION['error']); ?></span>
        </div>
        <?php unset($_SESSION['error']); ?>
    <?php endif; ?>

    <div class="bg-white rounded-xl shadow-lg p-6 mb-8">
        <h2 class="text-2xl font-bold text-gray-800 mb-4">Post a New Notice</h2>
        <form action="staff_notices.php" method="POST" class="space-y-4">
            <div>
                <label for="title" class="block text-sm font-medium text-gray-700">Notice Title</label>
                <input type="text" id="title" name="title" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
            </div>
            <div>
                <label for="content" class="block text-sm font-medium text-gray-700">Notice Content</label>
                <textarea id="content" name="content" rows="4" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"></textarea>
            </div>
            <button type="submit" name="add_notice" class="w-full inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                Post Notice
            </button>
        </form>
    </div>

    <div class="bg-white rounded-xl shadow-lg overflow-hidden border border-gray-200">
        <div class="p-6">
            <h2 class="text-2xl font-bold text-gray-800">Your Department's Notices</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-100">
                    <tr>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Title</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Content</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Posted On</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php if (!empty($notices)): ?>
                        <?php foreach ($notices as $notice): ?>
                            <tr class="hover:bg-gray-50 transition-colors duration-150">
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900"><?= htmlspecialchars($notice['title']) ?></td>
                                <td class="px-6 py-4 text-sm text-gray-700 max-w-lg truncate"><?= htmlspecialchars($notice['content']) ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?= date('Y-m-d H:i', strtotime($notice['created_at'])) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="3" class="px-6 py-4 text-center text-sm text-gray-500">No notices have been posted yet.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include_once 'staff_footer.php'; ?>
