<?php
session_start();
include_once('connect_db.php');

if(isset($_SESSION['username'])){
    $id=$_SESSION['admin_id'];
    $admin_user=$_SESSION['username'];
}else{
    header("location:http://".$_SERVER['HTTP_HOST'].dirname($_SERVER['PHP_SELF'])."/index.php");
    exit();
}

$message1 = '';

// 1. Fetch user data safely to pre-fill the form
if(isset($_GET['username'])) {
    $fetch_user = $_GET['username'];
    $stmt = $con->prepare("SELECT * FROM cashier WHERE username = :user");
    $stmt->execute([':user' => $fetch_user]);
    $userData = $stmt->fetch(PDO::FETCH_ASSOC);

    if(!$userData) {
        die("Cashier not found.");
    }
} else {
    header("Location: admin_cashier.php");
    exit();
}

// 2. Handle the update safely
if(isset($_POST['submit'])){
    $fname=$_POST['first_name'];
    $lname=$_POST['last_name'];
    $sid=$_POST['staff_id'];
    $postal=$_POST['postal_address'];
    $phone=$_POST['phone'];
    $email=$_POST['email'];
    $new_username=$_POST['username'];
    $pas=$_POST['password'];
    
    // The original ID is passed via hidden input to prevent overwriting the wrong user
    $cashier_id = $_POST['cashier_id']; 

    // If password is empty, keep the old one. Otherwise update it.
    if(empty($pas)) {
        $sql = "UPDATE cashier SET first_name=:fname, last_name=:lname, staff_id=:sid, 
                postal_address=:postal, phone=:phone, email=:email, username=:user 
                WHERE cashier_id=:id";
        $stmtUpdate = $con->prepare($sql);
        $result = $stmtUpdate->execute([
            ':fname'=>$fname, ':lname'=>$lname, ':sid'=>$sid, 
            ':postal'=>$postal, ':phone'=>$phone, ':email'=>$email, 
            ':user'=>$new_username, ':id'=>$cashier_id
        ]);
    } else {
        $sql = "UPDATE cashier SET first_name=:fname, last_name=:lname, staff_id=:sid, 
                postal_address=:postal, phone=:phone, email=:email, username=:user, password=:pas 
                WHERE cashier_id=:id";
        $stmtUpdate = $con->prepare($sql);
        $result = $stmtUpdate->execute([
            ':fname'=>$fname, ':lname'=>$lname, ':sid'=>$sid, 
            ':postal'=>$postal, ':phone'=>$phone, ':email'=>$email, 
            ':user'=>$new_username, ':pas'=>$pas, ':id'=>$cashier_id
        ]);
    }

    if($result) {
        header("location:http://".$_SERVER['HTTP_HOST'].dirname($_SERVER['PHP_SELF'])."/admin_cashier.php");
        exit();
    } else {
        $message1="Update Failed, Try again";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Update Cashier - Pharmacy Management System</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Figtree:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
<style>
:root {
  --pine: #123b36; --pine-soft: #1c524b; --pine-line: #2a5f57; --amber: #f2b544;
  --mist: #eef4f1; --paper: #ffffff; --ink: #17211f; --ink-soft: #5b6b67;
  --line: #d5e0dc; --danger: #b3261e; --danger-bg: #fdecea; --radius: 10px;
  --sidebar: 260px; --font: "Figtree", system-ui, -apple-system, sans-serif;
}
*, *::before, *::after { box-sizing: border-box; }
html { -webkit-text-size-adjust: 100%; }
body { margin: 0; font-family: var(--font); font-size: 16px; line-height: 1.5; color: var(--ink); background: var(--mist); }
a { color: inherit; text-decoration: none; }
:focus-visible { outline: 3px solid var(--amber); outline-offset: 2px; }

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

.card { background: var(--paper); border: 1px solid var(--line); border-radius: 14px; }
.form-card { max-width: 680px; padding: 28px; }
.form-card h2 { margin: 0 0 24px; font-size: 1.25rem; }
.grid { display: grid; grid-template-columns: 1fr 1fr; gap: 18px 16px; }
.span-2 { grid-column: 1 / -1; }
.field label { display: block; margin-bottom: 6px; font-weight: 600; font-size: 0.95rem; }
.field input { width: 100%; padding: 12px 14px; font: inherit; color: var(--ink); background: var(--paper); border: 1.5px solid var(--line); border-radius: var(--radius); transition: border-color .15s, box-shadow .15s; }
.field input:focus { outline: none; border-color: var(--pine); box-shadow: 0 0 0 3px rgba(18, 59, 54, .15); }
.field input:read-only { background-color: var(--mist); color: var(--ink-soft); cursor: not-allowed; border-color: var(--mist); }
.form-actions { display: flex; gap: 12px; margin-top: 24px; }
.btn { display: inline-block; padding: 12px 22px; font: inherit; font-weight: 600; color: #fff; background: var(--pine); border: 0; border-radius: var(--radius); cursor: pointer; transition: background-color .15s; }
.btn:hover { background: var(--pine-soft); }
.btn--ghost { color: var(--ink); background: var(--mist); }
.btn--ghost:hover { background: #dfe9e5; }
.alert { margin: 0 0 20px; padding: 12px 14px; border-radius: var(--radius); font-size: 0.95rem; font-weight: 500; border: 1px solid #f0b8b3; background: var(--danger-bg); color: var(--danger); }

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
      <li><a href="admin.php"><i class="fas fa-home"></i>Dashboard</a></li>
      <li><a href="admin_pharmacist.php"><i class="fas fa-prescription-bottle-alt"></i>Pharmacist</a></li>
      <li><a href="admin_manager.php"><i class="fas fa-user-cog"></i>Manager</a></li>
      <li><a href="admin_cashier.php" aria-current="page"><i class="fas fa-cash-register"></i>Cashier</a></li>
      <li><a href="admin_localdata.php"><i class="fas fa-database"></i>Local Data</a></li>
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
      <span class="avatar" aria-hidden="true"><?php echo htmlspecialchars(strtoupper(substr($admin_user, 0, 1))); ?></span>
      <span><?php echo htmlspecialchars($admin_user); ?></span>
    </div>
  </header>

  <main class="main">
    <h1>Update Cashier</h1>
    <p class="lead">Edit the details for <strong><?php echo htmlspecialchars($userData['first_name'] . ' ' . $userData['last_name']); ?></strong>.</p>

    <section class="card form-card">
      <?php if ($message1 !== ''): ?>
        <div class="alert" role="alert"><?php echo htmlspecialchars($message1); ?></div>
      <?php endif; ?>

      <form name="myform" action="" method="post" autocomplete="off">
        <input type="hidden" name="cashier_id" value="<?php echo htmlspecialchars($userData['cashier_id']); ?>">

        <div class="grid">
          <div class="field">
            <label for="first_name">First name</label>
            <input name="first_name" id="first_name" type="text" required pattern="[A-Za-z ]+" title="Letters and spaces only" value="<?php echo htmlspecialchars($userData['first_name']); ?>">
          </div>
          <div class="field">
            <label for="last_name">Last name</label>
            <input name="last_name" id="last_name" type="text" required pattern="[A-Za-z ]+" title="Letters and spaces only" value="<?php echo htmlspecialchars($userData['last_name']); ?>">
          </div>
          <div class="field">
            <label for="staff_id">Staff ID (Read-Only)</label>
            <input name="staff_id" id="staff_id" type="text" readonly value="<?php echo htmlspecialchars($userData['staff_id']); ?>">
          </div>
          <div class="field">
            <label for="phone">Phone</label>
            <input name="phone" id="phone" type="tel" required value="<?php echo htmlspecialchars($userData['phone']); ?>">
          </div>
          <div class="field span-2">
            <label for="email">Email</label>
            <input name="email" id="email" type="email" required value="<?php echo htmlspecialchars($userData['email']); ?>">
          </div>
          <div class="field span-2">
            <label for="postal_address">Address</label>
            <input name="postal_address" id="postal_address" type="text" required value="<?php echo htmlspecialchars($userData['postal_address']); ?>">
          </div>
          <div class="field">
            <label for="username">Username</label>
            <input name="username" id="username" type="text" required autocomplete="off" value="<?php echo htmlspecialchars($userData['username']); ?>">
          </div>
          <div class="field">
            <label for="password">New Password</label>
            <input name="password" id="password" type="password" placeholder="Leave blank to keep current" autocomplete="new-password">
            <small style="display:block; margin-top:4px; color:var(--ink-soft); font-size:0.85rem;">Only fill this in if you want to change the password.</small>
          </div>
        </div>

        <div class="form-actions">
          <button type="submit" name="submit" class="btn">Save Changes</button>
          <a href="admin_cashier.php" class="btn btn--ghost">Cancel</a>
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