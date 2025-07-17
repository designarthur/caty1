<?php
// api/payments.php - Handles Stripe payment processing and booking creation/updates

// --- Production-Ready Error Handling ---
ini_set('display_errors', 0);
error_reporting(E_ALL);
ini_set('error_log', __DIR__ . '/../logs/php_errors.log');
if (!file_exists(__DIR__ . '/../logs')) {
    mkdir(__DIR__ . '/../logs', 0775, true);
}


session_start();
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../vendor/autoload.php'; // Composer autoload for Stripe

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

// --- Initialize Stripe ---
try {
    // Set your Stripe API key from environment variables
    \Stripe\Stripe::setApiKey($_ENV['STRIPE_SECRET_KEY'] ?? '');

    if (empty(\Stripe\Stripe::getApiKey())) {
        throw new Exception("Stripe secret key is not configured in the .env file.");
    }
} catch (Exception $e) {
    error_log("Stripe initialization failed: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Payment system configuration error.']);
    exit;
}

// --- Input Validation ---
$invoice_id_from_form = filter_input(INPUT_POST, 'invoice_id', FILTER_VALIDATE_INT);
$amountToPay        = filter_var($_POST['amount'] ?? 0, FILTER_VALIDATE_FLOAT);
$paymentMethodId    = $_POST['payment_method_id'] ?? null; // Received from Stripe.js
$savePaymentMethod  = isset($_POST['save_payment_method']) && $_POST['save_payment_method'] === 'true'; // From checkbox
$customer_id_from_form = $_POST['stripe_customer_id'] ?? null; // Can be passed from frontend for existing customers

if (empty($invoice_id_from_form) || $amountToPay <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid invoice details or amount.']);
    exit;
}

if (empty($paymentMethodId)) {
    echo json_encode(['success' => false, 'message' => 'A valid payment method is required.']);
    exit;
}

$conn->begin_transaction();

try {
    // 1. Fetch the invoice from the database to verify it exists and belongs to the user.
    $stmt_invoice = $conn->prepare("SELECT id, invoice_number, quote_id, booking_id FROM invoices WHERE id = ? AND user_id = ? AND status IN ('pending', 'partially_paid')");
    $stmt_invoice->bind_param("ii", $invoice_id_from_form, $user_id);
    $stmt_invoice->execute();
    $result_invoice = $stmt_invoice->get_result();
    if ($result_invoice->num_rows === 0) {
        throw new Exception("Invoice not found, already paid, or you are not authorized to pay it.");
    }
    $invoice_data = $result_invoice->fetch_assoc();
    $invoice_id = $invoice_data['id'];
    $invoice_number = $invoice_data['invoice_number'];
    $quote_id = $invoice_data['quote_id'];
    $booking_id_from_invoice = $invoice_data['booking_id'];
    $stmt_invoice->close();

    // 2. Get or Create Stripe Customer
    $stripe_customer_id = null;
    $stmt_get_customer = $conn->prepare("SELECT stripe_customer_id FROM users WHERE id = ?");
    $stmt_get_customer->bind_param("i", $user_id);
    $stmt_get_customer->execute();
    $user_stripe_data = $stmt_get_customer->get_result()->fetch_assoc();
    $stmt_get_customer->close();

    if (!empty($user_stripe_data['stripe_customer_id'])) {
        $stripe_customer_id = $user_stripe_data['stripe_customer_id'];
    } else {
        // Create a new Stripe Customer
        $customer = \Stripe\Customer::create([
            'email' => $_SESSION['user_email'],
            'name' => $_SESSION['user_first_name'] . ' ' . $_SESSION['user_last_name'],
            'description' => "Customer for user ID {$user_id} - " . $_SESSION['user_email'],
        ]);
        $stripe_customer_id = $customer->id;

        // Save Stripe Customer ID to local database
        $stmt_update_user = $conn->prepare("UPDATE users SET stripe_customer_id = ? WHERE id = ?");
        $stmt_update_user->bind_param("si", $stripe_customer_id, $user_id);
        $stmt_update_user->execute();
        $stmt_update_user->close();
    }

    // 3. Attach Payment Method to Customer if new and saving is requested
    if ($savePaymentMethod && $paymentMethodId) {
        try {
            \Stripe\PaymentMethod::retrieve($paymentMethodId)->attach(['customer' => $stripe_customer_id]);
        } catch (\Stripe\Exception\InvalidRequestException $e) {
            // Handle if payment method is already attached or other error
            error_log("Stripe PaymentMethod attach failed (may already be attached): " . $e->getMessage());
            // Continue with payment, as it might just be already attached
        }
    }

    // 4. Create and Confirm Payment Intent
    $paymentIntent = \Stripe\PaymentIntent::create([
        'amount'               => (int)($amountToPay * 100), // Amount in cents
        'currency'             => 'usd',
        'customer'             => $stripe_customer_id,
        'payment_method'       => $paymentMethodId,
        'off_session'          => false, // Require CVC confirmation
        'confirm'              => true,
        'description'          => "Invoice {$invoice_number} payment",
        'metadata'             => [
            'invoice_id' => $invoice_id,
            'user_id'    => $user_id,
            'invoice_number' => $invoice_number
        ],
        'return_url'           => (isset($_SERVER['HTTPS']) ? "https" : "http") . "://$_SERVER[HTTP_HOST]/customer/dashboard.php#invoices", // Return URL for 3D Secure
        'error_on_requires_action' => true, // Fail payment if further action is required
    ]);

    // Handle payment intent status
    if ($paymentIntent->status === 'succeeded') {
        $transaction_id = $paymentIntent->id;
        $payment_method_type = $paymentIntent->payment_method_types[0] ?? 'card'; // e.g., card, us_bank_account
        $card_brand = '';
        $card_last4 = '';

        if ($paymentIntent->charges->data[0]->payment_method_details->type === 'card') {
            $card_brand = $paymentIntent->charges->data[0]->payment_method_details->card->brand ?? '';
            $card_last4 = $paymentIntent->charges->data[0]->payment_method_details->card->last4 ?? '';
        }
        
        $payment_method_used = ucfirst($payment_method_type);
        if (!empty($card_brand) && !empty($card_last4)) {
            $payment_method_used = ucfirst($card_brand) . " ending in " . $card_last4;
        }

        // 5. Update the invoice status in our database to 'paid'.
        $stmt_update_invoice = $conn->prepare("UPDATE invoices SET status = 'paid', payment_method = ?, transaction_id = ? WHERE id = ?");
        $stmt_update_invoice->bind_param("ssi", $payment_method_used, $transaction_id, $invoice_id);
        if (!$stmt_update_invoice->execute()) {
            throw new Exception("Failed to update invoice status in the database.");
        }
        $stmt_update_invoice->close();

        // 6. Check if this payment is for an extension, relocation, swap, or a new booking.
        $final_booking_id = null;
        $is_extension_invoice = strpos($invoice_number, 'INV-EXT-') === 0;
        $is_relocation_invoice = strpos($invoice_number, 'INV-REL-') === 0;
        $is_swap_invoice = strpos($invoice_number, 'INV-SWA-') === 0;

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
                $notes = "Booking extended by {$ext_data['requested_days']} days due to paid invoice #{$invoice_number}.";
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
            $notes = "{$service_name} service paid via invoice #{$invoice_number}. Booking status updated to '{$new_booking_status}'.";
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
    } elseif ($paymentIntent->status === 'requires_action' && $paymentIntent->next_action->type === 'use_stripe_sdk') {
        // Handle 3D Secure or other required actions
        $conn->rollback(); // Rollback local transaction as payment isn't complete yet
        echo json_encode([
            'success' => false,
            'message' => 'Payment requires additional action (e.g., 3D Secure).',
            'requires_action' => true,
            'payment_intent_client_secret' => $paymentIntent->client_secret
        ]);
    } else {
        // Generic failure
        $conn->rollback();
        throw new Exception("Payment failed with status: " . $paymentIntent->status);
    }

} catch (\Stripe\Exception\CardException $e) {
    $conn->rollback();
    // Card was declined or other card-related error
    error_log("Stripe Card Error: " . $e->getMessage() . " Code: " . $e->getCode() . " Card: " . ($e->getJsonBody()['error']['decline_code'] ?? 'N/A'));
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
} catch (\Stripe\Exception\RateLimitException $e) {
    $conn->rollback();
    error_log("Stripe Rate Limit Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Too many requests to payment gateway. Please try again in a moment.']);
} catch (\Stripe\Exception\InvalidRequestException $e) {
    $conn->rollback();
    error_log("Stripe Invalid Request Error: " . $e->getMessage() . " Param: " . ($e->getJsonBody()['error']['param'] ?? 'N/A'));
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
} catch (\Stripe\Exception\AuthenticationException $e) {
    $conn->rollback();
    error_log("Stripe Authentication Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Payment system authentication failed. Please contact support.']);
} catch (\Stripe\Exception\ApiConnectionException $e) {
    $conn->rollback();
    error_log("Stripe API Connection Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Could not connect to payment system. Please check your internet connection.']);
} catch (\Stripe\Exception\ApiErrorException $e) {
    $conn->rollback();
    error_log("Stripe API Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'An unexpected payment error occurred. Please try again.']);
} catch (Exception $e) {
    $conn->rollback();
    error_log("General Payment Processing Failed for Invoice: {$invoice_id_from_form}, User: {$user_id}. Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Payment failed: ' . $e->getMessage()]);
} finally {
    if (isset($conn) && $conn->ping()) {
        $conn->close();
    }
}
?>
