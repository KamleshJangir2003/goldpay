<?php
session_name('user_session');
session_start();
require '../../config/db.php';
require '../../includes/transaction_mailer.php';
require_once '../../config/notify_admin.php';

// Auto-create tables if missing
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS `user_transactions` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `user_id` int(11) NOT NULL,
        `type` varchar(30) NOT NULL,
        `amount` decimal(15,2) NOT NULL,
        `currency` varchar(10) NOT NULL DEFAULT 'INR',
        `description` text DEFAULT NULL,
        `status` varchar(20) NOT NULL DEFAULT 'pending',
        `chain` varchar(50) DEFAULT NULL,
        `wallet_address` varchar(100) DEFAULT NULL,
        `created_at` datetime NOT NULL DEFAULT current_timestamp(),
        PRIMARY KEY (`id`),
        KEY `user_id` (`user_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $pdo->exec("CREATE TABLE IF NOT EXISTS `inr_deposits` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `user_id` int(11) NOT NULL,
        `amount` decimal(15,2) NOT NULL,
        `method` varchar(50) DEFAULT NULL,
        `utr_number` varchar(100) DEFAULT NULL,
        `bank_id` int(11) DEFAULT NULL,
        `status` enum('pending','approved','rejected') NOT NULL DEFAULT 'pending',
        `approved_at` datetime DEFAULT NULL,
        `created_at` datetime NOT NULL DEFAULT current_timestamp(),
        PRIMARY KEY (`id`),
        KEY `user_id` (`user_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
} catch (PDOException $e) { /* already exists */ }

$userId = $_SESSION['user_id'] ?? 0;
if (!$userId) {
    header('Location: ../../auth/login.php');
    exit;
}

$message = '';
$msgType = '';

// Fetch wallet
$stmt = $pdo->prepare("SELECT * FROM wallets WHERE user_id = ?");
$stmt->execute([$userId]);
$wallet = $stmt->fetch(PDO::FETCH_ASSOC);
$usdtBalance = floatval($wallet['usdt_balance'] ?? 0);

// Fetch rate from settings
require '../../config/usdt_rate.php';

