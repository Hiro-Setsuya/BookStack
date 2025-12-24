<?php
// PHPMailer configuration for BookStack
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

// Load PHPMailer
require_once __DIR__ . '/../lib/PHPMailer-master/src/Exception.php';
require_once __DIR__ . '/../lib/PHPMailer-master/src/PHPMailer.php';
require_once __DIR__ . '/../lib/PHPMailer-master/src/SMTP.php';

// Function to send email using PHPMailer
function sendEmail($to, $subject, $message, $fromName = 'BookStack')
{
    $mail = new PHPMailer(true);

    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'nullbyte235@gmail.com';
        $mail->Password   = 'mije slqy qkpo gwvy';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        // Recipients
        $mail->setFrom('nullbyte235@gmail.com', $fromName);
        $mail->addAddress($to);

        // Content
        $mail->isHTML(true);
        $mail->CharSet = 'UTF-8';
        $mail->Subject = $subject;
        $mail->Body    = $message;

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Email could not be sent. Error: {$mail->ErrorInfo}");
        return false;
    }
}

// Return configured settings for reference
return [
    'from_email' => 'nullbyte235@gmail.com',
    'from_name' => 'BookStack',
    'smtp_host' => 'smtp.gmail.com',
    'smtp_port' => 587,
    'smtp_username' => 'nullbyte235@gmail.com'
];
