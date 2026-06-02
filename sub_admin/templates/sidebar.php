<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Dollario Admin Sidebar</title>
  <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
  <style>
    body {
      margin: 0;
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
      background: #f0f2f5;
    }
  

    .sidebar {
      width: 250px;
      height: 100vh;
      background: #0e1a2b;
      color: white;
      position: fixed;
      padding: 20px 0;
      overflow-y: auto;
    }

    .sidebar .logo {
      text-align: center;
      font-size: 22px;
      font-weight: bold;
      padding: 10px 0;
      margin-bottom: 10px;
    }

    .menu {
      list-style: none;
      padding: 0;
      margin: 0;
    }

    .menu li.section {
      padding: 10px 20px;
      font-size: 13px;
      text-transform: uppercase;
      font-weight: bold;
      color: #aaa;
      background: #0b1624;
    }

    .menu li a {
      color: white;
      text-decoration: none;
      display: flex;
      align-items: center;
      padding: 12px 20px;
      transition: background 0.3s;
    }

    .menu li a:hover,
    .menu li a.active {
      background: #1d2e49;
    }

    .menu li a .material-icons {
      margin-right: 15px;
      font-size: 20px;
    }
  </style>
</head>
<body>

  <div class="sidebar">
    <div class="logo">
      <img src="../../User_dashboard/image/logo.png" alt="Goldpay" style="height: auto; width: 80px;">
    </div>
    <ul class="menu">
      <li class="section">Main</li>
      <li><a href="../modules/dashboard.php" class="active"><span class="material-icons">dashboard</span> Dashboard</a></li>

      <li class="section">User Management</li>
      <li><a href="../modules/all_users.php"><span class="material-icons">people</span> All Users</a></li>
      <li><a href="../modules/kyc_approvals.php"><span class="material-icons">verified_user</span> KYC Approvals</a></li>
      <li><a href="../modules/login_history.php"><span class="material-icons">history</span> Login History</a></li>

      <li class="section">Financial</li>
      <li><a href="../modules/usdt_deposits.php"><span class="material-icons">account_balance_wallet</span> USDT Deposits</a></li>
      <li><a href="../modules/inr_withdrawals.php"><span class="material-icons">money_off</span> INR Withdrawals</a></li>
      <li><a href="../modules/rate_management.php"><span class="material-icons">trending_up</span> Rate Management</a></li>
      <li><a href="../modules/transaction_reports.php"><span class="material-icons">receipt_long</span> Transaction Reports</a></li>

      <li class="section">Marketing</li>
      <li><a href="../modules/referral_system.php"><span class="material-icons">group_add</span> Referral System</a></li>
      <li><a href="../modules/campaigns.php"><span class="material-icons">campaign</span> Campaigns</a></li>
      <li><a href="../modules/notifications.php"><span class="material-icons">notifications</span> Notifications</a></li>

      
      <li class="section">Administration</li>
      <li><a href="../modules/sub_admins.php"><span class="material-icons">admin_panel_settings</span> Sub-Admins</a></li>
      <li><a href="../modules/security.php"><span class="material-icons">security</span> Security</a></li>
      <li><a href="../modules/audit_logs.php"><span class="material-icons">receipt</span> Audit Logs</a></li>
      <li><a href="../modules/settings.php"><span class="material-icons">settings</span> Settings</a></li>
    </ul>
  </div>

</body>
</html>
