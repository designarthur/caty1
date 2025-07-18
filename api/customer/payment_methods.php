<?php
// api/customer/payment_methods.php

// --- Production-Ready Error Handling & Setup ---
header('Content-Type: application/json');
ini_set('display_errors', 0); // Do not display errors to the client
error_reporting(E_ALL);
ini_set('log_errors', 1); // Log errors to a file
ini_set('error_log', __DIR__ . '/../logs/php_errors.log'); // Ensure this path is writable

// Generic error handler to catch fatal errors and always return JSON
register_shutdown_function(function () {
    $error = error_get_last();
    if ($error !== null && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        if (!headers_sent()) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'A critical server error occurred. Please contact support.']);
        }
    }
});

// --- Main Execution Block ---
try {
    // Start session and include necessary files
    session_start();
    require_once __DIR__ . '/../../includes/db.php';
    require_once __DIR__ . '/../../includes/session.php';
    require_once __DIR__ . '/../../includes/functions.php';
    require_once __DIR__ . '/../../vendor/autoload.php';

    // Check if user is logged in
    if (!is_logged_in()) {
        throw new Exception('Unauthorized access. Please log in.', 401);
    }

    $user_id = $_SESSION['user_id'];
    $request_method = $_SERVER['REQUEST_METHOD'];
    $action = $_REQUEST['action'] ?? '';

    // Initialize Stripe API
    \Stripe\Stripe::setApiKey($_ENV['STRIPE_SECRET_KEY'] ?? getenv('STRIPE_SECRET_KEY'));

    // Route request based on method and action
    if ($request_method === 'POST') {
        validate_csrf_token(); // Validate CSRF token for all POST actions
        switch ($action) {
            case 'add_method':
                handleAddPaymentMethod($conn, $user_id);
                break;
            case 'set_default':
                handleSetDefaultPaymentMethod($conn, $user_id);
                break;
            case 'delete_method':
                handleDeletePaymentMethod($conn, $user_id);
                break;
            case 'update_method':
                handleUpdatePaymentMethod($conn, $user_id);
                break;
            default:
                throw new Exception('Invalid POST action specified.', 400);
        }
    } elseif ($request_method === 'GET' && $action === 'get_default_method') {
        handleGetDefaultMethod($conn, $user_id);
    } else {
        throw new Exception('Invalid request method or action.', 405);
    }

} catch (Exception $e) {
    // This will catch CSRF validation errors and any other general exceptions
    $code = $e->getCode() >= 400 ? $e->getCode() : 400; // Use exception code if it's a valid HTTP status
    http_response_code($code);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
} finally {
    if (isset($conn) && $conn instanceof mysqli && $conn->ping()) {
        $conn->close();
    }
}


// --- Function Definitions ---

