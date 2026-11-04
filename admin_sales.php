<?php
session_start();
include_once('connect_db.php');

if(!isset($_SESSION['username'])){
    header("location:index.php");
    exit();
}
$user = $_SESSION['username'];
$message1 = '';

// Fetch Sales from the 'receipts' table
try {
    $query = "
        SELECT r.*, pt.Name as payment_method 
        FROM receipts r
        LEFT JOIN paymenttypes pt ON r.payType = pt.Name
        ORDER BY r.date DESC
    ";
    
    $stmt = $con->prepare($query);
    $stmt->execute();
    $sales = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // --- DEBUG: If you want to see exactly what columns exist, uncomment the line below ---
    // if(isset($sales[0])) { die('<pre>' . print_r($sales[0], true) . '</pre>'); }

} catch (PDOException $e) {
    $message1 = "Error loading sales: " . htmlspecialchars($e->getMessage());
    $sales = [];
}

// Calculate Total Revenue for today
$total_today = 0;
$today = date('Y-m-d');
foreach($sales as $s) {
    // Check for 'amount' or 'total' to prevent errors
    $amt = $s['amount'] ?? $s['total'] ?? $s['payment_amount'] ?? 0;
    $date_val = $s['date'] ?? $s['payment_date'] ?? null;
    
    if($date_val && date('Y-m-d', strtotime($date_val)) === $today) {
        $total_today += $amt;
    }
}

$page_title = "Sales Transactions - Pharmacy Management System";
$topbar_title = "Admin Dashboard";

include 'includes/head.php';
include 'includes/sidebar_admin.php';
include 'includes/header.php';
?>

<main class="main">
    <h1>Sales Transactions</h1>
    <p class="lead">View all processed payments, invoices, and daily revenues.</p>

    <?php if ($message1 !== ''): ?>
      <div class="alert alert--error" role="alert"><?php echo htmlspecialchars($message1); ?></div>
    <?php endif; ?>

    <div class="metrics-grid" style="margin-bottom: 30px;">
        <div class="metric-card">
            <div class="metric-icon metric-icon--pine"><i class="fas fa-money-bill-wave"></i></div>
            <div class="metric-info">
              <h3>Total Revenue Today</h3>
              <p>$<?php echo number_format((float)$total_today, 2); ?></p>
            </div>
        </div>
        <div class="metric-card">
            <div class="metric-icon metric-icon--amber"><i class="fas fa-receipt"></i></div>
            <div class="metric-info">
              <h3>Total Transactions</h3>
              <p><?php echo number_format(count($sales)); ?></p>
            </div>
        </div>
    </div>

    <section class="card">
      <div class="card-head">
        <h2>All Transactions <span class="count">(<?php echo count($sales); ?>)</span></h2>
        <?php if (count($sales) > 0): ?>
        <div class="search">
          <i class="fas fa-search" aria-hidden="true"></i>
          <input type="search" id="filter" placeholder="Search by invoice or method..." aria-label="Search sales">
        </div>
        <?php endif; ?>
      </div>

      <?php if (count($sales) === 0): ?>
        <div class="empty">
          <p>No sales transactions found in the receipts table.</p>
        </div>
      <?php else: ?>
      <div class="table-wrap">
        <table id="salesTable">
          <thead>
            <tr>
              <th>Invoice No.</th>
              <th>Amount</th>
              <th>Payment Method</th>
              <th>Serial No.</th>
              <th>Date & Time</th>
            </tr>
          </thead>
          <tbody>
          <?php foreach ($sales as $row): 
            // Safely detect column names based on common variations
            $inv = $row['invoice_no'] ?? $row['invoice_id'] ?? 'N/A';
            $amt = $row['amount'] ?? $row['total'] ?? $row['payment_amount'] ?? 0;
            $type = $row['payment_method'] ?? $row['payType'] ?? 'Unknown';
            $serial = $row['serial_no'] ?? 'N/A';
            $date_val = $row['date'] ?? $row['payment_date'] ?? date('Y-m-d');
          ?>
            <tr>
              <td><strong>#<?php echo htmlspecialchars($inv); ?></strong></td>
              <td>$<?php echo number_format((float)$amt, 2); ?></td>
              <td>
                <span style="background: var(--mist); padding: 4px 8px; border-radius: 4px; font-size: 0.85rem; font-weight: 600;">
                    <?php echo htmlspecialchars($type); ?>
                </span>
              </td>
              <td><?php echo htmlspecialchars($serial); ?></td>
              <td><?php echo date('M j, Y - g:i A', strtotime($date_val)); ?></td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <div class="no-match" id="noMatch">No sales match your search.</div>
      <?php endif; ?>
    </section>
</main>

<script>
(function () {
  var filter = document.getElementById('filter');
  if (filter) {
    var rows = document.querySelectorAll('#salesTable tbody tr');
    var noMatch = document.getElementById('noMatch');
    filter.addEventListener('input', function () {
      var q = filter.value.trim().toLowerCase();
      var shown = 0;
      rows.forEach(function (r) {
        var hit = r.textContent.toLowerCase().indexOf(q) !== -1;
        r.style.display = hit ? '' : 'none';
        if (hit) shown++;
      });
      noMatch.style.display = shown === 0 ? 'block' : 'none';
    });
  }
})();
</script>

<?php include 'includes/footer.php'; ?>