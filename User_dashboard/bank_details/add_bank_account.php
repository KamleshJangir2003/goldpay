<?php
session_name('user_session');
session_start();
require_once __DIR__ . '/../config/db.php';

if (!isset($_SESSION['user_id'])) { header("Location: ../auth/login.php"); exit; }
$userId = $_SESSION['user_id'];

$error = $success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $bank_name      = trim($_POST['bank_name'] ?? '');
    $account_holder = trim($_POST['account_holder'] ?? '');
    $account_number = trim($_POST['account_number'] ?? '');
    $ifsc_code      = strtoupper(trim($_POST['ifsc_code'] ?? ''));

    if (!$bank_name || !$account_holder || !$account_number || !$ifsc_code) {
        $error = "All fields are required.";
    } else {
        // Check if first account — make it primary
        $countStmt = $pdo->prepare("SELECT COUNT(*) FROM bank_accounts WHERE user_id = ?");
        $countStmt->execute([$userId]);
        $isPrimary = ($countStmt->fetchColumn() == 0) ? 1 : 0;

        $pdo->prepare("INSERT INTO bank_accounts (user_id, bank_name, account_holder, account_number, ifsc_code, is_primary) VALUES (?, ?, ?, ?, ?, ?)")
            ->execute([$userId, $bank_name, $account_holder, $account_number, $ifsc_code, $isPrimary]);

        header("Location: ../page/profile.php?success=bank_added"); exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Add Bank Account - Goldpay</title>
  <link rel="icon" type="image/x-icon" href="../../favicon.ico">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link href="https://fonts.googleapis.com/icon?family=Material+Icons+Round" rel="stylesheet">
  <style>
    * { margin:0; padding:0; box-sizing:border-box; font-family:'Poppins',sans-serif; }
    body { background:#f1f5f9; min-height:100vh; display:flex; align-items:center; justify-content:center; padding:20px; }
    .card { background:#fff; border-radius:16px; box-shadow:0 4px 20px rgba(0,0,0,0.08); padding:32px; width:100%; max-width:460px; }
    .card-header { display:flex; align-items:center; gap:12px; margin-bottom:24px; }
    .card-header .icon { background:#eff6ff; color:#2563eb; border-radius:12px; padding:10px; display:flex; }
    .card-header h2 { font-size:1.2rem; font-weight:700; color:#1e293b; }
    label { font-size:0.83rem; font-weight:600; color:#374151; display:block; margin-bottom:5px; }
    input, select { width:100%; padding:11px 14px; border:1.5px solid #e2e8f0; border-radius:10px; font-size:0.92rem; outline:none; margin-bottom:16px; background:#fff; transition:border-color 0.2s; }
    input:focus, select:focus { border-color:#6366f1; }
    .btn-submit { width:100%; padding:13px; background:linear-gradient(135deg,#6366f1,#4f46e5); color:#fff; border:none; border-radius:10px; font-size:1rem; font-weight:700; cursor:pointer; display:flex; align-items:center; justify-content:center; gap:8px; }
    .btn-submit:hover { opacity:0.9; }
    .btn-back { display:block; text-align:center; margin-top:14px; color:#6366f1; text-decoration:none; font-size:0.88rem; }
    .alert { padding:12px 16px; border-radius:10px; margin-bottom:18px; font-size:0.85rem; font-weight:500; }
    .alert.error { background:#fef2f2; color:#991b1b; border:1px solid #fecaca; }
    .info-box { background:#eff6ff; border:1px solid #bfdbfe; border-radius:10px; padding:12px 14px; margin-bottom:20px; font-size:0.82rem; color:#1e40af; }
  </style>
</head>
<body>
<div class="card">
  <div class="card-header">
    <div class="icon"><span class="material-icons-round">account_balance</span></div>
    <h2>Add Bank Account</h2>
  </div>

  <?php if ($error): ?>
    <div class="alert error"><?= htmlspecialchars($error) ?></div>
  <?php endif; ?>

  <div class="info-box">
    💡 Your bank account will be used for INR withdrawals. First account is set as primary automatically.
  </div>

  <form method="POST">
    <label>Bank Name</label>
    <select name="bank_name" required>
      <option value="">-- Select Bank --</option>
      <?php foreach (['SBI','HDFC Bank','ICICI Bank','Axis Bank','Kotak Mahindra','Punjab National Bank','Bank of Baroda','Canara Bank','Union Bank','IndusInd Bank','Yes Bank','IDFC First Bank','Other'] as $b): ?>
        <option value="<?= $b ?>" <?= (($_POST['bank_name'] ?? '') === $b) ? 'selected' : '' ?>><?= $b ?></option>
      <?php endforeach; ?>
    </select>

    <label>Account Holder Name</label>
    <input type="text" name="account_holder" placeholder="As per bank records" value="<?= htmlspecialchars($_POST['account_holder'] ?? '') ?>" required>

    <label>Account Number</label>
    <input type="text" name="account_number" placeholder="Enter account number" value="<?= htmlspecialchars($_POST['account_number'] ?? '') ?>" required>

    <label>IFSC Code</label>
    <input type="text" name="ifsc_code" placeholder="e.g. HDFC0001234" maxlength="11" style="text-transform:uppercase" value="<?= htmlspecialchars($_POST['ifsc_code'] ?? '') ?>" required>

    <button type="submit" class="btn-submit">
      <span class="material-icons-round">add</span>
      Add Bank Account
    </button>
  </form>
  <a href="../page/profile.php" class="btn-back">← Back to Profile</a>
</div>
</body>
</html>
