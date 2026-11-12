<?php
session_start();
include_once('../connect_db.php');

if(isset($_SESSION['username'])){
    $user=$_SESSION['username'];
    $fname=$_SESSION['first_name'];
}else{
    header("location:../index.php");
    exit();
}

// Fetch all prescriptions safely
try {
    $stmt = $con->query("SELECT * FROM prescription ORDER BY date DESC");
    $prescriptions = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error_msg = "Database error: " . htmlspecialchars($e->getMessage());
    $prescriptions = [];
}

$page_title = "View Prescriptions - Pharmacy Management System";
$topbar_title = "Pharmacist Dashboard";

include '../includes/head.php';
include '../includes/sidebar_pharmacist.php';
include '../includes/header.php';
?>

<main class="main">
    <h1>Prescriptions</h1>
    <p class="lead">Review and manage patient prescriptions.</p>

    <?php if (isset($error_msg)): ?>
      <div class="alert alert--error" role="alert"><?php echo $error_msg; ?></div>
    <?php endif; ?>

    <section class="card">
      <div class="card-head">
        <h2>All Prescriptions <span class="count">(<?php echo count($prescriptions); ?>)</span></h2>
        <?php if (count($prescriptions) > 0): ?>
        <div class="search">
          <i class="fas fa-search" aria-hidden="true"></i>
          <input type="search" id="filter" placeholder="Search by customer or invoice..." aria-label="Search prescriptions">
        </div>
        <?php endif; ?>
      </div>

      <?php if (count($prescriptions) === 0): ?>
        <div class="empty">
          <p>No prescriptions found in the system.</p>
        </div>
      <?php else: ?>
      <div class="table-wrap">
        <table id="presTable">
          <thead>
            <tr>
              <th>Prescription No.</th>
              <th>Customer</th>
              <th>Invoice No.</th>
              <th>Date Issued</th>
            </tr>
          </thead>
          <tbody>
          <?php foreach ($prescriptions as $row): ?>
            <tr>
              <td><strong>#<?php echo htmlspecialchars($row['prescription_id']); ?></strong></td>
              <td><?php echo htmlspecialchars($row['customer_name']); ?></td>
              <td><?php echo htmlspecialchars($row['invoice_id']); ?></td>
              <td><?php echo htmlspecialchars(date('M j, Y', strtotime($row['date']))); ?></td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <div class="no-match" id="noMatch">No prescriptions match your search.</div>
      <?php endif; ?>
    </section>
</main>

<script>
(function () {
  var filter = document.getElementById('filter');
  if (filter) {
    var rows = document.querySelectorAll('#presTable tbody tr');
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