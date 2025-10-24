<?php
session_start();
include_once '../includes/config.php';
include_once 'admin_header.php';

// Check if the user is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

// Fetch user details
$user_id = $_GET['id'] ?? null;
if ($user_id) {
    $stmt = $conn->prepare("SELECT * FROM users WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    $stmt->close();
} else {
    header("Location: manage_users.php");
    exit();
}

$message = '';
$message_class = '';

// Handle form submission for adding a bill
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $amount = $_POST['amount'];
    $description = $_POST['description'];
    
    // Insert bill into the database
    $stmt = $conn->prepare("INSERT INTO bills (user_id, amount, description, created_at) VALUES (?, ?, ?, NOW())");
    $stmt->bind_param("ids", $user_id, $amount, $description);
    
    if ($stmt->execute()) {
        $message = "Bill added successfully.";
        $message_class = 'bg-green-100 text-green-800';
    } else {
        $message = "Error adding bill.";
        $message_class = 'bg-red-100 text-red-800';
    }
    $stmt->close();
}

$conn->close();
?>

<h1 class="text-3xl font-bold text-gray-800 mb-6">Add Bill for <?= htmlspecialchars($user['full_name']) ?></h1>

<?php if ($message): ?>
    <div class="p-4 mb-4 text-sm rounded-lg <?= htmlspecialchars($message_class) ?>" role="alert">
        <?= htmlspecialchars($message) ?>
    </div>
<?php endif; ?>

<form action="add_bill.php?id=<?= $user_id ?>" method="POST" class="space-y-4">
    <div>
        <label for="amount" class="block text-gray-700 font-medium mb-2">Bill Amount</label>
        <input type="number" id="amount" name="amount" required class="w-full p-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
    </div>
    <div>
        <label for="description" class="block text-gray-700 font-medium mb-2">Description</label>
        <textarea id="description" name="description" rows="3" required class="w-full p-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"></textarea>
    </div>
    <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold py-3 px-4 rounded-lg transition-colors duration-200">
        Add Bill
    </button>
</form>

<?php
include_once 'admin_footer.php';
?>