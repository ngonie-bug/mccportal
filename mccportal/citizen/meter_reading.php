<?php
session_start();
include_once '../includes/config.php';

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

$message = '';
$message_class = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize and validate inputs
    $citizen_id = $_SESSION['user_id'];
    $reading_value = filter_var($_POST['reading_value'], FILTER_VALIDATE_FLOAT);
    $account_number = filter_var($_POST['account_number'], FILTER_SANITIZE_STRING); // New input

    // Validate inputs
    if ($reading_value === false || $reading_value < 0) {
        $message = "Invalid meter reading value.";
        $message_class = 'text-red-500';
    } else if (empty($account_number)) {
        $message = "Account number or stand number is required.";
        $message_class = 'text-red-500';
    } else {
        // Handle file upload
        $photo_url = '';
        $upload_dir = '../uploads/readings/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        if (isset($_FILES['meter_photo']) && $_FILES['meter_photo']['error'] === UPLOAD_ERR_OK) {
            $file_info = $_FILES['meter_photo'];
            
            // Validate file type and size
            $file_ext = strtolower(pathinfo($file_info['name'], PATHINFO_EXTENSION));
            $allowed_ext = ['jpeg', 'jpg', 'png'];

            if (in_array($file_ext, $allowed_ext) && $file_info['size'] <= 5 * 1024 * 1024) {
                $unique_name = uniqid('reading_') . '.' . $file_ext;
                $upload_path = $upload_dir . $unique_name;
                
                if (move_uploaded_file($file_info['tmp_name'], $upload_path)) {
                    $photo_url = $upload_path;
                } else {
                    $message = "Error uploading file.";
                    $message_class = 'text-red-500';
                }
            } else {
                $message = "Invalid file type or size. Please use JPEG, PNG under 5MB.";
                $message_class = 'text-red-500';
            }
        } else {
            $message = "Please upload a photo of the meter reading.";
            $message_class = 'text-red-500';
        }

        if ($message === '' && $photo_url !== '') {
            // Prepare and execute database insert
            $stmt = $conn->prepare("INSERT INTO meter_readings (citizen_id, reading_value, photo_url, account_number, status) VALUES (?, ?, ?, ?, 'pending')");
            $stmt->bind_param("dssd", $citizen_id, $reading_value, $photo_url, $account_number);
            
            if ($stmt->execute()) {
                $reading_id = $conn->insert_id;
                
                // Insert a notification for the user
                $notification_title = "Meter Reading Submitted";
                $notification_message = "Your meter reading of " . htmlspecialchars($reading_value) . " m³ has been submitted successfully (ID #" . $reading_id . "). It will be verified by a city official shortly.";
                
                $notification_stmt = $conn->prepare("INSERT INTO notifications (user_id, title, message, type, created_at) VALUES (?, ?, ?, 'info', NOW())");
                $notification_stmt->bind_param("iss", $citizen_id, $notification_title, $notification_message);
                $notification_stmt->execute();
                $notification_stmt->close();
                
                $message = "Meter reading submitted successfully! It will be verified by a city official shortly.";
                $message_class = 'text-green-500';
            } else {
                $message = "Error submitting reading: " . $stmt->error;
                $message_class = 'text-red-500';
            }
            $stmt->close();
        }
    }
}

$conn->close();

include_once '../includes/header.php';
?>

<div class="bg-white p-8 rounded-lg shadow-md w-full max-w-2xl mx-auto my-12">
    <h2 class="text-3xl font-bold text-center text-gray-800 mb-6">Submit Meter Reading</h2>
    <p class="text-gray-600 text-center mb-6">Please provide the current reading from your water meter.</p>

    <?php if ($message): ?>
        <div class="p-4 mb-4 text-sm rounded-lg bg-gray-100 <?= htmlspecialchars($message_class) ?>" role="alert">
            <?= htmlspecialchars($message) ?>
        </div>
    <?php endif; ?>

    <form action="meter_reading.php" method="POST" enctype="multipart/form-data" class="space-y-4">
        <!-- Reading Value -->
        <div>
            <label for="reading_value" class="block text-gray-700 font-medium mb-2">Meter Reading Value (m³)</label>
            <input type="number" step="0.01" id="reading_value" name="reading_value" required
                    class="w-full p-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                    placeholder="e.g., 125.75">
        </div>
        
        <!-- Account Number or Stand Number -->
        <div>
            <label for="account_number" class="block text-gray-700 font-medium mb-2">Account Number / Stand Number</label>
            <input type="text" id="account_number" name="account_number" required
                    class="w-full p-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                    placeholder="Enter your account or stand number">
        </div>

        <!-- Photo Upload -->
        <div>
            <label for="meter_photo" class="block text-gray-700 font-medium mb-2">Photo of Meter Reading</label>
            <input type="file" id="meter_photo" name="meter_photo" required accept="image/jpeg,image/png"
                    class="w-full text-gray-700 bg-gray-50 border border-gray-300 rounded-lg cursor-pointer focus:outline-none">
        </div>

        <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold py-3 px-4 rounded-lg transition-colors duration-200">
            <i class="fas fa-upload mr-2"></i> Submit Reading
        </button>
    </form>
</div>

<?php include_once '../includes/footer.php'; ?>