<?php
session_start();
include_once '../includes/config.php';

header('Content-Type: application/json');

// This would be for admin users to send system notifications
// For demo purposes, we'll create some sample system notifications

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (isset($input['action']) && $input['action'] === 'create_system_notification') {
        $title = $input['title'] ?? 'System Notification';
        $message = $input['message'] ?? 'System update notification';
        $type = $input['type'] ?? 'system';
        
        // Send to all users (in a real system, you'd have better targeting)
        $stmt = $conn->prepare("INSERT INTO notifications (user_id, title, message, type, created_at) SELECT user_id, ?, ?, ?, NOW() FROM users");
        $stmt->bind_param("sss", $title, $message, $type);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'System notification sent to all users']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to send notification']);
        }
        $stmt->close();
    }
    
    // Demo: Create sample notifications for testing
    if (isset($input['action']) && $input['action'] === 'create_demo_notifications') {
        $user_id = $_SESSION['user_id'] ?? 1;
        
        $demo_notifications = [
            ['title' => 'System Maintenance', 'message' => 'Scheduled system maintenance will occur on Sunday from 2:00 AM to 4:00 AM.', 'type' => 'system'],
            ['title' => 'New Feature Available', 'message' => 'You can now view your payment history in the Payment Center.', 'type' => 'info'],
            ['title' => 'Report Update', 'message' => 'Your pothole report #123 has been assigned to the Roads Department.', 'type' => 'report']
        ];
        
        foreach ($demo_notifications as $notification) {
            $stmt = $conn->prepare("INSERT INTO notifications (user_id, title, message, type, created_at) VALUES (?, ?, ?, ?, NOW())");
            $stmt->bind_param("isss", $user_id, $notification['title'], $notification['message'], $notification['type']);
            $stmt->execute();
            $stmt->close();
        }
        
        echo json_encode(['success' => true, 'message' => 'Demo notifications created']);
    }
}

$conn->close();
?>
