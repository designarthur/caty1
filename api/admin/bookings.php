<?php
// api/admin/bookings.php - Admin API for Bookings Management

// --- Setup & Includes ---
session_start();
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../includes/functions.php';

header('Content-Type: application/json');

// --- Security & Authorization ---
if (!is_logged_in() || !has_role('admin')) {
    http_response_code(403); // Forbidden
    echo json_encode(['success' => false, 'message' => 'Unauthorized access.']);
    exit;
}

// --- Request Routing ---
$request_method = $_SERVER['REQUEST_METHOD'];
$action = $_REQUEST['action'] ?? ''; // Use $_REQUEST to handle GET or POST actions

try {
    if ($request_method === 'POST') {
        switch ($action) {
            case 'update_status':
                handleUpdateStatus($conn);
                break;
            case 'assign_vendor':
                handleAssignVendor($conn);
                break;
            case 'add_charge':
                handleAddCharge($conn);
                break;
            case 'approve_extension':
                handleApproveExtension($conn);
                break;
            case 'delete_bulk':
                handleDeleteBulk($conn);
                break;
            default:
                throw new Exception('Invalid POST action specified.');
        }
    } elseif ($request_method === 'GET') {
        switch ($action) {
            case 'get_booking_by_quote_id':
                handleGetBookingByQuoteId($conn);
                break;
            default:
                throw new Exception('Invalid GET action specified.');
        }
    } else {
        http_response_code(405); // Method Not Allowed
        throw new Exception('Invalid request method.');
    }
} catch (Exception $e) {
    // Catch any exceptions thrown from handler functions
    http_response_code(400); // Bad Request for most client-side errors
    error_log("Admin Bookings API Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
} finally {
    if (isset($conn) && $conn->ping()) {
        $conn->close();
    }
}


// --- Handler Functions ---

function handleUpdateStatus($conn) {
    $booking_id = filter_input(INPUT_POST, 'booking_id', FILTER_VALIDATE_INT);
    $newStatus = trim($_POST['status'] ?? '');
    $notes = "Status updated to " . ucwords(str_replace('_', ' ', $newStatus)) . " by admin.";

    if (!$booking_id || empty($newStatus)) {
        throw new Exception('Booking ID and new status are required.');
    }

    $allowedStatuses = [
        'pending', 'scheduled', 'assigned', 'pickedup', 'out_for_delivery',
        'delivered', 'in_use', 'awaiting_pickup', 'completed', 'cancelled',
        'relocated', 'swapped'
    ];
    if (!in_array($newStatus, $allowedStatuses)) {
        throw new Exception('Invalid status value provided.');
    }

    $conn->begin_transaction();

    $stmt_fetch = $conn->prepare("SELECT b.booking_number, b.status AS old_status, u.id as user_id, u.first_name, u.email FROM bookings b JOIN users u ON b.user_id = u.id WHERE b.id = ?");
    $stmt_fetch->bind_param("i", $booking_id);
    $stmt_fetch->execute();
    $booking_data = $stmt_fetch->get_result()->fetch_assoc();
    $stmt_fetch->close();

    if (!$booking_data) {
        throw new Exception("Booking not found.");
    }
    if ($booking_data['old_status'] === $newStatus) {
        echo json_encode(['success' => true, 'message' => 'Booking status is already set. No update needed.']);
        $conn->rollback();
        return;
    }

    $stmt_update = $conn->prepare("UPDATE bookings SET status = ? WHERE id = ?");
    $stmt_update->bind_param("si", $newStatus, $booking_id);
    if (!$stmt_update->execute()) {
        throw new Exception("Database error on status update: " . $stmt_update->error);
    }
    $stmt_update->close();

    $stmt_log = $conn->prepare("INSERT INTO booking_status_history (booking_id, status, notes) VALUES (?, ?, ?)");
    $stmt_log->bind_param("iss", $booking_id, $newStatus, $notes);
    if (!$stmt_log->execute()) {
        throw new Exception("Failed to log status history: " . $stmt_log->error);
    }
    $stmt_log->close();

    $notification_message = "Your booking #BK-{$booking_data['booking_number']} has been updated to: " . ucwords(str_replace('_', ' ', $newStatus)) . ".";
    $notification_link = "bookings?booking_id={$booking_id}";
    $stmt_notify = $conn->prepare("INSERT INTO notifications (user_id, type, message, link) VALUES (?, 'booking_status_update', ?, ?)");
    $stmt_notify->bind_param("iss", $booking_data['user_id'], $notification_message, $notification_link);
    $stmt_notify->execute();
    $stmt_notify->close();

    $emailBody = "<p>Dear {$booking_data['first_name']},</p><p>The status of your booking #BK-{$booking_data['booking_number']} has been updated to: <strong>" . ucwords(str_replace('_', ' ', $newStatus)) . "</strong>.</p>";
    sendEmail($booking_data['email'], "Update on your Booking #BK-{$booking_data['booking_number']}", $emailBody);

    $conn->commit();
    echo json_encode(['success' => true, 'message' => 'Booking status updated successfully!']);
}


function handleAssignVendor($conn) {
    $booking_id = filter_input(INPUT_POST, 'booking_id', FILTER_VALIDATE_INT);
    $vendor_id = filter_input(INPUT_POST, 'vendor_id', FILTER_VALIDATE_INT);

    if (!$booking_id || !$vendor_id) {
        throw new Exception('Booking ID and Vendor ID are required.');
    }

    $conn->begin_transaction();
    
    $stmt_update = $conn->prepare("UPDATE bookings SET vendor_id = ?, status = 'assigned' WHERE id = ?");
    $stmt_update->bind_param("ii", $vendor_id, $booking_id);
    $stmt_update->execute();
    
    if($stmt_update->affected_rows > 0) {
        $notes = "Booking assigned to a vendor by admin.";
        $stmt_log = $conn->prepare("INSERT INTO booking_status_history (booking_id, status, notes) VALUES (?, 'assigned', ?)");
        $stmt_log->bind_param("is", $booking_id, $notes);
        $stmt_log->execute();
        $stmt_log->close();
        
        $conn->commit();
        echo json_encode(['success' => true, 'message' => 'Vendor assigned successfully and status updated.']);
    } else {
        $conn->rollback();
        throw new Exception('Failed to assign vendor or vendor was already assigned.');
    }
    $stmt_update->close();
}

function handleAddCharge($conn) {
    $booking_id = filter_input(INPUT_POST, 'booking_id', FILTER_VALIDATE_INT);
    $charge_type = trim($_POST['charge_type'] ?? '');
    $amount = filter_input(INPUT_POST, 'amount', FILTER_VALIDATE_FLOAT);
    $description = trim($_POST['description'] ?? '');
    $admin_user_id = $_SESSION['user_id'];

    if (!$booking_id || empty($charge_type) || !$amount || $amount <= 0 || empty($description)) {
        throw new Exception('Booking ID, charge type, a valid amount, and description are required.');
    }
    
    $conn->begin_transaction();
    
    // 1. Fetch booking user for invoice creation
    $stmt_user = $conn->prepare("SELECT user_id, booking_number FROM bookings WHERE id = ?");
    $stmt_user->bind_param("i", $booking_id);
    $stmt_user->execute();
    $booking_data = $stmt_user->get_result()->fetch_assoc();
    $stmt_user->close();

    if(!$booking_data) {
        throw new Exception('Could not find the user associated with this booking.');
    }
    $user_id = $booking_data['user_id'];
    $booking_number = $booking_data['booking_number'];

    // 2. Insert into booking_charges
    $stmt_charge = $conn->prepare("INSERT INTO booking_charges (booking_id, charge_type, amount, description, created_by_admin_id) VALUES (?, ?, ?, ?, ?)");
    $stmt_charge->bind_param("isdsi", $booking_id, $charge_type, $amount, $description, $admin_user_id);
    if(!$stmt_charge->execute()) {
        throw new Exception("Failed to save the additional charge: " . $stmt_charge->error);
    }
    $charge_id = $conn->insert_id;
    $stmt_charge->close();

    // 3. Create a new invoice for this charge
    $invoice_number = 'INV-CHG-' . strtoupper(generateToken(6));
    $due_date = date('Y-m-d', strtotime('+14 days'));
    $notes = "Additional charge for Booking #{$booking_number}: " . $description;

    $stmt_invoice = $conn->prepare("INSERT INTO invoices (user_id, booking_id, invoice_number, amount, status, due_date, notes) VALUES (?, ?, ?, ?, 'pending', ?, ?)");
    $stmt_invoice->bind_param("iisdss", $user_id, $booking_id, $invoice_number, $amount, $due_date, $notes);
     if(!$stmt_invoice->execute()) {
        throw new Exception("Failed to create invoice for the charge: " . $stmt_invoice->error);
    }
    $invoice_id = $conn->insert_id;
    $stmt_invoice->close();
    
    // --- FIX: Add line item for additional charge ---
    $stmt_insert_item = $conn->prepare("INSERT INTO invoice_items (invoice_id, description, quantity, unit_price, total) VALUES (?, ?, 1, ?, ?)");
    $item_description = "Additional Charge ({$charge_type}) for Booking #{$booking_number}: {$description}";
    $stmt_insert_item->bind_param("isdd", $invoice_id, $item_description, $amount, $amount);
    if (!$stmt_insert_item->execute()) {
        throw new Exception('Failed to insert invoice item for additional charge.');
    }
    $stmt_insert_item->close();


    // 4. Link the charge to the new invoice
    $stmt_link = $conn->prepare("UPDATE booking_charges SET invoice_id = ? WHERE id = ?");
    $stmt_link->bind_param("ii", $invoice_id, $charge_id);
    $stmt_link->execute();
    $stmt_link->close();

    // 5. Notify Customer
    $notification_message = "An additional charge of $" . number_format($amount, 2) . " for '{$description}' has been added to Booking #{$booking_number}.";
    $notification_link = "invoices?invoice_id={$invoice_id}";
    $stmt_notify = $conn->prepare("INSERT INTO notifications (user_id, type, message, link) VALUES (?, 'payment_due', ?, ?)");
    $stmt_notify->bind_param("iss", $user_id, $notification_message, $notification_link);
    $stmt_notify->execute();
    $stmt_notify->close();

    $conn->commit();
    echo json_encode(['success' => true, 'message' => 'Additional charge added and invoice generated successfully.']);
}


function handleGetBookingByQuoteId($conn) {
    $quoteId = filter_input(INPUT_GET, 'quote_id', FILTER_VALIDATE_INT);
    if (!$quoteId) {
        throw new Exception('Quote ID is required.');
    }

    $stmt = $conn->prepare("SELECT b.id FROM bookings b JOIN invoices i ON b.invoice_id = i.id WHERE i.quote_id = ?");
    $stmt->bind_param("i", $quoteId);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $booking = $result->fetch_assoc();
        echo json_encode(['success' => true, 'booking_id' => $booking['id']]);
    } else {
        throw new Exception('No booking found for this quote.');
    }
    $stmt->close();
}

