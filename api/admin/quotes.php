<?php
// api/admin/quotes.php - Handles admin actions related to quotes

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

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); // Method Not Allowed
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit;
}

// --- Input Processing ---
$action = $_POST['action'] ?? '';
$quoteId = filter_input(INPUT_POST, 'quote_id', FILTER_VALIDATE_INT);

// --- Action Routing ---
try {
    // CSRF token validation with enhanced logging
    if (!isset($_POST['csrf_token'])) {
        error_log("CSRF token missing in POST data for action: $action");
        throw new Exception('CSRF token missing. Please refresh and try again.');
    }
    // error_log("Received CSRF token: " . $_POST['csrf_token']); // Uncomment for debugging
    validate_csrf_token(); // This function will throw an exception if token is invalid

    switch ($action) {
        case 'submit_quote':
            handleSubmitQuote($conn);
            break;
        case 'resend_quote':
            handleResendQuote($conn, $quoteId);
            break;
        case 'reject_quote':
            handleRejectQuote($conn, $quoteId);
            break;
        case 'delete_bulk':
            handleDeleteBulk($conn);
            break;
        case 'update_items_only': // New action to update only item details
            handleUpdateItemsOnly($conn);
            break;
        case 'update_status': // New action to update quote status for junk removal
            handleUpdateStatus($conn);
            break;
        default:
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid action specified.']);
            break;
    }
} catch (Exception $e) {
    http_response_code(500); // Internal Server Error
    error_log("Admin Quote API Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'An internal server error occurred. ' . $e->getMessage()]);
} finally {
    if (isset($conn) && $conn->ping()) {
        $conn->close();
    }
}


// --- Helper Functions for Item Details Management ---

/**
 * Deletes existing item details for a quote based on service type.
 */
function deleteQuoteItemDetails($conn, $quoteId, $serviceType) {
    if ($serviceType === 'equipment_rental') {
        $stmt = $conn->prepare("DELETE FROM quote_equipment_details WHERE quote_id = ?");
    } elseif ($serviceType === 'junk_removal') {
        $stmt = $conn->prepare("DELETE FROM junk_removal_details WHERE quote_id = ?");
    } else {
        return; // No specific details table for other types
    }
    $stmt->bind_param("i", $quoteId);
    $stmt->execute();
    $stmt->close();
}

/**
 * Inserts new item details for a quote based on service type.
 */
function insertQuoteItemDetails($conn, $quoteId, $serviceType, $items) {
    if ($serviceType === 'equipment_rental') {
        $stmt = $conn->prepare("INSERT INTO quote_equipment_details (quote_id, equipment_name, quantity, duration_days, specific_needs) VALUES (?, ?, ?, ?, ?)");
        foreach ($items as $item) {
            $equipmentName = $item['equipment_name'] ?? 'N/A';
            $quantity = filter_var($item['quantity'], FILTER_VALIDATE_INT, ['options' => ['default' => 1]]);
            $durationDays = filter_var($item['duration_days'], FILTER_VALIDATE_INT) === false ? null : (int)$item['duration_days'];
            $specificNeeds = $item['specific_needs'] ?? null;
            $stmt->bind_param("isiss", $quoteId, $equipmentName, $quantity, $durationDays, $specificNeeds);
            $stmt->execute();
        }
    } elseif ($serviceType === 'junk_removal') {
        // For junk removal, we update the single JSON field
        $final_junk_items_json = json_encode($items); // The entire array is one JSON string
        // Check if a record already exists, if so UPDATE, else INSERT
        $stmt_check = $conn->prepare("SELECT COUNT(*) FROM junk_removal_details WHERE quote_id = ?");
        $stmt_check->bind_param("i", $quoteId);
        $stmt_check->execute();
        $exists = $stmt_check->get_result()->fetch_row()[0] > 0;
        $stmt_check->close();

        if ($exists) {
            $stmt = $conn->prepare("UPDATE junk_removal_details SET junk_items_json = ? WHERE quote_id = ?");
            $stmt->bind_param("si", $final_junk_items_json, $quoteId);
        } else {
            // This case might not be hit if AI creates the draft, but as a fallback
            $stmt = $conn->prepare("INSERT INTO junk_removal_details (quote_id, junk_items_json) VALUES (?, ?)");
            $stmt->bind_param("is", $quoteId, $final_junk_items_json);
        }
        $stmt->execute();
    }
    $stmt->close();
}


// --- New Handler for updating only item details ---
function handleUpdateItemsOnly($conn) {
    $quoteId = filter_input(INPUT_POST, 'quote_id', FILTER_VALIDATE_INT);
    $serviceType = $_POST['service_type'] ?? '';
    $items = json_decode($_POST['items'] ?? '[]', true);

    if (!$quoteId || empty($serviceType) || !is_array($items)) {
        throw new Exception('Invalid parameters for item update.');
    }

    $conn->begin_transaction();
    try {
        // Verify the quote exists and belongs to the correct service type
        $stmt_verify = $conn->prepare("SELECT id FROM quotes WHERE id = ? AND service_type = ?");
        $stmt_verify->bind_param("is", $quoteId, $serviceType);
        $stmt_verify->execute();
        if ($stmt_verify->get_result()->num_rows === 0) {
            throw new Exception('Quote not found or service type mismatch.');
        }
        $stmt_verify->close();

        deleteQuoteItemDetails($conn, $quoteId, $serviceType);
        insertQuoteItemDetails($conn, $quoteId, $serviceType, $items);

        $conn->commit();
        echo json_encode(['success' => true, 'message' => 'Item details updated successfully!']);
    } catch (Exception $e) {
        $conn->rollback();
        throw $e;
    }
}


// --- Main Handler for Submitting a Quote ---
function handleSubmitQuote($conn) {
    // --- Input Validation ---
    $quoteId = filter_input(INPUT_POST, 'quote_id', FILTER_VALIDATE_INT);
    $serviceType = $_POST['service_type'] ?? ''; // New: Get service type from form
    $items = json_decode($_POST['items'] ?? '[]', true); // New: Get items from form

    // Conditional pricing fields
    $quotedPrice = null; // Will be set based on service type
    $totalCost = filter_input(INPUT_POST, 'total_cost', FILTER_VALIDATE_FLOAT); // For junk removal
    $basePrice = filter_input(INPUT_POST, 'quoted_price', FILTER_VALIDATE_FLOAT); // For equipment rental

    $dailyRate = filter_input(INPUT_POST, 'daily_rate', FILTER_VALIDATE_FLOAT); 
    $relocationCharge = filter_input(INPUT_POST, 'relocation_charge', FILTER_VALIDATE_FLOAT); 
    $isRelocationIncluded = filter_input(INPUT_POST, 'is_relocation_included', FILTER_VALIDATE_INT) === 1; 
    $swapCharge = filter_input(INPUT_POST, 'swap_charge', FILTER_VALIDATE_FLOAT); 
    $isSwapIncluded = filter_input(INPUT_POST, 'is_swap_included', FILTER_VALIDATE_INT) === 1; 

    $discount = filter_input(INPUT_POST, 'discount', FILTER_VALIDATE_FLOAT);
    $tax = filter_input(INPUT_POST, 'tax', FILTER_VALIDATE_FLOAT);
    $adminNotes = trim($_POST['admin_notes'] ?? '');
    $attachmentPath = null;

    if (!$quoteId || empty($serviceType) || !is_array($items)) {
        throw new Exception('A valid Quote ID, service type, and items are required.');
    }
    
    // Set quotedPrice based on service type
    if ($serviceType === 'equipment_rental') {
        if ($basePrice === false || $basePrice < 0) {
            throw new Exception('A valid Base Price is required for equipment rental (must be 0 or greater).');
        }
        $quotedPrice = $basePrice;
    } elseif ($serviceType === 'junk_removal') {
        if ($totalCost === false || $totalCost < 0) {
            throw new Exception('A valid Total Cost is required for junk removal (must be 0 or greater).');
        }
        $quotedPrice = $totalCost; // For junk removal, 'quoted_price' column stores the 'Total Cost'
        // Set other equipment-specific charges to null/0 for junk removal
        $dailyRate = 0; $relocationCharge = 0; $isRelocationIncluded = 0; $swapCharge = 0; $isSwapIncluded = 0;
    } else {
        throw new Exception('Unsupported service type.');
    }

    $conn->begin_transaction();

    // Fetch customer info for email/notification
    $stmt_fetch = $conn->prepare("SELECT u.id, u.email, u.first_name FROM users u JOIN quotes q ON u.id = q.user_id WHERE q.id = ?");
    $stmt_fetch->bind_param("i", $quoteId);
    $stmt_fetch->execute();
    $quote_user_data = $stmt_fetch->get_result()->fetch_assoc();
    $stmt_fetch->close();
    if (!$quote_user_data) throw new Exception('User for the quote not found.');

    // Handle file upload
    if (isset($_FILES['attachment']) && $_FILES['attachment']['error'] == UPLOAD_ERR_OK) {
        $uploadDir = __DIR__ . '/../../uploads/quote_attachments/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        $fileName = time() . '_' . basename($_FILES['attachment']['name']);
        $uploadFile = $uploadDir . $fileName;

        if (move_uploaded_file($_FILES['attachment']['tmp_name'], $uploadFile)) {
            $attachmentPath = '/uploads/quote_attachments/' . $fileName;
        } else {
            throw new Exception('Failed to move uploaded file.');
        }
    }


    // 1. Update the quote in the database
    $stmt_update = $conn->prepare("UPDATE quotes SET status = 'quoted', quoted_price = ?, daily_rate = ?, relocation_charge = ?, is_relocation_included = ?, swap_charge = ?, is_swap_included = ?, discount = ?, tax = ?, admin_notes = ?, attachment_path = ? WHERE id = ?");
    $stmt_update->bind_param("dddiddddssi", 
        $quotedPrice, 
        $dailyRate, 
        $relocationCharge, 
        $isRelocationIncluded, 
        $swapCharge, 
        $isSwapIncluded,     
        $discount, 
        $tax, 
        $adminNotes, 
        $attachmentPath, 
        $quoteId
    );

    if (!$stmt_update->execute()) { 
        throw new Exception('Failed to update quote in the database: ' . $stmt_update->error);
    }
    $stmt_update->close();

    // 2. Update the item details (delete old, insert new)
    deleteQuoteItemDetails($conn, $quoteId, $serviceType);
    insertQuoteItemDetails($conn, $quoteId, $serviceType, $items);


    // 3. Prepare and send email notification
    $customerEmail = $quote_user_data['email'];
    // Calculate final price for email ONLY based on quoted_price, discount, and tax
    $final_email_price = ($quotedPrice ?? 0) - ($discount ?? 0) + ($tax ?? 0);
    $final_email_price = max(0, $final_email_price); // Ensure not negative

    $template_vars = [
        'template_companyName' => getSystemSetting('company_name') ?? 'CAT Dump',
        'template_quoteId' => $quoteId,
        'template_quotedPrice' => number_format($final_email_price, 2), 
        'template_adminNotes' => $adminNotes,
        'template_customerQuoteLink' => "https://{$_SERVER['HTTP_HOST']}/customer/dashboard.php#quotes?quote_id={$quoteId}"
    ];
    ob_start();
    extract($template_vars);
    include __DIR__ . '/../../includes/mail_templates/quote_ready_email.php';
    $emailBody = ob_get_clean();
    sendEmail($customerEmail, "Your Quote #Q{$quoteId} is Ready!", $emailBody);

    // 4. Create a system notification for the user
    $notification_message = "Your quote #{$quoteId} is ready! The quoted price is $" . number_format($final_email_price, 2) . ".";
    $notification_link = "quotes?quote_id={$quoteId}";
    $stmt_notify = $conn->prepare("INSERT INTO notifications (user_id, type, message, link) VALUES (?, 'new_quote', ?, ?)");
    $stmt_notify->bind_param("iss", $quote_user_data['id'], $notification_message, $notification_link);
    $stmt_notify->execute();
    $stmt_notify->close();

    $conn->commit();
    echo json_encode(['success' => true, 'message' => 'Quote submitted and customer notified!']);
}

/**
 * Rejects a quote and notifies the customer.
 */
function handleRejectQuote($conn, $quoteId) {
    $conn->begin_transaction();

    // Fetch customer info for notification
    $stmt_fetch = $conn->prepare("SELECT u.id, u.email, u.first_name FROM users u JOIN quotes q ON u.id = q.user_id WHERE q.id = ?");
    $stmt_fetch->bind_param("i", $quoteId);
    $stmt_fetch->execute();
    $quote_user_data = $stmt_fetch->get_result()->fetch_assoc();
    $stmt_fetch->close();
    if (!$quote_user_data) throw new Exception('User for the quote not found.');

    // 1. Update quote status
    $stmt_update = $conn->prepare("UPDATE quotes SET status = 'rejected' WHERE id = ?");
    $stmt_update->bind_param("i", $quoteId);
    if (!$stmt_update->execute()) { 
        throw new Exception('Failed to update quote status: ' . $stmt_update->error);
    }
    $stmt_update->close();

    // 2. Notify customer via email
    $emailBody = "<p>Dear {$quote_user_data['first_name']},</p><p>We regret to inform you that your quote request #Q{$quoteId} has been rejected at this time. Please contact us if you have any questions.</p>";
    sendEmail($quote_user_data['email'], "Update on your Quote Request #Q{$quoteId}", $emailBody);

    // 3. Create system notification
    $notification_message = "Your quote request #{$quoteId} has been rejected.";
    $notification_link = "quotes?quote_id={$quoteId}";
    $stmt_notify = $conn->prepare("INSERT INTO notifications (user_id, type, message, link) VALUES (?, 'quote_rejected', ?, ?)");
    $stmt_notify->bind_param("iss", $quote_user_data['id'], $notification_message, $notification_link);
    if (!$stmt_notify->execute()) {
        error_log('Failed to create notification: ' . $stmt_notify->error);
    }
    $stmt_notify->close();

    $conn->commit();
    echo json_encode(['success' => true, 'message' => 'Quote has been rejected and the customer notified.']);
}

/**
 * Re-sends the quote notification email to the customer.
 */
function handleResendQuote($conn, $quoteId) {
    // Fetch current quote details to get quoted price and admin notes
    $stmt_fetch = $conn->prepare("
        SELECT q.quoted_price, q.admin_notes, q.discount, q.tax, u.email, u.first_name
        FROM quotes q
        JOIN users u ON q.user_id = u.id
        WHERE q.id = ? AND q.status = 'quoted'
    ");
    $stmt_fetch->bind_param("i", $quoteId);
    $stmt_fetch->execute();
    $quote_data = $stmt_fetch->get_result()->fetch_assoc();
    $stmt_fetch->close();

    if (!$quote_data) {
        throw new Exception('Quote not found or is not in a "quoted" status.');
    }

    // Calculate final price for email
    $final_email_price = ($quote_data['quoted_price'] ?? 0) - ($quote_data['discount'] ?? 0) + ($quote_data['tax'] ?? 0);
    $final_email_price = max(0, $final_email_price); // Ensure not negative

    // Prepare and send email notification
    $template_vars = [
        'template_companyName' => getSystemSetting('company_name') ?? 'CAT Dump',
        'template_quoteId' => $quoteId,
        'template_quotedPrice' => number_format($final_email_price, 2), 
        'template_adminNotes' => $quote_data['admin_notes'],
        'template_customerQuoteLink' => "https://{$_SERVER['HTTP_HOST']}/customer/dashboard.php#quotes?quote_id={$quoteId}"
    ];
    ob_start();
    extract($template_vars);
    include __DIR__ . '/../../includes/mail_templates/quote_ready_email.php';
    $emailBody = ob_get_clean();

    if (sendEmail($quote_data['email'], "[Resend] Your Quote #Q{$quoteId} is Ready!", $emailBody)) {
        echo json_encode(['success' => true, 'message' => 'Quote resent successfully!']);
    } else {
        throw new Exception('Failed to resend email notification.');
    }
}

/**
 * Deletes multiple quotes in bulk.
 */
function handleDeleteBulk($conn) {
    $quote_ids = $_POST['quote_ids'] ?? [];
    if (empty($quote_ids) || !is_array($quote_ids)) {
        throw new Exception("No quote IDs provided for bulk deletion.");
    }

    $conn->begin_transaction();

    try {
        $placeholders = implode(',', array_fill(0, count($quote_ids), '?'));
        $types = str_repeat('i', count($quote_ids));
        
        // 1. Get booking IDs associated with these invoices
        $stmt_fetch_bookings = $conn->prepare("SELECT id FROM bookings WHERE invoice_id IN (SELECT id FROM invoices WHERE quote_id IN ($placeholders))");
        $stmt_fetch_bookings->bind_param($types, ...$quote_ids);
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
        $stmt_delete_quotes = $conn->prepare("DELETE FROM quotes WHERE id IN ($placeholders)");
        $stmt_delete_quotes->bind_param($types, ...$quote_ids);
        if (!$stmt_delete_quotes->execute()) {
            throw new Exception("Failed to delete quotes: " . $stmt_delete_quotes->error);
        }
        $stmt_delete_quotes->close();

        $conn->commit();
        echo json_encode(['success' => true, 'message' => 'Selected quotes and their associated data have been deleted.']);

    } catch (Exception $e) {
        $conn->rollback();
        error_log("Bulk delete error: " . $e->getMessage());
        throw $e;
    }
}

/**
 * Handles updating the status of a single quote (e.g., from pending to quoted or cancelled).
 * This is used specifically by the junk_removal admin page to update quote status.
 *
 * @param mysqli $conn The database connection object.
 * @return array Response array with success status and message.
 * @throws Exception If parameters are invalid, quote not found, or database error occurs.
 */
function handleUpdateStatus($conn) {
    $quote_id = filter_input(INPUT_POST, 'quote_id', FILTER_VALIDATE_INT);
    $new_status = $_POST['status'] ?? '';

    if (!$quote_id || empty($new_status)) {
        throw new Exception('Quote ID and new status are required.');
    }
    
    // Validate the new status to ensure it's one of the allowed enum values
    $allowed_statuses = ['pending', 'quoted', 'accepted', 'rejected', 'converted_to_booking', 'cancelled'];
    if (!in_array($new_status, $allowed_statuses)) {
        throw new Exception('Invalid status provided.');
    }

    $conn->begin_transaction();

    try {
        // 1. Fetch current quote and customer details for notification
        $stmt_fetch = $conn->prepare("
            SELECT q.status AS old_status, u.id as user_id, u.first_name, u.email
            FROM quotes q 
            JOIN users u ON q.user_id = u.id 
            WHERE q.id = ?
        ");
        $stmt_fetch->bind_param("i", $quote_id);
        $stmt_fetch->execute();
        $quote_data = $stmt_fetch->get_result()->fetch_assoc();
        $stmt_fetch->close();

        if (!$quote_data) {
            throw new Exception('Quote or associated user not found.');
        }
        if ($quote_data['old_status'] === $new_status) {
            $conn->rollback();
            return ['success' => true, 'message' => 'Quote status is already set. No update needed.'];
        }

        // 2. Update the quote status
        $stmt_update = $conn->prepare("UPDATE quotes SET status = ? WHERE id = ?");
        $stmt_update->bind_param("si", $new_status, $quote_id);
        if (!$stmt_update->execute()) {
            throw new Exception("Database error on status update: " . $stmt_update->error);
        }
        $stmt_update->close();

        // 3. Notify Customer about status update
        $email_subject = "Update on your quote #Q{$quote_id}";
        $email_body = "<p>Dear {$quote_data['first_name']},</p><p>The status for your quote #<strong>Q{$quote_id}</strong> has been updated to: <strong>" . strtoupper(str_replace('_', ' ', $new_status)) . "</strong>.</p>";
        sendEmail($quote_data['email'], $email_subject, $email_body);

        // 4. Create system notification
        $notification_message = "Your quote #Q{$quote_id} status has been updated to: " . ucwords(str_replace('_', ' ', $new_status)) . ".";
        $notification_link = "quotes?quote_id={$quote_id}";
        $stmt_notify = $conn->prepare("INSERT INTO notifications (user_id, type, message, link) VALUES (?, 'quote_status_update', ?, ?)"); // Using a generic 'quote_status_update' type
        $stmt_notify->bind_param("iss", $quote_data['user_id'], $notification_message, $notification_link);
        $stmt_notify->execute();
        $stmt_notify->close();

        $conn->commit();
        return ['success' => true, 'message' => "Quote status updated to '{$new_status}' and customer notified."];

    } catch (Exception $e) {
        $conn->rollback();
        error_log("Update quote status error for quote_id: $quote_id, status: $new_status - " . $e->getMessage());
        throw $e;
    }
}