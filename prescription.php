<?php
session_start();
include_once('connect_db.php');
date_default_timezone_set('Africa/Nairobi');

if (isset($_SESSION['username'])) {
    $id    = $_SESSION['pharmacist_id'];
    $fname = $_SESSION['first_name'];
    $user  = $_SESSION['username'];
} else {
    header("location:index.php");
    exit();
}

function h($v) { return htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8'); }
function initial($s) { $c = strtoupper(substr(trim((string) $s), 0, 1)); return $c !== '' ? $c : '?'; }

$message1 = '';

// Fetch all prescriptions
$prescriptions = [];
try {
    $prescriptions = $con->query("SELECT * FROM prescription ORDER BY date DESC")->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Load prescriptions failed: " . $e->getMessage());
    $message1 = "Could not load prescriptions.";
}

// Next invoice number
try {
    $invoice   = $con->query("SELECT 1+MAX(invoice_id) AS next_id FROM invoice")->fetch(PDO::FETCH_ASSOC);
    $invoiceNo = empty($invoice['next_id']) ? 10 : $invoice['next_id'];
    $_SESSION['invoice'] = $invoiceNo;
} catch (PDOException $e) {
    $invoiceNo = 10;
}

// Drugs for the dropdown
try {
    $drugs = $con->query("SELECT drug_name FROM stock ORDER BY drug_name")->fetchAll(PDO::FETCH_COLUMN);
} catch (PDOException $e) {
    $drugs = [];
}

// Stats
$today = date('Y-m-d');
$month = date('Y-m');
$today_count = 0;
$month_count = 0;
foreach ($prescriptions as $p) {
    $ts = strtotime($p['date']);
    if (date('Y-m-d', $ts) === $today) { $today_count++; }
    if (date('Y-m', $ts) === $month)   { $month_count++; }
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
  --pine:#123b36; --pine-soft:#1c524b; --pine-line:#2a5f57; --amber:#f2b544;
  --mist:#eef4f1; --paper:#fff; --ink:#17211f; --ink-soft:#5b6b67; --line:#d5e0dc;
  --danger:#b3261e; --danger-bg:#fdecea; --ok:#1e6b3a; --ok-bg:#e6f4ea; --warn:#8a5a00; --warn-bg:#fdf1d6;
  --radius:10px; --sidebar:260px;
  --font:"Figtree", system-ui, -apple-system, "Segoe UI", Roboto, Arial, sans-serif;
}
*,*::before,*::after { box-sizing:border-box; }
html { -webkit-text-size-adjust:100%; }
body { margin:0; font-family:var(--font); font-size:16px; line-height:1.5; color:var(--ink); background:var(--mist); }
a { color:inherit; text-decoration:none; }
:focus-visible { outline:3px solid var(--amber); outline-offset:2px; }

/* Shell */
.sidebar { position:fixed; inset:0 auto 0 0; width:var(--sidebar); display:flex; flex-direction:column; background:var(--pine); color:#fff; padding:24px 16px; z-index:30; overflow-y:auto; }
.brand { display:flex; align-items:center; gap:12px; padding:0 8px 24px; margin-bottom:8px; border-bottom:1px solid var(--pine-line); }
.brand-mark { flex:none; width:40px; height:18px; border-radius:999px; background:linear-gradient(90deg,var(--amber) 0 50%,#f7f3e8 50% 100%); transform:rotate(-24deg); }
.brand-name { font-weight:700; line-height:1.2; letter-spacing:-.01em; }
.nav { list-style:none; margin:16px 0 0; padding:0; flex:1; }
.nav li + li { margin-top:2px; }
.nav-label { margin:20px 12px 6px !important; font-size:.72rem; font-weight:600; letter-spacing:.08em; text-transform:uppercase; color:#7fa59b; }
.nav a { display:flex; align-items:center; gap:14px; padding:11px 12px; border-radius:var(--radius); color:#c4d8d2; font-weight:500; transition:background-color .15s,color .15s; }
.nav a i { width:20px; text-align:center; }
.nav a:hover { background:var(--pine-soft); color:#fff; }
.nav a[aria-current="page"] { background:#fff; color:var(--pine); }
.sidebar-foot { margin-top:16px; padding-top:16px; border-top:1px solid var(--pine-line); }
.sidebar-foot a { display:flex; align-items:center; gap:14px; padding:11px 12px; border-radius:var(--radius); color:#c4d8d2; font-weight:500; }
.sidebar-foot a i { width:20px; text-align:center; }
.sidebar-foot a:hover { background:var(--pine-soft); color:#fff; }
.topbar { position:sticky; top:0; z-index:20; margin-left:var(--sidebar); display:flex; align-items:center; justify-content:space-between; gap:16px; padding:14px 40px; background:rgba(255,255,255,.92); backdrop-filter:blur(8px); border-bottom:1px solid var(--line); }
.topbar-title { font-weight:600; }
.menu-btn { display:none; align-items:center; justify-content:center; width:42px; height:42px; font-size:1.1rem; color:var(--pine); background:var(--mist); border:0; border-radius:var(--radius); cursor:pointer; }
.user-chip { display:flex; align-items:center; gap:10px; font-weight:500; }
.avatar { display:grid; place-items:center; width:36px; height:36px; border-radius:50%; background:var(--pine); color:#fff; font-weight:700; }
.main { margin-left:var(--sidebar); padding:40px; max-width:1240px; }

/* Page header + stats */
.page-head { margin-bottom:24px; }
.page-head h1 { margin:0 0 4px; font-size:clamp(1.6rem,2.6vw,2.1rem); line-height:1.15; letter-spacing:-.02em; }
.page-head p { margin:0; color:var(--ink-soft); }
.stats { display:grid; grid-template-columns:repeat(auto-fit,minmax(210px,1fr)); gap:16px; margin-bottom:26px; }
.stat { display:flex; align-items:center; gap:16px; padding:18px 20px; background:var(--paper); border:1px solid var(--line); border-radius:16px; }
.stat-icon { flex:none; display:grid; place-items:center; width:46px; height:46px; border-radius:14px; background:var(--mist); color:var(--pine); font-size:1.1rem; }
.stat-icon--amber { background:var(--warn-bg); color:var(--warn); }
.stat-icon--green { background:var(--ok-bg); color:var(--ok); }
.stat-label { font-size:.75rem; font-weight:600; letter-spacing:.05em; text-transform:uppercase; color:var(--ink-soft); }
.stat-value { font-size:1.5rem; font-weight:700; line-height:1.2; }

/* Alerts + tabs */
.alert { margin:0 0 20px; padding:12px 14px; border-radius:var(--radius); font-size:.95rem; font-weight:500; border:1px solid #f0b8b3; background:var(--danger-bg); color:var(--danger); }
.tabs { display:inline-flex; gap:4px; padding:4px; margin-bottom:20px; background:#dfe9e5; border-radius:12px; }
.tab { padding:9px 18px; font:inherit; font-weight:600; color:var(--ink-soft); background:transparent; border:0; border-radius:9px; cursor:pointer; transition:background-color .15s,color .15s; }
.tab:hover { color:var(--ink); }
.tab[aria-selected="true"] { background:var(--paper); color:var(--pine); box-shadow:0 1px 3px rgba(18,59,54,.12); }
.panel[hidden] { display:none; }

/* Card + table */
.card { background:var(--paper); border:1px solid var(--line); border-radius:16px; overflow:hidden; }
.card-head { display:flex; align-items:center; justify-content:space-between; gap:16px; flex-wrap:wrap; padding:16px 20px; border-bottom:1px solid var(--line); }
.card-head h2 { margin:0; font-size:1.05rem; }
.count { color:var(--ink-soft); font-weight:500; }
.search { position:relative; }
.search i { position:absolute; left:12px; top:50%; transform:translateY(-50%); color:var(--ink-soft); font-size:.9rem; pointer-events:none; }
.search input { width:260px; max-width:100%; padding:9px 12px 9px 34px; font:inherit; font-size:.95rem; color:var(--ink); background:var(--mist); border:1.5px solid transparent; border-radius:var(--radius); }
.search input:focus { outline:none; border-color:var(--pine); background:var(--paper); }
.table-wrap { overflow-x:auto; }
table { width:100%; border-collapse:collapse; font-size:.95rem; }
th, td { padding:14px 20px; text-align:left; white-space:nowrap; }
th { font-size:.78rem; font-weight:600; letter-spacing:.04em; text-transform:uppercase; color:var(--ink-soft); background:#f7faf9; border-bottom:1px solid var(--line); }
td { border-bottom:1px solid #e6eeeb; vertical-align:middle; }
tbody tr:last-child td { border-bottom:0; }
tbody tr:hover td { background:#f7faf9; }
th.actions, td.actions { text-align:right; }
.person { display:flex; align-items:center; gap:12px; }
.mini-avatar { flex:none; display:grid; place-items:center; width:36px; height:36px; border-radius:50%; background:var(--mist); color:var(--pine); font-weight:700; font-size:.85rem; }
.pill { display:inline-block; padding:3px 10px; border-radius:999px; background:var(--mist); font-size:.82rem; font-weight:600; }
.pill--today { background:var(--ok-bg); color:var(--ok); margin-left:8px; }
.icon-btn { display:inline-grid; place-items:center; width:36px; height:36px; border-radius:8px; color:var(--ink-soft); transition:background-color .15s,color .15s; }
.icon-btn--delete:hover { background:var(--danger-bg); color:var(--danger); }
.empty { padding:56px 20px; text-align:center; color:var(--ink-soft); }
.empty i { font-size:2rem; color:var(--line); margin-bottom:12px; display:block; }
.empty p { margin:0 0 16px; }
.no-match { display:none; padding:32px 20px; text-align:center; color:var(--ink-soft); }

/* Form */
.form-card { max-width:760px; padding:28px; }
.form-head { display:flex; align-items:center; justify-content:space-between; gap:12px; flex-wrap:wrap; margin-bottom:22px; }
.form-head h2 { margin:0; font-size:1.25rem; }
.invoice-chip { display:inline-flex; align-items:center; gap:8px; padding:6px 14px; border-radius:999px; background:var(--warn-bg); color:var(--warn); font-weight:600; font-size:.88rem; }
.form-section { display:flex; align-items:center; gap:10px; margin:6px 0 0; font-size:.95rem; font-weight:700; color:var(--pine); }
.form-section i { display:grid; place-items:center; width:30px; height:30px; border-radius:9px; background:var(--mist); font-size:.85rem; }
.form-section + .grid { margin-top:14px; }
.grid { display:grid; grid-template-columns:1fr 1fr; gap:18px 16px; }
.divider { height:1px; background:var(--line); margin:26px 0 20px; }
.span-2 { grid-column:1 / -1; }
.field label { display:block; margin-bottom:6px; font-weight:600; font-size:.92rem; }
.field input, .field select { width:100%; padding:12px 14px; font:inherit; color:var(--ink); background:var(--paper); border:1.5px solid var(--line); border-radius:var(--radius); transition:border-color .15s,box-shadow .15s; }
.field input:focus, .field select:focus { outline:none; border-color:var(--pine); box-shadow:0 0 0 3px rgba(18,59,54,.15); }
.hint { margin:6px 0 0; font-size:.82rem; color:var(--warn); }
.form-actions { display:flex; gap:12px; margin-top:26px; }
.btn { display:inline-flex; align-items:center; gap:8px; padding:12px 22px; font:inherit; font-weight:600; color:#fff; background:var(--pine); border:0; border-radius:var(--radius); cursor:pointer; transition:background-color .15s; }
.btn:hover { background:var(--pine-soft); }
.btn--ghost { color:var(--ink); background:var(--mist); }
.btn--ghost:hover { background:#dfe9e5; }

/* Mobile */
.scrim { display:none; }
@media (max-width:900px) {
  .sidebar { transform:translateX(-100%); transition:transform .2s ease; }
  body.nav-open .sidebar { transform:none; }
  body.nav-open .scrim { display:block; position:fixed; inset:0; z-index:25; background:rgba(10,25,22,.5); }
  .topbar { margin-left:0; padding:12px 20px; }
  .menu-btn { display:inline-flex; }
  .main { margin-left:0; padding:24px 20px 48px; }
  .grid { grid-template-columns:1fr; }
  .form-card { padding:20px; }
  .search, .search input { width:100%; }
}
@media (prefers-reduced-motion:reduce) { .sidebar { transition:none; } }
</style>
</head>
<body>

  <aside class="sidebar" id="sidebar" aria-label="Main navigation">
    <div class="brand">
      <span class="brand-mark" aria-hidden="true"></span>
      <span class="brand-name">MedFlow<br>PMS</span>
    </div>
    <ul class="nav">
      <li><a href="pharmacist.php"><i class="fas fa-home"></i>Dashboard</a></li>
      <li class="nav-label">Pharmacy</li>
      <li><a href="prescription.php" aria-current="page"><i class="fas fa-prescription-bottle-alt"></i>Prescription</a></li>
      <li><a href="stock_pharmacist.php"><i class="fas fa-pills"></i>Stock</a></li>
      <li><a href="pharmacist_customers.php"><i class="fas fa-users"></i>Customers</a></li>
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
      <span class="avatar" aria-hidden="true"><?php echo h(strtoupper(substr($user, 0, 1))); ?></span>
      <span><?php echo h($user); ?></span>
    </div>
  </header>

  <main class="main">
    <div class="page-head">
      <h1>Prescriptions</h1>
      <p>View patient history or create a new prescription.</p>
    </div>

    <?php if ($message1 !== ''): ?>
      <div class="alert" role="alert"><?php echo h($message1); ?></div>
    <?php endif; ?>

    <!-- Stats -->
    <section class="stats">
      <div class="stat">
        <span class="stat-icon"><i class="fas fa-file-prescription"></i></span>
        <div>
          <div class="stat-label">Total Prescriptions</div>
          <div class="stat-value"><?php echo number_format(count($prescriptions)); ?></div>
        </div>
      </div>
      <div class="stat">
        <span class="stat-icon stat-icon--green"><i class="fas fa-calendar-day"></i></span>
        <div>
          <div class="stat-label">Today</div>
          <div class="stat-value"><?php echo number_format($today_count); ?></div>
        </div>
      </div>
      <div class="stat">
        <span class="stat-icon stat-icon--amber"><i class="fas fa-calendar-alt"></i></span>
        <div>
          <div class="stat-label">This Month</div>
          <div class="stat-value"><?php echo number_format($month_count); ?></div>
        </div>
      </div>
      <div class="stat">
        <span class="stat-icon"><i class="fas fa-hashtag"></i></span>
        <div>
          <div class="stat-label">Next Invoice</div>
          <div class="stat-value">#<?php echo h($invoiceNo); ?></div>
        </div>
      </div>
    </section>

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
          <input type="search" id="filter" placeholder="Search customer, number, invoice..." aria-label="Search prescriptions">
        </div>
        <?php endif; ?>
      </div>

      <?php if (count($prescriptions) === 0): ?>
        <div class="empty">
          <i class="fas fa-file-medical"></i>
          <p>No prescriptions found. Create the first one to get started.</p>
          <button type="button" class="btn" data-go="tab_2"><i class="fas fa-plus"></i> Create New</button>
        </div>
      <?php else: ?>
      <div class="table-wrap">
        <table id="presTable">
          <thead>
            <tr>
              <th>Prescription</th>
              <th>Customer</th>
              <th>Invoice</th>
              <th>Date</th>
              <th class="actions">Actions</th>
            </tr>
          </thead>
          <tbody>
          <?php foreach ($prescriptions as $row):
              $ts = strtotime($row['date']);
              $isToday = (date('Y-m-d', $ts) === $today);
          ?>
            <tr>
              <td><strong>#<?php echo h($row['prescription_id']); ?></strong></td>
              <td>
                <div class="person">
                  <span class="mini-avatar" aria-hidden="true"><?php echo h(initial($row['customer_name'])); ?></span>
                  <?php echo h($row['customer_name']); ?>
                </div>
              </td>
              <td><span class="pill">#<?php echo h($row['invoice_id']); ?></span></td>
              <td>
                <?php echo h(date('M j, Y', $ts)); ?>
                <?php if ($isToday): ?><span class="pill pill--today">Today</span><?php endif; ?>
              </td>
              <td class="actions">
                <a class="icon-btn icon-btn--delete" title="Delete" aria-label="Delete prescription <?php echo h($row['prescription_id']); ?>"
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
      <div class="form-head">
        <h2>New Prescription</h2>
        <span class="invoice-chip"><i class="fas fa-file-invoice"></i> Invoice #<?php echo h($invoiceNo); ?> will be generated</span>
      </div>

      <form name="myform" action="invoice.php" method="post" autocomplete="off">
        <div class="form-section"><i class="fas fa-user"></i> Customer Details</div>
        <div class="grid">
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
            <input name="age" id="age" type="number" min="0" max="130" required>
          </div>
          <div class="field">
            <label for="sex">Gender</label>
            <select name="sex" id="sex" required>
              <option value="">Select...</option>
              <option value="Male">Male</option>
              <option value="Female">Female</option>
            </select>
          </div>
          <div class="field">
            <label for="postal_address">Address</label>
            <input name="postal_address" id="postal_address" type="text" required>
          </div>
          <div class="field">
            <label for="phone">Phone</label>
            <input name="phone" id="phone" type="tel" required>
          </div>
        </div>

        <div class="divider"></div>

        <div class="form-section"><i class="fas fa-pills"></i> Drug Details</div>
        <div class="grid">
          <div class="field span-2">
            <label for="drug_name">Select Drug</label>
            <select name="drug_name" id="drug_name" required>
              <option value="">Select Drug...</option>
              <?php foreach ($drugs as $drug): ?>
                <option value="<?php echo h($drug); ?>"><?php echo h($drug); ?></option>
              <?php endforeach; ?>
            </select>
            <?php if (empty($drugs)): ?>
              <p class="hint"><i class="fas fa-exclamation-circle"></i> No medicines in stock yet. Add some on the Stock page first.</p>
            <?php endif; ?>
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
            <input name="quantity" id="quantity" type="number" min="1" step="1" required>
          </div>
        </div>

        <div class="form-actions">
          <button type="submit" name="submit" class="btn"><i class="fas fa-check"></i> Create Prescription</button>
          <button type="button" class="btn btn--ghost" data-go="tab_1">Cancel</button>
        </div>
      </form>
    </section>
  </main>

<script>
(function () {
  // Mobile menu
  var body = document.body, menuBtn = document.getElementById('menuBtn'), scrim = document.getElementById('scrim');
  function setNav(open) { body.classList.toggle('nav-open', open); menuBtn.setAttribute('aria-expanded', open ? 'true' : 'false'); }
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
      var q = filter.value.trim().toLowerCase(), shown = 0;
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