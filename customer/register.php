<?php
// customer/register.php
require_once '../includes/db.php';
require_once '../includes/functions.php';
require_once '../includes/session.php'; // For session_start() and CSRF token

if (isset($_SESSION['user_id'])) {
    redirect('dashboard.php'); // Redirect if already logged in
}

$message = ''; // To display success or error messages

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        validate_csrf_token(); // Validate CSRF token

        $first_name = isset($_POST['first_name']) ? strip_tags(trim($_POST['first_name'])) : '';
        $last_name = isset($_POST['last_name']) ? strip_tags(trim($_POST['last_name'])) : '';
        $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
        $password = $_POST['password']; // Keep original for hashing
        $confirm_password = $_POST['confirm_password'];

        // Basic validation
        if (empty($first_name) || empty($last_name) || empty($email) || empty($password) || empty($confirm_password)) {
            throw new Exception("Please fill in all fields.");
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception("Invalid email format.");
        }
        if ($password !== $confirm_password) {
            throw new Exception("Passwords do not match.");
        }
        if (strlen($password) < 8) {
            throw new Exception("Password must be at least 8 characters long.");
        }

        // Check if email already exists
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
        if (!$stmt) {
            throw new Exception("Database prepare error: " . $conn->error);
        }
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            throw new Exception("Email already registered. Please login or use a different email.");
        }
        $stmt->close();

        // Hash password
        $password_hash = hashPassword($password);

        // Insert new user into database
        $stmt = $conn->prepare("INSERT INTO users (first_name, last_name, email, password_hash, is_active) VALUES (?, ?, ?, ?, 1)");
        if (!$stmt) {
            throw new Exception("Database prepare error: " . $conn->error);
        }
        $stmt->bind_param("ssss", $first_name, $last_name, $email, $password_hash);
        
        if ($stmt->execute()) {
            $message = "<div class='bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4' role='alert'>Registration successful! You can now log in.</div>";
            
            // --- Send Confirmation Email to Customer ---
            $customerName = htmlspecialchars($first_name);
            $customerEmail = htmlspecialchars($email);
            $loginLink = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://{$_SERVER['HTTP_HOST']}/customer/login.php";
            
            // Get company name from system settings for the email
            $companyName = getSystemSetting('company_name');
            if (!$companyName) {
                $companyName = 'Catdump'; // Fallback if not set in DB
            }

            // Capture the email template content
            ob_start();
            // Pass an empty string for $password to indicate it's not a temporary password
            // The template will handle displaying appropriate message.
            include __DIR__ . '/../includes/mail_templates/confirmation_email.php';
            $emailBody = ob_get_clean();

            // Send the email
            if (!sendEmail($customerEmail, "Welcome to " . htmlspecialchars($companyName) . "!", $emailBody)) {
                error_log("Failed to send welcome email to {$customerEmail} on registration.");
            }
            // --- End Send Confirmation Email ---

            redirect('login.php?registration=success');
        } else {
            throw new Exception("Registration failed: " . $stmt->error);
        }
        $stmt->close();

    } catch (Exception $e) {
        $message = "<div class='bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4' role='alert'>" . htmlspecialchars($e->getMessage()) . "</div>";
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
    <title>Register - Catdump Customer Portal</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        /* Page-specific styles for registration form */
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
        .register-container {
            max-width: 600px;
            margin: 5rem auto;
            background-color: #ffffff;
            border-radius: 1.5rem;
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.08);
            padding: 3rem;
            border: 1px solid rgba(0, 0, 0, 0.05);
            text-align: center;
        }
        .register-container h2 {
            font-size: 2.5rem;
            font-weight: 800;
            margin-bottom: 2rem;
            color: #2d3748;
        }
        .register-container .btn-submit {
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
        .register-container .btn-submit:hover {
            background-color: #155bb5;
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(26, 115, 232, 0.6);
        }
        .register-container .privacy-text {
            font-size: 0.85rem;
            color: #718096;
            margin-top: 1.5rem;
        }
        .register-container .privacy-text a {
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
        <div class="register-container">
            <img src="../assets/images/logocatdump.png" alt="Catdump Logo" class="mx-auto h-24 w-auto mb-6">
            <h2>Create Your Account</h2>
            <?php echo $message; ?>
            <form action="register.php" method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label for="first_name" class="form-label text-left block">First Name</label>
                        <input type="text" id="first_name" name="first_name" class="form-input" placeholder="John" value="<?php echo htmlspecialchars($_POST['first_name'] ?? ''); ?>" required>
                    </div>
                    <div>
                        <label for="last_name" class="form-label text-left block">Last Name</label>
                        <input type="text" id="last_name" name="last_name" class="form-input" placeholder="Doe" value="<?php echo htmlspecialchars($_POST['last_name'] ?? ''); ?>" required>
                    </div>
                </div>
                <div>
                    <label for="email" class="form-label text-left block">Email Address</label>
                    <input type="email" id="email" name="email" class="form-input" placeholder="your@example.com" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" required>
                </div>
                <div>
                    <label for="password" class="form-label text-left block">Password</label>
                    <input type="password" id="password" name="password" class="form-input" placeholder="••••••••" required>
                </div>
                <div>
                    <label for="confirm_password" class="form-label text-left block">Confirm Password</label>
                    <input type="password" id="confirm_password" name="confirm_password" class="form-input" placeholder="••••••••" required>
                </div>
                <button type="submit" class="btn-submit">Register Account</button>
            </form>
            <p class="mt-8 text-gray-700">Already have an account? <a href="login.php" class="text-blue-custom font-semibold hover:underline">Log In</a></p>
            <p class="privacy-text">By registering, you agree to our <a href="../PrivacyPolicy.html">Privacy Policy</a> and <a href="../Terms and Conditions.html">Terms and Conditions</a>.</p>
        </div>
    </main>

    <?php include '../includes/public_footer.php'; ?>

</body>
</html>