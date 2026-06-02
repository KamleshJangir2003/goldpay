<?php
session_name('user_session');
session_start();
require '../../config/db.php';
require '../../includes/transaction_mailer.php';
require_once '../../config/notify_admin.php';

$userId = $_SESSION['user_id'] ?? 0;
if (!$userId) { header('Location: ../../auth/login.php'); exit; }

// Fetch wallet
$stmt = $pdo->prepare("SELECT * FROM wallets WHERE user_id = ?");
$stmt->execute([$userId]);
$wallet = $stmt->fetch(PDO::FETCH_ASSOC);
$inrBalance  = floatval($wallet['inr_balance'] ?? 0);
$usdtBalance = floatval($wallet['usdt_balance'] ?? 0);

// Fetch rate from settings
require '../../config/usdt_rate.php';

// Fetch buy rate options
function getBuyOption($pdo, $key, $default) {
    try {
        $s = $pdo->prepare("SELECT setting_value FROM settings WHERE setting_group='rates' AND setting_key=? LIMIT 1");
        $s->execute([$key]); $r = $s->fetch(PDO::FETCH_ASSOC);
        return $r ? $r['setting_value'] : $default;
    } catch (Exception $e) { return $default; }
}
$buyRate1  = floatval(getBuyOption($pdo, 'usdt_sell_rate_1', $usdtRate));
$buyRate2  = floatval(getBuyOption($pdo, 'usdt_sell_rate_2', $usdtRate));
$buyLabel1 = getBuyOption($pdo, 'usdt_sell_label_1', 'Mixed Fund');
$buyLabel2 = getBuyOption($pdo, 'usdt_sell_label_2', 'Premium Rate');

