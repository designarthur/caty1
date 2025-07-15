<?php
// api/customer/profile.php

// --- Setup & Includes ---
session_start();
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../includes/functions.php'; // We will add CSRF functions here

header('Content-Type: application/json');

// --- Security & Authorization ---
// Ensure the user is a logged-in customer.
if (!is_logged_in()) {
    http_response_code(403); // Forbidden
    echo json_encode(['success' => false, 'message' => 'Unauthorized access. Please log in.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); // Method Not Allowed
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit;
}

// --- CSRF Token Validation ---
// This is the crucial step to prevent CSRF attacks.
try {
    // This function will check the submitted token against the one in the session.
    // We will define it in `functions.php`.
    validate_csrf_token();
} catch (Exception $e) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Invalid security token. Please refresh the page and try again.']);
    exit;
}

// --- Input Processing & Validation ---
$user_id = $_SESSION['user_id'];
$errors = [];

// Use filter_input for secure and clean input handling.
$firstName = filter_input(INPUT_POST, 'first_name', FILTER_SANITIZE_SPECIAL_CHARS);
$lastName = filter_input(INPUT_POST, 'last_name', FILTER_SANITIZE_SPECIAL_CHARS);
$email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
$phoneNumber = filter_input(INPUT_POST, 'phone_number', FILTER_SANITIZE_SPECIAL_CHARS);
$address = filter_input(INPUT_POST, 'address', FILTER_SANITIZE_SPECIAL_CHARS);
$city = filter_input(INPUT_POST, 'city', FILTER_SANITIZE_SPECIAL_CHARS);
$state = filter_input(INPUT_POST, 'state', FILTER_SANITIZE_SPECIAL_CHARS);
$zipCode = filter_input(INPUT_POST, 'zip_code', FILTER_SANITIZE_SPECIAL_CHARS);

// Validate the sanitized data.
if (empty($firstName) || empty($lastName) || empty($email) || empty($phoneNumber) || empty($address) || empty($city) || empty($state) || empty($zipCode)) {
    $errors['general'] = 'All fields are required.';
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors['email'] = 'Please enter a valid email address.';
}
// You can add more specific validation rules for phone, zip, etc.

if (!empty($errors)) {
    http_response_code(400); // Bad Request
    echo json_encode(['success' => false, 'message' => 'Please correct the errors.', 'errors' => $errors]);
    exit;
}

// --- Database Logic ---
$conn->begin_transaction();
try {
    // Check if the new email is already in use by another user.
    $stmt_check = $conn->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
    $stmt_check->bind_param("si", $email, $user_id);
    $stmt_check->execute();
    if ($stmt_check->get_result()->num_rows > 0) {
        throw new Exception('This email is already in use by another account.');
    }
    $stmt_check->close();

    // Prepare and execute the update statement.
    $stmt_update = $conn->prepare("UPDATE users SET first_name = ?, last_name = ?, email = ?, phone_number = ?, address = ?, city = ?, state = ?, zip_code = ? WHERE id = ?");
    $stmt_update->bind_param("ssssssssi", $firstName, $lastName, $email, $phoneNumber, $address, $city, $state, $zipCode, $user_id);
    $stmt_update->execute();

    // Update the session variables to reflect the changes immediately.
    $_SESSION['user_first_name'] = $firstName;
    $_SESSION['user_last_name'] = $lastName;
    $_SESSION['user_email'] = $email;

    $conn->commit();
    echo json_encode(['success' => true, 'message' => 'Profile updated successfully!']);

} catch (Exception $e) {
    $conn->rollback();
    http_response_code(400); // Use 400 for client-side errors like duplicate email
    error_log("Profile update failed for user ID {$user_id}: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
} finally {
    if (isset($stmt_update)) {
        $stmt_update->close();
    }
    $conn->close();
}
?>