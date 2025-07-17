<?php
// api/payments.php - Handles Stripe payment processing and booking creation/updates

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

// --- Initialize Stripe API ---
try {
    // Set your secret key. Remember to switch to your live secret key in production.
    // See your keys here: https://dashboard.stripe.com/apikeys
    \Stripe\Stripe::setApiKey($_ENV['STRIPE_SECRET_KEY'] ?? getenv('STRIPE_SECRET_KEY'));
} catch (Exception $e) {
    error_log("Stripe API initialization failed: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Payment system configuration error.']);
    exit;
}

// --- Input Validation ---
$invoiceNumber      = trim($_POST['invoice_number'] ?? '');
$amountToPay        = filter_var($_POST['amount'] ?? 0, FILTER_VALIDATE_FLOAT);
$paymentMethodId    = $_POST['payment_method_id'] ?? null; // Coming from Stripe.js
$saveCard           = filter_var($_POST['save_card'] ?? false, FILTER_VALIDATE_BOOLEAN);
$originalPaymentMethodId = $_POST['original_payment_method_id'] ?? null; // To check if an existing method was used


if (empty($invoiceNumber) || $amountToPay <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid invoice details or amount.']);
    exit;
}

if (empty($paymentMethodId) && empty($originalPaymentMethodId)) {
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

    // Ensure Stripe customer exists (this function handles creation/validation)
    // This function is defined in api/customer/payment_methods.php, assuming it's available or in functions.php
    $stripe_customer_id = ensureStripeCustomerExists($conn, $user_id); 
    
    $final_payment_method_id = $paymentMethodId; // Use the new payment method by default

    // If a saved card token was sent, retrieve the actual Stripe Payment Method ID from your DB
    if ($originalPaymentMethodId) {
        $stmt_get_saved_pm = $conn->prepare("SELECT braintree_payment_token FROM user_payment_methods WHERE id = ? AND user_id = ?"); // 'braintree_payment_token' now stores Stripe PM ID
        $stmt_get_saved_pm->bind_param("ii", $originalPaymentMethodId, $user_id);
        $stmt_get_saved_pm->execute();
        $saved_pm_data = $stmt_get_saved_pm->get_result()->fetch_assoc();
        if ($saved_pm_data && !empty($saved_pm_data['braintree_payment_token'])) {
            $final_payment_method_id = $saved_pm_data['braintree_payment_token'];
        } else {
             throw new Exception("Saved payment method not found or not authorized.");
        }
        $stmt_get_saved_pm->close();
    }


    // --- FIX: Add check for positive amount before Stripe PaymentIntent creation ---
    $amount_in_cents = (int)($amountToPay * 100);
    if ($amount_in_cents <= 0) {
        throw new Exception("Payment amount must be greater than zero.");
    }
    // --- END FIX ---

    // --- Debugging Log: Log the payload just before sending to Stripe ---
    error_log("Stripe PaymentIntent creation payload for Invoice: {$invoiceNumber}, User: {$user_id}:");
    error_log("Amount (cents): " . $amount_in_cents);
    error_log("Currency: usd");
    error_log("Customer ID: " . $stripe_customer_id);
    error_log("Payment Method ID: " . $final_payment_method_id);
    error_log("Raw POST data: " . print_r($_POST, true)); 
    // --- END Debugging Log ---

    // 2. Create a PaymentIntent
    $paymentIntent = \Stripe\PaymentIntent::create([
        'amount' => $amount_in_cents, // Amount in cents
        'currency' => 'usd', // Adjust currency as needed
        'customer' => $stripe_customer_id,
        'payment_method' => $final_payment_method_id,
        'off_session' => true, // Indicates it's a customer-initiated payment and might be off-session
        'confirm' => true, // Confirm the payment immediately
        'metadata' => [
            'invoice_id' => $invoice_id,
            'invoice_number' => $invoiceNumber,
            'user_id' => $user_id
        ],
        'error_on_requires_action' => true, // Fail if 3D Secure is required and not handled client-side
    ]);

    // 3. Handle PaymentIntent status
    if ($paymentIntent->status == 'succeeded') {
        $transaction_id = $paymentIntent->id;
        $payment_method_type = $paymentIntent->payment_method_types[0] ?? 'card'; // e.g., 'card'
        $card_last4 = '';
        if (isset($paymentIntent->charges->data[0]->payment_method_details->card->last4)) {
            $card_last4 = $paymentIntent->charges->data[0]->payment_method_details->card->last4;
        }
        $payment_method_used = ucfirst($payment_method_type) . " ending in " . $card_last4;

        // If 'save_card' is true and a new payment method was used, attach it to the customer
        if ($saveCard && $paymentMethodId && !$originalPaymentMethodId) {
            // Attach the PaymentMethod to the Customer
            \Stripe\PaymentMethod::retrieve($paymentMethodId)->attach(['customer' => $stripe_customer_id]);

            // Save the Stripe Payment Method ID in your database for future use
            // The 'braintree_payment_token' column will now store Stripe Payment Method IDs
            $stmt_save_token = $conn->prepare(
                "INSERT INTO user_payment_methods (user_id, braintree_payment_token, card_type, last_four, expiration_month, expiration_year, cardholder_name, billing_address, is_default)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)"
            );
            $stmt_save_token->bind_param("issssssis",
                $user_id,
                $paymentMethodId, // Store the Stripe Payment Method ID here
                $paymentIntent->charges->data[0]->payment_method_details->card->brand ?? 'Unknown',
                $card_last4,
                $paymentIntent->charges->data[0]->payment_method_details->card->exp_month ?? '',
                $paymentIntent->charges->data[0]->payment_method_details->card->exp_year ?? '',
                $_POST['cardholder_name'] ?? '',
                $_POST['billing_address'] ?? '',
                false // Not setting as default during add; user can do that in payment methods page
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

    } elseif ($paymentIntent->status == 'requires_action' || $paymentIntent->status == 'requires_source_action') {
        // This means 3D Secure or other authentication is required.
        // The client-side will need to handle this.
        $conn->rollback(); // Rollback DB changes as payment isn't complete yet
        echo json_encode([
            'success' => false,
            'message' => 'Payment requires additional action (e.g., 3D Secure).',
            'requires_action' => true,
            'payment_intent_client_secret' => $paymentIntent->client_secret
        ]);
    } else {
        // Other non-succeeded statuses
        $conn->rollback();
        throw new Exception("Payment processing failed with status: " . $paymentIntent->status);
    }

} catch (\Stripe\Exception\CardException $e) {
    $conn->rollback();
    error_log("Stripe Card Error: " . $e->getError()->message);
    echo json_encode(['success' => false, 'message' => $e->getError()->message]);
} catch (\Stripe\Exception\RateLimitException $e) {
    $conn->rollback();
    error_log("Stripe Rate Limit Error: " . $e->getError()->message);
    echo json_encode(['success' => false, 'message' => 'Too many requests. Please try again shortly.']);
} catch (\Stripe\Exception\InvalidRequestException $e) {
    $conn->rollback();
    error_log("Stripe Invalid Request Error: " . $e->getError()->message);
    echo json_encode(['success' => false, 'message' => 'Invalid payment request. Please check details.']);
} catch (\Stripe\Exception\AuthenticationException $e) {
    $conn->rollback();
    error_log("Stripe Authentication Error: " . $e->getError()->message);
    echo json_encode(['success' => false, 'message' => 'Payment gateway authentication failed. Please contact support.']);
} catch (\Stripe\Exception\ApiConnectionException $e) {
    $conn->rollback();
    error_log("Stripe API Connection Error: " . $e->getError()->message);
    echo json_encode(['success' => false, 'message' => 'Cannot connect to payment gateway. Please check your internet connection and try again.']);
} catch (\Stripe\Exception\ApiErrorException $e) {
    $conn->rollback();
    error_log("Stripe API Error: " . $e->getError()->message);
    echo json_encode(['success' => false, 'message' => $e->getError()->message]);
} catch (Exception $e) {
    $conn->rollback();
    error_log("Payment processing failed for Invoice: $invoiceNumber, User: $user_id. Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Payment failed: ' . $e->getMessage()]);
}

$conn->close();