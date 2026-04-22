<?php
session_name('user_session');
session_start();
require '../../config/db.php';

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

// Company USDT wallet addresses
$wallets = [
    'TRC20' => 'TYourTRC20WalletAddressHere',
    'ERC20' => '0xYourERC20WalletAddressHere',
    'BEP20' => '0xYourBEP20WalletAddressHere',
];

// Fetch QR images from settings
$qrImages = [];
foreach (['TRC20','ERC20','BEP20'] as $net) {
    $k = 'usdt_qr_' . strtolower($net);
    $r = $pdo->prepare("SELECT setting_value FROM settings WHERE setting_group='qr' AND setting_key=?");
    $r->execute([$k]);
    $qrImages[$net] = ($row = $r->fetch(PDO::FETCH_ASSOC)) ? $row['setting_value'] : null;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $amount   = floatval($_POST['amount']);
    $chain    = htmlspecialchars($_POST['chain']);
    $txHash   = htmlspecialchars(trim($_POST['tx_hash'] ?? ''));

    if ($amount < 1) {
        $message = "Minimum deposit is 1 USDT.";
        $msgType = "error";
    } elseif (empty($txHash)) {
        $message = "Please enter the Transaction Hash / TxID.";
        $msgType = "error";
    } elseif (!array_key_exists($chain, $wallets)) {
        $message = "Invalid network selected.";
        $msgType = "error";
    } else {
        // Check duplicate tx_hash
        $dupCheck = $pdo->prepare("SELECT id FROM usdt_deposits WHERE tx_hash = ?");
        $dupCheck->execute([$txHash]);
        if ($dupCheck->fetch()) {
            $message = "This transaction hash has already been submitted.";
            $msgType = "error";
        } else {
            $pdo->prepare("INSERT INTO usdt_deposits (user_id, tx_hash, wallet_address, amount, chain, status, created_at) VALUES (?, ?, ?, ?, ?, 'pending', NOW())")
                ->execute([$userId, $txHash, $wallets[$chain], $amount, $chain]);

            $pdo->prepare("INSERT INTO user_transactions (user_id, type, amount, currency, description, created_at) VALUES (?, 'deposit', ?, 'USDT', ?, NOW())")
                ->execute([$userId, $amount, "USDT Deposit via $chain — TxHash: $txHash"]);

            $message = "USDT deposit of $amount USDT submitted! Will be credited after confirmation.";
            $msgType = "success";
        }
    }
}

