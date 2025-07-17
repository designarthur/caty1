<?php
// includes/functions.php

// --- Dependency Loader & Namespace Imports ---
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// This ensures all Composer packages (like PHPMailer and Dotenv) are available.
require_once __DIR__ . '/../vendor/autoload.php';

// --- Environment Variable Loading ---
try {
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
    $dotenv->load();
} catch (Exception $e) {
    error_log("Could not load .env file: " . $e->getMessage());
    // In a production environment, you might want to die() here if the .env file is critical.
}


// --- Core Utility Functions ---

/**
 * Redirects the user to a specified URL and terminates the script.
 * @param string $url The URL to redirect to.
 */
function redirect($url) {
    header("Location: " . $url);
    exit();
}

/**
 * Hashes a password using the modern and secure BCRYPT algorithm.
 * @param string $password The plain text password.
 * @return string The hashed password.
 */
function hashPassword($password) {
    return password_hash($password, PASSWORD_DEFAULT);
}

/**
 * Verifies a plain text password against a stored hash.
 *
 * @param string $password The plain text password.
 * @param string $hash The hashed password from the database.
 * @return bool True if the passwords match, false otherwise.
 */
function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}


// --- CSRF Protection Functions ---

/**
 * Generates and stores a CSRF token in the session if one doesn't exist.
 * @return string The generated token.
 */
function generate_csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Validates the submitted CSRF token against the one in the session.
 * @throws Exception if the token is invalid or missing.
 */
