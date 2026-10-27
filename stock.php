<?php
session_start();
include_once('connect_db.php');

// Verify Manager Session
if(isset($_SESSION['username'])){
    $id=$_SESSION['manager_id'];
    $fname=$_SESSION['first_name'];
    $user=$_SESSION['username'];
}else{
    header("location:http://".$_SERVER['HTTP_HOST'].dirname($_SERVER['PHP_SELF'])."/index.php");
    exit();
}

$message1 = '';
$form = ['drug_name'=>'','category'=>'','description'=>'','company'=>'','supplier'=>'','quantity'=>'','cost'=>''];

// Handle Add Medicine
if(isset($_POST['submit'])){
    $sname=$_POST['drug_name'];
    $cat=$_POST['category'];
    $des=$_POST['description'];
    $com=$_POST['company'];
    $sup=$_POST['supplier'];
    $qua=$_POST['quantity'];
    $cost=$_POST['cost'];
    $sta="Available";

    // Keep form data if error occurs
    $form = [
        'drug_name'=>$sname, 'category'=>$cat, 'description'=>$des, 
        'company'=>$com, 'supplier'=>$sup, 'quantity'=>$qua, 'cost'=>$cost
    ];

    try {
        $stmt = $con->prepare("INSERT INTO stock(drug_name, category, description, company, supplier, quantity, cost, status, date_supplied)
                               VALUES(:sname, :cat, :des, :com, :sup, :qua, :cost, :sta, NOW())");
        $sql = $stmt->execute([
            ':sname' => $sname, ':cat' => $cat, ':des' => $des,
            ':com'   => $com, ':sup' => $sup, ':qua' => $qua,
            ':cost'  => $cost, ':sta' => $sta
        ]);

        if($sql) {
            header("location:http://".$_SERVER['HTTP_HOST'].dirname($_SERVER['PHP_SELF'])."/stock.php?added=1");
            exit();
        } else {
            $message1="Registration Failed. Try again.";
        }
    } catch (PDOException $e) {
        $message1="Database Error: " . htmlspecialchars($e->getMessage());
    }
}

// Fetch all stock
try {
    $result = $con->query("SELECT * FROM stock ORDER BY stock_id DESC");
    $stocks = $result->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $message1 = "Database Error: " . htmlspecialchars($e->getMessage());
    $stocks = [];
}

$hasError = ($message1 !== '');
$added = isset($_GET['added']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Manage Stock - Pharmacy Management System</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Figtree:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
<style>
:root {
  --pine: #123b36; --pine-soft: #1c524b; --pine-line: #2a5f57; --amber: #f2b544;
  --mist: #eef4f1; --paper: #ffffff; --ink: #17211f; --ink-soft: #5b6b67;
  --line: #d5e0dc; --danger: #b3261e; --danger-bg: #fdecea;
  --ok: #1e6b3a; --ok-bg: #e6f4ea; --radius: 10px; --sidebar: 260px;
  --font: "Figtree", system-ui, -apple-system, "Segoe UI", Roboto, Arial, sans-serif;
}

*, *::before, *::after { box-sizing: border-box; }
html { -webkit-text-size-adjust: 100%; }
body { margin: 0; font-family: var(--font); font-size: 16px; line-height: 1.5; color: var(--ink); background: var(--mist); }
a { color: inherit; text-decoration: none; }
:focus-visible { outline: 3px solid var(--amber); outline-offset: 2px; }

/* ---------- Shell ---------- */
.sidebar { position: fixed; inset: 0 auto 0 0; width: var(--sidebar); display: flex; flex-direction: column; background: var(--pine); color: #fff; padding: 24px 16px; z-index: 30; overflow-y: auto; }
.brand { display: flex; align-items: center; gap: 12px; padding: 0 8px 24px; margin-bottom: 8px; border-bottom: 1px solid var(--pine-line); }
.brand-mark { flex: none; width: 40px; height: 18px; border-radius: 999px; background: linear-gradient(90deg, var(--amber) 0 50%, #f7f3e8 50% 100%); transform: rotate(-24deg); }
.brand-name { font-weight: 700; line-height: 1.2; letter-spacing: -0.01em; }

.nav { list-style: none; margin: 16px 0 0; padding: 0; flex: 1; }
.nav li + li { margin-top: 2px; }
.nav a { display: flex; align-items: center; gap: 14px; padding: 11px 12px; border-radius: var(--radius); color: #c4d8d2; font-weight: 500; transition: background-color .15s, color .15s; }
.nav a i { width: 20px; text-align: center; font-size: 1rem; }
.nav a:hover { background: var(--pine-soft); color: #fff; }
.nav a[aria-current="page"] { background: #fff; color: var(--pine); }

.sidebar-foot { margin-top: 16px; padding-top: 16px; border-top: 1px solid var(--pine-line); }
.sidebar-foot a { display: flex; align-items: center; gap: 14px; padding: 11px 12px; border-radius: var(--radius); color: #c4d8d2; font-weight: 500; }
.sidebar-foot a i { width: 20px; text-align: center; }
.sidebar-foot a:hover { background: var(--pine-soft); color: #fff; }

.topbar { position: sticky; top: 0; z-index: 20; margin-left: var(--sidebar); display: flex; align-items: center; justify-content: space-between; gap: 16px; padding: 14px 40px; background: var(--paper); border-bottom: 1px solid var(--line); }
.topbar-title { font-weight: 600; }
.menu-btn { display: none; align-items: center; justify-content: center; width: 42px; height: 42px; font-size: 1.1rem; color: var(--pine); background: var(--mist); border: 0; border-radius: var(--radius); cursor: pointer; }
.user-chip { display: flex; align-items: center; gap: 10px; font-weight: 500; }
.avatar { display: grid; place-items: center; width: 36px; height: 36px; border-radius: 50%; background: var(--pine); color: #fff; font-weight: 700; }

.main { margin-left: var(--sidebar); padding: 40px; max-width: 1200px; }
.main h1 { margin: 0 0 6px; font-size: clamp(1.6rem, 2.6vw, 2.2rem); line-height: 1.15; letter-spacing: -0.02em; }
.main .lead { margin: 0 0 28px; color: var(--ink-soft); }

/* ---------- Alerts & Tabs ---------- */
.alert { margin: 0 0 20px; padding: 12px 14px; border-radius: var(--radius); font-size: 0.95rem; font-weight: 500; }
.alert--error { border: 1px solid #f0b8b3; background: var(--danger-bg); color: var(--danger); }
.alert--ok { border: 1px solid #b9dcc5; background: var(--ok-bg); color: var(--ok); }

.tabs { display: inline-flex; gap: 4px; padding: 4px; margin-bottom: 20px; background: #dfe9e5; border-radius: 12px; }
.tab { padding: 9px 18px; font: inherit; font-weight: 600; color: var(--ink-soft); background: transparent; border: 0; border-radius: 9px; cursor: pointer; transition: background-color .15s, color .15s; }
.tab:hover { color: var(--ink); }
.tab[aria-selected="true"] { background: var(--paper); color: var(--pine); }
.panel[hidden] { display: none; }

/* ---------- Table Card ---------- */
.card { background: var(--paper); border: 1px solid var(--line); border-radius: 14px; overflow: hidden; }
.card-head { display: flex; align-items: center; justify-content: space-between; gap: 16px; flex-wrap: wrap; padding: 16px 20px; border-bottom: 1px solid var(--line); }
.card-head h2 { margin: 0; font-size: 1.05rem; }
.count { color: var(--ink-soft); font-weight: 500; }

.search { position: relative; }
.search i { position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: var(--ink-soft); font-size: 0.9rem; pointer-events: none; }
.search input { width: 260px; max-width: 100%; padding: 9px 12px 9px 34px; font: inherit; font-size: 0.95rem; color: var(--ink); background: var(--mist); border: 1.5px solid transparent; border-radius: var(--radius); }
.search input:focus { outline: none; border-color: var(--pine); background: var(--paper); }

.table-wrap { overflow-x: auto; }
table { width: 100%; border-collapse: collapse; font-size: 0.95rem; }
th, td { padding: 13px 20px; text-align: left; white-space: nowrap; }
th { font-size: 0.875rem; font-weight: 600; color: var(--ink-soft); background: #f7faf9; border-bottom: 1px solid var(--line); }
td { border-bottom: 1px solid #e6eeeb; }
tbody tr:last-child td { border-bottom: 0; }
tbody tr:hover td { background: #f7faf9; }
th.actions, td.actions { text-align: right; }

.icon-btn { display: inline-grid; place-items: center; width: 36px; height: 36px; border-radius: 8px; color: var(--ink-soft); transition: background-color .15s, color .15s; }
.icon-btn--delete:hover { background: var(--danger-bg); color: var(--danger); }

.empty { padding: 48px 20px; text-align: center; color: var(--ink-soft); }
.empty p { margin: 0 0 16px; }
.no-match { display: none; padding: 32px 20px; text-align: center; color: var(--ink-soft); }

/* ---------- Form Card ---------- */
.form-card { max-width: 680px; padding: 28px; }
.form-card h2 { margin: 0 0 24px; font-size: 1.25rem; }
.grid { display: grid; grid-template-columns: 1fr 1fr; gap: 18px 16px; }
.span-2 { grid-column: 1 / -1; }
.field label { display: block; margin-bottom: 6px; font-weight: 600; font-size: 0.95rem; }
.field input { width: 100%; padding: 12px 14px; font: inherit; color: var(--ink); background: var(--paper); border: 1.5px solid var(--line); border-radius: var(--radius); transition: border-color .15s, box-shadow .15s; }
.field input:focus { outline: none; border-color: var(--pine); box-shadow: 0 0 0 3px rgba(18, 59, 54, .15); }
.form-actions { display: flex; gap: 12px; margin-top: 24px; }
.btn { display: inline-block; padding: 12px 22px; font: inherit; font-weight: 600; color: #fff; background: var(--pine); border: 0; border-radius: var(--radius); cursor: pointer; transition: background-color .15s; }
.btn:hover { background: var(--pine-soft); }
.btn--ghost { color: var(--ink); background: var(--mist); }
.btn--ghost:hover { background: #dfe9e5; }

/* ---------- Mobile ---------- */
.scrim { display: none; }
@media (max-width: 900px) {
  .sidebar { transform: translateX(-100%); transition: transform .2s ease; }
  body.nav-open .sidebar { transform: none; }
  body.nav-open .scrim { display: block; position: fixed; inset: 0; z-index: 25; background: rgba(10, 25, 22, .5); }
  .topbar { margin-left: 0; padding: 12px 20px; }
  .menu-btn { display: inline-flex; }
  .main { margin-left: 0; padding: 28px 20px 48px; }
  .grid { grid-template-columns: 1fr; }
  .form-card { padding: 20px; }
  .search, .search input { width: 100%; }
}
@media (prefers-reduced-motion: reduce) { .sidebar { transition: none; } }
</style>
</head>
<body>

  <aside class="sidebar" id="sidebar" aria-label="Main navigation">
    <div class="brand">
      <span class="brand-mark" aria-hidden="true"></span>
      <span class="brand-name">Pharmacy<br>Management</span>
    </div>
    <ul class="nav">
      <li><a href="manager.php"><i class="fas fa-home"></i>Dashboard</a></li>
      <li><a href="view.php"><i class="fas fa-users"></i>View Users</a></li>
      <li><a href="view_prescription.php"><i class="fas fa-prescription-bottle-alt"></i>View Prescription</a></li>
      <li><a href="stock.php" aria-current="page"><i class="fas fa-pills"></i>Manage Stock</a></li>
    </ul>
    <div class="sidebar-foot">
      <a href="logout.php"><i class="fas fa-power-off"></i>Log out</a>
    </div>
  </aside>
  <div class="scrim" id="scrim"></div>

  <header class="topbar">
    <button type="button" class="menu-btn" id="menuBtn" aria-label="Open menu" aria-controls="sidebar" aria-expanded="false">
      <i class="fas fa-bars"></i>
    </button>
    <span class="topbar-title">Manager Dashboard</span>
    <div class="user-chip">
      <span class="avatar" aria-hidden="true"><?php echo htmlspecialchars(strtoupper(substr($user, 0, 1))); ?></span>
      <span><?php echo htmlspecialchars($user); ?></span>
    </div>
  </header>

  <main class="main">
    <h1>Manage Stock</h1>
    <p class="lead">View and add medicines to the pharmacy inventory.</p>

    <?php if ($added): ?>
      <div class="alert alert--ok" role="status">Medicine added successfully.</div>
    <?php endif; ?>
    <?php if ($message1 !== ''): ?>
      <div class="alert alert--error" role="alert"><?php echo htmlspecialchars($message1); ?></div>
    <?php endif; ?>

    <div class="tabs" role="tablist" aria-label="Stock actions">
      <button type="button" class="tab" role="tab" id="tab_1" aria-controls="content_1"
              aria-selected="<?php echo $hasError ? 'false' : 'true'; ?>">View Stock</button>
      <button type="button" class="tab" role="tab" id="tab_2" aria-controls="content_2"
              aria-selected="<?php echo $hasError ? 'true' : 'false'; ?>">Add Medicine</button>
    </div>

    <!-- View Stock -->
    <section class="panel card" id="content_1" role="tabpanel" aria-labelledby="tab_1" <?php echo $hasError ? 'hidden' : ''; ?>>
      <div class="card-head">
        <h2>All Medicines <span class="count">(<?php echo count($stocks); ?>)</span></h2>
        <?php if (count($stocks) > 0): ?>
        <div class="search">
          <i class="fas fa-search" aria-hidden="true"></i>
          <input type="search" id="filter" placeholder="Search by name or category..." aria-label="Search stock">
        </div>
        <?php endif; ?>
      </div>

      <?php if (count($stocks) === 0): ?>
        <div class="empty">
          <p>No medicines in stock. Add the first one to get started.</p>
          <button type="button" class="btn" data-go="tab_2">Add Medicine</button>
        </div>
      <?php else: ?>
      <div class="table-wrap">
        <table id="stockTable">
          <thead>
            <tr>
              <th>ID</th>
              <th>Name</th>
              <th>Category</th>
              <th>Company</th>
              <th>Supplier</th>
              <th>Qty</th>
              <th>Unit Cost</th>
              <th>Status</th>
              <th>Date</th>
              <th class="actions">Actions</th>
            </tr>
          </thead>
          <tbody>
          <?php foreach ($stocks as $row): ?>
            <tr>
              <td class="num"><?php echo htmlspecialchars($row['stock_id']); ?></td>
              <td><strong><?php echo htmlspecialchars($row['drug_name']); ?></strong></td>
              <td><?php echo htmlspecialchars($row['category']); ?></td>
              <td><?php echo htmlspecialchars($row['company']); ?></td>
              <td><?php echo htmlspecialchars($row['supplier']); ?></td>
              <td><?php echo htmlspecialchars($row['quantity']); ?></td>
              <td><?php echo htmlspecialchars($row['cost']); ?></td>
              <td><?php echo htmlspecialchars($row['status']); ?></td>
              <td><?php echo htmlspecialchars(date('M j, Y', strtotime($row['date_supplied']))); ?></td>
              <td class="actions">
                <a class="icon-btn icon-btn--delete" title="Delete" aria-label="Delete <?php echo htmlspecialchars($row['drug_name']); ?>"
                   href="delete_stock.php?stock_id=<?php echo urlencode($row['stock_id']); ?>"
                   onclick="return confirm('Are you sure you want to delete this medicine?');"><i class="fas fa-trash"></i></a>
              </td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <div class="no-match" id="noMatch">No medicines match your search.</div>
      <?php endif; ?>
    </section>

    <!-- Add Medicine -->
    <section class="panel card form-card" id="content_2" role="tabpanel" aria-labelledby="tab_2" <?php echo $hasError ? '' : 'hidden'; ?>>
      <h2>Add a new medicine</h2>
      
      <form name="myform" action="stock.php" method="post" autocomplete="off">
        <div class="grid">
          <div class="field">
            <label for="drug_name">Drug Name</label>
            <input name="drug_name" id="drug_name" type="text" required value="<?php echo htmlspecialchars($form['drug_name']); ?>">
          </div>
          <div class="field">
            <label for="category">Category</label>
            <input name="category" id="category" type="text" required value="<?php echo htmlspecialchars($form['category']); ?>">
          </div>
          <div class="field span-2">
            <label for="description">Description</label>
            <input name="description" id="description" type="text" required value="<?php echo htmlspecialchars($form['description']); ?>">
          </div>
          <div class="field">
            <label for="company">Manufacturing Company</label>
            <input name="company" id="company" type="text" required value="<?php echo htmlspecialchars($form['company']); ?>">
          </div>
          <div class="field">
            <label for="supplier">Supplier</label>
            <input name="supplier" id="supplier" type="text" required value="<?php echo htmlspecialchars($form['supplier']); ?>">
          </div>
          <div class="field">
            <label for="quantity">Quantity</label>
            <input name="quantity" id="quantity" type="number" min="1" required value="<?php echo htmlspecialchars($form['quantity']); ?>">
          </div>
          <div class="field">
            <label for="cost">Unit Cost</label>
            <input name="cost" id="cost" type="number" step="0.01" min="0" required value="<?php echo htmlspecialchars($form['cost']); ?>">
          </div>
        </div>

        <div class="form-actions">
          <button type="submit" name="submit" class="btn">Add Medicine</button>
          <button type="button" class="btn btn--ghost" data-go="tab_1">Cancel</button>
        </div>
      </form>
    </section>
  </main>

<script>
(function () {
  // Mobile menu
  var body = document.body;
  var menuBtn = document.getElementById('menuBtn');
  var scrim = document.getElementById('scrim');
  function setNav(open) {
    body.classList.toggle('nav-open', open);
    menuBtn.setAttribute('aria-expanded', open ? 'true' : 'false');
  }
  menuBtn.addEventListener('click', function () { setNav(!body.classList.contains('nav-open')); });
  scrim.addEventListener('click', function () { setNav(false); });
  document.addEventListener('keydown', function (e) { if (e.key === 'Escape') setNav(false); });

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

  // Table search
  var filter = document.getElementById('filter');
  if (filter) {
    var rows = document.querySelectorAll('#stockTable tbody tr');
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
</body>
</html>