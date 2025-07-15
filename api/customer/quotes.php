<?php
// api/customer/quotes.php - Handles customer actions on quotes (accept/reject, delete_bulk).

// --- Setup & Includes ---
session_start();
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../includes/functions.php';

header('Content-Type: application/json');

// --- Security & Authorization ---
if (!is_logged_in() || !has_role('customer')) {
    http_response_code(403); // Forbidden
    echo json_encode(['success' => false, 'message' => 'Unauthorized access.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); // Method Not Allowed
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit;
}

// --- CSRF Token Validation ---
try {
    validate_csrf_token();
} catch (Exception $e) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Invalid security token. Please refresh the page and try again.']);
    exit;
}

// --- Input Processing & Action Routing ---
$action = $_POST['action'] ?? '';
$user_id = $_SESSION['user_id'];
$quote_id = filter_input(INPUT_POST, 'quote_id', FILTER_VALIDATE_INT);


// Common logic for single quote actions
if ($action !== 'delete_bulk' && !$quote_id) {
    http_response_code(400); // Bad Request
    echo json_encode(['success' => false, 'message' => 'Invalid request. Missing action or quote ID.']);
    exit;
}

// --- Main Logic ---
try {
    if ($action !== 'delete_bulk') { // For single quote actions, verify ownership
        // First, verify the quote belongs to the user and is in a valid state for the action.
        // Fetch all relevant pricing details from the quote
        $stmt_verify = $conn->prepare("SELECT status, quoted_price, service_type, daily_rate, swap_charge, relocation_charge, discount, tax, is_swap_included, is_relocation_included FROM quotes WHERE id = ? AND user_id = ?");
        $stmt_verify->bind_param("ii", $quote_id, $user_id);
        $stmt_verify->execute();
        $quote = $stmt_verify->get_result()->fetch_assoc();
        $stmt_verify->close();

        if (!$quote) {
            throw new Exception('Quote not found or you do not have permission to access it.');
        }
    }


    switch ($action) {
        case 'accept_quote':
            if ($quote['status'] !== 'quoted') {
                throw new Exception('This quote cannot be accepted in its current state.');
            }
            $response = handleAcceptQuote($conn, $quote_id, $user_id, $quote);
            break;
        case 'reject_quote':
            if (!in_array($quote['status'], ['quoted', 'pending'])) {
                throw new Exception('This quote cannot be rejected in its current state.');
            }
            $response = handleRejectQuote($conn, $quote_id, $user_id);
            break;
        case 'submit_draft_quote':
             if ($quote['status'] !== 'customer_draft') {
                throw new Exception('This quote is not a draft and cannot be submitted.');
            }
            $response = handleSubmitDraftQuote($conn, $quote_id, $user_id);
            break;
        case 'delete_bulk':
            $response = handleDeleteBulk($conn, $user_id); // Pass user_id for ownership check
            break;
        default:
            throw new Exception('Invalid action specified.');
    }

    echo json_encode($response);

} catch (Exception $e) {
    http_response_code(400); // Use 400 for most action-related errors
    error_log("Customer Quote API Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
} finally {
    if (isset($conn) && $conn->ping()) {
        $conn->close();
    }
}

// --- Handler Functions ---

/**
 * Handles submitting a draft quote, updating its status to 'pending'.
 */
function handleSubmitDraftQuote($conn, $quote_id, $user_id) {
    // Additional details from the form can be processed here if needed,
    // for now, we'll just update the status.
    $stmt = $conn->prepare("UPDATE quotes SET status = 'pending' WHERE id = ? AND user_id = ? AND status = 'customer_draft'");
    $stmt->bind_param("ii", $quote_id, $user_id);

    if ($stmt->execute()) {
        if ($stmt->affected_rows > 0) {
            // Optionally, notify admin about the new quote submission
            return ['success' => true, 'message' => 'Your quote request has been successfully submitted to our team for review.'];
        } else {
            return ['success' => false, 'message' => 'Could not submit quote. It might have been already submitted.'];
        }
    } else {
        throw new Exception('Failed to submit quote request.');
    }
}


/**
 * Handles accepting a quote, which creates a new invoice and populates its line items.
 *
 * @param mysqli $conn The database connection object.
 * @param int $quote_id The ID of the quote being accepted.
 * @param int $user_id The ID of the user accepting the quote.
 * @param array $quote_data The full quote data including pricing and service type.
 * @return array A success/failure response array, including the new invoice_id on success.
 * @throws Exception If invoice creation or item insertion fails.
 */
function handleAcceptQuote($conn, $quote_id, $user_id, $quote_data) {
    $conn->begin_transaction();

    try {
        // Explicitly set timezone to avoid any server configuration issues with date functions.
        date_default_timezone_set('UTC'); 

        // 1. Update Quote Status to 'accepted'
        $stmt_update = $conn->prepare("UPDATE quotes SET status = 'accepted' WHERE id = ?");
        $stmt_update->bind_param("i", $quote_id);
        if (!$stmt_update->execute()) {
            throw new Exception('Failed to update quote status: ' . $stmt_update->error);
        }
        $stmt_update->close();

        // Calculate final amount for the initial invoice: base quoted_price - discount + tax
        // IMPORTANT: Relocation and Swap charges are NOT included here unless explicitly part of the base quote.
        $initial_invoice_amount = (float)($quote_data['quoted_price'] ?? 0) - (float)($quote_data['discount'] ?? 0) + (float)($quote_data['tax'] ?? 0);
        $initial_invoice_amount = max(0, $initial_invoice_amount); // Ensure amount is not negative

        // 2. Create a new Invoice for the initial service
        $invoice_number = 'INV-' . strtoupper(generateToken(8));
        
        // Calculate due_date
        $seven_days_later_timestamp = strtotime('+7 days');
        if ($seven_days_later_timestamp === false) {
            error_log("Failed to generate timestamp for due_date in handleAcceptQuote.");
            throw new Exception('Failed to generate a valid timestamp for the due date.');
        }
        $due_date_str = date('Y-m-d', $seven_days_later_timestamp); // Ensure it's a string variable
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $due_date_str)) { // Use the string variable for validation
            error_log("Invalid due_date format: " . $due_date_str);
            throw new Exception('Generated due date is not in valid YYYY-MM-DD format.');
        }

        $stmt_invoice = $conn->prepare("INSERT INTO invoices (quote_id, user_id, invoice_number, amount, status, due_date, discount, tax) VALUES (?, ?, ?, ?, 'pending', ?, ?, ?)");
        // Pass variables by reference directly
        $stmt_invoice->bind_param("iisdsdd", $quote_id, $user_id, $invoice_number, $initial_invoice_amount, $due_date_str, $quote_data['discount'], $quote_data['tax']);
        if (!$stmt_invoice->execute()) {
            error_log('Failed to create invoice: ' . $stmt_invoice->error . ' with due_date: ' . $due_date_str);
            throw new Exception('Failed to create invoice: ' . $stmt_invoice->error);
        }
        $invoice_id = $conn->insert_id;
        $stmt_invoice->close();

        // 3. Populate Invoice Items based on service type
        $stmt_insert_item = $conn->prepare("INSERT INTO invoice_items (invoice_id, description, quantity, unit_price, total) VALUES (?, ?, ?, ?, ?)");
        
        // Add the main service as a line item
        $description = ucwords(str_replace('_', ' ', $quote_data['service_type'])) . " (Quote #{$quote_id})";
        $quantity = 1;
        $unit_price = (float)($quote_data['quoted_price'] ?? 0); // Ensure float
        $total = $unit_price;
        $stmt_insert_item->bind_param("isidd", $invoice_id, $description, $quantity, $unit_price, $total);
        if (!$stmt_insert_item->execute()) {
            throw new Exception('Failed to insert main service item into invoice_items: ' . $stmt_insert_item->error);
        }

        // Add specific details for equipment rental if applicable
        if ($quote_data['service_type'] === 'equipment_rental') {
            $stmt_eq_details = $conn->prepare("SELECT equipment_name, quantity, duration_days, specific_needs FROM quote_equipment_details WHERE quote_id = ?");
            $stmt_eq_details->bind_param("i", $quote_id);
            $stmt_eq_details->execute();
            $eq_details = $stmt_eq_details->get_result();
            $stmt_eq_details->close();

            while($item = $eq_details->fetch_assoc()){
                $item_desc = " - " . htmlspecialchars($item['equipment_name']);
                if (!empty($item['duration_days'])) {
                    $item_desc .= " ({$item['duration_days']} days)";
                }
                if (!empty($item['specific_needs'])) {
                    $item_desc .= " - " . htmlspecialchars($item['specific_needs']);
                }
                // These are descriptive items, not individually priced on the invoice
                $zero_price = 0; // Use a variable for literal zero
                $stmt_insert_item->bind_param("isidd", $invoice_id, $item_desc, $item['quantity'], $zero_price, $zero_price);
                if (!$stmt_insert_item->execute()) {
                    error_log('Failed to insert detailed equipment item: ' . $stmt_insert_item->error);
                }
            }
        } elseif ($quote_data['service_type'] === 'junk_removal') {
            $stmt_junk_details = $conn->prepare("SELECT junk_items_json, recommended_dumpster_size, additional_comment FROM junk_removal_details WHERE quote_id = ?");
            $stmt_junk_details->bind_param("i", $quote_id);
            $stmt_junk_details->execute();
            $junk_details = $stmt_junk_details->get_result()->fetch_assoc();
            $stmt_junk_details->close();

            if (!empty($junk_details['junk_items_json'])) {
                $parsed_junk_items = json_decode($junk_details['junk_items_json'], true);
                foreach ($parsed_junk_items as $item) {
                    $item_desc = " - " . ($item['itemType'] ?? 'Unknown Item');
                    $item_qty = $item['quantity'] ?? 1;
                    // These are descriptive items, not priced individually
                    $zero_price = 0; // Use a variable for literal zero
                    $stmt_insert_item->bind_param("isidd", $invoice_id, $item_desc, $item_qty, $zero_price, $zero_price);
                    if (!$stmt_insert_item->execute()) {
                        error_log('Failed to insert detailed junk item: ' . $stmt_insert_item->error);
                    }
                }
            }
            if (!empty($junk_details['recommended_dumpster_size'])) {
                $item_desc = " - Recommended Dumpster Size: " . $junk_details['recommended_dumpster_size'];
                $zero_price = 0; // Use a variable for literal zero
                $stmt_insert_item->bind_param("isidd", $invoice_id, $item_desc, 1, $zero_price, $zero_price);
                if (!$stmt_insert_item->execute()) {
                    error_log('Failed to insert recommended dumpster size item: ' . $stmt_insert_item->error);
                }
            }
            if (!empty($junk_details['additional_comment'])) {
                $item_desc = " - Additional Comments: " . $junk_details['additional_comment'];
                $zero_price = 0; // Use a variable for literal zero
                $stmt_insert_item->bind_param("isidd", $invoice_id, $item_desc, 1, $zero_price, $zero_price);
                if (!$stmt_insert_item->execute()) {
                    error_log('Failed to insert additional comment item: ' . $stmt_insert_item->error);
                }
            }
        }
        $stmt_insert_item->close();


        // 4. Create Notification for the customer to pay the new invoice
        $notification_message = "Quote #Q{$quote_id} accepted! Please pay the new invoice to confirm your booking.";
        $notification_link = "invoices?invoice_id={$invoice_id}";
        $stmt_notify = $conn->prepare("INSERT INTO notifications (user_id, type, message, link) VALUES (?, 'payment_due', ?, ?)");
        $stmt_notify->bind_param("iss", $user_id, $notification_message, $notification_link);
        if (!$stmt_notify->execute()) {
            error_log('Failed to create notification: ' . $stmt_notify->error);
        }
        $stmt_notify->close();

        $conn->commit();
        return [
            'success' => true,
            'message' => 'Quote accepted! Redirecting to your new invoice for payment.',
            'invoice_id' => $invoice_id
        ];

    } catch (Exception $e) {
        $conn->rollback();
        throw $e; // Re-throw to be caught by the main try-catch block
    }
}

