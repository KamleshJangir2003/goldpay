<?php
// Handle status change BEFORE any output
if (isset($_GET['change_status']) && isset($_GET['id'])) {
    require_once __DIR__ . '/../includes/config.php';
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $id = intval($_GET['id']);
    $newStatus = $_GET['change_status'] === 'active' ? 'inactive' : 'active';

    $updateStmt = $pdo->prepare("UPDATE users SET status = :status WHERE id = :id");
    $updateStmt->execute(['status' => $newStatus, 'id' => $id]);

    header("Location: sub_admins.php");
    exit();
}
?>

<?php
include '../templates/sidebar.php';
include '../templates/header.php';
require_once '../includes/config.php';

$pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Fetch users
$stmt = $pdo->prepare("SELECT id, username, email, role, status FROM users");
$stmt->execute();
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
    <link rel="icon" type="image/x-icon" href="../../favicon.ico">
    <style>
    body {
        background-color: #f8f9fa;
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        overflow-x: hidden;
    }

    .container {
        margin-left: 260px;
        max-width: calc(100vw - 260px);
        background-color: #fff;
        box-shadow: 0 0 10px rgba(0,0,0,0.1);
        border-radius: 10px;
        overflow-x: auto;
    }

    h2 {
        text-align: center;
        margin-bottom: 30px;
        color: #333;
    }

    .table {
        width: 100%;
        border-collapse: collapse;
        background-color: white;
    }

    .table th {
        background-color: #343a40;
        color: #fff;
        text-align: center;
    }

    .table td, .table th {
        padding: 12px;
        border: 1px solid #dee2e6;
        vertical-align: middle;
        text-align: center;
    }

    .btn {
        padding: 6px 12px;
        border-radius: 5px;
        font-size: 14px;
        text-decoration: none;
    }

    .btn-success {
        background-color: #28a745;
        color: white;
    }

    .btn-danger {
        background-color: #dc3545;
        color: white;
    }

    .btn:hover {
        opacity: 0.9;
    }

    @media screen and (max-width: 768px) {
        .table th, .table td { font-size: 13px; padding: 8px; }
        h2 { font-size: 20px; }
        .container { margin-left: 0; max-width: 100%; padding: 12px; }
        .table { min-width: 500px; }
    }
</style>

</head>
<body>
    <div class="container mt-4">
    <h2>Sub Admin Management</h2>
    <div style="overflow-x:auto;">
    <table class="table table-bordered">
        <thead>
            <tr>
                <th>ID</th>
                <th>Username</th>
                <th>Email</th>
                <th>Role</th>
                <th>Status</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($users as $user): ?>
                <tr>
                    <td><?= htmlspecialchars($user['id']) ?></td>
                    <td><?= htmlspecialchars($user['username']) ?></td>
                    <td><?= htmlspecialchars($user['email']) ?></td>
                    <td><?= htmlspecialchars($user['role']) ?></td>
                    <td><?= htmlspecialchars($user['status']) ?></td>
                    <td>
                        <a href="sub_admins.php?change_status=<?= $user['status'] ?>&id=<?= $user['id'] ?>" class="btn btn-sm <?= $user['status'] == 'active' ? 'btn-danger' : 'btn-success' ?>">
                            <?= $user['status'] == 'active' ? 'Deactivate' : 'Activate' ?>
                        </a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    </div>
</div>

</body>
</html>
