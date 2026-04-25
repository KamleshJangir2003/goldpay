<?php
if (session_status() === PHP_SESSION_NONE) { session_name('admin_session'); session_start(); }
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: ../login.php"); exit();
}
require_once '../includes/config.php';
$conn = new mysqli($host, $username, $password, $dbname);
if ($conn->connect_error) { die("Connection failed: " . $conn->connect_error); }
include('../templates/sidebar.php');
include('../templates/header.php');

// Apply filters
$conditions = [];

if (!empty($_GET['status'])) {
    $status = $conn->real_escape_string($_GET['status']);
    $conditions[] = "status = '$status'";
}

if (!empty($_GET['registration_date'])) {
    $dateFilter = $_GET['registration_date'];
    if ($dateFilter == 'today') {
        $conditions[] = "DATE(created_at) = CURDATE()";
    } elseif ($dateFilter == 'week') {
        $conditions[] = "YEARWEEK(created_at, 1) = YEARWEEK(CURDATE(), 1)";
    } elseif ($dateFilter == 'month') {
        $conditions[] = "MONTH(created_at) = MONTH(CURDATE()) AND YEAR(created_at) = YEAR(CURDATE())";
    }
}

$whereClause = count($conditions) > 0 ? 'WHERE ' . implode(' AND ', $conditions) : '';


