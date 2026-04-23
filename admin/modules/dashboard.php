<?php
if (session_status() === PHP_SESSION_NONE) { session_name('admin_session'); session_start(); }
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: ../login.php"); exit();
}
require_once '../includes/db.php';
?>
<?php
try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("DB Error: " . $e->getMessage());
}

function getTransactions($pdo, $page = 1, $perPage = 5) {
    $offset = ($page - 1) * $perPage;
    try {
        $stmt = $pdo->prepare("SELECT t.*, u.username FROM user_transactions t LEFT JOIN users u ON t.user_id = u.id ORDER BY t.created_at DESC LIMIT :offset, :perPage");
        $stmt->bindParam(':offset',  $offset,  PDO::PARAM_INT);
        $stmt->bindParam(':perPage', $perPage, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) { return []; }
}
function countTransactions($pdo) {
    try { return $pdo->query("SELECT COUNT(*) FROM user_transactions")->fetchColumn(); }
    catch (Exception $e) { return 0; }
}

$totalUsers = $pdo->query("SELECT COUNT(*) FROM admin_users")->fetchColumn();

$currentPage       = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$transactions      = getTransactions($pdo, $currentPage);
$totalTransactions = countTransactions($pdo);
$totalPages        = max(1, ceil($totalTransactions / 5));

try { $pendingKycCount = $pdo->query("SELECT COUNT(*) FROM kyc_documents WHERE status='pending'")->fetchColumn(); }
catch (Exception $e) { $pendingKycCount = 0; }

try { $activeInvestmentCount = $pdo->query("SELECT COUNT(*) FROM investments WHERE status='active'")->fetchColumn(); }
catch (Exception $e) { $activeInvestmentCount = 0; }
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin Dashboard</title>
  <link rel="icon" type="image/x-icon" href="../../favicon.ico">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/icon?family=Material+Icons+Round" rel="stylesheet">
  <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.23/jspdf.plugin.autotable.min.js"></script>
  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

    body {
      font-family: 'Segoe UI', sans-serif;
      background: #f4f6f9;
      color: #333;
    }

    /* ── Page Wrapper ── */
    .adm-page {
      margin-left: 250px;
      padding: 24px;
      min-height: 100vh;
    }

    .adm-page-title {
      font-size: 1.3rem;
      font-weight: 700;
      margin-bottom: 20px;
      color: #1a2332;
      display: flex;
      align-items: center;
      gap: 8px;
    }

    /* ── Stats Grid ── */
    .adm-stats {
      display: grid;
      grid-template-columns: repeat(4, 1fr);
      gap: 16px;
      margin-bottom: 24px;
    }

    .adm-stat {
      background: #fff;
      border-radius: 12px;
      padding: 20px;
      box-shadow: 0 2px 8px rgba(0,0,0,0.06);
      display: flex;
      align-items: center;
      gap: 16px;
    }

    .adm-stat-icon {
      width: 48px; height: 48px;
      border-radius: 12px;
      display: flex; align-items: center; justify-content: center;
      font-size: 22px;
      flex-shrink: 0;
    }
    .icon-blue   { background: #e8f0fe; color: #1a73e8; }
    .icon-green  { background: #e6f4ea; color: #1e8e3e; }
    .icon-orange { background: #fef3e2; color: #e37400; }
    .icon-purple { background: #f3e8fd; color: #8430ce; }

    .adm-stat-info .label { font-size: 12px; color: #666; margin-bottom: 4px; }
    .adm-stat-info .value { font-size: 1.6rem; font-weight: 700; color: #1a2332; line-height: 1; }
    .adm-stat-info .trend { font-size: 11px; color: #1e8e3e; margin-top: 4px; }

    /* ── Table Card ── */
    .adm-table-card {
      background: #fff;
      border-radius: 12px;
      box-shadow: 0 2px 8px rgba(0,0,0,0.06);
      padding: 20px;
      margin-bottom: 20px;
      overflow: hidden;
    }

    .adm-table-head {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 16px;
      flex-wrap: wrap;
      gap: 10px;
    }

    .adm-table-title { font-size: 1rem; font-weight: 700; color: #1a2332; }

    .adm-table-actions { display: flex; gap: 8px; flex-wrap: wrap; }

    .adm-btn {
      padding: 7px 14px;
      border-radius: 7px;
      border: 1px solid #ddd;
      background: #fff;
      cursor: pointer;
      font-size: 13px;
      display: inline-flex;
      align-items: center;
      gap: 5px;
      transition: background 0.2s;
    }
    .adm-btn:hover { background: #f5f5f5; }
    .adm-btn.primary { background: #1a73e8; color: #fff; border-color: #1a73e8; }
    .adm-btn.primary:hover { background: #1558b0; }
    .adm-btn.sm { padding: 5px 8px; font-size: 12px; }

    /* ── Table ── */
    .adm-table-wrap { overflow-x: auto; -webkit-overflow-scrolling: touch; }

    table { width: 100%; border-collapse: collapse; min-width: 500px; }
    th {
      text-align: left;
      padding: 11px 14px;
      background: #f8f9fa;
      color: #666;
      font-size: 12px;
      font-weight: 600;
      text-transform: uppercase;
      letter-spacing: 0.3px;
      white-space: nowrap;
    }
    td {
      padding: 12px 14px;
      border-bottom: 1px solid #f1f3f5;
      font-size: 13px;
      color: #333;
    }
    tr:last-child td { border-bottom: none; }
    tr:hover td { background: #fafbfc; }

    .status-badge {
      padding: 3px 10px;
      border-radius: 20px;
      font-size: 11px;
      font-weight: 600;
      white-space: nowrap;
    }
    .status-approved, .status-verified  { background: #e6f4ea; color: #1e8e3e; }
    .status-pending                     { background: #fef3e2; color: #e37400; }
    .status-rejected                    { background: #fce8e6; color: #c5221f; }
    .status-completed                   { background: #e6f4ea; color: #1e8e3e; }
    .status-processing                  { background: #fef3e2; color: #e37400; }

    /* ── Pagination ── */
    .adm-pagination {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-top: 16px;
      flex-wrap: wrap;
      gap: 10px;
    }
    .adm-pagination-info { font-size: 12px; color: #666; }
    .adm-pagination-controls { display: flex; gap: 6px; flex-wrap: wrap; }
    .adm-page-item {
      width: 32px; height: 32px;
      display: flex; align-items: center; justify-content: center;
      border-radius: 6px;
      border: 1px solid #ddd;
      cursor: pointer;
      font-size: 13px;
      transition: all 0.2s;
    }
    .adm-page-item:hover { background: #f0f0f0; }
    .adm-page-item.active { background: #1a73e8; color: #fff; border-color: #1a73e8; }
    .adm-page-item.disabled { opacity: 0.4; cursor: not-allowed; }

    /* ── Modal ── */
    .adm-modal {
      display: none;
      position: fixed;
      inset: 0;
      background: rgba(0,0,0,0.45);
      z-index: 2000;
      align-items: center;
      justify-content: center;
      padding: 16px;
    }
    .adm-modal.open { display: flex; }
    .adm-modal-box {
      background: #fff;
      border-radius: 12px;
      width: 100%;
      max-width: 520px;
      max-height: 90vh;
      overflow-y: auto;
    }
    .adm-modal-hdr {
      padding: 16px 20px;
      border-bottom: 1px solid #eee;
      display: flex;
      justify-content: space-between;
      align-items: center;
      position: sticky; top: 0;
      background: #fff;
    }
    .adm-modal-hdr h3 { font-size: 1rem; font-weight: 700; }
    .adm-modal-close { cursor: pointer; font-size: 20px; color: #666; background: none; border: none; }
    .adm-modal-body { padding: 20px; }
    .adm-modal-ftr {
      padding: 14px 20px;
      border-top: 1px solid #eee;
      display: flex;
      justify-content: flex-end;
      gap: 8px;
    }

    .adm-form-row { display: flex; gap: 14px; }
    .adm-form-group { margin-bottom: 14px; flex: 1; }
    .adm-form-group label { display: block; font-size: 12px; font-weight: 600; margin-bottom: 5px; color: #444; }
    .adm-form-group input,
    .adm-form-group select,
    .adm-form-group textarea {
      width: 100%;
      padding: 9px 12px;
      border: 1px solid #ddd;
      border-radius: 7px;
      font-size: 13px;
      outline: none;
      transition: border 0.2s;
    }
    .adm-form-group input:focus,
    .adm-form-group select:focus { border-color: #1a73e8; }

    /* ══════ MOBILE ══════ */
    @media (max-width: 768px) {
      .adm-page { margin-left: 0; padding: 12px; }

      .adm-stats { grid-template-columns: 1fr 1fr; gap: 10px; }

      .adm-stat { padding: 14px; gap: 12px; }
      .adm-stat-icon { width: 40px; height: 40px; font-size: 18px; border-radius: 10px; }
      .adm-stat-info .value { font-size: 1.3rem; }

      .adm-table-head { flex-direction: column; align-items: flex-start; }
      .adm-table-actions { width: 100%; justify-content: flex-start; }

      table { min-width: 100%; }
      th { font-size: 11px; padding: 9px 10px; }
      td { font-size: 12px; padding: 10px; }

      .adm-pagination { flex-direction: column; align-items: flex-start; }

      .adm-form-row { flex-direction: column; gap: 0; }
    }

    @media (max-width: 420px) {
      .adm-stats { grid-template-columns: 1fr; }
    }
  </style>
</head>
<body>

<?php include '../templates/sidebar.php'; ?>
<?php include '../templates/header.php'; ?>

<div class="adm-page">

  <div class="adm-page-title">
    <i class="fas fa-chart-line"></i> Dashboard Overview
  </div>

  <!-- Stats -->
  <div class="adm-stats">
    <div class="adm-stat">
      <div class="adm-stat-icon icon-blue"><i class="fas fa-users"></i></div>
      <div class="adm-stat-info">
        <div class="label">Total Users</div>
        <div class="value"><?= $totalUsers ?></div>
        <div class="trend">↑ 12.5% this month</div>
      </div>
    </div>
    <div class="adm-stat">
      <div class="adm-stat-icon icon-green"><i class="fas fa-wallet"></i></div>
      <div class="adm-stat-info">
        <div class="label">Active Investments</div>
        <div class="value"><?= $activeInvestmentCount ?></div>
        <div class="trend">↑ 8.3% this week</div>
      </div>
    </div>
    <div class="adm-stat">
      <div class="adm-stat-icon icon-orange"><i class="fas fa-id-card-alt"></i></div>
      <div class="adm-stat-info">
        <div class="label">Pending KYC</div>
        <div class="value"><?= $pendingKycCount ?></div>
        <div class="trend">Live data</div>
      </div>
    </div>
    <div class="adm-stat">
      <div class="adm-stat-icon icon-purple"><i class="fas fa-dollar-sign"></i></div>
      <div class="adm-stat-info">
        <div class="label">USDT/INR Rate</div>
        <div class="value">89.90</div>
        <div class="trend">↑ 0.5% last hour</div>
      </div>
    </div>
  </div>

  <!-- Recent Users -->
  <div class="adm-table-card">
    <div class="adm-table-head">
      <div class="adm-table-title">Recent Users</div>
      <div class="adm-table-actions">
        <button class="adm-btn" id="filterUsersBtn"><span class="material-icons-round" style="font-size:16px">filter_list</span> Filter</button>
        <button class="adm-btn" id="exportUsersBtn"><span class="material-icons-round" style="font-size:16px">download</span> Export</button>
        <button class="adm-btn primary" onclick="openAdmModal('addUserModal')"><span class="material-icons-round" style="font-size:16px">add</span> Add User</button>
      </div>
    </div>
    <div class="adm-table-wrap">
      <table id="usersTable">
        <thead>
          <tr>
            <th>User ID</th><th>Name</th><th>Phone</th><th>Joined</th><th>Status</th><th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php
          if (!$conn->connect_error) {
            $result = $conn->query("SELECT * FROM admin_users ORDER BY created_at DESC LIMIT 5");
            if ($result && $result->num_rows > 0) {
              while ($row = $result->fetch_assoc()) {
                $uid   = "#USR" . str_pad($row['id'], 4, '0', STR_PAD_LEFT);
                $name  = htmlspecialchars($row['username'] ?? 'N/A');
                $phone = htmlspecialchars($row['phone'] ?? 'N/A');
                $joined = isset($row['created_at']) ? date('d M, h:i A', strtotime($row['created_at'])) : 'N/A';
                $status = $row['status'] ?? 'Unknown';
                $sc = '';
                $sl = strtolower($status);
                if ($sl === 'verified' || $sl === 'active')  $sc = 'status-approved';
                elseif ($sl === 'pending' || $sl === 'pending kyc') $sc = 'status-pending';
                elseif ($sl === 'rejected' || $sl === 'kyc rejected') $sc = 'status-rejected';
                echo "<tr>
                  <td>$uid</td>
                  <td>$name</td>
                  <td>$phone</td>
                  <td>$joined</td>
                  <td><span class='status-badge $sc'>$status</span></td>
                  <td>
                    <button class='adm-btn sm' onclick=\"viewUserDetails('$uid')\"><span class='material-icons-round' style='font-size:15px'>visibility</span></button>
                    <button class='adm-btn sm' onclick=\"editUser('$uid')\"><span class='material-icons-round' style='font-size:15px'>edit</span></button>
                    <button class='adm-btn sm' onclick=\"messageUser('$uid')\"><span class='material-icons-round' style='font-size:15px'>mail</span></button>
                  </td>
                </tr>";
              }
            } else {
              echo "<tr><td colspan='6' style='text-align:center;color:#999;padding:20px'>No users found.</td></tr>";
            }
          }
          ?>
        </tbody>
      </table>
    </div>
    <div class="adm-pagination">
      <div class="adm-pagination-info">Showing 1–5 entries</div>
      <div class="adm-pagination-controls">
        <div class="adm-page-item disabled"><span class="material-icons-round" style="font-size:16px">chevron_left</span></div>
        <div class="adm-page-item active">1</div>
        <div class="adm-page-item">2</div>
        <div class="adm-page-item">3</div>
        <div class="adm-page-item"><span class="material-icons-round" style="font-size:16px">chevron_right</span></div>
      </div>
    </div>
  </div>

  <!-- Recent Transactions -->
  <div class="adm-table-card">
    <div class="adm-table-head">
      <div class="adm-table-title">Recent Transactions</div>
      <div class="adm-table-actions">
        <button class="adm-btn" id="filterTransactionsBtn"><span class="material-icons-round" style="font-size:16px">filter_list</span> Filter</button>
        <button class="adm-btn" id="exportTransactionsBtn"><span class="material-icons-round" style="font-size:16px">download</span> Export</button>
      </div>
    </div>
    <div class="adm-table-wrap">
      <table id="transactionsTable">
        <thead>
          <tr><th>TX ID</th><th>User</th><th>Amount</th><th>Type</th><th>Status</th><th>Date</th><th>Actions</th></tr>
        </thead>
        <tbody>
          <?php foreach ($transactions as $tx): ?>
          <tr>
            <td>#TXN<?= str_pad($tx['id'], 5, '0', STR_PAD_LEFT) ?></td>
            <td><?= htmlspecialchars($tx['username'] ?? 'User #'.$tx['user_id']) ?></td>
            <td>
              <?= $tx['currency'] === 'INR' ? '₹' : '' ?><?= number_format($tx['amount'], 2) ?><?= $tx['currency'] === 'USDT' ? ' USDT' : '' ?>
            </td>
            <td><?= ucfirst(htmlspecialchars($tx['type'])) ?></td>
            <td><span class="status-badge status-<?= strtolower($tx['status'] ?? 'pending') ?>"><?= ucfirst(htmlspecialchars($tx['status'] ?? 'pending')) ?></span></td>
            <td><?= isset($tx['created_at']) ? date('d M, h:i A', strtotime($tx['created_at'])) : 'N/A' ?></td>
            <td>
              <button class="adm-btn sm" onclick="viewTransaction('<?= $tx['id'] ?>')"><span class="material-icons-round" style="font-size:15px">visibility</span></button>
              <button class="adm-btn sm" onclick="downloadReceipt('<?= $tx['id'] ?>')"><span class="material-icons-round" style="font-size:15px">receipt</span></button>
            </td>
          </tr>
          <?php endforeach; ?>
          <?php if (empty($transactions)): ?>
          <tr><td colspan="7" style="text-align:center;color:#999;padding:20px">No transactions found.</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
    <div class="adm-pagination">
      <div class="adm-pagination-info">
        <?php if ($totalTransactions == 0): ?>
          Showing 0 of 0
        <?php else: ?>
          Showing <?= (($currentPage-1)*5)+1 ?>–<?= min($currentPage*5, $totalTransactions) ?> of <?= $totalTransactions ?>
        <?php endif; ?>
      </div>
      <div class="adm-pagination-controls">
        <?php if ($currentPage > 1): ?>
          <div class="adm-page-item" onclick="location.href='?page=<?= $currentPage-1 ?>'"><span class="material-icons-round" style="font-size:16px">chevron_left</span></div>
        <?php else: ?>
          <div class="adm-page-item disabled"><span class="material-icons-round" style="font-size:16px">chevron_left</span></div>
        <?php endif; ?>
        <?php for ($i=1; $i<=$totalPages; $i++): ?>
          <div class="adm-page-item <?= $i===$currentPage?'active':'' ?>" onclick="location.href='?page=<?= $i ?>'"> <?= $i ?></div>
        <?php endfor; ?>
        <?php if ($currentPage < $totalPages): ?>
          <div class="adm-page-item" onclick="location.href='?page=<?= $currentPage+1 ?>'"><span class="material-icons-round" style="font-size:16px">chevron_right</span></div>
        <?php else: ?>
          <div class="adm-page-item disabled"><span class="material-icons-round" style="font-size:16px">chevron_right</span></div>
        <?php endif; ?>
      </div>
    </div>
  </div>

</div><!-- /adm-page -->

<!-- Add User Modal -->
<div class="adm-modal" id="addUserModal">
  <div class="adm-modal-box">
    <div class="adm-modal-hdr">
      <h3>Add New User</h3>
      <button class="adm-modal-close" onclick="closeAdmModal('addUserModal')">✕</button>
    </div>
    <div class="adm-modal-body">
      <form id="addUserForm" method="POST" action="add_user.php">
        <div class="adm-form-row">
          <div class="adm-form-group"><label>First Name</label><input type="text" name="firstName" required></div>
          <div class="adm-form-group"><label>Last Name</label><input type="text" name="lastName" required></div>
        </div>
        <div class="adm-form-group"><label>Email</label><input type="email" name="email" required></div>
        <div class="adm-form-group"><label>Phone</label><input type="tel" name="phone" required></div>
        <div class="adm-form-group">
          <label>User Type</label>
          <select name="userType" required>
            <option value="">Select</option>
            <option>Investor</option><option>Trader</option><option>Admin</option>
          </select>
        </div>
        <div class="adm-form-group"><label>Initial Balance (₹)</label><input type="number" name="initialBalance" value="0"></div>
        <div class="adm-form-group">
          <label>Status</label>
          <select name="userStatus" required>
            <option>Active</option><option>Inactive</option><option>Suspended</option>
          </select>
        </div>
      </form>
    </div>
    <div class="adm-modal-ftr">
      <button class="adm-btn" onclick="closeAdmModal('addUserModal')">Cancel</button>
      <button class="adm-btn primary" onclick="document.getElementById('addUserForm').submit()">Add User</button>
    </div>
  </div>
</div>

<?php if (isset($_SESSION['success'])): ?>
  <div style="position:fixed;bottom:20px;right:20px;background:#1e8e3e;color:#fff;padding:12px 20px;border-radius:8px;z-index:3000">
    <?= $_SESSION['success']; unset($_SESSION['success']); ?>
  </div>
<?php endif; ?>

<script>
  function openAdmModal(id)  { document.getElementById(id).classList.add('open'); }
  function closeAdmModal(id) { document.getElementById(id).classList.remove('open'); }

  function viewTransaction(id)  { alert('Transaction: ' + id); }
  function downloadReceipt(id)  { alert('Receipt: ' + id); }
  function viewUserDetails(id)  { alert('User: ' + id); }
  function editUser(id)         { alert('Edit: ' + id); }
  function messageUser(id)      { alert('Message: ' + id); }

  document.getElementById('filterTransactionsBtn').addEventListener('click', function(){ alert('Filter'); });
  document.getElementById('exportTransactionsBtn').addEventListener('click', function(){ alert('Export'); });
  document.getElementById('filterUsersBtn').addEventListener('click', function(){ alert('Filter'); });

  document.getElementById('exportUsersBtn').addEventListener('click', function () {
    const { jsPDF } = window.jspdf;
    const doc = new jsPDF();
    const table = document.getElementById('usersTable');
    const rows = [];
    for (let i = 1; i < table.rows.length; i++) {
      const r = [];
      for (let j = 0; j < table.rows[i].cells.length - 1; j++) r.push(table.rows[i].cells[j].innerText);
      rows.push(r);
    }
    doc.autoTable({ head: [["User ID","Name","Phone","Joined","Status"]], body: rows, startY: 20, theme: 'grid' });
    doc.save('users_report.pdf');
  });

  // Close modal on backdrop click
  document.querySelectorAll('.adm-modal').forEach(function(m){
    m.addEventListener('click', function(e){ if(e.target === m) m.classList.remove('open'); });
  });
</script>

<?php include '../templates/footer.php'; ?>
</body>
</html>
