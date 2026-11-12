<?php
session_start();
include_once('connect_db.php');

if(isset($_SESSION['username'])){
    $id=$_SESSION['admin_id'];
    $admin_user=$_SESSION['username'];
}else{
    header("location:index.php");
    exit();
}

$message1 = '';

// 1. Fetch user data safely to pre-fill the form
if(isset($_GET['username'])) {
    $fetch_user = $_GET['username'];
    $stmt = $con->prepare("SELECT * FROM cashier WHERE username = :user");
    $stmt->execute([':user' => $fetch_user]);
    $userData = $stmt->fetch(PDO::FETCH_ASSOC);

    if(!$userData) {
        die("Cashier not found.");
    }
} else {
    header("Location: admin/admin_cashier.php");
    exit();
}

// 2. Handle the update safely
if(isset($_POST['submit'])){
    $fname=$_POST['first_name'];
    $lname=$_POST['last_name'];
    $sid=$_POST['staff_id'];
    $postal=$_POST['postal_address'];
    $phone=$_POST['phone'];
    $email=$_POST['email'];
    $new_username=$_POST['username'];
    $pas=$_POST['password'];
    
    // The original ID is passed via hidden input to prevent overwriting the wrong user
    $cashier_id = $_POST['cashier_id']; 

    // If password is empty, keep the old one. Otherwise update it.
    if(empty($pas)) {
        $sql = "UPDATE cashier SET first_name=:fname, last_name=:lname, staff_id=:sid, 
                postal_address=:postal, phone=:phone, email=:email, username=:user 
                WHERE cashier_id=:id";
        $stmtUpdate = $con->prepare($sql);
        $result = $stmtUpdate->execute([
            ':fname'=>$fname, ':lname'=>$lname, ':sid'=>$sid, 
            ':postal'=>$postal, ':phone'=>$phone, ':email'=>$email, 
            ':user'=>$new_username, ':id'=>$cashier_id
        ]);
    } else {
        $sql = "UPDATE cashier SET first_name=:fname, last_name=:lname, staff_id=:sid, 
                postal_address=:postal, phone=:phone, email=:email, username=:user, password=:pas 
                WHERE cashier_id=:id";
        $stmtUpdate = $con->prepare($sql);
        $result = $stmtUpdate->execute([
            ':fname'=>$fname, ':lname'=>$lname, ':sid'=>$sid, 
            ':postal'=>$postal, ':phone'=>$phone, ':email'=>$email, 
            ':user'=>$new_username, ':pas'=>$pas, ':id'=>$cashier_id
        ]);
    }

    if($result) {
        header("location:admin/admin_cashier.php");
        exit();
    } else {
        $message1="Update Failed, Try again";
    }
}

$page_title = "Update Cashier - Pharmacy Management System";
$topbar_title = "Admin Dashboard";

include 'includes/head.php';
include 'includes/sidebar_admin.php';
include 'includes/header.php';
?>

<main class="main">
    <h1>Update Cashier</h1>
    <p class="lead">Edit the details for <strong><?php echo htmlspecialchars($userData['first_name'] . ' ' . $userData['last_name']); ?></strong>.</p>

    <section class="card form-card">
      <?php if ($message1 !== ''): ?>
        <div class="alert alert--error" role="alert"><?php echo htmlspecialchars($message1); ?></div>
      <?php endif; ?>

      <form name="myform" action="" method="post" autocomplete="off">
        <input type="hidden" name="cashier_id" value="<?php echo htmlspecialchars($userData['cashier_id']); ?>">

        <div class="grid">
          <div class="field">
            <label for="first_name">First name</label>
            <input name="first_name" id="first_name" type="text" required pattern="[A-Za-z ]+" title="Letters and spaces only" value="<?php echo htmlspecialchars($userData['first_name']); ?>">
          </div>
          <div class="field">
            <label for="last_name">Last name</label>
            <input name="last_name" id="last_name" type="text" required pattern="[A-Za-z ]+" title="Letters and spaces only" value="<?php echo htmlspecialchars($userData['last_name']); ?>">
          </div>
          <div class="field">
            <label for="staff_id">Staff ID (Read-Only)</label>
            <input name="staff_id" id="staff_id" type="text" readonly value="<?php echo htmlspecialchars($userData['staff_id']); ?>">
          </div>
          <div class="field">
            <label for="phone">Phone</label>
            <input name="phone" id="phone" type="tel" required value="<?php echo htmlspecialchars($userData['phone']); ?>">
          </div>
          <div class="field span-2">
            <label for="email">Email</label>
            <input name="email" id="email" type="email" required value="<?php echo htmlspecialchars($userData['email']); ?>">
          </div>
          <div class="field span-2">
            <label for="postal_address">Address</label>
            <input name="postal_address" id="postal_address" type="text" required value="<?php echo htmlspecialchars($userData['postal_address']); ?>">
          </div>
          <div class="field">
            <label for="username">Username</label>
            <input name="username" id="username" type="text" required autocomplete="off" value="<?php echo htmlspecialchars($userData['username']); ?>">
          </div>
          <div class="field">
            <label for="password">New Password</label>
            <input name="password" id="password" type="password" placeholder="Leave blank to keep current" autocomplete="new-password">
            <small style="display:block; margin-top:4px; color:var(--ink-soft); font-size:0.85rem;">Only fill this in if you want to change the password.</small>
          </div>
        </div>

        <div class="form-actions">
          <button type="submit" name="submit" class="btn">Save Changes</button>
          <a href="admin/admin_cashier.php" class="btn btn--ghost">Cancel</a>
        </div>
      </form>
    </section>
</main>

<?php include 'includes/footer.php'; ?>