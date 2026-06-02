<?php
if (session_status() === PHP_SESSION_NONE) { session_name('admin_session'); session_start(); }
require '../includes/db.php';
include '../templates/sidebar.php';
include '../templates/header.php';

$msg = '';

// Handle sell price update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['sell_price_1'])) {
    $sp1 = floatval($_POST['sell_price_1']);
    $sp2 = floatval($_POST['sell_price_2']);
    $label1 = $conn->real_escape_string(trim($_POST['sell_label_1'] ?? 'Price 1'));
    $label2 = $conn->real_escape_string(trim($_POST['sell_label_2'] ?? 'Price 2'));
    if ($sp1 > 0 && $sp2 > 0) {
        foreach ([
            ['usdt_sell_rate_1', $sp1], ['usdt_sell_rate_2', $sp2],
            ['usdt_sell_label_1', $label1], ['usdt_sell_label_2', $label2]
        ] as [$key, $val]) {
            $s = $conn->prepare("INSERT INTO settings (setting_group, setting_key, setting_value) VALUES ('rates', ?, ?) ON DUPLICATE KEY UPDATE setting_value = ?");
            $s->bind_param("sss", $key, $val, $val);
            $s->execute(); $s->close();
        }
        $msg = 'sell_success';
    } else {
        $msg = 'sell_error';
    }
}

// Handle wallet address update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['wallet_network'])) {
    $wnet = ($_POST['wallet_network'] ?? '') === 'TRC20' ? 'TRC20' : '';
    if ($wnet) {
        $waddr = trim($_POST['wallet_address'] ?? '');
        $wkey = 'usdt_wallet_' . strtolower($wnet);
        $sw = $conn->prepare("INSERT INTO settings (setting_group, setting_key, setting_value) VALUES ('qr', ?, ?) ON DUPLICATE KEY UPDATE setting_value = ?");
        $sw->bind_param("sss", $wkey, $waddr, $waddr);
        $sw->execute(); $sw->close();
        $msg = 'wallet_success';
    }
}

// Handle QR upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['qr_image']) && $_FILES['qr_image']['error'] === UPLOAD_ERR_OK) {
    $network = ($_POST['qr_network'] ?? '') === 'TRC20' ? 'TRC20' : '';
    if ($network) {
        $ext = strtolower(pathinfo($_FILES['qr_image']['name'], PATHINFO_EXTENSION));
        if (in_array($ext, ['jpg','jpeg','png','gif','webp'])) {
            $filename = 'qr_usdt_' . strtolower($network) . '.' . $ext;
            $dest = '../uploads/' . $filename;
            foreach (glob('../uploads/qr_usdt_' . strtolower($network) . '.*') as $old) { unlink($old); }
            move_uploaded_file($_FILES['qr_image']['tmp_name'], $dest);
            $key = 'usdt_qr_' . strtolower($network);
            $val = $filename;
            $stmt2 = $conn->prepare("INSERT INTO settings (setting_group, setting_key, setting_value) VALUES ('qr', ?, ?) ON DUPLICATE KEY UPDATE setting_value = ?");
            $stmt2->bind_param("sss", $key, $val, $val);
            $stmt2->execute();
            $stmt2->close();
            $msg = 'qr_success';
        } else {
            $msg = 'qr_error';
        }
    }
}

// Get sell prices
function getSetting($conn, $key, $default = '') {
    $r = $conn->query("SELECT setting_value FROM settings WHERE setting_group='rates' AND setting_key='$key'");
    return ($r && $row = $r->fetch_assoc()) ? $row['setting_value'] : $default;
}
$sellRate1  = getSetting($conn, 'usdt_sell_rate_1', '89.80');
$sellRate2  = getSetting($conn, 'usdt_sell_rate_2', '90.00');
$sellLabel1 = getSetting($conn, 'usdt_sell_label_1', 'Mixed Fund');
$sellLabel2 = getSetting($conn, 'usdt_sell_label_2', 'Premium Rate');

