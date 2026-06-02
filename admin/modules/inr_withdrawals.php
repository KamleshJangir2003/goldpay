<?php
include "../includes/db.php";

// PHPMailer for sending emails
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
require_once __DIR__ . '/../../User_dashboard/PHPMailer/src/Exception.php';
require_once __DIR__ . '/../../User_dashboard/PHPMailer/src/PHPMailer.php';
require_once __DIR__ . '/../../User_dashboard/PHPMailer/src/SMTP.php';

function sendStatusEmail($email, $username, $subject, $details) {
    if (!defined('_ENV_LOADED')) require_once __DIR__ . '/../../env.php';
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = $_ENV['SMTP_HOST'];
        $mail->SMTPAuth   = true;
        $mail->Username   = $_ENV['SMTP_USER'];
        $mail->Password   = $_ENV['SMTP_PASS'];
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;
        $mail->setFrom($_ENV['SMTP_FROM'], $_ENV['SMTP_FROM_NAME']);
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
            <p style='color:#c7d2fe;margin:4px 0 0;font-size:0.85rem;'>Goldpay</p>
          </div>
          <div style='padding:24px 28px;'>
            <p style='color:#374151;margin-bottom:16px;'>Hi <strong>{$username}</strong>, your withdrawal status has been updated:</p>
            <table style='width:100%;border-collapse:collapse;border:1px solid #e2e8f0;border-radius:8px;overflow:hidden;'>
              {$rows}
            </table>
            <p style='color:#64748b;font-size:0.8rem;margin-top:20px;'>If you have any questions, please contact support.</p>
          </div>
          <div style='background:#f8fafc;padding:14px 28px;text-align:center;font-size:0.75rem;color:#94a3b8;'>
            &copy; " . date('Y') . " Goldpay. All rights reserved.
          </div>
        </div>";
        
        $mail->send();
    } catch (Exception $e) {
        error_log("Status email failed: " . $mail->ErrorInfo);
    }
}