?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>admin-All Users</title>
    <link rel="icon" type="image/x-icon" href="../../favicon.ico">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>

    <link href="https://fonts.googleapis.com/css2?family=Inter&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons+Round" rel="stylesheet">
    <style>
        * { box-sizing: border-box; }

        html, body { overflow-x: hidden; }

        body {
            font-family: 'Inter', sans-serif;
            background: #f5f6fa;
            margin: 0;
        }

        .content-area {
            max-width: 1200px;
            margin-left: 260px;
            padding: 20px;
        }

        .page-title {
            display: flex;
            align-items: center;
            font-size: 24px;
            margin-bottom: 20px;
            gap: 10px;
        }

        .filter-card {
            background: #fff;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            margin-bottom: 20px;
        }

        .filter-header {
            display: flex;
            align-items: center;
            font-weight: bold;
            margin-bottom: 15px;
            gap: 8px;
        }

        .filter-row {
            display: flex;
            gap: 16px;
            flex-wrap: wrap;
        }

        .form-group {
            flex: 1;
            min-width: 160px;
        }

        .form-group label { font-size: 13px; font-weight: 500; }

        .form-control {
            width: 100%;
            padding: 8px 10px;
            font-size: 14px;
            margin-top: 5px;
            border: 1px solid #ddd;
            border-radius: 6px;
        }

        .filter-actions {
            margin-top: 16px;
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .btn {
            padding: 8px 16px;
            border: none;
            cursor: pointer;
            font-size: 14px;
            border-radius: 6px;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            text-decoration: none;
        }

        .btn-outline { background: #f0f0f0; border: 1px solid #ddd; color: #333; }
        .btn-primary { background: #4CAF50; color: white; }
        .btn-sm { padding: 4px 8px; font-size: 0.8rem; }

        .data-table-container {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            padding: 20px;
            margin-left: 260px;
            margin-top: 0;
            margin-right: 20px;
            margin-bottom: 30px;
        }

        .table-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 16px;
            flex-wrap: wrap;
            gap: 10px;
        }

        .table-title { font-size: 1.2rem; font-weight: bold; }

        .table-actions { display: flex; gap: 8px; flex-wrap: wrap; }

        .table-scroll { overflow-x: auto; -webkit-overflow-scrolling: touch; }

        table {
            width: 100%;
            border-collapse: collapse;
            min-width: 560px;
        }

        th, td {
            padding: 12px 14px;
            text-align: left;
            border-bottom: 1px solid #eee;
            white-space: nowrap;
        }

        th { background: #f7f7f7; font-size: 13px; }
        td { font-size: 13px; }

        .status-badge {
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 0.72rem;
            font-weight: 500;
        }

        .status-approved  { background: #e6f7ee; color: #00a854; }
        .status-pending   { background: #fff7e6; color: #fa8c16; }
        .status-rejected  { background: #fff1f0; color: #f5222d; }

        .pagination {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 16px;
            flex-wrap: wrap;
            gap: 10px;
        }

        .pagination-info { font-size: 13px; color: #666; }

        .pagination-controls { display: flex; gap: 6px; flex-wrap: wrap; }

        .page-item {
            padding: 6px 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            cursor: pointer;
            font-size: 13px;
            display: flex;
            align-items: center;
        }

        .page-item.active { background: #4CAF50; color: white; border-color: #4CAF50; }
        .page-item.disabled { opacity: 0.5; cursor: not-allowed; }

        @media screen and (max-width: 768px) {
            .content-area {
                margin-left: 0;
                padding: 12px;
            }
            .data-table-container {
                margin-left: 0;
                margin-right: 0;
                padding: 14px;
                border-radius: 8px;
            }
            .filter-row { flex-direction: column; gap: 10px; }
            .form-group { min-width: 100%; }
            .table-header { flex-direction: column; align-items: flex-start; }
            .pagination { flex-direction: column; align-items: flex-start; }
        }
    </style>
</head>

<body>

    <div class="content-area">
        <div class="page-title">
            
            
        </div>

        <!-- Filter Form -->
        <form method="GET">
            <div class="filter-card">
                <div class="filter-header">
                    <span class="material-icons-round">filter_alt</span>
                    <span>Filter Users</span>
                </div>
                <div class="filter-body">
                    <div class="filter-row">
                        <div class="form-group">
                            <label for="userStatusFilter">Status</label>
                            <select id="userStatusFilter" name="status" class="form-control">
                                <option value="">All Statuses</option>
                                <option value="active" <?= ($_GET['status'] ?? '') == 'active' ? 'selected' : '' ?>>Active</option>
                                <option value="pending" <?= ($_GET['status'] ?? '') == 'pending' ? 'selected' : '' ?>>Pending</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="registrationDate">Registration Date</label>
                            <select id="registrationDate" name="registration_date" class="form-control">
                                <option value="">All Time</option>
                                <option value="today" <?= ($_GET['registration_date'] ?? '') == 'today' ? 'selected' : '' ?>>Today</option>
                                <option value="week" <?= ($_GET['registration_date'] ?? '') == 'week' ? 'selected' : '' ?>>
                                    This Week</option>
                                <option value="month" <?= ($_GET['registration_date'] ?? '') == 'month' ? 'selected' : '' ?>>This Month</option>
                            </select>
                        </div>
                    </div>
                    <div class="filter-actions">
                        <a href="all_users.php" class="btn btn-outline">
                            <span class="material-icons-round">refresh</span>
                            Reset
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <span class="material-icons-round">filter_alt</span>
                            Apply Filters
                        </button>
                    </div>
                </div>
            </div>
        </form>


        <!-- Applied Filters Summary -->
        <?php
        $activeFilters = [];

        if (!empty($_GET['status'])) {
            $activeFilters[] = "Status: " . ucfirst($_GET['status']);
        }
        if (!empty($_GET['registration_date'])) {
            $labels = ['today' => 'Today', 'week' => 'This Week', 'month' => 'This Month'];
            $activeFilters[] = "Registration Date: " . ($labels[$_GET['registration_date']] ?? $_GET['registration_date']);
        }
        if (count($activeFilters) > 0): ?>
            <div style="margin-bottom: 20px; font-size: 16px; color: #333;">
                <strong>Filters Applied:</strong> <?= implode(' | ', $activeFilters); ?>
            </div>
        <?php endif; ?>


        <!-- Users Table -->

    </div>

    <!--Users Found--->
    <div class="data-table-container">
        <div class="table-header">
            <div class="table-title">Users Found</div>
            <div class="table-actions">
                <button class="btn btn-outline" id="filterUsersBtn">
                    <span class="material-icons-round">filter_list</span>
                    Filter
                </button>
                <button class="btn btn-outline" id="exportUsersBtn">
                    <span class="material-icons-round">download</span>
                    Export
                </button>
                <!--<button class="btn btn-primary" onclick="openModal('addUserModal')">
                            <span class="material-icons-round">add</span>
                            Add User
                        </button>-->
            </div>
        </div>
        <div class="table-scroll">
        <table id="usersTable">
            <thead>
                <tr>
                    <th>User ID</th>
                    <th>Name</th>
                    <th>Phone</th>
                    <th>Joined</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $result = $conn->query("SELECT id, username, email, mobile, status FROM users $whereClause ORDER BY id DESC");

                if ($result && $result->num_rows > 0) {
                    while ($row = $result->fetch_assoc()) {
                        $userId = "#USR" . str_pad($row['id'], 4, '0', STR_PAD_LEFT);
                        $name = htmlspecialchars($row['username'] ?? 'N/A');
                        $phone = htmlspecialchars($row['mobile'] ?? 'N/A');
                        $joined = 'N/A';
                        $status = ucfirst($row['status'] ?? 'Unknown');

                        $statusClass = "";
                        if (strtolower($row['status']) == "active") $statusClass = "status-approved";
                        elseif (strtolower($row['status']) == "pending") $statusClass = "status-pending";

                        echo "<tr>
            <td>{$userId}</td>
            <td><span>{$name}</span></td>
            <td>{$phone}</td>
            <td>{$joined}</td>
            <td><span class='status-badge {$statusClass}'>{$status}</span></td>
            <td>
                <button class='btn btn-sm btn-outline'><span class='material-icons-round'>visibility</span></button>
                <button class='btn btn-sm btn-outline'><span class='material-icons-round'>edit</span></button>
            </td>
        </tr>";
                    }
                } else {
                    echo "<tr><td colspan='6'>No users found.</td></tr>";
                }
                ?>


            </tbody>
        </table>
        </div><!-- /table-scroll -->
        <div class="pagination">
            <div class="pagination-info">Showing 1 to 5 of 42 entries</div>
            <div class="pagination-controls">
                <div class="page-item disabled">
                    <span class="material-icons-round">chevron_left</span>
                </div>
                <div class="page-item active">1</div>
                <div class="page-item">2</div>
                <div class="page-item">3</div>
                <div class="page-item">4</div>
                <div class="page-item">5</div>
                <div class="page-item">
                    <span class="material-icons-round">chevron_right</span>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.getElementById('exportUsersBtn').addEventListener('click', function () {
            const table = document.getElementById('usersTable');

            html2canvas(table).then(canvas => {
                const imgData = canvas.toDataURL('image/png');
                const { jsPDF } = window.jspdf;
                const pdf = new jsPDF('p', 'mm', 'a4');

                const pageWidth = pdf.internal.pageSize.getWidth();
                const imgWidth = pageWidth - 20; // 10mm margin on each side
                const imgHeight = (canvas.height * imgWidth) / canvas.width;

                pdf.addImage(imgData, 'PNG', 10, 10, imgWidth, imgHeight);
                pdf.save("users-list.pdf");
            });
        });
    </script>



</body>

</html>

<?php $conn->close(); ?>