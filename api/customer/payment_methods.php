<?php
// api/customer/payment_methods.php

// Production-safe error reporting for API endpoint
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED & ~E_STRICT);


// Start session and include necessary files
session_start();
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/session.php'; // For is_logged_in() and $_SESSION['user_id']
require_once __DIR__ . '/../../includes/functions.php'; // For validate_csrf_token()
require_once __DIR__ . '/../../vendor/autoload.php'; // For Stripe


header('Content-Type: application/json');

// Check if user is logged in
if (!is_logged_in()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access. Please log in.']);
    exit;
}

$user_id = $_SESSION['user_id'];
$request_method = $_SERVER['REQUEST_METHOD'];
$action = $_REQUEST['action'] ?? ''; // Use $_REQUEST to handle both GET and POST

// --- Initialize Stripe API ---
try {
    \Stripe\Stripe::setApiKey($_ENV['STRIPE_SECRET_KEY'] ?? getenv('STRIPE_SECRET_KEY'));
} catch (Exception $e) {
    error_log("Stripe API initialization failed in payment_methods.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Payment system configuration error.']);
    exit;
}


if ($request_method === 'POST') {
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
            echo json_encode(['success' => false, 'message' => 'Invalid POST action.']);
            break;
    }
} elseif ($request_method === 'GET') {
    switch($action) {
        case 'get_default_method':
            handleGetDefaultMethod($conn, $user_id);
            break;
        default:
            echo json_encode(['success' => false, 'message' => 'Invalid GET action.']);
            break;
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
}

$conn->close();

function handleAddPaymentMethod($conn, $user_id) {
    $paymentMethodId = trim($_POST['payment_method_id'] ?? ''); // Stripe PaymentMethod ID
    $cardholderName = trim($_POST['cardholder_name'] ?? '');
    $billingAddress = trim($_POST['billing_address'] ?? '');
    $setDefault = isset($_POST['set_default']) && $_POST['set_default'] === 'on';

    if (empty($paymentMethodId) || empty($cardholderName) || empty($billingAddress)) {
        echo json_encode(['success' => false, 'message' => 'Payment method ID, cardholder name, and billing address are required.']);
        return;
    }

    $conn->begin_transaction();
    try {
        // Retrieve PaymentMethod details from Stripe
        $paymentMethod = \Stripe\PaymentMethod::retrieve($paymentMethodId);

        $cardType = $paymentMethod->card->brand ?? 'unknown';
        $lastFour = $paymentMethod->card->last4 ?? '';
        $expMonth = $paymentMethod->card->exp_month ?? '';
        $expYear = $paymentMethod->card->exp_year ?? '';

        // Attach PaymentMethod to Stripe Customer
        $stripe_customer_id = null;
        $stmt_user_stripe_id = $conn->prepare("SELECT stripe_customer_id FROM users WHERE id = ?");
        $stmt_user_stripe_id->bind_param("i", $user_id);
        $stmt_user_stripe_id->execute();
        $user_stripe_data = $stmt_user_stripe_id->get_result()->fetch_assoc();
        if ($user_stripe_data && !empty($user_stripe_data['stripe_customer_id'])) {
            $stripe_customer_id = $user_stripe_data['stripe_customer_id'];
        }
        $stmt_user_stripe_id->close();

        if (!$stripe_customer_id) {
            // Create new Stripe customer if not exists
            $customer = \Stripe\Customer::create([
                'email' => $_SESSION['user_email'],
                'name' => $_SESSION['user_first_name'] . ' ' . $_SESSION['user_last_name'],
                'payment_method' => $paymentMethodId, // Set as default PaymentMethod
                'invoice_settings' => ['default_payment_method' => $paymentMethodId],
                'metadata' => ['database_user_id' => $user_id]
            ]);
            $stripe_customer_id = $customer->id;

            $stmt_update_user_stripe_id = $conn->prepare("UPDATE users SET stripe_customer_id = ? WHERE id = ?");
            $stmt_update_user_stripe_id->bind_param("si", $stripe_customer_id, $user_id);
            $stmt_update_user_stripe_id->execute();
            $stmt_update_user_stripe_id->close();
        } else {
            // Attach PaymentMethod to existing Customer
            $paymentMethod->attach(['customer' => $stripe_customer_id]);
        }

        // If setting as default, update Stripe customer's default payment method
        if ($setDefault) {
            \Stripe\Customer::update($stripe_customer_id, [
                'invoice_settings' => ['default_payment_method' => $paymentMethodId],
            ]);
        }


        // Update is_default status for existing methods if new one is default
        if ($setDefault) {
            $stmt_unset_default = $conn->prepare("UPDATE user_payment_methods SET is_default = FALSE WHERE user_id = ?");
            $stmt_unset_default->bind_param("i", $user_id);
            $stmt_unset_default->execute();
            $stmt_unset_default->close();
        }

        // Store PaymentMethod ID and details in your database
        // `braintree_payment_token` column is now repurposed to store Stripe PaymentMethod IDs
        $stmt_insert = $conn->prepare("INSERT INTO user_payment_methods (user_id, braintree_payment_token, card_type, last_four, expiration_month, expiration_year, cardholder_name, is_default, billing_address) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt_insert->bind_param("issssssis", $user_id, $paymentMethodId, $cardType, $lastFour, $expMonth, $expYear, $cardholderName, $setDefault, $billingAddress);

        if ($stmt_insert->execute()) {
            $conn->commit();
            echo json_encode(['success' => true, 'message' => 'Payment method added successfully!']);
        } else {
            throw new Exception("Database insert failed: " . $stmt_insert->error);
        }
        $stmt_insert->close();
    } catch (\Stripe\Exception\ApiErrorException $e) {
        $conn->rollback();
        error_log("Stripe API Error adding payment method: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Stripe error: ' . $e->getMessage()]);
    } catch (Exception $e) {
        $conn->rollback();
        error_log("Add payment method failed: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Failed to add payment method: ' . $e->getMessage()]);
    }
}

function handleSetDefaultPaymentMethod($conn, $user_id) {
    $methodId = $_POST['id'] ?? null;
    if (empty($methodId)) {
        echo json_encode(['success' => false, 'message' => 'Payment method ID required.']);
        return;
    }

    $conn->begin_transaction();
    try {
        // Get Stripe Customer ID and PaymentMethod ID from your DB
        $stmt_get_pm_info = $conn->prepare("SELECT u.stripe_customer_id, upm.braintree_payment_token FROM users u JOIN user_payment_methods upm ON u.id = upm.user_id WHERE upm.id = ? AND u.id = ?");
        $stmt_get_pm_info->bind_param("ii", $methodId, $user_id);
        $stmt_get_pm_info->execute();
        $pm_info = $stmt_get_pm_info->get_result()->fetch_assoc();
        $stmt_get_pm_info->close();

        if (!$pm_info || empty($pm_info['stripe_customer_id']) || empty($pm_info['braintree_payment_token'])) {
            throw new Exception("Payment method or Stripe customer ID not found.");
        }

        // Set as default in Stripe
        \Stripe\Customer::update($pm_info['stripe_customer_id'], [
            'invoice_settings' => ['default_payment_method' => $pm_info['braintree_payment_token']],
        ]);

        // Unset current default in your DB
        $stmt_unset = $conn->prepare("UPDATE user_payment_methods SET is_default = FALSE WHERE user_id = ?");
        $stmt_unset->bind_param("i", $user_id);
        $stmt_unset->execute();
        $stmt_unset->close();

        // Set new default in your DB
        $stmt_set = $conn->prepare("UPDATE user_payment_methods SET is_default = TRUE WHERE id = ? AND user_id = ?");
        $stmt_set->bind_param("ii", $methodId, $user_id);
        if ($stmt_set->execute() && $stmt_set->affected_rows > 0) {
            $conn->commit();
            echo json_encode(['success' => true, 'message' => 'Default payment method updated.']);
        } else {
            throw new Exception("Payment method not found or failed to set as default.");
        }
        $stmt_set->close();
    } catch (\Stripe\Exception\ApiErrorException $e) {
        $conn->rollback();
        error_log("Stripe API Error setting default payment method: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Stripe error: ' . $e->getMessage()]);
    } catch (Exception $e) {
        $conn->rollback();
        error_log("Set default failed: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Failed to set default payment method.']);
    }
}

function handleDeletePaymentMethod($conn, $user_id) {
    $methodId = $_POST['id'] ?? null; // ID from your database
    $stripePmId = $_POST['stripe_pm_id'] ?? null; // Stripe PaymentMethod ID

    if (empty($methodId) || empty($stripePmId)) {
        echo json_encode(['success' => false, 'message' => 'Payment method ID (local and Stripe) required.']);
        return;
    }

    $conn->begin_transaction();
    try {
        $stmt_check = $conn->prepare("SELECT is_default, braintree_payment_token FROM user_payment_methods WHERE id = ? AND user_id = ?");
        $stmt_check->bind_param("ii", $methodId, $user_id);
        $stmt_check->execute();
        $method_info = $stmt_check->get_result()->fetch_assoc();
        $stmt_check->close();

        if (!$method_info || $method_info['braintree_payment_token'] !== $stripePmId) {
            throw new Exception("Payment method not found or unauthorized.");
        }

        if ($method_info['is_default']) {
            $stmt_count = $conn->prepare("SELECT COUNT(*) FROM user_payment_methods WHERE user_id = ?");
            $stmt_count->bind_param("i", $user_id);
            $stmt_count->execute();
            $count = $stmt_count->get_result()->fetch_row()[0];
            $stmt_count->close();
            if ($count <= 1) {
                throw new Exception("Cannot delete the only default payment method. Please add another method first or contact support.");
            }
        }

        // Detach PaymentMethod from Stripe Customer
        // This is important to ensure it's no longer usable
        \Stripe\PaymentMethod::retrieve($stripePmId)->detach();

        // Delete from your database
        $stmt_delete = $conn->prepare("DELETE FROM user_payment_methods WHERE id = ? AND user_id = ?");
        $stmt_delete->bind_param("ii", $methodId, $user_id);
        if ($stmt_delete->execute() && $stmt_delete->affected_rows > 0) {
            $conn->commit();
            echo json_encode(['success' => true, 'message' => 'Payment method deleted.']);
        } else {
            throw new Exception("Payment method not found in DB or failed to delete.");
        }
        $stmt_delete->close();
    } catch (\Stripe\Exception\ApiErrorException $e) {
        $conn->rollback();
        error_log("Stripe API Error deleting payment method: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Stripe error: ' . $e->getMessage()]);
    } catch (Exception $e) {
        $conn->rollback();
        error_log("Delete method failed: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

function handleUpdatePaymentMethod($conn, $user_id) {
    $methodId = $_POST['id'] ?? null;
    $cardholderName = trim($_POST['cardholder_name'] ?? '');
    $expirationMonth = trim($_POST['expiration_month'] ?? '');
    $expirationYear = trim($_POST['expiration_year'] ?? '');
    $billingAddress = trim($_POST['billing_address'] ?? '');
    $setDefault = isset($_POST['set_default']) && $_POST['set_default'] === 'on';

    if (empty($methodId) || empty($cardholderName) || empty($expirationMonth) || empty($expirationYear) || empty($billingAddress)) {
        echo json_encode(['success' => false, 'message' => 'All fields are required.']);
        return;
    }
    if (!preg_match('/^(0[1-9]|1[0-2])$/', $expirationMonth) || !preg_match('/^\d{4}$/', $expirationYear)) {
        echo json_encode(['success' => false, 'message' => 'Invalid expiration date format (MM/YYYY).']);
        return;
    }

    $conn->begin_transaction();
    try {
        // Get the Stripe Payment Method ID associated with this local ID
        $stmt_get_stripe_pm_id = $conn->prepare("SELECT braintree_payment_token, is_default FROM user_payment_methods WHERE id = ? AND user_id = ?");
        $stmt_get_stripe_pm_id->bind_param("ii", $methodId, $user_id);
        $stmt_get_stripe_pm_id->execute();
        $method_db_info = $stmt_get_stripe_pm_id->get_result()->fetch_assoc();
        $stmt_get_stripe_pm_id->close();

        if (!$method_db_info) {
            throw new Exception("Payment method not found in database.");
        }
        $stripePmId = $method_db_info['braintree_payment_token'];

        // Update PaymentMethod in Stripe (only allowed fields: billing_details)
        // Note: Card number itself cannot be updated directly via Stripe API.
        \Stripe\PaymentMethod::update(
            $stripePmId,
            [
                'billing_details' => [
                    'name' => $cardholderName,
                    'address' => [
                        'line1' => $billingAddress, // Stripe usually needs address broken down
                        // Add city, state, postal_code if available in your billingAddress field
                    ],
                ],
                // Update expiry is not directly supported on PaymentMethod object.
                // It's tied to the card itself. For changes, typically a new card is used.
                // You might need to add complex logic here if expiry updates are crucial for your users.
            ]
        );

        // If setting as default, update Stripe customer's default payment method
        if ($setDefault) {
            $stripe_customer_id = null;
            $stmt_user_stripe_id = $conn->prepare("SELECT stripe_customer_id FROM users WHERE id = ?");
            $stmt_user_stripe_id->bind_param("i", $user_id);
            $stmt_user_stripe_id->execute();
            $user_stripe_data = $stmt_user_stripe_id->get_result()->fetch_assoc();
            $stmt_user_stripe_id->close();
            if ($user_stripe_data && !empty($user_stripe_data['stripe_customer_id'])) {
                $stripe_customer_id = $user_stripe_data['stripe_customer_id'];
                \Stripe\Customer::update($stripe_customer_id, [
                    'invoice_settings' => ['default_payment_method' => $stripePmId],
                ]);
            }
        }

        // Update is_default status for other methods if new one is default
        if ($setDefault && !$method_db_info['is_default']) { // Only unset others if it wasn't already default
            $stmt_unset = $conn->prepare("UPDATE user_payment_methods SET is_default = FALSE WHERE user_id = ?");
            $stmt_unset->bind_param("i", $user_id);
            $stmt_unset->execute();
            $stmt_unset->close();
        }


        $stmt_update = $conn->prepare("UPDATE user_payment_methods SET cardholder_name = ?, expiration_month = ?, expiration_year = ?, billing_address = ?, is_default = ? WHERE id = ? AND user_id = ?");
        $stmt_update->bind_param("ssssiii", $cardholderName, $expirationMonth, $expirationYear, $billingAddress, $setDefault, $methodId, $user_id);

        if ($stmt_update->execute()) {
            if ($stmt_update->affected_rows > 0) {
                $conn->commit();
                echo json_encode(['success' => true, 'message' => 'Payment method updated successfully!']);
            } else {
                $conn->rollback();
                echo json_encode(['success' => true, 'message' => 'No changes made or payment method not found.']);
            }
        } else {
            throw new Exception("Database update failed: " . $stmt_update->error);
        }
        $stmt_update->close();
    } catch (\Stripe\Exception\ApiErrorException $e) {
        $conn->rollback();
        error_log("Stripe API Error updating payment method: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Stripe error: ' . $e->getMessage()]);
    } catch (Exception $e) {
        $conn->rollback();
        error_log("Update method failed: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Failed to update payment method: ' . $e->getMessage()]);
    }
}

function handleGetDefaultMethod($conn, $user_id) {
    $stmt = $conn->prepare("SELECT id, braintree_payment_token, card_type, last_four, expiration_month, expiration_year, cardholder_name, billing_address FROM user_payment_methods WHERE user_id = ? AND is_default = TRUE LIMIT 1");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $method = $result->fetch_assoc();
    $stmt->close();

    if ($method) {
        echo json_encode(['success' => true, 'method' => $method]);
    } else {
        echo json_encode(['success' => false, 'message' => 'No default payment method found.']);
    }
}
