<?php
session_start();
include_once('connect_db.php');

if (empty($_SESSION['username'])) { 
    header('Location: index.php'); 
    exit(); 
}
$user = $_SESSION['username'];

function safe_h($val) {
    return htmlspecialchars((string)($val ?? ''), ENT_QUOTES, 'UTF-8');
}

try {
    $rows = $con->query("SELECT * FROM suppliers ORDER BY supplier_id DESC")->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $rows = [];
    $message1 = "Error loading suppliers: " . $e->getMessage();
}

$page_title = "Suppliers - MedFlow PMS";
$topbar_title = "Manager Dashboard";

include 'includes/head.php';
include 'includes/sidebar_manager.php';
include 'includes/header.php';
?>

<main class="main">
    <h1>Suppliers Directory</h1>
    <p class="lead">Manage your relationships with medicine manufacturers and distributors.</p>

    <?php if (isset($message1)): ?>
      <div class="alert alert--error"><?php echo safe_h($message1); ?></div>
    <?php endif; ?>

    <section class="card">
      <div class="card-head">
        <h2>All Suppliers <span class="count">(<?php echo count($rows); ?>)</span></h2>
        <?php if (count($rows) > 0): ?>
        <div class="search">
          <i class="fas fa-search" aria-hidden="true"></i>
          <input type="search" id="filter" placeholder="Search company, contact, phone..." aria-label="Search suppliers">
        </div>
        <?php endif; ?>
      </div>

      <?php if (count($rows) === 0): ?>
        <div class="empty">
          <i class="fas fa-truck-loading"></i>
          <p>No suppliers found in the system.</p>
        </div>
      <?php else: ?>
      <div class="table-wrap">
        <table id="supplierTable">
          <thead>
            <tr>
              <th>Company Name</th>
              <th>ID</th>
              <th>Contact Person</th>
              <th>Phone</th>
              <th>Email</th>
            </tr>
          </thead>
          <tbody>
          <?php foreach ($rows as $row):
              $company = trim((string) ($row['company_name'] ?? $row['name'] ?? 'Unknown Company'));
              $contact = trim((string) ($row['contact_name'] ?? $row['contact_person'] ?? ''));
              $phone   = trim((string) ($row['phone'] ?? ''));
              $email   = trim((string) ($row['email'] ?? ''));
          ?>
            <tr>
              <td>
                <strong><?php echo safe_h($company); ?></strong>
              </td>
              <td>
                <span style="background:var(--mist); padding:3px 8px; border-radius:4px; font-size:0.85rem;">
                  #<?php echo safe_h($row['supplier_id'] ?? $row['id'] ?? 'N/A'); ?>
                </span>
              </td>
              <td><?php echo $contact !== '' ? safe_h($contact) : '&mdash;'; ?></td>
              <td><?php echo $phone !== '' ? safe_h($phone) : '&mdash;'; ?></td>
              <td><?php echo $email !== '' ? safe_h($email) : '&mdash;'; ?></td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <div class="no-match" id="noMatch" style="display:none; padding:20px; text-align:center; color:var(--ink-soft);">No suppliers match your search.</div>
      <?php endif; ?>
    </section>
</main>

<script>
(function () {
  // Table search
  var filter = document.getElementById('filter');
  if (filter) {
    var rows = document.querySelectorAll('#supplierTable tbody tr');
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

<?php include 'includes/footer.php'; ?>