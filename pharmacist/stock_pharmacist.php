<?php
session_start();
include_once('../connect_db.php');
date_default_timezone_set('Africa/Nairobi');

if (isset($_SESSION['username'])) {
    $id   = $_SESSION['pharmacist_id'];
    $user = $_SESSION['username'];
} else {
    header("location:index.php");
    exit();
}

function h($v) { return htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8'); }

$currency      = 'KES';
$low_threshold = 10; 
$message1      = '';
$form = ['drug_name'=>'','category'=>'','description'=>'','company'=>'','supplier'=>'','quantity'=>'','cost'=>''];

// ---------- Add medicine ----------
if (isset($_POST['submit'])) {
    foreach ($form as $k => $v) {
        $form[$k] = trim($_POST[$k] ?? '');
    }

    if (in_array('', $form, true)) {
        $message1 = "Please fill in every field.";
    } elseif (!ctype_digit($form['quantity']) || (int) $form['quantity'] < 1) {
        $message1 = "Quantity must be a whole number of 1 or more.";
    } elseif (!is_numeric($form['cost']) || $form['cost'] < 0) {
        $message1 = "Unit cost must be zero or more.";
    } else {
        try {
            $stmt = $con->prepare(
                "INSERT INTO stock (drug_name, category, description, company, supplier, quantity, cost, status, date_supplied)
                 VALUES (:sname, :cat, :des, :com, :sup, :qua, :cost, :sta, NOW())"
            );
            $stmt->execute([
                ':sname' => $form['drug_name'], ':cat' => $form['category'], ':des' => $form['description'],
                ':com'   => $form['company'],   ':sup' => $form['supplier'], ':qua' => (int) $form['quantity'],
                ':cost'  => $form['cost'],      ':sta' => 'Available'
            ]);
            header("location:stock_pharmacist.php?added=1");
            exit();
        } catch (PDOException $e) {
            error_log("Add stock failed: " . $e->getMessage());
            $message1 = "Could not save the medicine. Please try again.";
        }
    }
}

// ---------- Fetch stock ----------
$stocks = [];
try {
    $stocks = $con->query("SELECT * FROM stock ORDER BY stock_id DESC")->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Load stock failed: " . $e->getMessage());
    $message1 = $message1 ?: "Could not load the stock list.";
}

// ---------- Stats ----------
$total_units = 0; $low = 0; $out = 0; $value = 0;
foreach ($stocks as $i => $s) {
    $q = (int) $s['quantity'];
    $total_units += max($q, 0);
    $value       += max($q, 0) * (float) $s['cost'];
    if ($q <= 0)                  { $state = 'out'; $out++; }
    elseif ($q <= $low_threshold) { $state = 'low'; $low++; }
    else                          { $state = 'ok'; }
    $stocks[$i]['_state'] = $state;
}

$hasError = ($message1 !== '');
$added    = isset($_GET['added']);

$page_title = "Manage Stock - Pharmacy Management System";
$topbar_title = "Pharmacist Dashboard";

include '../includes/head.php';
?>

<!-- CUSTOM STYLES FOR THIS SPECIFIC PAGE (Stats, Chips, Table) -->
<style>
.page-head { margin-bottom:24px; }
.page-head h1 { margin:0 0 4px; font-size:clamp(1.6rem,2.6vw,2.1rem); line-height:1.15; letter-spacing:-.02em; }
.page-head p { margin:0; color:var(--ink-soft); }
.stats { display:grid; grid-template-columns:repeat(auto-fit,minmax(210px,1fr)); gap:16px; margin-bottom:26px; }
.stat { display:flex; align-items:center; gap:16px; padding:18px 20px; background:var(--paper); border:1px solid var(--line); border-radius:16px; }
.stat-icon { flex:none; display:grid; place-items:center; width:46px; height:46px; border-radius:14px; background:var(--mist); color:var(--pine); font-size:1.1rem; }
.stat-icon--amber { background:var(--warn-bg); color:var(--warn); }
.stat-icon--red { background:var(--danger-bg); color:var(--danger); }
.stat-icon--green { background:var(--ok-bg); color:var(--ok); }
.stat-label { font-size:.75rem; font-weight:600; letter-spacing:.05em; text-transform:uppercase; color:var(--ink-soft); }
.stat-value { font-size:1.5rem; font-weight:700; line-height:1.2; }
.stat-value small { font-size:.9rem; font-weight:600; color:var(--ink-soft); }

.tools { display:flex; align-items:center; gap:12px; flex-wrap:wrap; }
.chips { display:flex; gap:8px; flex-wrap:wrap; }
.chip { padding:6px 14px; font:inherit; font-size:.86rem; font-weight:600; color:var(--ink-soft); background:var(--paper); border:1.5px solid var(--line); border-radius:999px; cursor:pointer; transition:all .15s; }
.chip:hover { border-color:var(--pine); color:var(--pine); }
.chip[aria-pressed="true"] { background:var(--pine); border-color:var(--pine); color:#fff; }

.sub { display:block; font-size:.8rem; color:var(--ink-soft); }
.qty { font-weight:700; }
.qty--low { color:var(--warn); }
.qty--out { color:var(--danger); }
.badge { display:inline-flex; align-items:center; gap:6px; padding:4px 10px; border-radius:999px; font-size:.8rem; font-weight:600; }
.badge::before { content:""; width:6px; height:6px; border-radius:50%; background:currentColor; }
.badge--ok { background:var(--ok-bg); color:var(--ok); }
.badge--warn { background:var(--warn-bg); color:var(--warn); }
.badge--bad { background:var(--danger-bg); color:var(--danger); }

.form-sub { margin:0 0 22px; color:var(--ink-soft); font-size:.92rem; }
.form-card h2 { margin:0 0 4px; font-size:1.25rem; }
</style>

<?php
include '../includes/sidebar_pharmacist.php';
include '../includes/header.php';
?>

<main class="main">
    <div class="page-head">
      <h1>Manage Stock</h1>
      <p>View and add medicines to the pharmacy inventory.</p>
    </div>

    <?php if ($added): ?>
      <div class="alert alert--ok" role="status">Medicine added successfully.</div>
    <?php endif; ?>
    <?php if ($message1 !== ''): ?>
      <div class="alert alert--error" role="alert"><?php echo h($message1); ?></div>
    <?php endif; ?>

    <!-- Stats -->
    <section class="stats">
      <div class="stat">
        <span class="stat-icon"><i class="fas fa-pills"></i></span>
        <div>
          <div class="stat-label">Medicines</div>
          <div class="stat-value"><?php echo number_format(count($stocks)); ?></div>
        </div>
      </div>
      <div class="stat">
        <span class="stat-icon stat-icon--amber"><i class="fas fa-exclamation-triangle"></i></span>
        <div>
          <div class="stat-label">Low Stock</div>
          <div class="stat-value"><?php echo number_format($low); ?></div>
        </div>
      </div>
      <div class="stat">
        <span class="stat-icon stat-icon--red"><i class="fas fa-ban"></i></span>
        <div>
          <div class="stat-label">Out of Stock</div>
          <div class="stat-value"><?php echo number_format($out); ?></div>
        </div>
      </div>
      <div class="stat">
        <span class="stat-icon stat-icon--green"><i class="fas fa-coins"></i></span>
        <div>
          <div class="stat-label">Stock Value</div>
          <div class="stat-value"><small><?php echo h($currency); ?></small> <?php echo number_format($value); ?></div>
        </div>
      </div>
    </section>

    <div class="tabs" role="tablist" aria-label="Stock actions">
      <button type="button" class="tab" role="tab" id="tab_1" aria-controls="content_1"
              aria-selected="<?php echo $hasError ? 'false' : 'true'; ?>">View Stock</button>
      <button type="button" class="tab" role="tab" id="tab_2" aria-controls="content_2"
              aria-selected="<?php echo $hasError ? 'true' : 'false'; ?>">Add Medicine</button>
    </div>

    <!-- View Stock -->
    <section class="panel card" id="content_1" role="tabpanel" aria-labelledby="tab_1" <?php echo $hasError ? 'hidden' : ''; ?>>
      <div class="card-head">
        <h2>All Medicines <span class="count">(<?php echo count($stocks); ?>)</span></h2>
        <?php if (count($stocks) > 0): ?>
        <div class="tools">
          <div class="chips" role="group" aria-label="Filter by stock level">
            <button type="button" class="chip" data-state="all" aria-pressed="true">All</button>
            <button type="button" class="chip" data-state="low" aria-pressed="false">Low (<?php echo $low; ?>)</button>
            <button type="button" class="chip" data-state="out" aria-pressed="false">Out (<?php echo $out; ?>)</button>
          </div>
          <div class="search">
            <i class="fas fa-search" aria-hidden="true"></i>
            <input type="search" id="filter" placeholder="Search name, category, supplier..." aria-label="Search stock">
          </div>
        </div>
        <?php endif; ?>
      </div>

      <?php if (count($stocks) === 0): ?>
        <div class="empty">
          <i class="fas fa-box-open"></i>
          <p>No medicines in stock. Add the first one to get started.</p>
          <button type="button" class="btn" data-go="tab_2"><i class="fas fa-plus"></i> Add Medicine</button>
        </div>
      <?php else: ?>
      <div class="table-wrap">
        <table id="stockTable">
          <thead>
            <tr>
              <th>ID</th>
              <th>Medicine</th>
              <th>Supplier</th>
              <th>Qty</th>
              <th>Unit Cost</th>
              <th>Status</th>
              <th>Date Added</th>
              <th class="actions">Actions</th>
            </tr>
          </thead>
          <tbody>
          <?php foreach ($stocks as $row):
              $st = $row['_state'];
              $badge = $st === 'out' ? ['badge--bad', 'Out of stock'] : ($st === 'low' ? ['badge--warn', 'Low stock'] : ['badge--ok', 'Available']);
          ?>
            <tr data-state="<?php echo $st; ?>">
              <td class="muted">#<?php echo h($row['stock_id']); ?></td>
              <td>
                <strong><?php echo h($row['drug_name']); ?></strong>
                <span class="sub"><?php echo h($row['category']); ?></span>
              </td>
              <td>
                <?php echo h($row['supplier']); ?>
                <span class="sub"><?php echo h($row['company']); ?></span>
              </td>
              <td><span class="qty <?php echo $st === 'out' ? 'qty--out' : ($st === 'low' ? 'qty--low' : ''); ?>"><?php echo number_format((int) $row['quantity']); ?></span></td>
              <td><?php echo h($currency); ?> <?php echo number_format((float) $row['cost'], 2); ?></td>
              <td><span class="badge <?php echo $badge[0]; ?>"><?php echo $badge[1]; ?></span></td>
              <td><?php echo h(date('M j, Y', strtotime($row['date_supplied']))); ?></td>
              <td class="actions">
                <a class="icon-btn icon-btn--delete" title="Delete" aria-label="Delete <?php echo h($row['drug_name']); ?>"
                   href="delete_stock.php?stock_id=<?php echo urlencode($row['stock_id']); ?>"
                   onclick="return confirm('Are you sure you want to delete this medicine?');"><i class="fas fa-trash"></i></a>
              </td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <div class="no-match" id="noMatch">No medicines match your search.</div>
      <?php endif; ?>
    </section>

    <!-- Add Medicine -->
    <section class="panel card form-card" id="content_2" role="tabpanel" aria-labelledby="tab_2" <?php echo $hasError ? '' : 'hidden'; ?>>
      <h2>Add a new medicine</h2>
      <p class="form-sub">Stock below <?php echo (int) $low_threshold; ?> units is flagged as low.</p>

      <form name="myform" action="stock_pharmacist.php" method="post" autocomplete="off">
        <div class="grid">
          <div class="field">
            <label for="drug_name">Drug Name</label>
            <input name="drug_name" id="drug_name" type="text" required value="<?php echo h($form['drug_name']); ?>">
          </div>
          <div class="field">
            <label for="category">Category</label>
            <input name="category" id="category" type="text" required value="<?php echo h($form['category']); ?>">
          </div>
          <div class="field span-2">
            <label for="description">Description</label>
            <input name="description" id="description" type="text" required value="<?php echo h($form['description']); ?>">
          </div>
          <div class="field">
            <label for="company">Manufacturing Company</label>
            <input name="company" id="company" type="text" required value="<?php echo h($form['company']); ?>">
          </div>
          <div class="field">
            <label for="supplier">Supplier</label>
            <input name="supplier" id="supplier" type="text" required value="<?php echo h($form['supplier']); ?>">
          </div>
          <div class="field">
            <label for="quantity">Quantity</label>
            <input name="quantity" id="quantity" type="number" min="1" step="1" required value="<?php echo h($form['quantity']); ?>">
          </div>
          <div class="field">
            <label for="cost">Unit Cost (<?php echo h($currency); ?>)</label>
            <input name="cost" id="cost" type="number" step="0.01" min="0" required value="<?php echo h($form['cost']); ?>">
          </div>
        </div>

        <div class="form-actions">
          <button type="submit" name="submit" class="btn"><i class="fas fa-plus"></i> Add Medicine</button>
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

  // Search + stock-level filter
  var filter = document.getElementById('filter');
  if (filter) {
    var rows = document.querySelectorAll('#stockTable tbody tr');
    var noMatch = document.getElementById('noMatch');
    var chips = document.querySelectorAll('.chip');
    var state = 'all';

    function apply() {
      var q = filter.value.trim().toLowerCase(), shown = 0;
      rows.forEach(function (r) {
        var hit = (state === 'all' || r.getAttribute('data-state') === state) &&
                  r.textContent.toLowerCase().indexOf(q) !== -1;
        r.style.display = hit ? '' : 'none';
        if (hit) shown++;
      });
      noMatch.style.display = shown === 0 ? 'block' : 'none';
    }
    filter.addEventListener('input', apply);
    chips.forEach(function (c) {
      c.addEventListener('click', function () {
        state = c.getAttribute('data-state');
        chips.forEach(function (x) { x.setAttribute('aria-pressed', x === c ? 'true' : 'false'); });
        apply();
      });
    });
  }
})();
</script>

<?php include '../includes/footer.php'; ?>