<?php include '../templates/sidebar.php'; include '../templates/header.php'; ?>
<?php
require_once '../includes/config.php';
$conn = new mysqli($host, $username, $password, $dbname);
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

// Handle Add Campaign
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['addCampaign'])) {
    $name = $_POST['name'];
    $description = $_POST['description'];
    $type = $_POST['type'];
    $status = $_POST['status'];
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];
    $terms = $_POST['terms'];

    $imagePath = '';
    if (isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
        $targetDir = "uploads/";
        if (!is_dir($targetDir)) mkdir($targetDir);
        $imagePath = $targetDir . basename($_FILES['image']['name']);
        move_uploaded_file($_FILES['image']['tmp_name'], $imagePath);
    }

    $stmt = $conn->prepare("INSERT INTO campaigns (name, description, type, status, start_date, end_date, image, terms) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssssss", $name, $description, $type, $status, $start_date, $end_date, $imagePath, $terms);
    $stmt->execute();
    $stmt->close();

    header("Location: campaigns.php?success=1");
    exit;
}

// Get all campaigns
$result = $conn->query("SELECT * FROM campaigns ORDER BY id DESC");
$allCampaigns = [];
while ($row = $result->fetch_assoc()) {
    $allCampaigns[] = $row;
}
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
    <link
        href="https://fonts.googleapis.com/css2?family=Cedarville+Cursive&family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,800;1,900&family=Roboto:ital,wght@0,100..900;1,100..900&display=swap"
        rel="stylesheet">
        <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>

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

        /* Table Styles */
        .data-table-container {
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            overflow: hidden;
        }

        .table-header {
            padding: 15px 20px;
            border-bottom: 1px solid #eee;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .table-title {
            font-size: 16px;
            font-weight: 600;
            color: #2d3748;
        }

        .table-actions {
            display: flex;
            gap: 10px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th, td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }

        th {
            font-weight: 600;
            color: #4b5563;
            background-color: #f9fafb;
        }

        tr:hover {
            background-color: #f8f9fa;
        }

        /* Status Badges */
        .status-badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 500;
        }

        .status-active {
            background-color: #e6f7ee;
            color: #10b981;
        }

        .status-inactive {
            background-color: #fef3c7;
            color: #d97706;
        }

        .status-pending {
            background-color: #e0f2fe;
            color: #0ea5e9;
        }

        .status-expired {
            background-color: #fee2e2;
            color: #ef4444;
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

        /* Campaign Cards */
        .campaign-cards {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }

        .campaign-card {
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            overflow: hidden;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .campaign-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
        }

        .campaign-image {
            height: 160px;
            background-color: #e5e7eb;
            background-size: cover;
            background-position: center;
            position: relative;
        }

        .campaign-badge {
            position: absolute;
            top: 10px;
            right: 10px;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 500;
        }

        .campaign-content {
            padding: 15px;
        }

        .campaign-title {
            font-size: 16px;
            font-weight: 600;
            margin-bottom: 8px;
            color: #2d3748;
        }

        .campaign-description {
            font-size: 14px;
            color: #6b7280;
            margin-bottom: 12px;
            display: -webkit-box;
            -webkit-line-clamp: 3;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .campaign-meta {
            display: flex;
            justify-content: space-between;
            font-size: 12px;
            color: #9ca3af;
            margin-bottom: 15px;
        }

        .campaign-actions {
            display: flex;
            gap: 10px;
        }

        /* Responsive Adjustments */
        @media (max-width: 768px) {
            .content-area { margin-left: 0; padding: 12px; max-width: 100%; }
            .form-row { flex-direction: column; gap: 15px; }
            .campaign-cards { grid-template-columns: 1fr; }
            .table-header { flex-direction: column; align-items: flex-start; gap: 10px; }
            table { min-width: 500px; }
        }
    </style>
</head>

<body>
    <!-- Content Area -->
    <div class="content-area">
        <div class="page-title">
            <span class="material-icons-round">campaign</span>
            <span>Campaigns</span>
        </div>

        <!-- Campaign Filters -->
        <form method="GET" action="campaigns.php">
    <div class="card">
        <div class="card-header">
            <div class="card-title">Filter Campaigns</div>
        </div>
        <div class="card-body">
            <div class="form-row">
                <div class="form-group">
                    <label for="campaignStatus">Status</label>
                    <select id="campaignStatus" name="status" class="form-control">
                        <option value="">All Statuses</option>
                        <option value="active">Active</option>
                        <option value="upcoming">Upcoming</option>
                        <option value="expired">Expired</option>
                        <option value="draft">Draft</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="campaignType">Type</label>
                    <select id="campaignType" name="type" class="form-control">
                        <option value="">All Types</option>
                        <option value="referral">Referral</option>
                        <option value="promotional">Promotional</option>
                        <option value="seasonal">Seasonal</option>
                        <option value="bonus">Bonus</option>
                    </select>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label for="startDate">Start Date</label>
                    <input type="date" id="startDate" name="start_date" class="form-control">
                </div>
                <div class="form-group">
                    <label for="endDate">End Date</label>
                    <input type="date" id="endDate" name="end_date" class="form-control">
                </div>
            </div>
            <div style="display: flex; justify-content: flex-end; gap: 10px;">
                <a href="campaigns.php" class="btn btn-outline">
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


        <!-- Campaign Cards -->
        <div class="card">
            <div class="card-header">
                <div class="card-title">Active Campaigns</div>
                <button class="btn btn-primary" onclick="openModal('addCampaignModal')">
                    <span class="material-icons-round">add</span>
                    New Campaign
                </button>
            </div>
            <div class="card-body">
                <div class="campaign-cards">
                    <!-- Campaign 1 -->
                    

                   <!-- Campaign 2 -->
                   <div class="campaign-card">
                        <div class="campaign-image" style="background-image: url('https://via.placeholder.com/300x160/2')">
                            <span class="campaign-badge status-active">Active</span>
                        </div>
                        <div class="campaign-content">
                            <h3 class="campaign-title">Festival Special Deposit</h3>
                            <p class="campaign-description">
                                Get 2% extra on all deposits made during the festival season. Minimum deposit of ₹5,000 required.
                            </p>
                            <div class="campaign-meta">
                                <span>Sep 10 - Oct 15, 2023</span>
                                <span>892 participants</span>
                            </div>
                            <div class="campaign-actions">
                                <button class="btn btn-sm btn-outline">
                                    <span class="material-icons-round">visibility</span>
                                    View
                                </button>
                                <button class="btn btn-sm btn-outline">
                                    <span class="material-icons-round">edit</span>
                                    Edit
                                </button>
                                <button class="btn btn-sm btn-outline">
                                    <span class="material-icons-round">analytics</span>
                                    Stats
                                </button>
                            </div>
                        </div>
                    </div>
<!-- Campaign 3 -->
<div class="campaign-card">
                        <div class="campaign-image" style="background-image: url('https://via.placeholder.com/300x160/3')">
                            <span class="campaign-badge status-upcoming">Upcoming</span>
                        </div>
                        <div class="campaign-content">
                            <h3 class="campaign-title">New Year Mega Bonus</h3>
                            <p class="campaign-description">
                                Celebrate the new year with special bonuses up to 15% on deposits. Tiered bonuses based on deposit amount.
                            </p>
                            <div class="campaign-meta">
                                <span>Dec 25 - Jan 5, 2024</span>
                                <span>Starts soon</span>
                            </div>
                            <div class="campaign-actions">
                                <button class="btn btn-sm btn-outline">
                                    <span class="material-icons-round">visibility</span>
                                    View
                                </button>
                                <button class="btn btn-sm btn-outline">
                                    <span class="material-icons-round">edit</span>
                                    Edit
                                </button>
                                <button class="btn btn-sm btn-outline">
                                    <span class="material-icons-round">analytics</span>
                                    Stats
                                </button>
                            </div>
                        </div>
                    </div>


                    <!-- Campaign 3 -->
                    <?php
$formSubmitted = false;

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['addCampaign'])) {
    $formSubmitted = true;

    // process form and insert into database...
    // (assuming insertion is successful)
}

