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

// Handle QR upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['qr_image']) && $_FILES['qr_image']['error'] === UPLOAD_ERR_OK) {
    $network = in_array($_POST['qr_network'] ?? '', ['TRC20','ERC20','BEP20']) ? $_POST['qr_network'] : '';
    if ($network) {
        $ext = strtolower(pathinfo($_FILES['qr_image']['name'], PATHINFO_EXTENSION));
        if (in_array($ext, ['jpg','jpeg','png','gif','webp'])) {
            $filename = 'qr_usdt_' . strtolower($network) . '.' . $ext;
            $dest = '../uploads/' . $filename;
            // Remove old QR files for this network
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

// Get current QRs
$qrImages = [];
foreach (['TRC20','ERC20','BEP20'] as $net) {
    $k = 'usdt_qr_' . strtolower($net);
    $r = $conn->query("SELECT setting_value FROM settings WHERE setting_group='qr' AND setting_key='$k'");
    $qrImages[$net] = ($r && $qr = $r->fetch_assoc()) ? $qr['setting_value'] : null;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>USDT Rate Management</title>
  <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
  <style>
    * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Inter', 'Segoe UI', sans-serif; }
    body { background: #f4f6f9; min-height: 100vh; }
    .content-area { margin-left: 250px; padding: 24px; }
    .page-header { background: rgba(255,255,255,0.95); backdrop-filter: blur(10px); border-radius: 16px; padding: 24px 32px; margin-bottom: 32px; box-shadow: 0 8px 32px rgba(0,0,0,0.1); }
    .page-title { display: flex; align-items: center; gap: 12px; font-size: 28px; font-weight: 700; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
    .page-title .material-icons { font-size: 32px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
    .cards-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(500px, 1fr)); gap: 24px; }
    .card { background: rgba(255,255,255,0.95); backdrop-filter: blur(10px); border-radius: 16px; box-shadow: 0 8px 32px rgba(0,0,0,0.1); padding: 32px; transition: all 0.3s ease; border: 1px solid rgba(255,255,255,0.3); }
    .card:hover { transform: translateY(-4px); box-shadow: 0 12px 48px rgba(0,0,0,0.15); }
    .card-header { display: flex; align-items: center; gap: 12px; margin-bottom: 24px; padding-bottom: 16px; border-bottom: 2px solid #f0f0f0; }
    .card-title { font-size: 20px; font-weight: 700; color: #1a202c; }
    .card-icon { width: 48px; height: 48px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 24px; }
    .icon-sell { background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); color: #fff; }
    .icon-qr { background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); color: #fff; }
    .card-desc { font-size: 14px; color: #718096; margin-bottom: 24px; line-height: 1.6; }
    .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 24px; }
    .form-group { position: relative; }
    label { display: block; margin-bottom: 8px; font-size: 13px; font-weight: 600; color: #4a5568; text-transform: uppercase; letter-spacing: 0.5px; }
    input[type="number"], input[type="text"] { width: 100%; padding: 12px 16px; border: 2px solid #e2e8f0; border-radius: 10px; font-size: 15px; transition: all 0.3s ease; background: #fff; }
    input[type="number"]:focus, input[type="text"]:focus { outline: none; border-color: #667eea; box-shadow: 0 0 0 4px rgba(102,126,234,0.1); transform: translateY(-2px); }
    .btn { display: inline-flex; align-items: center; justify-content: center; gap: 8px; padding: 12px 28px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: #fff; border: none; border-radius: 10px; font-size: 15px; font-weight: 600; cursor: pointer; transition: all 0.3s ease; box-shadow: 0 4px 15px rgba(102,126,234,0.4); }
    .btn:hover { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(102,126,234,0.6); }
    .btn:active { transform: translateY(0); }
    .alert { padding: 16px 20px; border-radius: 12px; margin-bottom: 24px; font-size: 14px; font-weight: 500; display: flex; align-items: center; gap: 10px; animation: slideIn 0.3s ease; }
    .alert.success { background: linear-gradient(135deg, #d4fc79 0%, #96e6a1 100%); color: #155724; border: none; }
    .alert.error { background: linear-gradient(135deg, #fa709a 0%, #fee140 100%); color: #721c24; border: none; }
    @keyframes slideIn { from { opacity: 0; transform: translateY(-10px); } to { opacity: 1; transform: translateY(0); } }
    .qr-item { background: linear-gradient(135deg, #fdfbfb 0%, #ebedee 100%); border-radius: 12px; padding: 20px; margin-bottom: 16px; transition: all 0.3s ease; border: 2px solid transparent; }
    .qr-item:hover { border-color: #667eea; transform: translateX(4px); }
    .qr-header { font-weight: 700; color: #2d3748; margin-bottom: 16px; font-size: 16px; display: flex; align-items: center; gap: 8px; }
    .qr-preview { width: 140px; height: 140px; border-radius: 12px; margin-bottom: 16px; box-shadow: 0 4px 12px rgba(0,0,0,0.1); }
    .qr-preview img { width: 100%; height: 100%; object-fit: contain; border-radius: 12px; }
    .qr-placeholder { width: 140px; height: 140px; background: linear-gradient(135deg, #e0e7ff 0%, #cffafe 100%); border-radius: 12px; display: flex; align-items: center; justify-content: center; color: #64748b; font-size: 13px; font-weight: 600; margin-bottom: 16px; }
    .qr-upload-form { display: flex; gap: 12px; align-items: center; flex-wrap: wrap; }
    input[type="file"] { flex: 1; min-width: 200px; padding: 10px; border: 2px dashed #cbd5e0; border-radius: 8px; font-size: 13px; cursor: pointer; transition: all 0.3s ease; }
    input[type="file"]:hover { border-color: #667eea; background: #f7fafc; }
    .btn-upload { padding: 10px 20px; font-size: 14px; }
    @media (max-width: 1200px) { .cards-grid { grid-template-columns: 1fr; } }
    @media (max-width: 768px) { .content-area { margin-left: 0; padding: 20px; } .form-row { grid-template-columns: 1fr; } .page-title { font-size: 22px; } }
  </style>
</head>
<body>
<div class="content-area">
  <div class="page-header">
    <div class="page-title">
      <span class="material-icons">currency_exchange</span>
      USDT Rate Management
    </div>
    <?php if ($msg === 'sell_success'): ?>
      <div class="alert success" style="margin-top:16px;margin-bottom:0;"><span class="material-icons">check_circle</span> Sell prices updated successfully.</div>
    <?php elseif ($msg === 'sell_error'): ?>
      <div class="alert error" style="margin-top:16px;margin-bottom:0;"><span class="material-icons">error</span> Both sell prices must be greater than 0.</div>
    <?php elseif ($msg === 'qr_success'): ?>
      <div class="alert success" style="margin-top:16px;margin-bottom:0;"><span class="material-icons">check_circle</span> QR code updated successfully.</div>
    <?php elseif ($msg === 'qr_error'): ?>
      <div class="alert error" style="margin-top:16px;margin-bottom:0;"><span class="material-icons">error</span> Invalid file. Only JPG, PNG, GIF, WEBP allowed.</div>
    <?php endif; ?>
  </div>

  <div class="cards-grid">
  <!-- Sell Price Management -->
  <div class="card">
    <div class="card-header">
      <div class="card-icon icon-sell">
        <span class="material-icons">sell</span>
      </div>
      <div class="card-title">USDT Sell Price Options</div>
    </div>
    <p class="card-desc">Configure two different selling rates for users to choose from when selling USDT.</p>
    <form method="POST">
      <div class="form-row">
        <div class="form-group">
          <label>Option 1 Label</label>
          <input type="text" name="sell_label_1" value="<?= htmlspecialchars($sellLabel1) ?>" required placeholder="e.g., Mixed Fund">
        </div>
        <div class="form-group">
          <label>Option 2 Label</label>
          <input type="text" name="sell_label_2" value="<?= htmlspecialchars($sellLabel2) ?>" required placeholder="e.g., Premium Rate">
        </div>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label>Price 1 (₹ per USDT)</label>
          <input type="number" name="sell_price_1" step="0.01" min="0.01" value="<?= $sellRate1 ?>" required placeholder="89.80">
        </div>
        <div class="form-group">
          <label>Price 2 (₹ per USDT)</label>
          <input type="number" name="sell_price_2" step="0.01" min="0.01" value="<?= $sellRate2 ?>" required placeholder="90.00">
        </div>
      </div>
      <button type="submit" class="btn">
        <span class="material-icons" style="font-size:20px;">save</span> Update Sell Prices
      </button>
    </form>
  </div>

  <!-- QR Code Management -->
  <div class="card">
    <div class="card-header">
      <div class="card-icon icon-qr">
        <span class="material-icons">qr_code_2</span>
      </div>
      <div class="card-title">USDT Deposit QR Codes</div>
    </div>
    <p class="card-desc">Upload QR codes for different blockchain networks to receive USDT deposits.</p>
    <?php foreach (['TRC20','ERC20','BEP20'] as $net): ?>
    <div class="qr-item">
      <div class="qr-header">
        <span class="material-icons" style="font-size:20px;color:#667eea;">account_balance_wallet</span>
        <?= $net ?> Network
      </div>
      <?php if ($qrImages[$net]): ?>
        <div class="qr-preview">
          <img src="../uploads/<?= htmlspecialchars($qrImages[$net]) ?>" alt="<?= $net ?> QR">
        </div>
      <?php else: ?>
        <div class="qr-placeholder">
          <span class="material-icons" style="font-size:48px;opacity:0.3;">qr_code</span>
        </div>
      <?php endif; ?>
      <form method="POST" enctype="multipart/form-data" class="qr-upload-form">
        <input type="hidden" name="qr_network" value="<?= $net ?>">
        <input type="file" name="qr_image" accept="image/*" required>
        <button type="submit" class="btn btn-upload">
          <span class="material-icons" style="font-size:18px;">upload</span> Upload
        </button>
      </form>
    </div>
    <?php endforeach; ?>
  </div>
  </div>
  </div>
</div>
</body>
</html>
