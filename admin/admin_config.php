<?php
session_start();
include_once('../connect_db.php');

if(!isset($_SESSION['username'])){
    header("location:../index.php");
    exit();
}
$user = $_SESSION['username'];
$message1 = '';
$message_ok = '';

// --- 1. HANDLE GLOBAL SETTINGS UPDATE ---
if(isset($_POST['update_settings'])){
    $settings_to_update = [
        'pharmacy_name' => $_POST['pharmacy_name'],
        'currency_symbol' => $_POST['currency_symbol'],
        'contact_email' => $_POST['contact_email'],
        'contact_phone' => $_POST['contact_phone'],
        'address' => $_POST['address']
    ];

    try {
        $con->beginTransaction();
        foreach($settings_to_update as $key => $value){
            $stmt = $con->prepare("UPDATE settings SET setting_value = :val WHERE setting_key = :key");
            $stmt->execute([':val' => $value, ':key' => $key]);
        }
        $con->commit();
        $message_ok = "System settings updated successfully.";
    } catch (PDOException $e) {
        $con->rollBack();
        $message1 = "Error updating settings: " . htmlspecialchars($e->getMessage());
    }
}

// --- 2. HANDLE ADD PAYMENT TYPE ---
if(isset($_POST['add_payment'])){
    $pay_name = trim($_POST['payment_name']);
    
    if(!empty($pay_name)){
        try {
            $stmt = $con->prepare("INSERT INTO paymenttypes (Name) VALUES (:name)");
            $stmt->execute([':name' => $pay_name]);
            $message_ok = "Payment method added successfully.";
        } catch (PDOException $e) {
            $message1 = "Error adding payment type: " . htmlspecialchars($e->getMessage());
        }
    }
}

// --- 3. HANDLE DELETE PAYMENT TYPE ---
if(isset($_GET['delete_payment'])){
    $id = $_GET['delete_payment'];
    try {
        $stmt = $con->prepare("DELETE FROM paymenttypes WHERE id = :id");
        $stmt->execute([':id' => $id]);
        header("Location: admin_config.php?deleted=1");
        exit();
    } catch (PDOException $e) {
        $message1 = "Error deleting payment type: " . htmlspecialchars($e->getMessage());
    }
}

if(isset($_GET['deleted'])){
    $message_ok = "Payment method deleted.";
}

// --- FETCH CURRENT DATA ---
// Settings
$settings = [];
try {
    $stmt = $con->query("SELECT * FROM settings");
    while($row = $stmt->fetch(PDO::FETCH_ASSOC)){
        $settings[$row['setting_key']] = $row['setting_value'];
    }
} catch (PDOException $e) {
    $message1 = "Error loading settings: " . htmlspecialchars($e->getMessage());
}

// Payment Types
$payTypes = [];
try {
    $stmt = $con->query("SELECT * FROM paymenttypes ORDER BY id ASC");
    $payTypes = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $message1 = "Error loading payment types: " . htmlspecialchars($e->getMessage());
}

$page_title = "System Configuration - Pharmacy";
$topbar_title = "Admin Dashboard";

include '../includes/head.php';
include '../includes/sidebar_admin.php';
include '../includes/header.php';
?>

<main class="main">
    <h1>System Configuration</h1>
    <p class="lead">Manage global app settings and accepted payment methods.</p>

    <?php if ($message_ok !== ''): ?>
      <div class="alert alert--ok" role="status"><?php echo htmlspecialchars($message_ok); ?></div>
    <?php endif; ?>
    <?php if ($message1 !== ''): ?>
      <div class="alert alert--error" role="alert"><?php echo htmlspecialchars($message1); ?></div>
    <?php endif; ?>

    <div class="grid" style="gap: 30px;">
        
        <!-- GLOBAL SETTINGS -->
        <section class="card form-card" style="max-width: 100%;">
            <h2>Global Settings</h2>
            <form action="admin_config.php" method="post" autocomplete="off">
                <div class="grid">
                    <div class="field span-2">
                        <label for="pharmacy_name">Pharmacy Name</label>
                        <input name="pharmacy_name" id="pharmacy_name" type="text" required value="<?php echo htmlspecialchars($settings['pharmacy_name'] ?? ''); ?>">
                    </div>
                    <div class="field">
                        <label for="currency_symbol">Currency Symbol</label>
                        <input name="currency_symbol" id="currency_symbol" type="text" required value="<?php echo htmlspecialchars($settings['currency_symbol'] ?? 'Ksh'); ?>">
                    </div>
                    <div class="field">
                        <label for="contact_phone">Contact Phone</label>
                        <input name="contact_phone" id="contact_phone" type="text" value="<?php echo htmlspecialchars($settings['contact_phone'] ?? ''); ?>">
                    </div>
                    <div class="field span-2">
                        <label for="contact_email">Support Email</label>
                        <input name="contact_email" id="contact_email" type="email" value="<?php echo htmlspecialchars($settings['contact_email'] ?? ''); ?>">
                    </div>
                    <div class="field span-2">
                        <label for="address">Physical Address</label>
                        <input name="address" id="address" type="text" value="<?php echo htmlspecialchars($settings['address'] ?? ''); ?>">
                    </div>
                </div>
                <div class="form-actions">
                    <button type="submit" name="update_settings" class="btn">Save Settings</button>
                </div>
            </form>
        </section>

        <!-- PAYMENT METHODS -->
        <section class="card" style="max-width: 100%; padding: 28px;">
            <h2 style="margin-top:0; margin-bottom: 20px; font-size: 1.25rem;">Payment Methods</h2>
            
            <form action="admin_config.php" method="post" style="display: flex; gap: 12px; margin-bottom: 24px;">
                <input type="text" name="payment_name" placeholder="e.g. PayPal, Insurance" required style="flex: 1; padding: 12px 14px; font: inherit; color: var(--ink); background: var(--paper); border: 1.5px solid var(--line); border-radius: var(--radius);">
                <button type="submit" name="add_payment" class="btn">Add Method</button>
            </form>

            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Payment Name</th>
                            <th class="actions">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach($payTypes as $pt): ?>
                        <tr>
                            <td class="num"><?php echo htmlspecialchars($pt['id']); ?></td>
                            <td><strong><?php echo htmlspecialchars($pt['Name']); ?></strong></td>
                            <td class="actions">
                                <a href="admin_config.php?delete_payment=<?php echo $pt['id']; ?>" class="icon-btn icon-btn--delete" onclick="return confirm('Delete this payment method?');">
                                    <i class="fas fa-trash"></i>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>

    </div>
</main>

<?php include '../includes/footer.php'; ?>