<link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
<link rel="stylesheet" href="../responsive.css">
<style>
  .sidebar {
    width: 250px;
    height: 100vh;
    background: #0e1a2b;
    color: white;
    position: fixed;
    top: 0;
    left: 0;
    padding: 0;
    overflow-y: auto;
    display: flex;
    flex-direction: column;
    transition: transform 0.3s ease;
    z-index: 1000;
  }

  .sidebar .logo {
    text-align: center;
    padding: 12px 0;
    margin-bottom: 0;
    border-bottom: 1px solid #1d2e49;
  }

  .sidebar .menu { list-style: none; padding: 0; margin: 0; flex: 1; }

  .sidebar .menu li.section {
    padding: 10px 20px;
    font-size: 12px;
    text-transform: uppercase;
    font-weight: bold;
    color: #aaa;
    background: #0b1624;
  }

  .sidebar .menu li a {
    color: white;
    text-decoration: none;
    display: flex;
    align-items: center;
    padding: 12px 20px;
    transition: background 0.3s;
    font-size: 14px;
  }

  .sidebar .menu li a:hover,
  .sidebar .menu li a.active { background: #1d2e49; }

  .sidebar .menu li a .material-icons { margin-right: 12px; font-size: 20px; }

  .sidebar-logout { border-top: 1px solid #1d2e49; }

  .sidebar-logout a {
    color: #ff6b6b;
    text-decoration: none;
    display: flex;
    align-items: center;
    padding: 16px 20px;
    transition: background 0.3s;
  }

  .sidebar-logout a:hover { background: #1d2e49; }
  .sidebar-logout a .material-icons { margin-right: 12px; font-size: 20px; }

  .sidebar-overlay {
    display: none;
    position: fixed;
    inset: 0;
    background: rgba(0,0,0,0.5);
    z-index: 999;
  }

  .sidebar-overlay.active { display: block; }

  @media (max-width: 768px) {
    .sidebar { transform: translateX(-100%); }
    .sidebar.active { transform: translateX(0); }
  }
</style>

<!-- Overlay -->
<div class="sidebar-overlay" id="sidebarOverlay"></div>

<div class="sidebar" id="sidebar">
  <div class="logo">
    <img src="../image/logo.png" alt="Dollario" style="height: auto; width: 80px; display: block; margin: 0 auto;">
  </div>
  <ul class="menu">
    <li class="section">Main</li>
    <li><a href="dashboard.php" class="active"><span class="material-icons">dashboard</span> Dashboard</a></li>
    <li><a href="transactions.php"><span class="material-icons">receipt_long</span> Transactions</a></li>
    <li><a href="kyc.php"><span class="material-icons">verified_user</span> KYC</a></li>
    <li><a href="trading.php"><span class="material-icons">trending_up</span> Trading</a></li>
    <li><a href="profile.php"><span class="material-icons">person</span> Profile</a></li>
    <li><a href="referral.php"><span class="material-icons">group_add</span> Referral</a></li>
    <li><a href="notifications.php"><span class="material-icons">notifications</span> Notifications</a></li>
    <li><a href="security.php"><span class="material-icons">lock</span> Security</a></li>
    <li><a href="my_help_requests.php"><span class="material-icons">support_agent</span> Help & Support</a></li>
  </ul>
  <div class="sidebar-logout">
    <a href="../logout.php"><span class="material-icons">logout</span> Logout</a>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
  const sidebar = document.getElementById('sidebar');
  const overlay = document.getElementById('sidebarOverlay');

  function toggleUserSidebar() {
    sidebar.classList.toggle('active');
    overlay.classList.toggle('active');
  }

  if (overlay) overlay.addEventListener('click', toggleUserSidebar);

  window.toggleUserSidebar = toggleUserSidebar;
});
</script>
