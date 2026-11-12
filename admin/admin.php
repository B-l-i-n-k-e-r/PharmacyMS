<?php
session_start();
include_once('../connect_db.php');

if(isset($_SESSION['username'])){
    $id=$_SESSION['admin_id'];
    $user=$_SESSION['username']; // Using $user for the includes
}else{
    header("location:../index.php");
    exit();
}

// --- FETCH DASHBOARD METRICS ---

// 1. Total Staff (Pharmacists + Managers + Cashiers)
try {
    $staffQuery = $con->query("
        SELECT 
            (SELECT COUNT(*) FROM pharmacist) + 
            (SELECT COUNT(*) FROM manager) + 
            (SELECT COUNT(*) FROM cashier) as total_staff
    ");
    $total_staff = $staffQuery->fetchColumn();
} catch (PDOException $e) { $total_staff = 0; }

// 2. Total Products in Stock
try {
    $stockQuery = $con->query("SELECT COUNT(*) FROM stock");
    $total_stock = $stockQuery->fetchColumn();
} catch (PDOException $e) { $total_stock = 0; }

// 3. Total Sales Today
try {
    $today = date('Y-m-d');
    $salesQuery = $con->prepare("SELECT SUM(amount) FROM receipts WHERE DATE(date) = :today");
    $salesQuery->execute([':today' => $today]);
    $sales_today = $salesQuery->fetchColumn() ?: 0.00;
} catch (PDOException $e) { $sales_today = 0.00; }
// 4. Low Stock Alerts (Items with quantity < 10)
try {
    $lowStockQuery = $con->query("SELECT COUNT(*) FROM stock WHERE quantity < 10");
    $low_stock = $lowStockQuery->fetchColumn();
} catch (PDOException $e) { $low_stock = 0; }

// --- PAGE SETTINGS ---
$page_title = "Admin Dashboard - Pharmacy Management System";
$topbar_title = "Admin Dashboard";

// --- LOAD LAYOUT ---
include '../includes/head.php';
include '../includes/sidebar_admin.php';
include '../includes/header.php';
?>

<main class="main">
    <h1>System Overview</h1>
    <p class="lead">Welcome back, <?php echo htmlspecialchars($user); ?>. Here is what's happening today.</p>

    <!-- METRICS DASHBOARD -->
    <div class="metrics-grid">
      
      <!-- Metric 1: Staff -->
      <div class="metric-card">
        <div class="metric-icon metric-icon--pine"><i class="fas fa-users"></i></div>
        <div class="metric-info">
          <h3>Total Staff</h3>
          <p><?php echo number_format($total_staff); ?></p>
        </div>
      </div>
      
      <!-- Metric 2: Stock Items -->
      <div class="metric-card">
        <div class="metric-icon metric-icon--amber"><i class="fas fa-pills"></i></div>
        <div class="metric-info">
          <h3>Products in Stock</h3>
          <p><?php echo number_format($total_stock); ?></p>
        </div>
      </div>

      <!-- Metric 3: Today's Sales -->
      <div class="metric-card">
        <div class="metric-icon metric-icon--pine"><i class="fas fa-money-bill-wave"></i></div>
        <div class="metric-info">
          <h3>Sales Today</h3>
          <p>Ksh <?php echo number_format($sales_today, 2); ?></p>
        </div>
      </div>

      <!-- Metric 4: Low Stock -->
      <div class="metric-card" style="<?php echo $low_stock > 0 ? 'border-color: var(--danger);' : ''; ?>">
        <div class="metric-icon metric-icon--danger"><i class="fas fa-exclamation-triangle"></i></div>
        <div class="metric-info">
          <h3>Low Stock Alerts</h3>
          <p><?php echo number_format($low_stock); ?> items</p>
        </div>
      </div>

    </div>

    <!-- MODULES -->
    <div class="group">
      <h2>System Management</h2>
       <div class="tiles">
        
        <a href="admin_products.php" class="tile">
          <span class="tile-icon"><i class="fas fa-box-open"></i></span>
          <div>
            <h3>Products Catalog</h3>
            <p>Manage complete product details and pricing.</p>
          </div>
        </a>

        <a href="admin_inventory.php" class="tile">
          <span class="tile-icon"><i class="fas fa-warehouse"></i></span>
          <div>
            <h3>Inventory</h3>
            <p>Full oversight of stock levels and expiry dates.</p>
          </div>
        </a>
        
         <a href="admin_sales.php" class="tile">
          <span class="tile-icon"><i class="fas fa-receipt"></i></span>
          <div>
            <h3>Sales Transactions</h3>
            <p>View all sales, invoices, and daily revenues.</p>
          </div>
        </a>
        
        <!-- UPDATED LINK HERE -->
        <a href="admin_customers.php" class="tile">
          <span class="tile-icon"><i class="fas fa-address-book"></i></span>
          <div>
            <h3>Customers</h3>
            <p>Manage patient profiles and purchase history.</p>
          </div>
        </a>

        <!-- UPDATED LINK HERE -->
        <a href="admin_suppliers.php" class="tile">
          <span class="tile-icon"><i class="fas fa-truck-loading"></i></span>
          <div>
            <h3>Suppliers</h3>
            <p>Manage supplier relationships and orders.</p>
          </div>
        </a>

        <a href="admin_reports.php" class="tile">
          <span class="tile-icon"><i class="fas fa-chart-line"></i></span>
          <div>
            <h3>Reports</h3>
            <p>Generate comprehensive financial and stock reports.</p>
          </div>
        </a>
        
        <a href="admin_config.php" class="tile">
          <span class="tile-icon"><i class="fas fa-cogs"></i></span>
          <div>
            <h3>System Config</h3>
            <p>Configure global settings, taxes, and system rules.</p>
          </div>
        </a>

      </div>
    </div>
</main>

<?php include '../includes/footer.php'; ?>