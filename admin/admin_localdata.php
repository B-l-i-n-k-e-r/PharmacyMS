<?php
session_start();
include_once('../connect_db.php');

if(isset($_SESSION['username'])){
    $id=$_SESSION['admin_id'];
    $username=$_SESSION['username'];
}else{
    header("location:../index.php");
    exit();
}

// Handle city filtering
$selected_val = "All"; // Default value
if(isset($_POST['cityvalue'])){
    $selected_val = $_POST['City'];
}

// Base dynamic query syntax definition
if($selected_val == "All" || $selected_val == "Select" || empty($selected_val)) {
    $queryStr = "SELECT * FROM pharmacist";
    $stmtView = $con->prepare($queryStr);
    $stmtView->execute();
} else {
    $queryStr = "SELECT * FROM pharmacist WHERE postal_address = :city";
    $stmtView = $con->prepare($queryStr);
    $stmtView->execute([':city' => $selected_val]);
}

$rows = $stmtView->fetchAll(PDO::FETCH_ASSOC);
$cities = ['Fullerton', 'Michikan', 'Newark', 'Ladora'];

$page_title = "Local Data - Pharmacy Management System";
$topbar_title = "Admin Dashboard";

include '../includes/head.php';
?>

<style>
@media print {
  @page { margin: 15mm; }
  body * { visibility: hidden; }
  .print-area, .print-area * { visibility: visible; }
  .print-area { position: absolute; left: 0; top: 0; width: 100%; border: none; box-shadow: none; }
  .print-header { display: flex !important; border-bottom: 3px solid #123b36; padding-bottom: 15px; margin-bottom: 25px; align-items: center; justify-content: space-between; }
  .print-header-left h1 { font-size: 22pt; margin: 0; color: #123b36; letter-spacing: -0.02em; }
  .print-header-left p { margin: 4px 0 0; font-size: 11pt; color: #555; }
  .print-meta { text-align: right; font-size: 10pt; color: #555; }
  .print-meta strong { color: #123b36; }
  .card { border: none; }
  .card-head { padding: 0 0 15px 0; border-bottom: 2px solid #e0e0e0; margin-bottom: 10px; }
  .card-head h2 { font-size: 14pt; color: #123b36; }
  table { font-size: 10pt; }
  th { background: #f0f0f0 !important; color: #123b36 !important; border-bottom: 1px solid #999; padding: 10px 12px; }
  td { padding: 10px 12px; border-bottom: 1px solid #e0e0e0; }
  .print-footer { display: block !important; position: fixed; bottom: 0; left: 0; width: 100%; text-align: center; font-size: 9pt; color: #888; border-top: 1px solid #ddd; padding-top: 10px; }
  .sidebar, .topbar, .filter-bar, .btn, .menu-btn, .scrim, .no-print, .badge { display: none !important; }
}
.print-header, .print-footer { display: none; }
</style>

<?php
include '../includes/sidebar_admin.php';
include '../includes/header.php';
?>

<main class="main">
    <h1>Local Data</h1>
    <p class="lead">Find pharmacists locally from the selected cities.</p>

    <form action="" method="post" class="filter-bar no-print">
      <label for="city">Filter by City:</label>
      <select name="City" id="city">
        <option value="Select" <?php echo ($selected_val == 'Select') ? 'selected' : ''; ?>>Select City</option>
        <option value="All" <?php echo ($selected_val == 'All') ? 'selected' : ''; ?>>All</option>
        <?php foreach ($cities as $city): ?>
          <option value="<?php echo $city; ?>" <?php echo ($selected_val == $city) ? 'selected' : ''; ?>>
            <?php echo $city; ?>
          </option>
        <?php endforeach; ?>
      </select>
      <button type="submit" name="cityvalue" class="btn">Apply Filter</button>
    </form>

    <section class="card print-area">
      <div class="print-header">
        <div class="print-header-left">
          <h1>Local Pharmacists Report</h1>
          <p>Pharmacy Management System</p>
        </div>
        <div class="print-meta">
          <strong>Filter Applied:</strong> <?php echo ($selected_val == 'All' || $selected_val == 'Select') ? 'All Cities' : htmlspecialchars($selected_val); ?><br>
          <strong>Generated on:</strong> <?php echo date('M j, Y \a\t g:i A'); ?><br>
          <strong>Total Records:</strong> <?php echo count($rows); ?>
        </div>
      </div>

      <div class="card-head">
        <h2>
          <?php if($selected_val == 'All' || $selected_val == 'Select' || empty($selected_val)): ?>
            All Pharmacists
          <?php else: ?>
            Pharmacists in <?php echo htmlspecialchars($selected_val); ?>
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
          <p>No pharmacists found for this selection.</p>
        </div>
      <?php else: ?>
      <div class="table-wrap">
        <table>
          <thead>
            <tr>
              <th class="num">ID</th>
              <th>Staff ID</th>
              <th>First name</th>
              <th>Last name</th>
              <th>Username</th>
              <th>Address</th>
              <th>Phone</th>
            </tr>
          </thead>
          <tbody>
          <?php foreach ($rows as $row): ?>
            <tr>
              <td class="num"><?php echo htmlspecialchars($row['pharmacist_id']); ?></td>
              <td><?php echo htmlspecialchars($row['staff_id']); ?></td>
              <td><?php echo htmlspecialchars($row['first_name']); ?></td>
              <td><?php echo htmlspecialchars($row['last_name']); ?></td>
              <td><?php echo htmlspecialchars($row['username']); ?></td>
              <td><?php echo htmlspecialchars($row['postal_address']); ?></td>
              <td><?php echo htmlspecialchars($row['phone']); ?></td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <?php endif; ?>

      <div class="print-footer">
        Pharmacy Management System &copy; <?php echo date('Y'); ?> — Official Report
      </div>
    </section>
</main>

<?php include '../includes/footer.php'; ?>