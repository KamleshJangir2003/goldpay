<?php
if (session_status() === PHP_SESSION_NONE) { session_name('admin_session'); session_start(); }
require 'includes/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $old     = $_POST['old_password'] ?? '';
    $new     = $_POST['new_password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';
    $user_id = $_SESSION['user_id'] ?? 0;

    if (!$user_id || !$old || !$new || $new !== $confirm) {
        $_SESSION['error'] = "Please fill all fields and confirm new password correctly.";
        header("Location: profile.php");
        exit;
    }

    $stmt = $conn->prepare("SELECT password FROM admin_users WHERE id=?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stmt->bind_result($hashed);
    $stmt->fetch();
    $stmt->close();

    if (!$hashed || !password_verify($old, $hashed)) {
        $_SESSION['error'] = "Current password is incorrect.";
        header("Location: profile.php");
        exit;
    }

    $new_hashed = password_hash($new, PASSWORD_DEFAULT);
    $update = $conn->prepare("UPDATE admin_users SET password=? WHERE id=?");
    $update->bind_param("si", $new_hashed, $user_id);
    $update->execute();
    $update->close();

    $_SESSION['success'] = "Password updated successfully.";
    header("Location: profile.php");
    exit;
}
?>
