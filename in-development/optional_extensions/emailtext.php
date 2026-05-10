<?php
$receiver_email = "cazul855@gmail.com";
$subject = "burat ay mataba";
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
$port = 465;        // 587 for TLS, 465 for SSL
try {
    // Server settings
    $mail->isSMTP();
    $mail->Host       = $host;       // e.g. smtp.gmail.com
    $mail->SMTPAuth   = true;
    $mail->Username   = $your_email_address; // Your email address
    $mail->Password   = $app_password;    // Your email/app password
    $mail->SMTPSecure = $smtpSecure;                    // 'tls' or 'ssl'
    $mail->Port       = $port;                      

    // Recipients
    $mail->setFrom( $your_email_address, 'Alphaminera');
    $mail->addAddress($receiver_email, 'Receiver');

    // Email content
    $mail->isHTML(true);
    $mail->Subject = $subject;
    $mail->Body    = $body;
    $mail->AltBody = $altbody;

    $mail->send();
    echo 'Email sent successfully!';
} catch (Exception $e) {
    echo "Message could not be sent. Error: {$mail->ErrorInfo}";
}
