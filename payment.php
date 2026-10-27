<?php
session_start();
include_once('connect_db.php');

if(isset($_SESSION['username'])){
    $id=$_SESSION['cashier_id'];
    $fname=$_SESSION['first_name'];
    $user=$_SESSION['username'];
}else{
    header("location:http://".$_SERVER['HTTP_HOST'].dirname($_SERVER['PHP_SELF'])."/index.php");
    exit();
}

// Fetch payment types securely using PDO
try {
    $stmt = $con->query("SELECT Name FROM paymentTypes");
    $paymentTypes = $stmt->fetchAll(PDO::FETCH_COLUMN);
} catch (PDOException $e) {
    $paymentTypes = [];
    $error_msg = "Database error: " . htmlspecialchars($e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Process Payment - Pharmacy Management System</title>
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

.main { margin-left: var(--sidebar); padding: 40px; max-width: 1100px; }
.main h1 { margin: 0 0 6px; font-size: clamp(1.6rem, 2.6vw, 2.2rem); line-height: 1.15; letter-spacing: -0.02em; }
.main .lead { margin: 0 0 28px; color: var(--ink-soft); }

/* ---------- Form Card ---------- */
.card { background: var(--paper); border: 1px solid var(--line); border-radius: 14px; }
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
  .grid { grid-template-columns: 1fr; }
  .form-card { padding: 20px; }
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
      <li><a href="cashier.php"><i class="fas fa-home"></i>Dashboard</a></li>
      <li><a href="payment.php" aria-current="page"><i class="fas fa-money-bill-wave-alt"></i>Process Payment</a></li>
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
    <span class="topbar-title">Cashier Dashboard</span>
    <div class="user-chip">
      <span class="avatar" aria-hidden="true"><?php echo htmlspecialchars(strtoupper(substr($user, 0, 1))); ?></span>
      <span><?php echo htmlspecialchars($user); ?></span>
    </div>
  </header>

  <main class="main">
    <h1>Process Payment</h1>
    <p class="lead">Enter the invoice details and payment method to generate a receipt.</p>

    <?php if (isset($error_msg)): ?>
      <div class="alert" role="alert"><?php echo $error_msg; ?></div>
    <?php endif; ?>

    <section class="card form-card">
      <form name="myform" action="receipt.php" method="post" autocomplete="off">
        <div class="grid">
          <div class="field span-2">
            <label for="invoice_no">Invoice No.</label>
            <input name="invoice_no" id="invoice_no" type="text" required>
          </div>
          
          <div class="field">
            <label for="amount">Amount</label>
            <input name="amount" id="amount" type="number" step="0.01" min="0" required>
          </div>
          
          <div class="field">
            <label for="payment_type">Payment Type</label>
            <select name="payType" id="payment_type" required>
              <option value="">Select Payment...</option>
              <?php foreach ($paymentTypes as $type): ?>
                <option value="<?php echo htmlspecialchars($type); ?>"><?php echo htmlspecialchars($type); ?></option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="field span-2">
            <label for="serial_no">Serial No. (Card/Cheque)</label>
            <input name="serial_no" id="serial_no" type="text" placeholder="Optional for cash payments">
          </div>
        </div>

        <div class="form-actions">
          <button type="submit" name="tuma" class="btn">Process Payment</button>
          <a href="cashier.php" class="btn btn--ghost">Cancel</a>
        </div>
      </form>
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