// Handle approve/reject/complete
if ($_SERVER['REQUEST_METHOD'] == "POST" && isset($_POST['action'], $_POST['id'])) {
    $id     = intval($_POST['id']);
    $action = $_POST['action'];

    if ($action === "approve") {
        $conn->query("UPDATE inr_withdrawals SET status = 'Approved', approved_at = NOW() WHERE id = $id");
        $row = $conn->query("SELECT w.id, w.user_id, w.amount, w.method, w.account_details, u.email, u.username FROM inr_withdrawals w LEFT JOIN users u ON w.user_id = u.id WHERE w.id = $id")->fetch_assoc();
        if ($row) {
            // Match by user_id + type + CAST amount to avoid decimal precision mismatch
            $conn->query("UPDATE user_transactions SET status = 'approved' WHERE user_id = {$row['user_id']} AND type = 'withdraw_inr' AND CAST(amount AS DECIMAL(10,2)) = CAST({$row['amount']} AS DECIMAL(10,2)) AND status = 'pending' ORDER BY created_at DESC LIMIT 1");
            sendStatusEmail($row['email'], $row['username'] ?? 'User', '✅ INR Withdrawal Approved - Goldpay', [
                'Transaction Type' => 'INR Withdrawal',
                'Amount'           => '₹' . number_format($row['amount'], 2),
                'Method'           => $row['method'],
                'Account Details'  => $row['account_details'],
                'Status'           => '✅ Approved',
                'Date & Time'      => date('d M Y, h:i A'),
            ]);
        }
    } elseif ($action === "complete") {
        $payment_notes = $conn->real_escape_string(trim($_POST['payment_notes'] ?? ''));
        $row = $conn->query("SELECT w.user_id, w.amount, w.method, w.account_details, u.email, u.username FROM inr_withdrawals w LEFT JOIN users u ON w.user_id = u.id WHERE w.id = $id AND w.status = 'Approved'")->fetch_assoc();
        if ($row) {
            $conn->query("UPDATE inr_withdrawals SET status = 'Completed', completed_at = NOW(), payment_notes = '$payment_notes' WHERE id = $id");
            // Update user_transactions with admin note and mark completed
            $note_escaped = $conn->real_escape_string($payment_notes);
            $conn->query("UPDATE user_transactions SET status = 'completed', admin_note = '$note_escaped' WHERE user_id = {$row['user_id']} AND type = 'withdraw_inr' AND CAST(amount AS DECIMAL(10,2)) = CAST({$row['amount']} AS DECIMAL(10,2)) ORDER BY created_at DESC LIMIT 1");
            // User notification
            $notif_msg = "Your INR withdrawal of ₹" . number_format($row['amount'], 2) . " has been completed." . ($payment_notes ? " Note: $payment_notes" : "");
            $conn->query("INSERT INTO user_notifications (user_id, title, message, type) VALUES ({$row['user_id']}, 'Withdrawal Completed ✅', '" . $conn->real_escape_string($notif_msg) . "', 'transaction')");
            sendStatusEmail($row['email'], $row['username'] ?? 'User', '✅ INR Withdrawal Completed - Goldpay', [
                'Transaction Type' => 'INR Withdrawal',
                'Amount'           => '₹' . number_format($row['amount'], 2),
                'Method'           => $row['method'],
                'Account Details'  => $row['account_details'],
                'Status'           => '✅ Payment Completed',
                'Admin Note'       => $payment_notes ?: '—',
                'Date & Time'      => date('d M Y, h:i A'),
            ]);
        }
    } else {
        // reject
        $row = $conn->query("SELECT w.user_id, w.amount, w.method, w.account_details, u.email, u.username FROM inr_withdrawals w LEFT JOIN users u ON w.user_id = u.id WHERE w.id = $id AND w.status = 'Pending'")->fetch_assoc();
        if ($row) {
            $conn->query("UPDATE wallets SET inr_balance = inr_balance + {$row['amount']} WHERE user_id = {$row['user_id']}");
            $conn->query("UPDATE user_transactions SET status = 'rejected' WHERE user_id = {$row['user_id']} AND type = 'withdraw_inr' AND amount = {$row['amount']} AND status = 'pending' LIMIT 1");
            sendStatusEmail($row['email'], $row['username'] ?? 'User', '❌ INR Withdrawal Rejected - Goldpay', [
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
    // Mark notification as read
    $conn->query("UPDATE admin_notifications SET is_read = 1 WHERE type = 'inr_withdrawal' AND is_read = 0 AND (ref_id = $id OR ref_id IS NULL)");
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
    .badge-pending   { background: #fff3cd; color: #856404; }
    .badge-approved  { background: #d1e7dd; color: #0f5132; }
    .badge-rejected  { background: #f8d7da; color: #842029; }
    .badge-completed { background: #cfe2ff; color: #084298; }
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
      // Count completed too
      $completed = $conn->query("SELECT COUNT(*) as c FROM inr_withdrawals WHERE status='Completed'")->fetch_assoc()['c'];
      $result = $conn->query($sql);
      $count = $offset + 1;
      if ($result && $result->num_rows > 0):
        while ($row = $result->fetch_assoc()):
          $s = strtolower($row['status']);
          $badgeClass = $s === 'pending' ? 'badge-pending' : ($s === 'approved' ? 'badge-approved' : ($s === 'completed' ? 'badge-completed' : 'badge-rejected'));
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
          <?php elseif ($row['status'] === 'Approved'): ?>
            <button class="btn btn-primary btn-sm" onclick="openCompleteModal(<?= $row['id'] ?>, '<?= htmlspecialchars(addslashes($row['username'] ?? 'User')) ?>', '<?= number_format($row['amount'], 2) ?>')">✔ Complete</button>
          <?php elseif ($row['status'] === 'Completed'): ?>
            <span class="text-success fw-bold">✔ Done</span>
            <?php if ($row['payment_notes']): ?>
              <br><small class="text-muted" title="<?= htmlspecialchars($row['payment_notes']) ?>">📝 <?= htmlspecialchars(mb_strimwidth($row['payment_notes'], 0, 30, '...')) ?></small>
            <?php endif; ?>
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

<!-- Complete Payment Modal -->
<div class="modal fade" id="completeModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header bg-primary text-white">
        <h5 class="modal-title">✔ Complete Payment</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <form method="POST">
        <input type="hidden" name="id" id="modal_id">
        <input type="hidden" name="action" value="complete">
        <div class="modal-body">
          <p class="mb-2">User: <strong id="modal_user"></strong> &nbsp;|&nbsp; Amount: <strong>₹<span id="modal_amt"></span></strong></p>
          <label class="form-label fw-semibold">Payment Note / Transaction Info <span class="text-muted fw-normal">(optional)</span></label>
          <textarea name="payment_notes" id="modal_notes" class="form-control" rows="4" placeholder="e.g. UTR: 123456789, Paid via NEFT on 25 May 2025..."></textarea>
          <small class="text-muted">Ye note user ke transaction history mein dikhega.</small>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary">✔ Mark as Completed</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
function openCompleteModal(id, user, amt) {
  document.getElementById('modal_id').value = id;
  document.getElementById('modal_user').textContent = user;
  document.getElementById('modal_amt').textContent = amt;
  document.getElementById('modal_notes').value = '';
  new bootstrap.Modal(document.getElementById('completeModal')).show();
}
</script>
</body>
</html>
