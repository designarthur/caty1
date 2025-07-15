<?php
// customer/forgot_password.php
require_once '../includes/db.php';
require_once '../includes/functions.php';
require_once '../includes/session.php'; // For session_start() and CSRF token

$message = ''; // To display success or error messages
$show_reset_form = false;
$reset_token = '';

// Handle password reset form submission (when user sets new password)
if (isset($_POST['action']) && $_POST['action'] === 'reset_password') {
    try {
        validate_csrf_token();

        $token_from_form = filter_input(INPUT_POST, 'token', FILTER_SANITIZE_STRING);
        $new_password = $_POST['new_password'];
        $confirm_new_password = $_POST['confirm_new_password'];

        if (empty($token_from_form) || empty($new_password) || empty($confirm_new_password)) {
            throw new Exception("Please fill in all fields.");
        }
        if ($new_password !== $confirm_new_password) {
            throw new Exception("New passwords do not match.");
        }
        if (strlen($new_password) < 8) {
            throw new Exception("Password must be at least 8 characters long.");
        }

        // Validate token from password_resets table
        $stmt = $conn->prepare("SELECT user_id, expires_at FROM password_resets WHERE token = ? AND expires_at > NOW()");
        if (!$stmt) {
            throw new Exception("Database prepare error: " . $conn->error);
        }
        $stmt->bind_param("s", $token_from_form);
        $stmt->execute();
        $result = $stmt->get_result();
        $reset_data = $result->fetch_assoc();
        $stmt->close();

        if (!$reset_data) {
            throw new Exception("Invalid or expired password reset token.");
        }

        $user_id = $reset_data['user_id'];
        $hashed_password = hashPassword($new_password);

        // Update user's password
        $conn->begin_transaction();
        $stmt = $conn->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
        if (!$stmt) {
            throw new Exception("Database prepare error: " . $conn->error);
        }
        $stmt->bind_param("si", $hashed_password, $user_id);
        if (!$stmt->execute()) {
            throw new Exception("Failed to update password: " . $stmt->error);
        }
        $stmt->close();

        // Invalidate the token
        $stmt = $conn->prepare("DELETE FROM password_resets WHERE token = ?");
        if (!$stmt) {
            throw new Exception("Database prepare error: " . $conn->error);
        }
        $stmt->bind_param("s", $token_from_form);
        if (!$stmt->execute()) {
            throw new Exception("Failed to invalidate token: " . $stmt->error);
        }
        $stmt->close();
        $conn->commit();

        $message = "<div class='bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4' role='alert'>Your password has been reset successfully. You can now log in.</div>";
        // Redirect to login page after success
        redirect('login.php?reset=success');

    } catch (Exception $e) {
        $conn->rollback(); // Rollback transaction on error
        $message = "<div class='bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4' role='alert'>" . htmlspecialchars($e->getMessage()) . "</div>";
        // If token was invalid/expired, ensure user sees reset form with error
        $show_reset_form = true;
        $reset_token = $token_from_form; // Keep token in form for re-submission if it was valid but another error occurred
    }
}
// Handle request for password reset link (when user enters email)
else if (isset($_POST['action']) && $_POST['action'] === 'request_reset') {
    try {
        validate_csrf_token();

        $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);

        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception("Please enter a valid email address.");
        }

        // Find user by email
        $stmt = $conn->prepare("SELECT id, first_name FROM users WHERE email = ?");
        if (!$stmt) {
            throw new Exception("Database prepare error: " . $conn->error);
        }
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        $stmt->close();

        if ($user) {
            $user_id = $user['id'];
            $token = generateToken(64); // Generate a secure token
            $expires_at = date('Y-m-d H:i:s', strtotime('+1 hour')); // Token valid for 1 hour

            // Invalidate any existing tokens for this user
            $stmt = $conn->prepare("DELETE FROM password_resets WHERE user_id = ?");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $stmt->close();

            // Store new token in database
            $stmt = $conn->prepare("INSERT INTO password_resets (user_id, token, expires_at) VALUES (?, ?, ?)");
            if (!$stmt) {
                throw new Exception("Database prepare error: " . $conn->error);
            }
            $stmt->bind_param("iss", $user_id, $token, $expires_at);
            if (!$stmt->execute()) {
                throw new Exception("Failed to save reset token: " . $stmt->error);
            }
            $stmt->close();

            $reset_link = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://{$_SERVER['HTTP_HOST']}/customer/forgot_password.php?token={$token}";
            $email_subject = "Catdump Password Reset Request";
            $email_body = "
                <p>Dear {$user['first_name']},</p>
                <p>You have requested a password reset for your Catdump account.</p>
                <p>Please click on the following link to reset your password:</p>
                <p><a href='{$reset_link}'>{$reset_link}</a></p>
                <p>This link is valid for 1 hour. If you did not request this, please ignore this email.</p>
                <p>Thank you,<br>The Catdump Team</p>
            ";

            if (sendEmail($email, $email_subject, $email_body)) {
                $message = "<div class='bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4' role='alert'>If an account with that email exists, a password reset link has been sent. Please check your inbox.</div>";
            } else {
                throw new Exception("Failed to send reset email. Please try again later.");
            }
        } else {
            // For security, always give a generic success message even if email not found
            $message = "<div class='bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4' role='alert'>If an account with that email exists, a password reset link has been sent. Please check your inbox.</div>";
        }

    } catch (Exception $e) {
        $message = "<div class='bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4' role='alert'>" . htmlspecialchars($e->getMessage()) . "</div>";
    }
}
// Check if a token is present in the URL (user clicked reset link)
else if (isset($_GET['token'])) {
    $reset_token = filter_input(INPUT_GET, 'token', FILTER_SANITIZE_STRING);

    if (empty($reset_token)) {
        $message = "<div class='bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4' role='alert'>Invalid password reset link.</div>";
    } else {
        // Verify token validity from database
        $stmt = $conn->prepare("SELECT user_id, expires_at FROM password_resets WHERE token = ? AND expires_at > NOW()");
        if (!$stmt) {
            $message = "<div class='bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4' role='alert'>Database error during token validation.</div>";
        } else {
            $stmt->bind_param("s", $reset_token);
            $stmt->execute();
            $result = $stmt->get_result();
            $reset_data = $result->fetch_assoc();
            $stmt->close();

            if ($reset_data) {
                $show_reset_form = true; // Show the new password form
            } else {
                $message = "<div class='bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4' role='alert'>Invalid or expired password reset token. Please request a new one.</div>";
            }
        }
    }
}

