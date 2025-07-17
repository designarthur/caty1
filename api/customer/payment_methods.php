<?php
// api/customer/payment_methods.php

// Production-safe error reporting for API endpoint
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED & ~E_STRICT);

session_start();
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../vendor/autoload.php'; // Composer autoload for Stripe

header('Content-Type: application/json');

// Check if user is logged in
if (!is_logged_in()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access. Please log in.']);
    exit;
}

$user_id = $_SESSION['user_id'];
$request_method = $_SERVER['REQUEST_METHOD'];
$action = $_REQUEST['action'] ?? ''; // Use $_REQUEST to handle both GET and POST

// --- Initialize Stripe ---
try {
    \Stripe\Stripe::setApiKey($_ENV['STRIPE_SECRET_KEY'] ?? '');

    if (empty(\Stripe\Stripe::getApiKey())) {
        throw new Exception("Stripe secret key is not configured in the .env file.");
    }
} catch (Exception $e) {
    error_log("Stripe initialization failed: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Payment system configuration error.']);
    exit;
}

// CSRF token validation
try {
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

/**
 * Gets or creates a Stripe Customer ID for the given user.
 *
 * @param mysqli $conn The database connection.
 * @param int $user_id The ID of the local user.
 * @return string The Stripe Customer ID.
 * @throws Exception If Stripe customer cannot be retrieved or created.
 */
function getOrCreateStripeCustomer($conn, $user_id) {
    // Try to retrieve existing Stripe Customer ID from local DB
    $stmt = $conn->prepare("SELECT stripe_customer_id, email, first_name, last_name FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $user_data = $result->fetch_assoc();
    $stmt->close();

    if ($user_data && !empty($user_data['stripe_customer_id'])) {
        return $user_data['stripe_customer_id'];
    }

    // If not found, create a new Stripe Customer
    try {
        $customer = \Stripe\Customer::create([
            'email' => $user_data['email'],
            'name'  => $user_data['first_name'] . ' ' . $user_data['last_name'],
            'description' => "Customer for user ID {$user_id}",
        ]);
        $stripe_customer_id = $customer->id;

        // Save new Stripe Customer ID to local DB
        $stmt_update = $conn->prepare("UPDATE users SET stripe_customer_id = ? WHERE id = ?");
        $stmt_update->bind_param("si", $stripe_customer_id, $user_id);
        $stmt_update->execute();
        $stmt_update->close();

        return $stripe_customer_id;
    } catch (\Stripe\Exception\ApiErrorException $e) {
        error_log("Error creating Stripe Customer: " . $e->getMessage());
        throw new Exception("Failed to create Stripe customer account. " . $e->getMessage());
    }
}

function handleAddPaymentMethod($conn, $user_id) {
    $paymentMethodId = trim($_POST['payment_method_id'] ?? '');
    $cardholderName = trim($_POST['cardholder_name'] ?? '');
    $billingAddress = trim($_POST['billing_address'] ?? '');
    $setDefault = isset($_POST['set_default']) && $_POST['set_default'] === 'true';

    // Card details sent for local storage only, not used to create PM on Stripe.
    $cardType = trim($_POST['card_type'] ?? 'Unknown');
    $lastFour = trim($_POST['last_four'] ?? '');
    $expirationMonth = trim($_POST['expiration_month'] ?? '');
    $expirationYear = trim($_POST['expiration_year'] ?? '');

    if (empty($paymentMethodId) || empty($cardholderName) || empty($billingAddress)) {
        echo json_encode(['success' => false, 'message' => 'All fields are required.']);
        return;
    }

    $conn->begin_transaction();
    try {
        $stripe_customer_id = getOrCreateStripeCustomer($conn, $user_id);

        // Attach Payment Method to Stripe Customer
        try {
            $stripePaymentMethod = \Stripe\PaymentMethod::retrieve($paymentMethodId);
            $stripePaymentMethod->attach(['customer' => $stripe_customer_id]);
        } catch (\Stripe\Exception\InvalidRequestException $e) {
            if (strpos($e->getMessage(), 'already been attached') !== false) {
                // Payment method already attached, can continue
                error_log("Payment method {$paymentMethodId} already attached to a customer. Continuing.");
            } else {
                throw $e; // Re-throw other attachment errors
            }
        }

        if ($setDefault) {
            // Unset current default payment method in local DB
            $stmt_unset_default = $conn->prepare("UPDATE user_payment_methods SET is_default = FALSE WHERE user_id = ?");
            $stmt_unset_default->bind_param("i", $user_id);
            $stmt_unset_default->execute();
            $stmt_unset_default->close();

            // Optionally, set as default for future invoices in Stripe (requires customer.invoice_settings update)
            // \Stripe\Customer::update($stripe_customer_id, ['invoice_settings' => ['default_payment_method' => $paymentMethodId]]);
        }

        // Store payment method details locally
        $stmt_insert = $conn->prepare("INSERT INTO user_payment_methods (user_id, stripe_payment_method_id, card_type, last_four, expiration_month, expiration_year, cardholder_name, is_default, billing_address) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt_insert->bind_param("issssssis", $user_id, $paymentMethodId, $cardType, $lastFour, $expirationMonth, $expirationYear, $cardholderName, $setDefault, $billingAddress);

        if ($stmt_insert->execute()) {
            $conn->commit();
            echo json_encode(['success' => true, 'message' => 'Payment method added successfully!']);
        } else {
            throw new Exception("Database insert failed: " . $stmt_insert->error);
        }
        $stmt_insert->close();
    } catch (Exception $e) {
        $conn->rollback();
        error_log("Add payment method failed: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Failed to add payment method: ' . $e->getMessage()]);
    }
}

function handleSetDefaultPaymentMethod($conn, $user_id) {
    $methodId = $_POST['id'] ?? null; // Local DB ID
    if (empty($methodId)) {
        echo json_encode(['success' => false, 'message' => 'Payment method ID required.']);
        return;
    }

    $conn->begin_transaction();
    try {
        // Unset current default in local DB
        $stmt_unset = $conn->prepare("UPDATE user_payment_methods SET is_default = FALSE WHERE user_id = ?");
        $stmt_unset->bind_param("i", $user_id);
        $stmt_unset->execute();
        $stmt_unset->close();

        // Set new default in local DB
        $stmt_set = $conn->prepare("UPDATE user_payment_methods SET is_default = TRUE WHERE id = ? AND user_id = ?");
        $stmt_set->bind_param("ii", $methodId, $user_id);
        if ($stmt_set->execute() && $stmt_set->affected_rows > 0) {
            // Optional: Update Stripe customer's default payment method for subscriptions/invoicing
            // Fetch Stripe Customer ID and Payment Method ID from local DB first
            $stmt_pm = $conn->prepare("SELECT stripe_payment_method_id FROM user_payment_methods WHERE id = ? AND user_id = ?");
            $stmt_pm->bind_param("ii", $methodId, $user_id);
            $stmt_pm->execute();
            $pm_data = $stmt_pm->get_result()->fetch_assoc();
            $stmt_pm->close();

            if ($pm_data && !empty($pm_data['stripe_payment_method_id'])) {
                $stripe_customer_id = getOrCreateStripeCustomer($conn, $user_id);
                try {
                    \Stripe\Customer::update(
                        $stripe_customer_id,
                        ['invoice_settings' => ['default_payment_method' => $pm_data['stripe_payment_method_id']]]
                    );
                } catch (\Stripe\Exception\ApiErrorException $e) {
                    error_log("Stripe error setting default payment method for customer {$stripe_customer_id}: " . $e->getMessage());
                    // Not a critical error for local DB, but worth logging
                }
            }

            $conn->commit();
            echo json_encode(['success' => true, 'message' => 'Default payment method updated.']);
        } else {
            throw new Exception("Payment method not found or failed to set as default.");
        }
        $stmt_set->close();
    } catch (Exception $e) {
        $conn->rollback();
        error_log("Set default failed: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Failed to set default payment method: ' . $e->getMessage()]);
    }
}

function handleDeletePaymentMethod($conn, $user_id) {
    $methodId = $_POST['id'] ?? null; // Local DB ID
    if (empty($methodId)) {
        echo json_encode(['success' => false, 'message' => 'Payment method ID required.']);
        return;
    }

    $conn->begin_transaction();
    try {
        $stmt_check = $conn->prepare("SELECT stripe_payment_method_id, is_default FROM user_payment_methods WHERE id = ? AND user_id = ?");
        $stmt_check->bind_param("ii", $methodId, $user_id);
        $stmt_check->execute();
        $method = $stmt_check->get_result()->fetch_assoc();
        $stmt_check->close();

        if (!$method) {
            throw new Exception("Payment method not found or you don't have permission to delete it.");
        }

        if ($method['is_default']) {
            $stmt_count = $conn->prepare("SELECT COUNT(*) FROM user_payment_methods WHERE user_id = ?");
            $stmt_count->bind_param("i", $user_id);
            $stmt_count->execute();
            $count = $stmt_count->get_result()->fetch_row()[0];
            $stmt_count->close();
            if ($count <= 1) {
                throw new Exception("Cannot delete the only default payment method. Please add another method first or contact support.");
            }
        }

        // Detach Payment Method from Stripe Customer
        if (!empty($method['stripe_payment_method_id'])) {
            try {
                $stripePaymentMethod = \Stripe\PaymentMethod::retrieve($method['stripe_payment_method_id']);
                if ($stripePaymentMethod->customer) { // Only detach if it's attached to a customer
                    $stripePaymentMethod->detach();
                }
            } catch (\Stripe\Exception\ApiErrorException $e) {
                // Log error but don't prevent local deletion if Stripe already removed it or it's unattached
                error_log("Stripe PaymentMethod detach failed: " . $e->getMessage());
            }
        }

        $stmt_delete = $conn->prepare("DELETE FROM user_payment_methods WHERE id = ? AND user_id = ?");
        $stmt_delete->bind_param("ii", $methodId, $user_id);
        if ($stmt_delete->execute() && $stmt_delete->affected_rows > 0) {
            $conn->commit();
            echo json_encode(['success' => true, 'message' => 'Payment method deleted.']);
        } else {
            throw new Exception("Payment method not found in DB or failed to delete.");
        }
        $stmt_delete->close();
    } catch (Exception $e) {
        $conn->rollback();
        error_log("Delete method failed: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

function handleUpdatePaymentMethod($conn, $user_id) {
    $methodId = $_POST['id'] ?? null; // Local DB ID
    $cardholderName = trim($_POST['cardholder_name'] ?? '');
    $expirationMonth = trim($_POST['expiration_month'] ?? '');
    $expirationYear = trim($_POST['expiration_year'] ?? '');
    $billingAddress = trim($_POST['billing_address'] ?? '');
    $setDefault = isset($_POST['set_default']) && $_POST['set_default'] === 'true';

    if (empty($methodId) || empty($cardholderName) || empty($expirationMonth) || empty($expirationYear) || empty($billingAddress)) {
        echo json_encode(['success' => false, 'message' => 'All fields are required.']);
        return;
    }
    // Simple validation for MM/YYYY
    if (!preg_match('/^(0[1-9]|1[0-2])$/', $expirationMonth) || !preg_match('/^\d{4}$/', $expirationYear)) {
        echo json_encode(['success' => false, 'message' => 'Invalid expiration date format (MM/YYYY).']);
        return;
    }

    $conn->begin_transaction();
    try {
        if ($setDefault) {
            // Unset current default in local DB
            $stmt_unset = $conn->prepare("UPDATE user_payment_methods SET is_default = FALSE WHERE user_id = ?");
            $stmt_unset->bind_param("i", $user_id);
            $stmt_unset->execute();
            $stmt_unset->close();

            // Set as default in Stripe (invoice_settings.default_payment_method)
            $stmt_pm_id = $conn->prepare("SELECT stripe_payment_method_id FROM user_payment_methods WHERE id = ? AND user_id = ?");
            $stmt_pm_id->bind_param("ii", $methodId, $user_id);
            $stmt_pm_id->execute();
            $pm_data = $stmt_pm_id->get_result()->fetch_assoc();
            $stmt_pm_id->close();

            if ($pm_data && !empty($pm_data['stripe_payment_method_id'])) {
                $stripe_customer_id = getOrCreateStripeCustomer($conn, $user_id);
                try {
                    \Stripe\Customer::update(
                        $stripe_customer_id,
                        ['invoice_settings' => ['default_payment_method' => $pm_data['stripe_payment_method_id']]]
                    );
                } catch (\Stripe\Exception\ApiErrorException $e) {
                    error_log("Stripe error setting default payment method on update: " . $e->getMessage());
                }
            }
        }

        // Update local DB record for billing details and default status
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
    } catch (Exception $e) {
        $conn->rollback();
        error_log("Update method failed: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Failed to update payment method.']);
    }
}

function handleGetDefaultMethod($conn, $user_id) {
    $stmt = $conn->prepare("SELECT * FROM user_payment_methods WHERE user_id = ? AND is_default = TRUE LIMIT 1");
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
