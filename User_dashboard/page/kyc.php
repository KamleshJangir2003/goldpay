<?php
if (session_status() === PHP_SESSION_NONE) { session_name('user_session'); session_start(); }
require '../config/db.php';

if (!isset($_SESSION['user_id'])) { header("Location: ../auth/login.php"); exit; }

$user_id = $_SESSION['user_id'];
$kycStatus = 'not_submitted';
$rejectionReason = '';
$kycRecordExists = false;

$stmt = $pdo->prepare("SELECT * FROM user_kyc WHERE user_id = ?");
$stmt->execute([$user_id]);
$kycData = $stmt->fetch(PDO::FETCH_ASSOC);

if ($kycData) {
    $kycStatus = $kycData['status'];
    $rejectionReason = $kycData['rejection_reason'] ?? '';
    $kycRecordExists = true;
}

// Handle file upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $kycStatus !== 'approved') {
    $uploadPath = __DIR__ . "/../uploads/kyc_documents/$user_id/";
    if (!is_dir($uploadPath)) mkdir($uploadPath, 0775, true);

    $allowedTypes = ['image/jpeg', 'image/png', 'application/pdf'];
    $error = '';

    function saveKycFile($fileInput, $prefix, $uploadPath, $allowedTypes) {
        if (!isset($_FILES[$fileInput]) || $_FILES[$fileInput]['error'] !== UPLOAD_ERR_OK) return null;
        $fileTmp  = $_FILES[$fileInput]['tmp_name'];
        $fileType = mime_content_type($fileTmp);
        if (!in_array($fileType, $allowedTypes)) throw new Exception("Invalid file type for $prefix. Only JPG, PNG, PDF allowed.");
        $ext      = pathinfo($_FILES[$fileInput]['name'], PATHINFO_EXTENSION);
        $filename = $prefix . '_' . time() . '.' . $ext;
        if (!move_uploaded_file($fileTmp, $uploadPath . $filename)) throw new Exception("Failed to upload $prefix.");
        return "uploads/kyc_documents/$user_id/" . $filename;
    }

    try {
        $panPath    = saveKycFile('pan_file', 'pan', $uploadPath, $allowedTypes);
        $aadhaarPath = saveKycFile('aadhaar_file', 'aadhaar', $uploadPath, $allowedTypes);
        $bankPath   = saveKycFile('bank_file', 'bank', $uploadPath, $allowedTypes);

        if (!$panPath || !$aadhaarPath || !$bankPath) throw new Exception("All 3 documents are required.");

        if ($kycRecordExists) {
            $pdo->prepare("UPDATE user_kyc SET pan_card=?, aadhaar_card=?, bank_statement=?, status='pending', rejection_reason=NULL, updated_at=NOW() WHERE user_id=?")
                ->execute([$panPath, $aadhaarPath, $bankPath, $user_id]);
        } else {
            $pdo->prepare("INSERT INTO user_kyc (user_id, pan_card, aadhaar_card, bank_statement, status) VALUES (?, ?, ?, ?, 'pending')")
                ->execute([$user_id, $panPath, $aadhaarPath, $bankPath]);
        }

        // Also update kyc_verifications table
        $pdo->prepare("INSERT INTO kyc_verifications (user_id, document_type, status, submitted_at) VALUES (?, 'full_kyc', 'pending', NOW()) ON DUPLICATE KEY UPDATE status='pending', submitted_at=NOW()")
            ->execute([$user_id]);

        header("Location: kyc.php?success=1"); exit;
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

$badgeColor = ['not_submitted' => '#64748b', 'pending' => '#f59e0b', 'approved' => '#22c55e', 'rejected' => '#ef4444'];
$bc = $badgeColor[$kycStatus] ?? '#64748b';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>KYC Verification - Goldpay</title>
  <link rel="icon" type="image/x-icon" href="../../favicon.ico">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link href="https://fonts.googleapis.com/icon?family=Material+Icons+Round" rel="stylesheet">
  <style>
    * { margin:0; padding:0; box-sizing:border-box; font-family:'Poppins',sans-serif; }
    body { background:#f1f5f9; min-height:100vh; }
    .page-wrap { margin-left:250px; padding:24px; }
    .card { background:#fff; border-radius:16px; box-shadow:0 2px 12px rgba(0,0,0,0.07); padding:32px; max-width:760px; }

    /* Header */
    .kyc-head { display:flex; justify-content:space-between; align-items:center; margin-bottom:24px; }
    .kyc-head h2 { font-size:1.3rem; font-weight:700; color:#1e293b; display:flex; align-items:center; gap:10px; }
    .kyc-head h2 .material-icons-round { color:#6366f1; }
    .badge { padding:5px 14px; border-radius:20px; font-size:0.8rem; font-weight:700; color:#fff; background:<?= $bc ?>; }

    /* Status boxes */
    .status-box { padding:16px 20px; border-radius:12px; margin-bottom:20px; display:flex; align-items:center; gap:12px; font-size:0.9rem; font-weight:500; }
    .status-box.success { background:#f0fdf4; color:#166534; border:1px solid #bbf7d0; }
    .status-box.error   { background:#fef2f2; color:#991b1b; border:1px solid #fecaca; }
    .status-box.warning { background:#fffbeb; color:#92400e; border:1px solid #fde68a; }
    .status-box.info    { background:#eff6ff; color:#1e40af; border:1px solid #bfdbfe; }

    /* Upload grid */
    .upload-grid { display:grid; grid-template-columns:1fr 1fr; gap:16px; margin-bottom:24px; }
    .upload-box { border:2px dashed #cbd5e1; border-radius:12px; padding:24px 16px; text-align:center; cursor:pointer; transition:all 0.2s; position:relative; background:#f8fafc; }
    .upload-box:hover { border-color:#6366f1; background:#eef2ff; }
    .upload-box input[type=file] { position:absolute; inset:0; opacity:0; cursor:pointer; width:100%; height:100%; }
    .upload-box .material-icons-round { font-size:32px; color:#6366f1; margin-bottom:8px; }
    .upload-box .doc-title { font-size:0.88rem; font-weight:600; color:#1e293b; margin-bottom:4px; }
    .upload-box .doc-sub   { font-size:0.75rem; color:#64748b; }
    .upload-box .file-name { font-size:0.75rem; color:#6366f1; margin-top:6px; font-weight:500; }

    /* Submit btn */
    .btn-submit { width:100%; padding:13px; background:linear-gradient(135deg,#6366f1,#4f46e5); color:#fff; border:none; border-radius:10px; font-size:1rem; font-weight:700; cursor:pointer; display:flex; align-items:center; justify-content:center; gap:8px; }
    .btn-submit:hover { opacity:0.9; }

    /* Approved box */
    .approved-box { text-align:center; padding:40px 20px; }
    .approved-box .material-icons-round { font-size:64px; color:#22c55e; }
    .approved-box h3 { font-size:1.3rem; font-weight:700; color:#1e293b; margin:12px 0 8px; }
    .approved-box p  { color:#64748b; font-size:0.9rem; }

    /* Steps */
    .steps { display:flex; gap:0; margin-bottom:28px; }
    .step { flex:1; text-align:center; position:relative; }
    .step::after { content:''; position:absolute; top:16px; left:50%; width:100%; height:2px; background:#e2e8f0; z-index:0; }
    .step:last-child::after { display:none; }
    .step-circle { width:32px; height:32px; border-radius:50%; display:inline-flex; align-items:center; justify-content:center; font-size:0.8rem; font-weight:700; position:relative; z-index:1; }
    .step-circle.done    { background:#22c55e; color:#fff; }
    .step-circle.active  { background:#6366f1; color:#fff; }
    .step-circle.waiting { background:#e2e8f0; color:#64748b; }
    .step-label { font-size:0.72rem; color:#64748b; margin-top:6px; }

    /* FAQ */
    .faq { margin-top:28px; border-top:1px solid #f1f5f9; padding-top:20px; }
    .faq h3 { font-size:0.95rem; font-weight:700; color:#1e293b; margin-bottom:14px; }
    .faq-item { margin-bottom:12px; }
    .faq-item strong { font-size:0.85rem; color:#374151; }
    .faq-item p { font-size:0.82rem; color:#64748b; margin-top:2px; }

    @media(max-width:768px) { .page-wrap { margin-left:0; padding:12px; } .upload-grid { grid-template-columns:1fr; } }
  </style>
</head>
<body>
<?php include('../sidebar.php'); ?>
<?php include('../mobile_header.php'); ?>

<div class="page-wrap">
<div class="card">

  <!-- Header -->
  <div class="kyc-head">
    <h2><span class="material-icons-round">verified_user</span> KYC Verification</h2>
    <span class="badge"><?= ucfirst($kycStatus === 'not_submitted' ? 'Not Submitted' : $kycStatus) ?></span>
  </div>

  <!-- Progress Steps -->
  <div class="steps">
    <div class="step">
      <div class="step-circle done"><span class="material-icons-round" style="font-size:16px">person</span></div>
      <div class="step-label">Account</div>
    </div>
    <div class="step">
      <div class="step-circle <?= in_array($kycStatus, ['pending','approved']) ? 'done' : ($kycStatus === 'not_submitted' ? 'active' : 'active') ?>">2</div>
      <div class="step-label">Documents</div>
    </div>
    <div class="step">
      <div class="step-circle <?= $kycStatus === 'pending' ? 'active' : ($kycStatus === 'approved' ? 'done' : 'waiting') ?>">3</div>
      <div class="step-label">Review</div>
    </div>
    <div class="step">
      <div class="step-circle <?= $kycStatus === 'approved' ? 'done' : 'waiting' ?>">4</div>
      <div class="step-label">Verified</div>
    </div>
  </div>

  <!-- Messages -->
  <?php if (isset($_GET['success'])): ?>
    <div class="status-box success"><span class="material-icons-round">check_circle</span> Documents submitted! We'll review within 24-48 hours.</div>
  <?php endif; ?>
  <?php if (!empty($error)): ?>
    <div class="status-box error"><span class="material-icons-round">error</span> <?= htmlspecialchars($error) ?></div>
  <?php endif; ?>
  <?php if ($kycStatus === 'pending'): ?>
    <div class="status-box warning"><span class="material-icons-round">pending</span> Your documents are under review. Please wait 24-48 hours.</div>
  <?php endif; ?>
  <?php if ($kycStatus === 'rejected' && $rejectionReason): ?>
    <div class="status-box error"><span class="material-icons-round">cancel</span> <div><strong>Rejected:</strong> <?= htmlspecialchars($rejectionReason) ?><br><small>Please re-upload correct documents below.</small></div></div>
  <?php endif; ?>

  <!-- Approved -->
  <?php if ($kycStatus === 'approved'): ?>
    <div class="approved-box">
      <span class="material-icons-round">verified</span>
      <h3>KYC Verified ✅</h3>
      <p>Your identity has been verified. You have full access to all features.</p>
    </div>

  <!-- Upload Form -->
  <?php else: ?>
    <form method="POST" enctype="multipart/form-data">
      <div class="upload-grid">

        <label class="upload-box">
          <span class="material-icons-round">credit_card</span>
          <div class="doc-title">PAN Card</div>
          <div class="doc-sub">JPG, PNG or PDF</div>
          <div class="file-name" id="pan-name">No file chosen</div>
          <input type="file" name="pan_file" accept=".jpg,.jpeg,.png,.pdf" required onchange="showName(this,'pan-name')">
        </label>

        <label class="upload-box">
          <span class="material-icons-round">badge</span>
          <div class="doc-title">Aadhaar Card</div>
          <div class="doc-sub">JPG, PNG or PDF</div>
          <div class="file-name" id="aadhaar-name">No file chosen</div>
          <input type="file" name="aadhaar_file" accept=".jpg,.jpeg,.png,.pdf" required onchange="showName(this,'aadhaar-name')">
        </label>

        <label class="upload-box" style="grid-column:1/-1">
          <span class="material-icons-round">account_balance</span>
          <div class="doc-title">Bank Statement / Passbook</div>
          <div class="doc-sub">JPG, PNG or PDF (last 3 months)</div>
          <div class="file-name" id="bank-name">No file chosen</div>
          <input type="file" name="bank_file" accept=".jpg,.jpeg,.png,.pdf" required onchange="showName(this,'bank-name')">
        </label>

      </div>

      <button type="submit" class="btn-submit">
        <span class="material-icons-round">upload_file</span>
        Submit Documents for Verification
      </button>
    </form>
  <?php endif; ?>

  <!-- FAQ -->
  <div class="faq">
    <h3>📋 Frequently Asked Questions</h3>
    <div class="faq-item"><strong>Why is KYC needed?</strong><p>It helps verify your identity and comply with financial regulations.</p></div>
    <div class="faq-item"><strong>How long does approval take?</strong><p>Usually within 24–48 hours after submission.</p></div>
    <div class="faq-item"><strong>What if my KYC is rejected?</strong><p>You'll see the reason above. Fix the issue and re-submit.</p></div>
    <div class="faq-item"><strong>Is my data secure?</strong><p>Yes, all documents are stored securely and encrypted.</p></div>
  </div>

</div>
</div>

<script>
function showName(input, id) {
  document.getElementById(id).textContent = input.files[0]?.name || 'No file chosen';
}
</script>
</body>
</html>