// Generate CSRF token for the form
$csrf_token = generate_csrf_token();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - Catdump Customer Portal</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        /* Page-specific styles for forgot password form */
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f8f9fa;
            color: #2d3748;
            overflow-x: hidden;
            line-height: 1.6;
        }

        .form-input {
            width: 100%;
            padding: 0.8rem 1rem;
            border: 1px solid #e2e8f0;
            border-radius: 0.5rem;
            margin-top: 0.5rem;
            margin-bottom: 1.5rem;
            font-size: 1rem;
            color: #2d3748;
            transition: border-color 0.2s ease, box-shadow 0.2s ease;
        }
        .form-input:focus {
            outline: none;
            border-color: #1a73e8;
            box-shadow: 0 0 0 3px rgba(26, 115, 232, 0.2);
        }
        .form-label {
            display: block;
            font-size: 1rem;
            font-weight: 500;
            color: #4a5568;
        }
        .forgot-password-container {
            max-width: 500px;
            margin: 5rem auto;
            background-color: #ffffff;
            border-radius: 1.5rem;
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.08);
            padding: 3rem;
            border: 1px solid rgba(0, 0, 0, 0.05);
            text-align: center;
        }
        .forgot-password-container h2 {
            font-size: 2.5rem;
            font-weight: 800;
            margin-bottom: 2rem;
            color: #2d3748;
        }
        .forgot-password-container .btn-submit {
            background-color: #1a73e8;
            color: white;
            padding: 1rem 2.5rem;
            border-radius: 0.75rem;
            font-weight: 700;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(26, 115, 232, 0.4);
            width: 100%;
            margin-top: 2rem;
        }
        .forgot-password-container .btn-submit:hover {
            background-color: #155bb5;
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(26, 115, 232, 0.6);
        }
        .forgot-password-container .privacy-text {
            font-size: 0.85rem;
            color: #718096;
            margin-top: 1.5rem;
        }
        .forgot-password-container .privacy-text a {
            color: #1a73e8;
            text-decoration: underline;
        }
        .text-blue-custom {
            color: #1a73e8;
        }
    </style>
</head>
<body class="antialiased">

    <?php include '../includes/public_header.php'; ?>

    <main class="py-12">
        <div class="forgot-password-container">
            <img src="../assets/images/logo.png" alt="Catdump Logo" class="mx-auto h-24 w-auto mb-6">
            <?php echo $message; ?>

            <?php if ($show_reset_form): ?>
                <h2>Reset Your Password</h2>
                <form action="forgot_password.php" method="POST">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                    <input type="hidden" name="action" value="reset_password">
                    <input type="hidden" name="token" value="<?php echo htmlspecialchars($reset_token); ?>">
                    <div>
                        <label for="new_password" class="form-label text-left block">New Password</label>
                        <input type="password" id="new_password" name="new_password" class="form-input" placeholder="••••••••" required>
                    </div>
                    <div>
                        <label for="confirm_new_password" class="form-label text-left block">Confirm New Password</label>
                        <input type="password" id="confirm_new_password" name="confirm_new_password" class="form-input" placeholder="••••••••" required>
                    </div>
                    <button type="submit" class="btn-submit">Set New Password</button>
                </form>
            <?php else: ?>
                <h2>Forgot Your Password?</h2>
                <p class="text-gray-600 mb-6">Enter your email address below and we'll send you a link to reset your password.</p>
                <form action="forgot_password.php" method="POST">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                    <input type="hidden" name="action" value="request_reset">
                    <div>
                        <label for="email" class="form-label text-left block">Email Address</label>
                        <input type="email" id="email" name="email" class="form-input" placeholder="your@example.com" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" required>
                    </div>
                    <button type="submit" class="btn-submit">Send Reset Link</button>
                </form>
                <p class="mt-8 text-gray-700">Remember your password? <a href="login.php" class="text-blue-custom font-semibold hover:underline">Log In</a></p>
            <?php endif; ?>
            <p class="privacy-text">By proceeding, you agree to our <a href="../PrivacyPolicy.html">Privacy Policy</a> and <a href="../Terms and Conditions.html">Terms and Conditions</a>.</p>
        </div>
    </main>

    <?php include '../includes/public_footer.php'; ?>

</body>
</html>