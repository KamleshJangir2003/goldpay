<?php
if (session_status() === PHP_SESSION_NONE) { session_name('admin_session'); session_start(); }
if (!isset($_SESSION['user_id'])) {
    header("Location: /dollario-new/admin/login.php");
    exit();
}

$username        = $_SESSION['username'] ?? 'Admin';
$email           = $_SESSION['email'] ?? '';
$phone           = $_SESSION['phone'] ?? '';
$profile_picture = $_SESSION['profile_picture'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile – Dollario Admin</title>
    <link rel="icon" type="image/x-icon" href="../favicon.ico">
    <link rel="stylesheet" href="style.css">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons|Material+Icons+Round" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        .profile-page { padding: 28px 24px; max-width: 960px; }

        .profile-hero {
            background: linear-gradient(135deg, #0e1a2b 0%, #1d2e49 100%);
            border-radius: 16px;
            padding: 32px 28px;
            display: flex;
            align-items: center;
            gap: 28px;
            margin-bottom: 28px;
            position: relative;
            overflow: hidden;
        }
        .profile-hero::before {
            content: '';
            position: absolute;
            top: -40px; right: -40px;
            width: 200px; height: 200px;
            border-radius: 50%;
            background: rgba(255,255,255,0.04);
        }

        .avatar-wrap { position: relative; flex-shrink: 0; }
        .avatar-wrap img,
        .avatar-placeholder {
            width: 90px; height: 90px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid rgba(255,255,255,0.2);
        }
        .avatar-placeholder {
            background: #1d2e49;
            display: flex; align-items: center; justify-content: center;
            font-size: 38px; color: #7a8fa6;
        }

        .hero-info { flex: 1; min-width: 0; }
        .hero-info h2 { color: #fff; font-size: 22px; margin: 0 0 4px; font-weight: 600; word-break: break-word; }
        .hero-info p  { color: #7a8fa6; font-size: 13px; margin: 0 0 3px; word-break: break-all; }
        .hero-badge {
            display: inline-flex; align-items: center; gap: 5px;
            background: rgba(255,255,255,0.08);
            color: #b0c4de; font-size: 12px;
            padding: 4px 10px; border-radius: 20px; margin-top: 8px;
        }

        /* Tabs */
        .profile-tabs {
            display: flex; gap: 4px;
            background: #f1f3f6; border-radius: 10px;
            padding: 4px; margin-bottom: 24px;
            width: 100%;
        }
        .ptab {
            flex: 1;
            padding: 8px 12px; border-radius: 8px;
            font-size: 13px; font-weight: 500;
            cursor: pointer; color: #64748b;
            transition: all 0.2s; border: none; background: none;
            display: flex; align-items: center; justify-content: center; gap: 6px;
            white-space: nowrap;
        }
        .ptab.active { background: #fff; color: #0e1a2b; box-shadow: 0 1px 4px rgba(0,0,0,0.1); }

        .ptab-content { display: none; }
        .ptab-content.active { display: block; }

        /* Form card */
        .form-card {
            background: #fff;
            border-radius: 14px;
            padding: 28px;
            box-shadow: 0 2px 12px rgba(0,0,0,0.07);
            border: 1px solid #e8ecf0;
        }
        .form-card h3 {
            font-size: 15px; font-weight: 600; color: #0e1a2b;
            margin: 0 0 20px; display: flex; align-items: center; gap: 8px;
        }
        .form-card h3 i { color: #36465d; font-size: 18px; }

        .field-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }

        .field { display: flex; flex-direction: column; gap: 5px; }
        .field label { font-size: 12px; font-weight: 600; color: #64748b; text-transform: uppercase; letter-spacing: 0.4px; }
        .field input {
            padding: 10px 13px;
            border: 1.5px solid #e2e8f0;
            border-radius: 8px;
            font-size: 14px; color: #1e293b;
            outline: none; transition: border 0.2s;
            background: #fafbfc;
            width: 100%;
        }
        .field input:focus { border-color: #36465d; background: #fff; }

        .save-btn {
            margin-top: 22px;
            padding: 11px 28px;
            background: #0e1a2b;
            color: #fff; border: none;
            border-radius: 8px; font-size: 14px; font-weight: 600;
            cursor: pointer; display: inline-flex; align-items: center; gap: 7px;
            transition: background 0.2s;
        }
        .save-btn:hover { background: #1d2e49; }

        /* Alert */
        .alert {
            padding: 11px 16px; border-radius: 8px;
            font-size: 13px; margin-bottom: 18px;
            display: flex; align-items: center; gap: 8px;
        }
        .alert-success { background: #ecfdf5; color: #065f46; border: 1px solid #a7f3d0; }
        .alert-error   { background: #fef2f2; color: #991b1b; border: 1px solid #fecaca; }

        /* Password strength */
        .strength-bar { height: 4px; border-radius: 4px; background: #e2e8f0; margin-top: 6px; overflow: hidden; }
        .strength-fill { height: 100%; border-radius: 4px; transition: width 0.3s, background 0.3s; width: 0; }

        /* Mobile */
        @media(max-width: 480px) {
            .profile-page { padding: 12px; }
            .profile-hero { flex-direction: column; text-align: center; padding: 20px 16px; gap: 16px; }
            .hero-info h2 { font-size: 18px; }
            .form-card { padding: 16px; }
            .field-grid { grid-template-columns: 1fr; }
            .save-btn { width: 100%; justify-content: center; }
            .ptab { font-size: 12px; padding: 8px 6px; gap: 4px; }
            .ptab i { font-size: 12px; }
        }

        /* Tablet */
        @media(min-width: 481px) and (max-width: 768px) {
            .profile-hero { flex-direction: column; text-align: center; padding: 24px 20px; }
            .profile-page { padding: 16px; }
            .field-grid { grid-template-columns: 1fr; }
            .save-btn { width: 100%; justify-content: center; }
        }
    </style>
</head>
<body>

<?php include 'templates/sidebar.php'; ?>

<div class="main-content">
    <?php include 'templates/header.php'; ?>

    <div class="profile-page">

        <!-- Hero Banner -->
        <div class="profile-hero">
            <div class="avatar-wrap">
                <?php if (!empty($profile_picture)): ?>
                    <img src="<?= htmlspecialchars($profile_picture) ?>" alt="Avatar">
                <?php else: ?>
                    <div class="avatar-placeholder"><i class="fas fa-user"></i></div>
                <?php endif; ?>
            </div>
            <div class="hero-info">
                <h2><?= htmlspecialchars($username) ?></h2>
                <p><i class="fas fa-envelope" style="font-size:11px;margin-right:4px;"></i><?= htmlspecialchars($email) ?></p>
                <?php if ($phone): ?>
                <p><i class="fas fa-phone" style="font-size:11px;margin-right:4px;"></i><?= htmlspecialchars($phone) ?></p>
                <?php endif; ?>
                <span class="hero-badge"><i class="fas fa-shield-alt" style="font-size:10px;"></i> Administrator</span>
            </div>
        </div>

        <!-- Alerts -->
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success"><i class="fas fa-check-circle"></i><?= $_SESSION['success']; unset($_SESSION['success']); ?></div>
        <?php endif; ?>
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-error"><i class="fas fa-exclamation-circle"></i><?= $_SESSION['error']; unset($_SESSION['error']); ?></div>
        <?php endif; ?>

        <!-- Tabs -->
        <div class="profile-tabs">
            <button class="ptab active" onclick="switchTab('info', this)">
                <i class="fas fa-user-edit"></i> Edit Profile
            </button>
            <button class="ptab" onclick="switchTab('password', this)">
                <i class="fas fa-lock"></i> Change Password
            </button>
        </div>

        <!-- Tab: Edit Profile -->
        <div class="ptab-content active" id="tab-info">
            <div class="form-card">
                <h3><i class="fas fa-id-card"></i> Personal Information</h3>
                <form action="update_profile.php" method="POST" enctype="multipart/form-data">
                    <div class="field-grid">
                        <div class="field">
                            <label>Username</label>
                            <input type="text" name="username" value="<?= htmlspecialchars($username) ?>" required>
                        </div>
                        <div class="field">
                            <label>Email Address</label>
                            <input type="email" name="email" value="<?= htmlspecialchars($email) ?>" required>
                        </div>
                        <div class="field">
                            <label>Phone Number</label>
                            <input type="text" name="phone" value="<?= htmlspecialchars($phone) ?>">
                        </div>
                    </div>
                    <button type="submit" name="update_profile" class="save-btn">
                        <i class="fas fa-save"></i> Save Changes
                    </button>
                </form>
            </div>
        </div>

        <!-- Tab: Change Password -->
        <div class="ptab-content" id="tab-password">
            <div class="form-card">
                <h3><i class="fas fa-key"></i> Update Password</h3>
                <form method="post" action="change_password.php">
                    <div class="field-grid">
                        <div class="field">
                            <label>Current Password</label>
                            <input type="password" name="old_password" required placeholder="••••••••">
                        </div>
                        <div class="field">
                            <label>New Password</label>
                            <input type="password" name="new_password" id="newPass" required placeholder="••••••••" oninput="checkStrength(this.value)">
                            <div class="strength-bar"><div class="strength-fill" id="strengthFill"></div></div>
                        </div>
                        <div class="field">
                            <label>Confirm New Password</label>
                            <input type="password" name="confirm_password" required placeholder="••••••••">
                        </div>
                    </div>
                    <button type="submit" class="save-btn">
                        <i class="fas fa-lock"></i> Update Password
                    </button>
                </form>
            </div>
        </div>

    </div><!-- /profile-page -->
</div><!-- /main-content -->

<script>
function switchTab(name, el) {
    document.querySelectorAll('.ptab-content').forEach(t => t.classList.remove('active'));
    document.querySelectorAll('.ptab').forEach(t => t.classList.remove('active'));
    document.getElementById('tab-' + name).classList.add('active');
    el.classList.add('active');
}

function checkStrength(val) {
    var fill = document.getElementById('strengthFill');
    var score = 0;
    if (val.length >= 8) score++;
    if (/[A-Z]/.test(val)) score++;
    if (/[0-9]/.test(val)) score++;
    if (/[^A-Za-z0-9]/.test(val)) score++;
    var colors = ['#ef4444','#f97316','#eab308','#22c55e'];
    fill.style.width  = (score * 25) + '%';
    fill.style.background = colors[score - 1] || '#e2e8f0';
}
</script>

</body>
</html>
