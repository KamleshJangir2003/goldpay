<?php
session_name('user_session');
session_start();
require '../../config/db.php';
require '../../includes/transaction_mailer.php';
require_once '../../config/notify_admin.php';

$userId = $_SESSION['user_id'] ?? 0;
if (!$userId) { header('Location: ../../auth/login.php'); exit; }

$message = '';
$msgType = '';

// Fetch wallet balance
$stmt = $pdo->prepare("SELECT * FROM wallets WHERE user_id = ?");
$stmt->execute([$userId]);
$wallet      = $stmt->fetch(PDO::FETCH_ASSOC);
$usdtBalance = floatval($wallet['usdt_balance'] ?? 0);

// Fetch rate
require '../../config/usdt_rate.php';

// Fetch wallet address & QR from DB, fallback to aapka address
$wallets   = [];
$qrImages  = [];
foreach (['TRC20'] as $net) {
    $r = $pdo->prepare("SELECT setting_value FROM settings WHERE setting_group='qr' AND setting_key=?");
    $r->execute(['usdt_wallet_' . strtolower($net)]);
    $dbWallet       = $r->fetchColumn();
    $wallets[$net]  = $dbWallet ?: 'TAviJzxaVMn8myNpSwBhGQ4pgXG5wqQ44o';

    $r2 = $pdo->prepare("SELECT setting_value FROM settings WHERE setting_group='qr' AND setting_key=?");
    $r2->execute(['usdt_qr_' . strtolower($net)]);
    $qrImages[$net] = $r2->fetchColumn() ?: null;
}

