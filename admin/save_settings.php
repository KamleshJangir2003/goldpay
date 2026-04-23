<?php
if (session_status() === PHP_SESSION_NONE) { session_name('admin_session'); session_start(); }
require 'includes/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_SESSION['user_id'] ?? 0;

    if (!$user_id) {
        header("Location: /dollario-new/admin/login.php");
        exit;
    }

    // Store non-DB settings in session
    $_SESSION['setting_notifications'] = $_POST['notifications'] ?? 'email';
    $_SESSION['setting_language']      = $_POST['language']      ?? 'en';
    $_SESSION['setting_theme']         = $_POST['theme']         ?? 'light';

    // Only 2FA goes to DB (column: two_fa_enabled)
    $twofa = isset($_POST['2fa']) && $_POST['2fa'] === 'enabled' ? 1 : 0;

    $stmt = $conn->prepare("UPDATE users SET two_fa_enabled = ? WHERE id = ?");
    $stmt->bind_param("ii", $twofa, $user_id);
    $stmt->execute();

    header("Location: settings.php?saved=1");
    exit;
}
?>
