<?php
// api/payments.php - Handles Braintree payment processing and booking creation/updates

// --- Production-Ready Error Handling ---
ini_set('display_errors', 0);
error_reporting(E_ALL);
ini_set('error_log', __DIR__ . '/../logs/php_errors.log');

register_shutdown_function(function () {
    $error = error_get_last();
    if ($error !== null && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        if (!headers_sent()) {
            header('Content-Type: application/json');
            http_response_code(500);
        }
        error_log("Fatal Error: " . $error['message'] . " in " . $error['file'] . " on line " . $error['line']);
        echo json_encode(['success' => false, 'message' => 'A critical server error occurred. Our team has been notified.']);
    }
});


// Start the session and include all necessary files.
session_start();
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../vendor/autoload.php';

header('Content-Type: application/json');

if (!is_logged_in()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access. Please log in.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit;
}

$user_id = $_SESSION['user_id'];

// --- Initialize Braintree Gateway ---
try {
    $gateway = new Braintree\Gateway([
        'environment' => $_ENV['BRAINTREE_ENVIRONMENT'] ?? 'sandbox',
        'merchantId'  => $_ENV['BRAINTREE_MERCHANT_ID'],
        'publicKey'   => $_ENV['BRAINTREE_PUBLIC_KEY'],
        'privateKey'  => $_ENV['BRAINTREE_PRIVATE_KEY']
    ]);
} catch (Exception $e) {
    error_log("Braintree Gateway initialization failed: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Payment system configuration error.']);
    exit;
}

// --- Input Validation ---
$invoiceNumber      = trim($_POST['invoice_number'] ?? '');
$amountToPay        = filter_var($_POST['amount'] ?? 0, FILTER_VALIDATE_FLOAT);
$paymentMethodNonce = $_POST['payment_method_nonce'] ?? null;
$paymentMethodToken = $_POST['payment_method_token'] ?? null;
$saveCard           = filter_var($_POST['save_card'] ?? false, FILTER_VALIDATE_BOOLEAN);


if (empty($invoiceNumber) || $amountToPay <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid invoice details or amount.']);
    exit;
}

if (empty($paymentMethodNonce) && empty($paymentMethodToken)) {
    echo json_encode(['success' => false, 'message' => 'A valid payment method is required.']);
    exit;
}


$conn->begin_transaction();

try {
    // 1. Fetch the invoice from the database to verify it exists and belongs to the user.
    $stmt_invoice = $conn->prepare("SELECT id, quote_id, booking_id FROM invoices WHERE invoice_number = ? AND user_id = ? AND status IN ('pending', 'partially_paid')");
    $stmt_invoice->bind_param("si", $invoiceNumber, $user_id);
    $stmt_invoice->execute();
    $result_invoice = $stmt_invoice->get_result();
    if ($result_invoice->num_rows === 0) {
        throw new Exception("Invoice not found, already paid, or you are not authorized to pay it.");
    }
    $invoice_data = $result_invoice->fetch_assoc();
    $invoice_id = $invoice_data['id'];
    $quote_id = $invoice_data['quote_id'];
    $booking_id_from_invoice = $invoice_data['booking_id'];
    $stmt_invoice->close();

    // 2. Prepare the transaction request for Braintree.
    $saleRequest = [
        'amount'   => (string)$amountToPay,
        'orderId'  => "INV-" . $invoiceNumber,
        'options'  => ['submitForSettlement' => true],
        'customer' => [
            'id'        => $user_id,
            'firstName' => $_SESSION['user_first_name'],
            'lastName'  => $_SESSION['user_last_name'],
            'email'     => $_SESSION['user_email']
        ]
    ];

    if (!empty($paymentMethodNonce)) {
        $saleRequest['paymentMethodNonce'] = $paymentMethodNonce;
        if ($saveCard) {
            $saleRequest['options']['storeInVaultOnSuccess'] = true;
        }
    } else {
        $saleRequest['paymentMethodToken'] = $paymentMethodToken;
    }


    // 3. Process the transaction by sending the request to Braintree.
    $result = $gateway->transaction()->sale($saleRequest);

    if (!$result->success) {
        throw new Exception("Payment gateway error: " . $result->message);
    }

    $transaction = $result->transaction;
    $transaction_id = $transaction->id;
    $payment_method_used = $transaction->creditCardDetails->cardType . " ending in " . $transaction->creditCardDetails->last4;

    if ($saveCard && isset($transaction->creditCardDetails->token)) {
        $newPaymentToken = $transaction->creditCardDetails->token;
        $cardDetails = $transaction->creditCardDetails;
        $stmt_save_token = $conn->prepare(
            "INSERT INTO user_payment_methods (user_id, braintree_payment_token, card_type, last_four, expiration_month, expiration_year, cardholder_name, billing_address)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
        );
        $stmt_save_token->bind_param("isssssss",
            $user_id, $newPaymentToken, $cardDetails->cardType, $cardDetails->last4,
            $cardDetails->expirationMonth, $cardDetails->expirationYear, $cardDetails->cardholderName,
            $_POST['billing_address'] ?? ''
        );
        $stmt_save_token->execute();
        $stmt_save_token->close();
    }


    // 4. Update the invoice status in our database to 'paid'.
    $stmt_update_invoice = $conn->prepare("UPDATE invoices SET status = 'paid', payment_method = ?, transaction_id = ? WHERE id = ?");
    $stmt_update_invoice->bind_param("ssi", $payment_method_used, $transaction_id, $invoice_id);
    if (!$stmt_update_invoice->execute()) {
        throw new Exception("Failed to update invoice status in the database.");
    }
    $stmt_update_invoice->close();

    // 5. Check if this payment is for an extension, relocation, swap, or a new booking.
    $final_booking_id = null;
    $is_extension_invoice = strpos($invoiceNumber, 'INV-EXT-') === 0;
    $is_relocation_invoice = strpos($invoiceNumber, 'INV-REL-') === 0;
    $is_swap_invoice = strpos($invoiceNumber, 'INV-SWA-') === 0;

    if ($is_extension_invoice && $booking_id_from_invoice) {
        // --- THIS IS A RENTAL EXTENSION PAYMENT ---
        $final_booking_id = $booking_id_from_invoice;

        // Get the requested extension days
        $stmt_ext = $conn->prepare("SELECT requested_days FROM booking_extension_requests WHERE invoice_id = ? AND status = 'approved'");
        $stmt_ext->bind_param("i", $invoice_id);
        $stmt_ext->execute();
        $ext_data = $stmt_ext->get_result()->fetch_assoc();
        $stmt_ext->close();
        
        if ($ext_data && $ext_data['requested_days'] > 0) {
            // Update the booking's end date
            $stmt_update_end_date = $conn->prepare("UPDATE bookings SET end_date = DATE_ADD(end_date, INTERVAL ? DAY) WHERE id = ?");
            $stmt_update_end_date->bind_param("ii", $ext_data['requested_days'], $final_booking_id);
            $stmt_update_end_date->execute();
            $stmt_update_end_date->close();

            // Log the status history for the extension payment
            $notes = "Booking extended by {$ext_data['requested_days']} days due to paid invoice #{$invoiceNumber}.";
            $stmt_log_ext = $conn->prepare("INSERT INTO booking_status_history (booking_id, status, notes) VALUES (?, 'extended', ?)");
            $stmt_log_ext->bind_param("is", $final_booking_id, $notes);
            $stmt_log_ext->execute();
            $stmt_log_ext->close();
        }

    } elseif (($is_relocation_invoice || $is_swap_invoice) && $booking_id_from_invoice) {
        // --- THIS IS A RELOCATION OR SWAP SERVICE PAYMENT ---
        $final_booking_id = $booking_id_from_invoice;
        $new_booking_status = $is_relocation_invoice ? 'relocated' : 'swapped';
        $service_name = $is_relocation_invoice ? 'Relocation' : 'Swap';

        // Update the booking status to 'relocated' or 'swapped'
        $stmt_update_booking_status = $conn->prepare("UPDATE bookings SET status = ? WHERE id = ?");
        $stmt_update_booking_status->bind_param("si", $new_booking_status, $final_booking_id);
        $stmt_update_booking_status->execute();
        $stmt_update_booking_status->close();

        // Log the status history
        $notes = "{$service_name} service paid via invoice #{$invoiceNumber}. Booking status updated to '{$new_booking_status}'.";
        $stmt_log_service = $conn->prepare("INSERT INTO booking_status_history (booking_id, status, notes) VALUES (?, ?, ?)");
        $stmt_log_service->bind_param("iss", $final_booking_id, $new_booking_status, $notes);
        $stmt_log_service->execute();
        $stmt_log_service->close();

    } elseif ($quote_id) {
        // --- THIS IS A NEW BOOKING FROM A QUOTE ---
        $final_booking_id = createBookingFromInvoice($conn, $invoice_id);
        if (!$final_booking_id) {
            throw new Exception("Booking could not be created after successful payment.");
        }
    } else {
        // This is some other type of charge that doesn't create a new booking or update an existing one's core status.
        // It might be a manual charge added by admin.
        $final_booking_id = $booking_id_from_invoice; // Keep the existing booking ID if present
    }


    $conn->commit();
    echo json_encode([
        'success'        => true,
        'message'        => 'Payment successful and booking confirmed!',
        'transaction_id' => $transaction_id,
        'booking_id'     => $final_booking_id
    ]);

} catch (Exception $e) {
    $conn->rollback();
    error_log("Payment processing failed for Invoice: $invoiceNumber, User: $user_id. Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Payment failed: ' . $e->getMessage()]);
}

$conn->close();