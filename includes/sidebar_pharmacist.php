<?php $current_page = basename($_SERVER['PHP_SELF']); ?>
<aside class="sidebar" id="sidebar" aria-label="Main navigation">
    <div class="brand">
      <span class="brand-mark" aria-hidden="true"></span>
      <span class="brand-name">MedFlow<br>PMS</span>
    </div>
    
    <ul class="nav">
      <li><a href="pharmacist.php" class="<?php echo $current_page == 'pharmacist.php' ? 'active' : ''; ?>"><i class="fas fa-home"></i>Dashboard</a></li>
      
      <li class="nav-label">Pharmacy</li>
      <li><a href="prescription.php" class="<?php echo $current_page == 'prescription.php' ? 'active' : ''; ?>"><i class="fas fa-prescription-bottle-alt"></i>Prescription</a></li>
      <li><a href="stock_pharmacist.php" class="<?php echo $current_page == 'stock_pharmacist.php' ? 'active' : ''; ?>"><i class="fas fa-pills"></i>Stock</a></li>
      <li><a href="pharmacist_customers.php" class="<?php echo $current_page == 'pharmacist_customers.php' || $current_page == 'customers.php' ? 'active' : ''; ?>"><i class="fas fa-users"></i>Customers</a></li>
    </ul>
    
    <div class="sidebar-foot">
      <a href="../logout.php"><i class="fas fa-power-off"></i>Log out</a>
    </div>
</aside>
<div class="scrim" id="scrim"></div>