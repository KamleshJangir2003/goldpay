<?php
include '../templates/sidebar.php';
include '../templates/header.php';
include '../includes/db.php'; // your DB connection

// Pagination setup
$limit = 10;
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Total login records
$countSql = "SELECT COUNT(*) as total_records FROM login_history";
$totalRecords = $conn->query($countSql)->fetch_assoc()['total_records'];
$totalPages = ceil($totalRecords / $limit);

// Total distinct users
$countUsersSql = "SELECT COUNT(DISTINCT user_id) as total_users FROM login_history";
$totalUsers = $conn->query($countUsersSql)->fetch_assoc()['total_users'];

// Fetch login history with pagination
$sql = "SELECT lh.*, u.email, u.username 
        FROM login_history lh 
        JOIN users u ON lh.user_id = u.id 
        ORDER BY lh.login_time DESC
        LIMIT $limit OFFSET $offset";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>admin-login_history</title>
    <link rel="icon" type="image/x-icon" href="../../favicon.ico">
    <style>
* { box-sizing: border-box; }
html, body { overflow-x: hidden; margin: 0; }
body { font-family: 'Roboto', sans-serif; background: #fff; }

.dash-over { margin-left: 260px; padding: 20px; }
.table-scroll { overflow-x: auto; -webkit-overflow-scrolling: touch; }
table { width: 100%; min-width: 600px; border-collapse: collapse; background: #fff; box-shadow: 0 4px 12px rgba(0,0,0,0.05); }
table th { background: #f2f2f2; color: #333; padding: 14px 12px; text-align: left; font-weight: 600; font-size: 14px; border-bottom: 2px solid #e0e0e0; }
table td { padding: 12px; border-bottom: 1px solid #eee; font-size: 14px; color: #444; }
table tr:hover td { background: #fafafa; }

@media(max-width: 768px) {
    .dash-over { margin-left: 0; padding: 12px; }
    table th, table td { font-size: 13px; padding: 8px 10px; }
}
    </style>
</head>
<body>
    

<div id="content" class="dash-over">
    <h2>Login History</h2>
    <p>Total Records: <?php echo $totalRecords; ?> | Distinct Users: <?php echo $totalUsers; ?></p>
    <div class="table-scroll">
    <table border="1" cellpadding="10" cellspacing="0" style="width:100%; border-collapse: collapse;">
        <tr>
            <th>UserName</th>
            <th>Email</th>
            <th>Login Time</th>
            <th>IP Address</th>
            <th>User Agent</th>
        </tr>
        <?php while ($row = $result->fetch_assoc()) { ?>
        <tr>
            <td><?php echo htmlspecialchars($row['username']); ?></td>
            <td><?php echo htmlspecialchars($row['email']); ?></td>
            <td><?php echo htmlspecialchars($row['login_time']); ?></td>
            <td><?php echo htmlspecialchars($row['ip_address']); ?></td>
            <td><?php echo htmlspecialchars($row['user_agent']); ?></td>
        </tr>
        <?php } ?>
    </table>
    </div>

    <!-- Pagination -->
    <?php if ($totalPages > 1): ?>
    <div style="margin-top:20px; display:flex; gap:6px; flex-wrap:wrap; align-items:center; justify-content:center;">
        <?php if ($page > 1): ?>
            <a href="?page=<?= $page - 1 ?>" style="padding:7px 14px; border:1px solid #ddd; border-radius:5px; text-decoration:none; color:#333; background:#fff;">&laquo; Prev</a>
        <?php endif; ?>

        <?php
        $start = max(1, $page - 2);
        $end = min($totalPages, $page + 2);
        if ($start > 1) echo '<span style="padding:7px 10px;">...</span>';
        for ($i = $start; $i <= $end; $i++):
        ?>
            <a href="?page=<?= $i ?>" style="padding:7px 14px; border:1px solid <?= $i == $page ? '#D4AF37' : '#ddd' ?>; border-radius:5px; text-decoration:none; color:<?= $i == $page ? '#fff' : '#333' ?>; background:<?= $i == $page ? '#D4AF37' : '#fff' ?>;"><?= $i ?></a>
        <?php endfor; ?>
        <?php if ($end < $totalPages) echo '<span style="padding:7px 10px;">...</span>'; ?>

        <?php if ($page < $totalPages): ?>
            <a href="?page=<?= $page + 1 ?>" style="padding:7px 14px; border:1px solid #ddd; border-radius:5px; text-decoration:none; color:#333; background:#fff;">Next &raquo;</a>
        <?php endif; ?>

        <span style="margin-left:10px; font-size:13px; color:#666;">Page <?= $page ?> of <?= $totalPages ?></span>
    </div>
    <?php endif; ?>

</div>
</body>
</html>

<?php include '../templates/footer.php'; ?>
