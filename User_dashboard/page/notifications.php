<?php
if (session_status() === PHP_SESSION_NONE) { session_name('user_session'); session_start(); }
include('submit_help.php');

require_once __DIR__ . '/../config/db.php';

$user_id = $_SESSION['user_id'] ?? null;
if (!$user_id) {
    header("Location: ../auth/login.php");
    exit();
}

$default_settings = [
    'deposit_alert'      => 0,
    'withdrawal_alert'   => 0,
    'login_alert'        => 0,
    'transaction_email'  => 0,
    'marketing_email'    => 0,
    'sms_alerts'         => 0,
    'push_notifications' => 1,
    'email_sms_toggle'   => 0
];

$query = "SELECT * FROM user_notifications WHERE user_id = ?";
$stmt  = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result        = $stmt->get_result();
$user_settings = $result->fetch_assoc() ?? $default_settings;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $deposit_alert      = isset($_POST['deposit_alert'])      ? 1 : 0;
    $withdrawal_alert   = isset($_POST['withdrawal_alert'])   ? 1 : 0;
    $login_alert        = isset($_POST['login_alert'])        ? 1 : 0;
    $transaction_email  = isset($_POST['transaction_email'])  ? 1 : 0;
    $marketing_email    = isset($_POST['marketing_email'])    ? 1 : 0;
    $sms_alerts         = isset($_POST['sms_alerts'])         ? 1 : 0;
    $push_notifications = isset($_POST['push_notifications']) ? 1 : 0;
    $email_sms_toggle   = isset($_POST['email_sms_toggle'])   ? 1 : 0;

    $check_stmt = $conn->prepare("SELECT id FROM user_notifications WHERE user_id = ?");
    $check_stmt->bind_param("i", $user_id);
    $check_stmt->execute();
    $exists = $check_stmt->get_result()->num_rows > 0;

    if ($exists) {
        $update_stmt = $conn->prepare("UPDATE user_notifications SET deposit_alert=?, withdrawal_alert=?, login_alert=?, transaction_email=?, marketing_email=?, sms_alerts=?, push_notifications=?, email_sms_toggle=? WHERE user_id=?");
        $update_stmt->bind_param("iiiiiiiii", $deposit_alert, $withdrawal_alert, $login_alert, $transaction_email, $marketing_email, $sms_alerts, $push_notifications, $email_sms_toggle, $user_id);
    } else {
        $update_stmt = $conn->prepare("INSERT INTO user_notifications (user_id, deposit_alert, withdrawal_alert, login_alert, transaction_email, marketing_email, sms_alerts, push_notifications, email_sms_toggle) VALUES (?,?,?,?,?,?,?,?,?)");
        $update_stmt->bind_param("iiiiiiiii", $user_id, $deposit_alert, $withdrawal_alert, $login_alert, $transaction_email, $marketing_email, $sms_alerts, $push_notifications, $email_sms_toggle);
    }

    if ($update_stmt->execute()) {
        $success_message = "Notification settings saved successfully!";
        $stmt->execute();
        $user_settings = $stmt->get_result()->fetch_assoc() ?? $default_settings;
    } else {
        $error_message = "Failed to update settings. Please try again.";
    }
}

// Count active notifications
$active_count = array_sum(array_values($user_settings));
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Notification Settings | Goldpay</title>
  <link rel="icon" type="image/x-icon" href="../../favicon.ico">
  <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
  <link rel="stylesheet" href="notifications.css">
</head>
<body>

<?php include('../sidebar.php'); ?>
<?php include('../mobile_header.php'); ?>

