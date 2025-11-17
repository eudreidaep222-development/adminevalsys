<?php
session_start();
require_once './1database_conn/connection.php';
require_once './Authentication/checkAuth.php';
require_once './helper/log_action.php';
require 'PHPMailer-master/src/PHPMailer.php';
require 'PHPMailer-master/src/SMTP.php';
require 'PHPMailer-master/src/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;


header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$error_message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (!empty($username) && !empty($password)) {
        $stmt = $conn->prepare("SELECT id, fullName, email, password, status, role, account_locked, archived, contact_number 
                                FROM users 
                                WHERE email = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();

            if (password_verify($password, $row['password'])) {
                // 🔒 Account restriction checks
                if (strtolower($row['status']) !== 'active') {
                    log_login_attempt($conn, $row['email'], 'failed', $row['id'], 'Account not active');
                    $error_message = "Your account is currently <b>" . htmlspecialchars($row['status']) . "</b>. Please contact the administrator.";
                } elseif (!empty($row['account_locked']) && $row['account_locked'] == 1) {
                    log_login_attempt($conn, $row['email'], 'failed', $row['id'], 'Account locked');
                    $error_message = "Your account is <b>locked</b> due to multiple failed login attempts. Please contact the administrator.";
                } elseif (!empty($row['archived']) && $row['archived'] == 1) {
                    log_login_attempt($conn, $row['email'], 'failed', $row['id'], 'Account archived');
                    $error_message = "Your account has been <b>archived</b>. Please contact the administrator.";
                } else {
                    // ✅ Step 1: Generate OTP
                    $otp = rand(100000, 999999);
                    $expiry = date("Y-m-d H:i:s", strtotime("+5 minutes"));
                    $stmt = $conn->prepare("UPDATE users SET otp_code = ?, otp_expiration = ?, otp_verified = 'no' WHERE id = ?");
                    $stmt->bind_param("ssi", $otp, $expiry, $row['id']);
                    $stmt->execute();

                    // ✅ Step 2: Send OTP via Email (PHPMailer)
                    $mail = new PHPMailer();
                    $mail->isSMTP();
                    $mail->Host = 'smtp.gmail.com';
                    $mail->SMTPAuth = true;
                    $mail->Username = 'jemdaep@kld.edu.ph'; // 🔒 Replace
                    $mail->Password = 'zxcn uabv tucw qrsd';   // 🔒 App password, not account password
                    $mail->SMTPSecure = 'tls';
                    $mail->Port = 587;

                    $mail->setFrom('jemdaep@kld.edu.ph', 'Evaluation System');
                    $mail->addAddress($row['email']);
                    $mail->isHTML(true);
                    $mail->Subject = 'Your OTP Code';
                    $mail->Body = "
                        <div style='font-family:sans-serif;'>
                            <h2>One-Time Password (OTP)</h2>
                            <p>Hi <b>{$row['fullName']}</b>,</p>
                            <p>Your OTP code is: <b style='font-size:20px;'>$otp</b></p>
                            <p>This code will expire in <b>5 minutes</b>.</p>
                        </div>";

                    if ($mail->send()) {
                        $_SESSION['temp_user_id'] = $row['id'];
                        $_SESSION['otp_email'] = $row['email'];
                        header("Location: verify-otp.php");
                        exit();
                    } else {
                        $error_message = "Failed to send OTP. Please try again later.";
                    }
                }
            } else {
                log_login_attempt($conn, $username, 'failed', $row['id'], 'Incorrect password');
                $error_message = "Invalid username or password.";
            }
        } else {
            log_login_attempt($conn, $username, 'failed', null, 'Email not found');
            $error_message = "Invalid username or password.";
        }

        $stmt->close();
    } else {
        $error_message = "All fields are required.";
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Administrator Evaluation System - Login</title>
    <link rel="shortcut icon" href="assets/img/logo-small.png">
    <link rel="stylesheet" href="assets/css/login.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
</head>
<body>

<form class="login-container" method="POST" action="">
    <div class="login-header">
        <img src="assets/img/logo-small.png" alt="Logo" class="login-logo">
        <h1 class="system-title">Administrator Evaluation System</h1>
        <h2 class="login-title">Sign In</h2>
    </div>

    <?php if (!empty($error_message)) : ?>
        <div class="error-message">
            <?php echo $error_message; ?>
        </div>
    <?php endif; ?>

    <div class="input-box">
        <i class='bx bxs-user'></i>
        <input type="text" name="username" placeholder="Email address" required autocomplete="off">
    </div>

    <div class="input-box">
        <i class='bx bxs-lock-alt'></i>
        <input type="password" name="password" placeholder="Password" required>
    </div>

    <div class="remember-forgot-box">
        <label class="remember-me">
            <input type="checkbox" name="remember-me" id="remember-me">
            Remember me
        </label>
        <a href="#" class="forgot-password">Forgot Password?</a>
    </div>

    <button class="login-button" type="submit">Login</button>
</form>


<script>
    function preventBack() {
        window.history.forward();
    }
    setTimeout(preventBack, 0);

    window.onload = function () {
        document.addEventListener("keydown", function (e) {
            if (e.keyCode === 8 && e.target.tagName !== "INPUT") { 
                e.preventDefault();
            }
        });
    }
</script>

</body>
</html>