// Fetch sell price options
function getSellOption($pdo, $key, $default) {
    try {
        $s = $pdo->prepare("SELECT setting_value FROM settings WHERE setting_group='rates' AND setting_key=? LIMIT 1");
        $s->execute([$key]); $r = $s->fetch(PDO::FETCH_ASSOC);
        return $r ? $r['setting_value'] : $default;
    } catch (Exception $e) { return $default; }
}
$sellRate1  = floatval(getSellOption($pdo, 'usdt_sell_rate_1', $usdtRate));
$sellRate2  = floatval(getSellOption($pdo, 'usdt_sell_rate_2', $usdtRate));
$sellLabel1 = getSellOption($pdo, 'usdt_sell_label_1', 'Mixed Fund');
$sellLabel2 = getSellOption($pdo, 'usdt_sell_label_2', 'Premium Rate');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $amount = floatval($_POST['usdt_amount']);
    $selectedRate = ($_POST['sell_rate_choice'] ?? '1') === '2' ? $sellRate2 : $sellRate1;
    $selectedLabel = ($_POST['sell_rate_choice'] ?? '1') === '2' ? $sellLabel2 : $sellLabel1;

    if ($amount <= 0) {
        $message = "Invalid amount!";
        $msgType = "error";
    } elseif ($amount > $usdtBalance) {
        $message = "Insufficient USDT balance! Available: " . number_format($usdtBalance, 2) . " USDT";
        $msgType = "error";
    } else {
        $inrCredit = $amount * $selectedRate;
        $newUsdt = $usdtBalance - $amount;
        $newInr = floatval($wallet['inr_balance']) + $inrCredit;

        // Update wallet
        $pdo->prepare("UPDATE wallets SET usdt_balance = ?, inr_balance = ? WHERE user_id = ?")
            ->execute([$newUsdt, $newInr, $userId]);

        // Record transaction
        $pdo->prepare("INSERT INTO user_transactions (user_id, type, amount, currency, description, status, created_at) VALUES (?, 'sell', ?, 'USDT', ?, 'completed', NOW())")
            ->execute([$userId, $amount, "Sold $amount USDT @ ₹$selectedRate ($selectedLabel) = ₹" . number_format($inrCredit, 2)]);

        // Send email
        $uRow = $pdo->prepare("SELECT email, username FROM users WHERE id = ?");
        $uRow->execute([$userId]);
        $uData = $uRow->fetch(PDO::FETCH_ASSOC);
        if ($uData) {
            sendTransactionEmail($uData['email'], $uData['username'] ?? 'User', '✅ Sell USDT Successful - Goldpay', [
                'Transaction Type' => 'Sell USDT',
                'USDT Sold'        => number_format($amount, 4) . ' USDT',
                'INR Received'     => '₹' . number_format($inrCredit, 2),
                'Rate Used'        => $selectedLabel . ' — 1 USDT = ₹' . number_format($selectedRate, 2),
                'Status'           => 'Completed',
                'Date & Time'      => date('d M Y, h:i A'),
            ]);
        }

        $message = "Successfully sold $amount USDT for ₹" . number_format($inrCredit, 2) . " ($selectedLabel)!";
        $msgType = "success";
        $usdtBalance = $newUsdt;
        $wallet['inr_balance'] = $newInr;
        $uName2 = $uData['username'] ?? 'User#'.$userId;
        addAdminNotif($pdo, 'Sell USDT', "$uName2 ne ".number_format($amount,4)." USDT becha Rs.".number_format($inrCredit,2)." me ($selectedLabel)", 'sell_usdt');
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Sell Crypto - Goldpay</title>
  <link rel="icon" type="image/x-icon" href="../../../favicon.ico">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link href="https://fonts.googleapis.com/icon?family=Material+Icons+Round" rel="stylesheet">
  <style>
    * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Poppins', sans-serif; }
    body { background: #f8fafc; display: flex; justify-content: center; align-items: center; min-height: 100vh; padding: 20px; }
    .card {
      background: #fff;
      border-radius: 16px;
      box-shadow: 0 4px 20px rgba(0,0,0,0.08);
      padding: 36px;
      width: 100%;
      max-width: 440px;
    }
    .card-header { display: flex; align-items: center; gap: 12px; margin-bottom: 28px; }
    .card-header .icon { background: #fef3c7; color: #d97706; border-radius: 12px; padding: 10px; display: flex; }
    .card-header h2 { font-size: 1.3rem; font-weight: 700; color: #1e293b; }
    .balance-box {
      background: #f8fafc;
      border-radius: 12px;
      padding: 16px;
      margin-bottom: 24px;
      display: flex;
      justify-content: space-between;
    }
    .balance-box .item label { font-size: 0.78rem; color: #64748b; }
    .balance-box .item .val { font-size: 1rem; font-weight: 600; color: #1e293b; }
    .rate-badge { background: #ede9fe; color: #7c3aed; font-size: 0.8rem; padding: 4px 10px; border-radius: 20px; font-weight: 600; margin-bottom: 20px; display: inline-block; }
    .price-options { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 20px; }
    .price-option {
      border: 2px solid #e2e8f0;
      border-radius: 12px;
      padding: 14px;
      cursor: pointer;
      transition: all 0.2s;
      text-align: center;
    }
    .price-option:hover { border-color: #f59e0b; }
    .price-option.selected { border-color: #f59e0b; background: #fffbeb; }
    .price-option:nth-child(2):hover { border-color: #22c55e; }
    .price-option:nth-child(2) { background: #f0fdf4; border-color: #bbf7d0; }
    .price-option:nth-child(2).selected { border-color: #22c55e; background: #dcfce7; }
    .price-option:nth-child(2) .opt-label { color: #166534; }
    .price-option:nth-child(2) .opt-price { color: #15803d; }
    .price-option .opt-label { font-size: 0.78rem; color: #64748b; margin-bottom: 4px; }
    .price-option .opt-price { font-size: 1.1rem; font-weight: 700; color: #1e293b; }
    label { font-size: 0.85rem; font-weight: 600; color: #374151; display: block; margin-bottom: 6px; }
    input {
      width: 100%;
      padding: 11px 14px;
      border: 1.5px solid #e2e8f0;
      border-radius: 10px;
      font-size: 0.95rem;
      outline: none;
      transition: border-color 0.2s;
      margin-bottom: 16px;
    }
    input:focus { border-color: #6366f1; }
    .inr-preview {
      background: #f0fdf4;
      border: 1.5px solid #bbf7d0;
      border-radius: 10px;
      padding: 12px 14px;
      margin-bottom: 20px;
      font-size: 0.9rem;
      color: #166534;
      font-weight: 600;
    }
    .btn-sell {
      width: 100%;
      padding: 13px;
      background: linear-gradient(135deg, #f59e0b, #d97706);
      color: white;
      border: none;
      border-radius: 10px;
      font-size: 1rem;
      font-weight: 700;
      cursor: pointer;
      transition: opacity 0.2s;
    }
    .btn-sell:hover { opacity: 0.9; }
    .btn-back {
      display: block;
      text-align: center;
      margin-top: 14px;
      color: #6366f1;
      text-decoration: none;
      font-size: 0.88rem;
    }
    .alert { padding: 12px 16px; border-radius: 10px; margin-bottom: 20px; font-size: 0.88rem; font-weight: 500; }
    .alert.success { background: #f0fdf4; color: #166534; border: 1px solid #bbf7d0; }
    .alert.error { background: #fef2f2; color: #991b1b; border: 1px solid #fecaca; }
  </style>
</head>
<body>
  <div class="card">
    <div class="card-header">
      <div class="icon"><span class="material-icons-round">upload</span></div>
      <h2>Sell USDT</h2>
    </div>

    <?php if ($message): ?>
      <div class="alert <?= $msgType ?>"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>

    <div class="balance-box">
      <div class="item">
        <label>USDT Balance</label>
        <div class="val"><?= number_format($usdtBalance, 2) ?> USDT</div>
      </div>
      <div class="item" style="text-align:right;">
        <label>INR Balance</label>
        <div class="val">₹<?= number_format(floatval($wallet['inr_balance']), 2) ?></div>
      </div>
    </div>

    <div class="rate-badge">1 USDT = ₹<?= number_format($usdtRate, 2) ?> (Market)</div>

    <form method="POST">
      <p style="font-size:0.82rem;font-weight:600;color:#374151;margin-bottom:10px;">Choose Sell Rate:</p>
      <div class="price-options">
        <div class="price-option selected" onclick="selectRate(1, this)">
          <div class="opt-label"><?= htmlspecialchars($sellLabel1) ?></div>
          <div class="opt-price">₹<?= number_format($sellRate1, 2) ?></div>
        </div>
        <div class="price-option" onclick="selectRate(2, this)">
          <div class="opt-label"><?= htmlspecialchars($sellLabel2) ?></div>
          <div class="opt-price">₹<?= number_format($sellRate2, 2) ?></div>
        </div>
      </div>
      <input type="hidden" name="sell_rate_choice" id="sell_rate_choice" value="1">
      <label>Amount to Sell (USDT)</label>
      <input type="number" name="usdt_amount" id="usdt_amount" min="0.01" step="0.01"
             max="<?= $usdtBalance ?>" placeholder="Enter USDT amount" required
             oninput="calcInr(this.value)">

      <div class="inr-preview" id="inr_preview">You will receive: ₹0.00</div>

      <button type="submit" class="btn-sell">
        <span class="material-icons-round" style="vertical-align:middle; font-size:18px;">sell</span>
        Sell USDT
      </button>
    </form>
    <a href="../../page/dashboard.php" class="btn-back">← Back to Dashboard</a>
  </div>

  <script>
    const rates = { 1: <?= $sellRate1 ?>, 2: <?= $sellRate2 ?> };
    let activeRate = rates[1];

    function selectRate(choice, el) {
      document.querySelectorAll('.price-option').forEach(e => e.classList.remove('selected'));
      el.classList.add('selected');
      document.getElementById('sell_rate_choice').value = choice;
      activeRate = rates[choice];
      calcInr(document.getElementById('usdt_amount').value);
    }

    function calcInr(val) {
      const amt = parseFloat(val) || 0;
      document.getElementById('inr_preview').textContent = 'You will receive: ₹' + (amt * activeRate).toFixed(2);
    }
  </script>
</body>
</html>
