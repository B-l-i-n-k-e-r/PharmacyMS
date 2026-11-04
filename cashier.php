<?php
session_start();
include_once('connect_db.php');

if (isset($_SESSION['username'])) {
    $id    = $_SESSION['cashier_id'];
    $fname = $_SESSION['first_name'];
    $user  = $_SESSION['username'];
} else {
    header("location:index.php");
    exit();
}

$currency  = 'KES'; // keep the same as receipt.php
$today     = date('Y-m-d');
$yesterday = date('Y-m-d', strtotime('-1 day'));

// ---- Greeting ----
$hour = (int) date('G');
$greeting = ($hour < 12) ? 'Good morning' : (($hour < 17) ? 'Good afternoon' : 'Good evening');

// ---- Defaults ----
$sales_today = 0;
$tx_today    = 0;
$sales_yday  = 0;
$avg_sale    = 0;
$trend       = null;   // % change vs yesterday
$week        = [];     // last 7 days: date => total
$methods     = [];     // today's payment mix
$recent      = [];     // latest transactions

// Build the last 7 days (oldest first) with zero values
for ($i = 6; $i >= 0; $i--) {
    $week[date('Y-m-d', strtotime("-$i day"))] = 0;
}

// ---- Today's and yesterday's totals ----
try {
    $stmt = $con->prepare(
        "SELECT DATE(date) AS d, COALESCE(SUM(total), 0) AS total, COUNT(*) AS cnt
         FROM receipts
         WHERE cashier_id = :cid AND DATE(date) >= :from
         GROUP BY DATE(date)"
    );
    $stmt->execute([':cid' => $id, ':from' => array_key_first($week)]);
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $r) {
        if (isset($week[$r['d']])) {
            $week[$r['d']] = (float) $r['total'];
        }
        if ($r['d'] === $today) {
            $sales_today = (float) $r['total'];
            $tx_today    = (int) $r['cnt'];
        }
        if ($r['d'] === $yesterday) {
            $sales_yday = (float) $r['total'];
        }
    }
    $avg_sale = $tx_today > 0 ? $sales_today / $tx_today : 0;
    if ($sales_yday > 0) {
        $trend = (($sales_today - $sales_yday) / $sales_yday) * 100;
    }
} catch (PDOException $e) {
    error_log("Dashboard stats failed: " . $e->getMessage());
}

// ---- Payment mix for today ----
try {
    $stmt = $con->prepare(
        "SELECT payType, COALESCE(SUM(total), 0) AS total, COUNT(*) AS cnt
         FROM receipts
         WHERE cashier_id = :cid AND DATE(date) = :today
         GROUP BY payType
         ORDER BY total DESC"
    );
    $stmt->execute([':cid' => $id, ':today' => $today]);
    $methods = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Dashboard methods failed: " . $e->getMessage());
}

// ---- Recent transactions ----
try {
    $stmt = $con->prepare(
        "SELECT * FROM receipts WHERE cashier_id = :cid ORDER BY date DESC, reciptNo DESC LIMIT 6"
    );
    $stmt->execute([':cid' => $id]);
    $recent = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Dashboard recent failed: " . $e->getMessage());
}

$week_max      = max($week) > 0 ? max($week) : 1;
$method_colors = ['#0f3d33', '#e0a030', '#4f8f7e', '#8aa9a0', '#c4d8d2'];

$page_title   = "Cashier Dashboard - Pharmacy";
$topbar_title = "Cashier Dashboard";

include 'includes/head.php';
?>

<style>
.dash { max-width: 1200px; }

