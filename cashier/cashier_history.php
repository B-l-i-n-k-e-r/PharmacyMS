<?php
session_start();
include_once('../connect_db.php');

if (!isset($_SESSION['username'])) {
    header("location:index.php");
    exit();
}
$user       = $_SESSION['username'];
$cashier_id = $_SESSION['cashier_id'];

$currency = 'KES'; // keep the same as receipt.php

$sales = [];
$message1 = '';

// Fetch ONLY this cashier's sales
try {
    $stmt = $con->prepare(
        "SELECT * FROM receipts WHERE cashier_id = :cid ORDER BY date DESC, reciptNo DESC"
    );
    $stmt->execute([':cid' => $cashier_id]);
    $sales = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Sales history failed: " . $e->getMessage());
    $message1 = "Could not load your sales. Please try again.";
}

// Revenue for today
$total_today = 0;
$count_today = 0;
$today = date('Y-m-d');
foreach ($sales as $s) {
    if (date('Y-m-d', strtotime($s['date'])) === $today) {
        $total_today += (float) $s['total'];
        $count_today++;
    }
}

$page_title   = "My Sales History - Pharmacy";
$topbar_title = "Cashier Dashboard";

include '../includes/head.php';
include '../includes/sidebar_cashier.php';
include '../includes/header.php';
?>

<main class="main">
    <h1>My Sales History</h1>
    <p class="lead">View all the transactions you have processed.</p>

    <?php if ($message1 !== ''): ?>
      <div class="alert alert--error" role="alert"><?php echo htmlspecialchars($message1); ?></div>
    <?php endif; ?>

    <div class="metrics-grid" style="margin-bottom: 30px;">
        <div class="metric-card">
            <div class="metric-icon metric-icon--pine"><i class="fas fa-money-bill-wave"></i></div>
            <div class="metric-info">
              <h3>My Revenue Today</h3>
              <p><?php echo htmlspecialchars($currency); ?> <?php echo number_format($total_today, 2); ?></p>
            </div>
        </div>
        <div class="metric-card">
            <div class="metric-icon metric-icon--amber"><i class="fas fa-receipt"></i></div>
            <div class="metric-info">
              <h3>Today's Transactions</h3>
              <p><?php echo number_format($count_today); ?></p>
            </div>
        </div>
        <div class="metric-card">
            <div class="metric-icon metric-icon--amber"><i class="fas fa-history"></i></div>
            <div class="metric-info">
              <h3>All-Time Transactions</h3>
              <p><?php echo number_format(count($sales)); ?></p>
            </div>
        </div>
    </div>

    <section class="card">
      <div class="card-head">
        <h2>All My Transactions <span class="count">(<?php echo count($sales); ?>)</span></h2>
        <?php if (count($sales) > 0): ?>
        <div class="search">
          <i class="fas fa-search" aria-hidden="true"></i>
          <input type="search" id="filter" placeholder="Search receipt, customer, method..." aria-label="Search sales">
        </div>
        <?php endif; ?>
      </div>

      <?php if (count($sales) === 0): ?>
        <div class="empty">
          <p>You haven't processed any sales yet.</p>
          <a href="payment.php" class="btn">Go to POS</a>
        </div>
      <?php else: ?>
      <div class="table-wrap">
        <table id="salesTable">
          <thead>
            <tr>
              <th>Receipt No.</th>
              <th>Customer</th>
              <th>Invoice / Rx</th>
              <th>Total</th>
              <th>Payment Method</th>
              <th>Date &amp; Time</th>
              <th></th>
            </tr>
          </thead>
          <tbody>
          <?php foreach ($sales as $row): ?>
            <tr>
              <td><strong>#<?php echo htmlspecialchars((string) $row['reciptNo']); ?></strong></td>
              <td>
                <?php
                  $cname  = $row['customer_name']  ?? '';
                  $cphone = $row['customer_phone'] ?? '';
                  echo $cname !== '' ? htmlspecialchars($cname) : '<span style="color: var(--ink-soft);">Walk-in</span>';
                  if ($cphone !== '') {
                      echo '<br><small style="color: var(--ink-soft);">' . htmlspecialchars($cphone) . '</small>';
                  }
                ?>
              </td>
              <td><?php echo htmlspecialchars((string) ($row['customer_id'] ?? '')) ?: '&mdash;'; ?></td>
              <td>
                <strong><?php echo htmlspecialchars($currency); ?> <?php echo number_format((float) $row['total'], 2); ?></strong>
                <?php if (!empty($row['discount']) && $row['discount'] > 0): ?>
                  <br><small style="color: var(--ink-soft);">Discount: <?php echo number_format((float) $row['discount'], 2); ?></small>
                <?php endif; ?>
              </td>
              <td>
                <span style="background: var(--mist); padding: 4px 8px; border-radius: 4px; font-size: 0.85rem; font-weight: 600;">
                    <?php echo htmlspecialchars((string) ($row['payType'] ?: 'N/A')); ?>
                </span>
              </td>
              <td><?php echo date('M j, Y - g:i A', strtotime($row['date'])); ?></td>
              <td><a href="receipt.php?receipt=<?php echo urlencode((string) $row['reciptNo']); ?>" style="font-weight: 600;">View</a></td>
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

<?php include '../includes/footer.php'; ?>