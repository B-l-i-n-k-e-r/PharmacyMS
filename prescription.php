<?php
session_start();
include_once('connect_db.php');

if(isset($_SESSION['username'])){
    $id=$_SESSION['pharmacist_id'];
    $fname=$_SESSION['first_name'];
    $user=$_SESSION['username'];
}else{
    header("location:http://".$_SERVER['HTTP_HOST'].dirname($_SERVER['PHP_SELF'])."/index.php");
    exit();
}

$message1 = '';

// Fetch all prescriptions
try {
    $stmt = $con->query("SELECT * FROM prescription ORDER BY date DESC");
    $prescriptions = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $message1 = "Database Error: " . htmlspecialchars($e->getMessage());
    $prescriptions = [];
}

// Fetch next invoice ID
try {
    $invNum = $con->query("SELECT 1+MAX(invoice_id) AS next_id FROM invoice");
    $invoice = $invNum->fetch(PDO::FETCH_ASSOC);
    $invoiceNo = empty($invoice['next_id']) ? 10 : $invoice['next_id'];
    $_SESSION['invoice'] = $invoiceNo;
} catch (PDOException $e) {
    $invoiceNo = 10;
}

// Fetch drugs for the dropdown
try {
    $drugsQuery = $con->query("SELECT drug_name FROM stock");
    $drugs = $drugsQuery->fetchAll(PDO::FETCH_COLUMN);
} catch (PDOException $e) {
    $drugs = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Prescriptions - Pharmacy Management System</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Figtree:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
<style>
:root {
  --pine: #123b36; --pine-soft: #1c524b; --pine-line: #2a5f57; --amber: #f2b544;
  --mist: #eef4f1; --paper: #ffffff; --ink: #17211f; --ink-soft: #5b6b67;
  --line: #d5e0dc; --danger: #b3261e; --danger-bg: #fdecea;
  --radius: 10px; --sidebar: 260px;
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

/* ---------- Tabs & Alerts ---------- */
.alert { margin: 0 0 20px; padding: 12px 14px; border-radius: var(--radius); font-size: 0.95rem; font-weight: 500; border: 1px solid #f0b8b3; background: var(--danger-bg); color: var(--danger); }

.tabs { display: inline-flex; gap: 4px; padding: 4px; margin-bottom: 20px; background: #dfe9e5; border-radius: 12px; }
.tab { padding: 9px 18px; font: inherit; font-weight: 600; color: var(--ink-soft); background: transparent; border: 0; border-radius: 9px; cursor: pointer; transition: background-color .15s, color .15s; }
.tab:hover { color: var(--ink); }
.tab[aria-selected="true"] { background: var(--paper); color: var(--pine); }
.panel[hidden] { display: none; }

/* ---------- Card & Table ---------- */
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
.field input, .field select { width: 100%; padding: 12px 14px; font: inherit; color: var(--ink); background: var(--paper); border: 1.5px solid var(--line); border-radius: var(--radius); transition: border-color .15s, box-shadow .15s; }
.field input:focus, .field select:focus { outline: none; border-color: var(--pine); box-shadow: 0 0 0 3px rgba(18, 59, 54, .15); }
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
      <li><a href="pharmacist.php"><i class="fas fa-home"></i>Dashboard</a></li>
      <li><a href="prescription.php" aria-current="page"><i class="fas fa-prescription-bottle-alt"></i>Prescription</a></li>
      <li><a href="stock_pharmacist.php"><i class="fas fa-pills"></i>Stock</a></li>
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
    <span class="topbar-title">Pharmacist Dashboard</span>
    <div class="user-chip">
      <span class="avatar" aria-hidden="true"><?php echo htmlspecialchars(strtoupper(substr($user, 0, 1))); ?></span>
      <span><?php echo htmlspecialchars($user); ?></span>
    </div>
  </header>

  <main class="main">
    <h1>Prescriptions</h1>
    <p class="lead">View patient history or create a new prescription.</p>

    <?php if ($message1 !== ''): ?>
      <div class="alert" role="alert"><?php echo htmlspecialchars($message1); ?></div>
    <?php endif; ?>

    <div class="tabs" role="tablist" aria-label="Prescription actions">
      <button type="button" class="tab" role="tab" id="tab_1" aria-controls="content_1" aria-selected="true">View Prescriptions</button>
      <button type="button" class="tab" role="tab" id="tab_2" aria-controls="content_2" aria-selected="false">Create New</button>
    </div>

    <!-- View Prescriptions -->
    <section class="panel card" id="content_1" role="tabpanel" aria-labelledby="tab_1">
      <div class="card-head">
        <h2>All Prescriptions <span class="count">(<?php echo count($prescriptions); ?>)</span></h2>
        <?php if (count($prescriptions) > 0): ?>
        <div class="search">
          <i class="fas fa-search" aria-hidden="true"></i>
          <input type="search" id="filter" placeholder="Search by customer..." aria-label="Search prescriptions">
        </div>
        <?php endif; ?>
      </div>

      <?php if (count($prescriptions) === 0): ?>
        <div class="empty">
          <p>No prescriptions found. Create the first one to get started.</p>
          <button type="button" class="btn" data-go="tab_2">Create New</button>
        </div>
      <?php else: ?>
      <div class="table-wrap">
        <table id="presTable">
          <thead>
            <tr>
              <th>Prescription No.</th>
              <th>Customer</th>
              <th>Invoice No.</th>
              <th>Date</th>
              <th class="actions">Actions</th>
            </tr>
          </thead>
          <tbody>
          <?php foreach ($prescriptions as $row): ?>
            <tr>
              <td><strong>#<?php echo htmlspecialchars($row['prescription_id']); ?></strong></td>
              <td><?php echo htmlspecialchars($row['customer_name']); ?></td>
              <td><?php echo htmlspecialchars($row['invoice_id']); ?></td>
              <td><?php echo htmlspecialchars(date('M j, Y', strtotime($row['date']))); ?></td>
              <td class="actions">
                <a class="icon-btn icon-btn--delete" title="Delete" aria-label="Delete prescription"
                   href="delete_prescription.php?prescription_id=<?php echo urlencode($row['prescription_id']); ?>"
                   onclick="return confirm('Are you sure you want to delete this prescription?');"><i class="fas fa-trash"></i></a>
              </td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <div class="no-match" id="noMatch">No prescriptions match your search.</div>
      <?php endif; ?>
    </section>

    <!-- Create New Prescription -->
    <section class="panel card form-card" id="content_2" role="tabpanel" aria-labelledby="tab_2" hidden>
      <h2>New Prescription</h2>
      <p class="lead" style="margin-bottom: 20px;">Invoice #<?php echo htmlspecialchars($invoiceNo); ?> will be generated.</p>

      <!-- Note: action points to invoice.php -->
      <form name="myform" action="invoice.php" method="post" autocomplete="off">
        <div class="grid">
          <div class="field span-2">
            <h3 style="margin: 0 0 8px; font-size: 1rem; color: var(--ink-soft);">Customer Details</h3>
          </div>
          <div class="field">
            <label for="customer_id">Customer ID</label>
            <input name="customer_id" id="customer_id" type="text" required>
          </div>
          <div class="field">
            <label for="customer_name">Customer Name</label>
            <input name="customer_name" id="customer_name" type="text" required>
          </div>
          <div class="field">
            <label for="age">Age</label>
            <input name="age" id="age" type="number" required>
          </div>
          <div class="field">
            <label for="sex">Gender</label>
            <select name="sex" id="sex" required>
              <option value="">Select...</option>
              <option value="Male">Male</option>
              <option value="Female">Female</option>
            </select>
          </div>
          <div class="field span-2">
            <label for="postal_address">Address</label>
            <input name="postal_address" id="postal_address" type="text" required>
          </div>
          <div class="field">
            <label for="phone">Phone</label>
            <input name="phone" id="phone" type="tel" required>
          </div>

          <div class="field span-2" style="margin-top: 12px;">
            <h3 style="margin: 0 0 8px; font-size: 1rem; color: var(--ink-soft);">Drug Details</h3>
          </div>
          
          <div class="field span-2">
            <label for="drug_name">Select Drug</label>
            <select name="drug_name" id="drug_name" required>
              <option value="">Select Drug...</option>
              <?php foreach ($drugs as $drug): ?>
                <option value="<?php echo htmlspecialchars($drug); ?>"><?php echo htmlspecialchars($drug); ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="field">
            <label for="strength">Strength</label>
            <input name="strength" id="strength" type="text" placeholder="e.g. 500mg">
          </div>
          <div class="field">
            <label for="dose">Dose</label>
            <input name="dose" id="dose" type="text" placeholder="e.g. 2 times a day">
          </div>
          <div class="field">
            <label for="quantity">Quantity</label>
            <input name="quantity" id="quantity" type="number" required>
          </div>
        </div>

        <div class="form-actions">
          <button type="submit" name="submit" class="btn">Create Prescription</button>
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
</body>
</html>