function handleAddPaymentMethod($conn, $user_id) {
    $paymentMethodId = $_POST['payment_method_id'] ?? '';
    $cardholderName = $_POST['cardholder_name'] ?? '';
    $billingAddress = $_POST['billing_address'] ?? '';
    $setDefault = isset($_POST['set_default']) && $_POST['set_default'] === 'on';

    if (empty($paymentMethodId) || empty($cardholderName)) {
        throw new Exception('Payment method details are required.', 400);
    }

    $conn->begin_transaction();
    try {
        $stripe_customer_id = ensureStripeCustomerExists($conn, $user_id);
        $paymentMethod = \Stripe\PaymentMethod::retrieve($paymentMethodId);
        $paymentMethod->attach(['customer' => $stripe_customer_id]);
        
        if ($setDefault) {
            \Stripe\Customer::update($stripe_customer_id, ['invoice_settings' => ['default_payment_method' => $paymentMethodId]]);
            $stmt_unset = $conn->prepare("UPDATE user_payment_methods SET is_default = FALSE WHERE user_id = ?");
            $stmt_unset->bind_param("i", $user_id);
            $stmt_unset->execute();
            $stmt_unset->close();
        }

        $stmt_insert = $conn->prepare("INSERT INTO user_payment_methods (user_id, braintree_payment_token, card_type, last_four, expiration_month, expiration_year, cardholder_name, is_default, billing_address) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt_insert->bind_param("issssssis", $user_id, $paymentMethodId, $paymentMethod->card->brand, $paymentMethod->card->last4, $paymentMethod->card->exp_month, $paymentMethod->card->exp_year, $cardholderName, $setDefault, $billingAddress);
        $stmt_insert->execute();
        $stmt_insert->close();
        
        $conn->commit();
        echo json_encode(['success' => true, 'message' => 'Payment method added successfully!']);
    } catch (Exception $e) {
        $conn->rollback();
        throw $e; // Re-throw to be caught by the main handler
    }
}

function handleSetDefaultPaymentMethod($conn, $user_id) {
    $methodId = $_POST['id'] ?? null;
    if (empty($methodId)) {
        throw new Exception('Payment method ID is required.', 400);
    }
    
    $conn->begin_transaction();
    try {
        $stripe_customer_id = ensureStripeCustomerExists($conn, $user_id);
        
        $stmt_get_pm = $conn->prepare("SELECT braintree_payment_token FROM user_payment_methods WHERE id = ? AND user_id = ?");
        $stmt_get_pm->bind_param("ii", $methodId, $user_id);
        $stmt_get_pm->execute();
        $pm_token = $stmt_get_pm->get_result()->fetch_assoc()['braintree_payment_token'] ?? null;
        $stmt_get_pm->close();

        if (!$pm_token) {
            throw new Exception("Payment method not found or unauthorized.", 404);
        }
        
        \Stripe\Customer::update($stripe_customer_id, ['invoice_settings' => ['default_payment_method' => $pm_token]]);
        
        $stmt_unset = $conn->prepare("UPDATE user_payment_methods SET is_default = FALSE WHERE user_id = ?");
        $stmt_unset->bind_param("i", $user_id);
        $stmt_unset->execute();
        $stmt_unset->close();
        
        $stmt_set = $conn->prepare("UPDATE user_payment_methods SET is_default = TRUE WHERE id = ? AND user_id = ?");
        $stmt_set->bind_param("ii", $methodId, $user_id);
        $stmt_set->execute();
        $stmt_set->close();
        
        $conn->commit();
        echo json_encode(['success' => true, 'message' => 'Default payment method updated.']);
    } catch (Exception $e) {
        $conn->rollback();
        throw $e;
    }
}

function handleDeletePaymentMethod($conn, $user_id) {
    $methodId = $_POST['id'] ?? null;
    $stripePmId = $_POST['stripe_pm_id'] ?? null;

    if (empty($methodId) || empty($stripePmId)) {
        throw new Exception('Both local and Stripe payment method IDs are required.', 400);
    }
    
    $conn->begin_transaction();
    try {
        $stmt_check = $conn->prepare("SELECT is_default FROM user_payment_methods WHERE id = ? AND user_id = ? AND braintree_payment_token = ?");
        $stmt_check->bind_param("iis", $methodId, $user_id, $stripePmId);
        $stmt_check->execute();
        $is_default = $stmt_check->get_result()->fetch_assoc()['is_default'] ?? null;
        $stmt_check->close();

        if ($is_default === null) {
            throw new Exception("Payment method not found or unauthorized.", 404);
        }
        if ($is_default) {
            throw new Exception("Cannot delete the default payment method. Please set another card as default first.", 400);
        }

        \Stripe\PaymentMethod::retrieve($stripePmId)->detach();
        
        $stmt_delete = $conn->prepare("DELETE FROM user_payment_methods WHERE id = ? AND user_id = ?");
        $stmt_delete->bind_param("ii", $methodId, $user_id);
        $stmt_delete->execute();
        $stmt_delete->close();
        
        $conn->commit();
        echo json_encode(['success' => true, 'message' => 'Payment method deleted.']);
    } catch (Exception $e) {
        $conn->rollback();
        throw $e;
    }
}

function handleUpdatePaymentMethod($conn, $user_id) {
    $methodId = $_POST['id'] ?? null;
    $cardholderName = $_POST['cardholder_name'] ?? '';
    $expirationMonth = $_POST['expiration_month'] ?? '';
    $expirationYear = $_POST['expiration_year'] ?? '';
    $billingAddress = $_POST['billing_address'] ?? '';
    $setDefault = isset($_POST['set_default']) && $_POST['set_default'] === 'on';

    if (empty($methodId) || empty($cardholderName) || !preg_match('/^(0[1-9]|1[0-2])$/', $expirationMonth) || !preg_match('/^\d{4}$/', $expirationYear)) {
        throw new Exception('Valid payment method details are required.', 400);
    }

    $conn->begin_transaction();
    try {
        $stripe_customer_id = ensureStripeCustomerExists($conn, $user_id);
        
        $stmt_get_pm = $conn->prepare("SELECT braintree_payment_token FROM user_payment_methods WHERE id = ? AND user_id = ?");
        $stmt_get_pm->bind_param("ii", $methodId, $user_id);
        $stmt_get_pm->execute();
        $stripePmId = $stmt_get_pm->get_result()->fetch_assoc()['braintree_payment_token'] ?? null;
        $stmt_get_pm->close();

        if (!$stripePmId) {
            throw new Exception("Payment method not found.", 404);
        }
        
        \Stripe\PaymentMethod::update($stripePmId, ['billing_details' => ['name' => $cardholderName, 'address' => ['line1' => $billingAddress]]]);
        
        if ($setDefault) {
            \Stripe\Customer::update($stripe_customer_id, ['invoice_settings' => ['default_payment_method' => $stripePmId]]);
            $stmt_unset = $conn->prepare("UPDATE user_payment_methods SET is_default = FALSE WHERE user_id = ?");
            $stmt_unset->bind_param("i", $user_id);
            $stmt_unset->execute();
            $stmt_unset->close();
        }

        $stmt_update = $conn->prepare("UPDATE user_payment_methods SET cardholder_name = ?, expiration_month = ?, expiration_year = ?, billing_address = ?, is_default = ? WHERE id = ? AND user_id = ?");
        $stmt_update->bind_param("ssssiii", $cardholderName, $expirationMonth, $expirationYear, $billingAddress, $setDefault, $methodId, $user_id);
        $stmt_update->execute();
        $stmt_update->close();
        
        $conn->commit();
        echo json_encode(['success' => true, 'message' => 'Payment method updated successfully!']);
    } catch (Exception $e) {
        $conn->rollback();
        throw $e;
    }
}