<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Load PHPMailer
require_once __DIR__ . '/../vendor/autoload.php';

/**
 * Send email using Gmail SMTP
 *
 * @param string $to       Receiver email
 * @param string $subject  Email subject
 * @param string $body     Email HTML body
 * @return bool
 */
function sendEmail($to, $subject, $body)
{
    // Load email configuration
    $mailConfig = require __DIR__ . '/../config/mail.php';

    // Make sure configuration is an array
    if (!is_array($mailConfig)) {
        error_log('Mail configuration error: config/mail.php must return an array.');
        return false;
    }

    $mail = new PHPMailer(true);

    try {

        // =========================
        // SMTP CONFIGURATION
        // =========================

        $mail->isSMTP();

        $mail->Host = $mailConfig['host'];
        $mail->SMTPAuth = true;
        $mail->Username = $mailConfig['username'];
        $mail->Password = $mailConfig['password'];

        // TLS encryption
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = $mailConfig['port'];

        // =========================
        // SENDER
        // =========================

        $mail->setFrom(
            $mailConfig['from_email'],
            $mailConfig['from_name']
        );

        // =========================
        // RECEIVER
        // =========================

        $mail->addAddress($to);

        // =========================
        // EMAIL CONTENT
        // =========================

        $mail->isHTML(true);

        $mail->CharSet = 'UTF-8';

        $mail->Subject = $subject;

        $mail->Body = $body;

        // Plain text fallback
        $mail->AltBody = strip_tags($body);

        // =========================
        // SEND EMAIL
        // =========================

        $mail->send();

        return true;

    } catch (Exception $e) {

        // Save error in PHP error log
        error_log(
            'Advaya Email Error: ' . $mail->ErrorInfo
        );

        return false;
    }
}