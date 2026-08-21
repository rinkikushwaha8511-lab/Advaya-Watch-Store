<?php

require_once __DIR__ . '/includes/mailer.php';

// Jis email par test mail receive karna hai
$to = 'rinkikushwaha8511@gmail.com';

$subject = 'Advaya Watches - Email Test';

$body = '
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Advaya Email Test</title>
</head>
<body>

    <h2>Welcome to Advaya Watches</h2>

    <p>This is a test email from your Advaya Watch E-Commerce project.</p>

    <p>
        If you received this email, your PHPMailer
        and Gmail SMTP integration is working successfully.
    </p>

    <p>Thank you!</p>

    <strong>Advaya Watches</strong>

</body>
</html>
';

if (sendEmail($to, $subject, $body)) {

    echo "<h2>Email sent successfully! ✅</h2>";
    echo "<p>Check the recipient inbox.</p>";

} else {

    echo "<h2>Email failed to send ❌</h2>";
    echo "<p>Please check the PHP error log.</p>";
}