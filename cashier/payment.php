<?php
session_start();
include_once('../connect_db.php');

if (isset($_SESSION['username'])) {
    $cashier_id = $_SESSION['cashier_id'];
    $fname      = $_SESSION['first_name'];
    $user       = $_SESSION['username'];
} else {
    header("location:../index.php");
    exit();
}

$message1 = '';
$old = []; // keeps the form filled in after a validation error

// Fetch payment types
try {
    $stmt = $con->query("SELECT Name FROM paymenttypes");
    $paymentTypes = $stmt->fetchAll(PDO::FETCH_COLUMN);
} catch (PDOException $e) {
    $paymentTypes = ['Cash', 'Card']; // Fallback
}

// Handle Payment Submission
if (isset($_POST['submit_payment'])) {
    $old = $_POST;

    $customer_name  = trim($_POST['customer_name'] ?? '');
    $customer_phone = trim($_POST['customer_phone'] ?? '');
    $invoice_val    = trim($_POST['invoice_no'] ?? '');
    $amount_raw     = $_POST['amount'] ?? '';
    $discount_raw   = ($_POST['discount'] ?? '') === '' ? '0' : $_POST['discount'];
    $payType        = trim($_POST['payType'] ?? '');
    $serial_no      = trim($_POST['serial_no'] ?? '');
    $tendered_raw   = trim($_POST['tendered'] ?? '');
    $notes          = trim($_POST['notes'] ?? '');

    $isCash = (strcasecmp($payType, 'Cash') === 0);

    if (!is_numeric($amount_raw) || $amount_raw <= 0) {
        $message1 = "Enter a valid amount greater than zero.";
    } elseif (!is_numeric($discount_raw) || $discount_raw < 0) {
        $message1 = "Discount must be zero or more.";
    } elseif ((int) round($discount_raw) >= (int) round($amount_raw)) {
        $message1 = "Discount must be less than the amount.";
    } elseif (!in_array($payType, $paymentTypes, true)) {
        $message1 = "Please select a valid payment method.";
    } elseif (strlen($customer_name) > 60) {
        $message1 = "Customer name must be 60 characters or fewer.";
    } elseif ($customer_phone !== '' && !preg_match('/^[0-9+\s-]{7,20}$/', $customer_phone)) {
        $message1 = "Enter a valid phone number (digits, spaces, + or - only).";
    } elseif (strlen($invoice_val) > 20) {
        $message1 = "Invoice / prescription number must be 20 characters or fewer.";
    } elseif (strlen($serial_no) > 10) {
        $message1 = "Serial number must be 10 characters or fewer.";
    } elseif (strlen($notes) > 255) {
        $message1 = "Notes must be 255 characters or fewer.";
    } else {
        $subtotal = (int) round($amount_raw);
        $discount = (int) round($discount_raw);
        $total    = $subtotal - $discount;           // amount actually payable

        // Amount tendered and change
        if ($tendered_raw === '') {
            if ($isCash) {
                $message1 = "Enter the amount received from the customer.";
            }
            $tendered = $total;
        } elseif (!is_numeric($tendered_raw) || $tendered_raw < 0) {
            $message1 = "Enter a valid amount received.";
            $tendered = $total;
        } else {
            $tendered = (int) round($tendered_raw);
            if ($tendered < $total) {
                $message1 = "Amount received is less than the total due (" . number_format($total) . ").";
            }
        }
        $change = max($tendered - $total, 0);

        if ($message1 === '') {
            $served_by = substr($fname, 0, 15);
            $serial    = ($serial_no === '')      ? null : $serial_no;
            $cname     = ($customer_name === '')  ? null : $customer_name;
            $cphone    = ($customer_phone === '') ? null : $customer_phone;
            $notes_db  = ($notes === '')          ? null : $notes;

            try {
                // reciptNo (auto-increment) and date (default timestamp) are filled by MySQL
                $stmt = $con->prepare(
                    "INSERT INTO receipts
                        (customer_id, customer_name, customer_phone, total, discount,
                         tendered, change_due, payType, serialno, notes, served_by, cashier_id)
                     VALUES
                        (:inv, :cname, :cphone, :total, :disc,
                         :tend, :chg, :type, :serial, :notes, :served, :cid)"
                );
                $stmt->execute([
                    ':inv'    => $invoice_val,          // '' if left blank (column is NOT NULL)
                    ':cname'  => $cname,
                    ':cphone' => $cphone,
                    ':total'  => $total,
                    ':disc'   => $discount,
                    ':tend'   => $tendered,
                    ':chg'    => $change,
                    ':type'   => $payType,
                    ':serial' => $serial,
                    ':notes'  => $notes_db,
                    ':served' => $served_by,
                    ':cid'    => $cashier_id
                ]);

                $receipt_no = $con->lastInsertId();

                header("Location: receipt.php?receipt=" . urlencode($receipt_no));
                exit();
            } catch (PDOException $e) {
                error_log("Payment insert failed: " . $e->getMessage());
                $message1 = "Could not save the payment. Please try again.";
            }
        }
    }
}

