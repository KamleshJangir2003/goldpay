<?php
if (session_status() === PHP_SESSION_NONE) { session_name('admin_session'); session_start(); }
$_SESSION['user_name']      = $_SESSION['user_name'] ?? $_SESSION['username'] ?? 'Admin';
$_SESSION['notifications']  = $_SESSION['notifications'] ?? 5;
?>
<script>
  (function(){
    var l = document.createElement('link');
    l.rel = 'icon'; l.type = 'image/png';
    l.href = '/dollario-new/admin/images/dollario-fav.png';
    document.head.appendChild(l);
  })();
</script>
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
<style>
  .adm-header {
    background: #fff;
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0 20px;
    height: 58px;
    box-shadow: 0 1px 4px rgba(0,0,0,0.08);
    margin-left: 252px;
    position: sticky;
    top: 0;
    z-index: 900;
  }

  .adm-search {
    position: relative;
    width: 260px;
  }
  .adm-search input {
    width: 100%;
    padding: 8px 36px 8px 14px;
    border: none;
    border-radius: 8px;
    background: #e9ecef;
    font-size: 13px;
    outline: none;
  }
  .adm-search i {
    position: absolute;
    right: 10px; top: 50%;
    transform: translateY(-50%);
    color: #888; font-size: 13px;
  }

  .adm-header-right {
    display: flex;
    align-items: center;
    gap: 18px;
  }

  .adm-notif {
    position: relative;
    cursor: pointer;
    font-size: 20px;
    user-select: none;
  }
  .adm-notif-count {
    position: absolute;
    top: -6px; right: -8px;
    background: #b58900;
    color: #fff;
    font-size: 10px;
    padding: 1px 5px;
    border-radius: 50%;
    font-weight: 600;
  }
  .adm-notif-drop {
    display: none;
    position: absolute;
    top: 36px; right: 0;
    background: #fff;
    box-shadow: 0 4px 16px rgba(0,0,0,0.12);
    border-radius: 8px;
    min-width: 240px;
    max-height: 280px;
    overflow-y: auto;
    z-index: 999;
  }
  .adm-notif-drop p {
    margin: 0;
    padding: 11px 16px;
    border-bottom: 1px solid #f1f1f1;
    font-size: 13px;
    color: #333;
  }
  .adm-notif-drop p:last-child { border-bottom: none; }

  .adm-user-drop { position: relative; cursor: pointer; font-size: 14px; user-select: none; }
  .adm-drop-menu {
    display: none;
    position: absolute;
    top: 40px; right: 0;
    background: #fff;
    box-shadow: 0 4px 16px rgba(0,0,0,0.12);
    border-radius: 8px;
    min-width: 160px;
    overflow: hidden;
    z-index: 999;
  }
  .adm-drop-menu.show { display: block; }
  .adm-drop-menu a {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 10px 16px;
    text-decoration: none;
    color: #333;
    font-size: 13px;
    transition: background 0.2s;
  }
  .adm-drop-menu a:hover { background: #f5f5f5; }

  .adm-hamburger {
    display: none;
    background: none;
    border: none;
    font-size: 24px;
    cursor: pointer;
    color: #333;
    padding: 4px 6px;
    line-height: 1;
  }

  @media (max-width: 768px) {
    .adm-header {
      margin-left: 0;
      padding: 0 12px;
      gap: 10px;
    }
    .adm-search { display: none; }
    .adm-hamburger { display: block; }
  }
</style>

<div class="adm-header">
  <button class="adm-hamburger" id="admHdrMenuBtn">☰</button>
  <div class="adm-search">
    <input type="text" placeholder="Search users, transactions…">
    <i class="fas fa-search"></i>
  </div>

  <div class="adm-header-right">
    <!-- Notifications -->
    <div class="adm-notif" id="admNotifBtn">
      🔔
      <span class="adm-notif-count"><?= $_SESSION['notifications'] ?></span>
      <div class="adm-notif-drop" id="admNotifDrop">
        <p>New user registered</p>
        <p>Deposit request received</p>
        <p>Password changed successfully</p>
        <p>Admin logged in</p>
        <p>New support ticket</p>
      </div>
    </div>

    <!-- User dropdown -->
    <div class="adm-user-drop" id="admUserBtn">
      👤 <?= htmlspecialchars($_SESSION['user_name']) ?> ▼
      <div class="adm-drop-menu" id="admDropMenu">
        <a href="/dollario-new/admin/profile.php"><i class="fas fa-user"></i> My Profile</a>
        <a href="/dollario-new/admin/settings.php"><i class="fas fa-cog"></i> Settings</a>
        <a href="/dollario-new/admin/logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
      </div>
    </div>
  </div>
</div>

<script>
  (function () {
    var notifBtn  = document.getElementById('admNotifBtn');
    var notifDrop = document.getElementById('admNotifDrop');
    var userBtn   = document.getElementById('admUserBtn');
    var dropMenu  = document.getElementById('admDropMenu');
    var hdrMenuBtn = document.getElementById('admHdrMenuBtn');

    if (hdrMenuBtn) {
      hdrMenuBtn.addEventListener('click', function (e) {
        e.stopPropagation();
        var sidebar = document.getElementById('admSidebar');
        var overlay = document.getElementById('admOverlay');
        if (sidebar) sidebar.classList.toggle('active');
        if (overlay) overlay.classList.toggle('active');
      });
    }

    notifBtn.addEventListener('click', function (e) {
      e.stopPropagation();
      var open = notifDrop.style.display === 'block';
      notifDrop.style.display = open ? 'none' : 'block';
      dropMenu.classList.remove('show');
    });

    userBtn.addEventListener('click', function (e) {
      e.stopPropagation();
      dropMenu.classList.toggle('show');
      notifDrop.style.display = 'none';
    });

    document.addEventListener('click', function () {
      notifDrop.style.display = 'none';
      dropMenu.classList.remove('show');
    });
  })();
</script>
