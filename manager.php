<?php
session_start();
include_once('connect_db.php');

if (empty($_SESSION['username'])) {
    header('Location: index.php');
    exit();
}

$fname = $_SESSION['first_name'] ?? '';
$user  = $_SESSION['username'];

// Data for the tiles
$groups = [
    'Management' => [
        ['manager_products.php', 'fa-box-open', 'Products Catalog', 'Manage medicine catalog and pricing.', 'pine'],
        ['manager_inventory.php', 'fa-warehouse', 'Inventory', 'Manage stock levels and expiry monitoring.', 'amber'],
        ['manager_sales.php', 'fa-receipt', 'Sales', 'View sales history and transaction details.', 'pine'],
    ],
    'Relations' => [
        ['manager_customers.php', 'fa-address-book', 'Customers', 'Manage customer profiles and history.', 'amber'],
        ['manager_suppliers.php', 'fa-truck-loading', 'Suppliers', 'Manage supplier profiles and orders.', 'pine'],
        ['manager_reports.php', 'fa-chart-pie', 'Reports', 'Generate profit/loss and inventory reports.', 'amber'],
    ]
];

$page_title = "Manager Dashboard - MedFlow PMS";
$topbar_title = "Manager Dashboard";

include 'includes/head.php';
include 'includes/sidebar_manager.php';
include 'includes/header.php';
?>

<main class="main">
    <h1><span id="greeting">Welcome back</span><?= $fname !== '' ? ', ' . htmlspecialchars($fname) : '' ?></h1>
    <p class="lead">Manage your pharmacy's users, prescriptions, and stock from one place.</p>

    <?php foreach ($groups as $title => $items): $gid = 'g-' . preg_replace('/\W+/', '-', strtolower($title)); ?>
      <section class="group" aria-labelledby="<?= htmlspecialchars($gid) ?>">
        <h2 id="<?= htmlspecialchars($gid) ?>"><?= htmlspecialchars($title) ?></h2>
        <div class="tiles">
          <?php foreach ($items as [$href, $icon, $label, $desc, $variant]): ?>
            <a href="<?= htmlspecialchars($href) ?>" class="tile tile--<?= htmlspecialchars($variant) ?>">
              <span class="tile-icon"><i class="fas <?= htmlspecialchars($icon) ?>" aria-hidden="true"></i></span>
              <div>
                <h3><?= htmlspecialchars($label) ?></h3>
                <p><?= htmlspecialchars($desc) ?></p>
              </div>
            </a>
          <?php endforeach; ?>
        </div>
      </section>
    <?php endforeach; ?>
</main>

<script>
  var h = new Date().getHours();
  document.getElementById('greeting').textContent =
    h < 12 ? 'Good morning' : h < 18 ? 'Good afternoon' : 'Good evening';
</script>

<?php include 'includes/footer.php'; ?>