<div class="notif-wrap">

  <!-- Page Header -->
  <div class="notif-page-header">
    <div class="header-icon">
      <span class="material-icons">notifications</span>
    </div>
    <div>
      <h1>Notification Settings</h1>
      <p>Manage how and when you receive alerts</p>
    </div>
  </div>

  <!-- Alert Messages -->
  <?php if (isset($success_message)): ?>
    <div class="notif-alert success">
      <span class="material-icons">check_circle</span>
      <?= htmlspecialchars($success_message) ?>
    </div>
  <?php endif; ?>

  <?php if (isset($error_message)): ?>
    <div class="notif-alert error">
      <span class="material-icons">error</span>
      <?= htmlspecialchars($error_message) ?>
    </div>
  <?php endif; ?>

  <!-- Stats Pills -->
  <div class="notif-stats">
    <div class="stat-pill">
      <div class="sp-icon purple"><span class="material-icons">notifications_active</span></div>
      <div class="sp-info">
        <div class="sp-val"><?= $active_count ?>/8</div>
        <div class="sp-lbl">Active Alerts</div>
      </div>
    </div>
    <div class="stat-pill">
      <div class="sp-icon blue"><span class="material-icons">email</span></div>
      <div class="sp-info">
        <div class="sp-val"><?= ($user_settings['transaction_email'] + $user_settings['marketing_email']) ?>/2</div>
        <div class="sp-lbl">Email Alerts</div>
      </div>
    </div>
    <div class="stat-pill">
      <div class="sp-icon green"><span class="material-icons">smartphone</span></div>
      <div class="sp-info">
        <div class="sp-val"><?= ($user_settings['push_notifications'] + $user_settings['deposit_alert'] + $user_settings['withdrawal_alert'] + $user_settings['login_alert']) ?>/4</div>
        <div class="sp-lbl">Push Alerts</div>
      </div>
    </div>
    <div class="stat-pill">
      <div class="sp-icon orange"><span class="material-icons">sms</span></div>
      <div class="sp-info">
        <div class="sp-val"><?= ($user_settings['sms_alerts'] + $user_settings['email_sms_toggle']) ?>/2</div>
        <div class="sp-lbl">SMS Alerts</div>
      </div>
    </div>
  </div>

  <!-- Form -->
  <form method="POST">

    <!-- Cards Grid -->
    <div class="notif-grid">

      <!-- In-App / Push -->
      <div class="notif-card">
        <div class="notif-card-head">
          <div class="card-icon purple"><span class="material-icons">phone_android</span></div>
          <div>
            <h3>In-App Notifications</h3>
            <span class="sub">Push alerts on your device</span>
          </div>
        </div>

        <div class="toggle-row">
          <div class="toggle-info">
            <span class="toggle-label">Push Notifications</span>
            <span class="toggle-desc">Enable all push notifications globally</span>
          </div>
          <label class="n-switch">
            <input type="checkbox" name="push_notifications" <?= $user_settings['push_notifications'] ? 'checked' : '' ?>>
            <span class="n-slider"></span>
          </label>
        </div>

        <div class="toggle-row">
          <div class="toggle-info">
            <span class="toggle-label">Deposit Alerts</span>
            <span class="toggle-desc">Notify when deposit is confirmed</span>
          </div>
          <label class="n-switch">
            <input type="checkbox" name="deposit_alert" <?= $user_settings['deposit_alert'] ? 'checked' : '' ?>>
            <span class="n-slider"></span>
          </label>
        </div>

        <div class="toggle-row">
          <div class="toggle-info">
            <span class="toggle-label">Withdrawal Alerts</span>
            <span class="toggle-desc">Notify when withdrawal is processed</span>
          </div>
          <label class="n-switch">
            <input type="checkbox" name="withdrawal_alert" <?= $user_settings['withdrawal_alert'] ? 'checked' : '' ?>>
            <span class="n-slider"></span>
          </label>
        </div>

        <div class="toggle-row">
          <div class="toggle-info">
            <span class="toggle-label">Login Alerts</span>
            <span class="toggle-desc">Alert on unrecognized device or IP</span>
          </div>
          <label class="n-switch">
            <input type="checkbox" name="login_alert" <?= $user_settings['login_alert'] ? 'checked' : '' ?>>
            <span class="n-slider"></span>
          </label>
        </div>
      </div>

      <!-- Email -->
      <div class="notif-card">
        <div class="notif-card-head">
          <div class="card-icon blue"><span class="material-icons">mark_email_read</span></div>
          <div>
            <h3>Email Notifications</h3>
            <span class="sub">Alerts sent to your email</span>
          </div>
        </div>

        <div class="toggle-row">
          <div class="toggle-info">
            <span class="toggle-label">Transaction Emails</span>
            <span class="toggle-desc">Email for every buy/sell/transfer</span>
          </div>
          <label class="n-switch">
            <input type="checkbox" name="transaction_email" <?= $user_settings['transaction_email'] ? 'checked' : '' ?>>
            <span class="n-slider"></span>
          </label>
        </div>

        <div class="toggle-row">
          <div class="toggle-info">
            <span class="toggle-label">Marketing Emails</span>
            <span class="toggle-desc">Promotions, offers and platform updates</span>
          </div>
          <label class="n-switch">
            <input type="checkbox" name="marketing_email" <?= $user_settings['marketing_email'] ? 'checked' : '' ?>>
            <span class="n-slider"></span>
          </label>
        </div>
      </div>

      <!-- SMS -->
      <div class="notif-card">
        <div class="notif-card-head">
          <div class="card-icon green"><span class="material-icons">chat_bubble</span></div>
          <div>
            <h3>SMS Notifications</h3>
            <span class="sub">Text alerts on your mobile</span>
          </div>
        </div>

        <div class="toggle-row">
          <div class="toggle-info">
            <span class="toggle-label">SMS Alerts</span>
            <span class="toggle-desc">Receive important alerts via SMS</span>
          </div>
          <label class="n-switch">
            <input type="checkbox" name="sms_alerts" <?= $user_settings['sms_alerts'] ? 'checked' : '' ?>>
            <span class="n-slider"></span>
          </label>
        </div>

        <div class="toggle-row">
          <div class="toggle-info">
            <span class="toggle-label">Email & SMS Master Toggle</span>
            <span class="toggle-desc">Enable or disable all email & SMS at once</span>
          </div>
          <label class="n-switch">
            <input type="checkbox" name="email_sms_toggle" <?= $user_settings['email_sms_toggle'] ? 'checked' : '' ?>>
            <span class="n-slider"></span>
          </label>
        </div>
      </div>

      <!-- Security Alerts (info card) -->
      <div class="notif-card">
        <div class="notif-card-head">
          <div class="card-icon orange"><span class="material-icons">security</span></div>
          <div>
            <h3>Security Alerts</h3>
            <span class="sub">Always on — cannot be disabled</span>
          </div>
        </div>

        <div class="toggle-row">
          <div class="toggle-info">
            <span class="toggle-label">Password Change Alert</span>
            <span class="toggle-desc">Notified whenever your password changes</span>
          </div>
          <label class="n-switch">
            <input type="checkbox" checked disabled>
            <span class="n-slider"></span>
          </label>
        </div>

        <div class="toggle-row">
          <div class="toggle-info">
            <span class="toggle-label">KYC Status Update</span>
            <span class="toggle-desc">Notified on KYC approval or rejection</span>
          </div>
          <label class="n-switch">
            <input type="checkbox" checked disabled>
            <span class="n-slider"></span>
          </label>
        </div>

        <div class="toggle-row">
          <div class="toggle-info">
            <span class="toggle-label">Suspicious Activity</span>
            <span class="toggle-desc">Immediate alert on suspicious account activity</span>
          </div>
          <label class="n-switch">
            <input type="checkbox" checked disabled>
            <span class="n-slider"></span>
          </label>
        </div>
      </div>

    </div><!-- /notif-grid -->

    <!-- Save Button -->
    <div class="notif-save-row">
      <button type="submit" class="btn-save">
        <span class="material-icons">save</span>
        Save Settings
      </button>
    </div>

  </form>
</div><!-- /notif-wrap -->

</body>
</html>
