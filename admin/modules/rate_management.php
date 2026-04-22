<?php
if (session_status() === PHP_SESSION_NONE) { session_name('admin_session'); session_start(); }
require '../includes/db.php';
include '../templates/sidebar.php';

$msg = '';

// Handle rate update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['usdt_rate'])) {
    $rate = floatval($_POST['usdt_rate']);
    if ($rate > 0) {
        $stmt = $conn->prepare("INSERT INTO settings (setting_group, setting_key, setting_value) VALUES ('rates', 'usdt_inr_rate', ?) ON DUPLICATE KEY UPDATE setting_value = ?");
        $stmt->bind_param("ss", $rate, $rate);
        $stmt->execute();
        $stmt->close();
        $msg = 'success';
    } else {
        $msg = 'error';
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

// Get current rate
$result = $conn->query("SELECT setting_value FROM settings WHERE setting_group='rates' AND setting_key='usdt_inr_rate'");
$currentRate = ($result && $row = $result->fetch_assoc()) ? floatval($row['setting_value']) : 89.80;

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
    * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Roboto', sans-serif; }
    body { background: #f5f7fa; }
    .content-area { margin-left: 260px; padding: 30px; }
    .page-title { display: flex; align-items: center; gap: 10px; margin-bottom: 24px; font-size: 20px; font-weight: 600; color: #2d3748; }
    .page-title .material-icons { color: #4e73df; }
    .card { background: #fff; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); padding: 28px; max-width: 480px; }
    .current-rate { background: #eef2ff; border-radius: 8px; padding: 16px 20px; margin-bottom: 24px; display: flex; align-items: center; gap: 12px; }
    .current-rate .label { font-size: 13px; color: #6b7280; }
    .current-rate .value { font-size: 1.6rem; font-weight: 700; color: #4e73df; }
    label { display: block; margin-bottom: 6px; font-size: 14px; font-weight: 500; color: #4b5563; }
    input[type="number"] { width: 100%; padding: 10px 14px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 15px; margin-bottom: 18px; }
    input[type="number"]:focus { outline: none; border-color: #4e73df; box-shadow: 0 0 0 3px rgba(78,115,223,0.1); }
    .btn { display: inline-flex; align-items: center; gap: 6px; padding: 10px 20px; background: #4e73df; color: #fff; border: none; border-radius: 6px; font-size: 14px; font-weight: 500; cursor: pointer; }
    .btn:hover { background: #3b5ab7; }
    .alert { padding: 12px 16px; border-radius: 6px; margin-bottom: 18px; font-size: 14px; }
    .alert.success { background: #f0fdf4; color: #166534; border: 1px solid #bbf7d0; }
    .alert.error { background: #fef2f2; color: #991b1b; border: 1px solid #fecaca; }
    @media (max-width: 768px) { .content-area { margin-left: 0; padding: 16px; } }
  </style>
</head>
<body>
<div class="content-area">
  <div class="page-title">
    <span class="material-icons">currency_exchange</span>
    USDT Rate Management
  </div>

  <?php if ($msg === 'success'): ?>
    <div class="alert success">✅ USDT rate successfully updated to ₹<?php echo number_format($currentRate, 2); ?></div>
  <?php elseif ($msg === 'qr_success'): ?>
    <div class="alert success">✅ QR code updated successfully.</div>
  <?php elseif ($msg === 'qr_error'): ?>
    <div class="alert error">❌ Invalid file. Only JPG, PNG, GIF, WEBP allowed.</div>
  <?php elseif ($msg === 'error'): ?>
    <div class="alert error">❌ Invalid rate. Please enter a value greater than 0.</div>
  <?php endif; ?>

  <div class="card">
    <div class="current-rate">
      <span class="material-icons" style="color:#4e73df;font-size:32px">monetization_on</span>
      <div>
        <div class="label">Current USDT/INR Rate</div>
        <div class="value">₹<?php echo number_format($currentRate, 2); ?></div>
      </div>
    </div>

    <form method="POST">
      <label for="usdt_rate">New USDT Rate (INR per 1 USDT)</label>
      <input type="number" id="usdt_rate" name="usdt_rate" step="0.01" min="0.01"
             value="<?php echo $currentRate; ?>" required>
      <button type="submit" class="btn">
        <span class="material-icons" style="font-size:18px">save</span>
        Update Rate
      </button>
    </form>
  </div>

  <!-- QR Code Management -->
  <div class="card" style="margin-top:24px;max-width:560px;">
    <div style="font-size:16px;font-weight:600;color:#2d3748;margin-bottom:20px;display:flex;align-items:center;gap:8px;">
      <span class="material-icons" style="color:#4e73df;">qr_code_2</span> USDT Deposit QR Codes
    </div>
    <?php foreach (['TRC20','ERC20','BEP20'] as $net): ?>
    <div style="border:1px solid #e5e7eb;border-radius:8px;padding:16px;margin-bottom:16px;">
      <div style="font-weight:600;color:#374151;margin-bottom:10px;"><?= $net ?> QR Code</div>
      <?php if ($qrImages[$net]): ?>
        <img src="../uploads/<?= htmlspecialchars($qrImages[$net]) ?>" alt="<?= $net ?> QR" style="width:120px;height:120px;object-fit:contain;border:1px solid #e5e7eb;border-radius:6px;margin-bottom:10px;display:block;">
      <?php else: ?>
        <div style="width:120px;height:120px;background:#f3f4f6;border-radius:6px;display:flex;align-items:center;justify-content:center;color:#9ca3af;font-size:12px;margin-bottom:10px;">No QR</div>
      <?php endif; ?>
      <form method="POST" enctype="multipart/form-data" style="display:flex;gap:8px;align-items:center;">
        <input type="hidden" name="qr_network" value="<?= $net ?>">
        <input type="file" name="qr_image" accept="image/*" required style="font-size:13px;">
        <button type="submit" class="btn" style="padding:7px 14px;font-size:13px;">
          <span class="material-icons" style="font-size:16px;">upload</span> Upload
        </button>
      </form>
    </div>
    <?php endforeach; ?>
  </div>
</div>
</body>
</html>
