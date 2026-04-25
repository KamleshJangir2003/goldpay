<?php
session_start();
if (!isset($_SESSION['admin_logged_in'])) {
    header("Location: ../login.php");
    exit;
}

require_once __DIR__ . '/../includes/config.php';

// Fetch all unverified users
$stmt = $pdo->query("SELECT id, name, email FROM users WHERE status = 'pending'");
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Verify Users</title>
</head>
<body>
    <h2>Pending User Verification</h2>

    <?php if (count($users) > 0): ?>
        <table border="1">
            <tr><th>Name</th><th>Email</th><th>Action</th></tr>
            <?php foreach ($users as $user): ?>
                <tr>
                    <td><?= htmlspecialchars($user['name']) ?></td>
                    <td><?= htmlspecialchars($user['email']) ?></td>
                    <td>
                        <form method="POST" action="verify_user.php">
                            <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                            <button type="submit">Verify</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
        </table>
    <?php else: ?>
        <p>No pending users found.</p>
    <?php endif; ?>
</body>
</html>
