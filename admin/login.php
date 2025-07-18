<?php
// admin/login.php - Admin Login Page

// Include necessary database, function, and session utilities
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/session.php';

// Redirect if already logged in and is an admin
if (is_logged_in() && has_role('admin')) {
    redirect('/admin/index.php'); // Redirect to admin dashboard if already authenticated as admin
} elseif (is_logged_in()) {
    // If logged in but not an admin, redirect to customer dashboard
    redirect('/customer/dashboard.php');
}

// Fetch company name from system settings for dynamic display
$companyName = getSystemSetting('company_name');
if (!$companyName) {
    $companyName = 'Catdump'; // Fallback if not set in DB
}

$login_error = '';

// --- Handle Login Form Submission ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (empty($email) || empty($password)) {
        $login_error = 'Please enter both email and password.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $login_error = 'Please enter a valid email address.';
    } else {
        // Correctly select 'password_hash' instead of 'password'
        $stmt = $conn->prepare("SELECT id, first_name, last_name, email, password_hash, role FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            // Verify password using 'password_hash' and check role
            if (verifyPassword($password, $user['password_hash']) && $user['role'] === 'admin') {
                // Admin login successful
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['user_first_name'] = $user['first_name'];
                $_SESSION['user_last_name'] = $user['last_name'];
                $_SESSION['user_role'] = $user['role'];

                redirect('/admin/index.php');
            } elseif (verifyPassword($password, $user['password_hash']) && $user['role'] !== 'admin') {
                // User is not an admin, redirect to customer dashboard
                redirect('/customer/dashboard.php');
            } else {
                $login_error = 'Invalid email or password.';
            }
        } else {
            $login_error = 'Invalid email or password.';
        }
        $stmt->close();
    }
}
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - <?php echo htmlspecialchars($companyName); ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f0f4f8;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
        }
        .login-container {
            background-color: #ffffff;
            border-radius: 1.5rem;
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.08);
            padding: 3rem;
            width: 100%;
            max-width: 480px;
            text-align: center;
            border: 1px solid rgba(0, 0, 0, 0.05);
        }
        .btn-primary {
            background-color: #1a73e8;
            color: white;
            padding: 0.85rem 2rem;
            border-radius: 0.75rem;
            font-weight: 700;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(26, 115, 232, 0.4);
            width: 100%;
        }
        .btn-primary:hover {
            background-color: #155bb5;
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(26, 115, 232, 0.6);
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
        .text-blue-custom {
            color: #1a73e8;
        }
        .error-message {
            color: #e53e3e;
            font-size: 0.875rem;
            margin-top: -1rem; /* Adjust margin to fit well below input */
            margin-bottom: 1rem;
            text-align: left;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="flex justify-center mb-6">
            <img src="../assets/images/logocatdump.png" alt="<?php echo htmlspecialchars($companyName); ?> Logo" class="h-20 w-20 rounded-full shadow-lg">
        </div>
        <h2 class="text-3xl font-extrabold text-gray-800 mb-2">Admin Login</h2>
        <p class="text-gray-500 mb-8">Access your <?php echo htmlspecialchars($companyName); ?> administration panel.</p>

        <?php if ($login_error): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-6" role="alert">
                <span class="block sm:inline"><?php echo htmlspecialchars($login_error); ?></span>
            </div>
        <?php endif; ?>

        <form method="POST" action="login.php" novalidate>
            <div class="mb-4">
                <label for="email" class="block text-left font-medium text-gray-700">Email Address</label>
                <input type="email" id="email" name="email" class="form-input" placeholder="admin@example.com" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" required>
            </div>
            <div class="mb-6">
                <label for="password" class="block text-left font-medium text-gray-700">Password</label>
                <input type="password" id="password" name="password" class="form-input" placeholder="••••••••" required>
            </div>
            <button type="submit" class="btn-primary">Log In</button>
        </form>
        <p class="text-center text-gray-500 text-sm mt-6">
            <a href="#" class="text-blue-custom hover:underline">Forgot Password?</a>
        </p>
    </div>
</body>
</html>