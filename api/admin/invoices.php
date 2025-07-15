<?php
// api/admin/invoices.php - Handles admin actions for invoices, like status changes

session_start();
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../includes/functions.php';

header('Content-Type: application/json');

// Security check: Ensure user is a logged-in admin
if (!is_logged_in() || !has_role('admin')) {
    die(json_encode(['success' => false, 'message' => 'Unauthorized access.']));
}

// Ensure it's a POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die(json_encode(['success' => false, 'message' => 'Invalid request method.']));
}

$action = $_POST['action'] ?? '';

try {
    // CSRF token validation with enhanced logging
    if (!isset($_POST['csrf_token'])) {
        error_log("CSRF token missing in POST data for action: $action");
        throw new Exception('CSRF token missing. Please refresh and try again.');
    }
    error_log("Received CSRF token: " . $_POST['csrf_token']);
    validate_csrf_token();

    switch ($action) {
        case 'update_status':
            $response = handleUpdateStatus($conn);
            break;
        case 'update_invoice':
            $response = handleUpdateInvoice($conn);
            break;
        case 'delete_bulk':
            $response = handleDeleteBulk($conn);
            break;
        default:
            throw new Exception('Invalid action specified.');
    }

    echo json_encode($response);

} catch (Exception $e) {
    http_response_code(400); // Bad Request for most client-side errors
    error_log("Admin Invoice API Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
} finally {
    if (isset($conn) && $conn->ping()) {
        $conn->close();
    }
}

/**
 * Handles updating the status of a single invoice and creates a booking if status is 'paid'.
 *
 * @param mysqli $conn The database connection object.
 * @return array Response array with success status and message.
 * @throws Exception If parameters are invalid, invoice not found, or database error occurs.
 */
function handleUpdateStatus($conn) {
    $invoice_id = filter_input(INPUT_POST, 'invoice_id', FILTER_VALIDATE_INT);
    $new_status = $_POST['status'] ?? '';

    if (!$invoice_id || empty($new_status)) {
        throw new Exception('Invalid or missing parameters.');
    }
    
    // Validate the new status to ensure it's one of the allowed enum values
    $allowed_statuses = ['pending', 'paid', 'partially_paid', 'cancelled'];
    if (!in_array($new_status, $allowed_statuses)) {
        throw new Exception('Invalid status provided.');
    }

    $conn->begin_transaction();

    try {
        // 1. Fetch current invoice and customer details for notification
        // Using LEFT JOIN to ensure service_type and quote_location are available even if invoice.quote_id is NULL
        // Also fetching user's address for fallback delivery_location
        // Fetching booking_id from invoice to check if it's an existing booking for additional charges
        $stmt_fetch = $conn->prepare("
            SELECT 
                i.invoice_number, i.status AS old_status, i.quote_id, i.amount, i.booking_id AS existing_booking_id,
                u.id AS user_id, u.first_name, u.email, u.address AS user_address, u.city AS user_city, u.state AS user_state, u.zip_code AS user_zip_code,
                q.service_type AS quote_service_type, q.location AS quote_location 
            FROM invoices i 
            JOIN users u ON i.user_id = u.id 
            LEFT JOIN quotes q ON i.quote_id = q.id 
            WHERE i.id = ?
        ");
        $stmt_fetch->bind_param("i", $invoice_id);
        $stmt_fetch->execute();
        $invoice_data = $stmt_fetch->get_result()->fetch_assoc();
        $stmt_fetch->close();

        if (!$invoice_data) {
            throw new Exception('Invoice or associated user not found.');
        }
        if ($invoice_data['old_status'] === $new_status) {
            $conn->rollback();
            return ['success' => true, 'message' => 'Invoice status is already set. No update needed.'];
        }

        // 2. Update the invoice status
        $stmt_update = $conn->prepare("UPDATE invoices SET status = ? WHERE id = ?");
        $stmt_update->bind_param("si", $new_status, $invoice_id);
        if (!$stmt_update->execute()) {
            throw new Exception("Database error on status update: " . $stmt_update->error);
        }
        $stmt_update->close();

        // 3. If status is 'paid', create a booking or update existing one (for extensions/charges)
        $booking_id = null;
        if ($new_status === 'paid') {
            // Check if this invoice is linked to an existing booking (e.g., for additional charges, extensions)
            if ($invoice_data['existing_booking_id']) {
                $booking_id = $invoice_data['existing_booking_id'];

                // If this payment is for an extension, update the booking's end_date
                // First, check if there's a related extension request
                $stmt_ext_req = $conn->prepare("SELECT requested_days FROM booking_extension_requests WHERE invoice_id = ? AND status = 'approved'");
                $stmt_ext_req->bind_param("i", $invoice_id);
                $stmt_ext_req->execute();
                $ext_data = $stmt_ext_req->get_result()->fetch_assoc();
                $stmt_ext_req->close();

                if ($ext_data && $ext_data['requested_days'] > 0) {
                    // Update the booking's end date
                    $stmt_update_end_date = $conn->prepare("UPDATE bookings SET end_date = DATE_ADD(end_date, INTERVAL ? DAY) WHERE id = ?");
                    $stmt_update_end_date->bind_param("ii", $ext_data['requested_days'], $booking_id);
                    $stmt_update_end_date->execute();
                    $stmt_update_end_date->close();

                    // Log the status history for the extension payment
                    $notes = "Booking extended by {$ext_data['requested_days']} days due to paid invoice #{$invoice_data['invoice_number']}.";
                    $stmt_log_ext = $conn->prepare("INSERT INTO booking_status_history (booking_id, status, notes) VALUES (?, 'extended', ?)");
                    $stmt_log_ext->bind_param("is", $booking_id, $notes);
                    $stmt_log_ext->execute();
                    $stmt_log_ext->close();
                }

            } else {
                // This is a new booking from a quote
                // Determine the delivery_location for the booking
                $delivery_location_for_booking = $invoice_data['quote_location'] ?? null;
                if (empty($delivery_location_for_booking)) {
                    // Fallback to user's registered address if quote location is not available
                    $delivery_location_for_booking = trim($invoice_data['user_address'] . ', ' . $invoice_data['user_city'] . ', ' . $invoice_data['user_state'] . ' ' . $invoice_data['user_zip_code']);
                    if (empty($delivery_location_for_booking)) {
                        $delivery_location_for_booking = 'N/A'; // Final fallback, though ideally a real address should be captured
                    }
                }

                // Determine service_type for the booking
                // Use quote_service_type if available, otherwise a generic 'other_service'
                $service_type_for_booking = $invoice_data['quote_service_type'] ?? 'other_service'; 
                
                // Ensure service_type_for_booking is one of the allowed ENUM values for 'bookings' table
                $allowed_booking_service_types = ['equipment_rental', 'junk_removal'];
                if (!in_array($service_type_for_booking, $allowed_booking_service_types)) {
                    // If it's not a standard type from a quote, default to 'equipment_rental' or 'junk_removal'
                    // or a new generic type if you add one to the ENUM in DB.
                    // For now, let's default to 'equipment_rental' if it's an unknown type.
                    $service_type_for_booking = 'equipment_rental'; 
                    error_log("Unknown service_type '{$invoice_data['quote_service_type']}' for booking ID {$booking_id}. Defaulting to 'equipment_rental'.");
                }


                // Create a booking
                $booking_number = 'BOOK-' . strtoupper(generateToken(6)); // Shorten token for booking number
                $start_date = date('Y-m-d'); // Adjust based on quote or requirements
                $booking_status = 'scheduled'; 

                $stmt_booking = $conn->prepare("
                    INSERT INTO bookings (invoice_id, user_id, booking_number, service_type, status, start_date, delivery_location, total_price)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt_booking->bind_param(
                    "iisssssd", // Types: i (invoice_id), i (user_id), s (booking_number), s (service_type), s (status), s (start_date), s (delivery_location), d (total_price)
                    $invoice_id,
                    $invoice_data['user_id'],
                    $booking_number,
                    $service_type_for_booking,
                    $booking_status,
                    $start_date,
                    $delivery_location_for_booking,
                    $invoice_data['amount'] // Using invoice amount as total_price for the booking
                );
                if (!$stmt_booking->execute()) {
                    throw new Exception("Failed to create booking: " . $stmt_booking->error);
                }
                $booking_id = $conn->insert_id;
                $stmt_booking->close();

                // Create notification for the user about the booking
                $notification_message = "Booking #$booking_number created for your paid invoice #{$invoice_data['invoice_number']}.";
                $notification_link = "bookings?booking_id=$booking_id";
                $stmt_notify_booking = $conn->prepare("
                    INSERT INTO notifications (user_id, type, message, link)
                    VALUES (?, 'booking_confirmed', ?, ?)
                ");
                $stmt_notify_booking->bind_param("iss", $invoice_data['user_id'], $notification_message, $notification_link);
                if (!$stmt_notify_booking->execute()) {
                    error_log("Failed to create booking notification for booking_id: $booking_id");
                }
                $stmt_notify_booking->close();
            }
        }

        // 4. Notify Customer about status update
        $email_subject = "Update on your invoice #{$invoice_data['invoice_number']}";
        $email_body = "<p>Dear {$invoice_data['first_name']},</p><p>The payment status for your invoice #<strong>{$invoice_data['invoice_number']}</strong> has been updated to: <strong>" . strtoupper($new_status) . "</strong>.</p>";
        if ($booking_id) {
            // Only add booking details if a booking was created or found
            $email_body .= "<p>A booking (#" . (getBookingNumberFromId($conn, $booking_id)) . ") has been created/updated for your service.</p>";
        }
        sendEmail($invoice_data['email'], $email_subject, $email_body);

        $conn->commit();
        $response = ['success' => true, 'message' => "Invoice status updated to '{$new_status}' and customer notified."];
        if ($booking_id) {
            $response['booking_id'] = $booking_id;
            $response['message'] .= " Booking #".getBookingNumberFromId($conn, $booking_id)." created/confirmed.";
        }
        return $response;

    } catch (Exception $e) {
        $conn->rollback();
        error_log("Update status error for invoice_id: $invoice_id, status: $new_status - " . $e->getMessage());
        throw $e;
    }
}

/**
 * Helper function to retrieve booking number by ID
 */
function getBookingNumberFromId($conn, $booking_id) {
    $stmt = $conn->prepare("SELECT booking_number FROM bookings WHERE id = ?");
    $stmt->bind_param("i", $booking_id);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return $result['booking_number'] ?? 'N/A';
}

/**
 * Handles updating the details of an invoice, including its line items, discount, and tax.
 *
 * @param mysqli $conn The database connection object.
 * @return array Response array with success status and message.
 * @throws Exception If input is invalid or a database error occurs.
 */
function handleUpdateInvoice($conn) {
    $invoice_id = filter_input(INPUT_POST, 'invoice_id', FILTER_VALIDATE_INT);
    $items = json_decode($_POST['items'] ?? '[]', true);
    $discount = filter_input(INPUT_POST, 'discount', FILTER_VALIDATE_FLOAT);
    $tax = filter_input(INPUT_POST, 'tax', FILTER_VALIDATE_FLOAT);

    if (!$invoice_id || !is_array($items)) {
        throw new Exception("Invalid input. Invoice ID and items are required.");
    }

    $conn->begin_transaction();

    try {
        // 1. Delete old line items
        $stmt_delete = $conn->prepare("DELETE FROM invoice_items WHERE invoice_id = ?");
        $stmt_delete->bind_param("i", $invoice_id);
        if (!$stmt_delete->execute()) {
            throw new Exception("Failed to delete old invoice items: " . $stmt_delete->error);
        }
        $stmt_delete->close();

        // 2. Insert new line items and calculate total amount
        $total_amount = 0;
        $stmt_insert = $conn->prepare("INSERT INTO invoice_items (invoice_id, description, quantity, unit_price, total) VALUES (?, ?, ?, ?, ?)");
        
        foreach ($items as $item) {
            $description = $item['description'] ?? 'No description';
            $quantity = filter_var($item['quantity'], FILTER_VALIDATE_INT, ['options' => ['default' => 0]]);
            $unit_price = filter_var($item['unit_price'], FILTER_VALIDATE_FLOAT, ['options' => ['default' => 0]]);
            $item_total = $quantity * $unit_price;
            $total_amount += $item_total;

            $stmt_insert->bind_param("isidd", $invoice_id, $description, $quantity, $unit_price, $item_total);
            if (!$stmt_insert->execute()) {
                throw new Exception("Failed to insert invoice item: " . $stmt_insert->error);
            }
        }
        $stmt_insert->close();

        // 3. Apply discount and tax to the final amount
        $final_amount = ($total_amount - ($discount ?? 0)) + ($tax ?? 0);

        // 4. Update the main invoice record with new totals
        $stmt_update_invoice = $conn->prepare("UPDATE invoices SET amount = ?, discount = ?, tax = ? WHERE id = ?");
        $stmt_update_invoice->bind_param("dddi", $final_amount, $discount, $tax, $invoice_id);
        if (!$stmt_update_invoice->execute()) {
            throw new Exception("Failed to update invoice: " . $stmt_update_invoice->error);
        }
        $stmt_update_invoice->close();
        
        $conn->commit();
        return ['success' => true, 'message' => 'Invoice updated successfully.'];

    } catch (Exception $e) {
        $conn->rollback();
        error_log("Update invoice error for invoice_id: $invoice_id - " . $e->getMessage());
        throw $e;
    }
}

/**
 * Handles bulk deletion of invoices.
 * Before deleting invoices, it deletes any associated bookings
 * to satisfy foreign key constraints.
 *
 * @param mysqli $conn The database connection object.
 * @return array Response array with success status and message.
 * @throws Exception If no invoice IDs are provided or a database error occurs.
 */
function handleDeleteBulk($conn) {
    $invoice_ids = $_POST['invoice_ids'] ?? [];
    if (empty($invoice_ids) || !is_array($invoice_ids)) {
        throw new Exception("No invoice IDs provided for bulk deletion.");
    }

    $conn->begin_transaction();

    try {
        $placeholders = implode(',', array_fill(0, count($invoice_ids), '?'));
        $types = str_repeat('i', count($invoice_ids));
        
        // 1. Get booking IDs associated with these invoices
        $stmt_fetch_bookings = $conn->prepare("SELECT id FROM bookings WHERE invoice_id IN ($placeholders)");
        $stmt_fetch_bookings->bind_param($types, ...$invoice_ids);
        $stmt_fetch_bookings->execute();
        $result_bookings = $stmt_fetch_bookings->get_result();
        $booking_ids_to_delete = [];
        while($row = $result_bookings->fetch_assoc()) {
            $booking_ids_to_delete[] = $row['id'];
        }
        $stmt_fetch_bookings->close();

        // 2. If there are associated bookings, delete them
        if (!empty($booking_ids_to_delete)) {
            $booking_placeholders = implode(',', array_fill(0, count($booking_ids_to_delete), '?'));
            $booking_types = str_repeat('i', count($booking_ids_to_delete));
            
            $stmt_delete_bookings = $conn->prepare("DELETE FROM bookings WHERE id IN ($booking_placeholders)");
            $stmt_delete_bookings->bind_param($booking_types, ...$booking_ids_to_delete);
            if (!$stmt_delete_bookings->execute()) {
                throw new Exception("Failed to delete associated bookings: " . $stmt_delete_bookings->error);
            }
            $stmt_delete_bookings->close();
        }

        // 3. Delete the invoices
        $stmt_delete_invoices = $conn->prepare("DELETE FROM invoices WHERE id IN ($placeholders)");
        $stmt_delete_invoices->bind_param($types, ...$invoice_ids);
        if (!$stmt_delete_invoices->execute()) {
            throw new Exception("Failed to delete invoices: " . $stmt_delete_invoices->error);
        }
        $stmt_delete_invoices->close();

        $conn->commit();
        return ['success' => true, 'message' => 'Selected invoices and their associated bookings have been deleted.'];

    } catch (Exception $e) {
        $conn->rollback();
        error_log("Bulk delete error: " . $e->getMessage());
        throw $e;
    }
}