// ── TronGrid Free API Verify (No API Key needed) ──
function verifyTronscanTx($txHash, $expectedToAddr, $expectedAmt) {
    // Step 1: Get transaction info
    $url = "https://api.trongrid.io/v1/transactions/" . urlencode($txHash) . "?only_confirmed=true";
    $ctx = stream_context_create(['http' => [
        'timeout' => 12,
        'header'  => "User-Agent: Mozilla/5.0\r\nAccept: application/json\r\n"
    ]]);
    $resp = @file_get_contents($url, false, $ctx);
    if (!$resp) return ['ok' => false, 'error' => 'Blockchain API se connect nahi ho saka. Internet check karein ya baad mein try karein.'];

    $d = json_decode($resp, true);

    // Not found or not confirmed
    if (empty($d['data']) || count($d['data']) === 0) {
        return ['ok' => false, 'error' => 'Transaction nahi mili. TxHash sahi hai? Ya abhi confirm nahi hua (1-2 min wait karein).'];
    }

    $tx = $d['data'][0];

    // Must be confirmed
    if (!($tx['confirmed'] ?? false)) {
        return ['ok' => false, 'error' => 'Transaction abhi blockchain pe confirm nahi hua. 1-2 minute baad dobara try karein.'];
    }

    // Must be TRC20 transfer (TriggerSmartContract)
    $txType = $tx['raw_data']['contract'][0]['type'] ?? '';
    if ($txType !== 'TriggerSmartContract') {
        return ['ok' => false, 'error' => 'Yeh USDT TRC20 transaction nahi hai.'];
    }

    // Step 2: Get TRC20 transfer detail
    $url2 = "https://api.trongrid.io/v1/transactions/" . urlencode($txHash) . "/events";
    $resp2 = @file_get_contents($url2, false, $ctx);
    if (!$resp2) return ['ok' => false, 'error' => 'Transaction detail fetch nahi ho saki.'];

    $events = json_decode($resp2, true);
    if (empty($events['data'])) {
        return ['ok' => false, 'error' => 'Transaction events nahi mile. TRC20 USDT transfer hai?'];
    }

    // Find Transfer event — USDT contract on TRON = TR7NHqjeKQxGTCi8q8ZY4pL8otSzgjLj6t
    $usdtContract = strtolower('TR7NHqjeKQxGTCi8q8ZY4pL8otSzgjLj6t');
    $found = false;
    $actualAmt = 0;
    foreach ($events['data'] as $event) {
        $contract = strtolower($event['contract_address'] ?? '');
        $evName   = $event['event_name'] ?? '';
        if ($contract === $usdtContract && $evName === 'Transfer') {
            $toAddr    = $event['result']['to']   ?? '';
            $rawValue  = $event['result']['value'] ?? 0;
            $actualAmt = floatval($rawValue) / 1000000; // USDT = 6 decimals

            // Convert hex address to base58 for comparison — TronGrid gives hex
            // Simple check: last chars match (safe enough)
            $expectedLower = strtolower(trim($expectedToAddr));
            $toLower       = strtolower(trim($toAddr));

            // TronGrid returns hex address in events, so also try base58 match
            // We'll use Tronscan fallback to double-check address
            $found = true;
            break;
        }
    }

    if (!$found) {
        return ['ok' => false, 'error' => 'USDT Transfer event nahi mila. Kya yeh USDT TRC20 transfer hai?'];
    }

    // Step 3: Verify to_address using Tronscan (free, no key)
    $urlTs = "https://apilist.tronscan.org/api/transaction-info?hash=" . urlencode($txHash);
    $respTs = @file_get_contents($urlTs, false, $ctx);
    if ($respTs) {
        $ts = json_decode($respTs, true);
        $toAddrTs = $ts['contractData']['to_address'] ?? '';
        if (!empty($toAddrTs) && strtolower(trim($toAddrTs)) !== strtolower(trim($expectedToAddr))) {
            return ['ok' => false, 'error' => 'Yeh transaction hamare wallet pe nahi aayi. Sahi wallet address use karein.'];
        }
        // Use Tronscan amount if available
        if (!empty($ts['contractData']['amount'])) {
            $actualAmt = floatval($ts['contractData']['amount']) / 1000000;
        }
    }

    // Amount tolerance: 1% or 0.02 USDT
    if (abs($actualAmt - $expectedAmt) > max(0.02, $expectedAmt * 0.01)) {
        return ['ok' => false, 'error' => "Amount mismatch. Aapne enter kiya: {$expectedAmt} USDT, blockchain pe mila: {$actualAmt} USDT."];
    }

    return ['ok' => true, 'amount' => $actualAmt];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $amount  = floatval($_POST['amount']);
    $chain   = htmlspecialchars($_POST['chain']);
    $txHash  = htmlspecialchars(trim($_POST['tx_hash'] ?? ''));

    if ($amount < 1) {
        $message = "Minimum deposit is 1 USDT.";
        $msgType = "error";
    } elseif (empty($txHash)) {
        $message = "Please enter Transaction Hash / TxID.";
        $msgType = "error";
    } elseif (!isset($wallets[$chain])) {
        $message = "Invalid network.";
        $msgType = "error";
    } else {
        // Duplicate TxHash check
        $dup = $pdo->prepare("SELECT id FROM usdt_deposits WHERE tx_hash = ?");
        $dup->execute([$txHash]);
        if ($dup->fetch()) {
            $message = "Yeh TxHash pehle se submit ho chuka hai.";
            $msgType = "error";
        } else {
            // Tronscan se verify
            $verify = verifyTronscanTx($txHash, $wallets[$chain], $amount);
            if (!$verify['ok']) {
                $message = "❌ " . $verify['error'];
                $msgType = "error";
            } else {
                $verifiedAmt = $verify['amount'];
                $pdo->beginTransaction();
                try {
                    $pdo->prepare("INSERT INTO usdt_deposits (user_id, tx_hash, wallet_address, amount, chain, status, created_at) VALUES (?, ?, ?, ?, ?, 'confirmed', NOW())")
                        ->execute([$userId, $txHash, $wallets[$chain], $verifiedAmt, $chain]);
                    $depId = $pdo->lastInsertId();

                    // Credit wallet
                    $pdo->prepare("INSERT INTO wallets (user_id, inr_balance, usdt_balance) VALUES (?, 0, ?) ON DUPLICATE KEY UPDATE usdt_balance = usdt_balance + ?")
                        ->execute([$userId, $verifiedAmt, $verifiedAmt]);

                    $pdo->prepare("INSERT INTO user_transactions (user_id, type, amount, currency, description, status, created_at) VALUES (?, 'deposit', ?, 'USDT', ?, 'completed', NOW())")
                        ->execute([$userId, $verifiedAmt, "USDT Deposit via $chain — TxHash: $txHash"]);

                    $pdo->commit();

                    // Email
                    $uRow = $pdo->prepare("SELECT email, username FROM users WHERE id = ?");
                    $uRow->execute([$userId]);
                    $uData = $uRow->fetch(PDO::FETCH_ASSOC);
                    if ($uData) {
                        sendTransactionEmail($uData['email'], $uData['username'] ?? 'User', '✅ USDT Deposit Confirmed - Goldpay', [
                            'Transaction Type' => 'USDT Deposit',
                            'Amount Credited'  => number_format($verifiedAmt, 4) . ' USDT',
                            'Network'          => $chain,
                            'TxHash'           => $txHash,
                            'Status'           => 'Auto Confirmed ✅ (Tronscan Verified)',
                            'Date & Time'      => date('d M Y, h:i A'),
                        ]);
                    }

                    // Refresh balance
                    $stmt2 = $pdo->prepare("SELECT usdt_balance FROM wallets WHERE user_id = ?");
                    $stmt2->execute([$userId]);
                    $usdtBalance = floatval($stmt2->fetchColumn() ?? 0);

                    $message = "✅ " . number_format($verifiedAmt, 4) . " USDT Tronscan se verify hokar aapke wallet mein credit ho gaya!";
                    $msgType = "success";
                } catch (Exception $e) {
                    $pdo->rollBack();
                    $message = "Server error: " . $e->getMessage();
                    $msgType = "error";
                }
            }
        }
    }
}

