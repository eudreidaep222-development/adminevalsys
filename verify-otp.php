<?php
session_start();
require_once './1database_conn/connection.php';
require 'PHPMailer-master/src/PHPMailer.php';
require 'PHPMailer-master/src/SMTP.php';
require 'PHPMailer-master/src/Exception.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// 🚨 OTP attempt protection
if (!isset($_SESSION['otp_attempts'])) {
    $_SESSION['otp_attempts'] = 0;
}

if ($_SESSION['otp_attempts'] >= 5) {
    session_destroy();
    header("Location: index.php?error=otp_limit");
    exit();
}

if (!isset($_SESSION['temp_user_id'])) {
    header("Location: index.php");
    exit();
}

$error = "";
$success = "";
$userId = $_SESSION['temp_user_id'];

// Get user info including role
$stmt = $conn->prepare("SELECT email, contact_number, fullName, otp_code, otp_expiration, role FROM users WHERE id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

$otp_email = $user['email'];
$contact_number = $user['contact_number'] ?? '';

// ✅ Handle OTP verification
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['verify_otp'])) {
    $_SESSION['otp_attempts'] += 1;
    $otp_input = trim($_POST['otp']);

    if ($user && $otp_input == $user['otp_code'] && strtotime($user['otp_expiration']) > time()) {
        // ✅ OTP Valid
        $stmt = $conn->prepare("UPDATE users SET otp_verified = 'yes', otp_code = NULL, otp_expiration = NULL WHERE id = ?");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $stmt->close();

        $_SESSION['otp_attempts'] = 0;
        $_SESSION['user_id'] = $userId;
        $_SESSION['role'] = $user['role']; // ✅ Store role for redirect
        unset($_SESSION['temp_user_id']);
        unset($_SESSION['otp_email']);

        // ✅ Redirect to login-loading
        header("Location: log-in-loading.php");
        exit();
    } else {
        $error = "Invalid or expired OTP.";
    }
}

// ✅ Handle Resend OTP with 30-second cooldown
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['resend_otp'])) {
    $now = time();
    if (!isset($_SESSION['otp_last_sent'])) $_SESSION['otp_last_sent'] = 0;

    if ($now - $_SESSION['otp_last_sent'] < 30) {
        $error = "Please wait " . (30 - ($now - $_SESSION['otp_last_sent'])) . " seconds before resending OTP.";
    } else {
        $method = $_POST['otp_method'] ?? 'email'; // default to email
        $otp = rand(100000, 999999);
        $expiry = date("Y-m-d H:i:s", strtotime("+5 minutes"));

        $stmt = $conn->prepare("UPDATE users SET otp_code = ?, otp_expiration = ?, otp_verified = 'no' WHERE id = ?");
        $stmt->bind_param("ssi", $otp, $expiry, $userId);
        $stmt->execute();
        $stmt->close();

        if ($method === 'email') {
            $mail = new PHPMailer();
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = 'jemdaep@kld.edu.ph'; // 🔒 Replace
            $mail->Password = 'zxcn uabv tucw qrsd';   // 🔒 App password
            $mail->SMTPSecure = 'tls';
            $mail->Port = 587;

            $mail->setFrom('jemdaep@kld.edu.ph', 'Evaluation System');
            $mail->addAddress($otp_email);
            $mail->isHTML(true);
            $mail->Subject = 'Your OTP Code';
            $mail->Body = "
                <div style='font-family:sans-serif;'>
                    <h2>One-Time Password (OTP)</h2>
                    <p>Hi <b>{$user['fullName']}</b>,</p>
                    <p>Your OTP code is: <b style='font-size:20px;'>$otp</b></p>
                    <p>This code will expire in <b>5 minutes</b>.</p>
                </div>";
            if ($mail->send()) {
                $_SESSION['otp_last_sent'] = $now;
                $success = "A new OTP has been sent to your email.";
            } else {
                $error = "Failed to send OTP email. Please try again later.";
            }
        } elseif ($method === 'sms') {
            // Simulate SMS sending
            $_SESSION['otp_last_sent'] = $now;
            $success = "A new OTP has been sent to your phone number $contact_number.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>OTP Verification</title>
<link rel="shortcut icon" href="assets/img/logo-small.png">
<link rel="stylesheet" href="assets/css/login.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
<script>
let resendCooldown = 30;
<?php if(isset($_SESSION['otp_last_sent'])): ?>
let lastSent = <?php echo $_SESSION['otp_last_sent']; ?>;
let now = Math.floor(Date.now() / 1000);
resendCooldown = Math.max(0, 30 - (now - lastSent));
<?php endif; ?>

function startCooldown() {
    const resendBtn = document.getElementById('resendBtn');
    if (resendCooldown > 0) {
        resendBtn.disabled = true;
        resendBtn.innerText = `Resend OTP (${resendCooldown}s)`;
        const interval = setInterval(() => {
            resendCooldown--;
            resendBtn.innerText = `Resend OTP (${resendCooldown}s)`;
            if (resendCooldown <= 0) {
                resendBtn.disabled = false;
                resendBtn.innerText = 'Resend OTP';
                clearInterval(interval);
            }
        }, 1000);
    }
}
window.onload = startCooldown;
</script>
</head>
<body>

<form class="login-container" method="POST" action="">
    <div class="login-header">
        <img src="assets/img/logo-small.png" alt="Logo" class="login-logo">
        <h1 class="system-title">Administrator Evaluation System</h1>
        <h2 class="login-title">OTP Verification</h2>
    </div>

    <?php if ($error): ?>
        <div class="error-message"><?php echo $error; ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
        <div class="success-message"><?php echo $success; ?></div>
    <?php endif; ?>

    <p>We’ve sent a 6-digit OTP to <b><?php echo htmlspecialchars($otp_email); ?></b></p>

    <div class="input-box">
        <input type="text" name="otp" placeholder="Enter OTP" maxlength="6" required autocomplete="off">
    </div>

    <div class="input-box">
        <label>Select OTP Delivery Method:</label>
        <select name="otp_method" required class="form-control">
            <option value="email" <?php echo (!empty($otp_email)) ? 'selected' : ''; ?>>Email</option>
            <option value="sms" <?php echo (!empty($contact_number) && empty($otp_email)) ? 'selected' : ''; ?>>SMS</option>
        </select>
    </div>

    <button type="submit" name="verify_otp" class="login-button">Verify OTP</button>
    <button type="submit" name="resend_otp" id="resendBtn" class="login-button" style="margin-top:10px;">Resend OTP</button>
</form>

</body>
</html>
