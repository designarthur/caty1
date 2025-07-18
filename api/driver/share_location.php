<?php
// api/driver/share_location.php - API for drivers to share live location

// --- Setup & Includes ---
// No session_start() here as this API is token-authenticated.
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/functions.php'; // For general utilities if needed

header('Content-Type: application/json');

// --- Request Routing ---
$request_method = $_SERVER['REQUEST_METHOD'];

try {
    if ($request_method === 'POST') {
        handleShareLiveLocation($conn);
    } else {
        http_response_code(405); // Method Not Allowed
        throw new Exception('Invalid request method. Only POST is allowed.');
    }
} catch (Exception $e) {
    // Catch any exceptions thrown from handler functions
    if (isset($conn) && $conn instanceof mysqli) {
        $conn->rollback(); // Safely attempt rollback
    }
    http_response_code(400); // Bad Request for most client-side errors
    error_log("Driver Share Location API Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
} finally {
    // Ensure the database connection is closed
    if (isset($conn) && $conn->ping()) {
        $conn->close();
    }
}

// --- Handler Functions ---

/**
 * Handles driver-initiated live location sharing.
 * Validates the access token and saves the location data.
 *
 * @param mysqli $conn The database connection object.
 * @throws Exception If parameters are invalid, token is invalid, or database error occurs.
 */
function handleShareLiveLocation($conn) {
    $booking_id = filter_input(INPUT_POST, 'booking_id', FILTER_VALIDATE_INT);
    $token = trim($_POST['token'] ?? '');
    $latitude = filter_input(INPUT_POST, 'latitude', FILTER_VALIDATE_FLOAT);
    $longitude = filter_input(INPUT_POST, 'longitude', FILTER_VALIDATE_FLOAT);

    if (!$booking_id || empty($token) || $latitude === false || $longitude === false) {
        throw new Exception('Booking ID, access token, latitude, and longitude are required and must be valid.');
    }

    $conn->begin_transaction();
    try {
        // 1. Validate the access token and fetch current booking data
        $stmt_check = $conn->prepare("SELECT id, status FROM bookings WHERE id = ? AND driver_access_token = ?");
        $stmt_check->bind_param("is", $booking_id, $token);
        $stmt_check->execute();
        $booking_data = $stmt_check->get_result()->fetch_assoc();
        $stmt_check->close();

        if (!$booking_data) {
            throw new Exception("Invalid booking ID or access token provided. Location cannot be shared.");
        }

        // Optional: You might want to restrict location sharing to certain booking statuses
        $allowed_statuses_for_location = ['assigned', 'out_for_delivery', 'delivered', 'in_use', 'awaiting_pickup', 'pickedup', 'relocation_requested', 'swap_requested'];
        if (!in_array($booking_data['status'], $allowed_statuses_for_location)) {
            throw new Exception("Location sharing is not allowed for current booking status: " . ucwords(str_replace('_', ' ', $booking_data['status'])));
        }

        // 2. Insert the new live location record
        $stmt_insert_location = $conn->prepare("INSERT INTO booking_live_locations (booking_id, latitude, longitude) VALUES (?, ?, ?)");
        $stmt_insert_location->bind_param("idd", $booking_id, $latitude, $longitude);
        if (!$stmt_insert_location->execute()) {
            throw new Exception("Failed to save live location: " . $stmt_insert_location->error);
        }
        $stmt_insert_location->close();

        $conn->commit();
        echo json_encode(['success' => true, 'message' => 'Live location shared successfully!']);

    } catch (Exception $e) {
        $conn->rollback();
        error_log("Failed to share live location for booking ID {$booking_id}: " . $e->getMessage());
        throw new Exception('Failed to share live location: ' . $e->getMessage());
    }
}