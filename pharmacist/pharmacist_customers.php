<?php
session_start();
include_once('../connect_db.php');

if (!isset($_SESSION['username'])) {
    header("location:../index.php");
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

$page_title = "Customers - Pharmacy Management System";
$topbar_title = "Pharmacist Dashboard";

include '../includes/head.php';
?>

<!-- CUSTOM STYLES FOR THIS SPECIFIC PAGE -->
<style>
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

.person { display:flex; align-items:center; gap:12px; }
.mini-avatar { flex:none; display:grid; place-items:center; width:38px; height:38px; border-radius:50%; background:var(--pine); color:#fff; font-weight:700; font-size:.9rem; }
.contact { display:inline-flex; align-items:center; gap:8px; color:var(--ink); }
.contact i { color:var(--ink-soft); font-size:.85rem; width:14px; text-align:center; }
a.contact:hover { color:var(--pine); text-decoration:underline; }
.none { color:var(--ink-soft); }
.pill { display:inline-block; padding:3px 10px; border-radius:999px; background:var(--mist); font-size:.82rem; font-weight:600; color:var(--ink-soft); }
</style>

<?php
include '../includes/sidebar_pharmacist.php';
include '../includes/header.php';
?>

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

<?php include '../includes/footer.php'; ?>