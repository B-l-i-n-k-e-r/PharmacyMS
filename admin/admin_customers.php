<?php
session_start();
include_once('../connect_db.php');

if(!isset($_SESSION['username'])){
    header("location:index.php");
    exit();
}
$user = $_SESSION['username'];

$message1 = '';
$form = ['first_name'=>'','last_name'=>'','phone'=>'','email'=>'','address'=>''];

// Handle Add Customer
if(isset($_POST['submit'])){
    $fname = $_POST['first_name'];
    $lname = $_POST['last_name'];
    $phone = $_POST['phone'];
    $email = $_POST['email'];
    $address = $_POST['address'];

    $form = ['first_name'=>$fname, 'last_name'=>$lname, 'phone'=>$phone, 'email'=>$email, 'address'=>$address];

    try {
        $stmt = $con->prepare("INSERT INTO customers(first_name, last_name, phone, email, address) VALUES(:fname, :lname, :phone, :email, :address)");
        $stmt->execute([
            ':fname' => $fname, ':lname' => $lname, ':phone' => $phone,
            ':email' => $email, ':address' => $address
        ]);
        header("location:admin_customers.php?added=1");
        exit();
    } catch (PDOException $e) {
        $message1 = "Database Error: " . htmlspecialchars($e->getMessage());
    }
}

// Fetch Customers
try {
    $stmtView = $con->query("SELECT * FROM customers ORDER BY customer_id DESC");
    $rows = $stmtView->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $message1 = "Error loading customers: " . htmlspecialchars($e->getMessage());
    $rows = [];
}

$hasError = ($message1 !== '');
$added = isset($_GET['added']);

$page_title = "Customers - Pharmacy Management System";
$topbar_title = "Admin Dashboard";

include '../includes/head.php';
include '../includes/sidebar_admin.php';
include '../includes/header.php';
?>

<main class="main">
    <h1>Customers</h1>
    <p class="lead">Manage patient profiles and contact details.</p>

    <?php if ($added): ?>
      <div class="alert alert--ok" role="status">Customer added successfully.</div>
    <?php endif; ?>
    <?php if ($message1 !== ''): ?>
      <div class="alert alert--error" role="alert"><?php echo htmlspecialchars($message1); ?></div>
    <?php endif; ?>

    <div class="tabs" role="tablist">
      <button type="button" class="tab" role="tab" id="tab_1" aria-controls="content_1" aria-selected="<?php echo $hasError ? 'false' : 'true'; ?>">View Customers</button>
      <button type="button" class="tab" role="tab" id="tab_2" aria-controls="content_2" aria-selected="<?php echo $hasError ? 'true' : 'false'; ?>">Add Customer</button>
    </div>

    <section class="panel card" id="content_1" role="tabpanel" <?php echo $hasError ? 'hidden' : ''; ?>>
      <div class="card-head">
        <h2>All Customers <span class="count">(<?php echo count($rows); ?>)</span></h2>
        <?php if (count($rows) > 0): ?>
        <div class="search">
          <i class="fas fa-search" aria-hidden="true"></i>
          <input type="search" id="filter" placeholder="Search by name or phone..." aria-label="Search customers">
        </div>
        <?php endif; ?>
      </div>

      <?php if (count($rows) === 0): ?>
        <div class="empty">
          <p>No customers found. Add your first customer.</p>
          <button type="button" class="btn" data-go="tab_2">Add Customer</button>
        </div>
      <?php else: ?>
      <div class="table-wrap">
        <table id="customerTable">
          <thead>
            <tr>
              <th>ID</th>
              <th>Name</th>
              <th>Phone</th>
              <th>Email</th>
              <th>Address</th>
              <th>Joined</th>
            </tr>
          </thead>
          <tbody>
          <?php foreach ($rows as $row): ?>
            <tr>
              <td class="num"><?php echo htmlspecialchars($row['customer_id']); ?></td>
              <td><strong><?php echo htmlspecialchars($row['first_name'] . ' ' . $row['last_name']); ?></strong></td>
              <td><?php echo htmlspecialchars($row['phone']); ?></td>
              <td><?php echo htmlspecialchars($row['email']); ?></td>
              <td><?php echo htmlspecialchars($row['address']); ?></td>
              <td><?php echo htmlspecialchars(date('M j, Y', strtotime($row['created_at']))); ?></td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <div class="no-match" id="noMatch">No customers match your search.</div>
      <?php endif; ?>
    </section>

    <section class="panel card form-card" id="content_2" role="tabpanel" <?php echo $hasError ? '' : 'hidden'; ?>>
      <h2>Add a new customer</h2>
      <form action="admin_customers.php" method="post" autocomplete="off">
        <div class="grid">
          <div class="field">
            <label for="first_name">First Name</label>
            <input name="first_name" id="first_name" type="text" required value="<?php echo htmlspecialchars($form['first_name']); ?>">
          </div>
          <div class="field">
            <label for="last_name">Last Name</label>
            <input name="last_name" id="last_name" type="text" required value="<?php echo htmlspecialchars($form['last_name']); ?>">
          </div>
          <div class="field">
            <label for="phone">Phone</label>
            <input name="phone" id="phone" type="tel" required value="<?php echo htmlspecialchars($form['phone']); ?>">
          </div>
          <div class="field">
            <label for="email">Email</label>
            <input name="email" id="email" type="email" value="<?php echo htmlspecialchars($form['email']); ?>">
          </div>
          <div class="field span-2">
            <label for="address">Address</label>
            <input name="address" id="address" type="text" value="<?php echo htmlspecialchars($form['address']); ?>">
          </div>
        </div>

        <div class="form-actions">
          <button type="submit" name="submit" class="btn">Add Customer</button>
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
    var rows = document.querySelectorAll('#customerTable tbody tr');
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