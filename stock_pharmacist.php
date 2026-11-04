<?php
session_start();
include_once('connect_db.php');
date_default_timezone_set('Africa/Nairobi');

if (isset($_SESSION['username'])) {
    $id   = $_SESSION['pharmacist_id'];
    $user = $_SESSION['username'];
} else {
    header("location:index.php");
    exit();
}

function h($v) { return htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8'); }

$currency      = 'KES';
$low_threshold = 10;   // quantity at or below this counts as "Low stock"
$message1      = '';
$form = ['drug_name'=>'','category'=>'','description'=>'','company'=>'','supplier'=>'','quantity'=>'','cost'=>''];

// ---------- Add medicine ----------
if (isset($_POST['submit'])) {
    foreach ($form as $k => $v) {
        $form[$k] = trim($_POST[$k] ?? '');
    }

    if (in_array('', $form, true)) {
        $message1 = "Please fill in every field.";
    } elseif (!ctype_digit($form['quantity']) || (int) $form['quantity'] < 1) {
        $message1 = "Quantity must be a whole number of 1 or more.";
    } elseif (!is_numeric($form['cost']) || $form['cost'] < 0) {
        $message1 = "Unit cost must be zero or more.";
    } else {
        try {
            $stmt = $con->prepare(
                "INSERT INTO stock (drug_name, category, description, company, supplier, quantity, cost, status, date_supplied)
                 VALUES (:sname, :cat, :des, :com, :sup, :qua, :cost, :sta, NOW())"
            );
            $stmt->execute([
                ':sname' => $form['drug_name'], ':cat' => $form['category'], ':des' => $form['description'],
                ':com'   => $form['company'],   ':sup' => $form['supplier'], ':qua' => (int) $form['quantity'],
                ':cost'  => $form['cost'],      ':sta' => 'Available'
            ]);
            header("location:stock_pharmacist.php?added=1");
            exit();
        } catch (PDOException $e) {
            error_log("Add stock failed: " . $e->getMessage());
            $message1 = "Could not save the medicine. Please try again.";
        }
    }
}

// ---------- Fetch stock ----------
$stocks = [];
try {
    $stocks = $con->query("SELECT * FROM stock ORDER BY stock_id DESC")->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Load stock failed: " . $e->getMessage());
    $message1 = $message1 ?: "Could not load the stock list.";
}

// ---------- Stats ----------
$total_units = 0; $low = 0; $out = 0; $value = 0;
foreach ($stocks as $i => $s) {
    $q = (int) $s['quantity'];
    $total_units += max($q, 0);
    $value       += max($q, 0) * (float) $s['cost'];
    if ($q <= 0)                  { $state = 'out'; $out++; }
    elseif ($q <= $low_threshold) { $state = 'low'; $low++; }
    else                          { $state = 'ok'; }
    $stocks[$i]['_state'] = $state;
}
$ok_count = count($stocks) - $low - $out;

$hasError = ($message1 !== '');
$added    = isset($_GET['added']);
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
.stat-icon--red { background:var(--danger-bg); color:var(--danger); }
.stat-icon--green { background:var(--ok-bg); color:var(--ok); }
.stat-label { font-size:.75rem; font-weight:600; letter-spacing:.05em; text-transform:uppercase; color:var(--ink-soft); }
.stat-value { font-size:1.5rem; font-weight:700; line-height:1.2; }
.stat-value small { font-size:.9rem; font-weight:600; color:var(--ink-soft); }

/* Alerts + tabs */
.alert { margin:0 0 20px; padding:12px 14px; border-radius:var(--radius); font-size:.95rem; font-weight:500; }
.alert--error { border:1px solid #f0b8b3; background:var(--danger-bg); color:var(--danger); }
.alert--ok { border:1px solid #b9dcc5; background:var(--ok-bg); color:var(--ok); }
.tabs { display:inline-flex; gap:4px; padding:4px; margin-bottom:20px; background:#dfe9e5; border-radius:12px; }
.tab { padding:9px 18px; font:inherit; font-weight:600; color:var(--ink-soft); background:transparent; border:0; border-radius:9px; cursor:pointer; transition:background-color .15s,color .15s; }
.tab:hover { color:var(--ink); }
.tab[aria-selected="true"] { background:var(--paper); color:var(--pine); box-shadow:0 1px 3px rgba(18,59,54,.12); }
.panel[hidden] { display:none; }

/* Card, filters, table */
.card { background:var(--paper); border:1px solid var(--line); border-radius:16px; overflow:hidden; }
.card-head { display:flex; align-items:center; justify-content:space-between; gap:16px; flex-wrap:wrap; padding:16px 20px; border-bottom:1px solid var(--line); }
.card-head h2 { margin:0; font-size:1.05rem; }
.count { color:var(--ink-soft); font-weight:500; }
.tools { display:flex; align-items:center; gap:12px; flex-wrap:wrap; }
.chips { display:flex; gap:8px; flex-wrap:wrap; }
.chip { padding:6px 14px; font:inherit; font-size:.86rem; font-weight:600; color:var(--ink-soft); background:var(--paper); border:1.5px solid var(--line); border-radius:999px; cursor:pointer; transition:all .15s; }
.chip:hover { border-color:var(--pine); color:var(--pine); }
.chip[aria-pressed="true"] { background:var(--pine); border-color:var(--pine); color:#fff; }
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
.sub { display:block; font-size:.8rem; color:var(--ink-soft); }
.qty { font-weight:700; }
.qty--low { color:var(--warn); }
.qty--out { color:var(--danger); }
.badge { display:inline-flex; align-items:center; gap:6px; padding:4px 10px; border-radius:999px; font-size:.8rem; font-weight:600; }
.badge::before { content:""; width:6px; height:6px; border-radius:50%; background:currentColor; }
.badge--ok { background:var(--ok-bg); color:var(--ok); }
.badge--warn { background:var(--warn-bg); color:var(--warn); }
.badge--bad { background:var(--danger-bg); color:var(--danger); }
.icon-btn { display:inline-grid; place-items:center; width:36px; height:36px; border-radius:8px; color:var(--ink-soft); transition:background-color .15s,color .15s; }
.icon-btn--delete:hover { background:var(--danger-bg); color:var(--danger); }
.empty { padding:56px 20px; text-align:center; color:var(--ink-soft); }
.empty i { font-size:2rem; color:var(--line); margin-bottom:12px; display:block; }
.empty p { margin:0 0 16px; }
.no-match { display:none; padding:32px 20px; text-align:center; color:var(--ink-soft); }

/* Form */
.form-card { max-width:760px; padding:28px; }
.form-card h2 { margin:0 0 4px; font-size:1.25rem; }
.form-sub { margin:0 0 22px; color:var(--ink-soft); font-size:.92rem; }
.grid { display:grid; grid-template-columns:1fr 1fr; gap:18px 16px; }
.span-2 { grid-column:1 / -1; }
.field label { display:block; margin-bottom:6px; font-weight:600; font-size:.92rem; }
.field input, .field select { width:100%; padding:12px 14px; font:inherit; color:var(--ink); background:var(--paper); border:1.5px solid var(--line); border-radius:var(--radius); transition:border-color .15s,box-shadow .15s; }
.field input:focus, .field select:focus { outline:none; border-color:var(--pine); box-shadow:0 0 0 3px rgba(18,59,54,.15); }
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
      <li><a href="prescription.php"><i class="fas fa-prescription-bottle-alt"></i>Prescription</a></li>
      <li><a href="stock_pharmacist.php" aria-current="page"><i class="fas fa-pills"></i>Stock</a></li>
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
      <h1>Manage Stock</h1>
      <p>View and add medicines to the pharmacy inventory.</p>
    </div>

    <?php if ($added): ?>
      <div class="alert alert--ok" role="status">Medicine added successfully.</div>
    <?php endif; ?>
    <?php if ($message1 !== ''): ?>
      <div class="alert alert--error" role="alert"><?php echo h($message1); ?></div>
    <?php endif; ?>

    <!-- Stats -->
    <section class="stats">
      <div class="stat">
        <span class="stat-icon"><i class="fas fa-pills"></i></span>
        <div>
          <div class="stat-label">Medicines</div>
          <div class="stat-value"><?php echo number_format(count($stocks)); ?></div>
        </div>
      </div>
      <div class="stat">
        <span class="stat-icon stat-icon--amber"><i class="fas fa-exclamation-triangle"></i></span>
        <div>
          <div class="stat-label">Low Stock</div>
          <div class="stat-value"><?php echo number_format($low); ?></div>
        </div>
      </div>
      <div class="stat">
        <span class="stat-icon stat-icon--red"><i class="fas fa-ban"></i></span>
        <div>
          <div class="stat-label">Out of Stock</div>
          <div class="stat-value"><?php echo number_format($out); ?></div>
        </div>
      </div>
      <div class="stat">
        <span class="stat-icon stat-icon--green"><i class="fas fa-coins"></i></span>
        <div>
          <div class="stat-label">Stock Value</div>
          <div class="stat-value"><small><?php echo h($currency); ?></small> <?php echo number_format($value); ?></div>
        </div>
      </div>
    </section>

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
        <div class="tools">
          <div class="chips" role="group" aria-label="Filter by stock level">
            <button type="button" class="chip" data-state="all" aria-pressed="true">All</button>
            <button type="button" class="chip" data-state="low" aria-pressed="false">Low (<?php echo $low; ?>)</button>
            <button type="button" class="chip" data-state="out" aria-pressed="false">Out (<?php echo $out; ?>)</button>
          </div>
          <div class="search">
            <i class="fas fa-search" aria-hidden="true"></i>
            <input type="search" id="filter" placeholder="Search name, category, supplier..." aria-label="Search stock">
          </div>
        </div>
        <?php endif; ?>
      </div>

      <?php if (count($stocks) === 0): ?>
        <div class="empty">
          <i class="fas fa-box-open"></i>
          <p>No medicines in stock. Add the first one to get started.</p>
          <button type="button" class="btn" data-go="tab_2"><i class="fas fa-plus"></i> Add Medicine</button>
        </div>
      <?php else: ?>
      <div class="table-wrap">
        <table id="stockTable">
          <thead>
            <tr>
              <th>ID</th>
              <th>Medicine</th>
              <th>Supplier</th>
              <th>Qty</th>
              <th>Unit Cost</th>
              <th>Status</th>
              <th>Date Added</th>
              <th class="actions">Actions</th>
            </tr>
          </thead>
          <tbody>
          <?php foreach ($stocks as $row):
              $st = $row['_state'];
              $badge = $st === 'out' ? ['badge--bad', 'Out of stock'] : ($st === 'low' ? ['badge--warn', 'Low stock'] : ['badge--ok', 'Available']);
          ?>
            <tr data-state="<?php echo $st; ?>">
              <td class="muted">#<?php echo h($row['stock_id']); ?></td>
              <td>
                <strong><?php echo h($row['drug_name']); ?></strong>
                <span class="sub"><?php echo h($row['category']); ?></span>
              </td>
              <td>
                <?php echo h($row['supplier']); ?>
                <span class="sub"><?php echo h($row['company']); ?></span>
              </td>
              <td><span class="qty <?php echo $st === 'out' ? 'qty--out' : ($st === 'low' ? 'qty--low' : ''); ?>"><?php echo number_format((int) $row['quantity']); ?></span></td>
              <td><?php echo h($currency); ?> <?php echo number_format((float) $row['cost'], 2); ?></td>
              <td><span class="badge <?php echo $badge[0]; ?>"><?php echo $badge[1]; ?></span></td>
              <td><?php echo h(date('M j, Y', strtotime($row['date_supplied']))); ?></td>
              <td class="actions">
                <a class="icon-btn icon-btn--delete" title="Delete" aria-label="Delete <?php echo h($row['drug_name']); ?>"
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
      <p class="form-sub">Stock below <?php echo (int) $low_threshold; ?> units is flagged as low.</p>

      <form name="myform" action="stock_pharmacist.php" method="post" autocomplete="off">
        <div class="grid">
          <div class="field">
            <label for="drug_name">Drug Name</label>
            <input name="drug_name" id="drug_name" type="text" required value="<?php echo h($form['drug_name']); ?>">
          </div>
          <div class="field">
            <label for="category">Category</label>
            <input name="category" id="category" type="text" required value="<?php echo h($form['category']); ?>">
          </div>
          <div class="field span-2">
            <label for="description">Description</label>
            <input name="description" id="description" type="text" required value="<?php echo h($form['description']); ?>">
          </div>
          <div class="field">
            <label for="company">Manufacturing Company</label>
            <input name="company" id="company" type="text" required value="<?php echo h($form['company']); ?>">
          </div>
          <div class="field">
            <label for="supplier">Supplier</label>
            <input name="supplier" id="supplier" type="text" required value="<?php echo h($form['supplier']); ?>">
          </div>
          <div class="field">
            <label for="quantity">Quantity</label>
            <input name="quantity" id="quantity" type="number" min="1" step="1" required value="<?php echo h($form['quantity']); ?>">
          </div>
          <div class="field">
            <label for="cost">Unit Cost (<?php echo h($currency); ?>)</label>
            <input name="cost" id="cost" type="number" step="0.01" min="0" required value="<?php echo h($form['cost']); ?>">
          </div>
        </div>

        <div class="form-actions">
          <button type="submit" name="submit" class="btn"><i class="fas fa-plus"></i> Add Medicine</button>
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

  // Search + stock-level filter
  var filter = document.getElementById('filter');
  if (filter) {
    var rows = document.querySelectorAll('#stockTable tbody tr');
    var noMatch = document.getElementById('noMatch');
    var chips = document.querySelectorAll('.chip');
    var state = 'all';

    function apply() {
      var q = filter.value.trim().toLowerCase(), shown = 0;
      rows.forEach(function (r) {
        var hit = (state === 'all' || r.getAttribute('data-state') === state) &&
                  r.textContent.toLowerCase().indexOf(q) !== -1;
        r.style.display = hit ? '' : 'none';
        if (hit) shown++;
      });
      noMatch.style.display = shown === 0 ? 'block' : 'none';
    }
    filter.addEventListener('input', apply);
    chips.forEach(function (c) {
      c.addEventListener('click', function () {
        state = c.getAttribute('data-state');
        chips.forEach(function (x) { x.setAttribute('aria-pressed', x === c ? 'true' : 'false'); });
        apply();
      });
    });
  }
})();
</script>
</body>
</html>