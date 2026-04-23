<?php
include "../includes/db.php";

// PHPMailer for sending emails
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
require_once __DIR__ . '/../../User_dashboard/PHPMailer/src/Exception.php';
require_once __DIR__ . '/../../User_dashboard/PHPMailer/src/PHPMailer.php';
require_once __DIR__ . '/../../User_dashboard/PHPMailer/src/SMTP.php';

function sendStatusEmail($email, $username, $subject, $details) {
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'Sharmagopesh706@gmail.com';
        $mail->Password   = 'auxplhwwkzetzuma';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;
        $mail->setFrom('Sharmagopesh706@gmail.com', 'MBPAY');
        $mail->addAddress($email, $username);
        $mail->isHTML(true);
        $mail->Subject = $subject;
        
        $rows = '';
        foreach ($details as $label => $value) {
            $rows .= "<tr>
                <td style='padding:8px 12px;font-weight:600;color:#374151;background:#f8fafc;width:40%;'>{$label}</td>
                <td style='padding:8px 12px;color:#1e293b;'>{$value}</td>
            </tr>";
        }
        
        $mail->Body = "
        <div style='font-family:Poppins,sans-serif;max-width:520px;margin:auto;border:1px solid #e2e8f0;border-radius:12px;overflow:hidden;'>
          <div style='background:linear-gradient(135deg,#6366f1,#4f46e5);padding:24px 28px;'>
            <h2 style='color:#fff;margin:0;font-size:1.2rem;'>Payment Status Update</h2>
            <p style='color:#c7d2fe;margin:4px 0 0;font-size:0.85rem;'>MBPAY</p>
          </div>
          <div style='padding:24px 28px;'>
            <p style='color:#374151;margin-bottom:16px;'>Hi <strong>{$username}</strong>, your withdrawal status has been updated:</p>
            <table style='width:100%;border-collapse:collapse;border:1px solid #e2e8f0;border-radius:8px;overflow:hidden;'>
              {$rows}
            </table>
            <p style='color:#64748b;font-size:0.8rem;margin-top:20px;'>If you have any questions, please contact support.</p>
          </div>
          <div style='background:#f8fafc;padding:14px 28px;text-align:center;font-size:0.75rem;color:#94a3b8;'>
            &copy; " . date('Y') . " MBPAY. All rights reserved.
          </div>
        </div>";
        
        $mail->send();
    } catch (Exception $e) {
        error_log("Status email failed: " . $mail->ErrorInfo);
    }
}

// Handle approve/reject
if ($_SERVER['REQUEST_METHOD'] == "POST" && isset($_POST['action'], $_POST['id'])) {
    $id     = intval($_POST['id']);
    $action = $_POST['action'] === "approve" ? "Approved" : "Rejected";

    if ($action === "Approved") {
        // Update status
        $conn->query("UPDATE inr_withdrawals SET status = 'Approved', approved_at = NOW() WHERE id = $id");
        // Update user_transactions status
        $row = $conn->query("SELECT w.user_id, w.amount, w.method, w.account_details, u.email, u.username FROM inr_withdrawals w LEFT JOIN users u ON w.user_id = u.id WHERE w.id = $id")->fetch_assoc();
        if ($row) {
            $conn->query("UPDATE user_transactions SET status = 'completed' WHERE user_id = {$row['user_id']} AND type = 'withdraw_inr' AND amount = {$row['amount']} AND status = 'pending' LIMIT 1");
            
            // Send success email
            sendStatusEmail($row['email'], $row['username'] ?? 'User', '✅ INR Withdrawal Successful - MBPAY', [
                'Transaction Type' => 'INR Withdrawal',
                'Amount'           => '₹' . number_format($row['amount'], 2),
                'Method'           => $row['method'],
                'Account Details'  => $row['account_details'],
                'Status'           => '✅ Approved & Processed',
                'Date & Time'      => date('d M Y, h:i A'),
            ]);
        }
    } else {
        // Rejected — refund INR back to wallet
        $row = $conn->query("SELECT w.user_id, w.amount, w.method, w.account_details, u.email, u.username FROM inr_withdrawals w LEFT JOIN users u ON w.user_id = u.id WHERE w.id = $id AND w.status = 'Pending'")->fetch_assoc();
        if ($row) {
            $conn->query("UPDATE wallets SET inr_balance = inr_balance + {$row['amount']} WHERE user_id = {$row['user_id']}");
            $conn->query("UPDATE user_transactions SET status = 'rejected' WHERE user_id = {$row['user_id']} AND type = 'withdraw_inr' AND amount = {$row['amount']} AND status = 'pending' LIMIT 1");
            
            // Send rejection email
            sendStatusEmail($row['email'], $row['username'] ?? 'User', '❌ INR Withdrawal Rejected - MBPAY', [
                'Transaction Type' => 'INR Withdrawal',
                'Amount'           => '₹' . number_format($row['amount'], 2),
                'Method'           => $row['method'],
                'Account Details'  => $row['account_details'],
                'Status'           => '❌ Rejected (Amount Refunded)',
                'Date & Time'      => date('d M Y, h:i A'),
            ]);
        }
        $conn->query("UPDATE inr_withdrawals SET status = 'Rejected' WHERE id = $id");
    }
    header("Location: inr_withdrawals.php");
    exit;
}

