<?php
session_start();
include_once '../includes/config.php';

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Handle mark as read action
if (isset($_POST['mark_read']) && isset($_POST['notification_id'])) {
    $notification_id = intval($_POST['notification_id']);
    $stmt = $conn->prepare("UPDATE notifications SET is_read = TRUE WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $notification_id, $user_id);
    $stmt->execute();
    $stmt->close();
}

// Handle mark all as read action
if (isset($_POST['mark_all_read'])) {
    $stmt = $conn->prepare("UPDATE notifications SET is_read = TRUE WHERE user_id = ? AND is_read = FALSE");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stmt->close();
}


// Fetch notifications
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';
$where_clause = "WHERE user_id = ?";
$params = [$user_id];
$param_types = "i";

if ($filter === 'unread') {
    $where_clause .= " AND is_read = FALSE";
} elseif ($filter === 'read') {
    $where_clause .= " AND is_read = TRUE";
} elseif (in_array($filter, ['payment', 'report', 'system', 'info', 'message'])) {
    $where_clause .= " AND type = ?";
    $params[] = $filter;
    $param_types .= "s";
}

$stmt = $conn->prepare("SELECT * FROM notifications $where_clause ORDER BY created_at DESC LIMIT 50");
$stmt->bind_param($param_types, ...$params);
$stmt->execute();
$notifications = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Get notification counts
$stmt = $conn->prepare("SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN is_read = FALSE THEN 1 ELSE 0 END) as unread,
    SUM(CASE WHEN type = 'payment' THEN 1 ELSE 0 END) as payment,
    SUM(CASE WHEN type = 'report' THEN 1 ELSE 0 END) as report,
    SUM(CASE WHEN type = 'system' THEN 1 ELSE 0 END) as system,
    SUM(CASE WHEN type = 'message' THEN 1 ELSE 0 END) as message
    FROM notifications WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$counts = $stmt->get_result()->fetch_assoc();
$stmt->close();

$conn->close();

include_once '../includes/header.php';
?>

<div class="bg-white p-8 rounded-lg shadow-md w-full max-w-6xl mx-auto">
    <div class="flex items-center justify-between mb-8">
        <div>
            <h2 class="text-3xl font-bold text-gray-800">Notifications</h2>
            <p class="text-gray-600 mt-2">Stay updated with your account activities</p>
        </div>
        <div class="flex items-center space-x-4">
            <?php if ($counts['unread'] > 0): ?>
                <form method="POST" class="inline">
                    <button type="submit" name="mark_all_read" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm">
                        <i class="fas fa-check-double mr-2"></i>Mark All Read
                    </button>
                </form>
            <?php endif; ?>
            <a href="dashboard.php" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-lg">
                <i class="fas fa-arrow-left mr-2"></i>Back to Dashboard
            </a>
        </div>
    </div>

    <!-- Filter Tabs -->
    <div class="mb-6">
        <div class="border-b border-gray-200">
            <nav class="-mb-px flex space-x-8">
                <a href="?filter=all" class="<?= $filter === 'all' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' ?> whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm">
                    All (<?= $counts['total'] ?>)
                </a>
                <a href="?filter=unread" class="<?= $filter === 'unread' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' ?> whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm">
                    Unread (<?= $counts['unread'] ?>)
                </a>
                <a href="?filter=message" class="<?= $filter === 'message' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' ?> whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm">
                    Messages (<?= $counts['message'] ?>)
                </a>
                <a href="?filter=report" class="<?= $filter === 'report' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' ?> whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm">
                    Reports (<?= $counts['report'] ?>)
                </a>
                <a href="?filter=system" class="<?= $filter === 'system' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' ?> whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm">
                    System (<?= $counts['system'] ?>)
                </a>
            </nav>
        </div>
    </div>

    <!-- Notifications List -->
    <?php if (empty($notifications)): ?>
        <div class="text-center py-12">
            <i class="fas fa-bell-slash text-gray-400 text-6xl mb-4"></i>
            <h3 class="text-xl font-semibold text-gray-600 mb-2">No Notifications</h3>
            <p class="text-gray-500">
                <?php if ($filter === 'unread'): ?>
                    You have no unread notifications.
                <?php elseif ($filter === 'read'): ?>
                    You have no read notifications.
                <?php else: ?>
                    You don't have any notifications yet.
                <?php endif; ?>
            </p>
        </div>
    <?php else: ?>
        <div class="space-y-4">
            <?php foreach ($notifications as $notification): ?>
                <div class="<?= $notification['is_read'] ? 'bg-gray-50' : 'bg-blue-50 border-l-4 border-blue-500' ?> rounded-lg p-6 transition-all hover:shadow-md">
                    <div class="flex items-start justify-between">
                        <div class="flex-1">
                            <div class="flex items-center space-x-3 mb-2">
                                <div class="flex-shrink-0">
                                    <?php
                                    $icon_class = '';
                                    $icon_color = '';
                                    switch ($notification['type']) {
                                        case 'payment':
                                            $icon_class = 'fas fa-credit-card';
                                            $icon_color = 'text-green-500';
                                            break;
                                        case 'report':
                                            $icon_class = 'fas fa-exclamation-triangle';
                                            $icon_color = 'text-yellow-500';
                                            break;
                                        case 'message':
                                            $icon_class = 'fas fa-comment';
                                            $icon_color = 'text-blue-500';
                                            break;
                                        case 'system':
                                            $icon_class = 'fas fa-cog';
                                            $icon_color = 'text-blue-500';
                                            break;
                                        case 'success':
                                            $icon_class = 'fas fa-check-circle';
                                            $icon_color = 'text-green-500';
                                            break;
                                        case 'warning':
                                            $icon_class = 'fas fa-exclamation-triangle';
                                            $icon_color = 'text-yellow-500';
                                            break;
                                        case 'error':
                                            $icon_class = 'fas fa-times-circle';
                                            $icon_color = 'text-red-500';
                                            break;
                                        default:
                                            $icon_class = 'fas fa-info-circle';
                                            $icon_color = 'text-blue-500';
                                    }
                                    ?>
                                    <i class="<?= $icon_class ?> <?= $icon_color ?> text-xl"></i>
                                </div>
                                <div>
                                    <h4 class="font-semibold text-gray-800 <?= !$notification['is_read'] ? 'font-bold' : '' ?>">
                                        <?= htmlspecialchars($notification['title']) ?>
                                    </h4>
                                    <p class="text-sm text-gray-600">
                                        <?= date('M j, Y \a\t g:i A', strtotime($notification['created_at'])) ?>
                                    </p>
                                </div>
                            </div>
                            <p class="text-gray-700 ml-8">
                                <?= htmlspecialchars($notification['message']) ?>
                            </p>
                        </div>
                        <div class="flex items-center space-x-2 ml-4">
                            <?php if (!$notification['is_read']): ?>
                                <span class="bg-blue-500 text-white text-xs px-2 py-1 rounded-full">New</span>
                                <form method="POST" class="inline">
                                    <!-- Fixed to use 'id' instead of 'notification_id' -->
                                    <input type="hidden" name="notification_id" value="<?= $notification['id'] ?>">
                                    <button type="submit" name="mark_read" class="text-gray-400 hover:text-gray-600" title="Mark as read">
                                        <i class="fas fa-check text-sm"></i>
                                    </button>
                                </form>
                            <?php else: ?>
                                <span class="text-gray-400 text-xs">Read</span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php include_once '../includes/footer.php'; ?>
