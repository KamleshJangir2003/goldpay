<?php
if (session_status() === PHP_SESSION_NONE) { session_name('user_session'); session_start(); }
include('submit_help.php');

$user_id = $_SESSION['user_id'] ?? null;
if (!$user_id) {
    header("Location: ../auth/login.php");
    exit();
}

require_once __DIR__ . '/../config/db.php';

// Fetch referral code
$stmt = $conn->prepare("SELECT referral_code FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($referral_code);
$stmt->fetch();
$stmt->close();

// Fetch referral earnings
$result   = $conn->query("SELECT SUM(amount) as total_earnings FROM referral_bonus WHERE referred_by = $user_id");
$earnings = $result->fetch_assoc()['total_earnings'] ?? 0.00;

// Level 1
$level1 = [];
$res1   = $conn->query("SELECT * FROM users WHERE referred_by = $user_id");
if ($res1) {
    while ($row = $res1->fetch_assoc()) $level1[] = $row;
}

// Level 2 & 3
$level2 = $level3 = [];
foreach ($level1 as $l1) {
    $res2 = $conn->query("SELECT * FROM users WHERE referred_by = " . (int)$l1['id']);
    if ($res2) {
        while ($row2 = $res2->fetch_assoc()) {
            $level2[] = $row2;
            $res3 = $conn->query("SELECT * FROM users WHERE referred_by = " . (int)$row2['id']);
            if ($res3) {
                while ($row3 = $res3->fetch_assoc()) $level3[] = $row3;
            }
        }
    }
}

// Bonus history
$bonus_query = $conn->query("SELECT * FROM referral_bonus WHERE referred_by = $user_id ORDER BY created_at DESC LIMIT 20");

$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$base_url  = $protocol . '://' . $_SERVER['HTTP_HOST'];
$script_path = $_SERVER['SCRIPT_NAME']; // e.g. /User_dashboard/page/referral.php
$auth_dir    = substr($script_path, 0, strrpos($script_path, '/page/')) . '/auth';
$referral_link = $base_url . $auth_dir . '/signup.php?ref=' . urlencode($referral_code ?? '');
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Referral Program | Goldpay</title>
  <link rel="icon" type="image/x-icon" href="../../favicon.ico">
  <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
  <link rel="stylesheet" href="referral.css">
</head>
<body>

<?php include('../sidebar.php'); ?>
<?php include('../mobile_header.php'); ?>

<div class="ref-wrap">

  <!-- Page Header -->
  <div class="ref-page-header">
    <div class="hdr-icon">
      <span class="material-icons">group_add</span>
    </div>
    <div>
      <h1>Referral Program</h1>
      <p>Invite friends and earn commission on every trade they make</p>
    </div>
  </div>

  <!-- Stats Row -->
  <div class="ref-stats">
    <div class="ref-stat-card">
      <div class="sc-icon gold"><span class="material-icons">currency_rupee</span></div>
      <div class="sc-info">
        <div class="sc-val">₹<?= number_format($earnings, 2) ?></div>
        <div class="sc-lbl">Total Earnings</div>
      </div>
    </div>
    <div class="ref-stat-card">
      <div class="sc-icon purple"><span class="material-icons">person</span></div>
      <div class="sc-info">
        <div class="sc-val"><?= count($level1) ?></div>
        <div class="sc-lbl">Level 1 Referrals</div>
      </div>
    </div>
    <div class="ref-stat-card">
      <div class="sc-icon green"><span class="material-icons">people</span></div>
      <div class="sc-info">
        <div class="sc-val"><?= count($level2) ?></div>
        <div class="sc-lbl">Level 2 Referrals</div>
      </div>
    </div>
    <div class="ref-stat-card">
      <div class="sc-icon blue"><span class="material-icons">groups</span></div>
      <div class="sc-info">
        <div class="sc-val"><?= count($level3) ?></div>
        <div class="sc-lbl">Level 3 Referrals</div>
      </div>
    </div>
  </div>

  <!-- Referral Link Banner -->
  <div class="ref-link-banner">
    <div class="banner-left">
      <h2>Your Referral Link</h2>
      <p>Share this link and earn commission on every trade your friends make</p>
      <div class="ref-code-badge">
        <span class="material-icons" style="font-size:16px;">tag</span>
        <?= htmlspecialchars($referral_code ?? 'N/A') ?>
      </div>
    </div>
    <div class="banner-right">
      <div class="ref-link-input-row">
        <input type="text" id="refLinkInput" value="<?= htmlspecialchars($referral_link) ?>" readonly>
        <button class="btn-copy" onclick="copyLink()">
          <span class="material-icons">content_copy</span> Copy
        </button>
      </div>
      <div class="btn-share-row">
        <a class="btn-share whatsapp"
           href="https://wa.me/?text=Join%20Dollario%20using%20my%20referral%20link%3A%20<?= urlencode($referral_link) ?>"
           target="_blank">
          <span class="material-icons">chat</span> WhatsApp
        </a>
        <a class="btn-share telegram"
           href="https://t.me/share/url?url=<?= urlencode($referral_link) ?>&text=Join%20Dollario!"
           target="_blank">
          <span class="material-icons">send</span> Telegram
        </a>
        <button class="btn-share copy-link" onclick="copyLink()">
          <span class="material-icons">link</span> Link
        </button>
      </div>
    </div>
  </div>

  <!-- Commission Badges -->
  <div class="commission-row">
    <div class="comm-badge l1">
      <div class="cb-pct">5%</div>
      <div class="cb-info">
        <div class="cb-lbl">Level 1 Commission</div>
        <div class="cb-sub">Direct referrals you invite</div>
      </div>
    </div>
    <div class="comm-badge l2">
      <div class="cb-pct">3%</div>
      <div class="cb-info">
        <div class="cb-lbl">Level 2 Commission</div>
        <div class="cb-sub">Friends invited by your referrals</div>
      </div>
    </div>
    <div class="comm-badge l3">
      <div class="cb-pct">1%</div>
      <div class="cb-info">
        <div class="cb-lbl">Level 3 Commission</div>
        <div class="cb-sub">Third-level network earnings</div>
      </div>
    </div>
  </div>

  <!-- How It Works -->
  <div class="how-it-works">
    <div class="section-title">
      <span class="material-icons">info</span> How It Works
    </div>
    <div class="steps-row">
      <div class="step-item">
        <div class="step-num">1</div>
        <h4>Share Your Link</h4>
        <p>Copy your unique referral link and share it with friends via WhatsApp, Telegram or any platform</p>
      </div>
      <div class="step-item">
        <div class="step-num">2</div>
        <h4>They Sign Up</h4>
        <p>Your friend registers on Goldpay using your referral link and completes KYC verification</p>
      </div>
      <div class="step-item">
        <div class="step-num">3</div>
        <h4>Earn Commission</h4>
        <p>Earn up to 5% commission on every trade your referral makes — credited instantly to your wallet</p>
      </div>
    </div>
  </div>

  <!-- Downline Tabs -->
  <div class="downline-section">
    <div class="tab-header">
      <button class="tab-btn active" onclick="switchTab(this,'tab1')">
        <span class="material-icons" style="font-size:17px;">person</span>
        Level 1
        <span class="tab-count"><?= count($level1) ?></span>
      </button>
      <button class="tab-btn" onclick="switchTab(this,'tab2')">
        <span class="material-icons" style="font-size:17px;">people</span>
        Level 2
        <span class="tab-count"><?= count($level2) ?></span>
      </button>
      <button class="tab-btn" onclick="switchTab(this,'tab3')">
        <span class="material-icons" style="font-size:17px;">groups</span>
        Level 3
        <span class="tab-count"><?= count($level3) ?></span>
      </button>
    </div>

    <!-- Tab 1 -->
    <div class="tab-pane active" id="tab1">
      <?php if (!empty($level1)): ?>
        <table class="ref-table">
          <thead>
            <tr>
              <th>User</th>
              <th>Email</th>
              <th>Joined</th>
              <th>Status</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($level1 as $u): ?>
              <tr>
                <td>
                  <div class="user-cell">
                    <div class="user-avatar"><?= strtoupper(substr($u['username'] ?? $u['name'] ?? 'U', 0, 1)) ?></div>
                    <?= htmlspecialchars($u['username'] ?? $u['name'] ?? '—') ?>
                  </div>
                </td>
                <td><?= htmlspecialchars($u['email']) ?></td>
                <td><?= date('d M Y', strtotime($u['created_at'])) ?></td>
                <td><span class="status-badge <?= ($u['status'] ?? 'active') === 'active' ? 'active' : 'inactive' ?>"><?= ucfirst($u['status'] ?? 'Active') ?></span></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      <?php else: ?>
        <div class="empty-state">
          <span class="material-icons">person_add</span>
          <p>No Level 1 referrals yet. Share your link to get started!</p>
        </div>
      <?php endif; ?>
    </div>

    <!-- Tab 2 -->
    <div class="tab-pane" id="tab2">
      <?php if (!empty($level2)): ?>
        <table class="ref-table">
          <thead>
            <tr>
              <th>User</th>
              <th>Email</th>
              <th>Joined</th>
              <th>Status</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($level2 as $u): ?>
              <tr>
                <td>
                  <div class="user-cell">
                    <div class="user-avatar"><?= strtoupper(substr($u['username'] ?? $u['name'] ?? 'U', 0, 1)) ?></div>
                    <?= htmlspecialchars($u['username'] ?? $u['name'] ?? '—') ?>
                  </div>
                </td>
                <td><?= htmlspecialchars($u['email']) ?></td>
                <td><?= date('d M Y', strtotime($u['created_at'])) ?></td>
                <td><span class="status-badge <?= ($u['status'] ?? 'active') === 'active' ? 'active' : 'inactive' ?>"><?= ucfirst($u['status'] ?? 'Active') ?></span></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      <?php else: ?>
        <div class="empty-state">
          <span class="material-icons">people_outline</span>
          <p>No Level 2 referrals yet.</p>
        </div>
      <?php endif; ?>
    </div>

    <!-- Tab 3 -->
    <div class="tab-pane" id="tab3">
      <?php if (!empty($level3)): ?>
        <table class="ref-table">
          <thead>
            <tr>
              <th>User</th>
              <th>Email</th>
              <th>Joined</th>
              <th>Status</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($level3 as $u): ?>
              <tr>
                <td>
                  <div class="user-cell">
                    <div class="user-avatar"><?= strtoupper(substr($u['username'] ?? $u['name'] ?? 'U', 0, 1)) ?></div>
                    <?= htmlspecialchars($u['username'] ?? $u['name'] ?? '—') ?>
                  </div>
                </td>
                <td><?= htmlspecialchars($u['email']) ?></td>
                <td><?= date('d M Y', strtotime($u['created_at'])) ?></td>
                <td><span class="status-badge <?= ($u['status'] ?? 'active') === 'active' ? 'active' : 'inactive' ?>"><?= ucfirst($u['status'] ?? 'Active') ?></span></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      <?php else: ?>
        <div class="empty-state">
          <span class="material-icons">groups</span>
          <p>No Level 3 referrals yet.</p>
        </div>
      <?php endif; ?>
    </div>

  </div><!-- /downline-section -->

  <!-- Bonus History -->
  <div class="bonus-section" style="margin-top:24px;">
    <div class="bonus-head">
      <h3><span class="material-icons">history</span> Bonus History</h3>
    </div>
    <?php if ($bonus_query && $bonus_query->num_rows > 0): ?>
      <table class="bonus-table">
        <thead>
          <tr>
            <th>Amount</th>
            <th>Description</th>
            <th>Date</th>
          </tr>
        </thead>
        <tbody>
          <?php while ($bonus = $bonus_query->fetch_assoc()): ?>
            <tr>
              <td class="amt-green">+₹<?= number_format($bonus['amount'], 2) ?></td>
              <td><?= htmlspecialchars($bonus['description'] ?? '—') ?></td>
              <td><?= date('d M Y, h:i A', strtotime($bonus['created_at'])) ?></td>
            </tr>
          <?php endwhile; ?>
        </tbody>
      </table>
    <?php else: ?>
      <div class="empty-state">
        <span class="material-icons">receipt_long</span>
        <p>No bonus history yet. Start referring to earn!</p>
      </div>
    <?php endif; ?>
  </div>

</div><!-- /ref-wrap -->

<!-- Toast -->
<div class="ref-toast" id="refToast">
  <span class="material-icons">check_circle</span>
  Referral link copied!
</div>

<script>
function copyLink() {
  const input = document.getElementById('refLinkInput');
  input.select();
  input.setSelectionRange(0, 99999);
  navigator.clipboard.writeText(input.value).then(() => showToast()).catch(() => {
    document.execCommand('copy');
    showToast();
  });
}

function showToast() {
  const t = document.getElementById('refToast');
  t.classList.add('show');
  setTimeout(() => t.classList.remove('show'), 2800);
}

function switchTab(btn, tabId) {
  document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
  document.querySelectorAll('.tab-pane').forEach(p => p.classList.remove('active'));
  btn.classList.add('active');
  document.getElementById(tabId).classList.add('active');
}
</script>

</body>
</html>