// Fetch active campaigns
$result = $conn->query("SELECT * FROM campaigns WHERE status = 'active'");

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        // render campaign card
    }
} else {
    // Only show this message if form was submitted and no campaigns found
    if ($formSubmitted) {
        echo '<p style="padding: 20px;">No active campaigns found.</p>';
    }
}
?>



                </div>
            </div>
        </div>

        <!-- Campaigns Table -->
        <div class="data-table-container" style="overflow-x:auto;">
            <div class="table-header">
                <div class="table-title">All Campaigns</div>
                <div class="table-actions">
                <button class="btn btn-outline" id="exportCampaignsBtn">
    <span class="material-icons-round">download</span>
    Export
</button>

                </div>
            </div>
            <table class="table" id="campaignsTable">

    <thead>
    <thead>
                    <tr>
                        <th>Campaign ID</th>
                        <th>Name</th>
                        <th>Type</th>
                        <th>Status</th>
                        <th>Start Date</th>
                        <th>End Date</th>
                      
                    </tr>
                </thead>
    <tbody id="campaigns-table-body">
<?php foreach ($allCampaigns as $campaign): ?>
    <tr>
        <td><?= $campaign['id'] ?></td>
        <td><?= htmlspecialchars($campaign['name']) ?></td>
        <td><?= htmlspecialchars($campaign['type']) ?></td>
        <td><?= htmlspecialchars($campaign['status']) ?></td>
        <td><?= $campaign['start_date'] ?></td>
        <td><?= $campaign['end_date'] ?></td>
    </tr>
