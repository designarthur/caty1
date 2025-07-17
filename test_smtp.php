<?php
// test_smtp.php

// Include the functions file which contains sendEmail and loads .env
require_once __DIR__ . '/includes/functions.php';

// Set recipient email (replace with an email you can access)
$to_email = 'webdesigner.xpt@gmail.com';
$subject = 'SMTP Test from Catdump Application';
$body = '<p>This is a test email sent from your Catdump application to verify SMTP settings.</p><p>If you received this, your SMTP configuration is working!</p>';
$alt_body = 'This is a test email sent from your Catdump application to verify SMTP settings. If you received this, your SMTP configuration is working!';

echo "Attempting to send a test email to: " . htmlspecialchars($to_email) . "<br>";

if (sendEmail($to_email, $subject, $body, $alt_body)) {
    echo "Test email sent successfully! Please check your inbox (and spam folder).";
} else {
    echo "Failed to send test email. Check your SMTP settings in the .env file and server error logs for details.";
}
?>