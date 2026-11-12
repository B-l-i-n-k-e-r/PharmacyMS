<?php
session_start();
include_once('../connect_db.php');
date_default_timezone_set('Africa/Nairobi');

if (isset($_SESSION['username'])) {
    $id    = $_SESSION['pharmacist_id'];
    $fname = $_SESSION['first_name'];
    $user  = $_SESSION['username'];
} else {
    header("location:index.php");
    exit();
}

function h($v) { return htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8'); }
function initial($s) { $c = strtoupper(substr(trim((string) $s), 0, 1)); return $c !== '' ? $c : '?'; }

$message1 = '';

// Fetch all prescriptions
$prescriptions = [];
try {
    $prescriptions = $con->query("SELECT * FROM prescription ORDER BY date DESC")->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Load prescriptions failed: " . $e->getMessage());
    $message1 = "Could not load prescriptions.";
}

// Next invoice number
try {
    $invoice   = $con->query("SELECT 1+MAX(invoice_id) AS next_id FROM invoice")->fetch(PDO::FETCH_ASSOC);
    $invoiceNo = empty($invoice['next_id']) ? 10 : $invoice['next_id'];
    $_SESSION['invoice'] = $invoiceNo;
} catch (PDOException $e) {
    $invoiceNo = 10;
}

// Drugs for the dropdown
try {
    $drugs = $con->query("SELECT drug_name FROM stock ORDER BY drug_name")->fetchAll(PDO::FETCH_COLUMN);
} catch (PDOException $e) {
    $drugs = [];
}

// Stats
$today = date('Y-m-d');
$month = date('Y-m');
$today_count = 0;
$month_count = 0;
foreach ($prescriptions as $p) {
    $ts = strtotime($p['date']);
    if (date('Y-m-d', $ts) === $today) { $today_count++; }
    if (date('Y-m', $ts) === $month)   { $month_count++; }
}

$page_title = "Prescriptions - Pharmacy Management System";
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
.mini-avatar { flex:none; display:grid; place-items:center; width:36px; height:36px; border-radius:50%; background:var(--mist); color:var(--pine); font-weight:700; font-size:.85rem; }
.pill { display:inline-block; padding:3px 10px; border-radius:999px; background:var(--mist); font-size:.82rem; font-weight:600; }
.pill--today { background:var(--ok-bg); color:var(--ok); margin-left:8px; }

.form-card { max-width:760px; padding:28px; }
.form-head { display:flex; align-items:center; justify-content:space-between; gap:12px; flex-wrap:wrap; margin-bottom:22px; }
.form-head h2 { margin:0; font-size:1.25rem; }
.invoice-chip { display:inline-flex; align-items:center; gap:8px; padding:6px 14px; border-radius:999px; background:var(--warn-bg); color:var(--warn); font-weight:600; font-size:.88rem; }
.form-section { display:flex; align-items:center; gap:10px; margin:6px 0 0; font-size:.95rem; font-weight:700; color:var(--pine); }
.form-section i { display:grid; place-items:center; width:30px; height:30px; border-radius:9px; background:var(--mist); font-size:.85rem; }
.form-section + .grid { margin-top:14px; }
.divider { height:1px; background:var(--line); margin:26px 0 20px; }
.hint { margin:6px 0 0; font-size:.82rem; color:var(--warn); }
</style>

<?php
include '../includes/sidebar_pharmacist.php';
include '../includes/header.php';
?>

<main class="main">
    <div class="page-head">
      <h1>Prescriptions</h1>
      <p>View patient history or create a new prescription.</p>
    </div>

    <?php if ($message1 !== ''): ?>
      <div class="alert" role="alert"><?php echo h($message1); ?></div>
    <?php endif; ?>

    <!-- Stats -->
    <section class="stats">
      <div class="stat">
        <span class="stat-icon"><i class="fas fa-file-prescription"></i></span>
        <div>
          <div class="stat-label">Total Prescriptions</div>
          <div class="stat-value"><?php echo number_format(count($prescriptions)); ?></div>
        </div>
      </div>
      <div class="stat">
        <span class="stat-icon stat-icon--green"><i class="fas fa-calendar-day"></i></span>
        <div>
          <div class="stat-label">Today</div>
          <div class="stat-value"><?php echo number_format($today_count); ?></div>
        </div>
      </div>
      <div class="stat">
        <span class="stat-icon stat-icon--amber"><i class="fas fa-calendar-alt"></i></span>
        <div>
          <div class="stat-label">This Month</div>
          <div class="stat-value"><?php echo number_format($month_count); ?></div>
        </div>
      </div>
      <div class="stat">
        <span class="stat-icon"><i class="fas fa-hashtag"></i></span>
        <div>
          <div class="stat-label">Next Invoice</div>
          <div class="stat-value">#<?php echo h($invoiceNo); ?></div>
        </div>
      </div>
    </section>

    <div class="tabs" role="tablist" aria-label="Prescription actions">
      <button type="button" class="tab" role="tab" id="tab_1" aria-controls="content_1" aria-selected="true">View Prescriptions</button>
      <button type="button" class="tab" role="tab" id="tab_2" aria-controls="content_2" aria-selected="false">Create New</button>
    </div>

    <!-- View Prescriptions -->
    <section class="panel card" id="content_1" role="tabpanel" aria-labelledby="tab_1">
      <div class="card-head">
        <h2>All Prescriptions <span class="count">(<?php echo count($prescriptions); ?>)</span></h2>
        <?php if (count($prescriptions) > 0): ?>
        <div class="search">
          <i class="fas fa-search" aria-hidden="true"></i>
          <input type="search" id="filter" placeholder="Search customer, number, invoice..." aria-label="Search prescriptions">
        </div>
        <?php endif; ?>
      </div>

      <?php if (count($prescriptions) === 0): ?>
        <div class="empty">
          <i class="fas fa-file-medical"></i>
          <p>No prescriptions found. Create the first one to get started.</p>
          <button type="button" class="btn" data-go="tab_2"><i class="fas fa-plus"></i> Create New</button>
        </div>
      <?php else: ?>
      <div class="table-wrap">
        <table id="presTable">
          <thead>
            <tr>
              <th>Prescription</th>
              <th>Customer</th>
              <th>Invoice</th>
              <th>Date</th>
              <th class="actions">Actions</th>
            </tr>
          </thead>
          <tbody>
          <?php foreach ($prescriptions as $row):
              $ts = strtotime($row['date']);
              $isToday = (date('Y-m-d', $ts) === $today);
          ?>
            <tr>
              <td><strong>#<?php echo h($row['prescription_id']); ?></strong></td>
              <td>
                <div class="person">
                  <span class="mini-avatar" aria-hidden="true"><?php echo h(initial($row['customer_name'])); ?></span>
                  <?php echo h($row['customer_name']); ?>
                </div>
              </td>
              <td><span class="pill">#<?php echo h($row['invoice_id']); ?></span></td>
              <td>
                <?php echo h(date('M j, Y', $ts)); ?>
                <?php if ($isToday): ?><span class="pill pill--today">Today</span><?php endif; ?>
              </td>
              <td class="actions">
                <a class="icon-btn icon-btn--delete" title="Delete" aria-label="Delete prescription <?php echo h($row['prescription_id']); ?>"
                   href="delete_prescription.php?prescription_id=<?php echo urlencode($row['prescription_id']); ?>"
                   onclick="return confirm('Are you sure you want to delete this prescription?');"><i class="fas fa-trash"></i></a>
              </td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <div class="no-match" id="noMatch">No prescriptions match your search.</div>
      <?php endif; ?>
    </section>

    <!-- Create New Prescription -->
    <section class="panel card form-card" id="content_2" role="tabpanel" aria-labelledby="tab_2" hidden>
      <div class="form-head">
        <h2>New Prescription</h2>
        <span class="invoice-chip"><i class="fas fa-file-invoice"></i> Invoice #<?php echo h($invoiceNo); ?> will be generated</span>
      </div>

      <form name="myform" action="invoice.php" method="post" autocomplete="off">
        <div class="form-section"><i class="fas fa-user"></i> Customer Details</div>
        <div class="grid">
          <div class="field">
            <label for="customer_id">Customer ID</label>
            <input name="customer_id" id="customer_id" type="text" required>
          </div>
          <div class="field">
            <label for="customer_name">Customer Name</label>
            <input name="customer_name" id="customer_name" type="text" required>
          </div>
          <div class="field">
            <label for="age">Age</label>
            <input name="age" id="age" type="number" min="0" max="130" required>
          </div>
          <div class="field">
            <label for="sex">Gender</label>
            <select name="sex" id="sex" required>
              <option value="">Select...</option>
              <option value="Male">Male</option>
              <option value="Female">Female</option>
            </select>
          </div>
          <div class="field">
            <label for="postal_address">Address</label>
            <input name="postal_address" id="postal_address" type="text" required>
          </div>
          <div class="field">
            <label for="phone">Phone</label>
            <input name="phone" id="phone" type="tel" required>
          </div>
        </div>

        <div class="divider"></div>

        <div class="form-section"><i class="fas fa-pills"></i> Drug Details</div>
        <div class="grid">
          <div class="field span-2">
            <label for="drug_name">Select Drug</label>
            <select name="drug_name" id="drug_name" required>
              <option value="">Select Drug...</option>
              <?php foreach ($drugs as $drug): ?>
                <option value="<?php echo h($drug); ?>"><?php echo h($drug); ?></option>
              <?php endforeach; ?>
            </select>
            <?php if (empty($drugs)): ?>
              <p class="hint"><i class="fas fa-exclamation-circle"></i> No medicines in stock yet. Add some on the Stock page first.</p>
            <?php endif; ?>
          </div>
          <div class="field">
            <label for="strength">Strength</label>
            <input name="strength" id="strength" type="text" placeholder="e.g. 500mg">
          </div>
          <div class="field">
            <label for="dose">Dose</label>
            <input name="dose" id="dose" type="text" placeholder="e.g. 2 times a day">
          </div>
          <div class="field">
            <label for="quantity">Quantity</label>
            <input name="quantity" id="quantity" type="number" min="1" step="1" required>
          </div>
        </div>

        <div class="form-actions">
          <button type="submit" name="submit" class="btn"><i class="fas fa-check"></i> Create Prescription</button>
          <button type="button" class="btn btn--ghost" data-go="tab_1">Cancel</button>
        </div>
      </form>
    </section>
</main>

<script>
(function () {
  // Tabs
  var tabs = [document.getElementById('tab_1'), document.getElementById('tab_2')];
  function show(id) {
    tabs.forEach(function (t) {
      var on = t.id === id;
      t.setAttribute('aria-selected', on ? 'true' : 'false');
      document.getElementById(t.getAttribute('aria-controls')).hidden = !on;
    });
  }
  tabs.forEach(function (t) { t.addEventListener('click', function () { show(t.id); }); });
  document.querySelectorAll('[data-go]').forEach(function (b) {
    b.addEventListener('click', function () { show(b.getAttribute('data-go')); });
  });

  // Table search
  var filter = document.getElementById('filter');
  if (filter) {
    var rows = document.querySelectorAll('#presTable tbody tr');
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