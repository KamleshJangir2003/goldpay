<?php
if (session_status() === PHP_SESSION_NONE) { session_name('user_session'); session_start(); }
require '../config/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

$userId = $_SESSION['user_id'];

// Fetch user
$userStmt = $pdo->prepare("SELECT id, username, email, mobile, status, two_fa_enabled, ip_address, created_at FROM users WHERE id = ?");
$userStmt->execute([$userId]);
$user = $userStmt->fetch(PDO::FETCH_ASSOC);
if (!$user) { header('Location: ../auth/login.php'); exit; }

// Last login from login_history
$llStmt = $pdo->prepare("SELECT ip_address, login_time FROM login_history WHERE user_id = ? ORDER BY login_time DESC LIMIT 1");
$llStmt->execute([$userId]);
$lastLogin = $llStmt->fetch(PDO::FETCH_ASSOC);

// Wallet
$walletStmt = $pdo->prepare("SELECT * FROM wallets WHERE user_id = ?");
$walletStmt->execute([$userId]);
$wallet = $walletStmt->fetch(PDO::FETCH_ASSOC) ?: ['inr_balance' => 0, 'usdt_balance' => 0];

// KYC - check user_kyc table first (most accurate)
$kycStmt = $pdo->prepare("SELECT status FROM user_kyc WHERE user_id = ? ORDER BY id DESC LIMIT 1");
$kycStmt->execute([$userId]);
$kycRow = $kycStmt->fetch(PDO::FETCH_ASSOC);
if (!$kycRow) {
    $kycStmt2 = $pdo->prepare("SELECT status FROM kyc_verifications WHERE user_id = ? ORDER BY id DESC LIMIT 1");
    $kycStmt2->execute([$userId]);
    $kycRow = $kycStmt2->fetch(PDO::FETCH_ASSOC);
}
$kyc = $kycRow ?: ['status' => 'not_verified'];

// Bank accounts
$bankStmt = $pdo->prepare("SELECT * FROM bank_accounts WHERE user_id = ? ORDER BY is_primary DESC, added_on DESC");
$bankStmt->execute([$userId]);
$bankAccounts = $bankStmt->fetchAll(PDO::FETCH_ASSOC);

// USDT rate
$rateRow = $pdo->query("SELECT rate FROM crypto_rates WHERE pair='USDT/INR' LIMIT 1")->fetch(PDO::FETCH_ASSOC);
$usdtRate = floatval($rateRow['rate'] ?? 89.80);

// Flash messages
$flash_success = $flash_error = '';
if (isset($_GET['success'])) {
    if ($_GET['success'] === 'profile_updated')  $flash_success = 'Profile updated successfully!';
    if ($_GET['success'] === 'password_changed') $flash_success = 'Password changed successfully!';
    if ($_GET['success'] === 'bank_added')       $flash_success = 'Bank account added successfully!';
    if ($_GET['success'] === 'primary_updated')  $flash_success = 'Primary account updated!';
}
if (isset($_GET['error'])) {
    if ($_GET['error'] === 'update_failed')      $flash_error = 'Failed to update. Please try again.';
    if ($_GET['error'] === 'incorrect_password') $flash_error = 'Current password is incorrect.';
    if ($_GET['error'] === 'password_mismatch')  $flash_error = 'New passwords do not match.';
}

// Normalize status — user_kyc uses 'approved', kyc_verifications uses 'verified'
$ksRaw = $kyc['status'] ?? 'not_verified';
$ks = ($ksRaw === 'approved') ? 'verified' : $ksRaw;
if (!in_array($ks, ['verified','pending','rejected','not_verified'])) $ks = 'not_verified';

