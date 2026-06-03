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
$inrBalance = floatval($wallet['inr_balance'] ?? 0);

// Fetch user bank accounts
$bankStmt = $pdo->prepare("SELECT * FROM bank_accounts WHERE user_id = ?");
$bankStmt->execute([$userId]);
$bankAccounts = $bankStmt->fetchAll(PDO::FETCH_ASSOC);

// Check auto-approve setting
$autoApprove = false;
try {
    $s = $pdo->query("SELECT setting_value FROM settings WHERE setting_group='payment' AND setting_key='deposit_approval' LIMIT 1");
    $autoApprove = ($s && $s->fetchColumn() == '0'); // 0 = auto approve ON (no manual needed)
} catch (Exception $e) {}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $amount = floatval($_POST['amount']);
    $method = htmlspecialchars($_POST['method']);
    $utr    = htmlspecialchars(trim($_POST['utr'] ?? ''));
    $bankId = intval($_POST['bank_id'] ?? 0);

    if ($amount < 100) {
        $message = "Minimum deposit amount is ₹100.";
        $msgType = "error";
    } elseif (empty($utr)) {
        $message = "Please enter UTR / Reference Number.";
        $msgType = "error";
    } else {
        // UTR duplicate check
        $utrCheck = $pdo->prepare("SELECT id FROM inr_deposits WHERE utr_number = ? AND status != 'rejected' LIMIT 1");
        $utrCheck->execute([$utr]);
        if ($utrCheck->fetch()) {
            $message = "This UTR number has already been used. Please enter a valid UTR.";
            $msgType = "error";
        } else {
            $newStatus = $autoApprove ? 'approved' : 'pending';
            $approvedAt = $autoApprove ? date('Y-m-d H:i:s') : null;

            $insertDeposit = $pdo->prepare("INSERT INTO inr_deposits (user_id, amount, method, utr_number, bank_id, status, approved_at, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())");
            $insertDeposit->execute([$userId, $amount, $method, $utr, $bankId ?: null, $newStatus, $approvedAt]);
            $depositId = $pdo->lastInsertId();

            if ($autoApprove) {
                // Credit wallet immediately
                $pdo->prepare("UPDATE wallets SET inr_balance = inr_balance + ? WHERE user_id = ?")->execute([$amount, $userId]);
                $pdo->prepare("INSERT INTO user_transactions (user_id, type, amount, currency, description, status, created_at) VALUES (?, 'deposit', ?, 'INR', ?, 'completed', NOW())")
                    ->execute([$userId, $amount, "INR Deposit via $method — UTR: $utr"]);
            } else {
                $pdo->prepare("INSERT INTO user_transactions (user_id, type, amount, currency, description, status, created_at) VALUES (?, 'deposit', ?, 'INR', ?, 'pending', NOW())")
                    ->execute([$userId, $amount, "INR Deposit via $method — UTR: $utr"]);
            }

            // Send email
            $uRow = $pdo->prepare("SELECT email, username FROM users WHERE id = ?");
            $uRow->execute([$userId]);
            $uData = $uRow->fetch(PDO::FETCH_ASSOC);
            if ($uData) {
                $statusLabel = $autoApprove ? 'Auto Approved ✅' : 'Pending (Admin Approval)';
                sendTransactionEmail($uData['email'], $uData['username'] ?? 'User', '💰 INR Deposit - Goldpay', [
                    'Transaction Type' => 'INR Deposit',
                    'Amount'           => '₹' . number_format($amount, 2),
                    'Payment Method'   => $method,
                    'UTR / Reference'  => $utr,
                    'Status'           => $statusLabel,
                    'Date & Time'      => date('d M Y, h:i A'),
                ]);
            }

            if (!$autoApprove) {
                $uRow2 = $pdo->prepare("SELECT username FROM users WHERE id = ?");
                $uRow2->execute([$userId]);
                $uName = ($uRow2->fetch(PDO::FETCH_ASSOC))['username'] ?? 'User';
                addAdminNotif($pdo, 'INR Deposit Request', "$uName ne Rs." . number_format($amount, 2) . " deposit request submit ki (UTR: $utr)", 'inr_deposit', $depositId);
            }

            $message = $autoApprove
                ? "✅ ₹" . number_format($amount, 2) . " deposit successful! Amount credited to your wallet."
                : "Deposit request of ₹" . number_format($amount, 2) . " submitted! Will be credited after admin approval (within 24 hours).";
            $msgType = "success";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Deposit INR - Goldpay</title>
  <link rel="icon" type="image/x-icon" href="../../../favicon.ico">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link href="https://fonts.googleapis.com/icon?family=Material+Icons+Round" rel="stylesheet">
  <style>
    * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Poppins', sans-serif; }
    body { background: #f8fafc; display: flex; justify-content: center; align-items: flex-start; min-height: 100vh; padding: 30px 20px; }
    .card { background: #fff; border-radius: 16px; box-shadow: 0 4px 20px rgba(0,0,0,0.08); padding: 36px; width: 100%; max-width: 460px; }
    .card-header { display: flex; align-items: center; gap: 12px; margin-bottom: 28px; }
    .card-header .icon { background: #dcfce7; color: #16a34a; border-radius: 12px; padding: 10px; display: flex; }
    .card-header h2 { font-size: 1.3rem; font-weight: 700; color: #1e293b; }
    .balance-box { background: #f8fafc; border-radius: 12px; padding: 14px 18px; margin-bottom: 24px; display: flex; justify-content: space-between; align-items: center; }
    .balance-box label { font-size: 0.78rem; color: #64748b; }
    .balance-box .val { font-size: 1.05rem; font-weight: 700; color: #1e293b; }
    label { font-size: 0.85rem; font-weight: 600; color: #374151; display: block; margin-bottom: 6px; }
    input, select { width: 100%; padding: 11px 14px; border: 1.5px solid #e2e8f0; border-radius: 10px; font-size: 0.95rem; outline: none; transition: border-color 0.2s; margin-bottom: 16px; background: #fff; }
    input:focus, select:focus { border-color: #6366f1; }
    .upi-box { background: #eff6ff; border: 1.5px dashed #93c5fd; border-radius: 12px; padding: 16px; margin-bottom: 20px; text-align: center; }
    .upi-box .upi-id { font-size: 1.1rem; font-weight: 700; color: #1d4ed8; }
    .upi-box small { color: #64748b; font-size: 0.8rem; }
    .btn-deposit { width: 100%; padding: 13px; background: linear-gradient(135deg, #22c55e, #16a34a); color: white; border: none; border-radius: 10px; font-size: 1rem; font-weight: 700; cursor: pointer; }
    .btn-deposit:hover { opacity: 0.9; }
    .btn-back { display: block; text-align: center; margin-top: 14px; color: #6366f1; text-decoration: none; font-size: 0.88rem; }
    .alert { padding: 12px 16px; border-radius: 10px; margin-bottom: 20px; font-size: 0.88rem; font-weight: 500; }
    .alert.success { background: #f0fdf4; color: #166534; border: 1px solid #bbf7d0; }
    .alert.error { background: #fef2f2; color: #991b1b; border: 1px solid #fecaca; }
    .note { font-size: 0.78rem; color: #64748b; margin-top: -10px; margin-bottom: 16px; }
  </style>
</head>
<body>
  <div class="card">
    <div class="card-header">
      <div class="icon"><span class="material-icons-round">account_balance</span></div>
      <h2>Deposit INR</h2>
    </div>

    <?php if ($message): ?>
      <div class="alert <?= $msgType ?>"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>

    <div class="balance-box">
      <div>
        <label>Current INR Balance</label>
        <div class="val">₹<?= number_format($inrBalance, 2) ?></div>
      </div>
      <span class="material-icons-round" style="color:#22c55e; font-size:32px;">account_balance_wallet</span>
    </div>

    <div class="upi-box">
      <div style="font-size:0.8rem; color:#64748b; margin-bottom:6px;">Send payment to</div>
      <div class="upi-id">dollario@upi</div>
      <small>Or Bank Transfer: HDFC Bank | A/C: 50100123456789 | IFSC: HDFC0001234</small>
    </div>

    <form method="POST">
      <label>Amount (INR)</label>
      <input type="number" name="amount" min="100" step="1" placeholder="Minimum ₹100" required>

      <label>Payment Method</label>
      <select name="method" required>
        <option value="">-- Select Method --</option>
        <option value="UPI">UPI</option>
        <option value="NEFT">NEFT / IMPS</option>
        <option value="Bank Transfer">Bank Transfer</option>
        <option value="Paytm">Paytm</option>
      </select>

      <label>UTR / Reference Number</label>
      <input type="text" name="utr" placeholder="Enter UTR or transaction reference" required>
      <p class="note">⚠️ Enter the UTR/reference number from your payment app after sending money.</p>

      <?php if (!empty($bankAccounts)): ?>
      <label>Your Bank Account (optional)</label>
      <select name="bank_id">
        <option value="">-- Select Account --</option>
        <?php foreach ($bankAccounts as $bank): ?>
          <option value="<?= $bank['id'] ?>"><?= htmlspecialchars($bank['bank_name']) ?> — <?= htmlspecialchars($bank['account_number']) ?></option>
        <?php endforeach; ?>
      </select>
      <?php endif; ?>

      <button type="submit" class="btn-deposit">
        <span class="material-icons-round" style="vertical-align:middle; font-size:18px;">send</span>
        Submit Deposit Request
      </button>
    </form>
    <a href="../../page/dashboard.php" class="btn-back">← Back to Dashboard</a>
  </div>
</body>
</html>
