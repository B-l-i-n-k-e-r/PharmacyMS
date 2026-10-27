<?php
session_start();
include_once('connect_db.php');

if(isset($_SESSION['username'])){
    $id=$_SESSION['manager_id'];
    $fname=$_SESSION['first_name'];
    $lname=$_SESSION['last_name'];
    $sid=$_SESSION['staff_id'];
    $user=$_SESSION['username'];
}else{
    header("location:http://".$_SERVER['HTTP_HOST'].dirname($_SERVER['PHP_SELF'])."/index.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?php echo htmlspecialchars($user); ?> - Pharmacy Management System</title>
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

.main {
  margin-left: var(--sidebar);
  padding: 40px;
  max-width: 1100px;
}
.main h1 {
  margin: 0 0 6px;
  font-size: clamp(1.6rem, 2.6vw, 2.2rem);
  line-height: 1.15;
  letter-spacing: -0.02em;
}
.main .lead { margin: 0 0 36px; color: var(--ink-soft); }

.group + .group { margin-top: 36px; }
.group h2 {
  margin: 0 0 14px;
  font-size: 1.05rem;
  font-weight: 600;
}

.tiles {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
  gap: 16px;
}

.tile {
  display: flex;
  flex-direction: column;
  gap: 14px;
  padding: 22px;
  background: var(--paper);
  border: 1px solid var(--line);
  border-radius: 14px;
  transition: border-color .15s, background-color .15s;
}
.tile:hover { border-color: var(--pine); }

.tile-icon {
  display: grid;
  place-items: center;
  width: 48px;
  height: 48px;
  border-radius: 12px;
  background: var(--mist);
  color: var(--pine);
  font-size: 1.15rem;
}
.tile--amber .tile-icon { background: #fbebc8; color: #7a5200; }
.tile--pine  .tile-icon { background: var(--pine); color: #fff; }

.tile h3 { margin: 0; font-size: 1.1rem; }
.tile p { margin: 4px 0 0; color: var(--ink-soft); font-size: 0.95rem; }

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
}

@media (prefers-reduced-motion: reduce) {
  .sidebar { transition: none; }
}
</style>
</head>
<body>
<div class="shell">

  <aside class="sidebar" id="sidebar" aria-label="Main navigation">
    <div class="brand">
      <span class="brand-mark" aria-hidden="true"></span>
      <span class="brand-name">Pharmacy<br>Management</span>
    </div>

    <ul class="nav">
      <li><a href="manager.php" aria-current="page"><i class="fas fa-home"></i>Dashboard</a></li>
      <li><a href="view.php"><i class="fas fa-users"></i>View Users</a></li>
      <li><a href="view_prescription.php"><i class="fas fa-prescription-bottle-alt"></i>View Prescription</a></li>
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
    <h1>Welcome back, <?php echo htmlspecialchars($fname); ?></h1>
    <p class="lead">Manage your pharmacy's users, prescriptions, and stock from one place.</p>

    <section class="group">
      <h2>User Management</h2>
      <div class="tiles">
        <a href="view.php" class="tile tile--pine">
          <span class="tile-icon"><i class="fas fa-users"></i></span>
          <div>
            <h3>View Users</h3>
            <p>See all registered staff members in the system.</p>
          </div>
        </a>
      </div>
    </section>

    <section class="group">
      <h2>Operations</h2>
      <div class="tiles">
        <a href="view_prescription.php" class="tile tile--amber">
          <span class="tile-icon"><i class="fas fa-prescription-bottle-alt"></i></span>
          <div>
            <h3>View Prescriptions</h3>
            <p>Review and manage patient prescriptions.</p>
          </div>
        </a>
        <a href="stock.php" class="tile tile--pine">
          <span class="tile-icon"><i class="fas fa-pills"></i></span>
          <div>
            <h3>Manage Stock</h3>
            <p>Update inventory, track medicine, and check suppliers.</p>
          </div>
        </a>
      </div>
    </section>
  </main>

</div>

<script>
  (function () {
    var body = document.body;
    var btn = document.getElementById('menuBtn');
    var scrim = document.getElementById('scrim');

    function setOpen(open) {
      body.classList.toggle('nav-open', open);
      btn.setAttribute('aria-expanded', open ? 'true' : 'false');
    }
    btn.addEventListener('click', function () {
      setOpen(!body.classList.contains('nav-open'));
    });
    scrim.addEventListener('click', function () { setOpen(false); });
    document.addEventListener('keydown', function (e) {
      if (e.key === 'Escape') setOpen(false);
    });
  })();
</script>
</body>
</html>