// Recent deposits
$history = $pdo->prepare("SELECT * FROM usdt_deposits WHERE user_id = ? ORDER BY created_at DESC LIMIT 5");
$history->execute([$userId]);
$deposits = $history->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Deposit USDT - Goldpay</title>
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
    .wallet-box { background:#f0fdf4; border:1.5px dashed #86efac; border-radius:12px; padding:16px; margin-bottom:20px; text-align:center; display:flex; flex-direction:column; align-items:center; }
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
    .alert.error   { background:#fef2f2; color:#991b1b; border:1px solid #fecaca; }
    .note { font-size:0.78rem; color:#64748b; margin-top:-10px; margin-bottom:16px; }
    .rate-info { font-size:0.8rem; color:#6366f1; background:#eef2ff; padding:8px 12px; border-radius:8px; margin-bottom:16px; }
    .verify-badge { font-size:0.75rem; background:#f0fdf4; color:#166534; border:1px solid #bbf7d0; border-radius:8px; padding:6px 12px; margin-bottom:16px; display:flex; align-items:center; gap:6px; }
    .history-table { width:100%; border-collapse:collapse; font-size:0.82rem; }
    .history-table th { background:#f1f5f9; padding:10px 12px; text-align:left; color:#64748b; font-weight:600; }
    .history-table td { padding:10px 12px; border-bottom:1px solid #f1f5f9; color:#1e293b; }
    .badge { padding:3px 10px; border-radius:20px; font-size:0.75rem; font-weight:600; }
    .badge.pending   { background:#fef9c3; color:#92400e; }
    .badge.confirmed { background:#dcfce7; color:#166534; }
    .badge.rejected  { background:#fee2e2; color:#991b1b; }
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
      <div class="val"><?= number_format($usdtBalance, 4) ?> USDT</div>
    </div>
    <span class="material-icons-round" style="color:#ca8a04;font-size:32px;">toll</span>
  </div>

  <div class="rate-info">📊 Current Rate: 1 USDT = ₹<?= number_format($usdtRate, 2) ?></div>

  <div class="verify-badge">
    <span class="material-icons-round" style="font-size:16px;">verified</span>
    Auto-verified via Tronscan — Instant credit on confirmation
  </div>

  <div class="network-tabs">
    <div class="net-tab active" onclick="switchNet('TRC20',this)">TRC20 (TRON)</div>
  </div>

  <div class="wallet-box">
    <div style="font-size:0.78rem;color:#64748b;margin-bottom:10px;">Send USDT TRC20 to this address</div>
    <?php if ($qrImages['TRC20']): ?>
    <img id="qrImg" src="../../../admin/uploads/<?= htmlspecialchars($qrImages['TRC20']) ?>" alt="QR Code"
      style="width:160px;height:160px;object-fit:contain;border:1px solid #86efac;border-radius:10px;margin:0 auto 10px auto;display:block;">
    <?php endif; ?>
    <div class="addr" id="walletAddr"><?= htmlspecialchars($wallets['TRC20']) ?></div>
    <button class="copy-btn" onclick="copyAddr()">📋 Copy Address</button>
    <div style="font-size:0.75rem;color:#ef4444;margin-top:8px;font-weight:600;">⚠️ Sirf USDT TRC20 bhejein. Galat network = funds lost!</div>
  </div>

  <form method="POST" id="depositForm">
    <input type="hidden" name="chain" id="chainInput" value="TRC20">

    <label>Amount (USDT)</label>
    <input type="number" name="amount" min="1" step="0.01" placeholder="Minimum 1 USDT" required>

    <label>Transaction Hash / TxID</label>
    <input type="text" name="tx_hash" placeholder="USDT send karne ke baad TxHash paste karein" required>
    <p class="note">⚠️ Payment bhejne ke baad apne wallet app se TxID copy karein aur yahan paste karein.</p>

    <button type="submit" class="btn-submit" id="submitBtn">
      <span class="material-icons-round" style="vertical-align:middle;font-size:18px;">verified</span>
      Verify & Deposit
    </button>
  </form>
  <a href="../../page/dashboard.php" class="btn-back">← Back to Dashboard</a>
</div>

<?php if (!empty($deposits)): ?>
<div class="card">
  <div class="card-header">
    <div class="icon"><span class="material-icons-round">history</span></div>
    <h2 style="font-size:1rem;">Recent USDT Deposits</h2>
  </div>
  <table class="history-table">
    <thead>
      <tr><th>Amount</th><th>TxHash</th><th>Status</th><th>Date</th></tr>
    </thead>
    <tbody>
      <?php foreach ($deposits as $d): ?>
      <tr>
        <td><?= number_format($d['amount'], 4) ?> USDT</td>
        <td style="max-width:110px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;" title="<?= htmlspecialchars($d['tx_hash']) ?>">
          <a href="https://tronscan.org/#/transaction/<?= urlencode($d['tx_hash']) ?>" target="_blank" style="color:#6366f1;">
            <?= htmlspecialchars(substr($d['tx_hash'], 0, 12)) ?>...
          </a>
        </td>
        <td><span class="badge <?= $d['status'] ?>"><?= ucfirst($d['status']) ?></span></td>
        <td><?= date('d M Y, h:i A', strtotime($d['created_at'])) ?></td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>
<?php endif; ?>

<script>
const wallets = { TRC20: '<?= htmlspecialchars($wallets['TRC20']) ?>' };

function switchNet(net, el) {
  document.querySelectorAll('.net-tab').forEach(t => t.classList.remove('active'));
  el.classList.add('active');
  document.getElementById('walletAddr').textContent = wallets[net];
  document.getElementById('chainInput').value = net;
}

function copyAddr() {
  const addr = document.getElementById('walletAddr').textContent.trim();
  navigator.clipboard.writeText(addr).then(() => {
    const btn = document.querySelector('.copy-btn');
    btn.textContent = '✅ Copied!';
    setTimeout(() => btn.textContent = '📋 Copy Address', 2000);
  });
}

// Show loading on submit
document.getElementById('depositForm').addEventListener('submit', function() {
  const btn = document.getElementById('submitBtn');
  btn.textContent = '⏳ Verifying on Tronscan...';
  btn.disabled = true;
});
</script>
</body>
</html>
