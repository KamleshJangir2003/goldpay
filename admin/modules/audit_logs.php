<?php 
include '../templates/sidebar.php';
include '../templates/header.php';  
$pageTitle = "Audit Logs | Goldpay Admin";
$activePage = "Audit Logs";


?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?></title>
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons+Round" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Roboto', sans-serif;
        }

        .content-area {
            margin-left: 260px;
            padding: 20px;
            background: #f5f7fa;
            min-height: 100vh;
            max-width: calc(100vw - 260px);
            overflow-x: hidden;
        }

        .page-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 30px;
        }

        .title-container {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .page-title {
            font-size: 24px;
            color: #2d3748;
            font-weight: 600;
        }

        .card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 15px rgba(0,0,0,0.05);
            margin-bottom: 25px;
        }

        .card-header {
            padding: 18px 25px;
            border-bottom: 1px solid #eee;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .card-title {
            font-size: 18px;
            font-weight: 500;
            color: #2d3748;
        }

        .filter-section {
            display: flex;
            gap: 15px;
            padding: 20px;
            flex-wrap: wrap;
        }

        .filter-group {
            flex: 1;
            min-width: 200px;
        }

        .filter-label {
            display: block;
            margin-bottom: 8px;
            color: #4a5568;
            font-size: 14px;
        }

        .filter-input {
            width: 100%;
            padding: 10px 15px;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            font-size: 14px;
        }

        .btn-group {
            display: flex;
            gap: 10px;
            padding: 20px;
            border-top: 1px solid #eee;
            justify-content: flex-end;
        }

        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.2s;
        }

        .btn-primary {
            background: #4e73df;
            color: white;
        }

        .btn-secondary {
            background: #e2e8f0;
            color: #4a5568;
        }

        .btn-icon {
            font-size: 18px;
        }

        .logs-table {
            width: 100%;
            border-collapse: collapse;
            background: white;
        }

        .logs-table th, 
        .logs-table td {
            padding: 15px 20px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }

        .logs-table th {
            background: #f8fafc;
            color: #64748b;
            font-weight: 500;
        }

        .status-badge {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
        }

        .status-success { background: #dcfce7; color: #166534; }
        .status-warning { background: #fef9c3; color: #854d0e; }
        .status-danger { background: #fee2e2; color: #991b1b; }

        .action-btn {
            padding: 6px 12px;
            border: none;
            background: none;
            cursor: pointer;
            color: #64748b;
        }

        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.4);
            justify-content: center;
            align-items: center;
            z-index: 1000;
        }

        .modal-content {
            background: white;
            border-radius: 12px;
            width: 90%;
            max-width: 600px;
            max-height: 90vh;
            overflow-y: auto;
        }

        .modal-header {
            padding: 20px;
            border-bottom: 1px solid #eee;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .modal-body {
            padding: 20px;
        }

        .log-detail-row {
            margin-bottom: 15px;
            padding: 12px;
            background: #f8fafc;
            border-radius: 8px;
        }

        .log-detail-label {
            color: #64748b;
            font-size: 14px;
            margin-bottom: 5px;
        }

        .log-detail-value {
            color: #1e293b;
            font-weight: 500;
        }

        @media (max-width: 768px) {
            .content-area { margin-left: 0; padding: 12px; max-width: 100%; }
            .filter-group { flex: 100%; }
            .logs-table { min-width: 600px; }
        }
    </style>
</head>
<body>
    <div class="content-area">
        <div class="page-header">
            <div class="title-container">
                <span class="material-icons-round" style="color: #4e73df; font-size: 32px;">receipt_long</span>
                <h1 class="page-title">Audit Logs</h1>
            </div>
            <button class="btn btn-primary" onclick="exportLogs()">
                <span class="material-icons-round btn-icon">download</span>
                Export CSV
            </button>
        </div>

        <div class="card">
            <div class="card-header">
                <h2 class="card-title">Filter Logs</h2>
            </div>
            <div class="filter-section">
                <div class="filter-group">
                    <label class="filter-label">Event Type</label>
                    <select class="filter-input" id="eventType">
                        <option value="">All Events</option>
                        <option value="login">Login</option>
                        <option value="modification">Modification</option>
                        <option value="transaction">Transaction</option>
                        <option value="security">Security</option>
                    </select>
                </div>
                <div class="filter-group">
                    <label class="filter-label">Date Range</label>
                    <select class="filter-input" id="dateRange">
                        <option value="all">All Time</option>
                        <option value="today">Today</option>
                        <option value="week">Last 7 Days</option>
                        <option value="month">This Month</option>
                    </select>
                </div>
                <div class="filter-group">
                    <label class="filter-label">Status</label>
                    <select class="filter-input" id="statusFilter">
                        <option value="">All Statuses</option>
                        <option value="success">Success</option>
                        <option value="warning">Warning</option>
                        <option value="danger">Danger</option>
                    </select>
                </div>
            </div>
            <div class="btn-group">
                <button class="btn btn-secondary" onclick="resetFilters()">
                    <span class="material-icons-round btn-icon">restart_alt</span>
                    Reset
                </button>
                <button class="btn btn-primary" onclick="applyFilters()">
                    <span class="material-icons-round btn-icon">filter_alt</span>
                    Apply Filters
                </button>
            </div>
        </div>

        <div class="card" style="overflow-x:auto;">
            <table class="logs-table">
                <thead>
                    <tr>
                        <th>Timestamp</th>
                        <th>Event Type</th>
                        <th>User</th>
                        <th>Description</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="logsBody">
                    <!-- Sample Data -->
                    <tr>
                        <td>2023-12-01 14:30</td>
                        <td>Login</td>
                        <td>admin@example.com</td>
                        <td>Successful login from 192.168.1.1</td>
                        <td><span class="status-badge status-success">Success</span></td>
                        <td>
                            <button class="action-btn" onclick="viewLogDetails(1)">
                                <span class="material-icons-round">visibility</span>
                            </button>
                        </td>
                    </tr>
                    <tr>
                        <td>2023-12-01 15:45</td>
                        <td>Security</td>
                        <td>system</td>
                        <td>Failed login attempt from 103.216.82.1</td>
                        <td><span class="status-badge status-danger">Blocked</span></td>
                        <td>
                            <button class="action-btn" onclick="viewLogDetails(2)">
                                <span class="material-icons-round">visibility</span>
                            </button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div class="card" style="margin-top: 20px;">
            <div class="btn-group">
                <button class="btn btn-secondary" onclick="loadMore()">
                    <span class="material-icons-round btn-icon">expand_more</span>
                    Load More
                </button>
            </div>
        </div>
    </div>

    <!-- Log Details Modal -->
    <div class="modal" id="logModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Log Details</h3>
                <button class="action-btn" onclick="closeModal()">
                    <span class="material-icons-round">close</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="log-detail-row">
                    <div class="log-detail-label">Timestamp</div>
                    <div class="log-detail-value" id="detailTime">-</div>
                </div>
                <div class="log-detail-row">
                    <div class="log-detail-label">Event Type</div>
                    <div class="log-detail-value" id="detailType">-</div>
                </div>
                <div class="log-detail-row">
                    <div class="log-detail-label">User</div>
                    <div class="log-detail-value" id="detailUser">-</div>
                </div>
                <div class="log-detail-row">
                    <div class="log-detail-label">IP Address</div>
                    <div class="log-detail-value" id="detailIP">-</div>
                </div>
                <div class="log-detail-row">
                    <div class="log-detail-label">Description</div>
                    <div class="log-detail-value" id="detailDesc">-</div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Modal Functions
        function viewLogDetails(logId) {
            // Simulated data - replace with actual data fetch
            const sampleData = {
                1: {
                    time: '2023-12-01 14:30',
                    type: 'Login',
                    user: 'admin@example.com',
                    ip: '192.168.1.1',
                    desc: 'Successful login from 192.168.1.1'
                },
                2: {
                    time: '2023-12-01 15:45',
                    type: 'Security',
                    user: 'system',
                    ip: '103.216.82.1',
                    desc: 'Failed login attempt from 103.216.82.1'
                }
            };

            const data = sampleData[logId];
            if(data) {
                document.getElementById('detailTime').textContent = data.time;
                document.getElementById('detailType').textContent = data.type;
                document.getElementById('detailUser').textContent = data.user;
                document.getElementById('detailIP').textContent = data.ip;
                document.getElementById('detailDesc').textContent = data.desc;
                document.getElementById('logModal').style.display = 'flex';
            }
        }

        function closeModal() {
            document.getElementById('logModal').style.display = 'none';
        }

        // Filter Functions
        function applyFilters() {
            const eventType = document.getElementById('eventType').value;
            const dateRange = document.getElementById('dateRange').value;
            const statusFilter = document.getElementById('statusFilter').value;
            // Add actual filter logic here
            console.log('Applying filters:', { eventType, dateRange, statusFilter });
        }

        function resetFilters() {
            document.getElementById('eventType').value = '';
            document.getElementById('dateRange').value = 'all';
            document.getElementById('statusFilter').value = '';
            applyFilters();
        }

        // Export Function
        function exportLogs() {
            const csvContent = "data:text/csv;charset=utf-8," 
                + "Timestamp,Event Type,User,Description,Status\n"
                + "2023-12-01 14:30,Login,admin@example.com,Successful login from 192.168.1.1,Success\n"
                + "2023-12-01 15:45,Security,system,Failed login attempt from 103.216.82.1,Blocked\n";

            const encodedUri = encodeURI(csvContent);
            const link = document.createElement("a");
            link.setAttribute("href", encodedUri);
            link.setAttribute("download", "audit_logs.csv");
            document.body.appendChild(link);
            link.click();
        }

        // Pagination
        function loadMore() {
            // Simulate loading more data
            const tbody = document.getElementById('logsBody');
            const newRow = document.createElement('tr');
            newRow.innerHTML = `
                <td>2023-12-02 09:15</td>
                <td>Transaction</td>
                <td>user123</td>
                <td>Processed payment ₹1500</td>
                <td><span class="status-badge status-success">Success</span></td>
                <td>
                    <button class="action-btn" onclick="viewLogDetails(3)">
                        <span class="material-icons-round">visibility</span>
                    </button>
                </td>
            `;
            tbody.appendChild(newRow);
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            if (event.target.classList.contains('modal')) {
                closeModal();
            }
        }
    </script>
</body>
</html>
<?php include '../templates/footer.php'; ?>