include '../templates/sidebar.php';
include '../templates/header.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>INR Withdrawals - Admin</title>
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
  <h2 class="mb-4">💸 INR Withdrawal Requests</h2>

  <!-- Stats -->
  <?php
  $pending  = $conn->query("SELECT COUNT(*) as c FROM inr_withdrawals WHERE status='Pending'")->fetch_assoc()['c'];
  $approved = $conn->query("SELECT COUNT(*) as c FROM inr_withdrawals WHERE status='Approved'")->fetch_assoc()['c'];
  $totalAmt = $conn->query("SELECT SUM(amount) as s FROM inr_withdrawals WHERE status='Approved'")->fetch_assoc()['s'] ?? 0;
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
        <div class="card-body"><h5>Total Paid Out</h5><h3 class="text-primary">₹<?= number_format($totalAmt, 2) ?></h3></div>
      </div>
    </div>
  </div>

  <div class="table-responsive">
  <table class="table table-bordered table-striped table-hover">
    <thead class="table-dark">
      <tr>
        <th>#</th>
        <th>User</th>
        <th>Amount (INR)</th>
        <th>Method</th>
        <th>Account Details</th>
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
      $totalPages = ceil($conn->query("SELECT COUNT(*) as c FROM inr_withdrawals")->fetch_assoc()['c'] / $limit);

      $sql = "SELECT w.*, u.username, u.email
              FROM inr_withdrawals w
              LEFT JOIN users u ON w.user_id = u.id
              ORDER BY w.requested_at DESC
              LIMIT $limit OFFSET $offset";
      $result = $conn->query($sql);
      $count = $offset + 1;
      if ($result && $result->num_rows > 0):
        while ($row = $result->fetch_assoc()):
          $badgeClass = strtolower($row['status']) === 'pending' ? 'badge-pending' : (strtolower($row['status']) === 'approved' ? 'badge-approved' : 'badge-rejected');
      ?>
      <tr>
        <td><?= $count++ ?></td>
        <td>
          <strong><?= htmlspecialchars($row['username'] ?? 'User #'.$row['user_id']) ?></strong><br>
          <small class="text-muted"><?= htmlspecialchars($row['email'] ?? '') ?></small>
        </td>
        <td><strong>₹<?= number_format($row['amount'], 2) ?></strong></td>
        <td><?= htmlspecialchars($row['method']) ?></td>
        <td><?= htmlspecialchars($row['account_details']) ?></td>
        <td><span class="badge <?= $badgeClass ?> px-2 py-1"><?= $row['status'] ?></span></td>
        <td><?= $row['requested_at'] ?></td>
        <td><?= $row['approved_at'] ?? '—' ?></td>
        <td>
          <?php if ($row['status'] === 'Pending'): ?>
            <form method="POST" style="display:inline-block;">
              <input type="hidden" name="id" value="<?= $row['id'] ?>">
              <button name="action" value="approve" class="btn btn-success btn-sm">✅ Approve</button>
            </form>
            <form method="POST" style="display:inline-block;" onsubmit="return confirm('Reject and refund?')">
              <input type="hidden" name="id" value="<?= $row['id'] ?>">
              <button name="action" value="reject" class="btn btn-danger btn-sm">❌ Reject</button>
            </form>
          <?php else: ?>
            <span class="text-muted">—</span>
          <?php endif; ?>
        </td>
      </tr>
      <?php endwhile; else: ?>
      <tr><td colspan="9" class="text-center text-muted">No withdrawal requests found.</td></tr>
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
