<?php
if (session_status() === PHP_SESSION_NONE) { session_name('admin_session'); session_start(); }
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: ../login.php"); exit();
}
include "../includes/db.php";

// Handle approve/reject
if ($_SERVER['REQUEST_METHOD'] == "POST" && isset($_POST['action'], $_POST['id'])) {
    $id     = intval($_POST['id']);
    $action = $_POST['action'];

    $row = $conn->query("SELECT * FROM inr_deposits WHERE id = $id")->fetch_assoc();

    if ($row && $row['status'] === 'pending') {
        if ($action === 'approve') {
            // Credit INR to wallet
            $conn->query("UPDATE wallets SET inr_balance = inr_balance + {$row['amount']} WHERE user_id = {$row['user_id']}");
            // If wallet doesn't exist, create it
            if ($conn->affected_rows === 0) {
                $conn->query("INSERT INTO wallets (user_id, inr_balance, usdt_balance) VALUES ({$row['user_id']}, {$row['amount']}, 0)");
            }
            // Update deposit status
            $conn->query("UPDATE inr_deposits SET status = 'approved', approved_at = NOW() WHERE id = $id");
            // Update user_transactions
            $conn->query("UPDATE user_transactions SET status = 'completed' WHERE user_id = {$row['user_id']} AND type = 'deposit' AND amount = {$row['amount']} AND status = 'pending' LIMIT 1");
        } else {
            $conn->query("UPDATE inr_deposits SET status = 'rejected' WHERE id = $id");
            $conn->query("UPDATE user_transactions SET status = 'rejected' WHERE user_id = {$row['user_id']} AND type = 'deposit' AND amount = {$row['amount']} AND status = 'pending' LIMIT 1");
        }
    }
    header("Location: inr_deposits_admin.php");
    exit;
}

include '../templates/sidebar.php';
include '../templates/header.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>INR Deposits - Admin</title>
  <link rel="icon" type="image/x-icon" href="../../favicon.ico">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    * { box-sizing: border-box; }
    html, body { overflow-x: hidden; }
    .container { margin-left: 260px; max-width: calc(100vw - 260px); }
    @media (max-width: 767px) { .container { margin-left: 0; max-width: 100%; padding: 12px; } }
    .badge-pending  { background: #fff3cd; color: #856404; }
    .badge-approved { background: #d1e7dd; color: #0f5132; }
    .badge-rejected { background: #f8d7da; color: #842029; }
  </style>
</head>
<body>
<div class="container mt-5">
  <h2 class="mb-4">💰 INR Deposit Requests</h2>

  <?php
  $pending  = $conn->query("SELECT COUNT(*) as c FROM inr_deposits WHERE status='pending'")->fetch_assoc()['c'];
  $approved = $conn->query("SELECT COUNT(*) as c FROM inr_deposits WHERE status='approved'")->fetch_assoc()['c'];
  $totalAmt = $conn->query("SELECT SUM(amount) as s FROM inr_deposits WHERE status='approved'")->fetch_assoc()['s'] ?? 0;
  ?>
  <div class="row mb-4">
    <div class="col-md-4">
      <div class="card text-center border-warning">
        <div class="card-body"><h5>Pending</h5><h3 class="text-warning"><?= $pending ?></h3></div>
      </div>
    </div>
    <div class="col-md-4">
      <div class="card text-center border-success">
        <div class="card-body"><h5>Approved</h5><h3 class="text-success"><?= $approved ?></h3></div>
      </div>
    </div>
    <div class="col-md-4">
      <div class="card text-center border-primary">
        <div class="card-body"><h5>Total Credited</h5><h3 class="text-primary">₹<?= number_format($totalAmt, 2) ?></h3></div>
      </div>
    </div>
  </div>

  <div class="table-responsive">
  <table class="table table-bordered table-striped table-hover">
    <thead class="table-dark">
      <tr>
        <th>#</th>
        <th>User</th>
        <th>Amount</th>
        <th>Method</th>
        <th>UTR / Reference</th>
        <th>Status</th>
        <th>Requested At</th>
        <th>Approved At</th>
        <th>Action</th>
      </tr>
    </thead>
    <tbody>
      <?php
      $limit = 10;
      $page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
      $offset = ($page - 1) * $limit;
      $totalPages = ceil($conn->query("SELECT COUNT(*) as c FROM inr_deposits")->fetch_assoc()['c'] / $limit);

      $sql = "SELECT d.*, u.username, u.email
              FROM inr_deposits d
              LEFT JOIN users u ON d.user_id = u.id
              ORDER BY d.created_at DESC
              LIMIT $limit OFFSET $offset";
      $result = $conn->query($sql);
      $count = $offset + 1;
      if ($result && $result->num_rows > 0):
        while ($row = $result->fetch_assoc()):
          $badgeClass = $row['status'] === 'pending' ? 'badge-pending' : ($row['status'] === 'approved' ? 'badge-approved' : 'badge-rejected');
      ?>
      <tr>
        <td><?= $count++ ?></td>
        <td>
          <strong><?= htmlspecialchars($row['username'] ?? 'User #'.$row['user_id']) ?></strong><br>
          <small class="text-muted"><?= htmlspecialchars($row['email'] ?? '') ?></small>
        </td>
        <td><strong>₹<?= number_format($row['amount'], 2) ?></strong></td>
        <td><?= htmlspecialchars($row['method']) ?></td>
        <td><code><?= htmlspecialchars($row['utr_number'] ?? '—') ?></code></td>
        <td><span class="badge <?= $badgeClass ?> px-2 py-1"><?= ucfirst($row['status']) ?></span></td>
        <td><?= $row['created_at'] ?></td>
        <td><?= $row['approved_at'] ?? '—' ?></td>
        <td>
          <?php if ($row['status'] === 'pending'): ?>
            <form method="POST" style="display:inline-block;">
              <input type="hidden" name="id" value="<?= $row['id'] ?>">
              <button name="action" value="approve" class="btn btn-success btn-sm">✅ Approve</button>
            </form>
            <form method="POST" style="display:inline-block;" onsubmit="return confirm('Reject this deposit?')">
              <input type="hidden" name="id" value="<?= $row['id'] ?>">
              <button name="action" value="reject" class="btn btn-danger btn-sm">❌ Reject</button>
            </form>
          <?php else: ?>
            <span class="text-muted">—</span>
          <?php endif; ?>
        </td>
      </tr>
      <?php endwhile; else: ?>
      <tr><td colspan="9" class="text-center text-muted">No deposit requests found.</td></tr>
      <?php endif; ?>
    </tbody>
  </table>
  </div>

  <?php if ($totalPages > 1): ?>
  <nav class="mt-3 d-flex justify-content-center">
    <ul class="pagination pagination-sm">
      <?php if ($page > 1): ?><li class="page-item"><a class="page-link" href="?page=<?= $page-1 ?>">&laquo;</a></li><?php endif; ?>
      <?php
        $start = max(1, $page - 2); $end = min($totalPages, $page + 2);
        if ($start > 1) echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
        for ($i = $start; $i <= $end; $i++):
      ?>
        <li class="page-item <?= $i == $page ? 'active' : '' ?>"><a class="page-link" href="?page=<?= $i ?>"><?= $i ?></a></li>
      <?php endfor;
        if ($end < $totalPages) echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
      ?>
      <?php if ($page < $totalPages): ?><li class="page-item"><a class="page-link" href="?page=<?= $page+1 ?>">&raquo;</a></li><?php endif; ?>
    </ul>
  </nav>
  <?php endif; ?>

</div>
</body>
</html>
