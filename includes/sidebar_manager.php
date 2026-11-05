<?php $current_page = basename($_SERVER['PHP_SELF']); ?>
<aside class="sidebar" id="sidebar" aria-label="Main navigation">
    <div class="brand">
      <span class="brand-mark" aria-hidden="true"></span>
      <span class="brand-name">MedFlow<br>PMS</span>
    </div>
    
    <ul class="nav">
      <li><a href="manager.php" class="<?php echo $current_page == 'manager.php' ? 'active' : ''; ?>"><i class="fas fa-home"></i>Dashboard</a></li>
      
      <li class="nav-label">Management</li>
      <li><a href="manager_products.php" class="<?php echo $current_page == 'manager_products.php' ? 'active' : ''; ?>"><i class="fas fa-box-open"></i>Products</a></li>
      <li><a href="manager_inventory.php" class="<?php echo $current_page == 'manager_inventory.php' ? 'active' : ''; ?>"><i class="fas fa-warehouse"></i>Inventory</a></li>
      <li><a href="manager_sales.php" class="<?php echo $current_page == 'manager_sales.php' ? 'active' : ''; ?>"><i class="fas fa-receipt"></i>Sales</a></li>
      <li><a href="manager_customers.php" class="<?php echo $current_page == 'manager_customers.php' ? 'active' : ''; ?>"><i class="fas fa-address-book"></i>Customers</a></li>
      <li><a href="manager_suppliers.php" class="<?php echo $current_page == 'manager_suppliers.php' ? 'active' : ''; ?>"><i class="fas fa-truck-loading"></i>Suppliers</a></li>
      
      <li class="nav-label">Analytics</li>
      <li><a href="manager_reports.php" class="<?php echo $current_page == 'manager_reports.php' ? 'active' : ''; ?>"><i class="fas fa-chart-pie"></i>Reports</a></li>
    </ul>
    
    <div class="sidebar-foot">
      <a href="logout.php"><i class="fas fa-power-off"></i>Log out</a>
    </div>
</aside>
<div class="scrim" id="scrim"></div>