$message = $msgType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $inrAmount = floatval($_POST['inr_amount']);
    $rateChoice = ($_POST['buy_rate_choice'] ?? '1') === '2' ? $buyRate2 : $buyRate1;
    $rateLabel  = ($_POST['buy_rate_choice'] ?? '1') === '2' ? $buyLabel2 : $buyLabel1;

    if ($inrAmount < 100) {
        $message = "Minimum buy amount is ₹100.";
        $msgType = "error";
    } elseif ($inrAmount > $inrBalance) {
        $message = "Insufficient INR balance! Available: ₹" . number_format($inrBalance, 2);
        $msgType = "error";
    } else {
        $usdtGet  = $inrAmount / $rateChoice;
        $newInr   = $inrBalance - $inrAmount;
        $newUsdt  = $usdtBalance + $usdtGet;

        $pdo->prepare("UPDATE wallets SET inr_balance = ?, usdt_balance = ? WHERE user_id = ?")
            ->execute([$newInr, $newUsdt, $userId]);

        $pdo->prepare("INSERT INTO user_transactions (user_id, type, amount, currency, description, status, created_at) VALUES (?, 'buy', ?, 'USDT', ?, 'completed', NOW())")
            ->execute([$userId, $usdtGet, "Bought " . number_format($usdtGet, 4) . " USDT @ ₹$rateChoice ($rateLabel) using ₹" . number_format($inrAmount, 2)]);

        $uRow = $pdo->prepare("SELECT email, username FROM users WHERE id = ?");
        $uRow->execute([$userId]);
        $uData = $uRow->fetch(PDO::FETCH_ASSOC);
        if ($uData) {
            sendTransactionEmail($uData['email'], $uData['username'] ?? 'User', '✅ Buy USDT Successful - Goldpay', [
                'Transaction Type' => 'Buy USDT',
                'INR Spent'        => '₹' . number_format($inrAmount, 2),
                'USDT Received'    => number_format($usdtGet, 4) . ' USDT',
                'Rate'             => $rateLabel . ' — 1 USDT = ₹' . number_format($rateChoice, 2),
                'Status'           => 'Completed',
                'Date & Time'      => date('d M Y, h:i A'),
            ]);
        }

        $message = "Successfully bought " . number_format($usdtGet, 4) . " USDT for ₹" . number_format($inrAmount, 2) . " ($rateLabel)!";
        $msgType = "success";
        $inrBalance  = $newInr;
        $usdtBalance = $newUsdt;
        $uName2 = $uData['username'] ?? 'User#'.$userId;
        addAdminNotif($pdo, 'Buy USDT', "$uName2 ne Rs.".number_format($inrAmount,2)." me ".number_format($usdtGet,4)." USDT kharida ($rateLabel)", 'buy_usdt');
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Buy USDT - Dollario</title>
  <link rel="icon" type="image/x-icon" href="../../../favicon.ico">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link href="https://fonts.googleapis.com/icon?family=Material+Icons+Round" rel="stylesheet">
  <style>
    * { margin:0; padding:0; box-sizing:border-box; font-family:'Poppins',sans-serif; }
    body { background:#f8fafc; display:flex; justify-content:center; align-items:center; min-height:100vh; padding:20px; }
    .card { background:#fff; border-radius:16px; box-shadow:0 4px 20px rgba(0,0,0,0.08); padding:36px; width:100%; max-width:440px; }
    .card-header { display:flex; align-items:center; gap:12px; margin-bottom:24px; }
    .card-header .icon { background:#dcfce7; color:#16a34a; border-radius:12px; padding:10px; display:flex; }
    .card-header h2 { font-size:1.3rem; font-weight:700; color:#1e293b; }
    .balance-box { background:#f8fafc; border-radius:12px; padding:16px; margin-bottom:20px; display:flex; justify-content:space-between; }
    .balance-box .item label { font-size:0.78rem; color:#64748b; }
    .balance-box .item .val { font-size:1rem; font-weight:600; color:#1e293b; }
    .rate-badge { background:#dcfce7; color:#166534; font-size:0.8rem; padding:4px 10px; border-radius:20px; font-weight:600; margin-bottom:20px; display:inline-block; }
    .price-options { display:grid; grid-template-columns:1fr 1fr; gap:12px; margin-bottom:20px; }
    .price-option { border:2px solid #e2e8f0; border-radius:12px; padding:14px; cursor:pointer; transition:all 0.2s; text-align:center; }
    .price-option .opt-label { font-size:0.78rem; color:#64748b; margin-bottom:4px; }
    .price-option .opt-price { font-size:1.1rem; font-weight:700; color:#1e293b; }
    .price-option:nth-child(1):hover { border-color:#f59e0b; }
    .price-option:nth-child(1).selected { border-color:#f59e0b; background:#fffbeb; }
    .price-option:nth-child(1).selected .opt-label { color:#92400e; }
    .price-option:nth-child(1).selected .opt-price { color:#b45309; }
    .price-option:nth-child(2) { background:#f0fdf4; border-color:#bbf7d0; }
    .price-option:nth-child(2):hover { border-color:#22c55e; }
    .price-option:nth-child(2).selected { border-color:#22c55e; background:#dcfce7; }
    .price-option:nth-child(2) .opt-label { color:#166534; }
    .price-option:nth-child(2) .opt-price { color:#15803d; }
    label { font-size:0.85rem; font-weight:600; color:#374151; display:block; margin-bottom:6px; }
    input { width:100%; padding:11px 14px; border:1.5px solid #e2e8f0; border-radius:10px; font-size:0.95rem; outline:none; transition:border-color 0.2s; margin-bottom:16px; }
    input:focus { border-color:#6366f1; }
    .preview-box { background:#eff6ff; border:1.5px solid #93c5fd; border-radius:10px; padding:12px 14px; margin-bottom:20px; font-size:0.9rem; color:#1d4ed8; font-weight:600; }
    .btn-buy { width:100%; padding:13px; background:linear-gradient(135deg,#22c55e,#16a34a); color:#fff; border:none; border-radius:10px; font-size:1rem; font-weight:700; cursor:pointer; }
    .btn-buy:hover { opacity:0.9; }
    .btn-back { display:block; text-align:center; margin-top:14px; color:#6366f1; text-decoration:none; font-size:0.88rem; }
    .alert { padding:12px 16px; border-radius:10px; margin-bottom:20px; font-size:0.88rem; font-weight:500; }
    .alert.success { background:#f0fdf4; color:#166534; border:1px solid #bbf7d0; }
    .alert.error { background:#fef2f2; color:#991b1b; border:1px solid #fecaca; }
    .quick-btns { display:flex; gap:8px; margin-bottom:16px; flex-wrap:wrap; }
    .quick-btn { padding:6px 14px; border:1.5px solid #e2e8f0; border-radius:8px; font-size:0.8rem; font-weight:600; cursor:pointer; background:#f8fafc; color:#374151; }
    .quick-btn:hover { border-color:#6366f1; color:#6366f1; }
  </style>
</head>
<body>
<div class="card">
  <div class="card-header">
    <div class="icon"><span class="material-icons-round">shopping_cart</span></div>
    <h2>Buy USDT</h2>
  </div>

  <?php if ($message): ?>
    <div class="alert <?= $msgType ?>"><?= htmlspecialchars($message) ?></div>
  <?php endif; ?>

  <div class="balance-box">
    <div class="item">
      <label>INR Balance</label>
      <div class="val">₹<?= number_format($inrBalance, 2) ?></div>
    </div>
    <div class="item" style="text-align:right">
      <label>USDT Balance</label>
      <div class="val"><?= number_format($usdtBalance, 4) ?> USDT</div>
    </div>
  </div>

  <div class="rate-badge">📈 1 USDT = ₹<?= number_format($usdtRate, 2) ?> (Market)</div>

  <form method="POST">
    <p style="font-size:0.82rem;font-weight:600;color:#374151;margin-bottom:10px;">Choose Buy Rate:</p>
    <div class="price-options">
      <div class="price-option selected" onclick="selectRate(1, this)">
        <div class="opt-label"><?= htmlspecialchars($buyLabel1) ?></div>
        <div class="opt-price">₹<?= number_format($buyRate1, 2) ?></div>
      </div>
      <div class="price-option" onclick="selectRate(2, this)">
        <div class="opt-label"><?= htmlspecialchars($buyLabel2) ?></div>
        <div class="opt-price">₹<?= number_format($buyRate2, 2) ?></div>
      </div>
    </div>
    <input type="hidden" name="buy_rate_choice" id="buy_rate_choice" value="1">
    <label>Enter INR Amount to Spend</label>

    <!-- Quick amount buttons -->
    <div class="quick-btns">
      <button type="button" class="quick-btn" onclick="setAmt(500)">₹500</button>
      <button type="button" class="quick-btn" onclick="setAmt(1000)">₹1,000</button>
      <button type="button" class="quick-btn" onclick="setAmt(5000)">₹5,000</button>
      <button type="button" class="quick-btn" onclick="setAmt(<?= floor($inrBalance) ?>)">Max</button>
    </div>

    <input type="number" name="inr_amount" id="inr_amount" min="100" step="1"
           max="<?= $inrBalance ?>" placeholder="Minimum ₹100" required
           oninput="calcUsdt(this.value)">

    <div class="preview-box" id="usdt_preview">
      You will get: <span id="usdt_val">0.0000</span> USDT
    </div>

    <button type="submit" class="btn-buy">
      <span class="material-icons-round" style="vertical-align:middle;font-size:18px">shopping_cart</span>
      Buy USDT
    </button>
  </form>
  <a href="../../page/dashboard.php" class="btn-back">← Back to Dashboard</a>
</div>

<script>
const rates = { 1: <?= $buyRate1 ?>, 2: <?= $buyRate2 ?> };
let activeRate = rates[1];

function selectRate(choice, el) {
  document.querySelectorAll('.price-option').forEach(e => e.classList.remove('selected'));
  el.classList.add('selected');
  document.getElementById('buy_rate_choice').value = choice;
  activeRate = rates[choice];
  calcUsdt(document.getElementById('inr_amount').value);
}
function calcUsdt(val) {
  const amt = parseFloat(val) || 0;
  document.getElementById('usdt_val').textContent = (amt / activeRate).toFixed(4);
}
function setAmt(val) {
  document.getElementById('inr_amount').value = val;
  calcUsdt(val);
}
</script>
</body>
</html>
