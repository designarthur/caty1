<?php
// api/driver/update_status.php - API for drivers to update booking status via unique link

// --- Setup & Includes ---
// No session_start() here as this API is token-authenticated, not session-authenticated.
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/functions.php'; // For generateToken() if needed, and other utilities

header('Content-Type: application/json');

// --- Request Routing ---
$request_method = $_SERVER['REQUEST_METHOD'];

try {
    if ($request_method === 'POST') {
        handleDriverUpdateStatus($conn);
    } else {
        http_response_code(405); // Method Not Allowed
        throw new Exception('Invalid request method. Only POST is allowed.');
    }
} catch (Exception $e) {
    // Catch any exceptions thrown from handler functions
    // Attempt to rollback any active transaction if an exception occurs
    if (isset($conn) && $conn->in_transaction) {
        $conn->rollback();
    }
    http_response_code(400); // Bad Request for most client-side errors
    error_log("Driver Update Status API Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
} finally {
    // Ensure the database connection is closed
    if (isset($conn) && $conn->ping()) {
        $conn->close();
    }
}

// --- Handler Functions ---

/**
 * Handles driver-initiated booking status updates.
 * Validates the access token and enforces valid status transitions.
 *
 * @param mysqli $conn The database connection object.
 * @throws Exception If parameters are invalid, token is invalid, or status transition is not allowed.
 */
function handleDriverUpdateStatus($conn) {
    $booking_id = filter_input(INPUT_POST, 'booking_id', FILTER_VALIDATE_INT);
    $newStatus = trim($_POST['new_status'] ?? '');
    $token = trim($_POST['token'] ?? '');

    if (!$booking_id || empty($newStatus) || empty($token)) {
        throw new Exception('Booking ID, new status, and access token are required.');
    }

    $conn->begin_transaction();

    // 1. Validate the access token and fetch current booking data
    $stmt_fetch = $conn->prepare("SELECT id, booking_number, status AS current_status, service_type FROM bookings WHERE id = ? AND driver_access_token = ?");
    $stmt_fetch->bind_param("is", $booking_id, $token);
    $stmt_fetch->execute();
    $booking_data = $stmt_fetch->get_result()->fetch_assoc();
    $stmt_fetch->close();

    if (!$booking_data) {
        throw new Exception("Invalid booking ID or access token provided.");
    }

    $current_status = $booking_data['current_status'];
    $service_type = $booking_data['service_type'];

    // 2. Define allowed status transitions for drivers
    $allowedTransitions = [
        'assigned' => ['out_for_delivery', 'cancelled'],
        'out_for_delivery' => ['delivered', 'cancelled'],
        'delivered' => ['in_use', 'completed'], // 'in_use' for equipment, 'completed' for junk removal
        'in_use' => ['awaiting_pickup'],
        'awaiting_pickup' => ['pickedup'],
        'pickedup' => ['completed'],
        'relocation_requested' => ['relocated', 'cancelled'],
        'swap_requested' => ['swapped', 'cancelled'],
        // No direct driver updates for 'pending', 'scheduled', 'completed', 'cancelled', 'relocated', 'swapped', 'extended'
    ];

    // Check if the new status is a valid transition from the current status
    if (!isset($allowedTransitions[$current_status]) || !in_array($newStatus, $allowedTransitions[$current_status])) {
        // Special handling for 'delivered' status based on service type
        if ($current_status === 'delivered') {
            if ($service_type === 'equipment_rental' && $newStatus === 'in_use') {
                // This is allowed
            } elseif ($service_type === 'junk_removal' && $newStatus === 'completed') {
                // This is allowed
            } else {
                throw new Exception("Invalid status transition from '{$current_status}' to '{$newStatus}' for service type '{$service_type}'.");
            }
        } else {
            throw new Exception("Invalid status transition from '{$current_status}' to '{$newStatus}'.");
        }
    }

    // Prevent updating to the same status unless explicitly allowed (which it's not in this logic)
    if ($current_status === $newStatus) {
        $conn->rollback();
        echo json_encode(['success' => true, 'message' => 'Booking status is already set. No update needed.']);
        return;
    }

    // 3. Update the booking status
    $stmt_update = $conn->prepare("UPDATE bookings SET status = ? WHERE id = ?");
    $stmt_update->bind_param("si", $newStatus, $booking_id);
    if (!$stmt_update->execute()) {
        throw new Exception("Database error on status update: " . $stmt_update->error);
    }
    $stmt_update->close();

    // 4. Log the status change in history
    $notes = "Status updated to " . ucwords(str_replace('_', ' ', $newStatus)) . " by driver via unique link.";
    $stmt_log = $conn->prepare("INSERT INTO booking_status_history (booking_id, status, notes) VALUES (?, ?, ?)");
    $stmt_log->bind_param("iss", $booking_id, $newStatus, $notes);
    if (!$stmt_log->execute()) {
        throw new Exception("Failed to log status history: " . $stmt_log->error);
    }
    $stmt_log->close();

    $conn->commit();
    echo json_encode(['success' => true, 'message' => 'Booking status updated successfully to ' . ucwords(str_replace('_', ' ', $newStatus)) . '!']);

}