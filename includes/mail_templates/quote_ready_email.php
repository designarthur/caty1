<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Your Quote from <?php echo htmlspecialchars($template_companyName); ?> is Ready!</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 20px auto; padding: 20px; border: 1px solid #ddd; border-radius: 8px; background-color: #f9f9f9; }
        .header { background-color: #0056b3; color: #ffffff; padding: 10px 20px; text-align: center; border-radius: 8px 8px 0 0; }
        .content { padding: 20px; }
        .button { display: inline-block; background-color: #007bff; color: #ffffff; padding: 10px 20px; text-decoration: none; border-radius: 5px; }
        .footer { text-align: center; font-size: 0.8em; color: #777; margin-top: 20px; border-top: 1px solid #eee; padding-top: 10px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h2>Your Quote from <?php echo htmlspecialchars($template_companyName); ?> is Ready!</h2>
        </div>
        <div class="content">
            <p>Dear Customer,</p>
            <p>Good news! Your quote request #Q<strong><?php echo htmlspecialchars($template_quoteId); ?></strong> has been processed.</p>
            <p>We are pleased to offer you a quotation of:</p>
            <p style="text-align: center; font-size: 1.5em; font-weight: bold; color: #28a745;">$<?php echo htmlspecialchars($template_quotedPrice); ?></p>
            <?php if (!empty($template_adminNotes)): ?>
                <p><span style="font-weight: bold;">Notes from our team:</span></p>
                <p style="background-color: #e9ecef; padding: 10px; border-left: 5px solid #007bff; white-space: pre-wrap;"><?php echo nl2br(htmlspecialchars($template_adminNotes)); ?></p>
            <?php endif; ?>
            <p>Please review the full details and accept or reject the quote by clicking the button below:</p>
            <p style="text-align: center; margin-top: 20px;">
                <a href="<?php echo htmlspecialchars($template_customerQuoteLink); ?>" class="button">View & Respond to Your Quote</a>
            </p>
            <p>You can also log in to your account dashboard at any time to view this and other requests.</p>
            <p>If you have any questions, please do not hesitate to contact us.</p>
            <p>Sincerely,<br><?php echo htmlspecialchars($template_companyName); ?> Team</p>
        </div>
        <div class="footer">
            <p>&copy; <?php echo date('Y'); ?> <?php echo htmlspecialchars($template_companyName); ?>. All rights reserved.</p>
        </div>
    </div>
</body>
</html>