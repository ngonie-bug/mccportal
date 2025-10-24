<?php
// Set the content type to JSON to ensure the response is handled correctly
header('Content-Type: application/json');

session_start();
include_once '../includes/config.php';

// Check for a POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); // Method Not Allowed
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit();
}

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401); // Unauthorized
    echo json_encode(['success' => false, 'message' => 'User not authenticated.']);
    exit();
}

$user_id = $_SESSION['user_id'];

// Get the POST data from the JSON body
$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['bill_id']) || !isset($input['payment_method'])) {
    http_response_code(400); // Bad Request
    echo json_encode(['success' => false, 'message' => 'Missing required data.']);
    exit();
}

$bill_id = (int)$input['bill_id'];
$payment_method = $input['payment_method'];

// Start a transaction to ensure both queries succeed or fail together
$conn->begin_transaction();

try {
    // 1. Fetch the amount for the bill
    $stmt = $conn->prepare("SELECT amount FROM bills WHERE bill_id = ? AND user_id = ?");
    if ($stmt === false) {
        throw new Exception("Prepare statement failed: " . $conn->error);
    }
    $stmt->bind_param("ii", $bill_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $bill = $result->fetch_assoc();
    $stmt->close();

    if (!$bill) {
        throw new Exception("Bill not found or does not belong to the user.");
    }
    $amount = $bill['amount'];

    // 2. Insert a new payment record
    $payment_date = date('Y-m-d H:i:s');
    $status = 'completed';
    $receipt_url = "receipts/{$user_id}/{$bill_id}/" . uniqid() . ".pdf";

    $stmt = $conn->prepare("INSERT INTO payments (user_id, bill_id, amount, payment_method, payment_date, status, receipt_url) VALUES (?, ?, ?, ?, ?, ?, ?)");
    if ($stmt === false) {
        throw new Exception("Prepare statement failed: " . $conn->error);
    }
    $stmt->bind_param("iidssss", $user_id, $bill_id, $amount, $payment_method, $payment_date, $status, $receipt_url);
    $stmt->execute();
    $stmt->close();

    // 3. Update the bill status to 'paid' (or delete it, depending on your system)
    $stmt = $conn->prepare("UPDATE bills SET status = 'paid' WHERE bill_id = ? AND user_id = ?");
    if ($stmt === false) {
        throw new Exception("Prepare statement failed: " . $conn->error);
    }
    $stmt->bind_param("ii", $bill_id, $user_id);
    $stmt->execute();
    $stmt->close();
    
    // If all queries were successful, commit the transaction
    $conn->commit();
    echo json_encode(['success' => true, 'message' => 'Payment recorded successfully.']);

} catch (Exception $e) {
    // If any query failed, roll back the transaction
    $conn->rollback();
    http_response_code(500); // Internal Server Error
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

$conn->close();
exit();
?>
