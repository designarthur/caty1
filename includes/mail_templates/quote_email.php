<?php
// This file is a template for the quote email.
// It should be included and its content used as the $body in sendEmail() function.

// Variables to be passed into this template:
// $customerName, $quoteDetails (array), $quotedPrice, $quoteLink, $rejectLink, $companyName

$quoteHtml = '';
foreach ($quoteDetails as $key => $value) {
    // Basic formatting, you can enhance this based on your quoteDetails structure
    $quoteHtml .= "<li><strong>" . ucwords(str_replace('_', ' ', $key)) . ":</strong> " . htmlspecialchars($value) . "</li>";
}

$emailBody = <<<EOT
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Your New Quote from {$companyName}</title>
    <style>
        body { font-family: 'Arial', sans-serif; background-color: #f4f4f4; margin: 0; padding: 0; }
        .email-container { max-width: 600px; margin: 20px auto; background-color: #ffffff; border-radius: 8px; overflow: hidden; box-shadow: 0 0 10px rgba(0, 0, 0, 0.1); }
        .header { background-color: #1a73e8; padding: 30px; color: white; text-align: center; }
        .header h1 { margin: 0; font-size: 28px; }
        .content { padding: 30px; color: #333333; line-height: 1.6; }
        .content h2 { color: #1a73e8; font-size: 24px; margin-top: 0; }
        .content ul { list-style: none; padding: 0; }
        .content ul li { margin-bottom: 10px; }
        .price-box { background-color: #e0f2f7; border: 2px solid #81d4fa; padding: 20px; border-radius: 8px; text-align: center; margin: 25px 0; }
        .price-box p { margin: 0; font-size: 18px; color: #0277bd; }
        .price-box .price { font-size: 48px; font-weight: bold; color: #1a73e8; margin: 10px 0; }
        .button-container { text-align: center; margin-top: 25px; }
        .button { display: inline-block; padding: 12px 25px; border-radius: 5px; text-decoration: none; font-weight: bold; font-size: 16px; margin: 0 10px; transition: background-color 0.3s ease; }
        .accept-button { background-color: #34a853; color: white; }
        .accept-button:hover { background-color: #2b8e45; }
        .reject-button { background-color: #f44336; color: white; }
        .reject-button:hover { background-color: #d32f2f; }
        .footer { background-color: #f0f0f0; padding: 20px; text-align: center; font-size: 12px; color: #666666; border-top: 1px solid #e0e0e0; }
        .footer a { color: #1a73e8; text-decoration: none; }
    </style>
</head>
<body>
    <div class="email-container">
        <div class="header">
            <h1>New Quote from {$companyName}</h1>
        </div>
        <div class="content">
            <p>Dear {$customerName},</p>
            <p>We're excited to inform you that your personalized quote for your recent request is ready! Our team has worked hard to get you the best price for your needs.</p>

            <h2>Quote Details:</h2>
            <ul>
                {$quoteHtml}
            </ul>

            <div class="price-box">
                <p>Your Personalized Quote:</p>
                <p class="price">\${$quotedPrice}</p>
            </div>

            <p>Please review the details above. You can accept or reject this quote using the buttons below, or by logging into your <a href="{$quoteLink}" style="color: #1a73e8;">Catdump Customer Dashboard</a>.</p>

            <div class="button-container">
                <a href="{$quoteLink}" class="button accept-button">Accept Quote</a>
                <a href="{$rejectLink}" class="button reject-button">Reject Quote</a>
            </div>

            <p style="margin-top: 30px;">If you have any questions or need further assistance, please do not hesitate to reply to this email or contact our support team.</p>
            <p>Thank you for choosing {$companyName}!</p>
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