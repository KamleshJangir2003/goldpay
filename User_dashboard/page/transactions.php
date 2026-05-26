<?php
if (session_status() === PHP_SESSION_NONE) { session_name('user_session'); session_start(); }
include('submit_help.php');
require '../config/db.php';

$userId = $_SESSION['user_id'] ?? 0;
if (!$userId) {
    header('Location: ../auth/login.php');
    exit;
}

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$records_per_page = 10;
$start = ($page - 1) * $records_per_page;

// Total count for this user only
$countStmt = $pdo->prepare("SELECT COUNT(*) FROM user_transactions WHERE user_id = ?");
$countStmt->execute([$userId]);
$totalTransactions = $countStmt->fetchColumn();

// Fetch paginated records for this user only
$stmt = $pdo->prepare("SELECT * FROM user_transactions WHERE user_id = ? ORDER BY created_at DESC LIMIT ?, ?");
$stmt->bindValue(1, $userId, PDO::PARAM_INT);
$stmt->bindValue(2, $start, PDO::PARAM_INT);
$stmt->bindValue(3, $records_per_page, PDO::PARAM_INT);
$stmt->execute();
$transactions = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Transaction History</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="icon" type="image/x-icon" href="../../favicon.ico">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="../responsive.css">
  <style>
    body {
      font-family: 'Inter', sans-serif;
      background: #f1f5f9;
      margin: 0;
      padding: 0;
    }

    .container {
      background: #ffffff;
      padding: 30px;
      border-radius: 12px;
      box-shadow: 0 2px 8px rgba(0,0,0,0.05);
      margin-left: 250px;
    }
    .page-header {
      font-size: 26px;
      font-weight: 600;
      margin-bottom: 10px;
      color: #111827;
    }

    .total-count {
      font-size: 16px;
      margin-bottom: 25px;
      color: #374151;
    }

    table {
      width: 100%;
      border-collapse: collapse;
      margin-bottom: 30px;
    }

    table thead {
      background-color: #f3f4f6;
    }

    table th, table td {
      padding: 14px 16px;
      border-bottom: 1px solid #e5e7eb;
      text-align: left;
      font-size: 14px;
      color: #374151;
    }

    table tbody tr:hover {
      background-color: #f9fafb;
    }

    .pagination {
      text-align: center;
    }

    .pagination a {
      margin: 0 4px;
      padding: 8px 14px;
      font-size: 14px;
      text-decoration: none;
      color: #374151;
      background-color: #e5e7eb;
      border-radius: 6px;
      transition: background-color 0.3s, color 0.3s;
    }

    .pagination a:hover {
      background-color: #d1d5db;
    }

    .pagination a.active {
      background-color: #2563eb;
      color: #fff;
    }

    @media (max-width: 768px) {
      table th, table td { font-size: 13px; padding: 10px; }
    }
  </style>
</head>
<body>
<?php include('../sidebar.php'); ?>
<?php include('../mobile_header.php'); ?>
<div class="container">
  <div class="page-header">📄 Transaction History</div>

  <div class="total-count">
    Total Transactions: <strong><?= $totalTransactions ?></strong>
  </div>

  <table>
    <thead>
      <tr>
        <th>ID</th>
        <th>Type</th>
        <th>Amount</th>
        <th>Currency</th>
        <th>Description</th>
        <th>Status</th>
        <th>Date</th>
      </tr>
    </thead>
    <tbody>
      <?php if ($transactions): ?>
        <?php foreach ($transactions as $row):
          $typeLabels = [
            'sell'         => '💱 Sell USDT',
            'deposit'      => '⬇️ Deposit INR',
            'withdraw_inr' => '⬆️ Withdraw INR',
            'withdraw'     => '🔼 Withdraw USDT',
          ];
          $typeLabel = $typeLabels[$row['type']] ?? ucfirst($row['type']);
          $statusColor = $row['status'] === 'completed' ? '#16a34a' : ($row['status'] === 'approved' ? '#2563eb' : ($row['status'] === 'rejected' ? '#dc2626' : '#d97706'));
          $statusLabel = $row['status'] === 'approved' ? 'Processing' : ucfirst($row['status'] ?? 'pending');
          $amtPrefix = in_array($row['type'], ['deposit', 'sell']) ? '+' : '-';
          $amtColor  = in_array($row['type'], ['deposit', 'sell']) ? '#16a34a' : '#dc2626';
        ?>
          <tr>
            <td data-label="ID">#<?= htmlspecialchars($row['id']) ?></td>
            <td data-label="Type"><?= $typeLabel ?></td>
            <td data-label="Amount" style="color:<?= $amtColor ?>; font-weight:600;">
              <?= $amtPrefix ?><?= $row['currency'] === 'INR' ? '₹' : '' ?><?= number_format($row['amount'], 2) ?><?= $row['currency'] === 'USDT' ? ' USDT' : '' ?>
            </td>
            <td data-label="Currency"><?= htmlspecialchars($row['currency']) ?></td>
            <td data-label="Description" style="font-size:13px; color:#64748b;">
              <?= htmlspecialchars($row['description'] ?? '') ?>
              <?php if (!empty($row['admin_note'])): ?>
                <br><span style="display:inline-block;margin-top:4px;background:#eff6ff;border-left:3px solid #3b82f6;padding:4px 8px;border-radius:4px;color:#1d4ed8;font-size:12px;">
                  💬 <?= nl2br(htmlspecialchars($row['admin_note'])) ?>
                </span>
              <?php endif; ?>
            </td>
            <td data-label="Status"><span style="background:<?= $statusColor ?>20; color:<?= $statusColor ?>; padding:3px 10px; border-radius:20px; font-size:12px; font-weight:600;"><?= $statusLabel ?></span></td>
            <td data-label="Date"><?= date('d M Y, h:i A', strtotime($row['created_at'])) ?></td>
          </tr>
        <?php endforeach; ?>
      <?php else: ?>
        <tr><td colspan="7" style="text-align:center;">No transactions found.</td></tr>
      <?php endif; ?>
    </tbody>
  </table>

  <div class="pagination">
    <?php
      $total_pages = ceil($totalTransactions / $records_per_page);
      for ($i = 1; $i <= $total_pages; $i++):
    ?>
      <a href="?page=<?= $i ?>" class="<?= ($i == $page) ? 'active' : '' ?>"><?= $i ?></a>
    <?php endfor; ?>
  </div>
</div>

</body>
</html>
