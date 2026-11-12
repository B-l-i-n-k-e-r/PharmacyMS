<?php
session_start();
include_once('../connect_db.php');

if(isset($_SESSION['username'])){
    $id=$_SESSION['admin_id'];
    $user=$_SESSION['username'];
}else{
    header("location:../index.php");
    exit();
}

// Handle filter selection
$selected_val = "All"; // Default setting
if(isset($_POST['findvalue'])){
    $selected_val = $_POST['Find'];
}

// Base definition structural execution
if($selected_val == "Min") {
    $queryStr = "SELECT * FROM (
                    SELECT * FROM stock_np 
                    UNION ALL SELECT * FROM stock_ca 
                    UNION ALL SELECT * FROM stock_dr
                 ) AS A
                 WHERE stock_id IN (
                    SELECT stock_id FROM (          
                        SELECT stock_id, cost FROM stock_np WHERE cost = (SELECT MIN(cost) FROM stock_np) 
                        UNION SELECT stock_id, cost FROM stock_ca WHERE cost = (SELECT MIN(cost) FROM stock_ca)
                        UNION SELECT stock_id, cost FROM stock_dr WHERE cost = (SELECT MIN(cost) FROM stock_dr)
                    ) AS M
                    WHERE cost IN (
                        SELECT MIN(cost) FROM (
                            SELECT stock_id, cost FROM stock_np WHERE cost = (SELECT MIN(cost) FROM stock_np) 
                            UNION SELECT stock_id, cost FROM stock_ca WHERE cost = (SELECT MIN(cost) FROM stock_ca)
                            UNION SELECT stock_id, cost FROM stock_dr WHERE cost = (SELECT MIN(cost) FROM stock_dr)
                        ) AS M2
                    )
                 )";
} elseif($selected_val == "Max") {
    $queryStr = "SELECT * FROM (
                    SELECT * FROM stock_np 
                    UNION ALL SELECT * FROM stock_ca 
                    UNION ALL SELECT * FROM stock_dr
                 ) AS A
                 WHERE stock_id IN (
                    SELECT stock_id FROM (          
                        SELECT stock_id, cost FROM stock_np WHERE cost = (SELECT MAX(cost) FROM stock_np) 
                        UNION SELECT stock_id, cost FROM stock_ca WHERE cost = (SELECT MAX(cost) FROM stock_ca)
                        UNION SELECT stock_id, cost FROM stock_dr WHERE cost = (SELECT MAX(cost) FROM stock_dr)
                    ) AS M
                    WHERE cost IN (
                        SELECT MAX(cost) FROM (
                            SELECT stock_id, cost FROM stock_np WHERE cost = (SELECT MAX(cost) FROM stock_np) 
                            UNION SELECT stock_id, cost FROM stock_ca WHERE cost = (SELECT MAX(cost) FROM stock_ca)
                            UNION SELECT stock_id, cost FROM stock_dr WHERE cost = (SELECT MAX(cost) FROM stock_dr)
                        ) AS M2
                    )
                 )";
} else {
    // Falls back to "All" or "Select" choices
    $queryStr = "SELECT * FROM stock_np 
                 UNION ALL SELECT * FROM stock_ca 
                 UNION ALL SELECT * FROM stock_dr 
                 ORDER BY stock_id";
}

$stmtView = $con->query($queryStr);
$rows = $stmtView->fetchAll(PDO::FETCH_ASSOC);

$page_title = "Global Data - Pharmacy Management System";
$topbar_title = "Admin Dashboard";

include '../includes/head.php';
?>

