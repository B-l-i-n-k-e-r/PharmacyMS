<?php
session_start();
include_once('connect_db.php');

if(isset($_SESSION['username'])){
    $id=$_SESSION['admin_id'];
    $username=$_SESSION['username'];
}else{
    header("location:http://".$_SERVER['HTTP_HOST'].dirname($_SERVER['PHP_SELF'])."/index.php");
    exit();
}

// Handle city filtering
$selected_val = "All"; // Default value
if(isset($_POST['cityvalue'])){
    $selected_val = $_POST['City'];
}

// Base dynamic query syntax definition
if($selected_val == "All" || $selected_val == "Select" || empty($selected_val)) {
    $queryStr = "SELECT * FROM pharmacist";
    $stmtView = $con->prepare($queryStr);
    $stmtView->execute();
} else {
    $queryStr = "SELECT * FROM pharmacist WHERE postal_address = :city";
    $stmtView = $con->prepare($queryStr);
    $stmtView->execute([':city' => $selected_val]);
}

$rows = $stmtView->fetchAll(PDO::FETCH_ASSOC);
$cities = ['Fullerton', 'Michikan', 'Newark', 'Ladora'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?php echo htmlspecialchars($username); ?> - Pharmacy Management System</title>
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
  --ok: #1e6b3a;
  --ok-bg: #e6f4ea;
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
.brand {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 0 8px 24px;
  margin-bottom: 8px;
  border-bottom: 1px solid var(--pine-line);
}
.brand-mark {
  flex: none;
  width: 40px;
  height: 18px;
  border-radius: 999px;
  background: linear-gradient(90deg, var(--amber) 0 50%, #f7f3e8 50% 100%);
  transform: rotate(-24deg);
}
.brand-name { font-weight: 700; line-height: 1.2; letter-spacing: -0.01em; }

.nav { list-style: none; margin: 16px 0 0; padding: 0; flex: 1; }
.nav li + li { margin-top: 2px; }
.nav a {
  display: flex;
  align-items: center;
  gap: 14px;
  padding: 11px 12px;
  border-radius: var(--radius);
  color: #c4d8d2;
  font-weight: 500;
  transition: background-color .15s, color .15s;
}
.nav a i { width: 20px; text-align: center; font-size: 1rem; }
.nav a:hover { background: var(--pine-soft); color: #fff; }
.nav a[aria-current="page"] { background: #fff; color: var(--pine); }

.sidebar-foot { margin-top: 16px; padding-top: 16px; border-top: 1px solid var(--pine-line); }
.sidebar-foot a {
  display: flex;
  align-items: center;
  gap: 14px;
  padding: 11px 12px;
  border-radius: var(--radius);
  color: #c4d8d2;
  font-weight: 500;
}
.sidebar-foot a i { width: 20px; text-align: center; }
.sidebar-foot a:hover { background: var(--pine-soft); color: #fff; }

.topbar {
  position: sticky;
  top: 0;
  z-index: 20;
  margin-left: var(--sidebar);
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 16px;
  padding: 14px 40px;
  background: var(--paper);
  border-bottom: 1px solid var(--line);
}
.topbar-title { font-weight: 600; }
.menu-btn {
  display: none;
  align-items: center;
  justify-content: center;
  width: 42px;
  height: 42px;
  font-size: 1.1rem;
  color: var(--pine);
  background: var(--mist);
  border: 0;
  border-radius: var(--radius);
  cursor: pointer;
}
.user-chip { display: flex; align-items: center; gap: 10px; font-weight: 500; }
.avatar {
  display: grid;
  place-items: center;
  width: 36px;
  height: 36px;
  border-radius: 50%;
  background: var(--pine);
  color: #fff;
  font-weight: 700;
}

.main { margin-left: var(--sidebar); padding: 40px; max-width: 1200px; }
.main h1 {
  margin: 0 0 6px;
  font-size: clamp(1.6rem, 2.6vw, 2.2rem);
  line-height: 1.15;
  letter-spacing: -0.02em;
}
.main .lead { margin: 0 0 28px; color: var(--ink-soft); }

/* ---------- Filter Bar ---------- */
.filter-bar {
  display: flex;
  align-items: center;
  gap: 12px;
  margin-bottom: 24px;
  flex-wrap: wrap;
}
.filter-bar label {
  font-weight: 600;
  font-size: 0.95rem;
}
.filter-bar select {
  padding: 10px 14px;
  font: inherit;
  font-size: 0.95rem;
  color: var(--ink);
  background: var(--paper);
  border: 1.5px solid var(--line);
  border-radius: var(--radius);
  cursor: pointer;
}
.filter-bar select:focus {
  outline: none;
  border-color: var(--pine);
  box-shadow: 0 0 0 3px rgba(18, 59, 54, .15);
}
.btn {
  display: inline-block;
  padding: 10px 20px;
  font: inherit;
  font-weight: 600;
  color: #fff;
  background: var(--pine);
  border: 0;
  border-radius: var(--radius);
  cursor: pointer;
  transition: background-color .15s;
}
.btn:hover { background: var(--pine-soft); }
.btn--outline {
  background: transparent;
  color: var(--pine);
  border: 1.5px solid var(--line);
}
.btn--outline:hover {
  background: var(--mist);
  border-color: var(--pine);
}

/* ---------- Table panel ---------- */
.card {
  background: var(--paper);
  border: 1px solid var(--line);
  border-radius: 14px;
  overflow: hidden;
}
.card-head {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 16px;
  flex-wrap: wrap;
  padding: 16px 20px;
  border-bottom: 1px solid var(--line);
}
.card-head h2 { margin: 0; font-size: 1.05rem; }
.count { color: var(--ink-soft); font-weight: 500; }

.table-wrap { overflow-x: auto; }
table { width: 100%; border-collapse: collapse; font-size: 0.95rem; }
th, td { padding: 13px 20px; text-align: left; white-space: nowrap; }
th {
  font-size: 0.875rem;
  font-weight: 600;
  color: var(--ink-soft);
  background: #f7faf9;
  border-bottom: 1px solid var(--line);
}
td { border-bottom: 1px solid #e6eeeb; }
tbody tr:last-child td { border-bottom: 0; }
tbody tr:hover td { background: #f7faf9; }
td.num, th.num { color: var(--ink-soft); }

.empty { padding: 48px 20px; text-align: center; color: var(--ink-soft); }
.empty p { margin: 0 0 16px; }

/* ---------- Small screens ---------- */
.scrim { display: none; }

@media (max-width: 900px) {
  .sidebar { transform: translateX(-100%); transition: transform .2s ease; }
  body.nav-open .sidebar { transform: none; }
  body.nav-open .scrim {
    display: block;
    position: fixed;
    inset: 0;
    z-index: 25;
    background: rgba(10, 25, 22, .5);
  }
  .topbar { margin-left: 0; padding: 12px 20px; }
  .menu-btn { display: inline-flex; }
  .main { margin-left: 0; padding: 28px 20px 48px; }
  .filter-bar { flex-direction: column; align-items: flex-start; }
  .filter-bar select { width: 100%; }
}

@media (prefers-reduced-motion: reduce) {
  .sidebar { transition: none; }
}

/* ---------- COOL PRINT STYLES ---------- */
@media print {
  @page { margin: 15mm; }
  
  body { 
    background: #fff; 
    font-size: 12pt; 
    color: #000;
  }

  /* Hide everything on the page */
  body * { visibility: hidden; }
  
  /* Show only the printable area */
  .print-area, .print-area * { visibility: visible; }
  
  /* Position the printable area at the top left */
  .print-area { 
    position: absolute; 
    left: 0; 
    top: 0; 
    width: 100%; 
    border: none; 
    box-shadow: none;
  }

  /* Print Header Styling */
  .print-header { 
    display: block !important; 
    border-bottom: 3px solid #123b36; 
    padding-bottom: 15px; 
    margin-bottom: 25px; 
    display: flex;
    align-items: center;
    justify-content: space-between;
  }
  .print-header-left h1 {
    font-size: 22pt;
    margin: 0;
    color: #123b36;
    letter-spacing: -0.02em;
  }
  .print-header-left p {
    margin: 4px 0 0;
    font-size: 11pt;
    color: #555;
  }
  .print-meta {
    text-align: right;
    font-size: 10pt;
    color: #555;
  }
  .print-meta strong {
    color: #123b36;
  }

  /* Table Styling for Print */
  .card { border: none; }
  .card-head { 
    padding: 0 0 15px 0; 
    border-bottom: 2px solid #e0e0e0;
    margin-bottom: 10px;
  }
  .card-head h2 { font-size: 14pt; color: #123b36; }
  
  table { font-size: 10pt; }
  th { 
    background: #f0f0f0 !important; 
    color: #123b36 !important; 
    border-bottom: 1px solid #999;
    padding: 10px 12px;
  }
  td { 
    padding: 10px 12px; 
    border-bottom: 1px solid #e0e0e0; 
  }
  tr:nth-child(even) td {
    background-color: #fafafa !important;
    -webkit-print-color-adjust: exact;
    print-color-adjust: exact;
  }

  /* Footer / Pagination */
  .print-footer {
    display: block !important;
    position: fixed;
    bottom: 0;
    left: 0;
    width: 100%;
    text-align: center;
    font-size: 9pt;
    color: #888;
    border-top: 1px solid #ddd;
    padding-top: 10px;
  }

  /* Hide specific elements */
  .sidebar, .topbar, .filter-bar, .btn, .menu-btn, .scrim, .no-print, .badge { 
    display: none !important; 
  }
}
.print-header, .print-footer { display: none; }
</style>
</head>
<body>

  <aside class="sidebar" id="sidebar" aria-label="Main navigation">
    <div class="brand">
      <span class="brand-mark" aria-hidden="true"></span>
      <span class="brand-name">Pharmacy<br>Management</span>
    </div>

    <ul class="nav">
      <li><a href="admin.php"><i class="fas fa-home"></i>Dashboard</a></li>
      <li><a href="admin_pharmacist.php"><i class="fas fa-prescription-bottle-alt"></i>Pharmacist</a></li>
      <li><a href="admin_manager.php"><i class="fas fa-user-cog"></i>Manager</a></li>
      <li><a href="admin_cashier.php"><i class="fas fa-cash-register"></i>Cashier</a></li>
      <li><a href="admin_localdata.php" aria-current="page"><i class="fas fa-database"></i>Local Data</a></li>
      <li><a href="admin_globaldata.php"><i class="fas fa-server"></i>Global Data</a></li>
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
    <span class="topbar-title">Admin dashboard</span>
    <div class="user-chip">
      <span class="avatar" aria-hidden="true"><?php echo htmlspecialchars(strtoupper(substr($username, 0, 1))); ?></span>
      <span><?php echo htmlspecialchars($username); ?></span>
    </div>
  </header>

  <main class="main">
    <h1>Local Data</h1>
    <p class="lead">Find pharmacists locally from the selected cities.</p>

    <!-- Filter Form (Hidden when printing) -->
    <form action="" method="post" class="filter-bar">
      <label for="city">Filter by City:</label>
      <select name="City" id="city">
        <option value="Select" <?php echo ($selected_val == 'Select') ? 'selected' : ''; ?>>Select City</option>
        <option value="All" <?php echo ($selected_val == 'All') ? 'selected' : ''; ?>>All</option>
        <?php foreach ($cities as $city): ?>
          <option value="<?php echo $city; ?>" <?php echo ($selected_val == $city) ? 'selected' : ''; ?>>
            <?php echo $city; ?>
          </option>
        <?php endforeach; ?>
      </select>
      <button type="submit" name="cityvalue" class="btn">Apply Filter</button>
    </form>

    <!-- Results Table (This is the print area) -->
    <section class="card print-area">
      
      <!-- Print-Only Header -->
      <div class="print-header">
        <div class="print-header-left">
          <h1>Local Pharmacists Report</h1>
          <p>Pharmacy Management System</p>
        </div>
        <div class="print-meta">
          <strong>Filter Applied:</strong> <?php echo ($selected_val == 'All' || $selected_val == 'Select') ? 'All Cities' : htmlspecialchars($selected_val); ?><br>
          <strong>Generated on:</strong> <?php echo date('M j, Y \a\t g:i A'); ?><br>
          <strong>Total Records:</strong> <?php echo count($rows); ?>
        </div>
      </div>

      <div class="card-head">
        <h2>
          <?php if($selected_val == 'All' || $selected_val == 'Select' || empty($selected_val)): ?>
            All Pharmacists
          <?php else: ?>
            Pharmacists in <?php echo htmlspecialchars($selected_val); ?>
          <?php endif; ?>
          <span class="count no-print">(<?php echo count($rows); ?>)</span>
        </h2>
        
        <!-- PRINT BUTTON -->
        <?php if (count($rows) > 0): ?>
        <button type="button" onclick="window.print()" class="btn btn--outline no-print">
          <i class="fas fa-print"></i> Print Report
        </button>
        <?php endif; ?>
      </div>

      <?php if (count($rows) === 0): ?>
        <div class="empty">
          <p>No pharmacists found for this selection.</p>
        </div>
      <?php else: ?>
      <div class="table-wrap">
        <table>
          <thead>
            <tr>
              <th class="num">ID</th>
              <th>Staff ID</th>
              <th>First name</th>
              <th>Last name</th>
              <th>Username</th>
              <th>Address</th>
              <th>Phone</th>
            </tr>
          </thead>
          <tbody>
          <?php foreach ($rows as $row): ?>
            <tr>
              <td class="num"><?php echo htmlspecialchars($row['pharmacist_id']); ?></td>
              <td><?php echo htmlspecialchars($row['staff_id']); ?></td>
              <td><?php echo htmlspecialchars($row['first_name']); ?></td>
              <td><?php echo htmlspecialchars($row['last_name']); ?></td>
              <td><?php echo htmlspecialchars($row['username']); ?></td>
              <td><?php echo htmlspecialchars($row['postal_address']); ?></td>
              <td><?php echo htmlspecialchars($row['phone']); ?></td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <?php endif; ?>

      <!-- Print-Only Footer (Pagination) -->
      <div class="print-footer">
        Pharmacy Management System &copy; <?php echo date('Y'); ?> — Official Report
      </div>
    </section>
  </main>

<script>
(function () {
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
})();
</script>
</body>
</html>