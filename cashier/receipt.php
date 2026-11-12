<?php
session_start();
include_once('../connect_db.php');

if (!isset($_SESSION['username'])) {
    header("location:../index.php");
    exit();
}
$user  = $_SESSION['username'];
$fname = $_SESSION['first_name'];

$message1 = '';
$currency = 'KES'; // change if you prefer another symbol

// Receipt number from the URL
$receipt_no = isset($_GET['receipt']) ? trim($_GET['receipt']) : '';

$receiptData = null;

if ($receipt_no !== '' && ctype_digit($receipt_no)) {
    try {
        $stmt = $con->prepare("SELECT * FROM receipts WHERE reciptNo = :rno LIMIT 1");
        $stmt->execute([':rno' => (int) $receipt_no]);
        $receiptData = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Receipt load failed: " . $e->getMessage());
        $message1 = "Could not load the receipt. Please try again.";
    }
}

if (!$receiptData && $message1 === '') {
    $message1 = "Receipt not found.";
}

// Pre-compute amounts for display
if ($receiptData) {
    $total    = (float) $receiptData['total'];
    $discount = (float) ($receiptData['discount'] ?? 0);
    $subtotal = $total + $discount;
    $tendered = $receiptData['tendered'];
    $change   = (float) ($receiptData['change_due'] ?? 0);
}

$page_title   = "Receipt - Pharmacy";
$topbar_title = "Cashier Dashboard";

include '../includes/head.php';
?>

<!-- PRINT STYLES -->
<style>
@media print {
  @page { margin: 10mm; }
  body * { visibility: hidden; }
  .print-area, .print-area * { visibility: visible; }
  .print-area { position: absolute; left: 0; top: 0; width: 100%; }
  .sidebar, .topbar, .btn, .menu-btn, .scrim, .no-print { display: none !important; }
  .card { border: none; box-shadow: none; }
}
.rcpt-row { display: flex; justify-content: space-between; margin-bottom: 8px; gap: 16px; }
.rcpt-row span { color: var(--ink-soft); }
.rcpt-row strong { text-align: right; }
</style>

<?php
include '../includes/sidebar_cashier.php';
include '../includes/header.php';
?>

