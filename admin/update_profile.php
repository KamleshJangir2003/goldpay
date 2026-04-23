<?php
if (session_status() === PHP_SESSION_NONE) { session_name('admin_session'); session_start(); }
require_once 'includes/db.php';

$user_id = $_SESSION['user_id'];
$username = $_POST['username'] ?? '';
$email = $_POST['email'] ?? '';
$phone = $_POST['phone'] ?? '';

if (empty($username) || empty($email) || empty($phone)) {
    $_SESSION['error'] = "All fields are required.";
    header("Location: profile.php");
    exit;
}

$stmt = $conn->prepare("UPDATE admin_users SET username = ?, email = ?, phone = ? WHERE id = ?");
$stmt->bind_param("sssi", $username, $email, $phone, $user_id);

if ($stmt->execute()) {
    $_SESSION['success'] = "Profile updated successfully.";
    $_SESSION['username'] = $username;
    $_SESSION['email']    = $email;
    $_SESSION['phone']    = $phone;
} else {
    $_SESSION['error'] = "Error updating profile.";
}

$stmt->close();
$conn->close();

header("Location: profile.php");
exit;
?>
