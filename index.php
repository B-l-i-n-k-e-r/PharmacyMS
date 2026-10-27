<?php
include_once 'connect_db.php';

$message  = '';   // Error message shown above the form
$username = '';   // Kept so the field is refilled after a failed login
$position = '';   // Kept so the chosen role stays selected

if (isset($_POST['submit'])) {
    $username = $_POST['username'];
    $password = $_POST['password'];
    $position = $_POST['position'] ?? '';

    switch ($position) {
        case 'Admin':
            // Use PDO prepared statements to protect against SQL Injection
            $stmt = $con->prepare("SELECT admin_id, username FROM admin WHERE username = :username AND password = :password");
            $stmt->execute([':username' => $username, ':password' => $password]);
            $row = $stmt->fetch(PDO::FETCH_NUM); // Fetch as numeric array to preserve $row[0], $row[1] indices

            if ($row) {
                if (session_status() == PHP_SESSION_NONE) { session_start(); }
                $_SESSION['admin_id'] = $row[0];
                $_SESSION['username'] = $row[1];
                header("Location: http://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . "/admin.php");
                exit();
            } else {
                $message = "Incorrect username or password. Check your details and try again.";
            }
            break;

        case 'Pharmacist':
            $stmt = $con->prepare("SELECT pharmacist_id, first_name, last_name, staff_id, username FROM pharmacist WHERE username = :username AND password = :password");
            $stmt->execute([':username' => $username, ':password' => $password]);
            $row = $stmt->fetch(PDO::FETCH_NUM);

            if ($row) {
                if (session_status() == PHP_SESSION_NONE) { session_start(); }
                $_SESSION['pharmacist_id'] = $row[0];
                $_SESSION['first_name']    = $row[1];
                $_SESSION['last_name']     = $row[2];
                $_SESSION['staff_id']      = $row[3];
                $_SESSION['username']      = $row[4];
                header("Location: http://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . "/pharmacist.php");
                exit();
            } else {
                $message = "Incorrect username or password. Check your details and try again.";
            }
            break;

        case 'Cashier':
            $stmt = $con->prepare("SELECT cashier_id, first_name, last_name, staff_id, username FROM cashier WHERE username = :username AND password = :password");
            $stmt->execute([':username' => $username, ':password' => $password]);
            $row = $stmt->fetch(PDO::FETCH_NUM);

            if ($row) {
                if (session_status() == PHP_SESSION_NONE) { session_start(); }
                $_SESSION['cashier_id'] = $row[0];
                $_SESSION['first_name'] = $row[1];
                $_SESSION['last_name']  = $row[2];
                $_SESSION['staff_id']   = $row[3];
                $_SESSION['username']   = $row[4];
                header("Location: http://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . "/cashier.php");
                exit();
            } else {
                $message = "Incorrect username or password. Check your details and try again.";
            }
            break;

        case 'Manager':
            $stmt = $con->prepare("SELECT manager_id, first_name, last_name, staff_id, username FROM manager WHERE username = :username AND password = :password");
            $stmt->execute([':username' => $username, ':password' => $password]);
            $row = $stmt->fetch(PDO::FETCH_NUM);

            if ($row) {
                if (session_status() == PHP_SESSION_NONE) { session_start(); }
                $_SESSION['manager_id'] = $row[0];
                $_SESSION['first_name'] = $row[1];
                $_SESSION['last_name']  = $row[2];
                $_SESSION['staff_id']   = $row[3];
                $_SESSION['username']   = $row[4];
                header("Location: http://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . "/manager.php");
                exit();
            } else {
                $message = "Incorrect username or password. Check your details and try again.";
            }
            break;

        default:
            $message = "Choose a role to sign in as.";
            break;
    }
}

$roles = ['Admin', 'Manager', 'Pharmacist', 'Cashier'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Sign in | Pharmacy Management System</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Figtree:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
:root {
  --pine: #123b36;
  --pine-soft: #1c524b;
  --amber: #f2b544;
  --mist: #eef4f1;
  --paper: #ffffff;
  --ink: #17211f;
  --ink-soft: #5b6b67;
  --line: #d5e0dc;
  --danger: #b3261e;
  --danger-bg: #fdecea;
  --radius: 10px;
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
  background: var(--paper);
}

:focus-visible { outline: 3px solid var(--amber); outline-offset: 2px; }

/* Layout */
.auth {
  min-height: 100vh;
  display: grid;
  grid-template-columns: minmax(320px, 5fr) 6fr;
}

/* Brand panel */
.auth-brand {
  position: relative;
  overflow: hidden;
  background: var(--pine);
  color: #fff;
  padding: 64px 56px;
  display: flex;
  flex-direction: column;
  justify-content: flex-end;
}
.auth-brand h1 {
  position: relative;
  z-index: 1;
  margin: 0 0 12px;
  font-size: clamp(2rem, 3.4vw, 3rem);
  line-height: 1.1;
  font-weight: 700;
  letter-spacing: -0.02em;
  max-width: 12ch;
}
.auth-brand p {
  position: relative;
  z-index: 1;
  margin: 0;
  max-width: 34ch;
  color: #bcd3cd;
  font-size: 1.05rem;
}

/* Capsule shapes */
.pill {
  position: absolute;
  width: 260px;
  height: 96px;
  border-radius: 999px;
  background: linear-gradient(90deg, var(--amber) 0 50%, #f7f3e8 50% 100%);
  opacity: 0.95;
}
.pill--one   { top: 9%;  left: 14%; transform: rotate(-24deg); }
.pill--two   { top: 26%; left: 48%; width: 200px; height: 76px; transform: rotate(18deg);
               background: linear-gradient(90deg, var(--pine-soft) 0 50%, #f7f3e8 50% 100%);
               box-shadow: inset 0 0 0 2px #2f6d64; }
.pill--three { top: 42%; left: 6%;  width: 170px; height: 64px; transform: rotate(-8deg);
               background: linear-gradient(90deg, #f7f3e8 0 50%, var(--amber) 50% 100%); }

/* Form panel */
.auth-panel {
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 48px 24px;
  background: var(--paper);
}
.auth-card { width: 100%; max-width: 400px; }
.auth-logo { display: block; max-height: 64px; width: auto; margin: 0 0 28px; }
.auth-card h2 { margin: 0 0 6px; font-size: 1.75rem; line-height: 1.2; letter-spacing: -0.01em; }
.auth-card .lead { margin: 0 0 28px; color: var(--ink-soft); }

/* Error message */
.alert {
  margin: 0 0 20px;
  padding: 12px 14px;
  border-radius: var(--radius);
  border: 1px solid #f0b8b3;
  background: var(--danger-bg);
  color: var(--danger);
  font-size: 0.95rem;
  font-weight: 500;
}

/* Role picker */
.role-group { border: 0; margin: 0 0 22px; padding: 0; }
.role-group legend { padding: 0; margin-bottom: 8px; font-weight: 600; font-size: 0.95rem; }
.role-options { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }
.role-options input { position: absolute; opacity: 0; pointer-events: none; }
.role-options label {
  display: block;
  padding: 11px 12px;
  text-align: center;
  font-weight: 500;
  color: var(--ink);
  background: var(--mist);
  border: 1.5px solid transparent;
  border-radius: var(--radius);
  cursor: pointer;
  transition: background-color .15s, border-color .15s, color .15s;
}
.role-options label:hover { border-color: var(--line); }
.role-options input:checked + label { background: var(--pine); color: #fff; }
.role-options input:focus-visible + label { outline: 3px solid var(--amber); outline-offset: 2px; }

/* Fields */
.field { margin-bottom: 18px; }
.field label { display: block; margin-bottom: 6px; font-weight: 600; font-size: 0.95rem; }
.field input {
  width: 100%;
  padding: 12px 14px;
  font: inherit;
  color: var(--ink);
  background: var(--paper);
  border: 1.5px solid var(--line);
  border-radius: var(--radius);
  transition: border-color .15s, box-shadow .15s;
}
.field input:focus {
  outline: none;
  border-color: var(--pine);
  box-shadow: 0 0 0 3px rgba(18, 59, 54, .15);
}
.password-wrap { position: relative; }
.password-wrap input { padding-right: 76px; }
.toggle-pass {
  position: absolute;
  top: 50%;
  right: 8px;
  transform: translateY(-50%);
  padding: 6px 10px;
  font: inherit;
  font-size: 0.875rem;
  font-weight: 600;
  color: var(--pine);
  background: transparent;
  border: 0;
  border-radius: 6px;
  cursor: pointer;
}
.toggle-pass:hover { background: var(--mist); }

/* Button */
.btn {
  display: block;
  width: 100%;
  margin-top: 8px;
  padding: 13px 18px;
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

/* Small screens */
@media (max-width: 860px) {
  .auth { grid-template-columns: 1fr; }
  .auth-brand { padding: 32px 24px; justify-content: flex-start; }
  .auth-brand h1 { font-size: 1.6rem; max-width: none; margin-bottom: 4px; }
  .auth-brand p { font-size: 0.95rem; }
  .pill--one   { top: -20px; left: auto; right: -60px; width: 200px; height: 74px; }
  .pill--two   { top: 40px;  left: auto; right: -90px; width: 160px; height: 60px; }
  .pill--three { display: none; }
  .auth-panel { align-items: flex-start; padding: 32px 20px 48px; }
}
</style>
</head>
<body>
  <main class="auth">

    <section class="auth-brand">
      <span class="pill pill--one" aria-hidden="true"></span>
      <span class="pill pill--two" aria-hidden="true"></span>
      <span class="pill pill--three" aria-hidden="true"></span>
      <h1>Pharmacy Management System</h1>
      <p>Manage stock, prescriptions and sales in one place.</p>
    </section>

    <section class="auth-panel">
      <div class="auth-card">
        <img class="auth-logo" src="images/ulg.png" alt="Logo">

        <h2>Sign in</h2>
        <p class="lead">Choose your role, then enter your username and password.</p>

        <?php if ($message !== ''): ?>
          <div class="alert" role="alert"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>

        <form method="post" action="index.php" autocomplete="on">

          <fieldset class="role-group">
            <legend>Sign in as</legend>
            <div class="role-options">
              <?php foreach ($roles as $i => $role): ?>
                <input type="radio" name="position" id="role<?= $i ?>"
                       value="<?= $role ?>" required
                       <?= $position === $role ? 'checked' : '' ?>>
                <label for="role<?= $i ?>"><?= $role ?></label>
              <?php endforeach; ?>
            </div>
          </fieldset>

          <div class="field">
            <label for="username">Username</label>
            <input type="text" id="username" name="username"
                   value="<?= htmlspecialchars($username) ?>"
                   autocomplete="username" required>
          </div>

          <div class="field">
            <label for="password">Password</label>
            <div class="password-wrap">
              <input type="password" id="password" name="password"
                     autocomplete="current-password" required>
              <button type="button" class="toggle-pass" id="togglePass"
                      aria-controls="password" aria-pressed="false">Show</button>
            </div>
          </div>

          <button type="submit" name="submit" class="btn">Sign in</button>
        </form>
      </div>
    </section>

  </main>

  <script>
    (function () {
      var btn = document.getElementById('togglePass');
      var input = document.getElementById('password');
      btn.addEventListener('click', function () {
        var show = input.type === 'password';
        input.type = show ? 'text' : 'password';
        btn.textContent = show ? 'Hide' : 'Show';
        btn.setAttribute('aria-pressed', show ? 'true' : 'false');
      });
    })();
  </script>
</body>
</html>