<?php
session_start();
include_once('../connect_db.php');

if(!isset($_SESSION['username'])){
    header("location:../index.php");
    exit();
}

$user = $_SESSION['username'];
$admin_id = $_SESSION['admin_id'];

$message1 = '';
$success = false;

// Get ID from URL
$stock_id = isset($_GET['id']) && is_numeric($_GET['id']) ? $_GET['id'] : 0;

if ($stock_id === 0) {
    header("location:admin_products.php");
    exit();
}

// Handle Form Submission
if(isset($_POST['update'])){
    $sname = $_POST['drug_name'];
    $cat   = $_POST['category'];
    $des   = $_POST['description'];
    $com   = $_POST['company'];
    $sup   = $_POST['supplier'];
    $cost  = $_POST['cost'];

    try {
        $stmt = $con->prepare("UPDATE stock 
                               SET drug_name = :sname, 
                                   category = :cat, 
                                   description = :des, 
                                   company = :com, 
                                   supplier = :sup, 
                                   cost = :cost 
                               WHERE stock_id = :id");
        
        $sql = $stmt->execute([
            ':sname' => $sname, 
            ':cat'   => $cat, 
            ':des'   => $des,
            ':com'   => $com, 
            ':sup'   => $sup, 
            ':cost'  => $cost,
            ':id'    => $stock_id
        ]);

        if($sql) {
            $success = true;
            $message1 = "Product updated successfully!";
        } else {
            $message1 = "Could not update the product.";
        }
    } catch (PDOException $e) {
        $message1 = "Database Error: " . htmlspecialchars($e->getMessage());
    }
}

// Fetch current product data
try {
    $stmt = $con->prepare("SELECT * FROM stock WHERE stock_id = :id");
    $stmt->execute([':id' => $stock_id]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$product) {
        header("location:admin_products.php");
        exit();
    }
} catch (PDOException $e) {
    die("Error loading product: " . htmlspecialchars($e->getMessage()));
}

// Layout Settings
$page_title = "Edit Product - Pharmacy Management System";
$topbar_title = "Admin Dashboard";

include '../includes/head.php';
include '../includes/sidebar_admin.php';
include '../includes/header.php';
?>

<main class="main">
    <div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 16px; margin-bottom: 28px;">
        <div>
            <h1>Edit Product</h1>
            <p class="lead" style="margin-bottom: 0;">Update details for <strong><?php echo htmlspecialchars($product['drug_name']); ?></strong></p>
        </div>
        <a href="admin_products.php" class="btn btn--outline"><i class="fas fa-arrow-left"></i> Back to Catalog</a>
    </div>

    <?php if ($message1 !== ''): ?>
      <div class="alert <?php echo $success ? 'alert--ok' : 'alert--error'; ?>" role="alert">
        <?php echo htmlspecialchars($message1); ?>
      </div>
    <?php endif; ?>

    <section class="panel card form-card" style="max-width: 800px;">
      <h2>Product Details</h2>
      <p class="hint">Modify the fields below and click Save Changes to update the catalog.</p>

      <form action="edit_product.php?id=<?php echo $stock_id; ?>" method="post" autocomplete="off">
        <div class="grid">
          <div class="field span-2">
            <label for="drug_name">Drug Name</label>
            <input name="drug_name" id="drug_name" type="text" required value="<?php echo htmlspecialchars($product['drug_name']); ?>">
          </div>
          <div class="field">
            <label for="category">Category</label>
            <input name="category" id="category" type="text" required value="<?php echo htmlspecialchars($product['category']); ?>">
          </div>
          <div class="field">
            <label for="company">Manufacturer</label>
            <input name="company" id="company" type="text" required value="<?php echo htmlspecialchars($product['company']); ?>">
          </div>
          <div class="field span-2">
            <label for="description">Description</label>
            <input name="description" id="description" type="text" required value="<?php echo htmlspecialchars($product['description']); ?>">
          </div>
          <div class="field">
            <label for="supplier">Supplier</label>
            <input name="supplier" id="supplier" type="text" required value="<?php echo htmlspecialchars($product['supplier']); ?>">
          </div>
          <div class="field">
            <label for="cost">Unit Cost (Ksh.)</label>
            <input name="cost" id="cost" type="number" step="0.01" min="0" required value="<?php echo htmlspecialchars($product['cost']); ?>">
          </div>
        </div>

        <div class="form-actions">
          <button type="submit" name="update" class="btn">Save Changes</button>
          <a href="admin_products.php" class="btn btn--ghost">Cancel</a>
        </div>
      </form>
    </section>
</main>

<?php include '../includes/footer.php'; ?>