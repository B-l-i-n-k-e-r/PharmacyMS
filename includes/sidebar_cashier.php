<?php $current_page = basename($_SERVER['PHP_SELF']); ?>
<aside class="sidebar" id="sidebar">
    <div class="brand">
      <span class="brand-mark"></span>
      <span class="brand-name">Pharmacy<br>Management</span>
    </div>
    <ul class="nav">
      <li><a href="cashier.php" class="<?php echo $current_page == 'cashier.php' ? 'active' : ''; ?>"><i class="fas fa-home"></i>Dashboard</a></li>
      
      <li style="margin-top: 16px; margin-bottom: 4px; padding-left: 12px; font-size: 0.75rem; font-weight: 700; color: #7da39e; text-transform: uppercase; letter-spacing: 0.05em;">Terminal</li>
      <li><a href="payment.php" class="<?php echo $current_page == 'payment.php' ? 'active' : ''; ?>"><i class="fas fa-cash-register"></i>POS (New Sale)</a></li>
      <li><a href="cashier_history.php" class="<?php echo $current_page == 'cashier_history.php' ? 'active' : ''; ?>"><i class="fas fa-history"></i>My Sales History</a></li>
    </ul>
    <div class="sidebar-foot">
      <a href="../logout.php"><i class="fas fa-power-off"></i>Log out</a>
    </div>
</aside>
<div class="scrim" id="scrim"></div>