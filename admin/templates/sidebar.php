<?php
if (session_status() === PHP_SESSION_NONE) { session_name('admin_session'); session_start(); }
if (!defined('BASE_URL')) require_once __DIR__ . '/../includes/config.php';
?>
<link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
<style>
  * { box-sizing: border-box; }

  .adm-topbar {
    display: none;
    background: #0e1a2b;
    color: white;
    padding: 10px 16px;
    justify-content: space-between;
    align-items: center;
    position: sticky;
    top: 0;
    z-index: 1001;
  }
  .adm-topbar img { height: 32px; }
  .adm-topbar .adm-menu-btn {
    background: none; border: none;
    color: white; font-size: 26px; cursor: pointer; line-height: 1;
  }

  .adm-overlay {
    display: none;
    position: fixed;
    inset: 0;
    background: rgba(0,0,0,0.5);
    z-index: 1000;
  }
  .adm-overlay.active { display: block; }

  .adm-sidebar {
    width: 250px;
    height: 100vh;
    background: #0e1a2b;
    color: white;
    position: fixed;
    top: 0; left: 0;
    overflow-y: auto;
    transition: transform 0.3s ease;
    z-index: 1001;
    display: flex;
    flex-direction: column;
  }

  .adm-sidebar .adm-logo {
    text-align: center;
    padding: 12px 0;
    border-bottom: 1px solid #1d2e49;
  }
  .adm-sidebar .adm-logo img { width: 80px; height: auto; display: block; margin: 0 auto; }
  .adm-topbar img { height: auto; width: 100px; }

  .adm-menu { list-style: none; padding: 0; margin: 0; flex: 1; }

  .adm-menu li.section {
    padding: 10px 20px;
    font-size: 11px;
    text-transform: uppercase;
    font-weight: 700;
    color: #7a8fa6;
    background: #0b1624;
    letter-spacing: 0.5px;
  }

  .adm-menu li a {
    color: #cdd8e3;
    text-decoration: none;
    display: flex;
    align-items: center;
    padding: 11px 20px;
    transition: background 0.2s;
    font-size: 13.5px;
    gap: 12px;
  }
  .adm-menu li a:hover,
  .adm-menu li a.active {
    background: #1d2e49;
    color: #fff;
  }
  .adm-menu li a .material-icons { font-size: 19px; flex-shrink: 0; }

  @media (max-width: 768px) {
    .adm-sidebar { transform: translateX(-100%); }
    .adm-sidebar.active { transform: translateX(0); }
  }

  @media (min-width: 769px) {
    .adm-sidebar { transform: translateX(0) !important; }
  }
</style>

<!-- Overlay -->
<div class="adm-overlay" id="admOverlay"></div>

<!-- Sidebar -->
<div class="adm-sidebar" id="admSidebar">
  <div class="adm-logo">
    <img src="<?= BASE_URL ?>/User_dashboard/image/logo.png" alt="Dollario">
  </div>
  <ul class="adm-menu">
    <li class="section">Main</li>
    <li><a href="<?= BASE_URL ?>/admin/modules/dashboard.php"><span class="material-icons">dashboard</span> Dashboard</a></li>

    <li class="section">User Management</li>
    <li><a href="<?= BASE_URL ?>/admin/modules/all_users.php"><span class="material-icons">people</span> All Users</a></li>
    <li><a href="<?= BASE_URL ?>/admin/modules/kyc_approvals.php"><span class="material-icons">verified_user</span> KYC Approvals</a></li>
    <li><a href="<?= BASE_URL ?>/admin/modules/login_history.php"><span class="material-icons">history</span> Login History</a></li>

    <li class="section">Financial</li>
    <li><a href="<?= BASE_URL ?>/admin/modules/rate_management.php"><span class="material-icons">currency_exchange</span> USDT Rate</a></li>
    <li><a href="<?= BASE_URL ?>/admin/modules/usdt_deposits.php"><span class="material-icons">account_balance_wallet</span> USDT Deposits</a></li>
    <li><a href="<?= BASE_URL ?>/admin/modules/inr_deposits_admin.php"><span class="material-icons">add_card</span> INR Deposits</a></li>
    <li><a href="<?= BASE_URL ?>/admin/modules/inr_withdrawals.php"><span class="material-icons">money_off</span> INR Withdrawals</a></li>
    <li><a href="<?= BASE_URL ?>/admin/modules/buy_usdt_admin.php"><span class="material-icons">shopping_cart</span> Buy USDT</a></li>
    <li><a href="<?= BASE_URL ?>/admin/modules/sell_usdt_admin.php"><span class="material-icons">sell</span> Sell USDT</a></li>
    <li><a href="<?= BASE_URL ?>/admin/modules/transaction_reports.php"><span class="material-icons">receipt_long</span> Transaction Reports</a></li>

    <li class="section">Marketing</li>
    <li><a href="<?= BASE_URL ?>/admin/modules/referral_system.php"><span class="material-icons">group_add</span> Referral System</a></li>
    <!-- <li><a href="<?= BASE_URL ?>/admin/modules/campaigns.php"><span class="material-icons">campaign</span> Campaigns</a></li> -->
    <li><a href="<?= BASE_URL ?>/admin/modules/notifications.php"><span class="material-icons">notifications</span> Notifications</a></li>

    <li class="section">Administration</li>
    <!-- <li><a href="<?= BASE_URL ?>/admin/modules/sub_admins.php"><span class="material-icons">admin_panel_settings</span> Sub-Admins</a></li> -->
    <li><a href="<?= BASE_URL ?>/admin/modules/security.php"><span class="material-icons">security</span> Security</a></li>
    <!-- <li><a href="<?= BASE_URL ?>/admin/modules/audit_logs.php"><span class="material-icons">receipt</span> Audit Logs</a></li> -->
    <li><a href="<?= BASE_URL ?>/admin/modules/settings.php"><span class="material-icons">settings</span> Settings</a></li>
    <li><a href="<?= BASE_URL ?>/admin/modules/admin_help_requests.php"><span class="material-icons">help</span> Help</a></li>
    <li><a href="<?= BASE_URL ?>/admin/profile.php"><span class="material-icons">account_circle</span> My Profile</a></li>
  </ul>
</div>

<script>
  (function () {
    var sidebar = document.getElementById('admSidebar');
    var overlay = document.getElementById('admOverlay');
    function toggle() {
      sidebar.classList.toggle('active');
      overlay.classList.toggle('active');
    }
    if (overlay) overlay.addEventListener('click', toggle);
  })();
</script>
