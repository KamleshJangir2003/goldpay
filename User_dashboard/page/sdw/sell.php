<?php
session_name('user_session');
session_start();
require '../../config/db.php';
require '../../includes/transaction_mailer.php';

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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $amount = floatval($_POST['usdt_amount']);

    if ($amount <= 0) {
        $message = "Invalid amount!";
        $msgType = "error";
    } elseif ($amount > $usdtBalance) {
        $message = "Insufficient USDT balance! Available: " . number_format($usdtBalance, 2) . " USDT";
        $msgType = "error";
    } else {
        $inrCredit = $amount * $usdtRate;
        $newUsdt = $usdtBalance - $amount;
        $newInr = floatval($wallet['inr_balance']) + $inrCredit;

        // Update wallet
        $pdo->prepare("UPDATE wallets SET usdt_balance = ?, inr_balance = ? WHERE user_id = ?")
            ->execute([$newUsdt, $newInr, $userId]);

        // Record transaction
        $pdo->prepare("INSERT INTO user_transactions (user_id, type, amount, currency, description, status, created_at) VALUES (?, 'sell', ?, 'USDT', ?, 'completed', NOW())")
            ->execute([$userId, $amount, "Sold $amount USDT @ ₹$usdtRate = ₹" . number_format($inrCredit, 2)]);

        // Send email
        $uRow = $pdo->prepare("SELECT email, username FROM users WHERE id = ?");
        $uRow->execute([$userId]);
        $uData = $uRow->fetch(PDO::FETCH_ASSOC);
        if ($uData) {
            sendTransactionEmail($uData['email'], $uData['username'] ?? 'User', '✅ Sell USDT Successful - MBPAY', [
                'Transaction Type' => 'Sell USDT',
                'USDT Sold'        => number_format($amount, 4) . ' USDT',
                'INR Received'     => '₹' . number_format($inrCredit, 2),
                'Rate'             => '1 USDT = ₹' . number_format($usdtRate, 2),
                'Status'           => 'Completed',
                'Date & Time'      => date('d M Y, h:i A'),
            ]);
        }

        $message = "Successfully sold $amount USDT for ₹" . number_format($inrCredit, 2) . "!";
        $msgType = "success";
        $usdtBalance = $newUsdt;
        $wallet['inr_balance'] = $newInr;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Sell Crypto - Dollario</title>
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

    <div class="rate-badge">1 USDT = ₹<?= number_format($usdtRate, 2) ?></div>

    <form method="POST">
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
    const rate = <?= $usdtRate ?>;
    function calcInr(val) {
      const amt = parseFloat(val) || 0;
      document.getElementById('inr_preview').textContent = 'You will receive: ₹' + (amt * rate).toFixed(2);
    }
  </script>
</body>
</html>
