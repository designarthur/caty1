<?php
// api/customer/invoices.php - Handles customer actions for invoices, like payment and bulk deletion

// Production-safe error reporting for API endpoint
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED & ~E_STRICT);

session_start();
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/session.php'; // For is_logged_in() and $_SESSION['user_id']
require_once __DIR__ . '/../../includes/functions.php'; // For validate_csrf_token()

header('Content-Type: application/json');

// Check if user is logged in
if (!is_logged_in()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access. Please log in.']);
    exit;
}

$user_id = $_SESSION['user_id'];
$request_method = $_SERVER['REQUEST_METHOD'];
$action = $_POST['action'] ?? ''; // Use $_POST for actions

// CSRF token validation
try {
    // Only validate if it's a POST request and not a GET (like get_unread_count)
    if ($request_method === 'POST') {
        validate_csrf_token();
    }
} catch (Exception $e) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Invalid security token. Please refresh the page and try again.']);
    exit;
}


if ($request_method === 'POST') {
    switch ($action) {
        // ... (existing payment related actions if any, not shown in the provided context for this API) ...
        case 'delete_bulk':
            handleDeleteBulk($conn, $user_id);
            break;
        default:
            echo json_encode(['success' => false, 'message' => 'Invalid POST action.']);
            break;
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
}

$conn->close();

/**
 * Handles bulk deletion of invoices for a specific customer.
 * This function ensures that only invoices belonging to the logged-in user are deleted.
 * It also handles the deletion of associated bookings to maintain referential integrity.
 *
 * @param mysqli $conn The database connection object.
 * @param int $user_id The ID of the currently logged-in user.
 * @throws Exception If no invoice IDs are provided, or a database error occurs.
 */
function handleDeleteBulk($conn, $user_id) {
    $invoice_ids = $_POST['invoice_ids'] ?? [];
    
    // Ensure invoice_ids is an array and not empty
    if (empty($invoice_ids) || !is_array($invoice_ids)) {
        echo json_encode(['success' => false, 'message' => 'No invoice IDs provided for bulk deletion.']);
        return;
    }

    $conn->begin_transaction();

    try {
        // Filter invoice IDs to ensure they belong to the current user
        $placeholders = implode(',', array_fill(0, count($invoice_ids), '?'));
        $types = str_repeat('i', count($invoice_ids));

        $stmt_check_ownership = $conn->prepare("SELECT id FROM invoices WHERE id IN ($placeholders) AND user_id = ?");
        // Bind parameters: first the invoice IDs, then the user_id
        $params_with_user_id = array_merge($invoice_ids, [$user_id]);
        $stmt_check_ownership->bind_param($types . 'i', ...$params_with_user_id);
        $stmt_check_ownership->execute();
        $result_ownership = $stmt_check_ownership->get_result();
        $filtered_invoice_ids = [];
        while ($row = $result_ownership->fetch_assoc()) {
            $filtered_invoice_ids[] = $row['id'];
        }
        $stmt_check_ownership->close();

        if (empty($filtered_invoice_ids)) {
            // If no invoices are found after filtering by user_id, it means
            // either invalid IDs were sent, or they don't belong to the user.
            throw new Exception("No valid invoices found for deletion or you do not have permission to delete these invoices.");
        }

        // Use the filtered IDs for deletion
        $delete_placeholders = implode(',', array_fill(0, count($filtered_invoice_ids), '?'));
        $delete_types = str_repeat('i', count($filtered_invoice_ids));
        
        // 1. Get booking IDs associated with these invoices
        $stmt_fetch_bookings = $conn->prepare("SELECT id FROM bookings WHERE invoice_id IN ($delete_placeholders)");
        $stmt_fetch_bookings->bind_param($delete_types, ...$filtered_invoice_ids);
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
            
            // Delete bookings (this should cascade to booking_charges, status_history, reviews)
            $stmt_delete_bookings = $conn->prepare("DELETE FROM bookings WHERE id IN ($booking_placeholders)");
            $stmt_delete_bookings->bind_param($booking_types, ...$booking_ids_to_delete);
            if (!$stmt_delete_bookings->execute()) {
                throw new Exception("Failed to delete associated bookings: " . $stmt_delete_bookings->error);
            }
            $stmt_delete_bookings->close();
        }

        // 3. Delete the invoices themselves
        // This should cascade delete from invoice_items
        $stmt_delete_invoices = $conn->prepare("DELETE FROM invoices WHERE id IN ($delete_placeholders) AND user_id = ?");
        $final_invoice_params = array_merge($filtered_invoice_ids, [$user_id]);
        $stmt_delete_invoices->bind_param($delete_types . 'i', ...$final_invoice_params);
        if (!$stmt_delete_invoices->execute()) {
            throw new Exception("Failed to delete invoices: " . $stmt_delete_invoices->error);
        }
        $stmt_delete_invoices->close();

        $conn->commit();
        echo json_encode(['success' => true, 'message' => 'Selected invoices and their associated data have been deleted.']);

    } catch (Exception $e) {
        $conn->rollback();
        error_log("Customer bulk delete invoices error: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}