<?php endforeach; ?>
</tbody>

    </thead>
    <tbody id="campaigns-table-body">
        <!-- Filtered rows will appear here -->
    </tbody>
</table>

            <div style="padding: 15px; display: flex; justify-content: space-between; align-items: center; border-top: 1px solid #eee;">
                <div>Showing 1 to 5 of 12 entries</div>
                <div style="display: flex; gap: 5px;">
                    <button class="btn btn-sm btn-outline" disabled>
                        <span class="material-icons-round">chevron_left</span>
                    </button>
                    <button class="btn btn-sm btn-outline active">1</button>
                    <button class="btn btn-sm btn-outline">2</button>
                    <button class="btn btn-sm btn-outline">3</button>
                    <button class="btn btn-sm btn-outline">
                        <span class="material-icons-round">chevron_right</span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Campaign Modal -->
    <div class="modal" id="addCampaignModal">
        <div class="modal-content">
            <div class="modal-header">
                <div class="modal-title">Create New Campaign</div>
                <div class="modal-close" onclick="closeModal('addCampaignModal')">
                    <span class="material-icons-round">close</span>
                </div>
            </div>
            <div class="modal-body">
            <form id="addCampaignForm" method="POST" action="campaigns.php" enctype="multipart/form-data">
    <input type="hidden" name="addCampaign" value="1">

    <div class="form-group">
        <label for="campaignName">Campaign Name</label>
        <input type="text" id="campaignName" name="name" class="form-control" required>
    </div>

    <div class="form-group">
        <label for="campaignDescription">Description</label>
        <textarea id="campaignDescription" name="description" class="form-control" rows="3" required></textarea>
    </div>

    <div class="form-row">
        <div class="form-group">
            <label for="campaignTypeModal">Type</label>
            <select id="campaignTypeModal" name="type" class="form-control" required>
                <option value="">Select Type</option>
                <option value="referral">Referral</option>
                <option value="promotional">Promotional</option>
                <option value="seasonal">Seasonal</option>
                <option value="bonus">Bonus</option>
            </select>
        </div>

        <div class="form-group">
            <label for="campaignStatusModal">Status</label>
            <select id="campaignStatusModal" name="status" class="form-control" required>
                <option value="draft">Draft</option>
                <option value="active">Active</option>
                <option value="upcoming">Upcoming</option>
            </select>
        </div>
    </div>

    <div class="form-row">
        <div class="form-group">
            <label for="startDateModal">Start Date</label>
            <input type="date" id="startDateModal" name="start_date" class="form-control" required>
        </div>
        <div class="form-group">
            <label for="endDateModal">End Date</label>
            <input type="date" id="endDateModal" name="end_date" class="form-control" required>
        </div>
    </div>

    <div class="form-group">
        <label for="campaignImage">Campaign Image</label>
        <input type="file" id="campaignImage" name="image" class="form-control">
    </div>

    <div class="form-group">
        <label for="campaignTerms">Terms & Conditions</label>
        <textarea id="campaignTerms" name="terms" class="form-control" rows="3" required></textarea>
    </div>

    
