<?php
session_start();
include_once '..includes/config.php';

// Check if the user is logged in
if (!isset($_SESSION['user_id']) || !isset($_GET['payment_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$payment_id = intval($_GET['payment_id']);

// Fetch payment details with bill information
$stmt = $conn->prepare("
    SELECT p.*, b.bill_type, b.description, u.full_name, u.email 
    FROM payments p 
    JOIN bills b ON p.bill_id = b.bill_id 
    JOIN users u ON p.user_id = u.user_id 
    WHERE p.payment_id = ? AND p.user_id = ?
");
$stmt->bind_param("ii", $payment_id, $user_id);
$stmt->execute();
$payment = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$payment) {
    header("Location: payments.php");
    exit();
}

$conn->close();

// Generate PDF if requested
if (isset($_GET['download']) && $_GET['download'] === 'pdf') {
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="receipt_' . $payment_id . '.pdf"');
    // In a real application, you would use a PDF library like TCPDF or FPDF
    echo "PDF generation would be implemented here with a proper PDF library.";
    exit();
}

include_once '..includes/header.php';
?>

<div class="bg-white p-8 rounded-lg shadow-md w-full max-w-2xl mx-auto">
    <div class="text-center mb-8">
        <h2 class="text-3xl font-bold text-gray-800">Payment Receipt</h2>
        <p class="text-gray-600 mt-2">Masvingo City Council</p>
    </div>

    <div class="border-2 border-gray-200 rounded-lg p-6">
         Receipt Header 
        <div class="flex justify-between items-start mb-6">
            <div>
                <h3 class="text-lg font-semibold text-gray-800">Receipt #<?= $payment_id ?></h3>
                <p class="text-sm text-gray-600">Transaction ID: <?= htmlspecialchars($payment['transaction_id']) ?></p>
            </div>
            <div class="text-right">
                <span class="<?= $payment['status'] === 'completed' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' ?> px-3 py-1 rounded-full text-sm font-medium">
                    <?= ucfirst($payment['status']) ?>
                </span>
            </div>
        </div>

         Customer Information 
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
            <div>
                <h4 class="font-semibold text-gray-800 mb-2">Customer Information</h4>
                <p class="text-gray-700"><?= htmlspecialchars($payment['full_name']) ?></p>
                <p class="text-gray-600 text-sm"><?= htmlspecialchars($payment['email']) ?></p>
            </div>
            <div>
                <h4 class="font-semibold text-gray-800 mb-2">Payment Details</h4>
                <p class="text-gray-700">Date: <?= date('M j, Y g:i A', strtotime($payment['payment_date'])) ?></p>
                <p class="text-gray-700">Method: <?= ucwords(str_replace('_', ' ', $payment['payment_method'])) ?></p>
            </div>
        </div>

         Bill Details 
        <div class="border-t border-gray-200 pt-6 mb-6">
            <h4 class="font-semibold text-gray-800 mb-4">Bill Details</h4>
            <div class="flex justify-between items-center mb-2">
                <span class="text-gray-700"><?= htmlspecialchars($payment['bill_type']) ?></span>
                <span class="font-semibold text-gray-900">$<?= number_format($payment['amount'], 2) ?></span>
            </div>
            <?php if ($payment['description']): ?>
                <p class="text-sm text-gray-600"><?= htmlspecialchars($payment['description']) ?></p>
            <?php endif; ?>
        </div>

         Total 
        <div class="border-t border-gray-200 pt-4">
            <div class="flex justify-between items-center text-lg font-bold">
                <span class="text-gray-800">Total Paid</span>
                <span class="text-gray-900">$<?= number_format($payment['amount'], 2) ?></span>
            </div>
        </div>

         Notes 
        <?php if ($payment['notes']): ?>
            <div class="mt-6 p-4 bg-gray-50 rounded-lg">
                <h5 class="font-semibold text-gray-800 mb-2">Notes</h5>
                <p class="text-gray-700 text-sm"><?= htmlspecialchars($payment['notes']) ?></p>
            </div>
        <?php endif; ?>
    </div>

     Actions 
    <div class="flex justify-center space-x-4 mt-8">
        <button onclick="window.print()" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg">
            <i class="fas fa-print mr-2"></i>Print Receipt
        </button>
        <a href="?payment_id=<?= $payment_id ?>&download=pdf" class="bg-green-600 hover:bg-green-700 text-white px-6 py-2 rounded-lg">
            <i class="fas fa-download mr-2"></i>Download PDF
        </a>
        <a href="payments.php" class="bg-gray-500 hover:bg-gray-600 text-white px-6 py-2 rounded-lg">
            <i class="fas fa-arrow-left mr-2"></i>Back to Payments
        </a>
    </div>
</div>

<style>
@media print {
    body * {
        visibility: hidden;
    }
    .bg-white, .bg-white * {
        visibility: visible;
    }
    .bg-white {
        position: absolute;
        left: 0;
        top: 0;
        width: 100%;
    }
    nav, footer, .flex.justify-center {
        display: none !important;
    }
}
</style>

<?php include_once '..includes/footer.php'; ?>
