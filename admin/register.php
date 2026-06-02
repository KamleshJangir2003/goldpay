<?php
session_start();
include 'includes/functions.php';

// Define the escape function if not already in functions.php
function escape($string) {
    return htmlspecialchars(trim($string), ENT_QUOTES, 'UTF-8');
}

// Initialize variables to avoid undefined warnings
$first_name = '';
$last_name = '';
$phone = '';
$country = '';
$email = '';
$username = '';
$password = '';
$confirm_password = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $first_name = escape($_POST['first_name'] ?? '');
    $last_name = escape($_POST['last_name'] ?? '');
    $phone = escape($_POST['phone'] ?? '');
    $country = escape($_POST['country'] ?? '');
    $email = escape($_POST['email'] ?? '');
    $username = escape($_POST['username'] ?? '');
    $password = escape($_POST['password'] ?? '');
    $confirm_password = escape($_POST['confirm_password'] ?? '');

    // Example validation
    if ($password !== $confirm_password) {
        echo "<p style='color:red;'>Passwords do not match!</p>";
    } else {
        // Continue registration logic...
    }
}

    if ($password === $confirm_password) {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $conn = dbConnect();

        $sql = "SELECT * FROM admin_users WHERE username = '$username' OR email = '$email'";
        $result = $conn->query($sql);

        if ($result->num_rows > 0) {
            $error = "Username or email already exists!";
        } else {
            $sql = "INSERT INTO admin_users (first_name, last_name, phone, country, email, username, password) 
                    VALUES ('$first_name', '$last_name', '$phone', '$country', '$email', '$username', '$hashed_password')";
            if ($conn->query($sql) === TRUE) {
                $success = "Registration successful. Redirecting to login...";
                header("refresh:2;url=login.php");
            } else {
                $error = "Error: " . $conn->error;
            }
        }

        $conn->close();
    } else {
        $error = "Passwords do not match!";
    }

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Register</title>
    <link rel="icon" type="image/x-icon" href="../favicon.ico">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Poppins', sans-serif; }

        body {
            display: flex;
            min-height: 100vh;
            background-color: #fff;
        }

        .left-section {
            width: 50%;
            background: #000000;
            color: #D4AF37;
            padding: 60px;
            display: flex;
            flex-direction: column;
            position: relative;
            clip-path: ellipse(100% 100% at 0% 50%);
            z-index: 1;
        }

        .left-section::before {
            content: '';
            position: absolute;
            top: 0; right: 0; bottom: 0; left: 0;
            background: url('your-background.jpg') no-repeat center center / cover;
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
            width: 120px;
            height: auto;
            display: block;
            margin: 0 0 30px 0;
            position: static;
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
            margin-bottom: 30px;
        }

        .form-group {
            margin-bottom: 18px;
        }

        .form-group input {
            width: 100%;
            padding: 14px 16px;
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            font-size: 15px;
            background-color: #f8f9fa;
        }

        .form-group input:focus {
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

        .message {
            color: red;
            margin-bottom: 15px;
            font-size: 15px;
        }

        .success {
            color: green;
        }

        .login-link {
            text-align: center;
            margin-top: 20px;
            font-size: 15px;
            color: #6c757d;
        }

        .login-link a {
            color: #36465D;
            font-weight: 600;
            text-decoration: none;
        }

        .mobile-logo { display: none; text-align: center; margin-bottom: 28px; }
        .mobile-logo img { width: 100px; height: auto; }

        @media (max-width: 1024px) {
            body { flex-direction: column; }
            .left-section { display: none; }
            .right-section { width: 100%; padding: 50px 40px; min-height: 100vh; justify-content: center; }
            .mobile-logo { display: block; }
        }

        @media (max-width: 768px) {
            .right-section { padding: 40px 24px; }
            .form-title { font-size: 22px; }
            .btn { padding: 14px; font-size: 15px; }
        }

        @media (max-width: 400px) {
            .right-section { padding: 30px 18px; }
        }
    </style>
</head>
<body>

<div class="left-section">
    <a href="login.php"><img src="../User_dashboard/image/logo.png" alt="Goldpay Logo" class="logo"></a>
    <div class="left-content">
        <h1 class="left-title">Create Admin Account</h1>
        <p class="left-subtitle">Register to access the admin panel securely.</p>
    </div>
</div>

<div class="right-section">
    <div class="mobile-logo"><a href="login.php"><img src="../User_dashboard/image/logo.png" alt="Goldpay"></a></div>
    <h2 class="form-title">Admin Register</h2>
    <p class="form-subtitle">Fill the details below to create your accounts</p>

    <?php if ($error): ?>
        <div class="message"><?= $error ?></div>
    <?php elseif ($success): ?>
        <div class="message success"><?= $success ?></div>
    <?php endif; ?>

    <form method="POST">
        <div class="form-group"><input type="text" name="first_name" placeholder="First Name" required></div>
        <div class="form-group"><input type="text" name="last_name" placeholder="Last Name" required></div>
        <div class="form-group"><input type="email" name="email" placeholder="Email" required></div>
        <div class="form-group"><input type="text" name="username" placeholder="Username" required></div>
        <div class="form-group"><input type="password" name="password" placeholder="Password" required></div>
        <div class="form-group"><input type="password" name="confirm_password" placeholder="Confirm Password" required></div>
        <button type="submit" class="btn btn-primary">Register</button>
    </form>

    <p class="login-link">Already have an account? <a href="login.php">Login</a></p>
</div>

</body>
</html>