$kycColor = ['verified' => '#22c55e', 'approved' => '#22c55e', 'pending' => '#f59e0b', 'rejected' => '#ef4444', 'not_verified' => '#ef4444'];
$kycIcon  = ['verified' => 'check_circle', 'approved' => 'check_circle', 'pending' => 'pending', 'rejected' => 'cancel', 'not_verified' => 'warning'];
$kycLabel = ['verified' => 'Identity Verified', 'approved' => 'Identity Verified', 'pending' => 'Verification Pending', 'rejected' => 'KYC Rejected', 'not_verified' => 'Not Verified'];
$kycSub   = ['verified' => 'Your account is fully verified', 'approved' => 'Your account is fully verified', 'pending' => 'Under review by our team', 'rejected' => 'Please re-submit correct documents', 'not_verified' => 'Complete KYC for full access'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>My Profile - Goldpay</title>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link href="https://fonts.googleapis.com/icon?family=Material+Icons+Round" rel="stylesheet">
  <style>
    :root {
      --primary: #6366f1; --secondary: #4f46e5;
      --bg: #f1f5f9; --surface: #fff;
      --text: #1e293b; --muted: #64748b;
      --green: #22c55e; --red: #ef4444; --yellow: #f59e0b;
      --radius: 14px; --shadow: 0 2px 12px rgba(0,0,0,0.07);
    }
    * { margin:0; padding:0; box-sizing:border-box; font-family:'Poppins',sans-serif; }
    body { background:var(--bg); min-height:100vh; }
    .page-wrap { margin-left:250px; padding:24px; }

    /* Alert */
    .alert { padding:12px 16px; border-radius:10px; margin-bottom:18px; font-size:0.88rem; font-weight:500; display:flex; align-items:center; gap:8px; }
    .alert.success { background:#f0fdf4; color:#166534; border:1px solid #bbf7d0; }
    .alert.error   { background:#fef2f2; color:#991b1b; border:1px solid #fecaca; }

    /* Profile Header Card */
    .profile-hero { background:linear-gradient(135deg,var(--primary),var(--secondary)); border-radius:var(--radius); padding:28px 32px; color:#fff; display:flex; align-items:center; gap:24px; margin-bottom:20px; box-shadow:var(--shadow); }
    .avatar { width:80px; height:80px; border-radius:50%; background:rgba(255,255,255,0.2); display:flex; align-items:center; justify-content:center; font-size:2.2rem; flex-shrink:0; }
    .profile-hero h2 { font-size:1.4rem; font-weight:700; }
    .profile-hero p  { font-size:0.85rem; opacity:0.85; margin-top:4px; }
    .hero-badge { background:rgba(255,255,255,0.2); padding:4px 12px; border-radius:20px; font-size:0.78rem; font-weight:600; display:inline-block; margin-top:8px; }

    /* Grid */
    .grid-2 { display:grid; grid-template-columns:1fr 1fr; gap:20px; }
    .grid-1 { display:grid; grid-template-columns:1fr; gap:20px; }

    /* Card */
    .card { background:var(--surface); border-radius:var(--radius); padding:24px; box-shadow:var(--shadow); }
    .card-title { font-size:0.95rem; font-weight:700; color:var(--text); display:flex; align-items:center; gap:8px; margin-bottom:18px; padding-bottom:12px; border-bottom:1px solid #f1f5f9; }
    .card-title .material-icons-round { font-size:18px; color:var(--primary); }

    /* Info rows */
    .info-row { display:flex; justify-content:space-between; align-items:center; padding:10px 0; border-bottom:1px solid #f8fafc; font-size:0.88rem; }
    .info-row:last-child { border-bottom:none; }
    .info-row .lbl { color:var(--muted); }
    .info-row .val { font-weight:600; color:var(--text); }

    /* KYC box */
    .kyc-box { display:flex; align-items:center; gap:12px; padding:14px; border-radius:10px; margin-bottom:16px; }

    /* Wallet items */
    .wallet-item { display:flex; justify-content:space-between; align-items:center; padding:12px 14px; background:var(--bg); border-radius:10px; margin-bottom:8px; }
    .wallet-item .w-label { font-size:0.82rem; color:var(--muted); }
    .wallet-item .w-val   { font-size:1rem; font-weight:700; color:var(--text); }

    /* Buttons */
    .btn { padding:9px 16px; border-radius:8px; font-size:0.85rem; font-weight:600; cursor:pointer; display:inline-flex; align-items:center; gap:6px; border:none; transition:all 0.2s; text-decoration:none; }
    .btn-primary { background:var(--primary); color:#fff; }
    .btn-primary:hover { background:var(--secondary); }
    .btn-outline { background:transparent; border:1.5px solid var(--primary); color:var(--primary); }
    .btn-outline:hover { background:#eef2ff; }
    .btn-sm { padding:6px 12px; font-size:0.78rem; }
    .btn-row { display:flex; gap:10px; flex-wrap:wrap; margin-top:14px; }

    /* Forms */
    .form-box { display:none; margin-top:18px; padding:20px; background:var(--bg); border-radius:12px; animation:fadeIn 0.3s; }
    .form-box.open { display:block; }
    @keyframes fadeIn { from{opacity:0;transform:translateY(8px)} to{opacity:1;transform:translateY(0)} }
    .form-box label { font-size:0.82rem; font-weight:600; color:var(--text); display:block; margin-bottom:5px; }
    .form-box input { width:100%; padding:10px 13px; border:1.5px solid #e2e8f0; border-radius:8px; font-size:0.9rem; margin-bottom:12px; outline:none; background:#fff; }
    .form-box input:focus { border-color:var(--primary); }
    .form-box .btn-primary { width:100%; justify-content:center; padding:11px; }

    /* Bank card */
    .bank-card { background:var(--bg); border-radius:10px; padding:14px 16px; margin-bottom:10px; display:flex; justify-content:space-between; align-items:center; }
    .bank-card .bank-name { font-weight:600; font-size:0.9rem; color:var(--text); }
    .bank-card .bank-num  { font-size:0.8rem; color:var(--muted); margin-top:2px; }
    .primary-badge { background:#dcfce7; color:#166534; font-size:0.72rem; font-weight:700; padding:3px 8px; border-radius:20px; }

    /* Status badge */
    .status-active   { color:var(--green); font-weight:600; }
    .status-pending  { color:var(--yellow); font-weight:600; }
    .status-inactive { color:var(--red); font-weight:600; }

    @media(max-width:900px) { .grid-2 { grid-template-columns:1fr; } }
    @media(max-width:768px) { .page-wrap { margin-left:0; padding:12px; } .profile-hero { flex-direction:column; text-align:center; } }
  </style>
</head>
<body>
<?php include('../sidebar.php'); ?>
<?php include('../mobile_header.php'); ?>

<div class="page-wrap">

  <?php if ($flash_success): ?>
    <div class="alert success"><span class="material-icons-round" style="font-size:18px">check_circle</span><?= htmlspecialchars($flash_success) ?></div>
  <?php endif; ?>
  <?php if ($flash_error): ?>
    <div class="alert error"><span class="material-icons-round" style="font-size:18px">error</span><?= htmlspecialchars($flash_error) ?></div>
  <?php endif; ?>

  <!-- Hero -->
  <div class="profile-hero">
    <div class="avatar"><span class="material-icons-round">person</span></div>
    <div>
      <h2><?= htmlspecialchars($user['username'] ?? 'User') ?></h2>
      <p><?= htmlspecialchars($user['email'] ?? '') ?></p>
      <span class="hero-badge">Member since <?= !empty($user['created_at']) ? date('M Y', strtotime($user['created_at'])) : 'N/A' ?></span>
    </div>
  </div>

  <div class="grid-2">

    <!-- Personal Info + Edit -->
    <div class="card">
      <div class="card-title"><span class="material-icons-round">badge</span> Personal Information</div>
      <div class="info-row"><span class="lbl">Username</span><span class="val"><?= htmlspecialchars($user['username'] ?? 'N/A') ?></span></div>
      <div class="info-row"><span class="lbl">Email</span><span class="val"><?= htmlspecialchars($user['email'] ?? 'N/A') ?></span></div>
      <div class="info-row"><span class="lbl">Mobile</span><span class="val"><?= htmlspecialchars($user['mobile'] ?? 'N/A') ?></span></div>
      <div class="info-row"><span class="lbl">Account Status</span>
        <span class="val status-<?= $user['status'] ?? 'pending' ?>"><?= ucfirst($user['status'] ?? 'pending') ?></span>
      </div>

      <div class="btn-row">
        <button class="btn btn-outline" onclick="toggleForm('edit-form')"><span class="material-icons-round">edit</span> Edit Profile</button>
        <button class="btn btn-outline" onclick="toggleForm('pass-form')"><span class="material-icons-round">lock</span> Change Password</button>
      </div>

      <!-- Edit Profile Form -->
      <div class="form-box" id="edit-form">
        <form action="../includes/edit_profile.php" method="POST">
          <label>Username</label>
          <input type="text" name="name" value="<?= htmlspecialchars($user['username'] ?? '') ?>" required>
          <label>Email</label>
          <input type="email" name="email" value="<?= htmlspecialchars($user['email'] ?? '') ?>" required>
          <label>Mobile</label>
          <input type="text" name="phone" value="<?= htmlspecialchars($user['mobile'] ?? '') ?>" required>
          <button type="submit" class="btn btn-primary"><span class="material-icons-round">save</span> Save Changes</button>
        </form>
      </div>

      <!-- Change Password Form -->
      <div class="form-box" id="pass-form">
        <form action="../includes/change_password.php" method="POST">
          <label>Current Password</label>
          <input type="password" name="old_password" placeholder="Enter current password" required>
          <label>New Password</label>
          <input type="password" name="new_password" placeholder="Enter new password" required>
          <label>Confirm New Password</label>
          <input type="password" name="confirm_password" placeholder="Confirm new password" required>
          <button type="submit" class="btn btn-primary"><span class="material-icons-round">lock_reset</span> Update Password</button>
        </form>
      </div>
    </div>

    <!-- Wallet Summary -->
    <div class="card">
      <div class="card-title"><span class="material-icons-round">account_balance_wallet</span> Wallet Summary</div>
      <div class="wallet-item">
        <div><div class="w-label">USDT Balance</div><div class="w-val"><?= number_format($wallet['usdt_balance'], 4) ?> USDT</div></div>
        <span class="material-icons-round" style="color:#ca8a04">toll</span>
      </div>
      <div class="wallet-item">
        <div><div class="w-label">INR Balance</div><div class="w-val">₹<?= number_format($wallet['inr_balance'], 2) ?></div></div>
        <span class="material-icons-round" style="color:#22c55e">currency_rupee</span>
      </div>
      <div class="wallet-item">
        <div><div class="w-label">Total Value (INR)</div><div class="w-val">₹<?= number_format($wallet['inr_balance'] + ($wallet['usdt_balance'] * $usdtRate), 2) ?></div></div>
        <span class="material-icons-round" style="color:var(--primary)">account_balance</span>
      </div>
      <div class="btn-row">
        <a href="sdw/deposit.php" class="btn btn-outline btn-sm"><span class="material-icons-round">add</span> Deposit INR</a>
        <a href="sdw/usdt_deposit.php" class="btn btn-outline btn-sm"><span class="material-icons-round">currency_bitcoin</span> Deposit USDT</a>
        <a href="sdw/withdraw.php" class="btn btn-outline btn-sm"><span class="material-icons-round">payments</span> Withdraw</a>
      </div>
    </div>

    <!-- KYC Verification -->
    <div class="card">
      <div class="card-title"><span class="material-icons-round">verified_user</span> KYC Verification</div>
      <div class="kyc-box" style="background:<?= $kycColor[$ksRaw] ?? '#ef444418' ?>18; color:<?= $kycColor[$ksRaw] ?? '#ef4444' ?>">
        <span class="material-icons-round" style="font-size:28px"><?= $kycIcon[$ksRaw] ?? 'warning' ?></span>
        <div>
          <div style="font-weight:700"><?= $kycLabel[$ksRaw] ?? 'Not Verified' ?></div>
          <div style="font-size:0.82rem;margin-top:2px"><?= $kycSub[$ksRaw] ?? 'Complete KYC for full access' ?></div>
        </div>
      </div>
      <?php if (!in_array($ksRaw, ['verified','approved'])): ?>
        <a href="kyc.php" class="btn btn-primary"><span class="material-icons-round">verified</span> Complete KYC</a>
      <?php endif; ?>
    </div>

    <!-- Security -->
    <div class="card">
      <div class="card-title"><span class="material-icons-round">security</span> Security Settings</div>
      <div class="info-row">
        <span class="lbl">2FA Authentication</span>
        <span class="val" style="color:<?= !empty($user['two_fa_enabled']) ? 'var(--green)' : 'var(--muted)' ?>">
          <?= !empty($user['two_fa_enabled']) ? '✔ Enabled' : 'Not Enabled' ?>
        </span>
      </div>
      <div class="info-row">
        <span class="lbl">Last Login</span>
        <span class="val"><?= !empty($lastLogin['login_time']) ? date('d M Y, h:i A', strtotime($lastLogin['login_time'])) : 'N/A' ?></span>
      </div>
      <div class="info-row">
        <span class="lbl">IP Address</span>
        <span class="val"><?= htmlspecialchars($lastLogin['ip_address'] ?? $user['ip_address'] ?? $_SERVER['REMOTE_ADDR']) ?></span>
      </div>
      <div class="btn-row">
        <a href="security.php" class="btn btn-outline btn-sm"><span class="material-icons-round">admin_panel_settings</span> Security Settings</a>
      </div>
    </div>

    <!-- Bank Accounts - full width -->
    <div class="card" style="grid-column:1/-1">
      <div class="card-title"><span class="material-icons-round">account_balance</span> Bank Accounts</div>
      <?php if ($bankAccounts): ?>
        <?php foreach ($bankAccounts as $bank): ?>
          <div class="bank-card">
            <div>
              <div class="bank-name"><?= htmlspecialchars($bank['bank_name']) ?></div>
              <div class="bank-num"><?= str_repeat('X', max(0, strlen($bank['account_number']) - 4)) . substr($bank['account_number'], -4) ?></div>
              <div style="font-size:0.75rem;color:var(--muted);margin-top:2px">Added <?= date('d M Y', strtotime($bank['added_on'])) ?></div>
            </div>
            <div style="display:flex;align-items:center;gap:10px">
              <?php if ($bank['is_primary']): ?>
                <span class="primary-badge">✔ Primary</span>
              <?php else: ?>
                <form method="POST" action="../bank_details/set_primary_account.php">
                  <input type="hidden" name="bank_id" value="<?= $bank['id'] ?>">
                  <button class="btn btn-outline btn-sm" type="submit">Set Primary</button>
                </form>
              <?php endif; ?>
            </div>
          </div>
        <?php endforeach; ?>
      <?php else: ?>
        <p style="color:var(--muted);font-size:0.88rem">No bank accounts added yet.</p>
      <?php endif; ?>
      <div class="btn-row">
        <a href="../bank_details/add_bank_account.php" class="btn btn-primary btn-sm"><span class="material-icons-round">add</span> Add Bank Account</a>
      </div>
    </div>

  </div>
</div>

<script>
function toggleForm(id) {
  document.querySelectorAll('.form-box').forEach(f => {
    if (f.id === id) f.classList.toggle('open');
    else f.classList.remove('open');
  });
}
<?php if (isset($_GET['success']) || isset($_GET['error'])): ?>
// Auto-open edit form on error
<?php if (isset($_GET['error']) && in_array($_GET['error'], ['update_failed','incorrect_password','password_mismatch'])): ?>
toggleForm('<?= $_GET['error'] === "password_mismatch" || $_GET['error'] === "incorrect_password" ? "pass-form" : "edit-form" ?>');
<?php endif; ?>
<?php endif; ?>
</script>
</body>
</html>
