<?php
session_start();
include_once('../connect_db.php');

if (empty($_SESSION['username'])) { header('Location: ../index.php'); exit(); }
$user = $_SESSION['username'];

function safe_h($val) {
    return htmlspecialchars((string)($val ?? ''), ENT_QUOTES, 'UTF-8');
}

try {
    $stmt = $con->query("SELECT r.*, pt.Name as payment_method FROM receipts r LEFT JOIN paymenttypes pt ON r.payType = pt.Name ORDER BY r.date DESC");
    $sales = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $sales = [];
    $message1 = "Error: " . $e->getMessage();
}

$total_revenue = 0;
foreach ($sales as $s) { $total_revenue += ($s['total'] ?? 0); }

$page_title = "Sales Ledger - MedFlow PMS";
$topbar_title = "Manager Dashboard";

include '../includes/head.php';
include '../includes/sidebar_manager.php';
include '../includes/header.php';
?>

<main class="main">
    <h1>Sales Ledger</h1>
    <p class="lead">View a complete history of all store transactions.</p>

    <div class="metrics-grid" style="margin-bottom: 30px;">
        <div class="metric-card">
            <div class="metric-icon metric-icon--pine"><i class="fas fa-coins"></i></div>
            <div class="metric-info">
              <h3>Total Revenue (All Time)</h3>
              <p style="white-space: nowrap;">Ksh&nbsp;<?php echo number_format($total_revenue, 2); ?></p>
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
        <div class="search">
          <i class="fas fa-search"></i>
          <input type="search" id="filter" placeholder="Search by receipt or method...">
        </div>
      </div>

      <div class="table-wrap">
        <table id="salesTable">
          <thead>
            <tr>
              <th>Receipt No.</th>
              <th>Customer ID</th>
              <th>Amount</th>
              <th>Method</th>
              <th>Date</th>
            </tr>
          </thead>
          <tbody>
          <?php foreach ($sales as $row): ?>
            <tr>
              <td><strong>#<?php echo safe_h($row['reciptNo'] ?? $row['receiptNo'] ?? $row['id'] ?? 'N/A'); ?></strong></td>
              <td><?php echo safe_h($row['customer_id'] ?? $row['customer_name']); ?></td>
              <td><strong>Ksh <?php echo number_format((float)($row['total'] ?? $row['amount'] ?? 0), 2); ?></strong></td>
              <td><?php echo safe_h($row['payment_method'] ?? $row['payType']); ?></td>
              <td><?php echo date('M j, Y - g:i A', strtotime($row['date'] ?? 'now')); ?></td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </section>
</main>

<script>
(function () {
  var filter = document.getElementById('filter');
  if (filter) {
    var rows = document.querySelectorAll('#salesTable tbody tr');
    filter.addEventListener('input', function () {
      var q = filter.value.trim().toLowerCase();
      rows.forEach(function (r) {
        r.style.display = r.textContent.toLowerCase().indexOf(q) !== -1 ? '' : 'none';
      });
    });
  }
})();
</script>

<?php include '../includes/footer.php'; ?>