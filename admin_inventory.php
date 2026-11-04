<?php
session_start();
include_once('connect_db.php');

if(!isset($_SESSION['username'])){
    header("location:index.php");
    exit();
}
$user = $_SESSION['username'];

$message1 = '';

// Handle Restock (Adding quantity)
if(isset($_POST['restock'])){
    $stock_id = $_POST['stock_id'];
    $add_qty = (int)$_POST['add_qty'];

    if($add_qty > 0) {
        try {
            // Update quantity and set status to Available
            $stmt = $con->prepare("UPDATE stock SET quantity = quantity + :qty, status = 'Available' WHERE stock_id = :id");
            $stmt->execute([':qty' => $add_qty, ':id' => $stock_id]);
            
            header("location:admin_inventory.php?restocked=1");
            exit();
        } catch (PDOException $e) {
            $message1 = "Error updating inventory: " . htmlspecialchars($e->getMessage());
        }
    }
}

// Fetch all inventory
try {
    $stmtView = $con->query("SELECT * FROM stock ORDER BY quantity ASC, drug_name ASC");
    $rows = $stmtView->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $message1 = "Error loading inventory: " . htmlspecialchars($e->getMessage());
    $rows = [];
}

$restocked = isset($_GET['restocked']);

// Layout Settings
$page_title = "Inventory Management - Pharmacy Management System";
$topbar_title = "Admin Dashboard";

include 'includes/head.php';
include 'includes/sidebar_admin.php';
include 'includes/header.php';
?>

<main class="main">
    <h1>Inventory Management</h1>
    <p class="lead">Monitor stock levels, add quantities, and track low stock items.</p>

    <?php if ($restocked): ?>
      <div class="alert alert--ok" role="status">Inventory updated successfully.</div>
    <?php endif; ?>
    <?php if ($message1 !== ''): ?>
      <div class="alert alert--error" role="alert"><?php echo htmlspecialchars($message1); ?></div>
    <?php endif; ?>

    <section class="card">
      <div class="card-head">
        <h2>Current Stock <span class="count">(<?php echo count($rows); ?>)</span></h2>
        <?php if (count($rows) > 0): ?>
        <div class="search">
          <i class="fas fa-search" aria-hidden="true"></i>
          <input type="search" id="filter" placeholder="Search inventory..." aria-label="Search inventory">
        </div>
        <?php endif; ?>
      </div>

      <?php if (count($rows) === 0): ?>
        <div class="empty">
          <p>No inventory found. Go to the Products Catalog to add products first.</p>
          <a href="admin_products.php" class="btn">Go to Products</a>
        </div>
      <?php else: ?>
      <div class="table-wrap">
        <table id="inventoryTable">
          <thead>
            <tr>
              <th>Drug Name</th>
              <th>Category</th>
              <th>Quantity</th>
              <th>Status</th>
              <th>Last Supplied</th>
              <th class="actions">Actions</th>
            </tr>
          </thead>
          <tbody>
          <?php foreach ($rows as $row): ?>
            <tr>
              <td><strong><?php echo htmlspecialchars($row['drug_name']); ?></strong></td>
              <td><?php echo htmlspecialchars($row['category']); ?></td>
              <td>
                <?php if($row['quantity'] < 10): ?>
                    <span style="color: var(--danger); font-weight: 700;"><?php echo htmlspecialchars($row['quantity']); ?></span>
                    <span style="font-size: 0.75rem; color: var(--danger); display: block;">Low Stock</span>
                <?php else: ?>
                    <?php echo htmlspecialchars($row['quantity']); ?>
                <?php endif; ?>
              </td>
              <td>
                <?php if($row['quantity'] > 0): ?>
                    <span style="color: var(--ok); font-weight: 600;">Available</span>
                <?php else: ?>
                    <span style="color: var(--danger); font-weight: 600;">Out of Stock</span>
                <?php endif; ?>
              </td>
              <td><?php echo htmlspecialchars(date('M j, Y', strtotime($row['date_supplied']))); ?></td>
              <td class="actions">
                <!-- Restock Form Inline -->
                <form method="post" style="display: flex; gap: 8px; justify-content: flex-end; align-items: center; margin: 0;">
                    <input type="hidden" name="stock_id" value="<?php echo $row['stock_id']; ?>">
                    <input type="number" name="add_qty" min="1" placeholder="Qty" required style="width: 70px; padding: 6px; border: 1px solid var(--line); border-radius: 6px;">
                    <button type="submit" name="restock" class="btn" style="padding: 6px 12px; font-size: 0.85rem;">Restock</button>
                </form>
              </td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <div class="no-match" id="noMatch">No items match your search.</div>
      <?php endif; ?>
    </section>
</main>

<script>
(function () {
  // Search
  var filter = document.getElementById('filter');
  if (filter) {
    var rows = document.querySelectorAll('#inventoryTable tbody tr');
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