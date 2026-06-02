<?php
session_name('user_session');
session_start();
if (isset($_SESSION['user_id'], $_SESSION['role']) && $_SESSION['role'] === 'user') {
    header("Location: ../dashboard.php");
    exit;
}

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/transaction_mailer.php';

// Handle signup form submission
$success = '';
$error = '';

// Pre-fill referral code from URL
$ref_from_url = trim($_GET['ref'] ?? '');

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $ref_code_input = strtoupper(trim($_POST['referral_code'] ?? ''));

    if (empty($username) || empty($email) || empty($password)) {
        $error = "All fields are required!";
    } elseif (empty($ref_code_input)) {
        $error = "Referral code is required to register!";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email format!";
    } elseif ($password !== $confirm_password) {
        $error = "Passwords do not match!";
    } elseif (strlen($password) < 6) {
        $error = "Password must be at least 6 characters!";
    } else {
        // Validate referral code
        $refStmt = $pdo->prepare("SELECT id FROM users WHERE referral_code = ?");
        $refStmt->execute([$ref_code_input]);
        $referrer = $refStmt->fetch(PDO::FETCH_ASSOC);

        if (!$referrer) {
            $error = "Invalid referral code! Please enter a valid referral code.";
        } else {
            // Check if email already exists
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$email]);

            if ($stmt->rowCount() > 0) {
                $error = "Email is already registered!";
            } else {
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                $newReferralCode = strtoupper(substr(md5(uniqid($username, true)), 0, 8));
                $referred_by_id = $referrer['id'];

                $stmt = $pdo->prepare("INSERT INTO users (username, email, password, role, status, referral_code, referred_by, mobile, otp) VALUES (?, ?, ?, 'user', 'active', ?, ?, '', '')");

                if ($stmt->execute([$username, $email, $hashedPassword, $newReferralCode, $referred_by_id])) {
                    // Notify admin about new registration
                    $refUserStmt = $pdo->prepare("SELECT username, referral_code FROM users WHERE id = ?");
                    $refUserStmt->execute([$referred_by_id]);
                    $refUser = $refUserStmt->fetch(PDO::FETCH_ASSOC);
                    sendTransactionEmail('Sharmagopesh706@gmail.com', 'Admin', '👤 New User Registered - Goldpay', [
                        'New Username'   => $username,
                        'Email'          => $email,
                        'Referral Code Used' => $ref_code_input,
                        'Referred By'    => $refUser['username'] ?? 'N/A',
                        'Registered On'  => date('d M Y, h:i A'),
                    ]);
                    $_SESSION['success'] = "Registration successful! Please log in.";
                    header("Location: login.php");
                    exit;
                } else {
                    $error = "Failed to register user. Please try again.";
                }
            }
        }
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Up - Goldpay</title>
    <link rel="icon" type="image/x-icon" href="../../favicon.ico">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }

        body {
            background-color: #ffffff;
            min-height: 100vh;
            display: flex;
            overflow-x: hidden;
        }

        .left-section {
            width: 50%;
            background: #000000;
            color: #D4AF37;
            padding: 60px;
            display: flex;
            flex-direction: column;
            position: relative;
            overflow: hidden;
            clip-path: ellipse(100% 100% at 0% 50%);
            z-index: 1;
        }

        .left-section::before {
            content: '';
            position: absolute;
            top: 0;
            right: 0;
            bottom: 0;
            left: 0;
            background: url('../images/backgroundimg.jpg') no-repeat center center;
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            z-index: -1;
        }

        .right-section {
            width: 50%;
            padding: 80px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .logo {
            width: 220px;
            position: absolute;
            top: 0;
            left: 0;
        }

        .left-content {
            margin-top: 180px;
            max-width: 500px;
        }

        .left-title {
            font-size: 32px;
            font-weight: 600;
            margin-bottom: 25px;
            line-height: 1.3;
        }

        .left-subtitle {
            font-size: 18px;
            opacity: 0.9;
            line-height: 1.6;
        }

        .form-title {
            font-size: 28px;
            font-weight: 600;
            margin-bottom: 10px;
            color: #36465D;
        }

        .form-subtitle {
            font-size: 16px;
            color: #6c757d;
            margin-bottom: 40px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-size: 14px;
            font-weight: 500;
            color: #495057;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .form-control {
            width: 100%;
            padding: 14px 16px;
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            font-size: 15px;
            transition: all 0.2s;
            background-color: #f8f9fa;
        }

        .form-control:focus {
            border-color: #36465D;
            box-shadow: 0 0 0 3px rgba(54, 70, 93, 0.1);
            outline: none;
            background-color: white;
        }

        .btn {
            width: 100%;
            padding: 16px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            margin-top: 15px;
        }

        .btn-primary {
            background-color: #D4AF37;
            color: white;
        }

        .btn-primary:hover {
            background-color: #D4AF37;
            transform: translateY(-2px);
        }

        .login-link,
        .register-link {
            text-align: center;
            margin-top: 20px;
            font-size: 15px;
            color: #6c757d;
        }

        .login-link a,
        .register-link a {
            color: #36465D;
            font-weight: 600;
            text-decoration: none;
        }

        .login-link a:hover,
        .register-link a:hover {
            text-decoration: underline;
        }

        .error {
            color: #dc3545;
            margin-bottom: 20px;
            padding: 10px;
            background-color: #f8d7da;
            border: 1px solid #f5c6cb;
            border-radius: 5px;
            font-size: 14px;
        }

        .success {
            color: #28a745;
            margin-bottom: 20px;
            padding: 10px;
            background-color: #d4edda;
            border: 1px solid #c3e6cb;
            border-radius: 5px;
            font-size: 14px;
        }

        .terms {
            margin-top: 30px;
            font-size: 13px;
            text-align: center;
            color: #6c757d;
        }

        .terms a {
            color: #36465D;
            text-decoration: underline;
        }

        .mobile-logo {
            display: none;
            text-align: center;
            margin-bottom: 28px;
        }
        .mobile-logo img {
            width: 140px;
            height: auto;
        }

        @media (max-width: 1024px) {
            body {
                flex-direction: column;
            }

            .left-section {
                display: none;
            }

            .right-section {
                width: 100%;
                padding: 50px 40px;
                min-height: 100vh;
                justify-content: center;
            }

            .mobile-logo {
                display: block;
            }
        }

        @media (max-width: 768px) {
            .right-section {
                padding: 40px 24px;
            }

            .form-title {
                font-size: 22px;
            }

            .form-subtitle {
                font-size: 13px;
                margin-bottom: 24px;
            }

            .btn {
                padding: 14px;
                font-size: 15px;
            }
        }

        @media (max-width: 400px) {
            .right-section {
                padding: 30px 18px;
            }
        }
    </style>
</head>

<body>
    <div class="left-section">
        <a href="../index.php"><img src="../image/logo.png" alt="Dollario Logo" class="logo"></a>
        <div class="left-content">
            <h1 class="left-title">Join Dollario Today</h1>
            <p class="left-subtitle">Start your crypto journey with our secure platform.</p>
        </div>
    </div>

    <div class="right-section">
        <div class="mobile-logo">
            <a href="../index.php"><img src="../image/logo.png" alt="Dollario"></a>
        </div>
        <h2 class="form-title">Create Account</h2>
        <p class="form-subtitle">Get started with your free account</p>

        <form method="POST" action="signup.php">
            <?php if (!empty($error)): ?>
                <div class="error"><?php echo $error; ?></div>
            <?php elseif (!empty($success)): ?>
                <div class="success"><?php echo $success; ?></div>
            <?php endif; ?>

            <?php if (empty($success)): ?>
                <!-- Normal signup fields -->
                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username" class="form-control" required value="<?php echo isset($username) ? htmlspecialchars($username) : ''; ?>">
                </div>

                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" class="form-control" required value="<?php echo isset($email) ? htmlspecialchars($email) : ''; ?>">
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" class="form-control" required>
                </div>

                <div class="form-group">
                    <label for="confirm_password">Confirm Password</label>
                    <input type="password" id="confirm_password" name="confirm_password" class="form-control" required>
                </div>

                <div class="form-group">
                    <label for="referral_code">Referral Code <span style="color:#dc3545;">*</span></label>
                    <input type="text" id="referral_code" name="referral_code" class="form-control" required
                        placeholder="Enter referral code"
                        value="<?php echo htmlspecialchars($_POST['referral_code'] ?? $ref_from_url); ?>">
                    <small style="color:#6c757d;font-size:12px;">Referral code is mandatory to register.</small>
                </div>

                <button type="submit" class="btn btn-primary">Sign Up</button>
            <?php else: ?>
                <!-- OTP Verification fields -->
                <div class="form-group">
                    <label for="otp">Enter OTP</label>
                    <input type="text" id="otp" name="otp" class="form-control" required>
                </div>

                <button type="submit" name="verify_otp" class="btn btn-primary">Verify OTP</button>
            <?php endif; ?>

            <p class="login-link">Already have an account? <a href="login.php">Login here</a></p>
            <p class="terms">By creating an account, you agree to our <a href="..\terms-condistion.php">Terms and Conditions</a> &amp; <a href="..\privacy-policy.php">Privacy Policy</a>.</p>
        </form>
    </div>
</body>
</html>
