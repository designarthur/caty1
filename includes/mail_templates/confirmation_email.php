<?php
// This file is a template for the account creation email.
// Variables to be passed into this template:
// $customerName, $customerEmail, $loginLink, $companyName
// Optional: $password (if set, assumes a temporary password is being provided)

// Ensure variables are defined for the template, providing defaults for robustness
$customerName = $customerName ?? 'Valued Customer';
$customerEmail = $customerEmail ?? 'your_email@example.com';
$loginLink = $loginLink ?? '#';
$companyName = $companyName ?? 'Your Company Name';
$password = $password ?? ''; // Default to empty string if not provided

$showTemporaryPassword = !empty($password);

// --- Build the conditional password section HTML outside the main Heredoc ---
$passwordSectionHtml = '';
if ($showTemporaryPassword) {
    $passwordSectionHtml = '<p><strong>Temporary Password:</strong> <strong>' . htmlspecialchars($password) . '</strong></p>';
} else {
    $passwordSectionHtml = '<p>You can now log in using the password you chose during registration.</p>';
}
// --- End conditional password section HTML build ---


$emailBody = <<<EOT
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome to {$companyName}!</title>
    <style>
        body {
            font-family: 'Arial', sans-serif;
            background-color: #f4f7f6;
            margin: 0;
            padding: 0;
            -webkit-text-size-adjust: 100%;
            -ms-text-size-adjust: 100%;
        }
        table {
            border-collapse: collapse;
            mso-table-lspace: 0pt;
            mso-table-rspace: 0pt;
            width: 100%;
        }
        td {
            padding: 0;
            font-size: 14px;
            line-height: 20px;
            color: #333333;
        }
        .email-container {
            max-width: 600px;
            margin: 0 auto;
            background-color: #ffffff;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
        }
        .header {
            background-color: #1a73e8; /* Blue header */
            padding: 30px;
            color: white;
            text-align: center;
            border-radius: 8px 8px 0 0;
        }
        .header h1 {
            margin: 0;
            font-size: 28px;
            font-weight: bold;
        }
        .header img {
            max-width: 60px;
            margin-bottom: 15px;
        }
        .content {
            padding: 30px;
            color: #333333;
            line-height: 1.6;
        }
        .content h2 {
            color: #1a73e8; /* Blue heading */
            font-size: 24px;
            margin-top: 0;
            margin-bottom: 20px;
        }
        .info-box {
            background-color: #e0f7fa; /* Light blue background */
            border: 1px solid #b2ebf2;
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
            word-break: break-word; /* For long emails/passwords */
        }
        .info-box p {
            margin: 5px 0;
        }
        .info-box strong {
            color: #1a73e8;
        }
        .button-container {
            text-align: center;
            margin-top: 25px;
            margin-bottom: 20px;
        }
        .button {
            display: inline-block;
            padding: 12px 25px;
            border-radius: 5px;
            background-color: #34a853; /* Green button */
            color: white !important; /* !important for email client compatibility */
            text-decoration: none;
            font-weight: bold;
            font-size: 16px;
            transition: background-color 0.3s ease;
        }
        .button:hover {
            background-color: #2b8e45; /* Darker green on hover */
        }
        .footer {
            background-color: #f0f0f0;
            padding: 20px;
            text-align: center;
            font-size: 12px;
            color: #666666;
            border-top: 1px solid #e0e0e0;
            border-radius: 0 0 8px 8px;
        }
        .footer a {
            color: #1a73e8;
            text-decoration: none;
        }
    </style>
</head>
<body>
    <div class="email-container">
        <div class="header">
            <img src="https://placehold.co/60x60/1a73e8/ffffff?text=CD" alt="Company Logo" style="vertical-align: middle; border-radius: 50%; max-width: 60px; height: auto;">
            <h1>Welcome to {$companyName}!</h1>
        </div>
        <div class="content">
            <p>Dear {$customerName},</p>
            <p>Thank you for choosing {$companyName} for your equipment rental and junk removal needs! Your account has been successfully created.</p>

            <h2>Your Account Details:</h2>
            <div class="info-box">
                <p><strong>Email:</strong> {$customerEmail}</p>
                {$passwordSectionHtml}
            </div>

            <p>You can log in to your personalized customer dashboard using the link below:</p>

            <div class="button-container">
                <a href="{$loginLink}" class="button">Go to Dashboard</a>
            </div>

            <p style="margin-top: 30px;">If you have any questions, feel free to contact us.</p>
            <p>We look forward to serving you!</p>
            <p>The {$companyName} Team</p>
        </div>
        <div class="footer">
            <p>&copy; {$companyName} 2025. All rights reserved.</p>
            <p style="margin-top: 5px;"><a href="#" style="color: #1a73e8; text-decoration: none;">Privacy Policy</a> | <a href="#" style="color: #1a73e8; text-decoration: none;">Terms of Service</a></p>
        </div>
    </div>
</body>
</html>
EOT;

echo $emailBody;