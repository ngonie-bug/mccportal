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

// Fetch category to department mapping
$category_to_department = [];
$sql_mapping = "SELECT category_name, department_id FROM category_department_mapping";
$result_mapping = $conn->query($sql_mapping);
if ($result_mapping) {
    while ($row = $result_mapping->fetch_assoc()) {
        $category_to_department[$row['category_name']] = $row['department_id'];
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $citizen_id = $_SESSION['user_id'];
    $category = $_POST['category'] ?? '';
    $description = $_POST['description'] ?? '';
    $location = $_POST['location'] ?? ''; // Location as a string
    $priority = $_POST['priority'] ?? 'low'; // Default to low if not set

    // Handle file uploads (up to 3 files)
    $photo_urls = [];
    $upload_dir = '../uploads/reports/';
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }

    if (isset($_FILES['attachments'])) {
        foreach ($_FILES['attachments']['error'] as $key => $error) {
            if ($error === UPLOAD_ERR_OK) {
                $file_info = [
                    'name' => $_FILES['attachments']['name'][$key],
                    'tmp_name' => $_FILES['attachments']['tmp_name'][$key],
                    'size' => $_FILES['attachments']['size'][$key],
                ];

                $file_ext = strtolower(pathinfo($file_info['name'], PATHINFO_EXTENSION));
                $allowed_ext = ['jpeg', 'jpg', 'png', 'mp4', 'mov'];

                if (in_array($file_ext, $allowed_ext) && $file_info['size'] <= 5 * 1024 * 1024) {
                    $unique_name = uniqid('report_') . '.' . $file_ext;
                    $upload_path = $upload_dir . $unique_name;

                    if (move_uploaded_file($file_info['tmp_name'], $upload_path)) {
                        $photo_urls[] = $upload_path;
                    } else {
                        $message = "Error uploading file: " . $file_info['name'];
                        $message_class = 'text-red-500';
                        break; // Stop on first upload error
                    }
                } else {
                    $message = "Invalid file type or size. Please use JPEG, PNG, MP4, or MOV under 5MB.";
                    $message_class = 'text-red-500';
                    break;
                }
            }
        }
    }
    
    // If there were no errors during file uploads, proceed with database insertion
    if ($message === '') {
        $photo_urls_json = json_encode($photo_urls); // Serialize the array to a JSON string

        // Retrieve the department ID based on the selected category
        $assigned_department_id = $category_to_department[$category] ?? null;

        if ($assigned_department_id === null) {
            $message = "Invalid category selected.";
            $message_class = 'text-red-500';
        } else {
            // Insert the report into the database
            $stmt = $conn->prepare("INSERT INTO reports (citizen_id, category, description, photo_urls, location, priority, status, assigned_to_department_id) VALUES (?, ?, ?, ?, ?, ?, 'submitted', ?)");
            $stmt->bind_param("isssssi", $citizen_id, $category, $description, $photo_urls_json, $location, $priority, $assigned_department_id);

            if ($stmt->execute()) {
                $report_id = $conn->insert_id;

                $notification_title = "Report Submitted Successfully";
                $notification_message = "Your " . $category . " report has been submitted and assigned ID #" . $report_id . ". A department officer will review it shortly.";
                
                $notification_stmt = $conn->prepare("INSERT INTO notifications (user_id, title, message, type, created_at) VALUES (?, ?, ?, 'report', NOW())");
                $notification_stmt->bind_param("iss", $citizen_id, $notification_title, $notification_message);
                $notification_stmt->execute();
                $notification_stmt->close();
                
                $message = "Your issue has been reported successfully! A department officer will get in touch shortly.";
                $message_class = 'text-green-500';
            } else {
                $message = "Error reporting issue: " . $stmt->error;
                $message_class = 'text-red-500';
            }
            $stmt->close();
        }
    }
}

$conn->close();
include_once '../includes/header.php';
?>

