<?php
session_start();
include_once('../connect_db.php');

if (empty($_SESSION['username'])) { 
    header('Location: index.php'); 
    exit(); 
}
$user = $_SESSION['username'];

function safe_h($val) {
    return htmlspecialchars((string)($val ?? ''), ENT_QUOTES, 'UTF-8');
}

function get_initial($str) {
    $c = strtoupper(substr(trim((string)$str), 0, 1)); 
    return $c !== '' ? $c : '?';
}

try {
    $rows = $con->query("SELECT * FROM customers ORDER BY customer_id DESC")->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $rows = [];
    $message1 = "Error: " . $e->getMessage();
}

$page_title = "Customers - MedFlow PMS";
$topbar_title = "Manager Dashboard";

include '../includes/head.php';
include '../includes/sidebar_manager.php';
include '../includes/header.php';
?>

<main class="main">
    <h1>Customer Directory</h1>
    <p class="lead">Manage patient profiles and contact details.</p>

    <?php if (isset($message1)): ?>
      <div class="alert alert--error"><?php echo safe_h($message1); ?></div>
    <?php endif; ?>

    <section class="card">
      <div class="card-head">
        <h2>All Customers <span class="count">(<?php echo count($rows); ?>)</span></h2>
        <?php if (count($rows) > 0): ?>
        <div class="search">
          <i class="fas fa-search" aria-hidden="true"></i>
          <input type="search" id="filter" placeholder="Search name, phone, email..." aria-label="Search customers">
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
              <th>Joined</th>
            </tr>
          </thead>
          <tbody>
          <?php foreach ($rows as $row):
              $full  = trim(($row['first_name'] ?? '') . ' ' . ($row['last_name'] ?? ''));
              $phone = trim((string) ($row['phone'] ?? ''));
              $email = trim((string) ($row['email'] ?? ''));
              $date  = $row['created_at'] ?? $row['date'] ?? null;
          ?>
            <tr>
              <td>
                <div style="display:flex; align-items:center; gap:12px;">
                  <span style="display:grid; place-items:center; width:36px; height:36px; border-radius:50%; background:var(--pine); color:#fff; font-weight:700; font-size:0.85rem;">
                    <?php echo safe_h(get_initial($row['first_name'] ?? '')); ?>
                  </span>
                  <strong><?php echo safe_h($full !== '' ? $full : 'Unnamed'); ?></strong>
                </div>
              </td>
              <td><span style="background:var(--mist); padding:3px 8px; border-radius:4px; font-size:0.85rem;">#<?php echo safe_h($row['customer_id']); ?></span></td>
              <td><?php echo $phone !== '' ? safe_h($phone) : '&mdash;'; ?></td>
              <td><?php echo $email !== '' ? safe_h($email) : '&mdash;'; ?></td>
              <td><?php echo $date ? date('M j, Y', strtotime($date)) : '&mdash;'; ?></td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <div class="no-match" id="noMatch" style="display:none; padding:20px; text-align:center; color:var(--ink-soft);">No customers match your search.</div>
      <?php endif; ?>
    </section>
</main>

<script>
(function () {
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