<?php
if (session_status() === PHP_SESSION_NONE) { session_name('admin_session'); session_start(); }
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: ../login.php"); exit();
}
include "../includes/db.php";
include '../templates/sidebar.php';
include '../templates/header.php';

$conn->query("UPDATE admin_notifications SET is_read = 1 WHERE type = 'sell_usdt' AND is_read = 0");

$search = $conn->real_escape_string($_GET['search'] ?? '');
$from   = $conn->real_escape_string($_GET['from'] ?? '');
$to     = $conn->real_escape_string($_GET['to'] ?? '');

$where = "WHERE t.type = 'sell'";
if ($search) $where .= " AND (u.username LIKE '%$search%' OR u.email LIKE '%$search%')";
if ($from)   $where .= " AND DATE(t.created_at) >= '$from'";
if ($to)     $where .= " AND DATE(t.created_at) <= '$to'";

$page    = max(1, intval($_GET['p'] ?? 1));
$perPage = 15;
$offset  = ($page - 1) * $perPage;

$total  = $conn->query("SELECT COUNT(*) as c FROM user_transactions t LEFT JOIN users u ON t.user_id = u.id $where")->fetch_assoc()['c'];
$pages  = ceil($total / $perPage);
$result = $conn->query("SELECT t.*, u.username, u.email FROM user_transactions t LEFT JOIN users u ON t.user_id = u.id $where ORDER BY t.created_at DESC LIMIT $offset, $perPage");
$stats  = $conn->query("SELECT COUNT(*) as total_orders, SUM(amount) as total_usdt FROM user_transactions WHERE type='sell' AND status='completed'")->fetch_assoc();
$totalInrReceived = $conn->query("SELECT SUM(CAST(REGEXP_REPLACE(SUBSTRING_INDEX(description,'= ',-1), '[^0-9.]', '') AS DECIMAL(15,2))) as s FROM user_transactions WHERE type='sell' AND status='completed'")->fetch_assoc()['s'] ?? 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Sell USDT - Admin</title>
  <link rel="icon" type="image/x-icon" href="../../favicon.ico">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    * { box-sizing: border-box; }
    html, body { overflow-x: hidden; }
    .wrap { margin-left: 260px; padding: 24px; max-width: calc(100vw - 260px); }
    @media (max-width: 767px) { .wrap { margin-left: 0; padding: 12px; max-width: 100%; } }
    .stat-card { border-radius: 12px; padding: 20px; color: #fff; margin-bottom: 12px; }

    @media (max-width: 767px) {
      .stat-card { padding: 14px; }
      .stat-card div:last-child { font-size: 1.4rem !important; }
      table thead { display: none; }
      table, table tbody, table tr, table td { display: block; width: 100%; }
      table tr { background: #fff; border: 1px solid #dee2e6; border-radius: 10px; margin-bottom: 12px; padding: 10px 14px; }
      table td { border: none; padding: 5px 0; font-size: 13px; display: flex; justify-content: space-between; align-items: center; }
      table td::before { content: attr(data-label); font-weight: 600; color: #555; font-size: 12px; flex-shrink: 0; margin-right: 8px; }
      table td:first-child { font-weight: 700; color: #dc3545; border-bottom: 1px solid #f1f1f1; padding-bottom: 8px; margin-bottom: 4px; }
      .table-responsive { overflow-x: unset; }
      .table-striped > tbody > tr:nth-of-type(odd) > * { background: transparent; }
      .table-hover > tbody > tr:hover > * { background: transparent; }
      .table-bordered { border: none !important; }
    }
  </style>
</head>
<body>
<div class="wrap">
  <h2 class="mb-4">&#x1F4C4; Sell USDT Transactions</h2>

  <div class="row mb-4">
    <div class="col-md-4">
      <div class="stat-card" style="background:#dc3545;">
        <div style="font-size:0.85rem;opacity:0.85;">Total Sell Orders</div>
        <div style="font-size:1.8rem;font-weight:700;"><?= number_format($stats['total_orders']) ?></div>
      </div>
    </div>
    <div class="col-md-4">
      <div class="stat-card" style="background:#6f42c1;">
        <div style="font-size:0.85rem;opacity:0.85;">Total USDT Sold</div>
        <div style="font-size:1.8rem;font-weight:700;"><?= number_format($stats['total_usdt'], 2) ?> USDT</div>
      </div>
    </div>
    <div class="col-md-4">
      <div class="stat-card" style="background:#0d6efd;">
        <div style="font-size:0.85rem;opacity:0.85;">Total INR Credited</div>
        <div style="font-size:1.8rem;font-weight:700;">&#x20B9;<?= number_format($totalInrReceived, 2) ?></div>
      </div>
    </div>
  </div>

  <form method="GET" class="row g-2 mb-4">
    <div class="col-md-4">
      <input type="text" name="search" value="<?= htmlspecialchars($_GET['search'] ?? '') ?>" class="form-control form-control-sm" placeholder="Search by username / email">
    </div>
    <div class="col-md-2">
      <input type="date" name="from" value="<?= htmlspecialchars($from) ?>" class="form-control form-control-sm">
    </div>
    <div class="col-md-2">
      <input type="date" name="to" value="<?= htmlspecialchars($to) ?>" class="form-control form-control-sm">
    </div>
    <div class="col-md-2">
      <button type="submit" class="btn btn-primary btn-sm w-100">&#x1F50D; Filter</button>
    </div>
    <div class="col-md-2">
      <a href="sell_usdt_admin.php" class="btn btn-outline-secondary btn-sm w-100">Reset</a>
    </div>
  </form>

  <p class="text-muted mb-2">Showing <?= $total ?> records</p>

  <div class="table-responsive">
    <table class="table table-bordered table-striped table-hover align-middle">
      <thead class="table-dark">
        <tr>
          <th>#</th><th>User</th><th>USDT Sold</th><th>INR Received</th><th>Rate / Label</th><th>Status</th><th>Date</th>
        </tr>
      </thead>
      <tbody>
        <?php
        $i = $offset + 1;
        if ($result && $result->num_rows > 0):
          while ($row = $result->fetch_assoc()):
            $desc = $row['description'] ?? '';
            // Format: "Sold X USDT @ ?RATE (LABEL) = ?AMOUNT"
            preg_match('/= .([0-9,\.]+)/', $desc, $inrM);
            preg_match('/@ .([0-9\.]+)(?:\s*\(([^)]+)\))?/', $desc, $rateM);
            $inrReceived = isset($inrM[1])  ? '&#x20B9;' . str_replace(',', '', $inrM[1]) : '&mdash;';
            $rateInfo    = isset($rateM[1]) ? '&#x20B9;' . $rateM[1] . (isset($rateM[2]) ? ' (' . $rateM[2] . ')' : '') : '&mdash;';
            $badge = $row['status'] === 'completed' ? 'success' : ($row['status'] === 'pending' ? 'warning' : 'danger');
        ?>
        <tr>
          <td data-label="#"><?= $i++ ?></td>
          <td data-label="User">
            <strong><?= htmlspecialchars($row['username'] ?? 'User #'.$row['user_id']) ?></strong><br>
            <small class="text-muted"><?= htmlspecialchars($row['email'] ?? '') ?></small>
          </td>
          <td data-label="USDT Sold"><strong><?= number_format($row['amount'], 4) ?> USDT</strong></td>
          <td data-label="INR Received"><strong><?= $inrReceived ?></strong></td>
          <td data-label="Rate / Label"><small><?= $rateInfo ?></small></td>
          <td data-label="Status"><span class="badge bg-<?= $badge ?>"><?= ucfirst($row['status']) ?></span></td>
          <td data-label="Date"><?= date('d M Y, h:i A', strtotime($row['created_at'])) ?></td>
        </tr>
        <?php endwhile; else: ?>
        <tr><td colspan="7" class="text-center text-muted">No sell transactions found.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>

  <?php if ($pages > 1): ?>
  <nav><ul class="pagination pagination-sm">
    <?php for ($pg = 1; $pg <= $pages; $pg++): ?>
      <li class="page-item <?= $pg == $page ? 'active' : '' ?>">
        <a class="page-link" href="?p=<?= $pg ?>&search=<?= urlencode($_GET['search'] ?? '') ?>&from=<?= urlencode($from) ?>&to=<?= urlencode($to) ?>"><?= $pg ?></a>
      </li>
    <?php endfor; ?>
  </ul></nav>
  <?php endif; ?>
</div>
</body>
</html>
