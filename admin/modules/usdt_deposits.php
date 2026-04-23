<?php
include "../includes/db.php";

// Handle approve / reject
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'], $_POST['deposit_id'])) {
    $depId  = intval($_POST['deposit_id']);
    $action = $_POST['action'];

    // Fetch deposit
    $dep = $conn->query("SELECT * FROM usdt_deposits WHERE id = $depId")->fetch_assoc();

    if ($dep && $dep['status'] === 'pending') {
        if ($action === 'approve') {
            $conn->begin_transaction();
            try {
                // Update deposit status
                $conn->query("UPDATE usdt_deposits SET status='confirmed' WHERE id=$depId");

                // Credit USDT to user wallet
                $userId = intval($dep['user_id']);
                $amount = floatval($dep['amount']);

                // Ensure wallet exists
                $conn->query("INSERT IGNORE INTO wallets (user_id, inr_balance, usdt_balance) VALUES ($userId, 0, 0)");
                $conn->query("UPDATE wallets SET usdt_balance = usdt_balance + $amount WHERE user_id = $userId");

                // Update transaction status
                $txHash = $conn->real_escape_string($dep['tx_hash']);
                $conn->query("UPDATE user_transactions SET status='completed' WHERE user_id=$userId AND type='deposit' AND currency='USDT' AND description LIKE '%$txHash%'");

                $conn->commit();
                $success = "Deposit approved and $amount USDT credited to user wallet.";
            } catch (Exception $e) {
                $conn->rollback();
                $error = "Error: " . $e->getMessage();
            }
        } elseif ($action === 'reject') {
            $conn->query("UPDATE usdt_deposits SET status='rejected' WHERE id=$depId");
            $success = "Deposit rejected.";
        }
    }
}

include '../templates/sidebar.php';
include '../templates/header.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>USDT Deposits - Admin</title>
  <link rel="icon" type="image/x-icon" href="../../favicon.ico">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
  <style>
    * { box-sizing: border-box; }
    html, body { overflow-x: hidden; }
    .container { margin-left: 260px; max-width: calc(100vw - 260px); }
    @media (max-width: 767px) { .container { margin-left: 0; max-width: 100%; padding: 12px; } }
    .badge-pending   { background:#fef9c3; color:#92400e; }
    .badge-confirmed { background:#dcfce7; color:#166534; }
    .badge-rejected  { background:#fee2e2; color:#991b1b; }
  </style>
</head>
<body>
<div class="container mt-5">
  <h2 class="mb-4">USDT Deposits</h2>

  <?php if (!empty($success)): ?>
    <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
  <?php endif; ?>
  <?php if (!empty($error)): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
  <?php endif; ?>

  <table class="table table-bordered table-striped align-middle">
    <thead class="table-dark">
      <tr>
        <th>#</th>
        <th>User</th>
        <th>Amount (USDT)</th>
        <th>Chain</th>
        <th>Tx Hash</th>
        <th>Status</th>
        <th>Date</th>
        <th>Action</th>
      </tr>
    </thead>
    <tbody>
      <?php
      $limit = 10;
      $page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
      $offset = ($page - 1) * $limit;
      $totalPages = ceil($conn->query("SELECT COUNT(*) as c FROM usdt_deposits")->fetch_assoc()['c'] / $limit);

      $result = $conn->query("
        SELECT ud.*, u.username, u.email
        FROM usdt_deposits ud
        LEFT JOIN users u ON u.id = ud.user_id
        ORDER BY ud.created_at DESC
        LIMIT $limit OFFSET $offset
      ");
      $count = $offset + 1;
      while ($row = $result->fetch_assoc()):
        $badgeClass = 'badge-' . $row['status'];
      ?>
      <tr>
        <td><?= $count++ ?></td>
        <td>
          <strong><?= htmlspecialchars($row['username'] ?? 'N/A') ?></strong><br>
          <small class="text-muted"><?= htmlspecialchars($row['email'] ?? '') ?></small>
        </td>
        <td><strong><?= number_format($row['amount'], 2) ?></strong></td>
        <td><?= htmlspecialchars($row['chain'] ?? 'TRC20') ?></td>
        <td>
          <span title="<?= htmlspecialchars($row['tx_hash']) ?>">
            <?= htmlspecialchars(substr($row['tx_hash'], 0, 16)) ?>...
          </span>
        </td>
        <td>
          <span class="badge <?= $badgeClass ?> px-2 py-1 rounded">
            <?= ucfirst($row['status']) ?>
          </span>
        </td>
        <td><?= date('d M Y, h:i A', strtotime($row['created_at'])) ?></td>
        <td>
          <?php if ($row['status'] === 'pending'): ?>
            <form method="POST" style="display:inline" onsubmit="return confirm('Approve this deposit?')">
              <input type="hidden" name="deposit_id" value="<?= $row['id'] ?>">
              <input type="hidden" name="action" value="approve">
              <button class="btn btn-success btn-sm">✔ Approve</button>
            </form>
            <form method="POST" style="display:inline" onsubmit="return confirm('Reject this deposit?')">
              <input type="hidden" name="deposit_id" value="<?= $row['id'] ?>">
              <input type="hidden" name="action" value="reject">
              <button class="btn btn-danger btn-sm">✘ Reject</button>
            </form>
          <?php else: ?>
            <span class="text-muted">—</span>
          <?php endif; ?>
        </td>
      </tr>
      <?php endwhile; ?>
    </tbody>
  </table>

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
