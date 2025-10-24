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
$user_id = $_SESSION['user_id'];

// Handle form submission for profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $full_name = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $account_number = trim($_POST['account_number']);
    $stand_number = trim($_POST['stand_number']);
    $location = trim($_POST['location']);

    // Input validation
    if (empty($full_name) || empty($email) || empty($account_number) || empty($stand_number) || empty($location)) {
        $message = "Full name, email, account number, stand number, and location are required.";
        $message_class = 'error';
    } else {
        // Check for duplicate email or phone, but exclude the current user
        $check_stmt = $conn->prepare("SELECT user_id FROM users WHERE (email = ? OR phone = ?) AND user_id != ?");
        $check_stmt->bind_param("ssi", $email, $phone, $user_id);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();

        if ($check_result->num_rows > 0) {
            $message = "The email or phone number is already in use by another account.";
            $message_class = 'error';
        } else {
            // Update user information
            $update_query = "UPDATE users SET full_name = ?, email = ?, phone = ?, account_number = ?, stand_number = ?, location = ? WHERE user_id = ?";
            $stmt = $conn->prepare($update_query);
            $stmt->bind_param("ssssssi", $full_name, $email, $phone, $account_number, $stand_number, $location, $user_id);

            if ($stmt->execute()) {
                $message = "Profile updated successfully!";
                $message_class = 'success';
            } else {
                $message = "Error updating profile: " . $stmt->error;
                $message_class = 'error';
            }
            $stmt->close();
        }
        $check_stmt->close();
    }
}

// Fetch current user details to pre-fill the form
$stmt = $conn->prepare("SELECT full_name, email, phone, account_number, stand_number, location FROM users WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();
$conn->close();

include_once '../includes/header.php';
?>

<div class="profile-container">
    <div class="profile-header">
        <h1 class="text-2xl font-bold text-gray-800">My Profile</h1>
        <p class="text-gray-600">Manage your personal information and account settings</p>
    </div>

    <?php if ($message): ?>
        <div class="alert alert-<?= $message_class ?>">
            <?= htmlspecialchars($message) ?>
        </div>
    <?php endif; ?>

    <form action="profile.php" method="POST">
        <div class="form-group">
            <label for="full_name" class="form-label">Full Name</label>
            <input type="text" id="full_name" name="full_name" value="<?= htmlspecialchars($user['full_name']) ?>" required class="form-input">
        </div>
        
        <div class="form-group">
            <label for="email" class="form-label">Email Address</label>
            <input type="email" id="email" name="email" value="<?= htmlspecialchars($user['email']) ?>" required class="form-input">
        </div>
        
        <div class="form-group">
            <label for="phone" class="form-label">Contact Number</label>
            <input type="text" id="phone" name="phone" value="<?= htmlspecialchars($user['phone']) ?>" class="form-input">
        </div>

        <div class="form-group">
            <label for="account_number" class="form-label">Account Number</label>
            <input type="text" id="account_number" name="account_number" value="<?= htmlspecialchars($user['account_number']) ?>" required class="form-input">
        </div>

        <div class="form-group">
            <label for="stand_number" class="form-label">Stand Number</label>
            <input type="text" id="stand_number" name="stand_number" value="<?= htmlspecialchars($user['stand_number']) ?>" required class="form-input">
        </div>

        <div class="form-group">
            <label for="location" class="form-label">Location</label>
            <input type="text" id="location" name="location" value="<?= htmlspecialchars($user['location']) ?>" required class="form-input">
        </div>

        <button type="submit" name="update_profile" class="btn-update">
            Update Profile
        </button>
    </form>
</div>

<?php include_once '../includes/footer.php'; ?>