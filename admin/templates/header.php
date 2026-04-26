<?php
if (session_status() === PHP_SESSION_NONE) { session_name('admin_session'); session_start(); }
if (!defined('BASE_URL')) require_once __DIR__ . '/../includes/config.php';
$_SESSION['user_name'] = $_SESSION['user_name'] ?? $_SESSION['username'] ?? 'Admin';

// Load real unread count from DB
$_notifCount = 0;
try {
    if (!isset($pdo)) require_once __DIR__ . '/../includes/config.php';
    $pdo->exec("CREATE TABLE IF NOT EXISTS `admin_notifications` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `title` varchar(255) NOT NULL,
        `message` text NOT NULL,
        `type` varchar(50) NOT NULL DEFAULT 'general',
        `is_read` tinyint(1) NOT NULL DEFAULT 0,
        `created_at` datetime NOT NULL DEFAULT current_timestamp(),
        PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $_notifCount = (int)$pdo->query("SELECT COUNT(*) FROM admin_notifications WHERE is_read = 0")->fetchColumn();
} catch (Exception $e) { $_notifCount = 0; }
?>
<script>
  (function(){
    var l = document.createElement('link');
    l.rel = 'icon'; l.type = 'image/png';
    l.href = '<?= BASE_URL ?>/admin/images/dollario-fav.png';
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
    margin-left: 250px;
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
  .adm-search-results {
    display: none;
    position: absolute;
    top: calc(100% + 6px); left: 0;
    width: 380px;
    background: #fff;
    border-radius: 10px;
    box-shadow: 0 6px 24px rgba(0,0,0,0.13);
    z-index: 1100;
    overflow: hidden;
    max-height: 360px;
    overflow-y: auto;
  }
  .adm-search-results.show { display: block; }
  .adm-sr-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 10px 14px;
    border-bottom: 1px solid #f1f3f5;
    cursor: pointer;
    transition: background 0.15s;
    text-decoration: none;
    color: inherit;
  }
  .adm-sr-item:last-child { border-bottom: none; }
  .adm-sr-item:hover { background: #f5f7fa; }
  .adm-sr-left { display: flex; flex-direction: column; gap: 2px; }
  .adm-sr-txnid { font-size: 13px; font-weight: 600; color: #1a2332; }
  .adm-sr-user  { font-size: 11px; color: #888; }
  .adm-sr-right { display: flex; flex-direction: column; align-items: flex-end; gap: 2px; }
  .adm-sr-amount { font-size: 13px; font-weight: 600; color: #1a73e8; }
  .adm-sr-badge {
    font-size: 10px; font-weight: 600;
    padding: 2px 8px; border-radius: 20px;
  }
  .adm-sr-badge.completed, .adm-sr-badge.approved { background:#e6f4ea; color:#1e8e3e; }
  .adm-sr-badge.pending   { background:#fef3e2; color:#e37400; }
  .adm-sr-badge.rejected  { background:#fce8e6; color:#c5221f; }
  .adm-sr-empty { padding: 16px; text-align: center; color: #999; font-size: 13px; }
  .adm-sr-loading { padding: 14px; text-align: center; color: #888; font-size: 13px; }

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
    position: fixed;
    top: 58px; right: 10px;
    background: #fff;
    box-shadow: 0 4px 16px rgba(0,0,0,0.12);
    border-radius: 8px;
    width: calc(100vw - 20px);
    max-width: 340px;
    max-height: 320px;
    overflow-y: auto;
    z-index: 1200;
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
    <input type="text" id="admSearchInput" placeholder="Search transactions…" autocomplete="off">
    <i class="fas fa-search"></i>
    <div class="adm-search-results" id="admSearchResults"></div>
  </div>

  <div class="adm-header-right">
    <!-- Notifications -->
    <div class="adm-notif" id="admNotifBtn">
      🔔
      <span class="adm-notif-count" id="admNotifCount" data-count="<?= $_notifCount ?>" style="<?= $_notifCount === 0 ? 'display:none' : '' ?>"><?= $_notifCount ?></span>
      <div class="adm-notif-drop" id="admNotifDrop">
        <div id="admNotifList"><div style="padding:14px;text-align:center;color:#999;font-size:13px;">Loading…</div></div>
        <div style="padding:8px 16px;border-top:1px solid #f1f1f1;text-align:right;">
          <a href="<?= BASE_URL ?>/admin/modules/notifications.php" style="font-size:12px;color:#6366f1;text-decoration:none;">View All</a>
          &nbsp;·&nbsp;
          <a href="#" id="admMarkRead" style="font-size:12px;color:#888;text-decoration:none;">Mark all read</a>
        </div>
      </div>
    </div>

    <!-- User dropdown -->
    <div class="adm-user-drop" id="admUserBtn">
      👤 <?= htmlspecialchars($_SESSION['user_name']) ?> ▼
      <div class="adm-drop-menu" id="admDropMenu">
        <a href="<?= BASE_URL ?>/admin/profile.php"><i class="fas fa-user"></i> My Profile</a>
        <a href="<?= BASE_URL ?>/admin/settings.php"><i class="fas fa-cog"></i> Settings</a>
        <a href="<?= BASE_URL ?>/admin/logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
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

    var _base = '<?= BASE_URL ?>';
    var notifPageUrls = {
      inr_deposit:    _base + '/admin/modules/inr_deposits_admin.php',
      usdt_deposit:   _base + '/admin/modules/usdt_deposits.php',
      inr_withdrawal: _base + '/admin/modules/inr_withdrawals.php',
      buy_usdt:       _base + '/admin/modules/buy_usdt_admin.php',
      sell_usdt:      _base + '/admin/modules/sell_usdt_admin.php',
      general:        _base + '/admin/modules/notifications.php'
    };

    function loadNotifications() {
      fetch(_base + '/admin/api/notifications.php')
        .then(function(r){ return r.json(); })
        .then(function(data){
          var list = document.getElementById('admNotifList');
          if (!data.length) {
            list.innerHTML = '<div style="padding:14px;text-align:center;color:#999;font-size:13px;">Koi notification nahi</div>';
            return;
          }
          var icons = { inr_deposit: '💰', usdt_deposit: '🪙', inr_withdrawal: '💸', buy_usdt: '🛒', sell_usdt: '📄', general: '🔔' };
          list.innerHTML = data.map(function(n){
            var ic = icons[n.type] || '🔔';
            var unread = n.is_read == 0 ? 'background:#fffbeb;' : '';
            var time = n.created_at.substring(0,16).replace('T',' ');
            var url = notifPageUrls[n.type] || notifPageUrls.general;
            return '<a href="'+url+'" style="display:block;padding:10px 16px;border-bottom:1px solid #f1f1f1;'+unread+'text-decoration:none;color:inherit;cursor:pointer;" '
              + 'onmouseover="this.style.background=\'#f5f7fa\'" onmouseout="this.style.background=\''+( n.is_read==0 ? '#fffbeb' : '#fff' )+'\'" >'
              + '<div style="font-size:13px;font-weight:600;color:#1a2332;">'+ic+' '+n.title+'</div>'
              + '<div style="font-size:12px;color:#555;margin-top:2px;">'+n.message+'</div>'
              + '<div style="font-size:11px;color:#aaa;margin-top:3px;">'+time+'</div>'
              + '</a>';
          }).join('');
        })
        .catch(function(){ document.getElementById('admNotifList').innerHTML = '<div style="padding:14px;text-align:center;color:#c00;font-size:13px;">Load error</div>'; });
    }

    function refreshCount() {
      fetch(_base + '/admin/api/notifications.php?action=count')
        .then(function(r){ return r.json(); })
        .then(function(d){
          var badge = document.getElementById('admNotifCount');
          var prevCount = parseInt(badge.getAttribute('data-count') || badge.textContent || '0');
          if (d.count > 0) { badge.textContent = d.count; badge.style.display = ''; }
          else { badge.style.display = 'none'; }
          // Play sound only if count increased
          if (d.count > prevCount) { playNotifSound(); }
          badge.setAttribute('data-count', d.count);
        });
    }

    var audioCtx = null;
    function unlockAudio() {
      if (!audioCtx) {
        audioCtx = new (window.AudioContext || window.webkitAudioContext)();
      }
      if (audioCtx.state === 'suspended') audioCtx.resume();
    }
    document.addEventListener('click', unlockAudio, { once: false });

    function playNotifSound() {
      try {
        unlockAudio();
        if (!audioCtx) return;
        var o = audioCtx.createOscillator();
        var g = audioCtx.createGain();
        o.connect(g); g.connect(audioCtx.destination);
        o.type = 'sine';
        o.frequency.setValueAtTime(880, audioCtx.currentTime);
        o.frequency.setValueAtTime(660, audioCtx.currentTime + 0.15);
        g.gain.setValueAtTime(0.5, audioCtx.currentTime);
        g.gain.exponentialRampToValueAtTime(0.001, audioCtx.currentTime + 0.5);
        o.start(audioCtx.currentTime);
        o.stop(audioCtx.currentTime + 0.5);
      } catch(e) {}
    }

    // Refresh count on page load + every 15s
    refreshCount();
    setInterval(refreshCount, 15000);

    notifBtn.addEventListener('click', function (e) {
      e.stopPropagation();
      var open = notifDrop.style.display === 'block';
      notifDrop.style.display = open ? 'none' : 'block';
      dropMenu.classList.remove('show');
      if (!open) loadNotifications();
    });

    var markRead = document.getElementById('admMarkRead');
    if (markRead) {
      markRead.addEventListener('click', function(e){
        e.preventDefault(); e.stopPropagation();
        fetch(_base + '/admin/api/notifications.php?action=mark_read')
          .then(function(){ refreshCount(); loadNotifications(); });
      });
    }

    userBtn.addEventListener('click', function (e) {
      e.stopPropagation();
      dropMenu.classList.toggle('show');
      notifDrop.style.display = 'none';
    });

    document.addEventListener('click', function () {
      notifDrop.style.display = 'none';
      dropMenu.classList.remove('show');
      document.getElementById('admSearchResults').classList.remove('show');
    });
  })();
</script>

<script>
(function(){
  var _base   = '<?= BASE_URL ?>';
  var input   = document.getElementById('admSearchInput');
  var results = document.getElementById('admSearchResults');
  var timer;

  input.addEventListener('input', function(){
    clearTimeout(timer);
    var q = this.value.trim();
    if (q.length < 2) { results.classList.remove('show'); return; }
    results.innerHTML = '<div class="adm-sr-loading">Searching…</div>';
    results.classList.add('show');
    timer = setTimeout(function(){
      fetch(_base + '/admin/api/search_transactions.php?q=' + encodeURIComponent(q))
        .then(function(r){ return r.json(); })
        .then(function(data){
          if (!data.length) {
            results.innerHTML = '<div class="adm-sr-empty">No transactions found</div>';
            return;
          }
          results.innerHTML = data.map(function(t){
            var sc = t.status.toLowerCase();
            return '<a class="adm-sr-item" href="' + _base + '/admin/modules/transaction_reports.php?search=' + encodeURIComponent(t.txn_id) + '">'
              + '<div class="adm-sr-left">'
              + '<span class="adm-sr-txnid">' + t.txn_id + ' &bull; ' + t.type + '</span>'
              + '<span class="adm-sr-user">' + t.username + ' &bull; ' + t.date + '</span>'
              + '</div>'
              + '<div class="adm-sr-right">'
              + '<span class="adm-sr-amount">' + t.amount + '</span>'
              + '<span class="adm-sr-badge ' + sc + '">' + t.status + '</span>'
              + '</div>'
              + '</a>';
          }).join('');
        })
        .catch(function(){ results.innerHTML = '<div class="adm-sr-empty">Error fetching results</div>'; });
    }, 300);
  });

  input.addEventListener('click', function(e){ e.stopPropagation(); });
  results.addEventListener('click', function(e){ e.stopPropagation(); });
})();
</script>
