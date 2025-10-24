<?php
// This file is the payment center for the logged-in citizen.
session_start();
include_once '../includes/config.php';

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    // Correctly redirect back to the login page
    header("Location: ../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Fetch user's bills.
// The error "Unknown column 'status'" was here.
// The WHERE clause 'status != 'paid'' has been removed.
// This assumes the 'bills' table only contains outstanding bills.
$stmt = $conn->prepare("SELECT * FROM bills WHERE user_id = ? ORDER BY due_date ASC");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$bills = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Fetch payment history
$stmt = $conn->prepare("SELECT p.*, b.bill_type, b.amount as bill_amount FROM payments p JOIN bills b ON p.bill_id = b.bill_id WHERE p.user_id = ? ORDER BY p.payment_date DESC LIMIT 10");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$payment_history = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$conn->close();

// Include the header
include_once '../includes/header.php';
?>

<div class="bg-white p-8 rounded-lg shadow-md w-full max-w-6xl mx-auto">
    <div class="flex items-center justify-between mb-8">
        <div>
            <h2 class="text-3xl font-bold text-gray-800">Payment Center</h2>
            <p class="text-gray-600 mt-2">Manage your bills and payment history</p>
        </div>
        <a href="dashboard.php" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-lg">
            <i class="fas fa-arrow-left mr-2"></i>Back to Dashboard
        </a>
    </div>

    <!-- Outstanding Bills -->
    <div class="mb-8">
        <h3 class="text-2xl font-semibold text-gray-800 mb-4">Outstanding Bills</h3>
        <?php if (empty($bills)): ?>
            <div class="bg-green-50 border border-green-200 rounded-lg p-6 text-center">
                <i class="fas fa-check-circle text-green-500 text-4xl mb-4"></i>
                <h4 class="text-lg font-semibold text-green-800">No Outstanding Bills</h4>
                <p class="text-green-600">You're all caught up with your payments!</p>
            </div>
        <?php else: ?>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <?php foreach ($bills as $bill): ?>
                    <div class="bg-white border border-gray-200 rounded-lg p-6 shadow-sm">
                        <div class="flex justify-between items-start mb-4">
                            <div>
                                <h4 class="font-semibold text-lg text-gray-800"><?= htmlspecialchars($bill['bill_type']) ?></h4>
                                <p class="text-sm text-gray-600">Bill #<?= htmlspecialchars($bill['bill_id']) ?></p>
                            </div>
                            <span class="<?= strtotime($bill['due_date']) < time() ? 'bg-red-100 text-red-800' : 'bg-yellow-100 text-yellow-800' ?> px-2 py-1 rounded-full text-xs font-medium">
                                <?= strtotime($bill['due_date']) < time() ? 'Overdue' : 'Due Soon' ?>
                            </span>
                        </div>
                        <div class="mb-4">
                            <p class="text-2xl font-bold text-gray-900">$<?= number_format($bill['amount'], 2) ?></p>
                            <p class="text-sm text-gray-600">Due: <?= date('M j, Y', strtotime($bill['due_date'])) ?></p>
                        </div>
                        <button onclick="payBill(<?= $bill['bill_id'] ?>, <?= $bill['amount'] ?>, '<?= htmlspecialchars($bill['bill_type']) ?>')" 
                                class="w-full bg-blue-600 hover:bg-blue-700 text-white py-2 px-4 rounded-lg font-medium transition-colors">
                            <i class="fas fa-credit-card mr-2"></i>Pay Now
                        </button>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Payment History -->
    <div>
        <h3 class="text-2xl font-semibold text-gray-800 mb-4">Recent Payment History</h3>
        <?php if (empty($payment_history)): ?>
            <div class="bg-gray-50 border border-gray-200 rounded-lg p-6 text-center">
                <i class="fas fa-history text-gray-400 text-4xl mb-4"></i>
                <h4 class="text-lg font-semibold text-gray-600">No Payment History</h4>
                <p class="text-gray-500">Your payment history will appear here once you make payments.</p>
            </div>
        <?php else: ?>
            <div class="bg-white border border-gray-200 rounded-lg overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Bill Type</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Method</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Receipt</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php foreach ($payment_history as $payment): ?>
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        <?= date('M j, Y', strtotime($payment['payment_date'])) ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        <?= htmlspecialchars($payment['bill_type']) ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                        $<?= number_format($payment['amount'], 2) ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        <?= htmlspecialchars($payment['payment_method']) ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="<?= $payment['status'] === 'completed' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' ?> px-2 py-1 rounded-full text-xs font-medium">
                                            <?= ucfirst($payment['status']) ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-blue-600">
                                        <a href="receipt.php?payment_id=<?= $payment['payment_id'] ?>" class="hover:text-blue-800">
                                            <i class="fas fa-download mr-1"></i>Download
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Payment Modal -->
<div id="paymentModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden items-center justify-center z-50">
    <div class="bg-white rounded-lg p-8 max-w-md w-full mx-4">
        <div class="flex justify-between items-center mb-6">
            <h3 class="text-xl font-semibold text-gray-800">Payment Details</h3>
            <button onclick="closePaymentModal()" class="text-gray-400 hover:text-gray-600">
                <i class="fas fa-times text-xl"></i>
            </button>
        </div>
        
        <div id="paymentContent">
            <div class="mb-4">
                <p class="text-sm text-gray-600">Bill Type</p>
                <p id="billType" class="font-semibold text-gray-800"></p>
            </div>
            <div class="mb-6">
                <p class="text-sm text-gray-600">Amount</p>
                <p id="billAmount" class="text-2xl font-bold text-gray-900"></p>
            </div>
            
            <div class="mb-6">
                <label class="block text-sm font-medium text-gray-700 mb-2">Payment Method</label>
                <select id="paymentMethod" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="credit_card">Credit Card</option>
                    <option value="debit_card">Debit Card</option>
                    <option value="mobile_money">Mobile Money</option>
                    <option value="bank_transfer">Bank Transfer</option>
                </select>
            </div>
            
            <div class="flex space-x-4">
                <button onclick="closePaymentModal()" class="flex-1 bg-gray-300 hover:bg-gray-400 text-gray-800 py-2 px-4 rounded-lg font-medium">
                    Cancel
                </button>
                <button onclick="processPayment()" class="flex-1 bg-blue-600 hover:bg-blue-700 text-white py-2 px-4 rounded-lg font-medium">
                    <i class="fas fa-credit-card mr-2"></i>Pay Now
                </button>
            </div>
        </div>
        
        <div id="processingContent" class="hidden text-center">
            <div class="mb-4">
                <i class="fas fa-spinner fa-spin text-4xl text-blue-600"></i>
            </div>
            <p class="text-lg font-semibold text-gray-800">Processing Payment...</p>
            <p class="text-sm text-gray-600">Please wait while we process your payment.</p>
        </div>

        <!-- Success/Failure Message Container -->
        <div id="messageContainer" class="hidden text-center">
            <div id="messageIcon" class="mb-4"></div>
            <p id="messageText" class="text-lg font-semibold"></p>
            <button onclick="location.reload()" class="mt-4 bg-gray-300 hover:bg-gray-400 text-gray-800 py-2 px-4 rounded-lg font-medium">
                OK
            </button>
        </div>
    </div>
</div>

<script>
let currentBillId = null;

function showMessage(message, type) {
    const messageContainer = document.getElementById('messageContainer');
    const messageText = document.getElementById('messageText');
    const messageIcon = document.getElementById('messageIcon');

    messageText.textContent = message;
    messageText.className = 'text-lg font-semibold ' + (type === 'success' ? 'text-green-600' : 'text-red-600');
    messageIcon.innerHTML = type === 'success' 
        ? '<i class="fas fa-check-circle text-4xl text-green-600"></i>' 
        : '<i class="fas fa-exclamation-circle text-4xl text-red-600"></i>';

    document.getElementById('paymentContent').classList.add('hidden');
    document.getElementById('processingContent').classList.add('hidden');
    messageContainer.classList.remove('hidden');
}

function payBill(billId, amount, billType) {
    currentBillId = billId;
    document.getElementById('billType').textContent = billType;
    document.getElementById('billAmount').textContent = '$' + amount.toFixed(2);
    document.getElementById('paymentModal').classList.remove('hidden');
    document.getElementById('paymentModal').classList.add('flex');
    document.getElementById('messageContainer').classList.add('hidden'); // Hide messages on open
    document.getElementById('paymentContent').classList.remove('hidden'); // Show payment content
}

function closePaymentModal() {
    document.getElementById('paymentModal').classList.add('hidden');
    document.getElementById('paymentModal').classList.remove('flex');
    document.getElementById('paymentContent').classList.remove('hidden');
    document.getElementById('processingContent').classList.add('hidden');
    document.getElementById('messageContainer').classList.add('hidden');
    currentBillId = null;
}

function processPayment() {
    const paymentMethod = document.getElementById('paymentMethod').value;
    
    // Show processing state
    document.getElementById('paymentContent').classList.add('hidden');
    document.getElementById('processingContent').classList.remove('hidden');
    
    // Send payment request
    // Corrected the fetch URL to a relative path from the 'citizen' folder
    fetch('../api/process_payment.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            bill_id: currentBillId,
            payment_method: paymentMethod
        })
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('Network response was not ok');
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            showMessage('Payment successful! Your receipt has been generated.', 'success');
            setTimeout(() => {
                location.reload();
            }, 3000);
        } else {
            showMessage('Payment failed: ' + data.message, 'error');
            
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showMessage('An error occurred while processing your payment.', 'error');
    });
}
</script>

<?php include_once '../includes/footer.php'; ?>
