<?php
// api/customer/reviews.php - Handles submission of customer reviews

// Start session and include necessary files
session_start();
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/session.php';

header('Content-Type: application/json');

// Check if user is logged in
if (!is_logged_in()) {
    echo json_encode(['success' => false, 'message' => 'You must be logged in to submit a review.']);
    exit;
}

// Only process POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit;
}

$user_id = $_SESSION['user_id'];
$booking_id = filter_input(INPUT_POST, 'booking_id', FILTER_VALIDATE_INT);
$rating = filter_input(INPUT_POST, 'rating', FILTER_VALIDATE_INT);
$review_text = trim($_POST['review_text'] ?? '');

// --- Validation ---
if (!$booking_id || !$rating) {
    echo json_encode(['success' => false, 'message' => 'Booking ID and rating are required.']);
    exit;
}

if ($rating < 1 || $rating > 5) {
    echo json_encode(['success' => false, 'message' => 'Rating must be between 1 and 5.']);
    exit;
}

$conn->begin_transaction();

try {
    // 1. Verify the booking belongs to the user and is completed
    $stmt_verify = $conn->prepare("SELECT id FROM bookings WHERE id = ? AND user_id = ? AND status = 'completed'");
    $stmt_verify->bind_param("ii", $booking_id, $user_id);
    $stmt_verify->execute();
    $result_verify = $stmt_verify->get_result();
    if ($result_verify->num_rows === 0) {
        $stmt_verify->close();
        throw new Exception('You can only review your own completed bookings.');
    }
    $stmt_verify->close();

    // 2. Check if a review for this booking already exists
    $stmt_check = $conn->prepare("SELECT id FROM reviews WHERE booking_id = ?");
    $stmt_check->bind_param("i", $booking_id);
    $stmt_check->execute();
    if ($stmt_check->get_result()->num_rows > 0) {
        $stmt_check->close();
        throw new Exception('A review has already been submitted for this booking.');
    }
    $stmt_check->close();

    // 3. Insert the new review
    $stmt_insert = $conn->prepare("INSERT INTO reviews (booking_id, user_id, rating, review_text) VALUES (?, ?, ?, ?)");
    $stmt_insert->bind_param("iiis", $booking_id, $user_id, $rating, $review_text);
    
    if (!$stmt_insert->execute()) {
        throw new Exception('Failed to save your review. Please try again.');
    }
    $stmt_insert->close();
    
    $conn->commit();
    echo json_encode(['success' => true, 'message' => 'Review submitted successfully!']);

} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

$conn->close();
?>