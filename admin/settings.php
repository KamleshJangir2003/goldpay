<?php
if (session_status() === PHP_SESSION_NONE) { session_name('admin_session'); session_start(); }
if (!isset($_SESSION['user_id'])) {
    header("Location: /dollario-new/admin/login.php");
    exit();
}
include "includes/db.php";

// Fetch saved settings
$user_id = $_SESSION['user_id'];
$settings = [
    'notifications' => $_SESSION['setting_notifications'] ?? 'email',
    'language'      => $_SESSION['setting_language']      ?? 'en',
    'theme'         => $_SESSION['setting_theme']         ?? 'light',
    'twofa'         => 'disabled'
];
$res = $conn->query("SELECT two_fa_enabled FROM users WHERE id = $user_id");
if ($res && $row = $res->fetch_assoc()) {
    $settings['twofa'] = $row['two_fa_enabled'] ? 'enabled' : 'disabled';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings – MBPAY Admin</title>
    <link rel="icon" type="image/x-icon" href="../favicon.ico">
    <link rel="stylesheet" href="/dollario-new/admin/style.css">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons|Material+Icons+Round" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        .settings-page { padding: 28px 24px; max-width: 860px; }

        /* Page header */
        .page-heading {
            display: flex; align-items: center; gap: 12px;
            margin-bottom: 28px;
        }
        .page-heading .icon-box {
            width: 44px; height: 44px; border-radius: 12px;
            background: #0e1a2b;
            display: flex; align-items: center; justify-content: center;
            color: #fff; font-size: 20px;
        }
        .page-heading h1 { font-size: 20px; font-weight: 700; color: #0e1a2b; margin: 0; }
        .page-heading p  { font-size: 13px; color: #64748b; margin: 2px 0 0; }

        /* Alert */
        .alert {
            display: flex; align-items: center; gap: 10px;
            padding: 12px 16px; border-radius: 10px;
            font-size: 13px; margin-bottom: 22px;
        }
        .alert-success { background: #ecfdf5; color: #065f46; border: 1px solid #a7f3d0; }

        /* Settings card */
        .s-card {
            background: #fff;
            border-radius: 14px;
            border: 1px solid #e8ecf0;
            box-shadow: 0 2px 10px rgba(0,0,0,0.06);
            margin-bottom: 20px;
            overflow: hidden;
        }
        .s-card-head {
            padding: 16px 22px;
            border-bottom: 1px solid #f1f5f9;
            display: flex; align-items: center; gap: 10px;
        }
        .s-card-head .s-icon {
            width: 34px; height: 34px; border-radius: 9px;
            display: flex; align-items: center; justify-content: center;
            font-size: 16px; flex-shrink: 0;
        }
        .s-card-head h3 { font-size: 14px; font-weight: 700; color: #0e1a2b; margin: 0; }
        .s-card-head p  { font-size: 12px; color: #94a3b8; margin: 2px 0 0; }

        /* Setting row */
        .s-row {
            display: flex; align-items: center; justify-content: space-between;
            padding: 16px 22px; gap: 16px;
            border-bottom: 1px solid #f8fafc;
        }
        .s-row:last-child { border-bottom: none; }
        .s-row-info { flex: 1; }
        .s-row-info label { font-size: 13px; font-weight: 600; color: #1e293b; display: block; margin-bottom: 2px; }
        .s-row-info span  { font-size: 12px; color: #94a3b8; }

        /* Select styled */
        .s-select {
            padding: 9px 36px 9px 13px;
            border: 1.5px solid #e2e8f0;
            border-radius: 8px;
            font-size: 13px; color: #1e293b;
            background: #f8fafc url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24'%3E%3Cpath fill='%2364748b' d='M7 10l5 5 5-5z'/%3E%3C/svg%3E") no-repeat right 10px center;
            appearance: none; -webkit-appearance: none;
            outline: none; cursor: pointer; min-width: 160px;
            transition: border 0.2s;
        }
        .s-select:focus { border-color: #36465d; background-color: #fff; }

        /* Toggle switch */
        .toggle-wrap { display: flex; align-items: center; gap: 10px; }
        .toggle { position: relative; width: 44px; height: 24px; flex-shrink: 0; }
        .toggle input { opacity: 0; width: 0; height: 0; }
        .toggle-slider {
            position: absolute; inset: 0;
            background: #e2e8f0; border-radius: 24px;
            cursor: pointer; transition: background 0.25s;
        }
        .toggle-slider::before {
            content: ''; position: absolute;
            width: 18px; height: 18px; border-radius: 50%;
            background: #fff; left: 3px; top: 3px;
            transition: transform 0.25s;
            box-shadow: 0 1px 3px rgba(0,0,0,0.2);
        }
        .toggle input:checked + .toggle-slider { background: #0e1a2b; }
        .toggle input:checked + .toggle-slider::before { transform: translateX(20px); }
        .toggle-label { font-size: 12px; color: #64748b; }

        /* Save button */
        .save-bar {
            display: flex; justify-content: flex-end;
            padding-top: 4px;
        }
        .save-btn {
            padding: 11px 30px;
            background: #0e1a2b; color: #fff;
            border: none; border-radius: 9px;
            font-size: 14px; font-weight: 600;
            cursor: pointer; display: inline-flex; align-items: center; gap: 8px;
            transition: background 0.2s;
        }
        .save-btn:hover { background: #1d2e49; }

        @media (max-width: 600px) {
            .s-row { flex-direction: column; align-items: flex-start; }
            .s-select { width: 100%; }
            .settings-page { padding: 16px; }
        }
    </style>
</head>
<body>

<?php include 'templates/sidebar.php'; ?>

<div class="main-content">
    <?php include 'templates/header.php'; ?>

    <div class="settings-page">

        <!-- Page Heading -->
        <div class="page-heading">
            <div class="icon-box"><i class="fas fa-sliders-h"></i></div>
            <div>
                <h1>Settings</h1>
                <p>Manage your account preferences and configurations</p>
            </div>
        </div>

        <!-- Success alert -->
        <?php if (isset($_GET['saved'])): ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle"></i> Settings saved successfully.
        </div>
        <?php endif; ?>

        <form method="POST" action="save_settings.php">

            <!-- Notifications & Language -->
            <div class="s-card">
                <div class="s-card-head">
                    <div class="s-icon" style="background:#eff6ff;color:#3b82f6;">
                        <i class="fas fa-bell"></i>
                    </div>
                    <div>
                        <h3>Notifications & Language</h3>
                        <p>Control how you receive alerts and your display language</p>
                    </div>
                </div>

                <div class="s-row">
                    <div class="s-row-info">
                        <label>Notification Preference</label>
                        <span>Choose how you want to receive system notifications</span>
                    </div>
                    <select name="notifications" class="s-select">
                        <option value="email"  <?= $settings['notifications']==='email' ?'selected':'' ?>>📧 Email</option>
                        <option value="sms"    <?= $settings['notifications']==='sms'   ?'selected':'' ?>>📱 SMS</option>
                        <option value="both"   <?= $settings['notifications']==='both'  ?'selected':'' ?>>📧📱 Both</option>
                    </select>
                </div>

                <div class="s-row">
                    <div class="s-row-info">
                        <label>Language</label>
                        <span>Select your preferred interface language</span>
                    </div>
                    <select name="language" class="s-select">
                        <option value="en" <?= $settings['language']==='en'?'selected':'' ?>>🇬🇧 English</option>
                        <option value="hi" <?= $settings['language']==='hi'?'selected':'' ?>>🇮🇳 Hindi</option>
                    </select>
                </div>
            </div>

            <!-- Appearance -->
            <div class="s-card">
                <div class="s-card-head">
                    <div class="s-icon" style="background:#fdf4ff;color:#a855f7;">
                        <i class="fas fa-paint-brush"></i>
                    </div>
                    <div>
                        <h3>Appearance</h3>
                        <p>Customize the look and feel of your dashboard</p>
                    </div>
                </div>

                <div class="s-row">
                    <div class="s-row-info">
                        <label>Theme</label>
                        <span>Switch between light and dark mode</span>
                    </div>
                    <select name="theme" class="s-select">
                        <option value="light" <?= $settings['theme']==='light'?'selected':'' ?>>☀️ Light</option>
                        <option value="dark"  <?= $settings['theme']==='dark' ?'selected':'' ?>>🌙 Dark</option>
                    </select>
                </div>
            </div>

            <!-- Security -->
            <div class="s-card">
                <div class="s-card-head">
                    <div class="s-icon" style="background:#fff7ed;color:#f97316;">
                        <i class="fas fa-shield-alt"></i>
                    </div>
                    <div>
                        <h3>Security</h3>
                        <p>Manage authentication and access security settings</p>
                    </div>
                </div>

                <div class="s-row">
                    <div class="s-row-info">
                        <label>Two-Factor Authentication</label>
                        <span>Add an extra layer of security to your account</span>
                    </div>
                    <div class="toggle-wrap">
                        <label class="toggle">
                            <input type="checkbox" name="2fa" value="enabled" <?= $settings['twofa']==='enabled'?'checked':'' ?>>
                            <span class="toggle-slider"></span>
                        </label>
                        <span class="toggle-label" id="tfaLabel"><?= $settings['twofa']==='enabled'?'Enabled':'Disabled' ?></span>
                    </div>
                </div>

                <div class="s-row">
                    <div class="s-row-info">
                        <label>Change Password</label>
                        <span>Update your admin account password</span>
                    </div>
                    <a href="/dollario-new/admin/profile.php" style="
                        padding: 9px 18px; border-radius: 8px;
                        border: 1.5px solid #e2e8f0; background: #f8fafc;
                        font-size: 13px; font-weight: 600; color: #1e293b;
                        text-decoration: none; display: inline-flex; align-items: center; gap: 6px;
                        transition: border 0.2s;">
                        <i class="fas fa-key"></i> Go to Profile
                    </a>
                </div>
            </div>

            <!-- Save -->
            <div class="save-bar">
                <button type="submit" class="save-btn">
                    <i class="fas fa-save"></i> Save Settings
                </button>
            </div>

        </form>
    </div>
</div>

<script>
// Live toggle label update
document.querySelector('.toggle input').addEventListener('change', function () {
    document.getElementById('tfaLabel').textContent = this.checked ? 'Enabled' : 'Disabled';
});
</script>

</body>
</html>
