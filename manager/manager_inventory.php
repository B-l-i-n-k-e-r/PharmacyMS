<?php
session_start();
include_once('../connect_db.php');

if (empty($_SESSION['username'])) { header('Location: ../index.php'); exit(); }
$user = $_SESSION['username'];

try {
    $stmt = $con->query("SELECT * FROM stock ORDER BY quantity ASC");
    $stocks = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) { $stocks = []; }

$page_title = "Inventory - MedFlow PMS";
$topbar_title = "Manager Dashboard";

include '../includes/head.php';
include '../includes/sidebar_manager.php';
include '../includes/header.php';
?>

<main class="main">
    <h1>Inventory Management</h1>
    <p class="lead">Monitor stock levels to determine what needs ordering.</p>

    <section class="card">
      <div class="card-head">
        <h2>Stock Levels <span class="count">(<?php echo count($stocks); ?>)</span></h2>
        <div class="search">
          <i class="fas fa-search"></i>
          <input type="search" id="filter" placeholder="Search drug name...">
        </div>
      </div>
      <div class="table-wrap">
        <table id="stockTable">
          <thead>
            <tr>
              <th>Drug Name</th>
              <th>Category</th>
              <th>Supplier</th>
              <th>Qty</th>
              <th>Status</th>
            </tr>
          </thead>
          <tbody>
          <?php foreach ($stocks as $row): ?>
            <tr>
              <td><strong><?php echo htmlspecialchars($row['drug_name']); ?></strong></td>
              <td><?php echo htmlspecialchars($row['category']); ?></td>
              <td><?php echo htmlspecialchars($row['supplier']); ?></td>
              <td>
                <?php if($row['quantity'] < 10): ?>
                    <span style="color: var(--danger); font-weight: 700;"><?php echo $row['quantity']; ?> (Low)</span>
                <?php else: ?>
                    <?php echo $row['quantity']; ?>
                <?php endif; ?>
              </td>
              <td>
                <?php if($row['quantity'] > 0): ?>
                    <span style="color: var(--ok); font-weight: 600;">Available</span>
                <?php else: ?>
                    <span style="color: var(--danger); font-weight: 600;">Out of Stock</span>
                <?php endif; ?>
              </td>
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
    var rows = document.querySelectorAll('#stockTable tbody tr');
    filter.addEventListener('input', function () {
      var q = filter.value.trim().toLowerCase();
      rows.forEach(function (r) { r.style.display = r.textContent.toLowerCase().indexOf(q) !== -1 ? '' : 'none'; });
    });
  }
})();
</script>

<?php include '../includes/footer.php'; ?>