// Helper to refill form values safely
function old($key) {
    global $old;
    return htmlspecialchars($old[$key] ?? '');
}

$page_title   = "Process Payment - Pharmacy";
$topbar_title = "Cashier Dashboard";

include '../includes/head.php';
include '../includes/sidebar_cashier.php';
include '../includes/header.php';
?>

<main class="main">
    <h1>Process Payment</h1>
    <p class="lead">Enter the customer and payment details to generate a receipt.</p>

    <?php if ($message1 !== ''): ?>
      <div class="alert alert--error" role="alert"><?php echo htmlspecialchars($message1); ?></div>
    <?php endif; ?>

    <div style="display: grid; grid-template-columns: 1.4fr 1fr; gap: 30px;">

        <!-- LEFT: PAYMENT FORM -->
        <section class="card form-card" style="max-width: 100%;">
            <form action="payment.php" method="post" autocomplete="off">

                <h2>Customer</h2>
                <div class="grid">
                    <div class="field">
                        <label for="customer_name">Customer Name <small>(optional)</small></label>
                        <input name="customer_name" id="customer_name" type="text" maxlength="60"
                               placeholder="e.g. Jane Wanjiku" value="<?php echo old('customer_name'); ?>">
                    </div>
                    <div class="field">
                        <label for="customer_phone">Phone <small>(optional)</small></label>
                        <input name="customer_phone" id="customer_phone" type="tel" maxlength="20"
                               placeholder="e.g. 0712 345 678" value="<?php echo old('customer_phone'); ?>">
                    </div>
                    <div class="field span-2">
                        <label for="invoice_no">Invoice / Prescription No. <small>(optional)</small></label>
                        <input name="invoice_no" id="invoice_no" type="text" maxlength="20"
                               placeholder="e.g. RX-1001" value="<?php echo old('invoice_no'); ?>">
                    </div>
                </div>

                <h2 style="margin-top: 28px;">Payment</h2>
                <div class="grid">
                    <div class="field">
                        <label for="amount">Amount</label>
                        <input name="amount" id="amount" type="number" step="1" min="1" required
                               placeholder="0" value="<?php echo old('amount'); ?>"
                               style="font-size: 1.3rem; font-weight: 700;">
                    </div>
                    <div class="field">
                        <label for="discount">Discount <small>(optional)</small></label>
                        <input name="discount" id="discount" type="number" step="1" min="0"
                               placeholder="0" value="<?php echo old('discount'); ?>"
                               style="font-size: 1.3rem; font-weight: 700;">
                    </div>
                    <div class="field">
                        <label for="payType">Payment Method</label>
                        <select name="payType" id="payType" required>
                            <option value="">Select...</option>
                            <?php foreach ($paymentTypes as $type): ?>
                                <option value="<?php echo htmlspecialchars($type); ?>"
                                    <?php echo (($old['payType'] ?? '') === $type) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($type); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="field">
                        <label for="serial_no">Serial No. / Ref <small>(card, cheque)</small></label>
                        <input name="serial_no" id="serial_no" type="text" maxlength="10"
                               placeholder="Optional for cash" value="<?php echo old('serial_no'); ?>">
                    </div>
                    <div class="field span-2">
                        <label for="tendered">Amount Received <small id="tenderedHint">(from customer)</small></label>
                        <input name="tendered" id="tendered" type="number" step="1" min="0"
                               placeholder="0" value="<?php echo old('tendered'); ?>"
                               style="font-size: 1.3rem; font-weight: 700;">
                    </div>
                    <div class="field span-2">
                        <label for="notes">Notes <small>(optional)</small></label>
                        <textarea name="notes" id="notes" rows="2" maxlength="255"
                                  placeholder="Anything to record about this sale"><?php echo old('notes'); ?></textarea>
                    </div>
                </div>

                <!-- Live summary -->
                <div style="background: var(--mist); border-radius: 8px; padding: 16px; margin-top: 24px;">
                    <div style="display: flex; justify-content: space-between; margin-bottom: 6px;">
                        <span style="color: var(--ink-soft);">Total Due</span>
                        <strong id="sumTotal" style="font-size: 1.4rem; color: var(--pine);">0</strong>
                    </div>
                    <div style="display: flex; justify-content: space-between;">
                        <span style="color: var(--ink-soft);">Change</span>
                        <strong id="sumChange" style="font-size: 1.1rem;">0</strong>
                    </div>
                </div>

                <div class="form-actions" style="margin-top: 24px;">
                    <button type="submit" name="submit_payment" class="btn" style="width: 100%; padding: 16px; font-size: 1.1rem;">
                        <i class="fas fa-check"></i> Complete Payment
                    </button>
                </div>
            </form>
        </section>

        <!-- RIGHT: INSTRUCTIONS -->
        <section class="card" style="padding: 28px; background: #f8faf9; align-self: start;">
            <h2 style="color: var(--pine);">Cashier Instructions</h2>
            <ul style="color: var(--ink-soft); line-height: 1.8; padding-left: 20px;">
                <li>Add the <strong>customer name and phone</strong> if the customer gives them.</li>
                <li>Enter the <strong>Invoice / Prescription No.</strong> when there is one.</li>
                <li>Enter the <strong>Amount</strong> and any <strong>Discount</strong>. The total due updates as you type.</li>
                <li>Select the <strong>Payment Method</strong>.</li>
                <li>For cash, enter the <strong>Amount Received</strong>. The change is worked out for you.</li>
                <li>For card or cheque, enter the <strong>Serial Number</strong>.</li>
                <li>Click <strong>Complete Payment</strong> to save the record.</li>
            </ul>

            <div style="margin-top: 30px; padding-top: 20px; border-top: 1px solid var(--line);">
                <p style="font-size: 0.9rem; color: var(--ink-soft);">
                    <i class="fas fa-info-circle"></i> The receipt number is generated automatically. After saving, you will be taken to the printable receipt.
                </p>
            </div>
        </section>

    </div>
