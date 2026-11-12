<?php
session_start();
include_once('../connect_db.php');

if(!isset($_SESSION['username'])){
    header("location:index.php");
    exit();
}
$user = $_SESSION['username'];

$message1 = '';
$form = ['company_name'=>'','contact_name'=>'','phone'=>'','email'=>'','address'=>''];

// Handle Add Supplier
if(isset($_POST['submit'])){
    $company = $_POST['company_name'];
    $contact = $_POST['contact_name'];
    $phone = $_POST['phone'];
    $email = $_POST['email'];
    $address = $_POST['address'];

    $form = ['company_name'=>$company, 'contact_name'=>$contact, 'phone'=>$phone, 'email'=>$email, 'address'=>$address];

    try {
        $stmt = $con->prepare("INSERT INTO suppliers(company_name, contact_name, phone, email, address) VALUES(:company, :contact, :phone, :email, :address)");
        $stmt->execute([
            ':company' => $company, ':contact' => $contact, ':phone' => $phone,
            ':email' => $email, ':address' => $address
        ]);
        header("location:admin_suppliers.php?added=1");
        exit();
    } catch (PDOException $e) {
        $message1 = "Database Error: " . htmlspecialchars($e->getMessage());
    }
}

// Fetch Suppliers
try {
    $stmtView = $con->query("SELECT * FROM suppliers ORDER BY supplier_id DESC");
    $rows = $stmtView->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $message1 = "Error loading suppliers: " . htmlspecialchars($e->getMessage());
    $rows = [];
}

$hasError = ($message1 !== '');
$added = isset($_GET['added']);

$page_title = "Suppliers - Pharmacy Management System";
$topbar_title = "Admin Dashboard";

include '../includes/head.php';
include '../includes/sidebar_admin.php';
include '../includes/header.php';
?>

<main class="main">
    <h1>Suppliers</h1>
    <p class="lead">Manage your relationships with medicine manufacturers and distributors.</p>

    <?php if ($added): ?>
      <div class="alert alert--ok" role="status">Supplier added successfully.</div>
    <?php endif; ?>
    <?php if ($message1 !== ''): ?>
      <div class="alert alert--error" role="alert"><?php echo htmlspecialchars($message1); ?></div>
    <?php endif; ?>

    <div class="tabs" role="tablist">
      <button type="button" class="tab" role="tab" id="tab_1" aria-controls="content_1" aria-selected="<?php echo $hasError ? 'false' : 'true'; ?>">View Suppliers</button>
      <button type="button" class="tab" role="tab" id="tab_2" aria-controls="content_2" aria-selected="<?php echo $hasError ? 'true' : 'false'; ?>">Add Supplier</button>
    </div>

    <section class="panel card" id="content_1" role="tabpanel" <?php echo $hasError ? 'hidden' : ''; ?>>
      <div class="card-head">
        <h2>All Suppliers <span class="count">(<?php echo count($rows); ?>)</span></h2>
        <?php if (count($rows) > 0): ?>
        <div class="search">
          <i class="fas fa-search" aria-hidden="true"></i>
          <input type="search" id="filter" placeholder="Search by company..." aria-label="Search suppliers">
        </div>
        <?php endif; ?>
      </div>

      <?php if (count($rows) === 0): ?>
        <div class="empty">
          <p>No suppliers found. Add your first supplier.</p>
          <button type="button" class="btn" data-go="tab_2">Add Supplier</button>
        </div>
      <?php else: ?>
      <div class="table-wrap">
        <table id="supplierTable">
          <thead>
            <tr>
              <th>ID</th>
              <th>Company Name</th>
              <th>Contact Person</th>
              <th>Phone</th>
              <th>Email</th>
            </tr>
          </thead>
          <tbody>
          <?php foreach ($rows as $row): ?>
            <tr>
              <td class="num"><?php echo htmlspecialchars($row['supplier_id']); ?></td>
              <td><strong><?php echo htmlspecialchars($row['company_name']); ?></strong></td>
              <td><?php echo htmlspecialchars($row['contact_name']); ?></td>
              <td><?php echo htmlspecialchars($row['phone']); ?></td>
              <td><?php echo htmlspecialchars($row['email']); ?></td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <div class="no-match" id="noMatch">No suppliers match your search.</div>
      <?php endif; ?>
    </section>

    <section class="panel card form-card" id="content_2" role="tabpanel" <?php echo $hasError ? '' : 'hidden'; ?>>
      <h2>Add a new supplier</h2>
      <form action="admin_suppliers.php" method="post" autocomplete="off">
        <div class="grid">
          <div class="field span-2">
            <label for="company_name">Company Name</label>
            <input name="company_name" id="company_name" type="text" required value="<?php echo htmlspecialchars($form['company_name']); ?>">
          </div>
          <div class="field">
            <label for="contact_name">Contact Person</label>
            <input name="contact_name" id="contact_name" type="text" required value="<?php echo htmlspecialchars($form['contact_name']); ?>">
          </div>
          <div class="field">
            <label for="phone">Phone</label>
            <input name="phone" id="phone" type="tel" required value="<?php echo htmlspecialchars($form['phone']); ?>">
          </div>
          <div class="field span-2">
            <label for="email">Email</label>
            <input name="email" id="email" type="email" value="<?php echo htmlspecialchars($form['email']); ?>">
          </div>
          <div class="field span-2">
            <label for="address">Address</label>
            <input name="address" id="address" type="text" value="<?php echo htmlspecialchars($form['address']); ?>">
          </div>
        </div>

        <div class="form-actions">
          <button type="submit" name="submit" class="btn">Add Supplier</button>
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

  var filter = document.getElementById('filter');
  if (filter) {
    var rows = document.querySelectorAll('#supplierTable tbody tr');
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