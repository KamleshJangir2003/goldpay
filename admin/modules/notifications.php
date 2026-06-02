<?php
// notifications.php
// Start session and include necessary files

include '../includes/db.php';
include '../templates/sidebar.php';
include '../templates/header.php';


$pageTitle = "Notifications | Goldpayo Admin";
$activePage = "Notifications";

// Handle notification actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['mark_as_read'])) {
        $notificationId = $_POST['notification_id'];
        $stmt = $conn->prepare("UPDATE notifications SET is_read = 1 WHERE id = ?");
        $stmt->bind_param("i", $notificationId);
        $stmt->execute();
    } elseif (isset($_POST['delete_notification'])) {
        $notificationId = $_POST['notification_id'];
        $stmt = $conn->prepare("DELETE FROM notifications WHERE id = ?");
        $stmt->bind_param("i", $notificationId);
        $stmt->execute();
    } elseif (isset($_POST['send_notification'])) {
        $title = $_POST['title'];
        $message = $_POST['message'];
        $type = $_POST['type'];
        $priority = $_POST['priority'];
        
        // Insert into database
        $stmt = $conn->prepare("INSERT INTO notifications (title, message, type, priority, created_at) VALUES (?, ?, ?, ?, NOW())");
        $stmt->bind_param("ssss", $title, $message, $type, $priority);
        $stmt->execute();
        
        $_SESSION['success'] = "Notification sent successfully!";
    } elseif (isset($_POST['mark_all_read'])) {
        $stmt = $conn->prepare("UPDATE notifications SET is_read = 1 WHERE is_read = 0");
        $stmt->execute();
    }
}

// Get filter parameters
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';
$search = isset($_GET['search']) ? $_GET['search'] : '';
$dateRange = isset($_GET['date_range']) ? $_GET['date_range'] : '';

// Build query based on filters
$query = "SELECT * FROM notifications WHERE 1=1";
$params = [];
$types = "";

if ($filter === 'unread') {
    $query .= " AND is_read = 0";
} elseif ($filter === 'system') {
    $query .= " AND type = 'system'";
} elseif ($filter === 'promotional') {
    $query .= " AND type = 'promotional'";
} elseif ($filter === 'user_alerts') {
    $query .= " AND type = 'user_alert'";
}

if (!empty($search)) {
    $query .= " AND (title LIKE ? OR message LIKE ?)";
    $searchTerm = "%$search%";
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $types .= "ss";
}

if (!empty($dateRange)) {
    if ($dateRange === 'today') {
        $query .= " AND DATE(created_at) = CURDATE()";
    } elseif ($dateRange === 'week') {
        $query .= " AND created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)";
    } elseif ($dateRange === 'month') {
        $query .= " AND created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)";
    } elseif ($dateRange === 'custom' && isset($_GET['start_date']) && isset($_GET['end_date'])) {
        $query .= " AND created_at BETWEEN ? AND ?";
        $params[] = $_GET['start_date'];
        $params[] = $_GET['end_date'] . ' 23:59:59';
        $types .= "ss";
    }
}

$query .= " ORDER BY created_at DESC LIMIT 20";

$stmt = $conn->prepare($query);

