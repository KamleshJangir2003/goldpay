<?php
include '../templates/sidebar.php';
include '../templates/header.php';
require_once '../includes/config.php';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    
    try {
        if (isset($_POST['action'])) {
            switch ($_POST['action']) {
                case 'save_general':
                    saveGeneralSettings($pdo);
                    break;
                case 'save_email':
                    saveEmailSettings($pdo);
                    break;
                case 'save_security':
                    saveSecuritySettings($pdo);
                    break;
                case 'save_notifications':
                    saveNotificationSettings($pdo);
                    break;
                case 'save_payment':
                    savePaymentSettings($pdo);
                    break;
                case 'save_maintenance':
                    saveMaintenanceSettings($pdo);
                    break;
                case 'test_email':
                    testEmailSettings();
                    break;
                default:
                    echo json_encode(['success' => false, 'message' => 'Invalid action']);
            }
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

// Function to save general settings
function saveGeneralSettings($pdo) {
    $settings = [
        'company_name' => $_POST['company_name'],
        'company_email' => $_POST['company_email'],
        'company_phone' => $_POST['company_phone'],
        'company_website' => $_POST['company_website'],
        'company_address' => $_POST['company_address'],
        'timezone' => $_POST['timezone'],
        'default_language' => $_POST['default_language'],
        'date_format' => $_POST['date_format'],
        'time_format' => $_POST['time_format'],
        'currency' => $_POST['currency'],
        'maintenance_mode' => isset($_POST['maintenance_mode']) ? 1 : 0,
        'user_registration' => isset($_POST['user_registration']) ? 1 : 0,
        'email_verification' => isset($_POST['email_verification']) ? 1 : 0,
        'kyc_verification' => isset($_POST['kyc_verification']) ? 1 : 0,
        'session_timeout' => $_POST['session_timeout']
    ];
    
    $pdo->beginTransaction();
    
    try {
        foreach ($settings as $key => $value) {
            $stmt = $pdo->prepare("INSERT INTO settings (setting_group, setting_key, setting_value) 
                                   VALUES ('general', ?, ?)
                                   ON DUPLICATE KEY UPDATE setting_value = ?");
            $stmt->execute([$key, $value, $value]);
        }
        
        $pdo->commit();
        echo json_encode(['success' => true, 'message' => 'General settings saved successfully']);
    } catch (Exception $e) {
        $pdo->rollBack();
        throw $e;
    }
}

// Function to save email settings
function saveEmailSettings($pdo) {
    $settings = [
        'smtp_host' => $_POST['smtp_host'],
        'smtp_port' => $_POST['smtp_port'],
        'smtp_username' => $_POST['smtp_username'],
        'smtp_password' => $_POST['smtp_password'],
        'smtp_encryption' => $_POST['smtp_encryption'],
        'from_email' => $_POST['from_email'],
        'from_name' => $_POST['from_name'],
        'welcome_email_template' => $_POST['welcome_email_template'],
        'transaction_email_template' => $_POST['transaction_email_template'],
        'notification_email_template' => $_POST['notification_email_template']
    ];
    
    $pdo->beginTransaction();
    
    try {
        foreach ($settings as $key => $value) {
            // Don't update password if empty (to keep existing password)
            if ($key === 'smtp_password' && empty($value)) {
                continue;
            }
            
            $stmt = $pdo->prepare("INSERT INTO settings (setting_group, setting_key, setting_value) 
                                   VALUES ('email', ?, ?)
                                   ON DUPLICATE KEY UPDATE setting_value = ?");
            $stmt->execute([$key, $value, $value]);
        }
        
        $pdo->commit();
        echo json_encode(['success' => true, 'message' => 'Email settings saved successfully']);
    } catch (Exception $e) {
        $pdo->rollBack();
        throw $e;
    }
}

// Function to save security settings
function saveSecuritySettings($pdo) {
    $settings = [
        'login_attempts' => $_POST['login_attempts'],
        'login_block_time' => $_POST['login_block_time'],
        'password_strength' => $_POST['password_strength'],
        '2fa_enabled' => isset($_POST['2fa_enabled']) ? 1 : 0,
        'ip_whitelist' => $_POST['ip_whitelist'],
        'session_fixation' => isset($_POST['session_fixation']) ? 1 : 0,
        'cookie_secure' => isset($_POST['cookie_secure']) ? 1 : 0,
        'cookie_httponly' => isset($_POST['cookie_httponly']) ? 1 : 0
    ];
    
    $pdo->beginTransaction();
    
    try {
        foreach ($settings as $key => $value) {
            $stmt = $pdo->prepare("INSERT INTO settings (setting_group, setting_key, setting_value) 
                                   VALUES ('security', ?, ?)
                                   ON DUPLICATE KEY UPDATE setting_value = ?");
            $stmt->execute([$key, $value, $value]);
        }
        
        $pdo->commit();
        echo json_encode(['success' => true, 'message' => 'Security settings saved successfully']);
    } catch (Exception $e) {
        $pdo->rollBack();
        throw $e;
    }
}

// Function to save notification settings
function saveNotificationSettings($pdo) {
    $settings = [
        'email_enabled' => isset($_POST['email_enabled']) ? 1 : 0,
        'sms_enabled' => isset($_POST['sms_enabled']) ? 1 : 0,
        'push_enabled' => isset($_POST['push_enabled']) ? 1 : 0,
        'deposit_notify' => isset($_POST['deposit_notify']) ? 1 : 0,
        'withdrawal_notify' => isset($_POST['withdrawal_notify']) ? 1 : 0,
        'login_notify' => isset($_POST['login_notify']) ? 1 : 0,
        'admin_notify_email' => $_POST['admin_notify_email']
    ];
    
    $pdo->beginTransaction();
    
    try {
        foreach ($settings as $key => $value) {
            $stmt = $pdo->prepare("INSERT INTO settings (setting_group, setting_key, setting_value) 
                                   VALUES ('notifications', ?, ?)
                                   ON DUPLICATE KEY UPDATE setting_value = ?");
            $stmt->execute([$key, $value, $value]);
        }
        
        $pdo->commit();
        echo json_encode(['success' => true, 'message' => 'Notification settings saved successfully']);
    } catch (Exception $e) {
        $pdo->rollBack();
        throw $e;
    }
}

// Function to save payment settings
function savePaymentSettings($pdo) {
    $settings = [
        'payment_gateway' => $_POST['payment_gateway'],
        'stripe_publishable_key' => $_POST['stripe_publishable_key'],
        'stripe_secret_key' => $_POST['stripe_secret_key'],
        'paypal_client_id' => $_POST['paypal_client_id'],
        'paypal_secret' => $_POST['paypal_secret'],
        'min_deposit' => $_POST['min_deposit'],
        'max_deposit' => $_POST['max_deposit'],
        'min_withdrawal' => $_POST['min_withdrawal'],
        'withdrawal_fee' => $_POST['withdrawal_fee'],
        'withdrawal_fee_type' => $_POST['withdrawal_fee_type'],
        'deposit_approval' => isset($_POST['deposit_approval']) ? 1 : 0
    ];
    
    $pdo->beginTransaction();
    
    try {
        foreach ($settings as $key => $value) {
            $stmt = $pdo->prepare("INSERT INTO settings (setting_group, setting_key, setting_value) 
                                   VALUES ('payment', ?, ?)
                                   ON DUPLICATE KEY UPDATE setting_value = ?");
            $stmt->execute([$key, $value, $value]);
        }
        
        $pdo->commit();
        echo json_encode(['success' => true, 'message' => 'Payment settings saved successfully']);
    } catch (Exception $e) {
        $pdo->rollBack();
        throw $e;
    }
}

// Function to save maintenance settings
function saveMaintenanceSettings($pdo) {
    $settings = [
        'maintenance_mode' => isset($_POST['maintenance_mode']) ? 1 : 0,
        'maintenance_message' => $_POST['maintenance_message'],
        'maintenance_start' => $_POST['maintenance_start'],
        'maintenance_end' => $_POST['maintenance_end'],
        'allowed_ips' => $_POST['allowed_ips']
    ];
    
    $pdo->beginTransaction();
    
    try {
        foreach ($settings as $key => $value) {
            $stmt = $pdo->prepare("INSERT INTO settings (setting_group, setting_key, setting_value) 
                                   VALUES ('maintenance', ?, ?)
                                   ON DUPLICATE KEY UPDATE setting_value = ?");
            $stmt->execute([$key, $value, $value]);
        }
        
        $pdo->commit();
        echo json_encode(['success' => true, 'message' => 'Maintenance settings saved successfully']);
    } catch (Exception $e) {
        $pdo->rollBack();
        throw $e;
    }
}

// Function to test email settings (simulated)
function testEmailSettings() {
    $email = $_POST['test_email'];
    $type = $_POST['test_email_type'];
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new Exception('Please enter a valid email address');
    }
    
    // In a real application, you would actually send a test email here
    // This is just a simulation
    sleep(1); // Simulate delay
    
    echo json_encode([
        'success' => true, 
        'message' => "Test $type email sent to $email (simulated)"
    ]);
}

// Get all settings from database
function getSettings($pdo) {
    $stmt = $pdo->query("SELECT setting_group, setting_key, setting_value FROM settings");
    $settings = [];
    
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $settings[$row['setting_group']][$row['setting_key']] = $row['setting_value'];
    }
    
    return $settings;
}

$settings = getSettings($pdo);

$pageTitle = "Settings | Dollario Admin";
$activePage = "Settings";
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>
        <?php echo $pageTitle; ?>
    </title>
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons+Round" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Roboto:wght@300;400;500;700&display=swap"
        rel="stylesheet">
    <style>
        .toast {
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 15px 20px;
            background-color: #4CAF50;
            color: white;
            border-radius: 4px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            z-index: 1001;
            display: flex;
            align-items: center;
            transform: translateX(150%);
            transition: transform 0.3s ease;
        }

        .toast.show {
            transform: translateX(0);
        }

        .toast.error {
            background-color: #f44336;
        }

        .toast .material-icons-round {
            margin-right: 10px;
        }
   
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

        /* Tabs */
        .tabs {
            display: flex;
            border-bottom: 1px solid #eee;
            margin-bottom: 20px;
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

        /* Settings Sections */
        .settings-section {
            margin-bottom: 30px;
        }

        .section-title {
            font-size: 16px;
            font-weight: 600;
            margin-bottom: 15px;
            color: #2d3748;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        /* Toggle Switch */
        .switch {
            position: relative;
            display: inline-block;
            width: 50px;
            height: 24px;
        }

        .switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }

        .slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: #d1d5db;
            transition: .4s;
            border-radius: 24px;
        }

        .slider:before {
            position: absolute;
            content: "";
            height: 16px;
            width: 16px;
            left: 4px;
            bottom: 4px;
            background-color: white;
            transition: .4s;
            border-radius: 50%;
        }

        input:checked+.slider {
            background-color: #4e73df;
        }

        input:checked+.slider:before {
            transform: translateX(26px);
        }

        /* Setting Item */
        .setting-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 0;
            border-bottom: 1px solid #eee;
        }

        .setting-info {
            flex: 1;
        }

        .setting-name {
            font-weight: 500;
            margin-bottom: 5px;
            color: #2d3748;
        }

        .setting-description {
            font-size: 13px;
            color: #6b7280;
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
            max-width: 500px;
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

        /* Responsive Adjustments */
        @media (max-width: 768px) {
            .content-area { padding: 12px; margin-left: 0; max-width: 100%; }
            .tabs { overflow-x: auto; padding-bottom: 5px; }
            .form-row { flex-direction: column; gap: 0; }
            .setting-item { flex-direction: column; align-items: flex-start; gap: 10px; }
        }

        /* Tab Content */
        .tab-content {
            display: none;
        }

        .tab-content.active {
            display: block;
        }

        /* Additional form styles */
        .radio-group {
            display: flex;
            gap: 15px;
            margin-bottom: 15px;
        }

        .radio-option {
            display: flex;
            align-items: center;
            gap: 5px;
        }

        /* IP list styling */
        .ip-list {
            font-family: monospace;
            font-size: 13px;
            height: 100px;
        }

        /* Maintenance message box */
        #maintenanceMessage {
            min-height: 120px;
        }
    </style>
</head>

<body>
    <!-- Content Area -->
    <div class="content-area">
        <div class="page-title">
            <span class="material-icons-round">settings</span>
            <span>Settings</span>
        </div>

        <!-- Settings Tabs -->
        <div class="card">
            <div class="card-body" style="padding-bottom: 0;">
                <div class="tabs">
                    <div class="tab active" data-tab="general">General</div>
                    <div class="tab" data-tab="security">Security</div>
                    <div class="tab" data-tab="notifications">Notifications</div>
                    <div class="tab" data-tab="payment">Payment</div>
                    <div class="tab" data-tab="maintenance">Maintenance</div>
                </div>
            </div>
        </div>

        <!-- General Settings -->
        <form id="generalSettingsForm" class="tab-content active" data-tab="general">

            <div class="card">
                <div class="card-header">
                    <div class="card-title">General Settings</div>
                </div>
                <div class="card-body">
                    <div class="settings-section">
                        <div class="section-title">
                            <span class="material-icons-round">business</span>
                            <span>Company Information</span>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="companyName">Company Name</label>
                                <input type="text" id="companyName" name="company_name" class="form-control"
                                    value="<?= htmlspecialchars($settings['general']['company_name'] ?? '') ?>">
                            </div>
                            <div class="form-group">
                                <label for="companyEmail">Contact Email</label>
                                <input type="email" id="companyEmail" name="company_email" class="form-control"
                                    value="<?= htmlspecialchars($settings['general']['company_email'] ?? '') ?>">
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="companyPhone">Contact Phone</label>
                                <input type="tel" id="companyPhone" name="company_phone" class="form-control"
                                    value="<?= htmlspecialchars($settings['general']['company_phone'] ?? '') ?>">
                            </div>
                            <div class="form-group">
                                <label for="companyWebsite">Website</label>
                                <input type="url" id="companyWebsite" name="company_website" class="form-control"
                                    value="<?= htmlspecialchars($settings['general']['company_website'] ?? '') ?>">
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="companyAddress">Address</label>
                            <textarea id="companyAddress" name="company_address" class="form-control"><?= 
                                    htmlspecialchars($settings['general']['company_address'] ?? '') ?></textarea>
                        </div>
                    </div>

                    <div class="settings-section">
                        <div class="section-title">
                            <span class="material-icons-round">language</span>
                            <span>Localization</span>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="timezone">Timezone</label>
                                <select id="timezone" name="timezone" class="form-control">
                                    <option value="Asia/Kolkata" <?=($settings['general']['timezone'] ?? ''
                                        )==='Asia/Kolkata' ? 'selected' : '' ?>>(UTC+5:30) Asia/Kolkata</option>
                                    <option value="UTC" <?=($settings['general']['timezone'] ?? '' )==='UTC'
                                        ? 'selected' : '' ?>>(UTC) Coordinated Universal Time</option>
                                    <!-- More timezone options would be here -->
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="defaultLanguage">Default Language</label>
                                <select id="defaultLanguage" name="default_language" class="form-control">
                                    <option value="en" <?=($settings['general']['default_language'] ?? '' )==='en'
                                        ? 'selected' : '' ?>>English</option>
                                    <option value="hi" <?=($settings['general']['default_language'] ?? '' )==='hi'
                                        ? 'selected' : '' ?>>Hindi</option>
                                    <option value="mr" <?=($settings['general']['default_language'] ?? '' )==='mr'
                                        ? 'selected' : '' ?>>Marathi</option>
                                    <!-- More language options would be here -->
                                </select>
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="dateFormat">Date Format</label>
                                <select id="dateFormat" name="date_format" class="form-control">
                                    <option value="d-m-Y" <?=($settings['general']['date_format'] ?? '' )==='d-m-Y'
                                        ? 'selected' : '' ?>>DD-MM-YYYY</option>
                                    <option value="m-d-Y" <?=($settings['general']['date_format'] ?? '' )==='m-d-Y'
                                        ? 'selected' : '' ?>>MM-DD-YYYY</option>
                                    <option value="Y-m-d" <?=($settings['general']['date_format'] ?? '' )==='Y-m-d'
                                        ? 'selected' : '' ?>>YYYY-MM-DD</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="timeFormat">Time Format</label>
                                <select id="timeFormat" name="time_format" class="form-control">
                                    <option value="12" <?=($settings['general']['time_format'] ?? '' )==='12'
                                        ? 'selected' : '' ?>>12-hour</option>
                                    <option value="24" <?=($settings['general']['time_format'] ?? '' )==='24'
                                        ? 'selected' : '' ?>>24-hour</option>
                                </select>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="currency">Default Currency</label>
                            <select id="currency" name="currency" class="form-control">
                                <option value="INR" <?=($settings['general']['currency'] ?? '' )==='INR' ? 'selected'
                                    : '' ?>>Indian Rupee (₹)</option>
                                <option value="USD" <?=($settings['general']['currency'] ?? '' )==='USD' ? 'selected'
                                    : '' ?>>US Dollar ($)</option>
                                <option value="EUR" <?=($settings['general']['currency'] ?? '' )==='EUR' ? 'selected'
                                    : '' ?>>Euro (€)</option>
                            </select>
                        </div>
                    </div>

                    <div class="settings-section">
                        <div class="section-title">
                            <span class="material-icons-round">tune</span>
                            <span>System Preferences</span>
                        </div>
                        <div class="setting-item">
                            <div class="setting-info">
                                <div class="setting-name">Maintenance Mode</div>
                                <div class="setting-description">When enabled, only admins can access the system</div>
                            </div>
                            <label class="switch">
                                <input type="checkbox" name="maintenance_mode"
                                    <?=($settings['general']['maintenance_mode'] ?? '0' )==='1' ? 'checked' : '' ?>>
                                <span class="slider round"></span>
                            </label>
                        </div>
                        <div class="setting-item">
                            <div class="setting-info">
                                <div class="setting-name">User Registration</div>
                                <div class="setting-description">Allow new users to register accounts</div>
                            </div>
                            <label class="switch">
                                <input type="checkbox" name="user_registration"
                                    <?=($settings['general']['user_registration'] ?? '0' )==='1' ? 'checked' : '' ?>>
                                <span class="slider round"></span>
                            </label>
                        </div>
                        <div class="setting-item">
                            <div class="setting-info">
                                <div class="setting-name">Email Verification</div>
                                <div class="setting-description">Require users to verify their email address</div>
                            </div>
                            <label class="switch">
                                <input type="checkbox" name="email_verification"
                                    <?=($settings['general']['email_verification'] ?? '0' )==='1' ? 'checked' : '' ?>>
                                <span class="slider round"></span>
                            </label>
                        </div>
                        <div class="setting-item">
                            <div class="setting-info">
                                <div class="setting-name">KYC Verification</div>
                                <div class="setting-description">Require KYC for withdrawals</div>
                            </div>
                            <label class="switch">
                                <input type="checkbox" name="kyc_verification"
                                    <?=($settings['general']['kyc_verification'] ?? '0' )==='1' ? 'checked' : '' ?>>
                                <span class="slider round"></span>
                            </label>
                        </div>
                        <div class="form-group">
                            <label for="sessionTimeout">Session Timeout (minutes)</label>
                            <input type="number" id="sessionTimeout" name="session_timeout" class="form-control"
                                value="<?= htmlspecialchars($settings['general']['session_timeout'] ?? '30') ?>"
                                min="1">
                        </div>
                    </div>
                </div>
                <div style="padding: 15px; border-top: 1px solid #eee;">
                    <button type="submit" class="btn btn-primary">
                        <span class="material-icons-round">save</span>
                        Save General Settings
                    </button>
                </div>
            </div>
        </form>



        <!-- Security Settings -->
        <form id="securitySettingsForm" class="tab-content" data-tab="security">
            <div class="card">
                <div class="card-header">
                    <div class="card-title">Security Settings</div>
                </div>
                <div class="card-body">
                    <div class="settings-section">
                        <div class="section-title">
                            <span class="material-icons-round">security</span>
                            <span>Login Security</span>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="loginAttempts">Max Login Attempts</label>
                                <input type="number" id="loginAttempts" name="login_attempts" class="form-control"
                                    value="<?= htmlspecialchars($settings['security']['login_attempts'] ?? '5') ?>"
                                    min="1">
                            </div>
                            <div class="form-group">
                                <label for="loginBlockTime">Block Time (minutes)</label>
                                <input type="number" id="loginBlockTime" name="login_block_time" class="form-control"
                                    value="<?= htmlspecialchars($settings['security']['login_block_time'] ?? '30') ?>"
                                    min="1">
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="passwordStrength">Password Strength</label>
                            <select id="passwordStrength" name="password_strength" class="form-control">
                                <option value="low" <?=($settings['security']['password_strength'] ?? '' )==='low'
                                    ? 'selected' : '' ?>>Low (6+ characters)</option>
                                <option value="medium" <?=($settings['security']['password_strength'] ?? '' )==='medium'
                                    ? 'selected' : '' ?>>Medium (8+ chars with numbers)</option>
                                <option value="high" <?=($settings['security']['password_strength'] ?? '' )==='high'
                                    ? 'selected' : '' ?>>High (10+ chars with numbers and symbols)</option>
                            </select>
                        </div>
                        <div class="setting-item">
                            <div class="setting-info">
                                <div class="setting-name">Two-Factor Authentication</div>
                                <div class="setting-description">Require users to enable 2FA for account security</div>
                            </div>
                            <label class="switch">
                                <input type="checkbox" name="2fa_enabled" <?=($settings['security']['2fa_enabled']
                                    ?? '0' )==='1' ? 'checked' : '' ?>>
                                <span class="slider round"></span>
                            </label>
                        </div>
                    </div>

                    <div class="settings-section">
                        <div class="section-title">
                            <span class="material-icons-round">admin_panel_settings</span>
                            <span>Advanced Security</span>
                        </div>
                        <div class="form-group">
                            <label for="ipWhitelist">IP Whitelist (comma separated)</label>
                            <textarea id="ipWhitelist" name="ip_whitelist" class="form-control ip-list"><?= 
                                htmlspecialchars($settings['security']['ip_whitelist'] ?? '') ?></textarea>
                        </div>
                        <div class="setting-item">
                            <div class="setting-info">
                                <div class="setting-name">Prevent Session Fixation</div>
                                <div class="setting-description">Regenerate session ID after login</div>
                            </div>
                            <label class="switch">
                                <input type="checkbox" name="session_fixation"
                                    <?=($settings['security']['session_fixation'] ?? '0' )==='1' ? 'checked' : '' ?>>
                                <span class="slider round"></span>
                            </label>
                        </div>
                        <div class="setting-item">
                            <div class="setting-info">
                                <div class="setting-name">Secure Cookies</div>
                                <div class="setting-description">Only send cookies over HTTPS</div>
                            </div>
                            <label class="switch">
                                <input type="checkbox" name="cookie_secure" <?=($settings['security']['cookie_secure']
                                    ?? '0' )==='1' ? 'checked' : '' ?>>
                                <span class="slider round"></span>
                            </label>
                        </div>
                        <div class="setting-item">
                            <div class="setting-info">
                                <div class="setting-name">HTTP Only Cookies</div>
                                <div class="setting-description">Prevent JavaScript access to cookies</div>
                            </div>
                            <label class="switch">
                                <input type="checkbox" name="cookie_httponly"
                                    <?=($settings['security']['cookie_httponly'] ?? '0' )==='1' ? 'checked' : '' ?>>
                                <span class="slider round"></span>
                            </label>
                        </div>
                    </div>
                </div>
                <div style="padding: 15px; border-top: 1px solid #eee;">
                    <button type="submit" class="btn btn-primary">
                        <span class="material-icons-round">save</span>
                        Save Security Settings
                    </button>
                </div>
            </div>
        </form>

        <!-- Notification Settings -->
        <form id="notificationSettingsForm" class="tab-content" data-tab="notifications">
            <div class="card">
                <div class="card-header">
                    <div class="card-title">Notification Settings</div>
                </div>
                <div class="card-body">
                    <div class="settings-section">
                        <div class="section-title">
                            <span class="material-icons-round">notifications</span>
                            <span>Notification Methods</span>
                        </div>
                        <div class="setting-item">
                            <div class="setting-info">
                                <div class="setting-name">Email Notifications</div>
                                <div class="setting-description">Enable email notifications for users</div>
                            </div>
                            <label class="switch">
                                <input type="checkbox" name="email_enabled"
                                    <?=($settings['notifications']['email_enabled'] ?? '0' )==='1' ? 'checked' : '' ?>>
                                <span class="slider round"></span>
                            </label>
                        </div>
                        <div class="setting-item">
                            <div class="setting-info">
                                <div class="setting-name">SMS Notifications</div>
                                <div class="setting-description">Enable SMS notifications (requires SMS gateway)</div>
                            </div>
                            <label class="switch">
                                <input type="checkbox" name="sms_enabled" <?=($settings['notifications']['sms_enabled']
                                    ?? '0' )==='1' ? 'checked' : '' ?>>
                                <span class="slider round"></span>
                            </label>
                        </div>
                        <div class="setting-item">
                            <div class="setting-info">
                                <div class="setting-name">Push Notifications</div>
                                <div class="setting-description">Enable push notifications for mobile app</div>
                            </div>
                            <label class="switch">
                                <input type="checkbox" name="push_enabled"
                                    <?=($settings['notifications']['push_enabled'] ?? '0' )==='1' ? 'checked' : '' ?>>
                                <span class="slider round"></span>
                            </label>
                        </div>
                    </div>

                    <div class="settings-section">
                        <div class="section-title">
                            <span class="material-icons-round">mail</span>
                            <span>Notification Types</span>
                        </div>
                        <div class="setting-item">
                            <div class="setting-info">
                                <div class="setting-name">Deposit Notifications</div>
                                <div class="setting-description">Notify users when deposits are received</div>
                            </div>
                            <label class="switch">
                                <input type="checkbox" name="deposit_notify"
                                    <?=($settings['notifications']['deposit_notify'] ?? '0' )==='1' ? 'checked' : '' ?>>
                                <span class="slider round"></span>
                            </label>
                        </div>
                        <div class="setting-item">
                            <div class="setting-info">
                                <div class="setting-name">Withdrawal Notifications</div>
                                <div class="setting-description">Notify users when withdrawals are processed</div>
                            </div>
                            <label class="switch">
                                <input type="checkbox" name="withdrawal_notify"
                                    <?=($settings['notifications']['withdrawal_notify'] ?? '0' )==='1' ? 'checked' : ''
                                    ?>>
                                <span class="slider round"></span>
                            </label>
                        </div>
                        <div class="setting-item">
                            <div class="setting-info">
                                <div class="setting-name">Login Notifications</div>
                                <div class="setting-description">Notify users about new logins</div>
                            </div>
                            <label class="switch">
                                <input type="checkbox" name="login_notify"
                                    <?=($settings['notifications']['login_notify'] ?? '0' )==='1' ? 'checked' : '' ?>>
                                <span class="slider round"></span>
                            </label>
                        </div>
                        <div class="form-group">
                            <label for="adminNotifyEmail">Admin Notification Email</label>
                            <input type="email" id="adminNotifyEmail" name="admin_notify_email" class="form-control"
                                value="<?= htmlspecialchars($settings['notifications']['admin_notify_email'] ?? '') ?>">
                        </div>
                    </div>
                </div>
                <div style="padding: 15px; border-top: 1px solid #eee;">
                    <button type="submit" class="btn btn-primary">
                        <span class="material-icons-round">save</span>
                        Save Notification Settings
                    </button>
                </div>
            </div>
        </form>

        <!-- Payment Settings -->
        <form id="paymentSettingsForm" class="tab-content" data-tab="payment">
            <div class="card">
                <div class="card-header">
                    <div class="card-title">Payment Settings</div>
                </div>
                <div class="card-body">
                    <div class="settings-section">
                        <div class="section-title">
                            <span class="material-icons-round">payments</span>
                            <span>Payment Gateways</span>
                        </div>
                        <div class="form-group">
                            <label for="paymentGateway">Default Payment Gateway</label>
                            <select id="paymentGateway" name="payment_gateway" class="form-control">
                                <option value="stripe" <?=($settings['payment']['payment_gateway'] ?? '' )==='stripe'
                                    ? 'selected' : '' ?>>Stripe</option>
                                <option value="paypal" <?=($settings['payment']['payment_gateway'] ?? '' )==='paypal'
                                    ? 'selected' : '' ?>>PayPal</option>
                                <option value="razorpay" <?=($settings['payment']['payment_gateway'] ?? ''
                                    )==='razorpay' ? 'selected' : '' ?>>Razorpay</option>
                            </select>
                        </div>

                        <div id="stripeSettings" class="gateway-settings"
                            style="<?= ($settings['payment']['payment_gateway'] ?? '') !== 'stripe' ? 'display: none;' : '' ?>">
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="stripePublishableKey">Stripe Publishable Key</label>
                                    <input type="text" id="stripePublishableKey" name="stripe_publishable_key"
                                        class="form-control"
                                        value="<?= htmlspecialchars($settings['payment']['stripe_publishable_key'] ?? '') ?>">
                                </div>
                                <div class="form-group">
                                    <label for="stripeSecretKey">Stripe Secret Key</label>
                                    <input type="password" id="stripeSecretKey" name="stripe_secret_key"
                                        class="form-control"
                                        value="<?= htmlspecialchars($settings['payment']['stripe_secret_key'] ?? '') ?>">
                                </div>
                            </div>
                        </div>

                        <div id="paypalSettings" class="gateway-settings"
                            style="<?= ($settings['payment']['payment_gateway'] ?? '') !== 'paypal' ? 'display: none;' : '' ?>">
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="paypalClientId">PayPal Client ID</label>
                                    <input type="text" id="paypalClientId" name="paypal_client_id" class="form-control"
                                        value="<?= htmlspecialchars($settings['payment']['paypal_client_id'] ?? '') ?>">
                                </div>
                                <div class="form-group">
                                    <label for="paypalSecret">PayPal Secret</label>
                                    <input type="password" id="paypalSecret" name="paypal_secret" class="form-control"
                                        value="<?= htmlspecialchars($settings['payment']['paypal_secret'] ?? '') ?>">
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="settings-section">
                        <div class="section-title">
                            <span class="material-icons-round">attach_money</span>
                            <span>Transaction Settings</span>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="minDeposit">Minimum Deposit Amount</label>
                                <input type="number" id="minDeposit" name="min_deposit" class="form-control"
                                    value="<?= htmlspecialchars($settings['payment']['min_deposit'] ?? '100') ?>"
                                    min="0" step="0.01">
                            </div>
                            <div class="form-group">
                                <label for="maxDeposit">Maximum Deposit Amount</label>
                                <input type="number" id="maxDeposit" name="max_deposit" class="form-control"
                                    value="<?= htmlspecialchars($settings['payment']['max_deposit'] ?? '100000') ?>"
                                    min="0" step="0.01">
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="minWithdrawal">Minimum Withdrawal Amount</label>
                                <input type="number" id="minWithdrawal" name="min_withdrawal" class="form-control"
                                    value="<?= htmlspecialchars($settings['payment']['min_withdrawal'] ?? '500') ?>"
                                    min="0" step="0.01">
                            </div>
                            <div class="form-group">
                                <label for="withdrawalFee">Withdrawal Fee</label>
                                <input type="number" id="withdrawalFee" name="withdrawal_fee" class="form-control"
                                    value="<?= htmlspecialchars($settings['payment']['withdrawal_fee'] ?? '10') ?>"
                                    min="0" step="0.01">
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="withdrawalFeeType">Withdrawal Fee Type</label>
                            <select id="withdrawalFeeType" name="withdrawal_fee_type" class="form-control">
                                <option value="percentage" <?=($settings['payment']['withdrawal_fee_type'] ?? ''
                                    )==='percentage' ? 'selected' : '' ?>>Percentage (%)</option>
                                <option value="fixed" <?=($settings['payment']['withdrawal_fee_type'] ?? '' )==='fixed'
                                    ? 'selected' : '' ?>>Fixed Amount</option>
                            </select>
                        </div>
                        <div class="setting-item">
                            <div class="setting-info">
                                <div class="setting-name">Manual Deposit Approval</div>
                                <div class="setting-description">Require admin approval for all deposits</div>
                            </div>
                            <label class="switch">
                                <input type="checkbox" name="deposit_approval"
                                    <?=($settings['payment']['deposit_approval'] ?? '0' )==='1' ? 'checked' : '' ?>>
                                <span class="slider round"></span>
                            </label>
                        </div>
                    </div>
                </div>
                <div style="padding: 15px; border-top: 1px solid #eee;">
                    <button type="submit" class="btn btn-primary">
                        <span class="material-icons-round">save</span>
                        Save Payment Settings
                    </button>
                </div>
            </div>
        </form>

        <!-- Maintenance Settings -->
        <form id="maintenanceSettingsForm" class="tab-content" data-tab="maintenance">
            <div class="card">
                <div class="card-header">
                    <div class="card-title">Maintenance Settings</div>
                </div>
                <div class="card-body">
                    <div class="settings-section">
                        <div class="section-title">
                            <span class="material-icons-round">engineering</span>
                            <span>Maintenance Mode</span>
                        </div>
                        <div class="setting-item">
                            <div class="setting-info">
                                <div class="setting-name">Enable Maintenance Mode</div>
                                <div class="setting-description">When enabled, only admins can access the site</div>
                            </div>
                            <label class="switch">
                                <input type="checkbox" name="maintenance_mode"
                                    <?=($settings['maintenance']['maintenance_mode'] ?? '0' )==='1' ? 'checked' : '' ?>>
                                <span class="slider round"></span>
                            </label>
                        </div>
                        <div class="form-group">
                            <label for="maintenanceMessage">Maintenance Message</label>
                            <textarea id="maintenanceMessage" name="maintenance_message" class="form-control"><?= 
                                htmlspecialchars($settings['maintenance']['maintenance_message'] ?? '') ?></textarea>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="maintenanceStart">Scheduled Start</label>
                                <input type="datetime-local" id="maintenanceStart                               name="
                                    maintenance_start" class="form-control"
                                    value="<?= htmlspecialchars($settings['maintenance']['maintenance_start'] ?? '') ?>">
                            </div>
                            <div class="form-group">
                                <label for="maintenanceEnd">Scheduled End</label>
                                <input type="datetime-local" id="maintenanceEnd" name="maintenance_end"
                                    class="form-control"
                                    value="<?= htmlspecialchars($settings['maintenance']['maintenance_end'] ?? '') ?>">
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="allowedIPs">Allowed IP Addresses (comma separated)</label>
                            <textarea id="allowedIPs" name="allowed_ips" class="form-control ip-list"><?= 
                             htmlspecialchars($settings['maintenance']['allowed_ips'] ?? '') ?></textarea>
                        </div>
                    </div>
                </div>
                <div style="padding: 15px; border-top: 1px solid #eee;">
                    <button type="submit" class="btn btn-primary">
                        <span class="material-icons-round">save</span>
                        Save Maintenance Settings
                    </button>
                </div>
            </div>
        </form>

        <div class="modal" id="testEmailModal">
            <div class="modal-content">
                <div class="modal-header">
                    <div class="modal-title">Test Email Configuration</div>
                    <div class="modal-close" onclick="closeModal('testEmailModal')">
                        <span class="material-icons-round">close</span>
                    </div>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label for="testEmailAddress">Email Address</label>
                        <input type="email" id="testEmailAddress" class="form-control"
                            placeholder="Enter email address to send test">
                    </div>
                    <div class="form-group">
                        <label for="testEmailType">Email Type</label>
                        <select id="testEmailType" class="form-control">
                            <option value="welcome">Welcome Email</option>
                            <option value="transaction">Transaction Email</option>
                            <option value="notification">Notification Email</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline" onclick="closeModal('testEmailModal')">Cancel</button>
                    <button type="button" id="sendTestEmailBtn" class="btn btn-primary">
                        <span class="material-icons-round">send</span>
                        Send Test Email
                    </button>
                </div>
            </div>
        </div>

        <!-- Toast Notification -->
        <div id="toast" class="toast">
            <span class="material-icons-round" id="toastIcon">check_circle</span>
            <span id="toastMessage">Settings saved successfully</span>
        </div>
    </div>

    <script>
        // Tab Switching Functionality
        document.querySelectorAll('.tab').forEach(tab => {
            tab.addEventListener('click', function () {
                const tabName = this.dataset.tab;

                // Remove active class from all tabs and content
                document.querySelectorAll('.tab, .tab-content').forEach(el => {
                    el.classList.remove('active');
                });

                // Add active class to clicked tab and corresponding content
                this.classList.add('active');
                document.querySelector(`.tab-content[data-tab="${tabName}"]`).classList.add('active');
            });
        });

        // Payment Gateway Selection Handling
        document.getElementById('paymentGateway').addEventListener('change', function () {
            document.querySelectorAll('.gateway-settings').forEach(el => {
                el.style.display = 'none';
            });
            document.getElementById(`${this.value}Settings`).style.display = 'block';
        });

        // Form Submission Handling for All Settings Forms
        const handleFormSubmit = (formId, actionName) => {
            document.getElementById(formId).addEventListener('submit', function (e) {
                e.preventDefault();

                const formData = new FormData(this);
                formData.append('action', actionName);

                fetch('settings.php', {
                    method: 'POST',
                    body: formData
                })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            showToast(data.message);
                        } else {
                            showToast(data.message, true);
                        }
                    })
                    .catch(error => {
                        showToast('An error occurred while saving settings', true);
                        console.error('Error:', error);
                    });
            });
        };

        // Initialize form handlers for all tabs
        handleFormSubmit('generalSettingsForm', 'save_general');
        handleFormSubmit('securitySettingsForm', 'save_security');
        handleFormSubmit('notificationSettingsForm', 'save_notifications');
        handleFormSubmit('paymentSettingsForm', 'save_payment');
        handleFormSubmit('maintenanceSettingsForm', 'save_maintenance');

        function showToast(message, isError = false) {
            const toast = document.getElementById('toast');
            const toastIcon = document.getElementById('toastIcon');
            const toastMessage = document.getElementById('toastMessage');

            toastMessage.textContent = message;

            if (isError) {
                toast.classList.add('error');
                toastIcon.textContent = 'error';
            } else {
                toast.classList.remove('error');
                toastIcon.textContent = 'check_circle';
            }

            toast.classList.add('show');

            setTimeout(() => {
                toast.classList.remove('show');
            }, 3000);
        }

        // Modal Functions
        function openModal(modalId) {
            document.getElementById(modalId).style.display = 'flex';
            document.body.style.overflow = 'hidden';
        }

        function closeModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
            document.body.style.overflow = 'auto';
        }

        // Tab switching
        document.querySelectorAll('.tab').forEach(tab => {
            tab.addEventListener('click', function () {
                // Remove active class from all tabs
                document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
                // Add active class to clicked tab
                this.classList.add('active');
                // Here you would typically load the appropriate settings section
            });
        });

        // Form submission for general settings
        document.getElementById('generalSettingsForm').addEventListener('submit', function (e) {
            e.preventDefault();

            const formData = new FormData(this);
            formData.append('action', 'save_general');

            fetch('settings.php', {
                method: 'POST',
                body: formData
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showToast(data.message);
                    } else {
                        showToast(data.message, true);
                    }
                })
                .catch(error => {
                    showToast('An error occurred while saving settings', true);
                    console.error('Error:', error);
                });
        });

        // Form submission for email settings
        document.getElementById('emailSettingsForm').addEventListener('submit', function (e) {
            e.preventDefault();

            const formData = new FormData(this);
            formData.append('action', 'save_email');

            fetch('settings.php', {
                method: 'POST',
                body: formData
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showToast(data.message);
                    } else {
                        showToast(data.message, true);
                    }
                })
                .catch(error => {
                    showToast('An error occurred while saving settings', true);
                    console.error('Error:', error);
                });
        });

        // Test email button
        document.getElementById('testEmailBtn').addEventListener('click', function () {
            openModal('testEmailModal');
        });

        // Send test email
        document.getElementById('sendTestEmailBtn').addEventListener('click', function () {
            const email = document.getElementById('testEmailAddress').value;
            const type = document.getElementById('testEmailType').value;

            if (!email) {
                showToast('Please enter an email address', true);
                return;
            }

            const formData = new FormData();
            formData.append('action', 'test_email');
            formData.append('test_email', email);
            formData.append('test_email_type', type);

            fetch('settings.php', {
                method: 'POST',
                body: formData
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showToast(data.message);
                        closeModal('testEmailModal');
                        document.getElementById('testEmailAddress').value = '';
                    } else {
                        showToast(data.message, true);
                    }
                })
                .catch(error => {
                    showToast('An error occurred while sending test email', true);
                    console.error('Error:', error);
                });
        });

        // Close modal when clicking outside
        window.onclick = function (event) {
            if (event.target.className === 'modal') {
                event.target.style.display = 'none';
                document.body.style.overflow = 'auto';
            }
        }

        function closeModal(id) {
            document.getElementById(id).style.display = "none";
        }

        function openModal(id) {
            document.getElementById(id).style.display = "block";
        }

        document.getElementById('testEmailBtn').addEventListener('click', function () {
            openModal('testEmailModal');
        });

        document.getElementById('sendTestEmailBtn').addEventListener('click', function () {
            const email = document.getElementById('testEmailAddress').value;
            const type = document.getElementById('testEmailType').value;

            if (!email) {
                alert("Please enter an email address.");
                return;
            }

            fetch('send_test_email.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ email, type })
            })
                .then(res => res.json())
                .then(data => {
                    alert(data.message);
                    if (data.success) closeModal('testEmailModal');
                })
                .catch(err => {
                    console.error(err);
                    alert("Error sending email.");
                });
        });
    </script>

</body>

</html>