function handleApproveExtension($conn) {
    $booking_id = filter_input(INPUT_POST, 'booking_id', FILTER_VALIDATE_INT);
    $request_id = filter_input(INPUT_POST, 'extension_request_id', FILTER_VALIDATE_INT);
    $pricing_option = $_POST['pricing_option'] ?? 'daily_rate';
    $extension_days = filter_input(INPUT_POST, 'extension_days', FILTER_VALIDATE_INT);
    $daily_rate = filter_input(INPUT_POST, 'daily_rate', FILTER_VALIDATE_FLOAT);
    $custom_total_price = filter_input(INPUT_POST, 'custom_total_price', FILTER_VALIDATE_FLOAT);

    if (!$booking_id || !$request_id || !$extension_days) {
        throw new Exception('Booking ID, Request ID, and extension days are required.');
    }

    if ($pricing_option === 'daily_rate' && (!$daily_rate || $daily_rate <= 0)) {
        throw new Exception('A valid daily rate is required for this pricing method.');
    }
    if ($pricing_option === 'custom_total' && (!$custom_total_price || $custom_total_price <= 0)) {
        throw new Exception('A valid custom total price is required for this pricing method.');
    }

    $extension_cost = ($pricing_option === 'daily_rate') ? ($extension_days * $daily_rate) : $custom_total_price;

    $conn->begin_transaction();

    // 1. Fetch booking and user details
    $stmt_booking = $conn->prepare("SELECT * FROM bookings WHERE id = ?");
    $stmt_booking->bind_param("i", $booking_id);
    $stmt_booking->execute();
    $booking = $stmt_booking->get_result()->fetch_assoc();
    $stmt_booking->close();
    if (!$booking) throw new Exception('Booking not found.');

    // 2. Update the extension request status to 'approved'
    $stmt_update_req = $conn->prepare("UPDATE booking_extension_requests SET status = 'approved' WHERE id = ? AND status = 'pending'");
    $stmt_update_req->bind_param("i", $request_id);
    $stmt_update_req->execute();
    if ($stmt_update_req->affected_rows === 0) {
        throw new Exception('Extension request not found or already processed.');
    }
    $stmt_update_req->close();
    
    // 3. Create a new invoice for the extension cost
    $invoice_number = 'INV-EXT-' . strtoupper(generateToken(6));
    $due_date = date('Y-m-d', strtotime('+7 days'));
    $notes = "Rental extension of {$extension_days} days for Booking #{$booking['booking_number']}";
    $stmt_invoice = $conn->prepare("INSERT INTO invoices (user_id, booking_id, invoice_number, amount, status, due_date, notes) VALUES (?, ?, ?, ?, 'pending', ?, ?)");
    $stmt_invoice->bind_param("iisdss", $booking['user_id'], $booking_id, $invoice_number, $extension_cost, $due_date, $notes);
    $stmt_invoice->execute();
    $invoice_id = $conn->insert_id;
    $stmt_invoice->close();
    
    // --- FIX: Add line item for rental extension ---
    $stmt_insert_item = $conn->prepare("INSERT INTO invoice_items (invoice_id, description, quantity, unit_price, total) VALUES (?, ?, 1, ?, ?)");
    $item_description = "Rental Extension for Booking #{$booking['booking_number']} ({$extension_days} days)";
    $stmt_insert_item->bind_param("isdd", $invoice_id, $item_description, $extension_cost, $extension_cost);
    if (!$stmt_insert_item->execute()) {
        throw new Exception('Failed to insert invoice item for rental extension.');
    }
    $stmt_insert_item->close();


    // Link the new invoice to the extension request
    $stmt_link_inv = $conn->prepare("UPDATE booking_extension_requests SET invoice_id = ? WHERE id = ?");
    $stmt_link_inv->bind_param("ii", $invoice_id, $request_id);
    $stmt_link_inv->execute();
    $stmt_link_inv->close();


    // 4. Log the approval and invoice creation
    $charge_description = "Rental extension of {$extension_days} days approved by admin.";
    $stmt_charge = $conn->prepare("INSERT INTO booking_charges (booking_id, invoice_id, charge_type, amount, description, created_by_admin_id) VALUES (?, ?, 'rental_extension', ?, ?, ?)");
    $stmt_charge->bind_param("iidss", $booking_id, $invoice_id, $extension_cost, $charge_description, $_SESSION['user_id']);
    $stmt_charge->execute();
    $stmt_charge->close();
    
    // Note: The booking end_date is NOT updated here. It should only be updated AFTER the customer pays the extension invoice.
    // This logic is now in api/payments.php

    // 5. Notify the customer
    $notification_message = "Your rental extension request for Booking #{$booking['booking_number']} has been approved! Please pay the new invoice to confirm.";
    $notification_link = "bookings?booking_id={$booking_id}"; // Link them to the booking page to see the new button
    $stmt_notify = $conn->prepare("INSERT INTO notifications (user_id, type, message, link) VALUES (?, 'payment_due', ?, ?)");
    $stmt_notify->bind_param("iss", $booking['user_id'], $notification_message, $notification_link);
    $stmt_notify->execute();
    $stmt_notify->close();

    $conn->commit();
    echo json_encode(['success' => true, 'message' => 'Rental extension approved. An invoice has been sent to the customer.']);
}

/**
 * Handles bulk deletion of bookings.
 * This function deletes the booking records and relies on ON DELETE CASCADE
 * to remove associated records in `booking_charges`, `booking_extension_requests`,
 * `booking_status_history`, and `reviews`.
 * Invoices and quotes are NOT deleted by this function, as they may have broader financial context.
 *
 * @param mysqli $conn The database connection object.
 * @return array Response array with success status and message.
 * @throws Exception If no booking IDs are provided or a database error occurs.
 */
function handleDeleteBulk($conn) {
    $booking_ids = $_POST['booking_ids'] ?? [];
    if (empty($booking_ids) || !is_array($booking_ids)) {
        throw new Exception("No booking IDs provided for bulk deletion.");
    }

    $conn->begin_transaction();

    try {
        $placeholders = implode(',', array_fill(0, count($booking_ids), '?'));
        $types = str_repeat('i', count($booking_ids));
        
        $stmt_delete_bookings = $conn->prepare("DELETE FROM bookings WHERE id IN ($placeholders)");
        $stmt_delete_bookings->bind_param($types, ...$booking_ids);
        
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