if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$result = $stmt->get_result();
$notifications = $result->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?></title>
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons+Round" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cedarville+Cursive&family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,800;1,900&family=Roboto:ital,wght@0,100..900;1,100..900&display=swap" rel="stylesheet">
    <style>
        /* Base Styles */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Roboto', 'Poppins', sans-serif;
        }

        body {
            background-color: #f5f7fa;
            color: #333;
            line-height: 1.6;
            overflow-x: hidden;
        }

        .material-icons-round {
            font-family: 'Material Icons Round';
            font-weight: normal;
            font-style: normal;
            font-size: 24px;
            line-height: 1;
            letter-spacing: normal;
            text-transform: none;
            display: inline-block;
            white-space: nowrap;
            word-wrap: normal;
            direction: ltr;
            -webkit-font-feature-settings: 'liga';
            -webkit-font-smoothing: antialiased;
            vertical-align: middle;
        }

        /* Content Area Styles */
        .content-area {
            margin-left: 260px;
            padding: 20px;
            max-width: calc(100vw - 260px);
            transition: all 0.3s ease;
        }

        .page-title {
            display: flex;
            align-items: center;
            margin-bottom: 20px;
        }

        .page-title span:first-child {
            margin-right: 10px;
            color: #4e73df;
        }

        .page-title span:last-child {
            font-size: 20px;
            font-weight: 600;
            color: #2d3748;
        }

        /* Card Styles */
        .card {
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            margin-bottom: 20px;
            overflow: hidden;
        }

        .card-header {
            padding: 15px 20px;
            border-bottom: 1px solid #eee;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .card-title {
            font-size: 16px;
            font-weight: 600;
            color: #2d3748;
        }

        .card-body {
            padding: 20px;
        }

        /* Button Styles */
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 8px 16px;
            border-radius: 6px;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s ease;
            border: 1px solid transparent;
        }

        .btn-primary {
            background-color: #4e73df;
            color: white;
        }

        .btn-primary:hover {
            background-color: #3b5ab7;
        }

        .btn-outline {
            background-color: transparent;
            border-color: #d1d5db;
            color: #4b5563;
        }

        .btn-outline:hover {
            background-color: #f3f4f6;
        }

        .btn-sm {
            padding: 6px 12px;
            font-size: 13px;
        }

        /* Notification Styles */
        .notification-list {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }

        .notification-item {
            display: flex;
            padding: 15px;
            border-radius: 8px;
            background-color: #fff;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
            transition: all 0.2s ease;
            border-left: 4px solid transparent;
        }

        .notification-item.unread {
            background-color: #f8f9fe;
            border-left-color: #4e73df;
        }

        .notification-item:hover {
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }

        .notification-icon {
            margin-right: 15px;
            color: #4e73df;
            font-size: 20px;
        }

        .notification-content {
            flex: 1;
        }

        .notification-title {
            font-weight: 500;
            margin-bottom: 5px;
            color: #2d3748;
        }

        .notification-message {
            font-size: 14px;
            color: #6b7280;
            margin-bottom: 8px;
        }

        .notification-meta {
            display: flex;
            justify-content: space-between;
            font-size: 12px;
            color: #9ca3af;
        }

        .notification-actions {
            display: flex;
            gap: 10px;
        }

        .notification-action-btn {
            background: none;
            border: none;
            color: #6b7280;
            cursor: pointer;
            font-size: 12px;
            display: flex;
            align-items: center;
            gap: 4px;
        }

        .notification-action-btn:hover {
            color: #4e73df;
        }

        /* Form Styles */
        .form-group {
            margin-bottom: 15px;
        }

        .form-row {
            display: flex;
            gap: 15px;
            margin-bottom: 15px;
        }

        .form-row .form-group {
            flex: 1;
            margin-bottom: 0;
        }

        label {
            display: block;
            margin-bottom: 6px;
            font-size: 14px;
            font-weight: 500;
            color: #4b5563;
        }

        .form-control {
            width: 100%;
            padding: 8px 12px;
            border: 1px solid #d1d5db;
            border-radius: 6px;
            font-size: 14px;
            transition: border-color 0.2s ease;
        }

        .form-control:focus {
            outline: none;
            border-color: #4e73df;
            box-shadow: 0 0 0 3px rgba(78, 115, 223, 0.1);
        }

        textarea.form-control {
            min-height: 100px;
            resize: vertical;
        }

        select.form-control {
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' fill='%236b7280' viewBox='0 0 16 16'%3E%3Cpath d='M7.247 11.14 2.451 5.658C1.885 5.013 2.345 4 3.204 4h9.592a1 1 0 0 1 .753 1.659l-4.796 5.48a1 1 0 0 1-1.506 0z'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 10px center;
            background-size: 16px 12px;
            padding-right: 30px;
        }

        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            z-index: 1000;
            justify-content: center;
            align-items: center;
        }

        .modal-content {
            background: #fff;
            border-radius: 8px;
            width: 90%;
            max-width: 600px;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        }

        .modal-header {
            padding: 15px 20px;
            border-bottom: 1px solid #eee;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .modal-title {
            font-size: 18px;
            font-weight: 600;
            color: #2d3748;
        }

        .modal-close {
            cursor: pointer;
            color: #6b7280;
        }

        .modal-close:hover {
            color: #4b5563;
        }

        .modal-body {
            padding: 20px;
        }

        .modal-footer {
            padding: 15px 20px;
            border-top: 1px solid #eee;
            display: flex;
            justify-content: flex-end;
            gap: 10px;
        }

        /* Tabs */
        .tabs {
            display: flex;
            border-bottom: 1px solid #eee;
            margin-bottom: 20px;
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
            flex-wrap: nowrap;
        }

        .tab {
            padding: 10px 20px;
            cursor: pointer;
            font-weight: 500;
            color: #6b7280;
            border-bottom: 2px solid transparent;
            transition: all 0.2s ease;
        }

        .tab.active {
            color: #4e73df;
            border-bottom-color: #4e73df;
        }

        .tab:hover:not(.active) {
            color: #4b5563;
        }

        /* Alert Styles */
        .alert {
            padding: 12px 15px;
            border-radius: 6px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
        }

        .alert-success {
            background-color: #d1fae5;
            color: #065f46;
            border: 1px solid #a7f3d0;
        }

        .alert-error {
            background-color: #fee2e2;
            color: #b91c1c;
            border: 1px solid #fecaca;
        }

        .alert-icon {
            margin-right: 10px;
            font-size: 20px;
        }

        /* Custom Date Range */
        .custom-date-range {
            display: none;
            margin-top: 10px;
            gap: 10px;
        }

        .custom-date-range.active {
            display: flex;
        }

        /* Responsive Adjustments */
        @media (max-width: 768px) {
            .content-area { padding: 12px; margin-left: 0; max-width: 100%; }
            .form-row { flex-direction: column; gap: 10px; }
            .notification-item { flex-direction: column; }
            .notification-icon { margin-right: 0; margin-bottom: 10px; }
            .notification-meta { flex-direction: column; gap: 8px; }
            .notification-actions { justify-content: flex-end; }
            .custom-date-range { flex-direction: column; }
            .card-header { flex-wrap: wrap; gap: 8px; }
        }
    </style>
