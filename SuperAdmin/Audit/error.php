<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Super Admin') {
    header('Location: ../../index.php');
    exit();
}

require_once '../../1database_conn/connection.php';
require_once '../../helper/log_action.php';

$errorMessage = null;
$successMessage = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['userFile'])) {
    if ($_FILES['userFile']['error'] === 0) {
        $file = fopen($_FILES['userFile']['tmp_name'], 'r');

        // Read header row & validate
        $header = fgetcsv($file);
        $expectedHeader = ['fullName', 'email', 'password', 'role', 'status', 'can_be_evaluated'];
        $normalizedHeader = array_map('strtolower', array_map('trim', $header));
        $normalizedExpected = array_map('strtolower', $expectedHeader);

        if ($normalizedHeader !== $normalizedExpected) {
            fclose($file);
            $errorMessage = "Invalid CSV format. Please use the provided template.";
        } else {
            while (($row = fgetcsv($file)) !== FALSE) {
                $fullName = trim($row[0]);
                $email = trim($row[1]);
                $rawPassword = trim($row[2]);
                $role = trim($row[3]);
                $status = trim($row[4]);
                $can_be_evaluated = (int)$row[5];

                if (empty($fullName) || !filter_var($email, FILTER_VALIDATE_EMAIL) || empty($rawPassword)) {
                    continue;
                }

                $password = password_hash($rawPassword, PASSWORD_BCRYPT);

                $otp = null;
                if (strtolower($status) === 'pending') {
                    $otp = rand(100000, 999999);
                }

                $stmt = $conn->prepare("INSERT INTO users (fullName, email, password, role, status, can_be_evaluated) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("sssssi", $fullName, $email, $password, $role, $status, $can_be_evaluated);
                $stmt->execute();
                $newUserId = $stmt->insert_id;
                $stmt->close();

                if ($otp) {
                    $otpStmt = $conn->prepare("INSERT INTO otp_verifications (user_id, otp_code, expires_at) VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 10 MINUTE))");
                    $otpStmt->bind_param("is", $newUserId, $otp);
                    $otpStmt->execute();
                    $otpStmt->close();

                    $subject = "Your Verification Code";
                    $message = "Hello $fullName,\n\nYour OTP is: $otp\nThis code will expire in 10 minutes.\n\n- Admin Eval System";
                    $headers = "From: no-reply@yourdomain.com\r\n";
                    mail($email, $subject, $message, $headers);
                }
            }
            fclose($file);

            log_action($conn, $_SESSION['user_id'], 'Upload Users', null, null, 'Bulk user upload via CSV');

            $successMessage = "Users uploaded successfully. Pending accounts have been sent OTP emails.";
        }
    }
}
?>