/**
 * Handles rejecting a quote.
 *
 * @param mysqli $conn The database connection object.
 * @param int $quote_id The ID of the quote being rejected.
 * @param int $user_id The ID of the user rejecting the quote.
 * @return array A success/failure response array.
 * @throws Exception If quote status update or notification fails.
 */
function handleRejectQuote($conn, $quote_id, $user_id) {
    $conn->begin_transaction();

    try {
        // 1. Update Quote Status
        $stmt_update = $conn->prepare("UPDATE quotes SET status = 'rejected' WHERE id = ?");
        $stmt_update->bind_param("i", $quote_id);
        if (!$stmt_update->execute()) {
            throw new Exception('Failed to update quote status: ' . $stmt_update->error);
        }
        $stmt_update->close();
        
        // 2. Create Notification
        $notification_message = "You have rejected quote #Q{$quote_id}.";
        $notification_link = "quotes?quote_id={$quote_id}";
        $stmt_notify = $conn->prepare("INSERT INTO notifications (user_id, type, message, link) VALUES (?, 'quote_rejected', ?, ?)");
        $stmt_notify->bind_param("iss", $user_id, $notification_message, $notification_link);
        if (!$stmt_notify->execute()) {
            error_log('Failed to create notification: ' . $stmt_notify->error);
        }
        $stmt_notify->close();

        $conn->commit();
        return ['success' => true, 'message' => 'Quote has been rejected.'];

    } catch (Exception $e) {
        $conn->rollback();
        throw $e; // Re-throw to be caught by the main try-catch block
    }
}