// Get current QRs and wallet addresses
$qrImages = []; $walletAddresses = [];
foreach (['TRC20'] as $net) {
    $k = 'usdt_qr_' . strtolower($net);
    $r = $conn->query("SELECT setting_value FROM settings WHERE setting_group='qr' AND setting_key='$k'");
    $qrImages[$net] = ($r && $qr = $r->fetch_assoc()) ? $qr['setting_value'] : null;
    $wk = 'usdt_wallet_' . strtolower($net);
    $wr = $conn->query("SELECT setting_value FROM settings WHERE setting_group='qr' AND setting_key='$wk'");
    $walletAddresses[$net] = ($wr && $wa = $wr->fetch_assoc()) ? $wa['setting_value'] : '';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>USDT Rate Management - Admin</title>
  <link rel="icon" type="image/x-icon" href="../../favicon.ico">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/icon?family=Material+Icons+Round" rel="stylesheet">
  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
    body { font-family: 'Segoe UI', sans-serif; background: #f4f6f9; color: #333; }

    .adm-page { margin-left: 250px; padding: 24px; min-height: 100vh; }

    .adm-page-title {
      font-size: 1.3rem; font-weight: 700; margin-bottom: 20px;
      color: #1a2332; display: flex; align-items: center; gap: 8px;
    }

    /* Alert */
    .adm-alert {
      padding: 11px 16px; border-radius: 8px; font-size: 13px;
      margin-bottom: 20px; display: flex; align-items: center; gap: 8px;
    }
    .adm-alert-success { background: #ecfdf5; color: #065f46; border: 1px solid #a7f3d0; }
    .adm-alert-error   { background: #fef2f2; color: #991b1b; border: 1px solid #fecaca; }

    /* Cards grid */
    .adm-cards-grid {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 20px;
    }

    .adm-table-card {
      background: #fff;
      border-radius: 14px;
      box-shadow: 0 2px 12px rgba(0,0,0,0.07);
      padding: 24px;
      border: 1px solid #e8ecf0;
    }

    .adm-card-head {
      display: flex; align-items: center; gap: 14px;
      margin-bottom: 20px; padding-bottom: 16px;
      border-bottom: 1px solid #f1f3f5;
    }
    .adm-card-icon {
      width: 46px; height: 46px; border-radius: 12px;
      display: flex; align-items: center; justify-content: center;
      font-size: 20px; flex-shrink: 0;
    }
    .icon-orange { background: #fef3e2; color: #e37400; }
    .icon-blue   { background: #e8f0fe; color: #1a73e8; }
    .adm-card-title { font-size: 15px; font-weight: 700; color: #1a2332; }
    .adm-card-desc  { font-size: 12px; color: #888; margin-top: 3px; }

    /* Price option boxes */
    .price-options-grid {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 14px;
      margin-bottom: 20px;
    }
    .price-option-box {
      border: 1.5px solid #e2e8f0;
      border-radius: 12px;
      padding: 16px;
      background: #fafbfc;
      transition: border-color 0.2s, box-shadow 0.2s;
    }
    .price-option-box:hover { border-color: #1a73e8; box-shadow: 0 2px 10px rgba(26,115,232,0.08); }
    .price-option-box .opt-number {
      font-size: 11px; font-weight: 700; color: #1a73e8;
      text-transform: uppercase; letter-spacing: 0.5px;
      margin-bottom: 10px; display: flex; align-items: center; gap: 5px;
    }
    .price-option-box .opt-number span {
      background: #e8f0fe; color: #1a73e8;
      width: 20px; height: 20px; border-radius: 50%;
      display: inline-flex; align-items: center; justify-content: center;
      font-size: 11px; font-weight: 700;
    }
    .field { display: flex; flex-direction: column; gap: 5px; margin-bottom: 10px; }
    .field:last-child { margin-bottom: 0; }
    .field label {
      font-size: 11px; font-weight: 600; color: #64748b;
      text-transform: uppercase; letter-spacing: 0.4px;
    }
    .field input {
      padding: 9px 12px; border: 1.5px solid #e2e8f0; border-radius: 8px;
      font-size: 13px; color: #1e293b; outline: none;
      transition: border 0.2s, box-shadow 0.2s; background: #fff; width: 100%;
    }
    .field input:focus { border-color: #1a73e8; box-shadow: 0 0 0 3px rgba(26,115,232,0.08); }
    .price-display {
      font-size: 22px; font-weight: 800; color: #1a2332;
      margin-top: 4px; display: flex; align-items: baseline; gap: 3px;
    }
    .price-display .currency { font-size: 14px; font-weight: 600; color: #64748b; }

    .adm-btn {
      padding: 10px 22px; border-radius: 8px; border: 1.5px solid #ddd;
      background: #fff; cursor: pointer; font-size: 13px;
      display: inline-flex; align-items: center; gap: 7px;
      transition: all 0.2s; font-weight: 600;
    }
    .adm-btn:hover { background: #f5f5f5; }
    .adm-btn.primary {
      background: #1a2332; color: #fff; border-color: #1a2332;
      box-shadow: 0 2px 8px rgba(26,35,50,0.15);
    }
    .adm-btn.primary:hover { background: #2d3f55; box-shadow: 0 4px 12px rgba(26,35,50,0.2); }
    .adm-btn.sm { padding: 7px 14px; font-size: 12px; }

    /* QR items */
    .qr-item {
      background: #f8f9fb; border-radius: 10px; padding: 16px;
      margin-bottom: 12px; border: 1.5px solid #edf0f4;
      display: flex; align-items: center; gap: 16px;
      transition: border-color 0.2s;
    }
    .qr-item:hover { border-color: #1a73e8; }
    .qr-item:last-child { margin-bottom: 0; }
    .qr-item-left { flex-shrink: 0; }
    .qr-item-right { flex: 1; min-width: 0; }
    .qr-item-head {
      font-size: 13px; font-weight: 700; color: #1a2332;
      margin-bottom: 10px; display: flex; align-items: center; gap: 6px;
    }
    .qr-network-badge {
      display: inline-flex; align-items: center; gap: 5px;
      background: #e8f0fe; color: #1a73e8;
      padding: 3px 10px; border-radius: 20px;
      font-size: 12px; font-weight: 700;
    }
    .qr-preview {
      width: 90px; height: 90px; border-radius: 8px;
      border: 1.5px solid #e2e8f0; overflow: hidden; background: #fff;
    }
    .qr-preview img { width: 100%; height: 100%; object-fit: contain; }
    .qr-placeholder {
      width: 90px; height: 90px; background: #e8f0fe;
      border-radius: 8px; display: flex; align-items: center;
      justify-content: center; color: #1a73e8;
    }
    .qr-upload-row { display: flex; gap: 8px; align-items: center; flex-wrap: wrap; margin-top: 8px; }
    .qr-upload-row input[type="file"] {
      flex: 1; min-width: 140px; padding: 7px 10px;
      border: 1.5px dashed #cbd5e0; border-radius: 7px;
      font-size: 12px; cursor: pointer; background: #fff;
      transition: border-color 0.2s;
    }
    .qr-upload-row input[type="file"]:hover { border-color: #1a73e8; }
    .wallet-addr-row { display: flex; gap: 8px; align-items: center; margin-top: 8px; }
    .wallet-input {
      flex: 1; padding: 7px 10px; border: 1.5px solid #cbd5e0;
      border-radius: 7px; font-size: 12px; font-family: monospace;
      background: #fff; outline: none; transition: border-color 0.2s;
    }
    .wallet-input:focus { border-color: #1a73e8; }

    @media (max-width: 900px) { .adm-cards-grid { grid-template-columns: 1fr; } }
    @media (max-width: 768px) {
      .adm-page { margin-left: 0; padding: 12px; }
      .price-options-grid { grid-template-columns: 1fr; }
      .qr-item { flex-direction: column; align-items: flex-start; }
    }
  </style>
</head>
<body>

<div class="adm-page">

  <div class="adm-page-title">
    <i class="fas fa-exchange-alt"></i> USDT Rate Management
  </div>

  <?php if ($msg === 'sell_success'): ?>
    <div class="adm-alert adm-alert-success"><i class="fas fa-check-circle"></i> Sell prices updated successfully.</div>
  <?php elseif ($msg === 'sell_error'): ?>
    <div class="adm-alert adm-alert-error"><i class="fas fa-exclamation-circle"></i> Both sell prices must be greater than 0.</div>
  <?php elseif ($msg === 'qr_success'): ?>
    <div class="adm-alert adm-alert-success"><i class="fas fa-check-circle"></i> QR code updated successfully.</div>
  <?php elseif ($msg === 'qr_error'): ?>
    <div class="adm-alert adm-alert-error"><i class="fas fa-exclamation-circle"></i> Invalid file. Only JPG, PNG, GIF, WEBP allowed.</div>
  <?php elseif ($msg === 'wallet_success'): ?>
    <div class="adm-alert adm-alert-success"><i class="fas fa-check-circle"></i> Wallet address updated successfully.</div>
  <?php endif; ?>

  <div class="adm-cards-grid">

    <!-- Sell Price Card -->
    <div class="adm-table-card">
      <div class="adm-card-head">
        <div class="adm-card-icon icon-orange"><i class="fas fa-tag"></i></div>
        <div>
          <div class="adm-card-title">USDT Sell Price Options</div>
          <div class="adm-card-desc">Set two selling rates for users to choose from</div>
        </div>
      </div>
      <form method="POST">
        <div class="price-options-grid">

          <!-- Option 1 -->
          <div class="price-option-box">
            <div class="opt-number"><span>1</span> Option 1</div>
            <div class="field">
              <label>Label</label>
              <input type="text" name="sell_label_1" value="<?= htmlspecialchars($sellLabel1) ?>" required placeholder="e.g., Mixed Fund">
            </div>
            <div class="field">
              <label>Price (₹ per USDT)</label>
              <input type="number" name="sell_price_1" step="0.01" min="0.01" value="<?= $sellRate1 ?>" required placeholder="89.80">
            </div>
            <div class="price-display">
              <span class="currency">₹</span>
              <span id="preview1"><?= $sellRate1 ?></span>
              <span class="currency">/ USDT</span>
            </div>
          </div>

          <!-- Option 2 -->
          <div class="price-option-box">
            <div class="opt-number"><span>2</span> Option 2</div>
            <div class="field">
              <label>Label</label>
              <input type="text" name="sell_label_2" value="<?= htmlspecialchars($sellLabel2) ?>" required placeholder="e.g., Premium Rate">
            </div>
            <div class="field">
              <label>Price (₹ per USDT)</label>
              <input type="number" name="sell_price_2" step="0.01" min="0.01" value="<?= $sellRate2 ?>" required placeholder="90.00">
            </div>
            <div class="price-display">
              <span class="currency">₹</span>
              <span id="preview2"><?= $sellRate2 ?></span>
              <span class="currency">/ USDT</span>
            </div>
          </div>

        </div>
        <button type="submit" class="adm-btn primary">
          <i class="fas fa-save"></i> Update Sell Prices
        </button>
      </form>

      <script>
        document.querySelector('[name="sell_price_1"]').addEventListener('input', function(){ document.getElementById('preview1').textContent = this.value || '0'; });
        document.querySelector('[name="sell_price_2"]').addEventListener('input', function(){ document.getElementById('preview2').textContent = this.value || '0'; });
      </script>
    </div>

    <!-- QR Code Card -->
    <div class="adm-table-card">
      <div class="adm-card-head">
        <div class="adm-card-icon icon-blue"><i class="fas fa-qrcode"></i></div>
        <div>
          <div class="adm-card-title">USDT Deposit QR Codes</div>
          <div class="adm-card-desc">Upload QR codes for each blockchain network</div>
        </div>
      </div>
      <?php foreach (['TRC20'] as $net): ?>
      <div class="qr-item">
        <div class="qr-item-left">
          <?php if ($qrImages[$net]): ?>
            <div class="qr-preview">
              <img src="../uploads/<?= htmlspecialchars($qrImages[$net]) ?>" alt="<?= $net ?> QR">
            </div>
          <?php else: ?>
            <div class="qr-placeholder"><i class="fas fa-qrcode" style="font-size:36px;opacity:0.4;"></i></div>
          <?php endif; ?>
        </div>
        <div class="qr-item-right">
          <div class="qr-item-head">
            <span class="qr-network-badge"><i class="fas fa-wallet"></i> <?= $net ?></span>
          </div>
          <form method="POST" enctype="multipart/form-data" class="qr-upload-row">
            <input type="hidden" name="qr_network" value="<?= $net ?>">
            <input type="file" name="qr_image" accept="image/*" required>
            <button type="submit" class="adm-btn primary sm">
              <i class="fas fa-upload"></i> Upload
            </button>
          </form>
          <form method="POST" class="wallet-addr-row">
            <input type="hidden" name="wallet_network" value="<?= $net ?>">
            <input type="text" name="wallet_address" value="<?= htmlspecialchars($walletAddresses[$net]) ?>" placeholder="Enter <?= $net ?> wallet address" class="wallet-input">
            <button type="submit" class="adm-btn primary sm">
              <i class="fas fa-save"></i> Save
            </button>
          </form>
        </div>
      </div>
      <?php endforeach; ?>
    </div>

  </div>
</div>
</body>
</html>