</main>

<script>
(function () {
    var amount   = document.getElementById('amount');
    var discount = document.getElementById('discount');
    var tendered = document.getElementById('tendered');
    var payType  = document.getElementById('payType');
    var sumTotal = document.getElementById('sumTotal');
    var sumChange = document.getElementById('sumChange');
    var hint     = document.getElementById('tenderedHint');

    function fmt(n) { return Number(n).toLocaleString(); }

    function update() {
        var a = parseFloat(amount.value) || 0;
        var d = parseFloat(discount.value) || 0;
        var total = Math.max(Math.round(a) - Math.round(d), 0);
        var isCash = payType.value.toLowerCase() === 'cash';

        sumTotal.textContent = fmt(total);

        var t = tendered.value === '' ? null : Math.round(parseFloat(tendered.value) || 0);
        var change = (t === null) ? 0 : Math.max(t - total, 0);
        sumChange.textContent = fmt(change);
        sumChange.style.color = (t !== null && t < total) ? '#b00020' : '';

        tendered.required = isCash;
        hint.textContent = isCash ? '(required for cash)' : '(optional)';
    }

    [amount, discount, tendered].forEach(function (el) { el.addEventListener('input', update); });
    payType.addEventListener('change', update);
    update();
})();
</script>

<?php include '../includes/footer.php'; ?>