<style>
@media print {
  @page { margin: 10mm; size: landscape; }
  body * { visibility: hidden; }
  .print-area, .print-area * { visibility: visible; }
  .print-area { position: absolute; left: 0; top: 0; width: 100%; border: none; box-shadow: none; }
  .print-header { display: flex !important; border-bottom: 3px solid #123b36; padding-bottom: 15px; margin-bottom: 20px; align-items: center; justify-content: space-between; }
  .print-header-left h1 { font-size: 20pt; margin: 0; color: #123b36; letter-spacing: -0.02em; }
  .print-header-left p { margin: 4px 0 0; font-size: 10pt; color: #555; }
  .print-meta { text-align: right; font-size: 9pt; color: #555; }
  .print-meta strong { color: #123b36; }
  .card { border: none; }
  .card-head { padding: 0 0 10px 0; border-bottom: 1px solid #e0e0e0; margin-bottom: 10px; }
  .card-head h2 { font-size: 12pt; color: #123b36; }
  table { font-size: 9pt; }
  th { background: #f0f0f0 !important; color: #123b36 !important; border-bottom: 1px solid #999; padding: 8px 10px; white-space: normal; }
  td { padding: 8px 10px; border-bottom: 1px solid #e0e0e0; white-space: normal; }
  .print-footer { display: block !important; position: fixed; bottom: 0; left: 0; width: 100%; text-align: center; font-size: 8pt; color: #888; border-top: 1px solid #ddd; padding-top: 8px; }
  .sidebar, .topbar, .filter-bar, .btn, .menu-btn, .scrim, .no-print, .badge { display: none !important; }
}
.print-header, .print-footer { display: none; }
</style>

<?php
include '../includes/sidebar_admin.php';
include '../includes/header.php';
?>

<main class="main">
    <h1>Global Data</h1>
    <p class="lead">Find minimum or maximum cost of medicine across all cities.</p>

    <form action="" method="post" class="filter-bar no-print">
      <label for="find">Filter Options:</label>
      <select name="Find" id="find">
        <option value="Select" <?php echo ($selected_val == 'Select') ? 'selected' : ''; ?>>----Select----</option>
        <option value="All" <?php echo ($selected_val == 'All') ? 'selected' : ''; ?>>All</option>
        <option value="Min" <?php echo ($selected_val == 'Min') ? 'selected' : ''; ?>>Min Cost</option>
        <option value="Max" <?php echo ($selected_val == 'Max') ? 'selected' : ''; ?>>Max Cost</option>
      </select>
      <button type="submit" name="findvalue" class="btn">Apply Filter</button>
    </form>

    <section class="card print-area">
      <div class="print-header">
        <div class="print-header-left">
          <h1>Global Medicines Report</h1>
          <p>Pharmacy Management System</p>
        </div>
        <div class="print-meta">
          <strong>Filter Applied:</strong> 
          <?php 
            if($selected_val == 'Min') echo 'Minimum Cost';
            elseif($selected_val == 'Max') echo 'Maximum Cost';
            else echo 'All Medicines';
          ?><br>
          <strong>Generated on:</strong> <?php echo date('M j, Y \a\t g:i A'); ?><br>
          <strong>Total Records:</strong> <?php echo count($rows); ?>
        </div>
      </div>

      <div class="card-head">
        <h2>
          <?php if($selected_val == 'Min'): ?>
            Minimum Cost Medicines
          <?php elseif($selected_val == 'Max'): ?>
            Maximum Cost Medicines
          <?php else: ?>
            All Medicines
          <?php endif; ?>
          <span class="count no-print">(<?php echo count($rows); ?>)</span>
        </h2>

        <?php if (count($rows) > 0): ?>
        <button type="button" onclick="window.print()" class="btn btn--outline no-print">
          <i class="fas fa-print"></i> Print Report
        </button>
        <?php endif; ?>
      </div>

      <?php if (count($rows) === 0): ?>
        <div class="empty">
          <p>No medicines found for this selection.</p>
        </div>
      <?php else: ?>
      <div class="table-wrap">
        <table>
          <thead>
            <tr>
              <th class="num">ID</th>
              <th>Name</th>
              <th>Category</th>
              <th>Description</th>
              <th>Company</th>
              <th>Supplier</th>
              <th>City</th>
              <th>Quantity</th>
              <th>Cost</th>
              <th>Status</th>
              <th>Date</th>
            </tr>
          </thead>
          <tbody>
          <?php foreach ($rows as $row): ?>
            <tr>
              <td class="num"><?php echo htmlspecialchars($row['stock_id']); ?></td>
              <td><?php echo htmlspecialchars($row['drug_name']); ?></td>
              <td><?php echo htmlspecialchars($row['category']); ?></td>
              <td><?php echo htmlspecialchars($row['description']); ?></td>
              <td><?php echo htmlspecialchars($row['company']); ?></td>
              <td><?php echo htmlspecialchars($row['supplier']); ?></td>
              <td><?php echo htmlspecialchars($row['city']); ?></td>
              <td><?php echo htmlspecialchars($row['quantity']); ?></td>
              <td>Ksh <?php echo number_format((float)$row['cost'], 2); ?></td>
              <td><?php echo htmlspecialchars($row['status']); ?></td>
              <td><?php echo htmlspecialchars($row['date_supplied']); ?></td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <?php endif; ?>

      <div class="print-footer">
        Pharmacy Management System &copy; <?php echo date('Y'); ?> — Official Global Report
      </div>
    </section>
</main>

<?php include '../includes/footer.php'; ?>