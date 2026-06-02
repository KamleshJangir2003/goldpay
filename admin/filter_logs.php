<?php
// DB connection (adjust credentials)
$conn = new mysqli("localhost", "u621774021_pay", "Goldpay999", "u621774021_Goldpay");

// Handle errors
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$type = $_POST['log_type'] ?? '';
$user = $_POST['log_user'] ?? '';
$date = $_POST['log_date'] ?? '';

$where = [];

if (!empty($type)) {
    $where[] = "action = '" . $conn->real_escape_string($type) . "'";
}
if (!empty($user)) {
    $where[] = "user_id = '" . $conn->real_escape_string($user) . "'";
}
if (!empty($date)) {
    $today = date("Y-m-d");
    if ($date == 'today') {
        $where[] = "DATE(created_at) = '$today'";
    } elseif ($date == 'week') {
        $weekAgo = date("Y-m-d", strtotime("-7 days"));
        $where[] = "DATE(created_at) >= '$weekAgo'";
    } elseif ($date == 'month') {
        $monthStart = date("Y-m-01");
        $where[] = "DATE(created_at) >= '$monthStart'";
    }
}

$sql = "SELECT * FROM system_logs";
if (!empty($where)) {
    $sql .= " WHERE " . implode(" AND ", $where);
}
$sql .= " ORDER BY created_at DESC";

$result = $conn->query($sql);
?>

<div class="card mt-4">
    <div class="card-header">
        <div class="card-title">Filtered Logs</div>
    </div>
    <div class="card-body">
        <?php if ($result && $result->num_rows > 0): ?>
            <?php while($row = $result->fetch_assoc()): ?>
                <div>
                    <strong>[<?= htmlspecialchars($row['created_at']) ?>]</strong>
                    (<?= htmlspecialchars($row['user_id']) ?> - <?= htmlspecialchars($row['action']) ?>)
                    ➜ <?= htmlspecialchars($row['details']) ?>
                </div>
                <hr>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="text-muted">No logs found with selected filters.</div>
        <?php endif; ?>
    </div>
</div>

<?php $conn->close(); ?>