/* Hero */
.dash-hero {
  display: flex; align-items: center; justify-content: space-between; gap: 24px; flex-wrap: wrap;
  background: linear-gradient(135deg, var(--pine) 0%, #1c5a4b 100%);
  color: #fff; border-radius: 18px; padding: 32px 36px; margin-bottom: 28px;
  position: relative; overflow: hidden;
}
.dash-hero::after {
  content: ""; position: absolute; right: -60px; top: -60px; width: 240px; height: 240px;
  border-radius: 50%; background: rgba(255,255,255,0.06);
}
.dash-hero h1 { margin: 0 0 6px; font-size: 1.8rem; color: #fff; }
.dash-hero p  { margin: 0; color: #c4d8d2; }
.dash-date    { font-size: 0.85rem; letter-spacing: .04em; text-transform: uppercase; color: #9fc2b8; margin-bottom: 8px; }
.dash-cta {
  position: relative; z-index: 1; display: inline-flex; align-items: center; gap: 10px;
  background: #fff; color: var(--pine); font-weight: 700; padding: 14px 24px;
  border-radius: 12px; text-decoration: none; transition: transform .15s, box-shadow .15s;
}
.dash-cta:hover { transform: translateY(-2px); box-shadow: 0 8px 20px rgba(0,0,0,.2); }

/* Stat cards */
.dash-stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(230px, 1fr)); gap: 20px; margin-bottom: 28px; }
.dash-stat {
  background: #fff; border: 1px solid var(--line); border-radius: 16px; padding: 22px;
  display: flex; flex-direction: column; gap: 6px;
}
.dash-stat-top { display: flex; align-items: center; justify-content: space-between; }
.dash-stat-label { font-size: 0.8rem; font-weight: 600; letter-spacing: .05em; text-transform: uppercase; color: var(--ink-soft); }
.dash-stat-icon {
  width: 40px; height: 40px; border-radius: 12px; display: grid; place-items: center;
  background: var(--mist); color: var(--pine);
}
.dash-stat-value { font-size: 1.9rem; font-weight: 700; line-height: 1.2; }
.dash-stat-value small { font-size: 1rem; font-weight: 600; color: var(--ink-soft); }
.chip { display: inline-flex; align-items: center; gap: 4px; font-size: 0.8rem; font-weight: 600; padding: 3px 10px; border-radius: 999px; width: fit-content; }
.chip--up   { background: #e3f4ec; color: #17794f; }
.chip--down { background: #fdeaea; color: #b3261e; }
.chip--flat { background: var(--mist); color: var(--ink-soft); }

/* Panels */
.dash-grid { display: grid; grid-template-columns: 1.6fr 1fr; gap: 20px; margin-bottom: 28px; }
.panel { background: #fff; border: 1px solid var(--line); border-radius: 16px; padding: 24px; }
.panel h2 { margin: 0 0 4px; font-size: 1.1rem; }
.panel-sub { margin: 0 0 20px; color: var(--ink-soft); font-size: 0.85rem; }

/* Bar chart */
.bars { display: flex; align-items: flex-end; gap: 12px; height: 190px; }
.bar-col { flex: 1; display: flex; flex-direction: column; align-items: center; justify-content: flex-end; height: 100%; gap: 8px; }
.bar-val { font-size: 0.72rem; color: var(--ink-soft); font-weight: 600; min-height: 14px; }
.bar {
  width: 100%; max-width: 46px; border-radius: 8px 8px 4px 4px;
  background: var(--mist); min-height: 4px; transition: opacity .15s;
}
.bar--today { background: var(--pine); }
.bar-col:hover .bar { opacity: .8; }
.bar-label { font-size: 0.78rem; color: var(--ink-soft); font-weight: 600; }
.bar-label--today { color: var(--pine); }

/* Payment mix */
.mix-bar { display: flex; height: 12px; border-radius: 999px; overflow: hidden; background: var(--mist); margin-bottom: 18px; }
.mix-bar span { display: block; height: 100%; }
.mix-row { display: flex; align-items: center; justify-content: space-between; padding: 10px 0; border-bottom: 1px solid var(--line); gap: 12px; }
.mix-row:last-child { border-bottom: 0; }
.mix-name { display: flex; align-items: center; gap: 10px; font-weight: 600; }
.mix-dot { width: 10px; height: 10px; border-radius: 50%; flex-shrink: 0; }
.mix-amt { text-align: right; font-weight: 700; }
.mix-amt small { display: block; font-weight: 500; color: var(--ink-soft); }

/* Recent table */
.panel-head { display: flex; align-items: center; justify-content: space-between; margin-bottom: 16px; }
.panel-head a { font-weight: 600; font-size: 0.9rem; color: var(--pine); text-decoration: none; }
.panel-head a:hover { text-decoration: underline; }
.rtable { width: 100%; border-collapse: collapse; }
.rtable th { text-align: left; font-size: 0.75rem; text-transform: uppercase; letter-spacing: .05em; color: var(--ink-soft); padding: 0 12px 10px; font-weight: 600; }
.rtable td { padding: 14px 12px; border-top: 1px solid var(--line); font-size: 0.92rem; vertical-align: middle; }
.rtable tr:hover td { background: #f8faf9; }
.pill { background: var(--mist); padding: 4px 10px; border-radius: 999px; font-size: 0.8rem; font-weight: 600; }
.muted { color: var(--ink-soft); }
.empty-note { text-align: center; color: var(--ink-soft); padding: 28px 0; }

@media (max-width: 900px) {
  .dash-grid { grid-template-columns: 1fr; }
  .dash-hero { padding: 24px; }
  .rtable .hide-sm { display: none; }
}
</style>

<?php
include 'includes/sidebar_cashier.php';
include 'includes/header.php';
?>

<main class="main">
<div class="dash">

    <!-- HERO -->
    <section class="dash-hero">
        <div style="position: relative; z-index: 1;">
            <div class="dash-date"><?php echo date('l, j F Y'); ?></div>
            <h1><?php echo $greeting; ?>, <?php echo htmlspecialchars($fname); ?></h1>
            <p>Ready to process some sales? Here is how your day is going.</p>
        </div>
        <a href="payment.php" class="dash-cta">
            <i class="fas fa-cash-register"></i> New Sale
        </a>
    </section>

    <!-- STATS -->
    <section class="dash-stats">
        <div class="dash-stat">
            <div class="dash-stat-top">
                <span class="dash-stat-label">Sales Today</span>
                <span class="dash-stat-icon"><i class="fas fa-money-bill-wave"></i></span>
            </div>
            <div class="dash-stat-value">
                <small><?php echo htmlspecialchars($currency); ?></small> <?php echo number_format($sales_today, 2); ?>
            </div>
            <?php if ($trend === null): ?>
                <span class="chip chip--flat">No sales yesterday to compare</span>
            <?php elseif ($trend > 0.05): ?>
                <span class="chip chip--up"><i class="fas fa-arrow-up"></i> <?php echo number_format($trend, 1); ?>% vs yesterday</span>
            <?php elseif ($trend < -0.05): ?>
                <span class="chip chip--down"><i class="fas fa-arrow-down"></i> <?php echo number_format(abs($trend), 1); ?>% vs yesterday</span>
            <?php else: ?>
                <span class="chip chip--flat">Same as yesterday</span>
            <?php endif; ?>
        </div>

        <div class="dash-stat">
            <div class="dash-stat-top">
                <span class="dash-stat-label">Transactions</span>
                <span class="dash-stat-icon"><i class="fas fa-receipt"></i></span>
            </div>
            <div class="dash-stat-value"><?php echo number_format($tx_today); ?></div>
            <span class="chip chip--flat">Processed today</span>
        </div>

        <div class="dash-stat">
            <div class="dash-stat-top">
                <span class="dash-stat-label">Average Sale</span>
                <span class="dash-stat-icon"><i class="fas fa-chart-line"></i></span>
            </div>
            <div class="dash-stat-value">
                <small><?php echo htmlspecialchars($currency); ?></small> <?php echo number_format($avg_sale, 2); ?>
            </div>
            <span class="chip chip--flat">Per transaction today</span>
        </div>
    </section>

    <!-- CHART + PAYMENT MIX -->
    <section class="dash-grid">
        <div class="panel">
            <h2>Last 7 Days</h2>
            <p class="panel-sub">Your daily sales total</p>
            <div class="bars">
                <?php foreach ($week as $date => $amt):
                    $h = $amt > 0 ? max(($amt / $week_max) * 100, 4) : 4;
                    $isToday = ($date === $today);
                ?>
                <div class="bar-col" title="<?php echo date('D j M', strtotime($date)) . ': ' . $currency . ' ' . number_format($amt, 2); ?>">
                    <span class="bar-val"><?php echo $amt > 0 ? number_format($amt) : ''; ?></span>
                    <div class="bar <?php echo $isToday ? 'bar--today' : ''; ?>" style="height: <?php echo $h; ?>%;"></div>
                    <span class="bar-label <?php echo $isToday ? 'bar-label--today' : ''; ?>">
                        <?php echo $isToday ? 'Today' : date('D', strtotime($date)); ?>
                    </span>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="panel">
            <h2>Payment Methods</h2>
            <p class="panel-sub">How customers paid today</p>
            <?php if (empty($methods) || $sales_today <= 0): ?>
                <div class="empty-note">No sales yet today.</div>
            <?php else: ?>
                <div class="mix-bar">
                    <?php foreach ($methods as $i => $m):
                        $pct = ($m['total'] / $sales_today) * 100; ?>
                        <span style="width: <?php echo number_format($pct, 2, '.', ''); ?>%; background: <?php echo $method_colors[$i % count($method_colors)]; ?>;"></span>
                    <?php endforeach; ?>
                </div>
                <?php foreach ($methods as $i => $m):
                    $pct = ($m['total'] / $sales_today) * 100; ?>
                    <div class="mix-row">
                        <div class="mix-name">
                            <span class="mix-dot" style="background: <?php echo $method_colors[$i % count($method_colors)]; ?>;"></span>
                            <?php echo htmlspecialchars($m['payType'] !== '' ? $m['payType'] : 'Unspecified'); ?>
                        </div>
                        <div class="mix-amt">
                            <?php echo htmlspecialchars($currency); ?> <?php echo number_format((float) $m['total'], 2); ?>
                            <small><?php echo (int) $m['cnt']; ?> sale<?php echo $m['cnt'] == 1 ? '' : 's'; ?> &middot; <?php echo number_format($pct, 0); ?>%</small>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </section>

    <!-- RECENT TRANSACTIONS -->
    <section class="panel">
        <div class="panel-head">
            <div>
                <h2>Recent Transactions</h2>
                <p class="panel-sub" style="margin: 0;">Your latest sales</p>
            </div>
            <a href="cashier_history.php">View all <i class="fas fa-arrow-right"></i></a>
        </div>

        <?php if (empty($recent)): ?>
            <div class="empty-note">
                You haven't processed any sales yet.<br><br>
                <a href="payment.php" class="btn">Start your first sale</a>
            </div>
        <?php else: ?>
        <div style="overflow-x: auto;">
            <table class="rtable">
                <thead>
                    <tr>
                        <th>Receipt</th>
                        <th>Customer</th>
                        <th class="hide-sm">Method</th>
                        <th>Total</th>
                        <th class="hide-sm">Time</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($recent as $r): ?>
                    <tr>
                        <td><strong>#<?php echo htmlspecialchars((string) $r['reciptNo']); ?></strong></td>
                        <td>
                            <?php
                            $cn = $r['customer_name'] ?? '';
                            echo $cn !== '' ? htmlspecialchars($cn) : '<span class="muted">Walk-in</span>';
                            ?>
                        </td>
                        <td class="hide-sm"><span class="pill"><?php echo htmlspecialchars((string) ($r['payType'] ?: 'N/A')); ?></span></td>
                        <td><strong><?php echo htmlspecialchars($currency); ?> <?php echo number_format((float) $r['total'], 2); ?></strong></td>
                        <td class="hide-sm muted">
                            <?php
                            $ts = strtotime($r['date']);
                            echo date('Y-m-d', $ts) === $today ? 'Today, ' . date('g:i A', $ts) : date('M j, g:i A', $ts);
                            ?>
                        </td>
                        <td><a href="receipt.php?receipt=<?php echo urlencode((string) $r['reciptNo']); ?>" style="font-weight: 600;">View</a></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </section>

</div>
</main>

<?php include 'includes/footer.php'; ?>