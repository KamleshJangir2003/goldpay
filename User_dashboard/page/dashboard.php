<?php
include('../auth_check.php');
require '../config/db.php';

$userId = $_SESSION['user_id'];

$userStmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$userStmt->execute([$userId]);
$user = $userStmt->fetch(PDO::FETCH_ASSOC);

try {
    $walletStmt = $pdo->prepare("SELECT * FROM wallets WHERE user_id = ?");
    $walletStmt->execute([$userId]);
    $walletData = $walletStmt->fetch(PDO::FETCH_ASSOC);
    if ($walletData) {
        $wallet = ['inr_balance' => floatval($walletData['inr_balance']), 'usdt_balance' => floatval($walletData['usdt_balance'])];
    } else {
        $initStmt = $pdo->prepare("INSERT INTO wallets (user_id, inr_balance, usdt_balance) VALUES (?, 0, 0)");
        $initStmt->execute([$userId]);
        $wallet = ['inr_balance' => 0, 'usdt_balance' => 0];
    }
} catch (PDOException $e) {
    $wallet = ['inr_balance' => 0, 'usdt_balance' => 0];
}

$transactions = [];
try {
    $txnStmt = $pdo->prepare("SELECT * FROM user_transactions WHERE user_id = ? ORDER BY created_at DESC LIMIT 5");
    $txnStmt->execute([$userId]);
    $transactions = $txnStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {}

// DB se USDT rate fetch karo
$rateStmt = $pdo->query("SELECT setting_value FROM settings WHERE setting_group='rates' AND setting_key='usdt_inr_rate'");
$rateRow = $rateStmt ? $rateStmt->fetch(PDO::FETCH_ASSOC) : null;
$currentPrice  = $rateRow ? floatval($rateRow['setting_value']) : 89.80;
$totalUSDTinINR = $wallet['usdt_balance'] * $currentPrice;
$totalBalance   = $wallet['inr_balance'] + $totalUSDTinINR;
$priceChange24h = 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>MBPAY - Dashboard</title>
  <link rel="icon" type="image/x-icon" href="../../favicon.ico">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link href="https://fonts.googleapis.com/icon?family=Material+Icons+Round" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <style>
    :root {
      --primary: #6366f1;
      --primary-light: rgba(99,102,241,0.1);
      --bg: #f1f5f9;
      --surface: #ffffff;
      --text: #1e293b;
      --muted: #64748b;
      --green: #22c55e;
      --red: #ef4444;
      --radius: 14px;
      --shadow: 0 2px 12px rgba(0,0,0,0.07);
    }

    * { margin:0; padding:0; box-sizing:border-box; font-family:'Poppins',sans-serif; }

    body { background: var(--bg); min-height:100vh; }

    /* ── Mobile Header ── */
    .mob-header {
      display: none;
      background: #0e1a2b;
      padding: 12px 16px;
      justify-content: space-between;
      align-items: center;
      position: sticky;
      top: 0;
      z-index: 998;
    }
    .mob-header img { height: 32px; }
    .mob-header button {
      background: none; border: none;
      color: #fff; font-size: 26px; cursor: pointer; line-height:1;
    }

    /* ── Layout ── */
    .page-wrap {
      margin-left: 250px;
      padding: 24px;
      min-height: 100vh;
    }

    /* ── Welcome Bar ── */
    .welcome-bar {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 20px;
    }
    .welcome-bar h2 { font-size: 1.3rem; color: var(--text); font-weight: 600; }
    .welcome-bar span { color: var(--muted); font-size: 0.85rem; }

    /* ── Stats Row ── */
    .stats-row {
      display: grid;
      grid-template-columns: repeat(3, 1fr);
      gap: 16px;
      margin-bottom: 20px;
    }
    .stat-card {
      background: var(--surface);
      border-radius: var(--radius);
      padding: 18px 20px;
      box-shadow: var(--shadow);
    }
    .stat-card .label { font-size: 0.78rem; color: var(--muted); margin-bottom: 6px; }
    .stat-card .value { font-size: 1.35rem; font-weight: 700; color: var(--text); }
    .stat-card .sub   { font-size: 0.75rem; color: var(--muted); margin-top: 4px; }
    .stat-card.primary { background: var(--primary); }
    .stat-card.primary .label,
    .stat-card.primary .value,
    .stat-card.primary .sub { color: #fff; }

    /* ── Main Grid ── */
    .main-grid {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 16px;
    }

    /* ── Card ── */
    .card {
      background: var(--surface);
      border-radius: var(--radius);
      padding: 20px;
      box-shadow: var(--shadow);
    }
    .card-head {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 16px;
    }
    .card-head h3 {
      font-size: 0.95rem;
      font-weight: 600;
      color: var(--text);
      display: flex;
      align-items: center;
      gap: 8px;
    }
    .card-head h3 .material-icons-round { font-size: 18px; color: var(--primary); }
    .live-badge {
      font-size: 0.72rem;
      font-weight: 600;
      color: var(--green);
      background: rgba(34,197,94,0.1);
      padding: 3px 8px;
      border-radius: 20px;
    }

    /* ── Chart ── */
    .chart-wrap { height: 130px; position: relative; }

    .price-row {
      display: flex;
      justify-content: space-between;
      margin-top: 12px;
      padding-top: 12px;
      border-top: 1px solid #f1f5f9;
    }
    .price-row .p-label { font-size: 0.75rem; color: var(--muted); }
    .price-row .p-val   { font-size: 1.1rem; font-weight: 700; color: var(--text); }
    .price-row .p-change { font-size: 0.9rem; font-weight: 600; }
    .up   { color: var(--green); }
    .down { color: var(--red); }

    /* ── Quick Actions ── */
    .actions-grid {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 10px;
    }
    .action-btn {
      display: flex;
      flex-direction: column;
      align-items: center;
      gap: 6px;
      padding: 14px 10px;
      border-radius: 12px;
      background: var(--bg);
      text-decoration: none;
      color: var(--text);
      font-size: 0.8rem;
      font-weight: 500;
      transition: background 0.2s;
    }
    .action-btn:hover { background: #e2e8f0; }
    .action-btn .material-icons-round { font-size: 22px; color: var(--primary); }

    /* ── Full-width card ── */
    .card.full { grid-column: 1 / -1; }

    /* ── Transaction List ── */
    .txn-list { display: flex; flex-direction: column; gap: 10px; }
    .txn-item {
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: 12px 14px;
      background: var(--bg);
      border-radius: 10px;
    }
    .txn-left  { display: flex; align-items: center; gap: 10px; }
    .txn-icon  { font-size: 20px; }
    .txn-title { font-size: 0.85rem; font-weight: 500; color: var(--text); }
    .txn-date  { font-size: 0.72rem; color: var(--muted); }
    .txn-amt   { font-size: 0.9rem; font-weight: 600; }
    .txn-empty { text-align: center; color: var(--muted); padding: 20px; font-size: 0.85rem; }

    /* ── View All link ── */
    .view-all { font-size: 0.8rem; color: var(--primary); text-decoration: none; font-weight: 500; }

    /* ══════════ MOBILE ══════════ */
    @media (max-width: 768px) {
      .mob-header { display: flex; }

      .page-wrap {
        margin-left: 0;
        padding: 12px;
      }

      .welcome-bar { margin-bottom: 14px; }
      .welcome-bar h2 { font-size: 1.1rem; }

      .stats-row {
        grid-template-columns: 1fr;
        gap: 10px;
        margin-bottom: 14px;
      }

      .stat-card { padding: 14px 16px; }
      .stat-card .value { font-size: 1.2rem; }

      .main-grid {
        grid-template-columns: 1fr;
        gap: 12px;
      }

      .card { padding: 16px; }

      .actions-grid { grid-template-columns: repeat(2, 1fr); gap: 8px; }
      .action-btn { padding: 12px 8px; font-size: 0.75rem; }

      .price-row .p-val { font-size: 1rem; }

      .txn-item { padding: 10px 12px; }
      .txn-title { font-size: 0.8rem; }
      .txn-amt   { font-size: 0.82rem; }
    }

    @media (max-width: 400px) {
      .stats-row { grid-template-columns: 1fr; }
      .actions-grid { grid-template-columns: repeat(2, 1fr); }
      .welcome-bar h2 { font-size: 1rem; }
    }
  </style>
</head>
<body>

<?php include('../sidebar.php'); ?>

<!-- Mobile Header -->
<div class="mob-header">
  <img src="../image/Dollario-logo .svg" alt="Dollario">
  <button id="menuToggle">☰</button>
</div>
<script>
  document.addEventListener('DOMContentLoaded', function () {
    var btn = document.getElementById('menuToggle');
    if (btn) btn.addEventListener('click', function () {
      if (window.toggleUserSidebar) window.toggleUserSidebar();
    });
  });
</script>

<div class="page-wrap">

  <!-- Welcome -->
  <div class="welcome-bar">
  <?php if (isset($_GET['help_sent'])): ?>
    <div style="position:fixed;top:20px;right:20px;background:#f0fdf4;color:#166534;border:1px solid #bbf7d0;padding:14px 20px;border-radius:10px;font-size:0.88rem;font-weight:600;z-index:9999;box-shadow:0 4px 12px rgba(0,0,0,0.1)">
      ✅ Help request submitted! We'll get back to you soon.
    </div>
    <script>setTimeout(()=>{const el=document.querySelector('[style*="f0fdf4"]');if(el)el.remove();},4000);</script>
  <?php endif; ?>
    <div>
      <h2>Welcome, <?php echo htmlspecialchars($user['username'] ?? 'User'); ?> 👋</h2>
      <span><?php echo date('l, d M Y'); ?></span>
    </div>
  </div>

  <!-- Stats Row -->
  <div class="stats-row">
    <div class="stat-card primary">
      <div class="label">Total Balance</div>
      <div class="value">₹<?php echo number_format($totalBalance, 2); ?></div>
      <div class="sub">INR + USDT combined</div>
    </div>
    <div class="stat-card">
      <div class="label">USDT Balance</div>
      <div class="value"><?php echo number_format($wallet['usdt_balance'], 2); ?> <small style="font-size:0.75rem;font-weight:400">USDT</small></div>
      <div class="sub">≈ ₹<?php echo number_format($totalUSDTinINR, 2); ?></div>
    </div>
    <div class="stat-card">
      <div class="label">INR Balance</div>
      <div class="value">₹<?php echo number_format($wallet['inr_balance'], 2); ?></div>
      <div class="sub">Available to withdraw</div>
    </div>
  </div>

  <!-- Main Grid -->
  <div class="main-grid">

    <!-- Market Overview -->
    <div class="card">
      <div class="card-head">
        <h3><span class="material-icons-round">trending_up</span> Market Overview</h3>
        <span class="live-badge">● Live</span>
      </div>
      <div class="chart-wrap">
        <canvas id="priceChart"></canvas>
      </div>
      <div class="price-row">
        <div>
          <div class="p-label">Current Price</div>
          <div class="p-val">₹<?php echo number_format($currentPrice, 2); ?></div>
        </div>
        <div style="text-align:right">
          <div class="p-label">24h Change</div>
          <div class="p-change <?php echo $priceChange24h >= 0 ? 'up' : 'down'; ?>">
            <?php echo ($priceChange24h >= 0 ? '+' : '') . number_format($priceChange24h, 2); ?>%
          </div>
        </div>
      </div>
    </div>

    <!-- Quick Actions -->
    <div class="card">
      <div class="card-head">
        <h3><span class="material-icons-round">flash_on</span> Quick Actions</h3>
      </div>
      <div class="actions-grid">
        <a href="../page/sdw/buy.php" class="action-btn">
          <span class="material-icons-round">shopping_cart</span>Buy USDT
        </a>
        <a href="../page/sdw/sell.php" class="action-btn">
          <span class="material-icons-round">upload</span>Sell USDT
        </a>
        <a href="../page/sdw/usdt_deposit.php" class="action-btn">
          <span class="material-icons-round">currency_bitcoin</span>Deposit USDT
        </a>
        <a href="../page/sdw/deposit.php" class="action-btn">
          <span class="material-icons-round">account_balance</span>Deposit INR
        </a>
        <a href="../page/sdw/withdraw.php" class="action-btn">
          <span class="material-icons-round">payments</span>Withdraw INR
        </a>
        <a href="transactions.php" class="action-btn">
          <span class="material-icons-round">receipt_long</span>History
        </a>
      </div>
    </div>

    <!-- Recent Activity - full width -->
    <div class="card full">
      <div class="card-head">
        <h3><span class="material-icons-round">history</span> Recent Activity</h3>
        <?php if (!empty($transactions)): ?>
          <a href="transactions.php" class="view-all">View All →</a>
        <?php endif; ?>
      </div>
      <div class="txn-list">
        <?php if (!empty($transactions)): ?>
          <?php foreach ($transactions as $txn):
            $isCredit = in_array($txn['type'], ['deposit', 'buy']);
            $color    = $isCredit ? 'var(--green)' : 'var(--red)';
            $icon     = $isCredit ? 'arrow_circle_up' : 'arrow_circle_down';
            $prefix   = $isCredit ? '+' : '-';
            $amt      = ($txn['currency'] === 'INR' ? '₹' : '') . number_format($txn['amount'], 2) . ($txn['currency'] === 'USDT' ? ' USDT' : '');
          ?>
          <div class="txn-item">
            <div class="txn-left">
              <span class="material-icons-round txn-icon" style="color:<?php echo $color; ?>"><?php echo $icon; ?></span>
              <div>
                <div class="txn-title"><?php echo htmlspecialchars($txn['description'] ?? ucfirst($txn['type'])); ?></div>
                <div class="txn-date"><?php echo date('d M Y, h:i A', strtotime($txn['created_at'])); ?></div>
              </div>
            </div>
            <div class="txn-amt" style="color:<?php echo $color; ?>"><?php echo $prefix . $amt; ?></div>
          </div>
          <?php endforeach; ?>
        <?php else: ?>
          <div class="txn-empty">No recent transactions</div>
        <?php endif; ?>
      </div>
    </div>

  </div><!-- /main-grid -->
</div><!-- /page-wrap -->

<!-- Buy Modal (responsive) -->
<div id="buyModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.5);z-index:1000;display:none;align-items:center;justify-content:center;padding:16px;">
  <div style="background:#fff;border-radius:14px;padding:24px;width:100%;max-width:400px;">
    <h3 style="margin-bottom:16px;font-size:1rem;">Buy USDT</h3>
    <form action="?page=buysell&action=buy-process" method="POST">
      <input type="hidden" name="coin" value="USDT">
      <label style="font-size:0.85rem;">Amount (USDT)</label>
      <input type="number" name="usdt_amount" id="usdt_amount" placeholder="e.g. 10" required oninput="calcTotal()" style="width:100%;padding:10px;margin:6px 0 14px;border:1px solid #e2e8f0;border-radius:8px;font-size:0.9rem;">
      <label style="font-size:0.85rem;">Price (INR per USDT)</label>
      <input type="number" name="price" id="price" value="85" required oninput="calcTotal()" style="width:100%;padding:10px;margin:6px 0 14px;border:1px solid #e2e8f0;border-radius:8px;font-size:0.9rem;">
      <label style="font-size:0.85rem;font-weight:600;">Total (INR)</label>
      <input type="text" id="total" name="total" readonly style="width:100%;padding:10px;margin:6px 0 20px;border:1px solid #e2e8f0;border-radius:8px;background:#f8fafc;font-size:0.9rem;">
      <button type="submit" style="width:100%;padding:12px;background:var(--primary);color:#fff;border:none;border-radius:8px;font-size:0.95rem;cursor:pointer;">Buy USDT</button>
      <button type="button" onclick="document.getElementById('buyModal').style.display='none'" style="width:100%;padding:10px;margin-top:8px;background:#f1f5f9;border:none;border-radius:8px;cursor:pointer;">Cancel</button>
    </form>
  </div>
</div>

<script>
function calcTotal() {
  var a = parseFloat(document.getElementById('usdt_amount').value) || 0;
  var p = parseFloat(document.getElementById('price').value) || 0;
  document.getElementById('total').value = (a * p).toFixed(2);
}

// Price Chart
const ctx = document.getElementById('priceChart').getContext('2d');
const priceChart = new Chart(ctx, {
  type: 'line',
  data: {
    labels: Array.from({length: 24}, (_, i) => i + ':00'),
    datasets: [{
      data: Array.from({length: 24}, () => (<?php echo $currentPrice; ?> + (Math.random()-0.5)*2).toFixed(2)),
      borderColor: '#6366f1',
      backgroundColor: 'rgba(99,102,241,0.08)',
      tension: 0.4, fill: true, pointRadius: 0, borderWidth: 2
    }]
  },
  options: {
    responsive: true, maintainAspectRatio: false,
    plugins: { legend: { display: false } },
    scales: { x: { display: false }, y: { display: false } }
  }
});

setInterval(() => {
  const d = priceChart.data.datasets[0].data.slice(1);
  d.push((parseFloat(d[d.length-1]) + (Math.random()-0.5)*0.5).toFixed(2));
  priceChart.data.datasets[0].data = d;
  priceChart.update('none');
}, 5000);
</script>

<!-- Help Chat Button -->
<button id="helpBtn" onclick="toggleHelpPanel()" style="position:fixed;bottom:24px;right:24px;z-index:2000;width:54px;height:54px;border-radius:50%;background:#075e54;border:none;cursor:pointer;box-shadow:0 4px 16px rgba(0,0,0,0.25);display:flex;align-items:center;justify-content:center;">
  <span class="material-icons-round" style="color:#fff;font-size:26px">support_agent</span>
</button>

<!-- Help Chat Panel -->
<div id="helpPanel" style="display:none;position:fixed;bottom:90px;right:24px;z-index:1999;width:380px;height:560px;max-width:calc(100vw - 32px);max-height:calc(100vh - 110px);border-radius:16px;overflow:hidden;box-shadow:0 8px 32px rgba(0,0,0,0.22);">
  <iframe src="my_help_requests.php?embed=1" style="width:100%;height:100%;border:none;"></iframe>
</div>

<script>
function toggleHelpPanel() {
  var panel = document.getElementById('helpPanel');
  panel.style.display = (panel.style.display === 'none' || panel.style.display === '') ? 'block' : 'none';
}
</script>

</body>
</html>
