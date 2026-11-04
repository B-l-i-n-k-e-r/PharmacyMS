<?php
session_start();
include_once('connect_db.php');

date_default_timezone_set('Africa/Nairobi'); // keeps the greeting and date correct

if (isset($_SESSION['username'])) {
    $id    = $_SESSION['pharmacist_id'];
    $fname = $_SESSION['first_name'];
    $lname = $_SESSION['last_name'];
    $sid   = $_SESSION['staff_id'];
    $user  = $_SESSION['username'];
} else {
    header("location:index.php");
    exit();
}

$hour     = (int) date('G');
$greeting = ($hour < 12) ? 'Good morning' : (($hour < 17) ? 'Good afternoon' : 'Good evening');
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?php echo htmlspecialchars($user); ?> - MedFlow PMS</title>
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

.nav-label {
  margin: 20px 12px 6px;
  font-size: 0.72rem;
  font-weight: 600;
  letter-spacing: .08em;
  text-transform: uppercase;
  color: #7fa59b;
}
.nav { list-style: none; margin: 8px 0 0; padding: 0; flex: 1; }
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
  background: rgba(255,255,255,.92);
  backdrop-filter: blur(8px);
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
  max-width: 1200px;
}

/* ---------- Hero ---------- */
.hero {
  position: relative;
  overflow: hidden;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 24px;
  flex-wrap: wrap;
  padding: 34px 38px;
  margin-bottom: 32px;
  color: #fff;
  border-radius: 20px;
  background: linear-gradient(135deg, var(--pine) 0%, #1f6154 100%);
}
.hero::before, .hero::after {
  content: "";
  position: absolute;
  border-radius: 50%;
  background: rgba(255,255,255,.06);
}
.hero::before { width: 260px; height: 260px; right: -70px; top: -90px; }
.hero::after  { width: 160px; height: 160px; right: 140px; bottom: -90px; background: rgba(242,181,68,.14); }
.hero > * { position: relative; z-index: 1; }
.hero-date { font-size: .8rem; font-weight: 600; letter-spacing: .08em; text-transform: uppercase; color: #9fc2b8; margin-bottom: 8px; }
.hero h1 { margin: 0 0 6px; font-size: clamp(1.6rem, 2.6vw, 2.1rem); line-height: 1.15; letter-spacing: -0.02em; color: #fff; }
.hero p { margin: 0; color: #c4d8d2; max-width: 46ch; }
.hero-meta {
  display: flex; align-items: center; gap: 10px;
  padding: 12px 18px; border-radius: 14px;
  background: rgba(255,255,255,.1); border: 1px solid rgba(255,255,255,.15);
  font-size: .9rem; color: #e4f0ec;
}
.hero-meta i { color: var(--amber); }
.hero-meta strong { color: #fff; }

/* ---------- Sections ---------- */
.section-title {
  margin: 0 0 16px;
  font-size: 1.05rem;
  font-weight: 600;
}
.section + .section { margin-top: 36px; }

/* ---------- Action cards ---------- */
.actions {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
  gap: 20px;
}
.action {
  position: relative;
  display: flex;
  flex-direction: column;
  gap: 18px;
  padding: 26px;
  background: var(--paper);
  border: 1px solid var(--line);
  border-radius: 18px;
  transition: transform .18s, box-shadow .18s, border-color .18s;
}
.action:hover {
  transform: translateY(-4px);
  border-color: var(--pine);
  box-shadow: 0 14px 30px rgba(18,59,54,.12);
}
.action-icon {
  display: grid;
  place-items: center;
  width: 56px;
  height: 56px;
  border-radius: 16px;
  font-size: 1.35rem;
}
.action--amber .action-icon { background: #fbebc8; color: #7a5200; }
.action--pine  .action-icon { background: var(--pine); color: #fff; }
.action h3 { margin: 0 0 6px; font-size: 1.2rem; }
.action p { margin: 0; color: var(--ink-soft); font-size: .95rem; }
.action-go {
  margin-top: auto;
  display: inline-flex;
  align-items: center;
  gap: 8px;
  font-weight: 600;
  font-size: .92rem;
  color: var(--pine);
}
.action-go i { transition: transform .18s; }
.action:hover .action-go i { transform: translateX(5px); }

/* ---------- Checklist ---------- */
.panel {
  background: var(--paper);
  border: 1px solid var(--line);
  border-radius: 18px;
  padding: 26px;
}
.panel-sub { margin: -8px 0 18px; color: var(--ink-soft); font-size: .88rem; }
.checks { display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 14px; }
.check {
  display: flex;
  align-items: flex-start;
  gap: 14px;
  padding: 16px;
  border-radius: 14px;
  background: var(--mist);
  transition: background-color .15s;
}
.check:hover { background: #e2ece8; }
.check-icon {
  flex: none;
  display: grid;
  place-items: center;
  width: 38px;
  height: 38px;
  border-radius: 10px;
  background: #fff;
  color: var(--pine);
}
.check h4 { margin: 0 0 2px; font-size: .98rem; }
.check p { margin: 0; color: var(--ink-soft); font-size: .86rem; }

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
  .main { margin-left: 0; padding: 24px 20px 48px; }
  .hero { padding: 26px 22px; border-radius: 16px; }
}

@media (prefers-reduced-motion: reduce) {
  .sidebar, .action, .action-go i { transition: none; }
  .action:hover { transform: none; }
}
</style>
</head>
<body>
<div class="shell">

  <aside class="sidebar" id="sidebar" aria-label="Main navigation">
    <div class="brand">
      <span class="brand-mark" aria-hidden="true"></span>
      <span class="brand-name">MedFlow<br>PMS</span>
    </div>

    <ul class="nav">
      <li><a href="pharmacist.php" aria-current="page"><i class="fas fa-home"></i>Dashboard</a></li>
    </ul>

    <div class="nav-label">Pharmacy</div>
    <ul class="nav" style="margin-top: 0; flex: 0;">
      <li><a href="prescription.php"><i class="fas fa-prescription-bottle-alt"></i>Prescription</a></li>
      <li><a href="stock_pharmacist.php"><i class="fas fa-pills"></i>Stock</a></li>
    </ul>

    <div style="flex: 1;"></div>

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

    <!-- HERO -->
    <section class="hero">
      <div>
        <div class="hero-date"><?php echo date('l, j F Y'); ?></div>
        <h1><?php echo $greeting; ?>, <?php echo htmlspecialchars($fname); ?></h1>
        <p>Manage your prescriptions and keep an eye on your stock levels.</p>
      </div>
      <div class="hero-meta">
        <i class="fas fa-id-badge"></i>
        <span>Staff ID <strong><?php echo htmlspecialchars((string) $sid); ?></strong></span>
      </div>
    </section>

    <!-- MAIN ACTIONS -->
    <section class="section">
      <h2 class="section-title">Where would you like to go?</h2>
      <div class="actions">
        <a href="prescription.php" class="action action--amber">
          <span class="action-icon"><i class="fas fa-prescription-bottle-alt"></i></span>
          <div>
            <h3>Prescriptions</h3>
            <p>Process new prescriptions and view patient history.</p>
          </div>
          <span class="action-go">Open prescriptions <i class="fas fa-arrow-right"></i></span>
        </a>

        <a href="stock_pharmacist.php" class="action action--pine">
          <span class="action-icon"><i class="fas fa-pills"></i></span>
          <div>
            <h3>Manage Stock</h3>
            <p>Check available medicines, update quantities, and track expiry.</p>
          </div>
          <span class="action-go">Open stock <i class="fas fa-arrow-right"></i></span>
        </a>
      </div>
    </section>

    <!-- DAILY CHECKLIST -->
    <section class="section">
      <div class="panel">
        <h2 class="section-title">Daily Checklist</h2>
        <p class="panel-sub">Quick reminders to keep the pharmacy running smoothly.</p>
        <div class="checks">
          <a href="prescription.php" class="check">
            <span class="check-icon"><i class="fas fa-clipboard-check"></i></span>
            <div>
              <h4>Review pending prescriptions</h4>
              <p>Process anything waiting from the doctors.</p>
            </div>
          </a>
          <a href="stock_pharmacist.php" class="check">
            <span class="check-icon"><i class="fas fa-box-open"></i></span>
            <div>
              <h4>Check low stock</h4>
              <p>Spot medicines running low before they run out.</p>
            </div>
          </a>
          <a href="stock_pharmacist.php" class="check">
            <span class="check-icon"><i class="fas fa-calendar-times"></i></span>
            <div>
              <h4>Track expiry dates</h4>
              <p>Remove or flag medicines close to expiry.</p>
            </div>
          </a>
        </div>
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