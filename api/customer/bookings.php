<?php
// api/customer/bookings.php - Handles customer-initiated booking actions like relocation, swap, and pickup requests.

// --- Setup & Includes ---
session_start();
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../includes/functions.php';

header('Content-Type: application/json');

// --- Security & Authorization ---
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

// --- Action Routing ---
$action = $_POST['action'] ?? '';
$user_id = $_SESSION['user_id']; // Get user_id from session for security checks

try {
    // CSRF token validation
    validate_csrf_token(); // Ensure CSRF token is validated for all POST actions

    switch ($action) {
        case 'request_relocation':
            handleServiceRequest($conn, 'relocation');
            break;
        case 'request_swap':
            handleServiceRequest($conn, 'swap');
            break;
        case 'schedule_pickup':
            handleSchedulePickup($conn);
            break;
        case 'request_extension':
            handleRequestExtension($conn);
            break;
        case 'delete_bulk': // Handle bulk delete action
            handleDeleteBulk($conn, $user_id);
            break;
        default:
            throw new Exception('Invalid action specified.');
    }
} catch (Exception $e) {
    http_response_code(400);
    error_log("Customer Booking API Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
} finally {
    if (isset($conn) && $conn->ping()) {
        $conn->close();
    }
}


// --- Handler Functions ---

/**
 * Handles customer requests for relocation or swap services.
 * Creates a new invoice if the service is not included in the original quote,
 * otherwise logs the request and notifies the user.
 *
 * @param mysqli $conn The database connection object.
 * @param string $serviceType 'relocation' or 'swap'.
 * @throws Exception If parameters are invalid or database errors occur.
 */
function handleServiceRequest($conn, $serviceType) {
    $booking_id = filter_input(INPUT_POST, 'booking_id', FILTER_VALIDATE_INT);
    $user_id = $_SESSION['user_id'];
    $new_address = trim($_POST['new_address'] ?? null); // Only relevant for relocation

    if (!$booking_id) {
        throw new Exception('A valid Booking ID is required.');
    }
    if ($serviceType === 'relocation' && empty($new_address)) {
        throw new Exception('A new address is required for relocation requests.');
    }

    $charge_column = ($serviceType === 'relocation') ? 'relocation_charge' : 'swap_charge';
    $included_column = ($serviceType === 'relocation') ? 'is_relocation_included' : 'is_swap_included';
    $service_name = ucwords($serviceType); // For messages (e.g., "Relocation", "Swap")
    $booking_status_requested = $serviceType . '_requested'; // e.g., 'relocation_requested'
    $booking_status_completed = ($serviceType === 'relocation') ? 'relocated' : 'swapped'; // Final status

    $conn->begin_transaction();

    try {
        // 1. Fetch the booking, its original quote, and the specific service flags/charges
        $stmt_fetch = $conn->prepare("
            SELECT b.booking_number, q.{$charge_column}, q.{$included_column}
            FROM bookings b
            JOIN invoices i ON b.invoice_id = i.id
            LEFT JOIN quotes q ON i.quote_id = q.id -- Use LEFT JOIN as quote_id can be NULL
            WHERE b.id = ? AND b.user_id = ?
        ");
        $stmt_fetch->bind_param("ii", $booking_id, $user_id);
        $stmt_fetch->execute();
        $quote_data = $stmt_fetch->get_result()->fetch_assoc();
        $stmt_fetch->close();

        if (!$quote_data) {
            throw new Exception("Could not find the specified booking or you don't have permission to access it.");
        }

        $is_included = (bool)($quote_data[$included_column] ?? false); // Default to false if column is null
        $charge_amount = (float)($quote_data[$charge_column] ?? 0.00); // Default to 0.00 if column is null

        // Scenario A: Service is pre-paid/included (or has no charge)
        if ($is_included || $charge_amount <= 0) {
            // Update booking status to 'relocated' or 'swapped' directly
            $stmt_update_booking_status = $conn->prepare("UPDATE bookings SET status = ? WHERE id = ?");
            $stmt_update_booking_status->bind_param("si", $booking_status_completed, $booking_id);
            $stmt_update_booking_status->execute();
            $stmt_update_booking_status->close();

            $notes = "Customer initiated {$service_name} request. This service is included in the original quote or has no charge. Status updated to {$booking_status_completed}.";
            if ($serviceType === 'relocation') {
                $notes .= " New address: {$new_address}";
                // Update the booking's delivery_location
                $stmt_update_booking_location = $conn->prepare("UPDATE bookings SET delivery_location = ? WHERE id = ?");
                $stmt_update_booking_location->bind_param("si", $new_address, $booking_id);
                $stmt_update_booking_location->execute();
                $stmt_update_booking_location->close();
            }
            
            $stmt_log = $conn->prepare("INSERT INTO booking_status_history (booking_id, status, notes) VALUES (?, ?, ?)");
            $stmt_log->bind_param("iss", $booking_id, $booking_status_completed, $notes);
            $stmt_log->execute();
            $stmt_log->close();

            // Notify customer that the request is being scheduled
            $notification_message = "Your {$service_name} request for booking #{$quote_data['booking_number']} has been received and is being scheduled by our team.";
            $notification_link = "bookings?booking_id={$booking_id}";
            $stmt_notify = $conn->prepare("INSERT INTO notifications (user_id, type, message, link) VALUES (?, 'booking_status_update', ?, ?)");
            $stmt_notify->bind_param("iss", $user_id, $notification_message, $notification_link);
            $stmt_notify->execute();
            $stmt_notify->close();
            
            // Notify Admin to take action (e.g., via a separate admin notification type)
            $admin_notification_message = "Customer requested {$service_name} for Booking #{$quote_data['booking_number']}. (Pre-paid/No charge)";
            $admin_notification_link = "bookings?booking_id={$booking_id}";
            $stmt_admin_notify = $conn->prepare("INSERT INTO notifications (user_id, type, message, link) VALUES (?, 'system_message', ?, ?)"); // Assuming admin user_id is 1
            $admin_id = 1; // Replace with actual admin user ID or a system notification mechanism
            $stmt_admin_notify->bind_param("iss", $admin_id, $admin_notification_message, $admin_notification_link);
            $stmt_admin_notify->execute();
            $stmt_admin_notify->close();


            $conn->commit();
            echo json_encode(['success' => true, 'message' => 'Your ' . $service_name . ' request has been sent for scheduling!']);
            return;
        }

        // Scenario B: Service requires payment (charge_amount > 0 and not included)
        // First, update booking status to 'relocation_requested' or 'swap_requested'
        $stmt_update_booking_status = $conn->prepare("UPDATE bookings SET status = ? WHERE id = ?");
        $stmt_update_booking_status->bind_param("si", $booking_status_requested, $booking_id);
        $stmt_update_booking_status->execute();
        $stmt_update_booking_status->close();

        // Log the status history for the request
        $notes = "Customer requested {$service_name} for Booking #{$quote_data['booking_number']}. Payment required. Status updated to {$booking_status_requested}.";
        $stmt_log = $conn->prepare("INSERT INTO booking_status_history (booking_id, status, notes) VALUES (?, ?, ?)");
        $stmt_log->bind_param("iss", $booking_id, $booking_status_requested, $notes);
        $stmt_log->execute();
        $stmt_log->close();
        
        // Create a new invoice for this service request
        $invoice_number = 'INV-' . strtoupper(substr($serviceType, 0, 3)) . '-' . generateToken(6);
        $due_date = date('Y-m-d', strtotime('+3 days')); 

        $stmt_invoice = $conn->prepare("INSERT INTO invoices (user_id, booking_id, invoice_number, amount, status, due_date, notes) VALUES (?, ?, ?, ?, 'pending', ?, ?)");
        $invoice_notes = "Invoice for {$service_name} Request on Booking #{$quote_data['booking_number']}";
        $stmt_invoice->bind_param("iisdss", $user_id, $booking_id, $invoice_number, $charge_amount, $due_date, $invoice_notes);
        $stmt_invoice->execute();
        $new_invoice_id = $conn->insert_id;
        $stmt_invoice->close();

        // Add line item for the new service charge
        $stmt_insert_item = $conn->prepare("INSERT INTO invoice_items (invoice_id, description, quantity, unit_price, total) VALUES (?, ?, ?, ?, ?)");
        $item_description = "{$service_name} Service for Booking #{$quote_data['booking_number']}";
        $item_quantity = 1;
        $item_unit_price = $charge_amount;
        $item_total = $charge_amount;
        $stmt_insert_item->bind_param("isidd", $new_invoice_id, $item_description, $item_quantity, $item_unit_price, $item_total);
        if (!$stmt_insert_item->execute()) {
            throw new Exception('Failed to insert invoice item for new service charge.');
        }
        $stmt_insert_item->close();


        // Create a notification for the customer
        $notification_message = "Your {$service_name} request for booking #{$quote_data['booking_number']} requires payment. Please pay invoice #{$invoice_number} to proceed.";
        $notification_link = "invoices?invoice_id={$new_invoice_id}";
        $stmt_notify = $conn->prepare("INSERT INTO notifications (user_id, type, message, link) VALUES (?, 'payment_due', ?, ?)");
        $stmt_notify->bind_param("iss", $user_id, $notification_message, $notification_link);
        $stmt_notify->execute();
        $stmt_notify->close();

        // Notify Admin about the new service request requiring payment
        $admin_notification_message = "Customer requested {$service_name} for Booking #{$quote_data['booking_number']}. Payment of $" . number_format($charge_amount, 2) . " required.";
        $admin_notification_link = "bookings?booking_id={$booking_id}";
        $admin_id = 1; // Assuming admin user_id is 1
        $stmt_admin_notify = $conn->prepare("INSERT INTO notifications (user_id, type, message, link) VALUES (?, 'system_message', ?, ?)");
        $stmt_admin_notify->bind_param("iss", $admin_id, $admin_notification_message, $admin_notification_link);
        $stmt_admin_notify->execute();
        $stmt_admin_notify->close();


        $conn->commit();
        
        echo json_encode([
            'success' => true,
            'message' => "{$service_name} request submitted! Please complete the payment for the new invoice.",
            'invoice_id' => $new_invoice_id
        ]);
    } catch (Exception $e) {
        $conn->rollback();
        throw $e; // Re-throw to be caught by the main try-catch block
    }
}


/**
 * Handles customer request to schedule a pickup for a booking.
 *
 * @param mysqli $conn The database connection object.
 * @throws Exception If parameters are invalid or database errors occur.
 */
function handleSchedulePickup($conn) {
    $booking_id = filter_input(INPUT_POST, 'booking_id', FILTER_VALIDATE_INT);
    $pickup_date = trim($_POST['pickup_date'] ?? '');
    $pickup_time = trim($_POST['pickup_time'] ?? '');
    $user_id = $_SESSION['user_id'];

    if (!$booking_id || empty($pickup_date) || empty($pickup_time)) {
        throw new Exception('Booking ID, pickup date, and pickup time are required.');
    }

    $conn->begin_transaction();

    try {
        $stmt_update = $conn->prepare("UPDATE bookings SET pickup_date = ?, pickup_time = ?, status = 'awaiting_pickup' WHERE id = ? AND user_id = ?");
        $stmt_update->bind_param("ssii", $pickup_date, $pickup_time, $booking_id, $user_id);
        $stmt_update->execute();
        
        if ($stmt_update->affected_rows === 0) {
            throw new Exception("Booking not found or you don't have permission to update it.");
        }
        $stmt_update->close();
        
        $notes = "Customer scheduled pickup for {$pickup_date} at {$pickup_time}.";
        $stmt_log = $conn->prepare("INSERT INTO booking_status_history (booking_id, status, notes) VALUES (?, 'awaiting_pickup', ?)");
        $stmt_log->bind_param("is", $booking_id, $notes);
        $stmt_log->execute();
        $stmt_log->close();

        $notification_message = "Your pickup for booking #{$booking_id} has been successfully scheduled for {$pickup_date}. Our team will confirm and process it.";
        $notification_link = "bookings?booking_id={$booking_id}";
        $stmt_notify = $conn->prepare("INSERT INTO notifications (user_id, type, message, link) VALUES (?, 'booking_status_update', ?, ?)");
        $stmt_notify->bind_param("iss", $user_id, $notification_message, $notification_link);
        $stmt_notify->execute();
        $stmt_notify->close();

        // Notify Admin about the new pickup request
        $admin_notification_message = "New pickup request for Booking #{$booking_id} on {$pickup_date} at {$pickup_time}.";
        $admin_notification_link = "bookings?booking_id={$booking_id}";
        $admin_id = 1; // Assuming admin user_id is 1
        $stmt_admin_notify = $conn->prepare("INSERT INTO notifications (user_id, type, message, link) VALUES (?, 'system_message', ?, ?)");
        $stmt_admin_notify->bind_param("iss", $admin_id, $admin_notification_message, $admin_notification_link);
        $stmt_admin_notify->execute();
        $stmt_admin_notify->close();

        $conn->commit();

        echo json_encode(['success' => true, 'message' => 'Pickup scheduled successfully!']);
    } catch (Exception $e) {
        $conn->rollback();
        throw $e;
    }
}

/**
 * Handles customer request for a rental extension.
 * Creates a pending extension request for admin approval.
 *
 * @param mysqli $conn The database connection object.
 * @throws Exception If parameters are invalid or database errors occur.
 */
function handleRequestExtension($conn) {
    $booking_id = filter_input(INPUT_POST, 'booking_id', FILTER_VALIDATE_INT);
    $extension_days = filter_input(INPUT_POST, 'extension_days', FILTER_VALIDATE_INT);
    $user_id = $_SESSION['user_id'];

    if (!$booking_id || !$extension_days || $extension_days <= 0) {
        throw new Exception('Booking ID and a valid number of extension days are required.');
    }

    $conn->begin_transaction();
    
    try {
        // Check if there's already a pending request for this booking
        $stmt_check_pending = $conn->prepare("SELECT id FROM booking_extension_requests WHERE booking_id = ? AND status = 'pending'");
        $stmt_check_pending->bind_param("i", $booking_id);
        $stmt_check_pending->execute();
        if ($stmt_check_pending->get_result()->num_rows > 0) {
            throw new Exception('You already have a pending extension request for this booking. Please wait for admin approval.');
        }
        $stmt_check_pending->close();

        // 1. Log the extension request
        $stmt_request = $conn->prepare("INSERT INTO booking_extension_requests (booking_id, user_id, requested_days) VALUES (?, ?, ?)");
        $stmt_request->bind_param("iii", $booking_id, $user_id, $extension_days);
        $stmt_request->execute();
        $stmt_request->close();

        // 2. Notify admin about the new request
        $admin_notification_message = "Customer has requested a {$extension_days}-day extension for Booking ID #{$booking_id}. Please review and approve.";
        $admin_notification_link = "bookings?booking_id={$booking_id}";
        // Assuming admin user_id is 1 for system-wide notifications
        $admin_id = 1; // Replace with actual admin user ID or a system notification mechanism
        $stmt_admin_notify = $conn->prepare("INSERT INTO notifications (user_id, type, message, link) VALUES (?, 'system_message', ?, ?)");
        $stmt_admin_notify->bind_param("iss", $admin_id, $admin_notification_message, $admin_notification_link);
        $stmt_admin_notify->execute();
        $stmt_admin_notify->close();

        // 3. Notify customer that the request has been submitted
        $notification_message = "Your request to extend Booking #{$booking_id} by {$extension_days} days has been submitted for approval.";
        $notification_link = "bookings?booking_id={$booking_id}";
        $stmt_notify = $conn->prepare("INSERT INTO notifications (user_id, type, message, link) VALUES (?, 'booking_status_update', ?, ?)");
        $stmt_notify->bind_param("iss", $user_id, $notification_message, $notification_link);
        $stmt_notify->execute();
        $stmt_notify->close();
        
        $conn->commit();

        echo json_encode([
            'success' => true,
            'message' => 'Your extension request has been submitted and is awaiting admin approval.'
        ]);
    } catch (Exception $e) {
        $conn->rollback();
        throw $e;
    }
}

/**
 * Handles bulk deletion of bookings.
 * This function deletes the booking records and relies on ON DELETE CASCADE
 * to remove associated records in `booking_charges`, `booking_extension_requests`,
 * `booking_status_history`, and `reviews`.
 * Invoices and quotes are NOT deleted by this function, as they may have broader financial context.
 *
 * @param mysqli $conn The database connection object.
 * @param int $user_id The ID of the currently logged-in user.
 * @return array Response array with success status and message.
 * @throws Exception If no booking IDs are provided or a database error occurs.
 */
function handleDeleteBulk($conn, $user_id) {
    $booking_ids = $_POST['booking_ids'] ?? [];
    if (empty($booking_ids) || !is_array($booking_ids)) {
        throw new Exception("No booking IDs provided for bulk deletion.");
    }

    $conn->begin_transaction();

    try {
        $placeholders = implode(',', array_fill(0, count($booking_ids), '?'));
        $types = str_repeat('i', count($booking_ids));
        
        // IMPORTANT SECURITY FIX: Ensure user_id is included in the WHERE clause
        // to prevent users from deleting bookings that do not belong to them.
        $stmt_delete_bookings = $conn->prepare("DELETE FROM bookings WHERE id IN ($placeholders) AND user_id = ?");
        // Bind parameters: first the booking IDs, then the user_id
        $types .= 'i'; // Add 'i' for the user_id integer parameter
        $params_with_user_id = array_merge($booking_ids, [$user_id]);

        $stmt_delete_bookings->bind_param($types, ...$params_with_user_id);
        
        if (!$stmt_delete_bookings->execute()) {
            throw new Exception("Failed to delete bookings: " . $stmt_delete_bookings->error);
        }
        $stmt_delete_bookings->close();

        $conn->commit();
        echo json_encode(['success' => true, 'message' => 'Selected bookings and their associated data have been deleted.']);

    } catch (Exception $e) {
        $conn->rollback();
        error_log("Bulk delete bookings error: " . $e->getMessage());
        throw $e;
    }
}