<?php
session_start();
include_once('connect_db.php');

if (!isset($_SESSION['username'])) {
    header("location:index.php");
    exit();
}
$user = $_SESSION['username'];

function h($v) { return htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8'); }
function initial($s) { $c = strtoupper(substr(trim((string) $s), 0, 1)); return $c !== '' ? $c : '?'; }

$message1 = '';
$rows = [];
try {
    $rows = $con->query("SELECT * FROM customers ORDER BY customer_id DESC")->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Load customers failed: " . $e->getMessage());
    $message1 = "Could not load the customer list.";
}

$with_phone = 0;
$with_email = 0;
foreach ($rows as $r) {
    if (trim((string) ($r['phone'] ?? '')) !== '') { $with_phone++; }
    if (trim((string) ($r['email'] ?? '')) !== '') { $with_email++; }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Customers - Pharmacy Management System</title>
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

.alert { margin:0 0 20px; padding:12px 14px; border-radius:var(--radius); font-size:.95rem; font-weight:500; border:1px solid #f0b8b3; background:var(--danger-bg); color:var(--danger); }

/* Card + table */
.card { background:var(--paper); border:1px solid var(--line); border-radius:16px; overflow:hidden; }
.card-head { display:flex; align-items:center; justify-content:space-between; gap:16px; flex-wrap:wrap; padding:16px 20px; border-bottom:1px solid var(--line); }
.card-head h2 { margin:0; font-size:1.05rem; }
.count { color:var(--ink-soft); font-weight:500; }
.search { position:relative; }
.search i { position:absolute; left:12px; top:50%; transform:translateY(-50%); color:var(--ink-soft); font-size:.9rem; pointer-events:none; }
.search input { width:280px; max-width:100%; padding:9px 12px 9px 34px; font:inherit; font-size:.95rem; color:var(--ink); background:var(--mist); border:1.5px solid transparent; border-radius:var(--radius); }
.search input:focus { outline:none; border-color:var(--pine); background:var(--paper); }
.table-wrap { overflow-x:auto; }
table { width:100%; border-collapse:collapse; font-size:.95rem; }
th, td { padding:14px 20px; text-align:left; white-space:nowrap; }
th { font-size:.78rem; font-weight:600; letter-spacing:.04em; text-transform:uppercase; color:var(--ink-soft); background:#f7faf9; border-bottom:1px solid var(--line); }
td { border-bottom:1px solid #e6eeeb; vertical-align:middle; }
tbody tr:last-child td { border-bottom:0; }
tbody tr:hover td { background:#f7faf9; }
.person { display:flex; align-items:center; gap:12px; }
.mini-avatar { flex:none; display:grid; place-items:center; width:38px; height:38px; border-radius:50%; background:var(--pine); color:#fff; font-weight:700; font-size:.9rem; }
.contact { display:inline-flex; align-items:center; gap:8px; color:var(--ink); }
.contact i { color:var(--ink-soft); font-size:.85rem; width:14px; text-align:center; }
a.contact:hover { color:var(--pine); text-decoration:underline; }
.none { color:var(--ink-soft); }
.pill { display:inline-block; padding:3px 10px; border-radius:999px; background:var(--mist); font-size:.82rem; font-weight:600; color:var(--ink-soft); }
.empty { padding:56px 20px; text-align:center; color:var(--ink-soft); }
.empty i { font-size:2rem; color:var(--line); margin-bottom:12px; display:block; }
.empty p { margin:0; }
.no-match { display:none; padding:32px 20px; text-align:center; color:var(--ink-soft); }

/* Mobile */
.scrim { display:none; }
@media (max-width:900px) {
  .sidebar { transform:translateX(-100%); transition:transform .2s ease; }
  body.nav-open .sidebar { transform:none; }
  body.nav-open .scrim { display:block; position:fixed; inset:0; z-index:25; background:rgba(10,25,22,.5); }
  .topbar { margin-left:0; padding:12px 20px; }
  .menu-btn { display:inline-flex; }
  .main { margin-left:0; padding:24px 20px 48px; }
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
      <li><a href="stock_pharmacist.php"><i class="fas fa-pills"></i>Stock</a></li>
      <li><a href="customers.php" aria-current="page"><i class="fas fa-users"></i>Customers</a></li>
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
      <h1>Customer Directory</h1>
      <p>Look up patient profiles and contact details.</p>
    </div>

    <?php if ($message1 !== ''): ?>
      <div class="alert" role="alert"><?php echo h($message1); ?></div>
    <?php endif; ?>

    <!-- Stats -->
    <section class="stats">
      <div class="stat">
        <span class="stat-icon"><i class="fas fa-users"></i></span>
        <div>
          <div class="stat-label">Total Customers</div>
          <div class="stat-value"><?php echo number_format(count($rows)); ?></div>
        </div>
      </div>
      <div class="stat">
        <span class="stat-icon stat-icon--green"><i class="fas fa-phone"></i></span>
        <div>
          <div class="stat-label">With Phone</div>
          <div class="stat-value"><?php echo number_format($with_phone); ?></div>
        </div>
      </div>
      <div class="stat">
        <span class="stat-icon stat-icon--amber"><i class="fas fa-envelope"></i></span>
        <div>
          <div class="stat-label">With Email</div>
          <div class="stat-value"><?php echo number_format($with_email); ?></div>
        </div>
      </div>
    </section>

    <section class="card">
      <div class="card-head">
        <h2>All Customers <span class="count">(<?php echo count($rows); ?>)</span></h2>
        <?php if (count($rows) > 0): ?>
        <div class="search">
          <i class="fas fa-search" aria-hidden="true"></i>
          <input type="search" id="filter" placeholder="Search name, phone, email, ID..." aria-label="Search customers">
        </div>
        <?php endif; ?>
      </div>

      <?php if (count($rows) === 0): ?>
        <div class="empty">
          <i class="fas fa-user-friends"></i>
          <p>No customers found yet.</p>
        </div>
      <?php else: ?>
      <div class="table-wrap">
        <table id="custTable">
          <thead>
            <tr>
              <th>Customer</th>
              <th>ID</th>
              <th>Phone</th>
              <th>Email</th>
            </tr>
          </thead>
          <tbody>
          <?php foreach ($rows as $row):
              $full  = trim(($row['first_name'] ?? '') . ' ' . ($row['last_name'] ?? ''));
              $phone = trim((string) ($row['phone'] ?? ''));
              $email = trim((string) ($row['email'] ?? ''));
          ?>
            <tr>
              <td>
                <div class="person">
                  <span class="mini-avatar" aria-hidden="true"><?php echo h(initial($row['first_name'] ?? '')); ?></span>
                  <strong><?php echo h($full !== '' ? $full : 'Unnamed'); ?></strong>
                </div>
              </td>
              <td><span class="pill">#<?php echo h($row['customer_id']); ?></span></td>
              <td>
                <?php if ($phone !== ''): ?>
                  <a class="contact" href="tel:<?php echo h(preg_replace('/[^0-9+]/', '', $phone)); ?>"><i class="fas fa-phone"></i><?php echo h($phone); ?></a>
                <?php else: ?><span class="none">&mdash;</span><?php endif; ?>
              </td>
              <td>
                <?php if ($email !== ''): ?>
                  <a class="contact" href="mailto:<?php echo h($email); ?>"><i class="fas fa-envelope"></i><?php echo h($email); ?></a>
                <?php else: ?><span class="none">&mdash;</span><?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <div class="no-match" id="noMatch">No customers match your search.</div>
      <?php endif; ?>
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

  // Table search
  var filter = document.getElementById('filter');
  if (filter) {
    var rows = document.querySelectorAll('#custTable tbody tr');
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