function validate_csrf_token() {
    if (!isset($_POST['csrf_token']) || !isset($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        throw new Exception('CSRF token validation failed. Please refresh and try again.');
    }
    unset($_SESSION['csrf_token']);
}


// --- Email Sending Function ---

/**
 * Sends an email using PHPMailer with credentials from environment variables.
 * @param string $to Recipient's email address.
 * @param string $subject The email subject.
 * @param string $body The HTML body of the email.
 * @param string $altBody Optional plain text alternative.
 * @return bool True on success, false on failure.
 */
function sendEmail($to, $subject, $body, $altBody = '') {
    $mail = new PHPMailer(true);

    try {
        // Server settings from the now-loaded .env file.
        // Use getenv() as a fallback for $_ENV if environment variables are set differently on your server.
        // Also, explicitly cast to string to prevent warnings if values are empty/null.
        $mail->isSMTP();
        $mail->Host       = (string)($_ENV['SMTP_HOST'] ?? getenv('SMTP_HOST'));
        $mail->SMTPAuth   = true;
        $mail->Username   = (string)($_ENV['SMTP_USER'] ?? getenv('SMTP_USER'));
        $mail->Password   = (string)($_ENV['SMTP_PASS'] ?? getenv('SMTP_PASS'));
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = (int)($_ENV['SMTP_PORT'] ?? getenv('SMTP_PORT'));

        // Recipients
        $mail->setFrom(
            (string)($_ENV['SMTP_FROM_EMAIL'] ?? getenv('SMTP_FROM_EMAIL')),
            (string)($_ENV['SMTP_FROM_NAME'] ?? getenv('SMTP_FROM_NAME'))
        );
        $mail->addAddress($to);

        // Content
        $mail->isHTML(true);
        $mail->Subject = (string)$subject; // Ensure subject is a string
        $mail->Body    = (string)$body;    // Ensure body is a string
        // Safely generate the plain-text body to prevent the "Deprecated" notice.
        // Ensure $body is treated as a string before strip_tags.
        $mail->AltBody = empty($altBody) ? strip_tags((string)$body) : (string)$altBody;

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Email could not be sent to {$to}. Mailer Error: {$mail->ErrorInfo}");
        return false; // Return false but don't crash the script.
    }
}


// --- Data & Business Logic Functions ---

/**
 * Generates a cryptographically secure random token.
 * @param int $length The desired length of the token string.
 * @return string
 */
function generateToken($length = 32) {
    return bin2hex(random_bytes($length / 2));
}

/**
 * Retrieves a system setting value from the database, using a static cache.
 * @param string $key The setting key to retrieve.
 * @return string|null
 */
function getSystemSetting($key) {
    global $conn; // IMPORTANT: Access the global database connection
    static $settings = [];

    if (!isset($settings[$key])) {
        // Ensure $conn is not null before preparing statement
        if (!$conn) {
            error_log("Attempted to call getSystemSetting('{$key}') but \$conn is null.");
            return null; // Or throw a more specific error if appropriate
        }
        $stmt = $conn->prepare("SELECT setting_value FROM system_settings WHERE setting_key = ?");
        $stmt->bind_param("s", $key);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        $settings[$key] = $result['setting_value'] ?? null;
        $stmt->close();
    }
    return $settings[$key];
}


/**
 * Gets the count of unread items for the admin sidebar.
 * @return array Associative array with 'quotes' and 'invoices' counts.
 */
function get_admin_notification_counts() {
    global $conn;
    $counts = ['quotes' => 0, 'invoices' => 0];

    // Get count of unviewed quotes
    $result_quotes = $conn->query("SELECT COUNT(*) AS count FROM quotes WHERE is_viewed_by_admin = 0 AND status = 'pending'");
    if ($result_quotes) {
        $counts['quotes'] = $result_quotes->fetch_assoc()['count'];
    }

    // Get count of unviewed invoices
    $result_invoices = $conn->query("SELECT COUNT(*) AS count FROM invoices WHERE is_viewed_by_admin = 0 AND status = 'pending'");
    if ($result_invoices) {
        $counts['invoices'] = $result_invoices->fetch_assoc()['count'];
    }

    return $counts;
}

/**
 * Ensures a Stripe Customer exists for the user. If not, it creates one and updates the DB.
 * If the existing Stripe Customer ID is invalid, it attempts to recreate a new one.
 * @param mysqli $conn The database connection.
 * @param int $user_id The ID of the user.
 * @return string The valid Stripe Customer ID.
 * @throws Exception If customer cannot be created/retrieved after multiple attempts.
 */
function ensureStripeCustomerExists(mysqli $conn, int $user_id): string {
    $max_attempts = 2; // Try to retrieve/create customer a couple of times
    for ($attempt = 1; $attempt <= $max_attempts; $attempt++) {
        // 1. Get stripe_customer_id from local DB
        $stripe_customer_id = null;
        $user_email = null;
        $user_name = null;

        $stmt_user_info = $conn->prepare("SELECT stripe_customer_id, email, first_name, last_name FROM users WHERE id = ?");
        $stmt_user_info->bind_param("i", $user_id);
        $stmt_user_info->execute();
        $user_data = $stmt_user_info->get_result()->fetch_assoc();
        $stmt_user_info->close();

        if ($user_data) {
            $stripe_customer_id = $user_data['stripe_customer_id'];
            $user_email = $user_data['email'];
            $user_name = trim($user_data['first_name'] . ' ' . $user_data['last_name']);
        } else {
            throw new Exception("User not found in local database.");
        }

        // 2. If local ID exists, try to retrieve it from Stripe
        if ($stripe_customer_id) {
            try {
                \Stripe\Customer::retrieve($stripe_customer_id);
                return $stripe_customer_id; // Existing customer is valid
            } catch (\Stripe\Exception\InvalidRequestException $e) {
                // "No such customer" error - local ID is invalid, try to recreate
                error_log("Stripe customer ID '{$stripe_customer_id}' for user {$user_id} is invalid. Attempting to recreate. Error: " . $e->getMessage());
                $stripe_customer_id = null; // Mark as invalid, proceed to creation
            } catch (Exception $e) {
                // Other Stripe API errors when retrieving customer
                error_log("Stripe API error retrieving customer '{$stripe_customer_id}' for user {$user_id}: " . $e->getMessage());
                throw new Exception("Failed to verify Stripe customer: " . $e->getMessage());
            }
        }

        // 3. If local ID is missing or invalid, create a new Stripe Customer
        if (!$stripe_customer_id) {
            try {
                $customer = \Stripe\Customer::create([
                    'email' => $user_email,
                    'name' => $user_name,
                    'metadata' => ['database_user_id' => $user_id]
                ]);
                $new_stripe_customer_id = $customer->id;

                // Update local DB with new Stripe Customer ID
                $stmt_update_user_stripe_id = $conn->prepare("UPDATE users SET stripe_customer_id = ? WHERE id = ?");
                $stmt_update_user_stripe_id->bind_param("si", $new_stripe_customer_id, $user_id);
                $stmt_update_user_stripe_id->execute();
                $stmt_update_user_stripe_id->close();
                
                return $new_stripe_customer_id; // Successfully created and updated
            } catch (Exception $e) {
                error_log("Stripe API error creating new customer for user {$user_id}: " . $e->getMessage());
                if ($attempt < $max_attempts) {
                    // Log and retry if not last attempt
                    usleep(100000); // Small delay before retry
                    continue;
                }
                throw new Exception("Failed to create Stripe customer: " . $e->getMessage());
            }
        }
    }
    throw new Exception("Failed to ensure a valid Stripe customer after multiple attempts.");
}


/**
 * Centralized function to create a booking after a successful payment.
 * @param mysqli $conn The database connection object.
 * @param int $invoice_id The ID of the paid invoice.
 * @return int|null The new booking ID on success, or null on failure.
 * @throws Exception on any failure.
 */
function createBookingFromInvoice(mysqli $conn, int $invoice_id): ?int {
    // Fetch all necessary data from the invoice, quote, and user tables.
    $stmt_fetch = $conn->prepare("
        SELECT
            i.user_id, i.amount, i.quote_id,
            q.service_type, q.location, q.delivery_date, q.removal_date, q.live_load_needed,
            q.is_urgent, q.driver_instructions, q.daily_rate, q.quoted_price,
            u.first_name, u.email
        FROM invoices i
        JOIN users u ON i.user_id = u.id
        LEFT JOIN quotes q ON i.quote_id = q.id -- Use LEFT JOIN as quote_id can be NULL
        WHERE i.id = ?
    ");
    $stmt_fetch->bind_param("i", $invoice_id);
    $stmt_fetch->execute();
    $data = $stmt_fetch->get_result()->fetch_assoc();
    $stmt_fetch->close();

    if (!$data) {
        throw new Exception("Could not retrieve necessary data to create booking for invoice ID: {$invoice_id}");
    }

    // Update quote status to 'converted_to_booking'
    if ($data['quote_id']) {
        $stmt_quote = $conn->prepare("UPDATE quotes SET status = 'converted_to_booking' WHERE id = ?");
        $stmt_quote->bind_param("i", $data['quote_id']);
        $stmt_quote->execute();
        $stmt_quote->close();
    }

    $booking_number = 'BK-' . str_pad($invoice_id, 6, '0', STR_PAD_LEFT);
    
    // --- FIX 1: Use quote's delivery_date or removal_date as booking start_date ---
    $start_date = $data['delivery_date'] ?? $data['removal_date']; 
    if (empty($start_date)) {
        // Fallback to current date if no specific date was set in the quote, though unlikely for a confirmed booking.
        $start_date = date('Y-m-d');
        error_log("Booking start_date not found in quote {$data['quote_id']}, defaulting to today for booking ID {$booking_id}");
    }

    $end_date = null; // Initialize end_date
    $equipment_details_json = null;
    $junk_details_json = null;

    // Fetch service-specific details from quote tables if available
    if ($data['service_type'] == 'equipment_rental' && $data['quote_id']) {
        $equipment_items_for_json = [];
        // Use JOIN to get equipment_details related to the quote
        $stmt_eq_details = $conn->prepare("SELECT equipment_name, quantity, duration_days, specific_needs FROM quote_equipment_details WHERE quote_id = ?");
        $stmt_eq_details->bind_param("i", $data['quote_id']);
        $stmt_eq_details->execute();
        $eq_result = $stmt_eq_details->get_result();
        while($eq_row = $eq_result->fetch_assoc()) {
            $equipment_items_for_json[] = $eq_row;
        }
        $stmt_eq_details->close();

        $equipment_details_json = json_encode($equipment_items_for_json);

        // --- FIX 2: Calculate end_date based on max duration from equipment_details ---
        // Assuming all equipment for a single booking will have the same (or relevant) rental duration
        $duration = 0;
        foreach ($equipment_items_for_json as $item) {
            $duration = max($duration, (int)($item['duration_days'] ?? 0));
        }
        // If no duration found, default to a reasonable period (e.g., 7 days)
        if ($duration === 0) {
            $duration = 7; 
            error_log("No duration_days found for equipment rental quote {$data['quote_id']}, defaulting to 7 days.");
        }
        
        // Calculate end_date from the determined start_date and duration
        if ($start_date) {
            try {
                $startDateTime = new DateTime($start_date);
                $end_date = $startDateTime->modify("+$duration days")->format('Y-m-d');
            } catch (Exception $e) {
                error_log("Error calculating end_date for booking from quote {$data['quote_id']}: {$e->getMessage()}");
                $end_date = null; // Fallback if date calculation fails
            }
        }

    } else if ($data['service_type'] == 'junk_removal' && $data['quote_id']) {
        $junk_details_for_json = [];
        $stmt_junk_details = $conn->prepare("SELECT junk_items_json, recommended_dumpster_size, additional_comment FROM junk_removal_details WHERE quote_id = ?");
        $stmt_junk_details->bind_param("i", $data['quote_id']);
        $stmt_junk_details->execute();
        $junk_result = $stmt_junk_details->get_result()->fetch_assoc();
        $stmt_junk_details->close();

        if ($junk_result) {
            $junk_details_for_json = [
                'junkItems' => json_decode($junk_result['junk_items_json'] ?? '[]', true),
                'recommendedDumpsterSize' => $junk_result['recommended_dumpster_size'],
                'additionalComment' => $junk_result['additional_comment']
            ];
        }
        $junk_details_json = json_encode($junk_details_for_json);
        // For junk removal, the end date is typically the same as the start date (one-time service)
        $end_date = $start_date; 
    }
    
    // Convert boolean-like values to actual integers (0 or 1) for tinyint columns
    $live_load_requested_int = (int)($data['live_load_needed'] ?? 0);
    $is_urgent_int = (int)($data['is_urgent'] ?? 0);

    // Define the literal 'scheduled' as a variable to pass by reference
    $status_scheduled = 'scheduled'; // Fix for bind_param Argument #6 error

    $stmt_booking = $conn->prepare("
        INSERT INTO bookings (invoice_id, user_id, booking_number, service_type, status, start_date, end_date, delivery_location, delivery_instructions, live_load_requested, is_urgent, total_price, equipment_details, junk_details)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    // Corrected bind_param types and arguments: "iissssssiisdss" for 14 variables
    $stmt_booking->bind_param(
        "iissssssiisdss", // Corrected from "iissssssiidsdss" to match 14 variables
        $invoice_id,
        $data['user_id'],
        $booking_number,
        $data['service_type'],
        $status_scheduled, // Use the variable here
        $start_date,
        $end_date, // This will now be correctly calculated or be null/same as start_date
        $data['location'],
        $data['driver_instructions'],
        $live_load_requested_int, 
        $is_urgent_int,           
        $data['amount'],          // Use the invoice amount as total_price for the booking
        $equipment_details_json,
        $junk_details_json
    );
    
    if (!$stmt_booking->execute()) {
        throw new Exception("Database insert failed for booking: " . $stmt_booking->error);
    }
    
    $booking_id = $conn->insert_id;
    $stmt_booking->close();
    
    $dashboardLink = "https://{$_SERVER['HTTP_HOST']}/customer/dashboard.php#bookings?booking_id={$booking_id}";
    $emailSubject = "Your Booking #{$booking_number} is Confirmed!";
    $emailBody = "<p>Dear {$data['first_name']},</p><p>Your booking for " . ucwords(str_replace('_', ' ', $data['service_type'])) . " (Booking #{$booking_number}) has been confirmed.</p><p>You can view full details here: <a href='{$dashboardLink}'>View Your Booking</a></p>";
    sendEmail($data['email'], $emailSubject, $emailBody);

    $notification_message = "Your booking #{$booking_number} has been confirmed!";
    $stmt_notify = $conn->prepare("INSERT INTO notifications (user_id, type, message, link) VALUES (?, 'booking_confirmed', ?, ?)");
    $stmt_notify->bind_param("iss", $data['user_id'], $notification_message, $dashboardLink);
    $stmt_notify->execute();
    $stmt_notify->close();

    return $booking_id;
}