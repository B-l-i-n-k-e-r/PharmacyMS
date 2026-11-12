<?php
session_start();
include_once('../connect_db.php');

if (!isset($_SESSION['username'])) {
    header("location:../index.php");
    exit();
}

$user = $_SESSION['username'];
$fname = $_SESSION['first_name'];

$page_title = "Success - Pharmacy";
$topbar_title = "Cashier Dashboard";

include '../includes/head.php';
include '../includes/sidebar_cashier.php';
include '../includes/header.php';
?>

<main class="main">
    <div style="display: flex; align-items: center; justify-content: center; min-height: 60vh;">
        <div class="card" style="max-width: 500px; width: 100%; padding: 48px; text-align: center;">
            
            <div style="width: 80px; height: 80px; background: #e6f4ea; color: #1e6b3a; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 24px;">
                <i class="fas fa-check-circle" style="font-size: 3rem;"></i>
            </div>

            <h1 style="margin: 0 0 12px; font-size: 1.8rem;">Success!</h1>
            <p style="color: var(--ink-soft); margin-bottom: 32px; font-size: 1.1rem;">
                The action was completed successfully. The database has been updated.
            </p>

            <div style="display: flex; flex-direction: column; gap: 12px;">
                <a href="payment.php" class="btn" style="padding: 14px; font-size: 1.05rem;">
                    <i class="fas fa-cash-register"></i> Process New Sale
                </a>
                <a href="cashier.php" class="btn btn--ghost" style="padding: 14px; font-size: 1.05rem;">
                    <i class="fas fa-home"></i> Back to Dashboard
                </a>
            </div>

        </div>
    </div>
</main>

<?php include '../includes/footer.php'; ?>