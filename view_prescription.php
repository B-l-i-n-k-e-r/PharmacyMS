<?php
session_start();
include_once('connect_db.php');

// Verify Manager Session
if(isset($_SESSION['username'])){
    $user=$_SESSION['username'];
    $fname=$_SESSION['first_name'];
}else{
    header("location:http://".$_SERVER['HTTP_HOST'].dirname($_SERVER['PHP_SELF'])."/index.php");
    exit();
}

// Fetch all prescriptions safely
try {
    $stmt = $con->query("SELECT * FROM prescription");
    $prescriptions = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error_msg = "Database error: " . htmlspecialchars($e->getMessage());
    $prescriptions = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>View Prescriptions - Pharmacy Management System</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Figtree:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
<style>
:root {
  --pine: #123b36;
  --pine-soft: #1c524b;
  --pine-line: #2a5f57;
  --amber: #f2b544;
  --mist: #eef4f1;
  --paper: #ffffff;
  --ink: #17211f;
  --ink-soft: #5b6b67;
  --line: #d5e0dc;
  --danger: #b3261e;
  --danger-bg: #fdecea;
  --radius: 10px;
  --sidebar: 260px;
  --font: "Figtree", system-ui, -apple-system, "Segoe UI", Roboto, Arial, sans-serif;
}

*, *::before, *::after { box-sizing: border-box; }
html { -webkit-text-size-adjust: 100%; }

body {
  margin: 0;
  font-family: var(--font);
  font-size: 16px;
  line-height: 1.5;
  color: var(--ink);
  background: var(--mist);
}

a { color: inherit; text-decoration: none; }
:focus-visible { outline: 3px solid var(--amber); outline-offset: 2px; }

/* ---------- Shell ---------- */
.sidebar {
  position: fixed;
  inset: 0 auto 0 0;
  width: var(--sidebar);
  display: flex;
  flex-direction: column;
  background: var(--pine);
  color: #fff;
  padding: 24px 16px;
  z-index: 30;
  overflow-y: auto;
}
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

.topbar {
  position: sticky; top: 0; z-index: 20; margin-left: var(--sidebar);
  display: flex; align-items: center; justify-content: space-between; gap: 16px;
  padding: 14px 40px; background: var(--paper); border-bottom: 1px solid var(--line);
}
.topbar-title { font-weight: 600; }
.menu-btn { display: none; align-items: center; justify-content: center; width: 42px; height: 42px; font-size: 1.1rem; color: var(--pine); background: var(--mist); border: 0; border-radius: var(--radius); cursor: pointer; }
.user-chip { display: flex; align-items: center; gap: 10px; font-weight: 500; }
.avatar { display: grid; place-items: center; width: 36px; height: 36px; border-radius: 50%; background: var(--pine); color: #fff; font-weight: 700; }

.main { margin-left: var(--sidebar); padding: 40px; max-width: 1200px; }
.main h1 { margin: 0 0 6px; font-size: clamp(1.6rem, 2.6vw, 2.2rem); line-height: 1.15; letter-spacing: -0.02em; }
.main .lead { margin: 0 0 28px; color: var(--ink-soft); }

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

.empty { padding: 48px 20px; text-align: center; color: var(--ink-soft); }
.no-match { display: none; padding: 32px 20px; text-align: center; color: var(--ink-soft); }
.alert { margin: 0 0 20px; padding: 12px 14px; border-radius: var(--radius); font-size: 0.95rem; font-weight: 500; border: 1px solid #f0b8b3; background: var(--danger-bg); color: var(--danger); }

/* ---------- Mobile ---------- */
.scrim { display: none; }
@media (max-width: 900px) {
  .sidebar { transform: translateX(-100%); transition: transform .2s ease; }
  body.nav-open .sidebar { transform: none; }
  body.nav-open .scrim { display: block; position: fixed; inset: 0; z-index: 25; background: rgba(10, 25, 22, .5); }
  .topbar { margin-left: 0; padding: 12px 20px; }
  .menu-btn { display: inline-flex; }
  .main { margin-left: 0; padding: 28px 20px 48px; }
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
      <li><a href="view_prescription.php" aria-current="page"><i class="fas fa-prescription-bottle-alt"></i>View Prescription</a></li>
      <li><a href="stock.php"><i class="fas fa-pills"></i>Manage Stock</a></li>
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
    <h1>Prescriptions</h1>
    <p class="lead">Review and manage patient prescriptions.</p>

    <?php if (isset($error_msg)): ?>
      <div class="alert" role="alert"><?php echo $error_msg; ?></div>
    <?php endif; ?>

    <section class="card">
      <div class="card-head">
        <h2>All Prescriptions <span class="count">(<?php echo count($prescriptions); ?>)</span></h2>
        <?php if (count($prescriptions) > 0): ?>
        <div class="search">
          <i class="fas fa-search" aria-hidden="true"></i>
          <input type="search" id="filter" placeholder="Search by customer or invoice..." aria-label="Search prescriptions">
        </div>
        <?php endif; ?>
      </div>

      <?php if (count($prescriptions) === 0): ?>
        <div class="empty">
          <p>No prescriptions found in the system.</p>
        </div>
      <?php else: ?>
      <div class="table-wrap">
        <table id="presTable">
          <thead>
            <tr>
              <th>Prescription No.</th>
              <th>Customer</th>
              <th>Invoice No.</th>
              <th>Date Issued</th>
            </tr>
          </thead>
          <tbody>
          <?php foreach ($prescriptions as $row): ?>
            <tr>
              <td><strong>#<?php echo htmlspecialchars($row['prescription_id']); ?></strong></td>
              <td><?php echo htmlspecialchars($row['customer_name']); ?></td>
              <td><?php echo htmlspecialchars($row['invoice_id']); ?></td>
              <td><?php echo htmlspecialchars($row['date']); ?></td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <div class="no-match" id="noMatch">No prescriptions match your search.</div>
      <?php endif; ?>
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