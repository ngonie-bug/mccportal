<?php
session_start();
// Corrected file paths to go up one directory and then into 'includes'
include_once '../includes/config.php';
include_once '../includes/header.php';

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Get the logged-in user's ID
$user_id = $_SESSION['user_id'];

// Check if the database connection exists
if (!$conn) {
    die("Database connection failed. Please check the config.php file.");
}

// Fetch all reports submitted by the current user
$reports = [];
$comments_by_report = [];

// Prepare the query to get user reports and associated staff/department info
$query = "SELECT r.*,
                u_staff.full_name AS staff_name,
                d.department_name
          FROM reports r
          LEFT JOIN users u_staff ON r.assigned_staff_id = u_staff.user_id
          LEFT JOIN departments d ON r.assigned_to_department_id = d.department_id
          WHERE r.citizen_id = ?
          ORDER BY r.created_at DESC";

// Use a try-catch block for better error handling in case of query issues
try {
    $stmt = $conn->prepare($query);
    if ($stmt) {
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();

        while ($row = $result->fetch_assoc()) {
            $reports[] = $row;
        }
        $stmt->close();
    } else {
        throw new Exception("Failed to prepare the reports query.");
    }
} catch (Exception $e) {
    // Log or display an error message
    echo "An error occurred: " . $e->getMessage();
}


// Fetch comments for all of the user's reports
if (!empty($reports)) {
    $report_ids = array_column($reports, 'report_id');
    // Using a prepared statement for the IN clause requires dynamically building the parameters
    $in_clause = implode(',', array_fill(0, count($report_ids), '?'));

    $comment_query = "SELECT c.report_id, c.comment, u.full_name, c.created_at
                     FROM comments c
                     JOIN users u ON c.staff_id = u.user_id
                     WHERE c.report_id IN ($in_clause)
                     ORDER BY c.created_at ASC";

    try {
        $comment_stmt = $conn->prepare($comment_query);
        if ($comment_stmt) {
            $types = str_repeat('i', count($report_ids));
            // Use splat operator (...) to pass array elements as arguments
            $comment_stmt->bind_param($types, ...$report_ids);
            $comment_stmt->execute();
            $comment_result = $comment_stmt->get_result();

            while ($comment_row = $comment_result->fetch_assoc()) {
                $comments_by_report[$comment_row['report_id']][] = $comment_row;
            }
            $comment_stmt->close();
        } else {
            throw new Exception("Failed to prepare the comments query.");
        }
    } catch (Exception $e) {
        // Log or display an error message
        echo "An error occurred fetching comments: " . $e->getMessage();
    }
}

$conn->close();
?>

<div class="container mx-auto p-8">
    <div class="bg-white rounded-lg shadow-lg p-6 mb-8">
        <h1 class="text-3xl font-bold text-gray-800 mb-2">My Reports</h1>
        <p class="text-gray-600">Track the status and progress of your submitted issues.</p>
    </div>

    <div class="space-y-6">
        <?php if (empty($reports)): ?>
            <div class="text-center text-gray-500 py-10">
                <p class="text-lg">You have not submitted any reports yet.</p>
                <a href="report_issue.php" class="mt-4 inline-block bg-purple-500 hover:bg-purple-600 text-white font-semibold py-2 px-6 rounded-lg shadow-md transition-colors duration-200">
                    Report an Issue
                </a>
            </div>
        <?php endif; ?>

        <?php foreach ($reports as $report):
            $photos = json_decode($report['photo_urls'], true) ?? [];

            // Determine status badge color
            $status_color_class = 'bg-gray-200 text-gray-800';
            if ($report['status'] === 'in_progress') {
                $status_color_class = 'bg-blue-100 text-blue-800';
            } elseif ($report['status'] === 'resolved') {
                $status_color_class = 'bg-green-100 text-green-800';
            } elseif ($report['status'] === 'rejected') {
                $status_color_class = 'bg-red-100 text-red-800';
            } elseif ($report['status'] === 'closed') {
                $status_color_class = 'bg-gray-400 text-gray-800';
            }
        ?>
            <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-200">
                <div class="flex justify-between items-center mb-2">
                    <h3 class="text-xl font-semibold text-gray-800">Report #<?= htmlspecialchars($report['report_id']) ?></h3>
                    <span class="px-3 py-1 rounded-full text-sm font-semibold <?= $status_color_class ?>">
                        <?= htmlspecialchars(ucfirst(str_replace('_', ' ', $report['status']))) ?>
                    </span>
                </div>
                <p class="text-sm text-gray-500">Submitted on: <?= date('F j, Y, g:i a', strtotime($report['created_at'])) ?></p>
                
                <p class="text-gray-700 mt-4 mb-2"><strong>Category:</strong> <?= htmlspecialchars($report['category']) ?></p>
                <p class="text-gray-700 mb-4"><strong>Description:</strong> <?= htmlspecialchars($report['description']) ?></p>

                <?php if (!empty($report['department_name'])): ?>
                    <p class="text-sm text-gray-700 mt-2"><strong>Assigned Department:</strong> <?= htmlspecialchars($report['department_name']) ?></p>
                <?php endif; ?>

                <?php if (!empty($report['staff_name'])): ?>
                    <p class="text-sm text-gray-700"><strong>Assigned Staff:</strong> <?= htmlspecialchars($report['staff_name']) ?></p>
                <?php endif; ?>

                <?php if (!empty($photos)): ?>
                    <div class="mt-4">
                        <p class="font-semibold text-gray-700 mb-2">Attachments:</p>
                        <div class="flex flex-wrap gap-2">
                            <?php foreach ($photos as $photo): ?>
                                <a href="<?= htmlspecialchars($photo) ?>" target="_blank" class="w-24 h-24 overflow-hidden rounded-md border border-gray-200 hover:border-blue-500 transition-colors duration-200">
                                    <img src="<?= htmlspecialchars($photo) ?>" alt="Attachment" class="w-full h-full object-cover">
                                </a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
                
                <!-- Comments Section -->
                <div class="mt-6">
                    <h4 class="font-semibold text-gray-800 mb-2">Staff Comments:</h4>
                    <div class="space-y-2 mb-4 max-h-48 overflow-y-auto">
                        <?php if (isset($comments_by_report[$report['report_id']])): ?>
                            <?php foreach ($comments_by_report[$report['report_id']] as $comment_row): ?>
                                <div class="border-b border-gray-200 last:border-b-0 py-2">
                                    <p class="text-sm"><strong><?= htmlspecialchars($comment_row['full_name']) ?>:</strong> <?= htmlspecialchars($comment_row['comment']) ?></p>
                                    <p class="text-xs text-gray-500"><?= date('F j, Y, g:i a', strtotime($comment_row['created_at'])) ?></p>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p class="text-sm text-gray-500">No staff comments yet.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<?php include_once '../includes/footer.php'; ?>