<main class="main">
    <div class="no-print" style="margin-bottom: 20px;">
        <a href="payment.php" style="color: var(--ink-soft); font-weight: 600;"><i class="fas fa-arrow-left"></i> Back to POS</a>
    </div>

    <?php if ($message1 !== ''): ?>
      <div class="alert alert--error" role="alert"><?php echo htmlspecialchars($message1); ?></div>
    <?php endif; ?>

    <?php if ($receiptData): ?>

    <div class="print-area" style="max-width: 500px; margin: 0 auto;">
        <div class="card" style="padding: 40px; text-align: center;">
            <div class="brand-mark" style="margin: 0 auto 20px; transform: rotate(0);"></div>
            <h1 style="margin: 0; font-size: 1.5rem;">Pharmacy Management</h1>
            <p style="color: var(--ink-soft); margin: 4px 0 24px;">Official Payment Receipt</p>

            <!-- Receipt info -->
            <div style="border-top: 1px dashed var(--line); border-bottom: 1px dashed var(--line); padding: 20px 0; margin-bottom: 24px; text-align: left;">
                <div class="rcpt-row">
                    <span>Receipt No:</span>
                    <strong>#<?php echo htmlspecialchars($receiptData['reciptNo']); ?></strong>
                </div>
                <?php if (!empty($receiptData['customer_id'])): ?>
                <div class="rcpt-row">
                    <span>Invoice / Rx No:</span>
                    <strong><?php echo htmlspecialchars($receiptData['customer_id']); ?></strong>
                </div>
                <?php endif; ?>
                <div class="rcpt-row">
                    <span>Date:</span>
                    <strong><?php echo date('M j, Y - g:i A', strtotime($receiptData['date'])); ?></strong>
                </div>
                <div class="rcpt-row" style="margin-bottom: 0;">
                    <span>Served By:</span>
                    <strong><?php echo htmlspecialchars($receiptData['served_by']); ?></strong>
                </div>
            </div>

            <!-- Customer -->
            <?php if (!empty($receiptData['customer_name']) || !empty($receiptData['customer_phone'])): ?>
            <div style="text-align: left; margin-bottom: 24px;">
                <?php if (!empty($receiptData['customer_name'])): ?>
                <div class="rcpt-row">
                    <span>Customer:</span>
                    <strong><?php echo htmlspecialchars($receiptData['customer_name']); ?></strong>
                </div>
                <?php endif; ?>
                <?php if (!empty($receiptData['customer_phone'])): ?>
                <div class="rcpt-row" style="margin-bottom: 0;">
                    <span>Phone:</span>
                    <strong><?php echo htmlspecialchars($receiptData['customer_phone']); ?></strong>
                </div>
                <?php endif; ?>
            </div>
            <?php endif; ?>

            <!-- Payment method -->
            <div style="text-align: left; margin-bottom: 24px;">
                <div class="rcpt-row">
                    <span>Payment Method:</span>
                    <strong><?php echo htmlspecialchars($receiptData['payType']); ?></strong>
                </div>
                <?php if (!empty($receiptData['serialno'])): ?>
                <div class="rcpt-row" style="margin-bottom: 0;">
                    <span>Serial No:</span>
                    <strong><?php echo htmlspecialchars($receiptData['serialno']); ?></strong>
                </div>
                <?php endif; ?>
            </div>

            <!-- Amounts -->
            <div style="text-align: left; margin-bottom: 16px;">
                <?php if ($discount > 0): ?>
                <div class="rcpt-row">
                    <span>Subtotal:</span>
                    <strong><?php echo htmlspecialchars($currency); ?> <?php echo number_format($subtotal, 2); ?></strong>
                </div>
                <div class="rcpt-row">
                    <span>Discount:</span>
                    <strong>- <?php echo htmlspecialchars($currency); ?> <?php echo number_format($discount, 2); ?></strong>
                </div>
                <?php endif; ?>
            </div>

            <div style="background: var(--mist); padding: 16px; border-radius: 8px; display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
                <span style="font-weight: 600; color: var(--ink-soft);">Total Paid</span>
                <span style="font-size: 1.8rem; font-weight: 700; color: var(--pine);">
                    <?php echo htmlspecialchars($currency); ?> <?php echo number_format($total, 2); ?>
                </span>
            </div>

            <?php if ($tendered !== null && $change > 0): ?>
            <div style="text-align: left; margin-bottom: 24px;">
                <div class="rcpt-row">
                    <span>Amount Received:</span>
                    <strong><?php echo htmlspecialchars($currency); ?> <?php echo number_format((float) $tendered, 2); ?></strong>
                </div>
                <div class="rcpt-row" style="margin-bottom: 0;">
                    <span>Change:</span>
                    <strong><?php echo htmlspecialchars($currency); ?> <?php echo number_format($change, 2); ?></strong>
                </div>
            </div>
            <?php endif; ?>

            <!-- Notes -->
            <?php if (!empty($receiptData['notes'])): ?>
            <div style="text-align: left; margin-bottom: 24px; padding-top: 16px; border-top: 1px dashed var(--line);">
                <span style="color: var(--ink-soft); font-size: 0.85rem;">Notes</span>
                <p style="margin: 4px 0 0;"><?php echo nl2br(htmlspecialchars($receiptData['notes'])); ?></p>
            </div>
            <?php endif; ?>

            <p style="font-size: 0.85rem; color: var(--ink-soft); margin-top: 24px;">Thank you for your business!</p>
        </div>

        <div class="no-print" style="display: flex; gap: 12px; margin-top: 24px;">
            <button type="button" onclick="window.print()" class="btn" style="flex: 1;">
                <i class="fas fa-print"></i> Print Receipt
            </button>
            <a href="payment.php" class="btn btn--ghost" style="flex: 1;">New Sale</a>
        </div>
    </div>

    <?php endif; ?>
</main>

<?php include '../includes/footer.php'; ?>