<?php
session_start();
include_once('../connect_db.php');

if (empty($_SESSION['username'])) { header('Location: ../index.php'); exit(); }
$user = $_SESSION['username'];

// Fetch Report Data
$load_failed = false;
try {
    $finance = $con->query("SELECT SUM(total) AS total_rev, COUNT(*) AS total_sales FROM receipts")->fetch(PDO::FETCH_ASSOC);
    $total_revenue = (float) ($finance['total_rev'] ?? 0);
    $total_sales   = (int)   ($finance['total_sales'] ?? 0);
    $avg_order     = $total_sales > 0 ? $total_revenue / $total_sales : 0;
} catch (PDOException $e) {
    $total_revenue = 0; $total_sales = 0; $avg_order = 0;
    $load_failed = true;
}

$page_title = "Financial Reports - MedFlow PMS";
$topbar_title = "Manager Dashboard";

include '../includes/head.php';
?>

<style>
/* ---------- Screen ---------- */
.report-head {
  display: flex;
  flex-wrap: wrap;
  align-items: flex-start;
  justify-content: space-between;
  gap: 16px 24px;
  margin-bottom: 28px;
}
.report-head .lead { margin-bottom: 0; }
.report-meta { margin: 0 0 20px; color: var(--ink-soft, #5b6b67); font-size: .95rem; }
.report-notice {
  margin: 0 0 20px;
  padding: 12px 16px;
  border: 1px solid var(--amber, #f2b544);
  border-left-width: 4px;
  border-radius: 10px;
  background: #fbebc8;
  color: #7a5200;
  font-weight: 500;
}
.print-header { display: none; }

/* ---------- Print ---------- */
@media print {
  @page { margin: 16mm; }

  .sidebar, .topbar, .scrim, .menu-btn, .btn, .no-print, .report-notice { display: none !important; }

  body { display: block; background: #fff; }
  .main, .content {
    margin: 0 !important;
    padding: 0 !important;
    max-width: none !important;
  }

  .print-header {
    display: block;
    margin-bottom: 20px;
    padding-bottom: 14px;
    border-bottom: 3px solid #123b36;
  }
  .print-header h1 { margin: 0 0 4px; font-size: 22pt; }
  .print-header p { margin: 0; color: #333; }

  .metric-card { break-inside: avoid; box-shadow: none; border: 1px solid #999; }
}
</style>

<?php
include '../includes/sidebar_manager.php';
include '../includes/header.php';
?>

<main class="main" id="main">
    <div class="report-head no-print">
        <div>
            <h1>Financial Reports</h1>
            <p class="lead">Generate official profit and loss summaries.</p>
        </div>
        <button type="button" onclick="window.print()" class="btn btn--outline">
          <i class="fas fa-print" aria-hidden="true"></i> Print report
        </button>
    </div>

    <?php if ($load_failed): ?>
        <p class="report-notice" role="alert">Report data could not be loaded, so the figures below show zero. Refresh the page or contact your administrator.</p>
    <?php endif; ?>

    <p class="report-meta no-print">Generated on <?= date('M j, Y') ?></p>

    <div class="print-area">
        <div class="print-header">
            <h1>Official Store Report</h1>
            <p>Generated on <?= date('M j, Y') ?> by <?= htmlspecialchars($user, ENT_QUOTES, 'UTF-8') ?></p>
        </div>

        <div class="metrics-grid">
            <div class="metric-card">
                <div class="metric-icon metric-icon--pine no-print"><i class="fas fa-money-bill-wave" aria-hidden="true"></i></div>
                <div class="metric-info">
                  <h3>Total Revenue</h3>
                  <p style="white-space: nowrap;">Ksh&nbsp;<?= number_format($total_revenue, 2) ?></p>
                </div>
            </div>
            <div class="metric-card">
                <div class="metric-icon metric-icon--amber no-print"><i class="fas fa-receipt" aria-hidden="true"></i></div>
                <div class="metric-info">
                  <h3>Total Transactions</h3>
                  <p><?= number_format($total_sales) ?></p>
                </div>
            </div>
            <div class="metric-card">
                <div class="metric-icon metric-icon--pine no-print"><i class="fas fa-chart-line" aria-hidden="true"></i></div>
                <div class="metric-info">
                  <h3>Avg. Order Value</h3>
                  <p style="white-space: nowrap;">Ksh&nbsp;<?= number_format($avg_order, 2) ?></p>
                </div>
            </div>
        </div>
    </div>
</main>

<?php include '../includes/footer.php'; ?>