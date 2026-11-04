<?php $current_page = basename($_SERVER['PHP_SELF']); ?>
<aside class="sidebar" id="sidebar">
    <div class="brand">
      <span class="brand-mark"></span>
      <span class="brand-name">Pharmacy<br>Management</span>
    </div>
    <ul class="nav">
      <li><a href="manager.php" class="<?php echo $current_page == 'manager.php' ? 'active' : ''; ?>"><i class="fas fa-home"></i>Dashboard</a></li>
      <li><a href="view.php" class="<?php echo $current_page == 'view.php' ? 'active' : ''; ?>"><i class="fas fa-users"></i>View Users</a></li>
      <li><a href="view_prescription.php" class="<?php echo $current_page == 'view_prescription.php' ? 'active' : ''; ?>"><i class="fas fa-prescription-bottle-alt"></i>Prescriptions</a></li>
      <li><a href="stock.php" class="<?php echo $current_page == 'stock.php' ? 'active' : ''; ?>"><i class="fas fa-pills"></i>Manage Stock</a></li>
    </ul>
    <div class="sidebar-foot">
      <a href="logout.php"><i class="fas fa-power-off"></i>Log out</a>
    </div>
</aside>
<div class="scrim" id="scrim"></div>