</head>

<body>
    <!-- Content Area -->
    <div class="content-area">
        <div class="page-title">
            <span class="material-icons-round">notifications</span>
            <span>Notifications</span>
        </div>

        <!-- Display success/error messages -->
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success">
                <span class="material-icons-round alert-icon">check_circle</span>
                <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
            </div>
        <?php endif; ?>

        <!-- Notification Tabs -->
        <div class="card">
            <div class="card-body" style="padding-bottom: 0;">
                <div class="tabs">
                    <a href="?filter=all" class="tab <?php echo $filter === 'all' ? 'active' : ''; ?>">All Notifications</a>
                    <a href="?filter=unread" class="tab <?php echo $filter === 'unread' ? 'active' : ''; ?>">Unread</a>
                    <a href="?filter=system" class="tab <?php echo $filter === 'system' ? 'active' : ''; ?>">System</a>
                    <a href="?filter=promotional" class="tab <?php echo $filter === 'promotional' ? 'active' : ''; ?>">Promotional</a>
                    <a href="?filter=user_alerts" class="tab <?php echo $filter === 'user_alerts' ? 'active' : ''; ?>">User Alerts</a>
                </div>
            </div>
        </div>

        <!-- Notification Actions -->
        <div class="card">
            <div class="card-header">
                <div class="card-title">Notification Management</div>
                <button class="btn btn-primary" onclick="openModal('sendNotificationModal')">
                    <span class="material-icons-round">send</span>
                    Send Notification
                </button>
            </div>
            <div class="card-body">
                <form method="GET" action="">
                    <input type="hidden" name="filter" value="<?php echo $filter; ?>">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="notificationSearch">Search Notifications</label>
                            <input type="text" id="notificationSearch" name="search" class="form-control" 
                                   placeholder="Search notifications..." value="<?php echo htmlspecialchars($search); ?>">
                        </div>
                        <div class="form-group">
                            <label for="notificationDate">Date Range</label>
                            <select id="notificationDate" name="date_range" class="form-control">
                                <option value="">All Time</option>
                                <option value="today" <?php echo $dateRange === 'today' ? 'selected' : ''; ?>>Today</option>
                                <option value="week" <?php echo $dateRange === 'week' ? 'selected' : ''; ?>>This Week</option>
                                <option value="month" <?php echo $dateRange === 'month' ? 'selected' : ''; ?>>This Month</option>
                                <option value="custom" <?php echo $dateRange === 'custom' ? 'selected' : ''; ?>>Custom Range</option>
                            </select>
                            <div class="custom-date-range <?php echo $dateRange === 'custom' ? 'active' : ''; ?>">
                                <div class="form-group">
                                    <label for="startDate">Start Date</label>
                                    <input type="date" id="startDate" name="start_date" class="form-control" 
                                           value="<?php echo isset($_GET['start_date']) ? htmlspecialchars($_GET['start_date']) : ''; ?>">
                                </div>
                                <div class="form-group">
                                    <label for="endDate">End Date</label>
                                    <input type="date" id="endDate" name="end_date" class="form-control" 
                                           value="<?php echo isset($_GET['end_date']) ? htmlspecialchars($_GET['end_date']) : ''; ?>">
                                </div>
                            </div>
                        </div>
                    </div>
                    <div style="display: flex; justify-content: flex-end; gap: 10px;">
                        <a href="notifications.php" class="btn btn-outline">
                            <span class="material-icons-round">refresh</span>
                            Reset
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <span class="material-icons-round">filter_alt</span>
                            Apply Filters
                        </button>
                    </div>
                </form>
            </div>
        </div>





        <!-- Notifications List -->
        <div class="card">
            <div class="card-header">
                <div class="card-title">Recent Notifications</div>
                <form method="POST" action="">
                    <button type="submit" name="mark_all_read" class="btn btn-outline">
                        <span class="material-icons-round">done_all</span>
                        Mark All as Read
                    </button>
                </form>
            </div>
            <div class="card-body">
                <div class="notification-list">
                    <?php if (empty($notifications)): ?>
                        <div style="text-align: center; padding: 20px; color: #6b7280;">
                            <span class="material-icons-round" style="font-size: 48px; margin-bottom: 10px;">notifications_off</span>
                            <p>No notifications found</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($notifications as $notification): ?>
                            <div class="notification-item <?php echo $notification['is_read'] ? '' : 'unread'; ?>">
                                <div class="notification-icon">
                                    <?php 
                                        $icon = 'notifications';
                                        if ($notification['type'] === 'system') $icon = 'settings';
                                        if ($notification['type'] === 'promotional') $icon = 'campaign';
                                        if ($notification['type'] === 'user_alert') $icon = 'warning';
                                        if ($notification['priority'] === 'high') $icon = 'priority_high';
                                    ?>
                                    <span class="material-icons-round"><?php echo $icon; ?></span>
                                </div>
                                <div class="notification-content">
                                    <div class="notification-title"><?php echo htmlspecialchars($notification['title']); ?></div>
                                    <div class="notification-message">
                                        <?php echo htmlspecialchars($notification['message']); ?>
                                    </div>
                                    <div class="notification-meta">
                                        <span><?php echo date('M j, Y g:i A', strtotime($notification['created_at'])); ?></span>
                                        <div class="notification-actions">
                                            <form method="POST" action="" style="display: inline;">
                                                <input type="hidden" name="notification_id" value="<?php echo $notification['id']; ?>">
                                                <button type="submit" name="<?php echo $notification['is_read'] ? 'mark_as_unread' : 'mark_as_read'; ?>" class="notification-action-btn">
                                                    <span class="material-icons-round" style="font-size: 14px;">check</span>
                                                    <?php echo $notification['is_read'] ? 'Mark as Unread' : 'Mark as Read'; ?>
                                                </button>
                                            </form>
                                            <form method="POST" action="" style="display: inline;">
                                                <input type="hidden" name="notification_id" value="<?php echo $notification['id']; ?>">
                                                <button type="submit" name="delete_notification" class="notification-action-btn" onclick="return confirm('Are you sure you want to delete this notification?');">
                                                    <span class="material-icons-round" style="font-size: 14px;">delete</span>
                                                    Delete
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
            <div style="padding: 15px; display: flex; justify-content: center; border-top: 1px solid #eee;">
                <button class="btn btn-outline">
                    <span class="material-icons-round">expand_more</span>
                    Load More Notifications
                </button>
            </div>
        </div>
    </div>

    <!-- Send Notification Modal -->
    <div class="modal" id="sendNotificationModal">
        <div class="modal-content">
            <div class="modal-header">
                <div class="modal-title">Send New Notification</div>
                <div class="modal-close" onclick="closeModal('sendNotificationModal')">
                    <span class="material-icons-round">close</span>
                </div>
            </div>
            <form id="sendNotificationForm" method="POST" action="">
                <div class="modal-body">
                    <div class="form-group">
                        <label for="notificationType">Notification Type</label>
                        <select id="notificationType" name="type" class="form-control" required>
                            <option value="">Select Type</option>
                            <option value="system">System Notification</option>
                            <option value="promotional">Promotional</option>
                            <option value="user_alert">User Alert</option>
                            <option value="update">System Update</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="notificationRecipients">Recipients</label>
                        <select id="notificationRecipients" class="form-control" required>
                            <option value="">Select Recipients</option>
                            <option value="all">All Users</option>
                            <option value="verified">Verified Users Only</option>
                            <option value="active">Active Users</option>
                            <option value="specific">Specific Users</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="notificationTitle">Title</label>
                        <input type="text" id="notificationTitle" name="title" class="form-control" placeholder="Enter notification title" required>
                    </div>
                    <div class="form-group">
                        <label for="notificationMessage">Message</label>
                        <textarea id="notificationMessage" name="message" class="form-control" rows="4" placeholder="Enter notification message" required></textarea>
                    </div>
                    <div class="form-group">
                        <label for="notificationPriority">Priority</label>
                        <select id="notificationPriority" name="priority" class="form-control">
                            <option value="normal">Normal</option>
                            <option value="high">High</option>
                            <option value="urgent">Urgent</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>
                            <input type="checkbox" id="sendEmail" name="send_email" checked>
                            Also send as email
                        </label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline" onclick="closeModal('sendNotificationModal')">Cancel</button>
                    <button type="submit" name="send_notification" class="btn btn-primary">Send Notification</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Modal Functions
        function openModal(modalId) {
            document.getElementById(modalId).style.display = 'flex';
            document.body.style.overflow = 'hidden';
        }

        function closeModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
            document.body.style.overflow = 'auto';
        }

        // Tab Switching
        document.querySelectorAll('.tab').forEach(tab => {
            tab.addEventListener('click', function(e) {
                if (this.tagName === 'A') return; // Let links handle navigation
                
                // Remove active class from all tabs
                document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
                // Add active class to clicked tab
                this.classList.add('active');
            });
        });

        // Date Range Selector
        const dateRangeSelect = document.getElementById('notificationDate');
        const customDateRange = document.querySelector('.custom-date-range');
        
        if (dateRangeSelect) {
            dateRangeSelect.addEventListener('change', function() {
                if (this.value === 'custom') {
                    customDateRange.classList.add('active');
                } else {
                    customDateRange.classList.remove('active');
                }
            });
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            if (event.target.className === 'modal') {
                event.target.style.display = 'none';
                document.body.style.overflow = 'auto';
            }
        }
    </script>
</body>
</html>