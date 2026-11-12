<?php
session_start();
include_once('../connect_db.php');

date_default_timezone_set('Africa/Nairobi'); 

if (isset($_SESSION['username'])) {
    $id    = $_SESSION['pharmacist_id'];
    $fname = $_SESSION['first_name'];
    $lname = $_SESSION['last_name'];
    $sid   = $_SESSION['staff_id'];
    $user  = $_SESSION['username'];
} else {
    header("location:../index.php");
    exit();
}

$hour     = (int) date('G');
$greeting = ($hour < 12) ? 'Good morning' : (($hour < 17) ? 'Good afternoon' : 'Good evening');

// Fetch Metrics for Dashboard
$today = date('Y-m-d');
try {
    $stmt1 = $con->prepare("SELECT COUNT(*) FROM prescription WHERE DATE(date) = :today");
    $stmt1->execute([':today' => $today]);
    $presc_today = $stmt1->fetchColumn();
} catch (PDOException $e) { $presc_today = 0; }

try {
    $stmt2 = $con->query("SELECT COUNT(*) FROM stock WHERE quantity < 10");
    $low_stock = $stmt2->fetchColumn();
} catch (PDOException $e) { $low_stock = 0; }

$page_title = "Pharmacist Dashboard - MedFlow PMS";
$topbar_title = "Pharmacist Dashboard";

include '../includes/head.php';
?>

<!-- CUSTOM STYLES FOR THIS SPECIFIC PAGE (Hero & Cards) -->
<style>
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

/* ---------- Action cards ---------- */
.section-title { margin: 0 0 16px; font-size: 1.05rem; font-weight: 600; }
.section + .section { margin-top: 36px; }
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
  display: grid; place-items: center; width: 56px; height: 56px;
  border-radius: 16px; font-size: 1.35rem;
}
.action--amber .action-icon { background: #fbebc8; color: #7a5200; }
.action--pine  .action-icon { background: var(--pine); color: #fff; }
.action h3 { margin: 0 0 6px; font-size: 1.2rem; }
.action p { margin: 0; color: var(--ink-soft); font-size: .95rem; }
.action-go {
  margin-top: auto; display: inline-flex; align-items: center; gap: 8px;
  font-weight: 600; font-size: .92rem; color: var(--pine);
}
.action-go i { transition: transform .18s; }
.action:hover .action-go i { transform: translateX(5px); }
</style>

<?php
include '../includes/sidebar_pharmacist.php';
include '../includes/header.php';
?>

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

    <!-- METRICS -->
    <section class="section" style="margin-bottom: 40px;">
      <div class="metrics-grid" style="margin-bottom: 0;">
          <div class="metric-card">
              <div class="metric-icon metric-icon--pine"><i class="fas fa-prescription"></i></div>
              <div class="metric-info">
                <h3>Prescriptions Today</h3>
                <p><?php echo number_format($presc_today); ?></p>
              </div>
          </div>
          <div class="metric-card" style="<?php echo $low_stock > 0 ? 'border-color: var(--danger);' : ''; ?>">
              <div class="metric-icon metric-icon--danger"><i class="fas fa-exclamation-triangle"></i></div>
              <div class="metric-info">
                <h3>Low Stock Alerts</h3>
                <p><?php echo number_format($low_stock); ?> items</p>
              </div>
          </div>
      </div>
    </section>

    <!-- MAIN ACTIONS -->
    <section class="section">
      <h2 class="section-title">Where would you like to go?</h2>
      <div class="actions">
        
        <!-- New POS -->
        <a href="prescription.php" class="action action--pine">
          <span class="action-icon"><i class="fas fa-cash-register"></i></span>
          <div>
            <h3>POS (Prescribe)</h3>
            <p>Process new prescriptions and send to cashier.</p>
          </div>
          <span class="action-go">Open POS <i class="fas fa-arrow-right"></i></span>
        </a>

        <!-- Inventory -->
        <a href="stock_pharmacist.php" class="action action--amber">
          <span class="action-icon"><i class="fas fa-pills"></i></span>
          <div>
            <h3>Inventory</h3>
            <p>Check available medicines, update quantities, and track expiry.</p>
          </div>
          <span class="action-go">Open inventory <i class="fas fa-arrow-right"></i></span>
        </a>

        <!-- Customers -->
        <a href="pharmacist_customers.php" class="action action--pine">
          <span class="action-icon"><i class="fas fa-address-book"></i></span>
          <div>
            <h3>Customers</h3>
            <p>View patient profiles, history, and contact information.</p>
          </div>
          <span class="action-go">View patients <i class="fas fa-arrow-right"></i></span>
        </a>

      </div>
    </section>

</main>

<?php include '../includes/footer.php'; ?>