<?php
// This file is a template for the account creation email.
// Variables to be passed into this template:
// $customerName, $customerEmail, $password, $loginLink, $companyName

$emailBody = <<<EOT
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome to {$companyName}!</title>
    <style>
        body { font-family: 'Arial', sans-serif; background-color: #f4f4f4; margin: 0; padding: 0; }
        .email-container { max-width: 600px; margin: 20px auto; background-color: #ffffff; border-radius: 8px; overflow: hidden; box-shadow: 0 0 10px rgba(0, 0, 0, 0.1); }
        .header { background-color: #1a73e8; padding: 30px; color: white; text-align: center; }
        .header h1 { margin: 0; font-size: 28px; }
        .content { padding: 30px; color: #333333; line-height: 1.6; }
        .content h2 { color: #1a73e8; font-size: 24px; margin-top: 0; }
        .info-box { background-color: #e0f7fa; border: 1px solid #b2ebf2; padding: 15px; border-radius: 5px; margin: 20px 0; }
        .info-box p { margin: 5px 0; }
        .button-container { text-align: center; margin-top: 25px; }
        .button { display: inline-block; padding: 12px 25px; border-radius: 5px; background-color: #34a853; color: white; text-decoration: none; font-weight: bold; font-size: 16px; transition: background-color 0.3s ease; }
        .button:hover { background-color: #2b8e45; }
        .footer { background-color: #f0f0f0; padding: 20px; text-align: center; font-size: 12px; color: #666666; border-top: 1px solid #e0e0e0; }
        .footer a { color: #1a73e8; text-decoration: none; }
    </style>
</head>
<body>
    <div class="email-container">
        <div class="header">
            <h1>Welcome to {$companyName}!</h1>
        </div>
        <div class="content">
            <p>Dear {$customerName},</p>
            <p>Thank you for choosing {$companyName} for your equipment rental and junk removal needs! Your account has been successfully created.</p>

            <h2>Your Account Details:</h2>
            <div class="info-box">
                <p><strong>Email:</strong> {$customerEmail}</p>
                <p><strong>Temporary Password:</strong> <strong>{$password}</strong></p>
            </div>

            <p>For security, we recommend changing your password after your first login. You can log in to your personalized customer dashboard using the link below:</p>

            <div class="button-container">
                <a href="{$loginLink}" class="button">Go to Dashboard</a>
            </div>

            <p style="margin-top: 30px;">If you have any questions, feel free to contact us.</p>
            <p>We look forward to serving you!</p>
            <p>The {$companyName} Team</p>
        </div>
        <div class="footer">
            <p>&copy; {$companyName} 2025. All rights reserved.</p>
            <p>123 Equipment Rd, Rental City, ST 12345</p>
            <p><a href="#">Privacy Policy</a> | <a href="#">Terms of Service</a></p>
        </div>
    </div>
</body>
</html>
EOT;

echo $emailBody;