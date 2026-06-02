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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $amount  = floatval($_POST['amount']);
    $method  = htmlspecialchars($_POST['method']);

    if ($method === 'Bank Transfer') {
        $accNo   = htmlspecialchars(trim($_POST['account_number'] ?? ''));
        $ifsc    = htmlspecialchars(trim($_POST['ifsc_code'] ?? ''));
        $holder  = htmlspecialchars(trim($_POST['account_holder'] ?? ''));
        $details = "A/C: $accNo | IFSC: $ifsc | Name: $holder";
    } else {
        $details = htmlspecialchars(trim($_POST['upi_id'] ?? ''));
    }

    if ($amount < 500) {
        $message = "Minimum withdrawal amount is ₹500.";
        $msgType = "error";
    } elseif ($amount > $inrBalance) {
        $message = "Insufficient INR balance! Available: ₹" . number_format($inrBalance, 2);
        $msgType = "error";
    } elseif (empty($details)) {
        $message = "Please enter account/UPI details.";
        $msgType = "error";
    } else {
        // Deduct INR balance immediately (hold)
        $newBalance = $inrBalance - $amount;
        $pdo->prepare("UPDATE wallets SET inr_balance = ? WHERE user_id = ?")
            ->execute([$newBalance, $userId]);

        // Insert into inr_withdrawals — capture ID immediately
        $wStmt = $pdo->prepare("INSERT INTO inr_withdrawals (user_id, amount, method, account_details, status, requested_at) VALUES (?, ?, ?, ?, 'Pending', NOW())");
        $wStmt->execute([$userId, $amount, $method, $details]);
        $withdrawId = (int)$pdo->lastInsertId();

        // Record in user_transactions
        $pdo->prepare("INSERT INTO user_transactions (user_id, type, amount, currency, description, status, created_at) VALUES (?, 'withdraw_inr', ?, 'INR', ?, 'pending', NOW())")
            ->execute([$userId, $amount, "INR Withdrawal via $method — $details"]);

        // Send email
        $uRow = $pdo->prepare("SELECT email, username FROM users WHERE id = ?");
        $uRow->execute([$userId]);
        $uData = $uRow->fetch(PDO::FETCH_ASSOC);
        if ($uData) {
            sendTransactionEmail($uData['email'], $uData['username'] ?? 'User', '💸 INR Withdrawal Request Submitted - Goldpay', [
                'Transaction Type' => 'INR Withdrawal',
                'Amount'           => '₹' . number_format($amount, 2),
                'Method'           => $method,
                'Account Details'  => $details,
                'Status'           => 'Pending (Processing)',
                'Date & Time'      => date('d M Y, h:i A'),
            ]);
        }

        // Admin notification with correct ref_id
        $uName = $uData['username'] ?? 'User';
        addAdminNotif($pdo, 'INR Withdrawal Request', "$uName ne Rs." . number_format($amount, 2) . " withdrawal request ki via $method", 'inr_withdrawal', $withdrawId);

        $message = "Withdrawal request of ₹" . number_format($amount, 2) . " submitted! Will be processed within 24 hours.";
        $msgType = "success";
        $inrBalance = $newBalance;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Withdraw INR - Dollario</title>
  <link rel="icon" type="image/x-icon" href="../../../favicon.ico">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link href="https://fonts.googleapis.com/icon?family=Material+Icons+Round" rel="stylesheet">
  <style>
    * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Poppins', sans-serif; }
    body { background: #f8fafc; display: flex; justify-content: center; align-items: flex-start; min-height: 100vh; padding: 30px 20px; }
    .card {
      background: #fff;
      border-radius: 16px;
      box-shadow: 0 4px 20px rgba(0,0,0,0.08);
      padding: 36px;
      width: 100%;
      max-width: 460px;
    }
    .card-header { display: flex; align-items: center; gap: 12px; margin-bottom: 28px; }
    .card-header .icon { background: #fef2f2; color: #dc2626; border-radius: 12px; padding: 10px; display: flex; }
    .card-header h2 { font-size: 1.3rem; font-weight: 700; color: #1e293b; }
    .balance-box {
      background: #f8fafc;
      border-radius: 12px;
      padding: 14px 18px;
      margin-bottom: 24px;
      display: flex;
      justify-content: space-between;
      align-items: center;
    }
    .balance-box label { font-size: 0.78rem; color: #64748b; }
    .balance-box .val { font-size: 1.05rem; font-weight: 700; color: #1e293b; }
    label { font-size: 0.85rem; font-weight: 600; color: #374151; display: block; margin-bottom: 6px; }
    input, select {
      width: 100%;
      padding: 11px 14px;
      border: 1.5px solid #e2e8f0;
      border-radius: 10px;
      font-size: 0.95rem;
      outline: none;
      transition: border-color 0.2s;
      margin-bottom: 16px;
      background: #fff;
    }
    input:focus, select:focus { border-color: #6366f1; }
    .btn-withdraw {
      width: 100%;
      padding: 13px;
      background: linear-gradient(135deg, #ef4444, #dc2626);
      color: white;
      border: none;
      border-radius: 10px;
      font-size: 1rem;
      font-weight: 700;
      cursor: pointer;
      transition: opacity 0.2s;
    }
    .btn-withdraw:hover { opacity: 0.9; }
    .btn-back { display: block; text-align: center; margin-top: 14px; color: #6366f1; text-decoration: none; font-size: 0.88rem; }
    .alert { padding: 12px 16px; border-radius: 10px; margin-bottom: 20px; font-size: 0.88rem; font-weight: 500; }
    .alert.success { background: #f0fdf4; color: #166534; border: 1px solid #bbf7d0; }
    .alert.error { background: #fef2f2; color: #991b1b; border: 1px solid #fecaca; }
    .note { font-size: 0.78rem; color: #64748b; margin-top: -10px; margin-bottom: 16px; }
    .min-note { background: #fffbeb; border: 1px solid #fde68a; border-radius: 8px; padding: 10px 14px; font-size: 0.8rem; color: #92400e; margin-bottom: 20px; }
  </style>
</head>
<body>
  <div class="card">
    <div class="card-header">
      <div class="icon"><span class="material-icons-round">payments</span></div>
      <h2>Withdraw INR</h2>
    </div>

    <?php if ($message): ?>
      <div class="alert <?= $msgType ?>"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>

    <div class="balance-box">
      <div>
        <label>Available INR Balance</label>
        <div class="val">₹<?= number_format($inrBalance, 2) ?></div>
      </div>
      <span class="material-icons-round" style="color:#ef4444; font-size:32px;">account_balance_wallet</span>
    </div>

    <div class="min-note">⚠️ Minimum withdrawal: ₹500 | Processing time: up to 24 hours</div>

    <form method="POST">
      <label>Amount (INR)</label>
      <input type="number" name="amount" min="500" step="1"
             max="<?= $inrBalance ?>" placeholder="Minimum ₹500" required>

      <label>Withdrawal Method</label>
      <select name="method" id="method" required onchange="toggleDetails(this.value)">
        <option value="">-- Select Method --</option>
        <option value="UPI">UPI</option>
        <option value="Bank Transfer">Bank Transfer / NEFT</option>
        <option value="Paytm">Paytm</option>
        <option value="PhonePe">PhonePe</option>
      </select>

      <div id="upi_field" style="display:none;">
        <label>UPI ID / Mobile Number</label>
        <input type="text" name="upi_id" id="upi_id" placeholder="e.g. name@upi or 9876543210">
        <p class="note">Double-check your UPI ID. Withdrawals cannot be reversed.</p>
      </div>

      <div id="bank_field" style="display:none;">
        <label>Account Number</label>
        <input type="text" name="account_number" id="account_number" placeholder="Enter account number">
        
        <label>IFSC Code</label>
        <input type="text" name="ifsc_code" id="ifsc_code" placeholder="e.g. HDFC0001234">
        
        <label>Account Holder Name</label>
        <input type="text" name="account_holder" id="account_holder" placeholder="Enter account holder name">
        <p class="note">Double-check your bank details. Withdrawals cannot be reversed.</p>
      </div>

      <button type="submit" class="btn-withdraw">
        <span class="material-icons-round" style="vertical-align:middle; font-size:18px;">send</span>
        Request Withdrawal
      </button>
    </form>
    <a href="../../page/dashboard.php" class="btn-back">← Back to Dashboard</a>
  </div>

  <script>
    function toggleDetails(method) {
      document.getElementById('upi_field').style.display = 'none';
      document.getElementById('bank_field').style.display = 'none';
      
      document.getElementById('upi_id').removeAttribute('required');
      document.getElementById('account_number').removeAttribute('required');
      document.getElementById('ifsc_code').removeAttribute('required');
      document.getElementById('account_holder').removeAttribute('required');
      
      if (method === 'Bank Transfer') {
        document.getElementById('bank_field').style.display = 'block';
        document.getElementById('account_number').setAttribute('required', 'required');
        document.getElementById('ifsc_code').setAttribute('required', 'required');
        document.getElementById('account_holder').setAttribute('required', 'required');
      } else if (method === 'UPI' || method === 'Paytm' || method === 'PhonePe') {
        document.getElementById('upi_field').style.display = 'block';
        document.getElementById('upi_id').setAttribute('required', 'required');
      }
    }
  </script>
</body>
</html>
