<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor/phpmailer/phpmailer/src/Exception.php';
require 'vendor/phpmailer/phpmailer/src/PHPMailer.php';
require 'vendor/phpmailer/phpmailer/src/SMTP.php';

function sendNotificationEmail($to_email, $to_name, $subject, $body_html) {
    $mail = new PHPMailer(true);

    try {
        // --- GMAIL ÜÇÜN SERVER AYARLARI ---
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';                     // Gmail-in SMTP serveri
        $mail->SMTPAuth   = true;
        $mail->Username   = $_ENV['GMAIL_USER']; // .env faylından gəlir
$mail->Password   = $_ENV['GMAIL_APP_PASSWORD']; // .env faylından gəlir
$mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;           // SSL istifadə olunur
        $mail->Port       = 465;                                  // SSL üçün Gmail portu
        $mail->CharSet    = 'UTF-8';

        // --- GÖNDƏRƏN VƏ ALAN ---
        // Göndərən olaraq da öz Gmail adresinizi yazmağınız tövsiyə olunur
        $mail->setFrom($_ENV['GMAIL_USER'], 'Stiker Fikiri');
        $mail->addAddress($to_email, $to_name);

        // --- MƏZMUN ---
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $body_html;
        $mail->AltBody = strip_tags($body_html);

        $mail->send();
        return true;
    } catch (Exception $e) {
        // Xətanı loga yazmaq daha yaxşıdır
        error_log("Message could not be sent. Mailer Error: {$mail->ErrorInfo}");
        return false;
    }
}
?>