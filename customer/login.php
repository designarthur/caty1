<?php
// customer/login.php
require_once '../includes/db.php';
require_once '../includes/functions.php';
require_once '../includes/session.php'; // Assuming session start and CSRF token generation are handled here

if (isset($_SESSION['user_id'])) {
    redirect('dashboard.php');
}

$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        validate_csrf_token(); // Validate CSRF token

        $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
        $password = $_POST['password']; // Password is handled by password_verify, so no sanitize here directly

        if (empty($email) || empty($password)) {
            throw new Exception("Please fill in all fields.");
        }

        // Fetch first_name, last_name, password_hash, is_active, AND role
        $stmt = $conn->prepare("SELECT id, first_name, last_name, password_hash, is_active, role FROM users WHERE email = ?");
        if (!$stmt) {
            throw new Exception("Database prepare error: " . $conn->error);
        }
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        $stmt->close();

        if ($user && verifyPassword($password, $user['password_hash'])) {
            if ($user['is_active']) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_first_name'] = $user['first_name'];
                $_SESSION['user_last_name'] = $user['last_name'];
                $_SESSION['user_email'] = $email;
                $_SESSION['user_role'] = $user['role']; // Store user role in session
                // Log login success
                // insert_audit_log($user['id'], 'login', 'User logged in successfully');
                redirect('dashboard.php');
            } else {
                throw new Exception("Your account is not active. Please contact support.");
            }
        } else {
            throw new Exception("Invalid email or password.");
        }
    } catch (Exception $e) {
        $message = "<div class='bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4' role='alert'>" . htmlspecialchars($e->getMessage()) . "</div>";
        // Log login attempt failure
        // insert_audit_log(null, 'login_failed', 'Login attempt failed for email: ' . $email . ' - ' . $e->getMessage());
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
    <title>Login - Catdump Customer Portal</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        /* This style block should mostly be for page-specific styles, not header/footer */
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f8f9fa;
            color: #2d3748;
            overflow-x: hidden;
            line-height: 1.6;
        }

        .container-box {
            max-width: 1280px;
            margin: 0 auto;
            padding: 1.5rem;
        }
        .section-box {
            background-color: #ffffff;
            border-radius: 1.5rem;
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.08);
            padding: 4rem;
            margin-bottom: 3.5rem;
            position: relative;
            overflow: hidden;
            border: 1px solid rgba(0, 0, 0, 0.05);
        }
        .section-box-alt {
            background-color: #eef2f6;
            border-radius: 1.5rem;
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.08);
            padding: 4rem;
            margin-bottom: 3.5rem;
            position: relative;
            overflow: hidden;
            border: 1px solid rgba(0, 0, 0, 0.05);
        }
        .btn-primary {
            background-color: #1a73e8;
            color: white;
            padding: 1.2rem 3.5rem;
            border-radius: 0.75rem;
            font-weight: 800;
            transition: all 0.3s ease;
            box-shadow: 0 8px 25px rgba(26, 115, 232, 0.4);
            letter-spacing: 0.05em;
            text-transform: uppercase;
        }
        .btn-primary:hover {
            background-color: #155bb5;
            transform: translateY(-7px);
            box-shadow: 0 15px 35px rgba(26, 115, 232, 0.6);
        }
        .btn-secondary {
            background-color: transparent;
            color: #1a73e8;
            padding: 1.2rem 3.5rem;
            border-radius: 0.75rem;
            font-weight: 700;
            transition: all 0.3s ease;
            border: 2px solid #1a73e8;
            letter-spacing: 0.05em;
            text-transform: uppercase;
        }
        .btn-secondary:hover {
            background-color: #1a73e8;
            color: white;
            transform: translateY(-7px);
            box-shadow: 0 8px 20px rgba(26, 115, 232, 0.2);
        }
        .text-blue-custom {
            color: #1a73e8;
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
        .login-container {
            max-width: 500px;
            margin: 5rem auto;
            background-color: #ffffff;
            border-radius: 1.5rem;
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.08);
            padding: 3rem;
            border: 1px solid rgba(0, 0, 0, 0.05);
            text-align: center;
        }
        .login-container h2 {
            font-size: 2.5rem;
            font-weight: 800;
            margin-bottom: 2rem;
            color: #2d3748;
        }
        .login-container .btn-submit {
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
        .login-container .btn-submit:hover {
            background-color: #155bb5;
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(26, 115, 232, 0.6);
        }
        .login-container .privacy-text {
            font-size: 0.85rem;
            color: #718096;
            margin-top: 1.5rem;
        }
        .login-container .privacy-text a {
            color: #1a73e8;
            text-decoration: underline;
        }
    </style>
</head>
<body class="antialiased">

    <?php include '../includes/public_header.php'; ?>

    <main class="py-12">
        <div class="login-container">
            <img src="../assets/images/logocatdump.png" alt="Catdump Logo" class="mx-auto h-24 w-auto mb-6">
            <h2>Welcome Back!</h2>
            <?php echo $message; ?>
            <form action="login.php" method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                <div>
                    <label for="email" class="form-label text-left block">Email Address</label>
                    <input type="email" id="email" name="email" class="form-input" placeholder="your@example.com" required>
                </div>
                <div>
                    <label for="password" class="form-label text-left block">Password</label>
                    <input type="password" id="password" name="password" class="form-input" placeholder="••••••••" required>
                </div>
                <div class="flex items-center justify-between text-sm mb-6">
                    <a href="forgot_password.php" class="text-blue-custom hover:underline">Forgot Password?</a>
                </div>
                <button type="submit" class="btn-submit">Log In</button>
            </form>
            <p class="mt-8 text-gray-700">Don't have an account? <a href="register.php" class="text-blue-custom font-semibold hover:underline">Sign Up</a></p>
            <p class="privacy-text">By logging in, you agree to our <a href="../PrivacyPolicy.php">Privacy Policy</a> and <a href="../TermsandConditions.php">Terms and Conditions</a>.</p>
        </div>
    </main>

    <?php include '../includes/public_footer.php'; ?>

    <script>
        // Animations on scroll, etc.
        // All header-related JS is now in public_header.php and runs immediately.
        // This script block should contain only page-specific JS for login.php.

        // Placeholder for any login-specific animations or interactions
        // that are *not* part of the global header/footer JS.
        document.addEventListener('DOMContentLoaded', function() {
            const animateOnScrollElements = document.querySelectorAll('.animate-on-scroll');
            if (animateOnScrollElements.length > 0) { // Check if elements exist before observing
                const observer = new IntersectionObserver((entries) => {
                    entries.forEach(entry => {
                        if (entry.isIntersecting) {
                            const delay = parseFloat(getComputedStyle(entry.target).transitionDelay || 0);
                            if (delay > 0) {
                                setTimeout(() => {
                                    entry.target.classList.add('is-visible');
                                }, delay * 1000);
                            } else {
                                entry.target.classList.add('is-visible');
                            }
                            observer.unobserve(entry.target);
                        }
                    });
                }, { threshold: 0.1, rootMargin: '0px 0px -50px 0px' });

                animateOnScrollElements.forEach(element => {
                    observer.observe(element);
                });
            }
        });
    </script>
</body>
</html>