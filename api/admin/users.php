<?php
// api/admin/users.php - Admin API for Users Management

// --- Setup & Includes ---
session_start();
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../includes/functions.php';

header('Content-Type: application/json');

// --- Security & Authorization ---
// Ensure the user is a logged-in administrator.
if (!is_logged_in() || !has_role('admin')) {
    http_response_code(403); // Forbidden
    echo json_encode(['success' => false, 'message' => 'Unauthorized access.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); // Method Not Allowed
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit;
}

// --- Input Processing ---
$action = $_POST['action'] ?? '';
$user_id_to_manage = filter_input(INPUT_POST, 'user_id', FILTER_VALIDATE_INT);
$acting_admin_id = $_SESSION['user_id'];

if (empty($user_id_to_manage)) {
    echo json_encode(['success' => false, 'message' => 'User ID is required.']);
    exit;
}

// Admins cannot perform critical actions on their own account via this API.
if ((int)$user_id_to_manage === (int)$acting_admin_id) {
    if ($action === 'delete_user' || ($action === 'update_user' && isset($_POST['role']) && $_POST['role'] !== 'admin')) {
        http_response_code(400); // Bad Request
        echo json_encode(['success' => false, 'message' => 'You cannot delete or change the role of your own account.']);
        exit;
    }
}


// --- Action Routing ---
switch ($action) {
    case 'update_user':
        handleUpdateUser($conn, $user_id_to_manage);
        break;
    case 'reset_password':
        handleResetPassword($conn, $user_id_to_manage);
        break;
    case 'delete_user':
        handleDeleteUser($conn, $user_id_to_manage);
        break;
    default:
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid action specified.']);
        break;
}

$conn->close();


// --- Handler Functions ---

/**
 * Handles updating a user's profile information.
 */
function handleUpdateUser($conn, $user_id) {
    // Whitelist of fields that are allowed to be updated. This prevents mass-assignment vulnerabilities.
    $allowed_fields = [
        'first_name', 'last_name', 'email', 'phone_number',
        'address', 'city', 'state', 'zip_code', 'role'
    ];
    $update_data = [];
    $types = '';
    $sql_parts = [];

    foreach ($allowed_fields as $field) {
        if (isset($_POST[$field])) {
            $update_data[] = trim($_POST[$field]);
            $sql_parts[] = "{$field} = ?";
            $types .= 's'; // Assume all are strings for simplicity in this dynamic query
        }
    }

    if (empty($sql_parts)) {
        echo json_encode(['success' => true, 'message' => 'No data provided to update.']);
        return;
    }

    // --- Validation ---
    if (!filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['success' => false, 'message' => 'Invalid email format.']);
        return;
    }
    // (Add other specific validations as needed)

    // Check for email uniqueness
    $stmt_check = $conn->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
    $stmt_check->bind_param("si", $_POST['email'], $user_id);
    $stmt_check->execute();
    if ($stmt_check->get_result()->num_rows > 0) {
        echo json_encode(['success' => false, 'message' => 'This email is already in use by another account.']);
        $stmt_check->close();
        return;
    }
    $stmt_check->close();

    // --- Database Update ---
    $sql = "UPDATE users SET " . implode(', ', $sql_parts) . " WHERE id = ?";
    $types .= 'i';
    $update_data[] = $user_id;

    $stmt_update = $conn->prepare($sql);
    $stmt_update->bind_param($types, ...$update_data);

    if ($stmt_update->execute()) {
        echo json_encode(['success' => true, 'message' => 'User updated successfully!']);
    } else {
        error_log("Failed to update user ID {$user_id}: " . $stmt_update->error);
        echo json_encode(['success' => false, 'message' => 'Failed to update user.']);
    }
    $stmt_update->close();
}


/**
 * Sends a password reset email with a secure token.
 * This is more secure than emailing a plain-text password.
 */
function handleResetPassword($conn, $user_id) {
    // Generate a secure, single-use token and an expiration time.
    $token = generateToken(64);
    $expires = date("U") + 3600; // Token expires in 1 hour

    $stmt_user = $conn->prepare("SELECT email, first_name FROM users WHERE id = ?");
    $stmt_user->bind_param("i", $user_id);
    $stmt_user->execute();
    $user = $stmt_user->get_result()->fetch_assoc();

    if (!$user) {
        echo json_encode(['success' => false, 'message' => 'User not found.']);
        return;
    }

    // You would need a table to store these tokens, e.g., `password_resets`
    // For now, we'll simulate this. A real implementation would store the token hash.
    // $hashed_token = hash('sha256', $token);
    // $stmt_token = $conn->prepare("INSERT INTO password_resets (email, token_hash, expires_at) VALUES (?, ?, ?)");
    // $stmt_token->bind_param("ssi", $user['email'], $hashed_token, $expires);
    // $stmt_token->execute();

    // Construct the reset link (you need to create this page)
    $resetLink = "https://yourwebsite.com/reset-password.php?token=" . $token;

    // Send the email
    $emailBody = "<p>Hello {$user['first_name']},</p><p>A password reset was requested for your account. Please click the link below to set a new password. This link is valid for 1 hour.</p><p><a href='{$resetLink}'>Reset Your Password</a></p>";
    $emailSent = sendEmail($user['email'], "Password Reset Request", $emailBody);

    if ($emailSent) {
        echo json_encode(['success' => true, 'message' => 'Password reset email sent successfully.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to send password reset email.']);
    }
}


/**
 * Deletes a user and all their associated data via database cascades.
 */
function handleDeleteUser($conn, $user_id) {
    // This function assumes you have set up ON DELETE CASCADE on your foreign keys
    // in the database schema. This is the cleanest way to handle deletion.
    $conn->begin_transaction();
    try {
        $stmt_delete = $conn->prepare("DELETE FROM users WHERE id = ?");
        $stmt_delete->bind_param("i", $user_id);
        $stmt_delete->execute();

        if ($stmt_delete->affected_rows > 0) {
            $conn->commit();
            echo json_encode(['success' => true, 'message' => 'User and all associated data have been deleted.']);
        } else {
            throw new Exception("User not found or could not be deleted.");
        }
        $stmt_delete->close();
    } catch (Exception $e) {
        $conn->rollback();
        error_log("Delete user transaction failed for ID {$user_id}: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Failed to delete user.']);
    }
}
?>