</form>

              <?php
ob_start(); // Start output buffering

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['addCampaign'])) {
    $name = $_POST['name'];
    $description = $_POST['description'];
    $type = $_POST['type'];
    $status = $_POST['status'];
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];
    $terms = $_POST['terms'];

    // Handle image upload
    $image_path = '';
    if (!empty($_FILES['image']['name'])) {
        $target_dir = "uploads/campaigns/";
        if (!is_dir($target_dir)) {
            mkdir($target_dir, 0777, true);
        }
        $image_path = $target_dir . basename($_FILES["image"]["name"]);
        move_uploaded_file($_FILES["image"]["tmp_name"], $image_path);
    }

    $conn = new mysqli($host, $username, $password, $dbname);
    $stmt = $conn->prepare("INSERT INTO campaigns (name, description, type, status, start_date, end_date, image, terms) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssssss", $name, $description, $type, $status, $start_date, $end_date, $image_path, $terms);
    $stmt->execute();

    // Redirect before any output
    header("Location: campaigns.php?success=1");
    exit();
}
?>


            </div>
            <div class="modal-footer">
                <button class="btn btn-outline" onclick="closeModal('addCampaignModal')">Cancel</button>
                <button class="btn btn-primary" type="submit" form="addCampaignForm">Create Campaign</button>

            </div>
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

        // Form Submission
        function submitCampaignForm() {
            // Simulate form submission
            setTimeout(() => {
                closeModal('addCampaignModal');
                alert('Campaign created successfully!');
                document.getElementById('addCampaignForm').reset();
            }, 1000);
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            if (event.target.className === 'modal') {
                event.target.style.display = 'none';
                document.body.style.overflow = 'auto';
            }
        }
    </script>
    <script>
document.querySelector('.btn-primary').addEventListener('click', function () {
    const status = document.getElementById('campaignStatus').value;
    const type = document.getElementById('campaignType').value;
    const startDate = document.getElementById('startDate').value;
    const endDate = document.getElementById('endDate').value;

    fetch(`campaigns.php?filter=1&status=${status}&type=${type}&startDate=${startDate}&endDate=${endDate}`)
        .then(res => res.json())
        .then(data => {
            console.log('Filtered Campaigns:', data);

            const tbody = document.getElementById('campaigns-table-body');
            tbody.innerHTML = '';

            if (data.length === 0) {
                tbody.innerHTML = '<tr><td colspan="6">No campaigns found</td></tr>';
                return;
            }

            data.forEach(c => {
                const row = `
                    <tr>
                        <td>${c.id}</td>
                        <td>${c.name}</td>
                        <td>${c.type}</td>
                        <td>${c.status}</td>
                        <td>${c.start_date}</td>
                        <td>${c.end_date}</td>
                    </tr>`;
                tbody.innerHTML += row;
            });
        })
        .catch(err => console.error('Error:', err));
});
</script>
<script>
document.getElementById('exportCampaignsBtn').addEventListener('click', function () {
    const table = document.getElementById('campaignsTable');

    html2canvas(table).then(canvas => {
        const imgData = canvas.toDataURL('image/png');
        const { jsPDF } = window.jspdf;
        const pdf = new jsPDF('p', 'mm', 'a4');

        const pageWidth = pdf.internal.pageSize.getWidth();
        const imgWidth = pageWidth - 20; // 10mm margin
        const imgHeight = (canvas.height * imgWidth) / canvas.width;

        pdf.addImage(imgData, 'PNG', 10, 10, imgWidth, imgHeight);
        pdf.save("campaigns-list.pdf");
    });
});
</script>


</body>

</html>
<?php include '../templates/footer.php'; ?>