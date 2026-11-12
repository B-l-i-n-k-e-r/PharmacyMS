<?php
session_start();
include_once('../connect_db.php');

if (empty($_SESSION['username'])) { header('Location: index.php'); exit(); }
$user = $_SESSION['username'];
$message1 = '';

// Handle Add Product
if(isset($_POST['submit'])){
    $sname = $_POST['drug_name'];
    $cat = $_POST['category'];
    $des = $_POST['description'];
    $com = $_POST['company'];
    $sup = $_POST['supplier'];
    $cost = $_POST['cost'];

    try {
        $stmt = $con->prepare("INSERT INTO stock(drug_name, category, description, company, supplier, quantity, cost, status, date_supplied) VALUES(:sname, :cat, :des, :com, :sup, 0, :cost, 'Out of Stock', NOW())");
        $stmt->execute([':sname'=>$sname, ':cat'=>$cat, ':des'=>$des, ':com'=>$com, ':sup'=>$sup, ':cost'=>$cost]);
        header("location:manager_products.php?added=1");
        exit();
    } catch (PDOException $e) { $message1 = "Error: " . $e->getMessage(); }
}

try {
    $rows = $con->query("SELECT * FROM stock ORDER BY stock_id DESC")->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) { $rows = []; }

$page_title = "Products Catalog - MedFlow PMS";
$topbar_title = "Manager Dashboard";

include '../includes/head.php';
include '../includes/sidebar_manager.php';
include '../includes/header.php';
?>

<main class="main">
    <h1>Products Catalog</h1>
    <p class="lead">Manage your medicine catalog, pricing, and manufacturer details.</p>

    <?php if (isset($_GET['added'])): ?>
      <div class="alert alert--ok">Product added successfully!</div>
    <?php endif; ?>
    <?php if ($message1 !== ''): ?>
      <div class="alert alert--error"><?php echo htmlspecialchars($message1); ?></div>
    <?php endif; ?>

    <div class="tabs" role="tablist">
      <button type="button" class="tab" role="tab" id="tab_1" aria-controls="content_1" aria-selected="true">View Products</button>
      <button type="button" class="tab" role="tab" id="tab_2" aria-controls="content_2" aria-selected="false">Add Product</button>
    </div>

    <section class="panel card" id="content_1" role="tabpanel">
      <div class="card-head">
        <h2>All Products <span class="count">(<?php echo count($rows); ?>)</span></h2>
        <div class="search">
          <i class="fas fa-search"></i>
          <input type="search" id="filter" placeholder="Search by name...">
        </div>
      </div>
      <div class="table-wrap">
        <table id="productTable">
          <thead>
            <tr>
              <th>Drug Name</th>
              <th>Category</th>
              <th>Company</th>
              <th>Unit Cost</th>
              <th class="actions">Actions</th>
            </tr>
          </thead>
          <tbody>
          <?php foreach ($rows as $row): ?>
            <tr>
              <td><strong><?php echo htmlspecialchars($row['drug_name']); ?></strong></td>
              <td><?php echo htmlspecialchars($row['category']); ?></td>
              <td><?php echo htmlspecialchars($row['company']); ?></td>
              <td>Ksh <?php echo number_format($row['cost'], 2); ?></td>
              <td class="actions">
                 <a class="icon-btn icon-btn--delete" href="delete_stock.php?stock_id=<?php echo urlencode($row['stock_id']); ?>" onclick="return confirm('Delete this product?');"><i class="fas fa-trash"></i></a>
              </td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </section>

    <section class="panel card form-card" id="content_2" role="tabpanel" hidden>
      <h2>Add a new product</h2>
      <form action="manager_products.php" method="post">
        <div class="grid">
          <div class="field span-2">
            <label>Drug Name</label>
            <input name="drug_name" type="text" required>
          </div>
          <div class="field">
            <label>Category</label>
            <input name="category" type="text" required>
          </div>
          <div class="field">
            <label>Manufacturer</label>
            <input name="company" type="text" required>
          </div>
          <div class="field span-2">
            <label>Description</label>
            <input name="description" type="text" required>
          </div>
          <div class="field">
            <label>Supplier</label>
            <input name="supplier" type="text" required>
          </div>
          <div class="field">
            <label>Unit Cost (Ksh.)</label>
            <input name="cost" type="number" step="0.01" required>
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
})();
</script>

<?php include '../includes/footer.php'; ?>