/**
 * Handles bulk deletion of quotes for the customer.
 * Deletes quotes only if they belong to the current user.
 * Associated bookings are deleted if linked to the deleted invoice.
 *
 * @param mysqli $conn The database connection object.
 * @param int $user_id The ID of the logged-in user.
 * @return array Response array with success status and message.
 * @throws Exception If no quote IDs are provided or a database error occurs.
 */
function handleDeleteBulk($conn, $user_id) {
    $quote_ids = $_POST['quote_ids'] ?? [];
    if (empty($quote_ids) || !is_array($quote_ids)) {
        throw new Exception("No quote IDs provided for bulk deletion.");
    }

    // Filter to ensure only quotes owned by the current user are processed
    $filtered_quote_ids = [];
    $placeholders = implode(',', array_fill(0, count($quote_ids), '?'));
    $types = str_repeat('i', count($quote_ids));

    $stmt_check_ownership = $conn->prepare("SELECT id FROM quotes WHERE id IN ($placeholders) AND user_id = ?");
    $stmt_check_ownership->bind_param($types . 'i', ...array_merge($quote_ids, [$user_id]));
    $stmt_check_ownership->execute();
    $result_ownership = $stmt_check_ownership->get_result();
    while ($row = $result_ownership->fetch_assoc()) {
        $filtered_quote_ids[] = $row['id'];
    }
    $stmt_check_ownership->close();

    if (empty($filtered_quote_ids)) {
        throw new Exception("No valid quotes found for deletion or you do not have permission to delete these quotes.");
    }

    $conn->begin_transaction();

    try {
        $delete_placeholders = implode(',', array_fill(0, count($filtered_quote_ids), '?'));
        $delete_types = str_repeat('i', count($filtered_quote_ids));
        
        // 1. Get booking IDs associated with invoices linked to these quotes
        $stmt_fetch_bookings = $conn->prepare("SELECT b.id FROM bookings b JOIN invoices i ON b.invoice_id = i.id WHERE i.quote_id IN ($delete_placeholders)");
        $stmt_fetch_bookings->bind_param($delete_types, ...$filtered_quote_ids);
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

        // 3. Delete the quotes (this will cascade delete from junk_removal_details and quote_equipment_details if foreign keys are set up correctly)
        $stmt_delete_quotes = $conn->prepare("DELETE FROM quotes WHERE id IN ($delete_placeholders)");
        $stmt_delete_quotes->bind_param($delete_types, ...$filtered_quote_ids);
        if (!$stmt_delete_quotes->execute()) {
            throw new Exception("Failed to delete quotes: " . $stmt_delete_quotes->error);
        }
        $stmt_delete_quotes->close();

        $conn->commit();
        return ['success' => true, 'message' => 'Selected quotes and their associated data have been deleted.'];

    } catch (Exception $e) {
        $conn->rollback();
        error_log("Customer bulk delete quotes error: " . $e->getMessage());
        throw $e;
    }
}