<div class="bg-white p-8 rounded-lg shadow-md w-full max-w-2xl mx-auto">
    <h2 class="text-3xl font-bold text-center text-gray-800 mb-6">Report an Issue</h2>
    <p class="text-gray-600 text-center mb-6">Please provide details about the issue. We'll forward it to the correct department.</p>

    <?php if ($message): ?>
        <div class="p-4 mb-4 text-sm rounded-lg bg-gray-100 <?= htmlspecialchars($message_class) ?>" role="alert">
            <?= htmlspecialchars($message) ?>
        </div>
    <?php endif; ?>

    <form action="report_issue.php" method="POST" enctype="multipart/form-data" class="space-y-6">
        <!-- Category -->
        <div>
            <label for="category" class="block text-gray-700 font-bold mb-2">Category <span class="text-red-500">*</span></label>
            <select class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" id="category" name="category" required>
                <option value="">Select a category</option>
                <?php foreach ($category_to_department as $cat => $dept_id): ?>
                    <option value="<?= htmlspecialchars($cat) ?>"><?= htmlspecialchars($cat) ?></option>
                <?php endforeach; ?>
            </select>
            <div class="text-sm text-gray-500 mt-2">Selecting a category ensures your report is sent to the right department.</div>
        </div>

        <!-- Location -->
        <div>
            <label for="location" class="block text-gray-700 font-bold mb-2">Location</label>
            <div class="flex items-center gap-2">
                <div class="relative flex-grow">
                    <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                        <i class="fas fa-map-marker-alt text-gray-400"></i>
                    </div>
                    <input type="text" id="location" name="location" placeholder="Street address, landmark, or area name"
                           class="w-full pl-10 px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <button type="button" id="getCurrentLocation"
                        class="px-4 py-2 bg-gray-200 text-gray-700 font-medium rounded-lg hover:bg-gray-300 transition-colors duration-200 flex items-center">
                    <i class="fas fa-crosshairs mr-1"></i> Current Location
                </button>
            </div>
            <div class="text-sm text-gray-500 mt-2">Help us locate the issue more quickly.</div>
        </div>

        <!-- Description -->
        <div>
            <label for="description" class="block text-gray-700 font-bold mb-2">Detailed Description <span class="text-red-500">*</span></label>
            <textarea class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                      id="description" name="description" rows="6" maxlength="1000"
                      placeholder="Provide detailed information about the issue..." required></textarea>
            <div class="text-sm text-gray-500 flex justify-between mt-1">
                <span><span id="charCount">0</span>/1000 characters</span>
                <span>Include as much detail as possible to help us understand and address the issue.</span>
            </div>
        </div>

        <!-- Priority Level -->
        <div>
            <label class="block text-gray-700 font-bold mb-2">Priority Level</label>
            <div class="flex flex-col md:flex-row gap-4">
                <div class="flex-1 flex items-center bg-gray-100 p-4 rounded-lg shadow-sm cursor-pointer hover:bg-gray-200 transition-colors duration-200">
                    <input class="form-radio h-5 w-5 text-green-600 mr-2 border-gray-300 focus:ring-green-500" type="radio" name="priority" id="low" value="low" checked>
                    <label class="block text-gray-700 font-medium" for="low">
                        <span class="inline-block px-2 py-1 text-xs font-semibold rounded-full bg-green-500 text-white">Low</span>
                        <small class="block text-gray-500">Non-urgent issue</small>
                    </label>
                </div>
                <div class="flex-1 flex items-center bg-gray-100 p-4 rounded-lg shadow-sm cursor-pointer hover:bg-gray-200 transition-colors duration-200">
                    <input class="form-radio h-5 w-5 text-yellow-600 mr-2 border-gray-300 focus:ring-yellow-500" type="radio" name="priority" id="medium" value="medium">
                    <label class="block text-gray-700 font-medium" for="medium">
                        <span class="inline-block px-2 py-1 text-xs font-semibold rounded-full bg-yellow-500 text-white">Medium</span>
                        <small class="block text-gray-500">Moderate concern</small>
                    </label>
                </div>
                <div class="flex-1 flex items-center bg-gray-100 p-4 rounded-lg shadow-sm cursor-pointer hover:bg-gray-200 transition-colors duration-200">
                    <input class="form-radio h-5 w-5 text-red-600 mr-2 border-gray-300 focus:ring-red-500" type="radio" name="priority" id="high" value="high">
                    <label class="block text-gray-700 font-medium" for="high">
                        <span class="inline-block px-2 py-1 text-xs font-semibold rounded-full bg-red-500 text-white">High</span>
                        <small class="block text-gray-500">Urgent attention needed</small>
                    </label>
                </div>
            </div>
        </div>

        <!-- Attachments -->
        <div>
            <label for="attachments" class="block text-gray-700 font-bold mb-2">Attachments (Up to 3 photos/videos, max 5MB each)</label>
            <input type="file" id="attachments" name="attachments[]" multiple accept="image/jpeg,image/png,video/mp4,video/quicktime"
                   class="w-full text-gray-700 bg-gray-50 border border-gray-300 rounded-lg cursor-pointer focus:outline-none">
        </div>

        <button type="submit" class="w-full bg-purple-600 hover:bg-purple-700 text-white font-semibold py-3 px-4 rounded-lg transition-colors duration-200">
            <i class="fas fa-exclamation-triangle mr-2"></i> Submit Report
        </button>
    </form>
</div>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const charCountSpan = document.getElementById('charCount');
        const descriptionTextarea = document.getElementById('description');
        const getLocationBtn = document.getElementById('getCurrentLocation');
        const locationInput = document.getElementById('location');

        // Character counter for the description textarea
        descriptionTextarea.addEventListener('keyup', () => {
            const count = descriptionTextarea.value.length;
            charCountSpan.textContent = count;
        });

        // Get the user's current location and populate the input field
        getLocationBtn.addEventListener('click', () => {
            if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition(position => {
                    const lat = position.coords.latitude;
                    const lon = position.coords.longitude;
                    // Set the location input to the latitude and longitude
                    locationInput.value = `Lat: ${lat.toFixed(4)}, Lon: ${lon.toFixed(4)}`;
                }, error => {
                    console.error("Error getting location: ", error);
                    // Use a custom message box instead of alert()
                    const messageBox = document.createElement('div');
                    messageBox.className = 'fixed top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 bg-red-500 text-white p-4 rounded-lg shadow-lg z-50';
                    messageBox.innerHTML = '<p>Unable to retrieve your location. Please enter it manually.</p>';
                    document.body.appendChild(messageBox);
                    setTimeout(() => messageBox.remove(), 3000);
                });
            } else {
                // Use a custom message box instead of alert()
                const messageBox = document.createElement('div');
                messageBox.className = 'fixed top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 bg-red-500 text-white p-4 rounded-lg shadow-lg z-50';
                messageBox.innerHTML = '<p>Geolocation is not supported by your browser.</p>';
                document.body.appendChild(messageBox);
                setTimeout(() => messageBox.remove(), 3000);
            }
        });
    });
</script>

<?php include_once '../includes/footer.php'; ?>
