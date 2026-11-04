<?php $current_page = basename($_SERVER['PHP_SELF']); ?>
<aside class="sidebar" id="sidebar">
    <div class="brand">
      <span class="brand-mark"></span>
      <span class="brand-name">Pharmacy<br>Management</span>
    </div>
    
    <ul class="nav">
      <li><a href="admin.php" class="<?php echo $current_page == 'admin.php' ? 'active' : ''; ?>"><i class="fas fa-home"></i>Dashboard</a></li>
      
      <li style="margin-top: 16px; margin-bottom: 4px; padding-left: 12px; font-size: 0.75rem; font-weight: 700; color: #7da39e; text-transform: uppercase; letter-spacing: 0.05em;">Management</li>
      <li><a href="admin_products.php" class="<?php echo $current_page == 'admin_products.php' ? 'active' : ''; ?>"><i class="fas fa-box-open"></i>Products Catalog</a></li>
      <li><a href="admin_inventory.php" class="<?php echo $current_page == 'admin_inventory.php' ? 'active' : ''; ?>"><i class="fas fa-warehouse"></i>Inventory</a></li>
      <li><a href="admin_customers.php" class="<?php echo $current_page == 'admin_customers.php' ? 'active' : ''; ?>"><i class="fas fa-address-book"></i>Customers</a></li>
      <li><a href="admin_suppliers.php" class="<?php echo $current_page == 'admin_suppliers.php' ? 'active' : ''; ?>"><i class="fas fa-truck-loading"></i>Suppliers</a></li>
      <li><a href="admin_sales.php" class="<?php echo $current_page == 'admin_sales.php' ? 'active' : ''; ?>"><i class="fas fa-receipt"></i>Sales</a></li>
            <li><a href="admin_reports.php" class="<?php echo $current_page == 'admin_reports.php' ? 'active' : ''; ?>"><i class="fas fa-chart-line"></i>Reports</a></li>
                  
      <li style="margin-top: 16px; margin-bottom: 4px; padding-left: 12px; font-size: 0.75rem; font-weight: 700; color: #7da39e; text-transform: uppercase; letter-spacing: 0.05em;">Users</li>
      <li><a href="admin_pharmacist.php" class="<?php echo $current_page == 'admin_pharmacist.php' ? 'active' : ''; ?>"><i class="fas fa-prescription-bottle-alt"></i>Pharmacists</a></li>
      <li><a href="admin_manager.php" class="<?php echo $current_page == 'admin_manager.php' ? 'active' : ''; ?>"><i class="fas fa-user-cog"></i>Managers</a></li>
      <li><a href="admin_cashier.php" class="<?php echo $current_page == 'admin_cashier.php' ? 'active' : ''; ?>"><i class="fas fa-cash-register"></i>Cashiers</a></li>
         
            <li style="margin-top: 16px; margin-bottom: 4px; padding-left: 12px; font-size: 0.75rem; font-weight: 700; color: #7da39e; text-transform: uppercase; letter-spacing: 0.05em;">Settings</li>
      <li><a href="admin_config.php" class="<?php echo $current_page == 'admin_config.php' ? 'active' : ''; ?>"><i class="fas fa-cogs"></i>System Config</a></li>
    </ul>
    
    <div class="sidebar-foot">
      <a href="logout.php"><i class="fas fa-power-off"></i>Log out</a>
    </div>
</aside>
<div class="scrim" id="scrim"></div>