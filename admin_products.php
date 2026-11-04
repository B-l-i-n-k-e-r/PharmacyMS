<?php
session_start();
include_once('connect_db.php');

if(!isset($_SESSION['username'])){
    header("location:index.php");
    exit();
}
$user = $_SESSION['username'];
$admin_id = $_SESSION['admin_id'];

$message = '';
$message1 = '';
$form = ['drug_name'=>'','category'=>'','description'=>'','company'=>'','supplier'=>'','cost'=>''];

// Handle Add Product
if(isset($_POST['submit'])){
    $sname = $_POST['drug_name'];
    $cat = $_POST['category'];
    $des = $_POST['description'];
    $com = $_POST['company'];
    $sup = $_POST['supplier'];
    $cost = $_POST['cost'];

    $form = ['drug_name'=>$sname, 'category'=>$cat, 'description'=>$des, 'company'=>$com, 'supplier'=>$sup, 'cost'=>$cost];

    try {
        // We set quantity to 0 here. Inventory page handles quantities.
        $stmt = $con->prepare("INSERT INTO stock(drug_name, category, description, company, supplier, quantity, cost, status, date_supplied)
                               VALUES(:sname, :cat, :des, :com, :sup, 0, :cost, 'Out of Stock', NOW())");
        $sql = $stmt->execute([
            ':sname' => $sname, ':cat' => $cat, ':des' => $des,
            ':com'   => $com, ':sup' => $sup, ':cost' => $cost
        ]);

        if($sql) {
            header("location:admin_products.php?added=1");
            exit();
        } else {
            $message1 = "Could not save the product.";
        }
    } catch (PDOException $e) {
        $message1 = "Database Error: " . htmlspecialchars($e->getMessage());
    }
}

// Fetch all products
try {
    $stmtView = $con->query("SELECT * FROM stock ORDER BY stock_id DESC");
    $rows = $stmtView->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $message1 = "Error loading products: " . htmlspecialchars($e->getMessage());
    $rows = [];
}

$hasError = ($message1 !== '');
$added = isset($_GET['added']);

// Layout Settings
$page_title = "Products Catalog - Pharmacy Management System";
$topbar_title = "Admin Dashboard";

include 'includes/head.php';
include 'includes/sidebar_admin.php';
include 'includes/header.php';
?>

<main class="main">
    <h1>Products Catalog</h1>
    <p class="lead">Manage your medicine catalog, pricing, and manufacturer details.</p>

    <?php if ($added): ?>
      <div class="alert alert--ok" role="status">Product added successfully! Go to Inventory to add stock.</div>
    <?php endif; ?>
    <?php if ($message1 !== ''): ?>
      <div class="alert alert--error" role="alert"><?php echo htmlspecialchars($message1); ?></div>
    <?php endif; ?>

    <div class="tabs" role="tablist">
      <button type="button" class="tab" role="tab" id="tab_1" aria-controls="content_1" aria-selected="<?php echo $hasError ? 'false' : 'true'; ?>">View Products</button>
      <button type="button" class="tab" role="tab" id="tab_2" aria-controls="content_2" aria-selected="<?php echo $hasError ? 'true' : 'false'; ?>">Add Product</button>
    </div>

    <!-- View Products -->
    <section class="panel card" id="content_1" role="tabpanel" <?php echo $hasError ? 'hidden' : ''; ?>>
      <div class="card-head">
        <h2>All Products <span class="count">(<?php echo count($rows); ?>)</span></h2>
        <?php if (count($rows) > 0): ?>
        <div class="search">
          <i class="fas fa-search" aria-hidden="true"></i>
          <input type="search" id="filter" placeholder="Search by name or category..." aria-label="Search products">
        </div>
        <?php endif; ?>
      </div>

      <?php if (count($rows) === 0): ?>
        <div class="empty">
          <p>No products in the catalog. Add your first product to get started.</p>
          <button type="button" class="btn" data-go="tab_2">Add Product</button>
        </div>
      <?php else: ?>
      <div class="table-wrap">
        <table id="productTable">
          <thead>
            <tr>
              <th>ID</th>
              <th>Drug Name</th>
              <th>Category</th>
              <th>Company</th>
              <th>Supplier</th>
              <th>Unit Cost</th>
              <th>Status</th>
            </tr>
          </thead>
          <tbody>
          <?php foreach ($rows as $row): ?>
            <tr>
              <td class="num"><?php echo htmlspecialchars($row['stock_id']); ?></td>
              <td><strong><?php echo htmlspecialchars($row['drug_name']); ?></strong></td>
              <td><?php echo htmlspecialchars($row['category']); ?></td>
              <td><?php echo htmlspecialchars($row['company']); ?></td>
              <td><?php echo htmlspecialchars($row['supplier']); ?></td>
              <td>$<?php echo number_format($row['cost'], 2); ?></td>
              <td>
                <?php if($row['quantity'] > 0): ?>
                    <span style="color: var(--ok); font-weight: 600;">In Stock</span>
                <?php else: ?>
                    <span style="color: var(--danger); font-weight: 600;">Out of Stock</span>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <div class="no-match" id="noMatch">No products match your search.</div>
      <?php endif; ?>
    </section>

    <!-- Add Product -->
    <section class="panel card form-card" id="content_2" role="tabpanel" <?php echo $hasError ? '' : 'hidden'; ?>>
      <h2>Add a new product</h2>
      <p class="hint">This adds the medicine to the catalog. You must set the initial quantity in the Inventory page.</p>

      <form action="admin_products.php" method="post" autocomplete="off">
        <div class="grid">
          <div class="field span-2">
            <label for="drug_name">Drug Name</label>
            <input name="drug_name" id="drug_name" type="text" required value="<?php echo htmlspecialchars($form['drug_name']); ?>">
          </div>
          <div class="field">
            <label for="category">Category</label>
            <input name="category" id="category" type="text" required value="<?php echo htmlspecialchars($form['category']); ?>">
          </div>
          <div class="field">
            <label for="company">Manufacturer</label>
            <input name="company" id="company" type="text" required value="<?php echo htmlspecialchars($form['company']); ?>">
          </div>
          <div class="field span-2">
            <label for="description">Description</label>
            <input name="description" id="description" type="text" required value="<?php echo htmlspecialchars($form['description']); ?>">
          </div>
          <div class="field">
            <label for="supplier">Supplier</label>
            <input name="supplier" id="supplier" type="text" required value="<?php echo htmlspecialchars($form['supplier']); ?>">
          </div>
          <div class="field">
            <label for="cost">Unit Cost ($)</label>
            <input name="cost" id="cost" type="number" step="0.01" min="0" required value="<?php echo htmlspecialchars($form['cost']); ?>">
          </div>
        </div>

        <div class="form-actions">
          <button type="submit" name="submit" class="btn">Add Product</button>
          <button type="button" class="btn btn--ghost" data-go="tab_1">Cancel</button>
        </div>
      </form>
    </section>
</main>

<script>
(function () {
  // Tabs
  var tabs = [document.getElementById('tab_1'), document.getElementById('tab_2')];
  function show(id) {
    tabs.forEach(function (t) {
      var on = t.id === id;
      t.setAttribute('aria-selected', on ? 'true' : 'false');
      document.getElementById(t.getAttribute('aria-controls')).hidden = !on;
    });
  }
  tabs.forEach(function (t) { t.addEventListener('click', function () { show(t.id); }); });
  document.querySelectorAll('[data-go]').forEach(function (b) {
    b.addEventListener('click', function () { show(b.getAttribute('data-go')); });
  });

  // Search
  var filter = document.getElementById('filter');
  if (filter) {
    var rows = document.querySelectorAll('#productTable tbody tr');
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