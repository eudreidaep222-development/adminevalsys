<?php
use PHPMailer\PHPMailer\PHPMailer;
require_once __DIR__ . '/../vendor/autoload.php';

function sendEmail($to, $subject, $body) {
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host = "smtp.example.com"; // Replace with your SMTP
        $mail->SMTPAuth = true;
        $mail->Username = "your-email@example.com";
        $mail->Password = "your-smtp-password";
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;

        $mail->setFrom("no-reply@example.com", "Admin Eval System");
        $mail->addAddress($to);

        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body = $body;

        return $mail->send();
    } catch (Exception $e) {
        error_log("Mail Error: " . $mail->ErrorInfo);
        return false;
    }
}
