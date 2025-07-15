<?php
// api/customer/junk_removal_update.php - Handles customer updates to junk removal drafts

// --- Setup & Includes ---
session_start();
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../includes/functions.php'; // For generateToken (if used for invoice numbers in future)

header('Content-Type: application/json');

// --- Security & Authorization ---
if (!is_logged_in() || !has_role('customer')) {
    http_response_code(403); // Forbidden
    echo json_encode(['success' => false, 'message' => 'Unauthorized access. Please log in.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); // Method Not Allowed
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit;
}

$user_id = $_SESSION['user_id'];
$action = $_POST['action'] ?? '';
$quote_id = filter_input(INPUT_POST, 'quote_id', FILTER_VALIDATE_INT);

// Basic validation for common parameters
if (!$quote_id || empty($action)) {
    http_response_code(400); // Bad Request
    echo json_encode(['success' => false, 'message' => 'Invalid request parameters.']);
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

// Ensure the quote belongs to the logged-in user and is a junk_removal service
$stmt_verify = $conn->prepare("SELECT status, service_type FROM quotes WHERE id = ? AND user_id = ? AND service_type = 'junk_removal'");
$stmt_verify->bind_param("ii", $quote_id, $user_id);
$stmt_verify->execute();
$quote_data = $stmt_verify->get_result()->fetch_assoc();
$stmt_verify->close();

if (!$quote_data) {
    http_response_code(404); // Not Found or Forbidden
    echo json_encode(['success' => false, 'message' => 'Junk removal request not found or you do not have permission to edit it.']);
    exit;
}

$current_quote_status = $quote_data['status'];

// --- Action Routing ---
$conn->begin_transaction();

try {
    switch ($action) {
        case 'update_junk_items':
            // Only allow updates if the quote is still a 'customer_draft'
            if ($current_quote_status !== 'customer_draft') {
                throw new Exception('Cannot update junk items for a request that is no longer a draft.');
            }
            handleUpdateJunkItems($conn, $quote_id);
            break;

        case 'submit_customer_draft':
            // Only allow submission if the quote is a 'customer_draft'
            if ($current_quote_status !== 'customer_draft') {
                throw new Exception('This request has already been submitted or is no longer a draft.');
            }
            handleSubmitCustomerDraft($conn, $quote_id);
            break;
        case 'delete_bulk':
            // This action is handled by api/customer/quotes.php, but if this API were to handle it,
            // it would be similar to the admin/quotes.php delete_bulk. For now, it's a passthrough.
            throw new Exception('Bulk delete is handled by the main quotes API. Invalid action for this endpoint.');
            
        default:
            throw new Exception('Invalid action specified.');
    }

    $conn->commit();
    // Responses are handled within the specific functions

} catch (Exception $e) {
    $conn->rollback();
    error_log("Junk Removal Update API Error: " . $e->getMessage());
    http_response_code(400); // Bad Request for action-specific errors
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
} finally {
    if (isset($conn) && $conn->ping()) {
        $conn->close();
    }
}

// --- Handler Functions ---

/**
 * Handles updating the junk items for a draft quote.
 *
 * @param mysqli $conn The database connection object.
 * @param int $quote_id The ID of the quote to update.
 * @throws Exception If junk_items data is invalid or database error occurs.
 */
function handleUpdateJunkItems($conn, $quote_id) {
    $junk_items_json_str = $_POST['junk_items'] ?? '[]';
    $junk_items = json_decode($junk_items_json_str, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('Invalid JSON format for junk items.');
    }
    if (!is_array($junk_items)) {
        throw new Exception('Junk items must be an array.');
    }
    if (empty($junk_items)) {
        throw new Exception('At least one junk item is required.');
    }

    // Basic validation for each item
    foreach ($junk_items as $item) {
        if (empty($item['itemType'])) {
            throw new Exception('Item Type cannot be empty for any junk item.');
        }
        if (!isset($item['quantity']) || !is_numeric($item['quantity']) || (int)$item['quantity'] <= 0) {
            throw new Exception('Quantity must be a positive number for all junk items.');
        }
        // Sanitize other fields
        $item['estDimensions'] = filter_var($item['estDimensions'] ?? '', FILTER_SANITIZE_SPECIAL_CHARS);
        $item['estWeight'] = filter_var($item['estWeight'] ?? '', FILTER_SANITIZE_SPECIAL_CHARS);
    }

    // Re-encode to ensure consistency after validation/sanitization
    $final_junk_items_json = json_encode($junk_items);

    // Update only the junk_items_json for the specific quote
    $stmt_update = $conn->prepare("UPDATE junk_removal_details SET junk_items_json = ? WHERE quote_id = ?");
    $stmt_update->bind_param("si", $final_junk_items_json, $quote_id);
    
    if (!$stmt_update->execute()) {
        throw new Exception('Failed to update junk items in the database: ' . $stmt_update->error);
    }
    $stmt_update->close();

    echo json_encode(['success' => true, 'message' => 'Junk items updated successfully!']);
}

/**
 * Handles submitting a customer draft quote, changing its status to 'pending'.
 *
 * @param mysqli $conn The database connection object.
 * @param int $quote_id The ID of the quote to submit.
 * @throws Exception If the quote status cannot be updated or notification fails.
 */
function handleSubmitCustomerDraft($conn, $quote_id) {
    // Update quote status from 'customer_draft' to 'pending'
    $stmt_update = $conn->prepare("UPDATE quotes SET status = 'pending' WHERE id = ? AND status = 'customer_draft'");
    $stmt_update->bind_param("i", $quote_id);
    
    if (!$stmt_update->execute()) {
        throw new Exception('Failed to update quote status: ' . $stmt_update->error);
    }
    
    if ($stmt_update->affected_rows === 0) {
        throw new Exception('Request not found or not in a draft state to be submitted.');
    }
    $stmt_update->close();

    // Notify admin about the new request
    $admin_notification_message = "A new junk removal request (#Q{$quote_id}) has been submitted and is awaiting your quotation.";
    $admin_notification_link = "junk_removal?quote_id={$quote_id}";
    // Assuming admin user_id is 1 for system-wide notifications
    $admin_id = 1; 
    $stmt_admin_notify = $conn->prepare("INSERT INTO notifications (user_id, type, message, link) VALUES (?, 'new_quote', ?, ?)");
    $stmt_admin_notify->bind_param("iss", $admin_id, $admin_notification_message, $admin_notification_link);
    $stmt_admin_notify->execute();
    $stmt_admin_notify->close();

    echo json_encode(['success' => true, 'message' => 'Junk removal request submitted for a quote! Our team will get back to you shortly.']);
}