// Recent USDT deposits
$history = $pdo->prepare("SELECT * FROM usdt_deposits WHERE user_id = ? ORDER BY created_at DESC LIMIT 5");
$history->execute([$userId]);
$deposits = $history->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Deposit USDT - Dollario</title>
  <link rel="icon" type="image/x-icon" href="../../../favicon.ico">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link href="https://fonts.googleapis.com/icon?family=Material+Icons+Round" rel="stylesheet">
  <style>
    * { margin:0; padding:0; box-sizing:border-box; font-family:'Poppins',sans-serif; }
    body { background:#f8fafc; min-height:100vh; padding:30px 20px; display:flex; flex-direction:column; align-items:center; }
    .card { background:#fff; border-radius:16px; box-shadow:0 4px 20px rgba(0,0,0,0.08); padding:32px; width:100%; max-width:500px; margin-bottom:20px; }
    .card-header { display:flex; align-items:center; gap:12px; margin-bottom:24px; }
    .card-header .icon { background:#fef9c3; color:#ca8a04; border-radius:12px; padding:10px; display:flex; }
    .card-header h2 { font-size:1.3rem; font-weight:700; color:#1e293b; }
    .balance-box { background:#f8fafc; border-radius:12px; padding:14px 18px; margin-bottom:20px; display:flex; justify-content:space-between; align-items:center; }
    .balance-box .val { font-size:1.05rem; font-weight:700; color:#1e293b; }
    .balance-box small { font-size:0.75rem; color:#64748b; }
    .network-tabs { display:flex; gap:8px; margin-bottom:20px; }
    .net-tab { flex:1; padding:10px; border:2px solid #e2e8f0; border-radius:10px; text-align:center; cursor:pointer; font-size:0.82rem; font-weight:600; color:#64748b; transition:all 0.2s; }
    .net-tab.active { border-color:#6366f1; color:#6366f1; background:#eef2ff; }
    .wallet-box { background:#f0fdf4; border:1.5px dashed #86efac; border-radius:12px; padding:16px; margin-bottom:20px; text-align:center; }
    .wallet-box .addr { font-size:0.85rem; font-weight:700; color:#166534; word-break:break-all; margin:8px 0; }
    .copy-btn { background:#22c55e; color:#fff; border:none; border-radius:8px; padding:6px 16px; font-size:0.8rem; cursor:pointer; font-weight:600; }
    .copy-btn:hover { background:#16a34a; }
    label { font-size:0.85rem; font-weight:600; color:#374151; display:block; margin-bottom:6px; }
    input, select { width:100%; padding:11px 14px; border:1.5px solid #e2e8f0; border-radius:10px; font-size:0.95rem; outline:none; transition:border-color 0.2s; margin-bottom:16px; background:#fff; }
    input:focus, select:focus { border-color:#6366f1; }
    .btn-submit { width:100%; padding:13px; background:linear-gradient(135deg,#6366f1,#4f46e5); color:#fff; border:none; border-radius:10px; font-size:1rem; font-weight:700; cursor:pointer; }
    .btn-submit:hover { opacity:0.9; }
    .btn-back { display:block; text-align:center; margin-top:14px; color:#6366f1; text-decoration:none; font-size:0.88rem; }
    .alert { padding:12px 16px; border-radius:10px; margin-bottom:20px; font-size:0.88rem; font-weight:500; }
    .alert.success { background:#f0fdf4; color:#166534; border:1px solid #bbf7d0; }
    .alert.error { background:#fef2f2; color:#991b1b; border:1px solid #fecaca; }
    .note { font-size:0.78rem; color:#64748b; margin-top:-10px; margin-bottom:16px; }
    .rate-info { font-size:0.8rem; color:#6366f1; background:#eef2ff; padding:8px 12px; border-radius:8px; margin-bottom:16px; }
    /* History table */
    .history-table { width:100%; border-collapse:collapse; font-size:0.82rem; }
    .history-table th { background:#f1f5f9; padding:10px 12px; text-align:left; color:#64748b; font-weight:600; }
    .history-table td { padding:10px 12px; border-bottom:1px solid #f1f5f9; color:#1e293b; }
    .badge { padding:3px 10px; border-radius:20px; font-size:0.75rem; font-weight:600; }
    .badge.pending  { background:#fef9c3; color:#92400e; }
    .badge.confirmed { background:#dcfce7; color:#166534; }
    .badge.rejected { background:#fee2e2; color:#991b1b; }
  </style>
</head>
<body>

<div class="card">
  <div class="card-header">
    <div class="icon"><span class="material-icons-round">currency_bitcoin</span></div>
    <h2>Deposit USDT</h2>
  </div>

  <?php if ($message): ?>
    <div class="alert <?= $msgType ?>"><?= htmlspecialchars($message) ?></div>
  <?php endif; ?>

  <div class="balance-box">
    <div>
      <small>Current USDT Balance</small>
      <div class="val"><?= number_format($usdtBalance, 2) ?> USDT</div>
    </div>
    <span class="material-icons-round" style="color:#ca8a04;font-size:32px;">toll</span>
  </div>

  <div class="rate-info">
    📊 Current Rate: 1 USDT = ₹<?= number_format($usdtRate, 2) ?>
  </div>

  <!-- Network Tabs -->
  <div class="network-tabs">
    <div class="net-tab active" onclick="switchNet('TRC20',this)">TRC20</div>
    <div class="net-tab" onclick="switchNet('ERC20',this)">ERC20</div>
    <div class="net-tab" onclick="switchNet('BEP20',this)">BEP20</div>
  </div>

  <!-- Wallet Address Display -->
  <div class="wallet-box">
    <div style="font-size:0.78rem;color:#64748b;margin-bottom:10px;">Send USDT to this address</div>
    <img id="qrImg" src="<?= $qrImages['TRC20'] ? '../../../admin/uploads/' . htmlspecialchars($qrImages['TRC20']) : '' ?>" alt="QR Code"
      style="width:160px;height:160px;object-fit:contain;border:1px solid #86efac;border-radius:10px;margin-bottom:10px;<?= $qrImages['TRC20'] ? '' : 'display:none;' ?>">
    <div class="addr" id="walletAddr"><?= $wallets['TRC20'] ?></div>
    <button class="copy-btn" onclick="copyAddr()">📋 Copy Address</button>
    <div style="font-size:0.75rem;color:#64748b;margin-top:8px;">⚠️ Only send USDT on selected network. Wrong network = lost funds.</div>
  </div>

  <form method="POST" id="depositForm">
    <input type="hidden" name="chain" id="chainInput" value="TRC20">

    <label>Amount (USDT)</label>
    <input type="number" name="amount" min="1" step="0.01" placeholder="Minimum 1 USDT" required>

    <label>Transaction Hash / TxID</label>
    <input type="text" name="tx_hash" placeholder="Paste your transaction hash here" required>
    <p class="note">⚠️ Enter the TxID from your wallet after sending USDT.</p>

    <button type="submit" class="btn-submit">
      <span class="material-icons-round" style="vertical-align:middle;font-size:18px;">send</span>
      Submit Deposit
    </button>
  </form>
  <a href="../../page/dashboard.php" class="btn-back">← Back to Dashboard</a>
</div>

<!-- Recent Deposits -->
<?php if (!empty($deposits)): ?>
<div class="card">
  <div class="card-header">
    <div class="icon"><span class="material-icons-round">history</span></div>
    <h2 style="font-size:1rem;">Recent USDT Deposits</h2>
  </div>
  <table class="history-table">
    <thead>
      <tr><th>Amount</th><th>Chain</th><th>TxHash</th><th>Status</th><th>Date</th></tr>
    </thead>
    <tbody>
      <?php foreach ($deposits as $d): ?>
      <tr>
        <td><?= number_format($d['amount'], 2) ?> USDT</td>
        <td><?= htmlspecialchars($d['chain']) ?></td>
        <td style="max-width:100px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;" title="<?= htmlspecialchars($d['tx_hash']) ?>"><?= htmlspecialchars(substr($d['tx_hash'], 0, 12)) ?>...</td>
        <td><span class="badge <?= $d['status'] ?>"><?= ucfirst($d['status']) ?></span></td>
        <td><?= date('d M Y', strtotime($d['created_at'])) ?></td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>
<?php endif; ?>

<script>
const wallets = {
  TRC20: '<?= $wallets['TRC20'] ?>',
  ERC20: '<?= $wallets['ERC20'] ?>',
  BEP20: '<?= $wallets['BEP20'] ?>'
};

const qrImages = {
  TRC20: '<?= $qrImages['TRC20'] ? '../../../admin/uploads/' . htmlspecialchars($qrImages['TRC20']) : '' ?>',
  ERC20: '<?= $qrImages['ERC20'] ? '../../../admin/uploads/' . htmlspecialchars($qrImages['ERC20']) : '' ?>',
  BEP20: '<?= $qrImages['BEP20'] ? '../../../admin/uploads/' . htmlspecialchars($qrImages['BEP20']) : '' ?>'
};

function switchNet(net, el) {
  document.querySelectorAll('.net-tab').forEach(t => t.classList.remove('active'));
  el.classList.add('active');
  document.getElementById('walletAddr').textContent = wallets[net];
  document.getElementById('chainInput').value = net;
  const qrImg = document.getElementById('qrImg');
  if (qrImages[net]) {
    qrImg.src = qrImages[net];
    qrImg.style.display = 'block';
  } else {
    qrImg.style.display = 'none';
  }
}

function copyAddr() {
  const addr = document.getElementById('walletAddr').textContent;
  navigator.clipboard.writeText(addr).then(() => alert('Address copied!'));
}
</script>
</body>
</html>
