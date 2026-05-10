<?php
$receiver_email = "chanblue8898@gmail.com";
$subject = "burat";
$body = "aslkdjaslkdjaslkdj";
$altbody = "";

require '../packages/PHPMailer-6.10.0/src/PHPMailer.php';
require '../packages/PHPMailer-6.10.0/src/SMTP.php';
require '../packages/PHPMailer-6.10.0/src/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Create a new PHPMailer instance
$mail = new PHPMailer(true);

$host = 'smtp.gmail.com';
$your_email_address = "cazul855@gmail.com";
$app_password = 'cycc zpds sxkr sqik';
$smtpSecure = 'ssl';
$port = 465; // 587 for TLS, 465 for SSL

try {
    // Server settings
    $mail->isSMTP();
    $mail->Host       = $host;
    $mail->SMTPAuth   = true;
    $mail->Username   = $your_email_address;
    $mail->Password   = $app_password;
    $mail->SMTPSecure = $smtpSecure;
    $mail->Port       = $port;

    // Recipients
    $mail->setFrom($your_email_address, 'Alphaminera');
    $mail->addAddress($receiver_email, 'Receiver');

    // File attachment (absolute or relative path)
    $mail->addAttachment('../files/test.pdf'); // Example file

    // Email content
    $mail->isHTML(true);
    $mail->Subject = $subject;
    $mail->Body    = $body;
    $mail->AltBody = $altbody;

    $mail->send();
    echo '✅ Email sent successfully with attachment.';
} catch (Exception $e) {
    echo "❌ Message could not be sent. Error: {$mail->ErrorInfo}";
}
