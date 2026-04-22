<?php
session_name('user_session');
session_start();

require_once __DIR__ . '/../config/db.php';

$error = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email    = $_POST['email'];
    $password = $_POST['password'];

    $stmt = $pdo->prepare("SELECT id, password, status FROM users WHERE email = ?");
    $stmt->execute([$email]);
    
    if ($stmt->rowCount() == 1) {
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        $id = $user['id'];
        $hashed_password = $user['password'];
        $status = $user['status'];
    
        if ($status === 'active' && password_verify($password, $hashed_password)) {
            session_unset();
            session_destroy();
            session_start();
            session_regenerate_id(true);
            $_SESSION['user_id'] = $id;
            $_SESSION['role'] = 'user';

            $ip = $_SERVER['REMOTE_ADDR'];
            $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
            $pdo->prepare("INSERT INTO login_history (user_id, ip_address, user_agent) VALUES (?, ?, ?)")->execute([$id, $ip, $ua]);
            $pdo->prepare("UPDATE users SET ip_address = ? WHERE id = ?")->execute([$ip, $id]);

            header("Location: ../page/dashboard.php");
            exit;
        } else {
            $error = "Your account is not active or password is incorrect!";
        }
    } else {
        $error = "User not found!";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>mbpay - User Login</title>
    <link rel="icon" type="image/x-icon" href="../../favicon.ico">
    <!-- <link rel="stylesheet" href="style.css"> -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    
</head>
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
            z-index: 0;
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
            position: relative;
            z-index: 1;
            background: #fff;
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
            background-color: #D4AF37;
            color: white;
        }

        .btn-primary:hover {
            background-color: #D4AF37;
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

        .forgot-password {
            text-align: right;
            margin-top: -10px;
            margin-bottom: 20px;
        }

        .forgot-password a {
            font-size: 14px;
            color: #36465D;
            text-decoration: none;
        }

        .forgot-password a:hover {
            text-decoration: underline;
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
<body>
    <div class="left-section">
        <a href="../index.php"><img src="../image/logo.png" alt="Dollario Logo" class="logo"></a>
        <div class="left-content">
            <h1 class="left-title">Welcome Back to MBPAY</h1>
            <p class="left-subtitle">Log in and continue your crypto journey.</p>
        </div>
    </div>

    <div class="right-section">
        <div class="mobile-logo">
            <a href="../index.php"><img src="../image/logo.png" alt="Dollario"></a>
        </div>
        <h2 class="form-title">User Login</h2>
        <p class="form-subtitle">Access your account securely</p>

        <?php if ($error): ?>
            <div style="color:#dc3545;background:#fff0f0;border:1px solid #f5c6cb;border-radius:8px;padding:12px 16px;margin-bottom:20px;font-size:14px;">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="login.php">
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" class="form-control" required>
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" class="form-control" required>
            </div>

            <div class="forgot-password">
                <a href="forgot_password.php">Forgot Password?</a>
            </div>

            <button type="submit" class="btn btn-primary">Login</button>
        </form>

        <p class="register-link">Don't have an account? <a href="signup.php">Sign Up</a></p>
        <form method="POST" action="guest_login.php">
    <!-- <button type="submit" class="btn btn-secondary">Continue as Guest</button> -->
</form>

        <p class="terms">By signing in, you agree to our <a href="..\terms-condistion.php">Terms and Conditions</a> &amp; <a href="..\privacy-policy.php">Privacy Policy</a>.</p>
    </div>
</body>
</html>