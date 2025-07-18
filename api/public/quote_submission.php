<?php
session_start();
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/functions.php'; // For sendEmail, generateToken, hashPassword, validate_csrf_token, getSystemSetting
require_once __DIR__ . '/../../includes/session.php'; // For is_logged_in (though public page won't always be logged in)

header('Content-Type: application/json');

// Global exception handler for API errors
set_exception_handler(function ($exception) {
    error_log("API Error in quote_submission.php: " . $exception->getMessage() . " on line " . $exception->getLine() . " in file " . $exception->getFile());
    if (!headers_sent()) {
        http_response_code(500);
    }
    echo json_encode(['success' => false, 'message' => 'An internal server error occurred. Please try again later.']);
    exit;
});

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method Not Allowed.']);
    exit;
}

try {
    // Validate CSRF token
    validate_csrf_token();

    // 1. Extract common user fields
    $name = trim($_POST['name'] ?? '');
    $email = filter_var(trim($_POST['email'] ?? ''), FILTER_VALIDATE_EMAIL);
    $phone = trim($_POST['phone'] ?? '');
    $company = trim($_POST['company'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $city = trim($_POST['city'] ?? '');
    $state = trim($_POST['state'] ?? '');
    $zip_code = trim($_POST['zip_code'] ?? '');
    $service_type = trim($_POST['service_type'] ?? '');

    // Basic validation for required common fields
    if (empty($name) || empty($email) || empty($phone) || empty($address) || empty($city) || empty($state) || empty($zip_code) || empty($service_type)) {
        throw new Exception('All required contact and service type fields must be filled.');
    }
    if (!$email) {
        throw new Exception('Invalid email format.');
    }

    $conn->begin_transaction();

    // 2. Find or Create User Account
    $user_id = null;
    $user_exists = false;
    $generated_password = null;

    $stmt_check_user = $conn->prepare("SELECT id, first_name, last_name FROM users WHERE email = ?");
    $stmt_check_user->bind_param("s", $email);
    $stmt_check_user->execute();
    $user_result = $stmt_check_user->get_result();
    if ($user_result->num_rows > 0) {
        $user_data = $user_result->fetch_assoc();
        $user_id = $user_data['id'];
        $user_exists = true;
        // If user exists, update their profile with the latest info from the form
        $name_parts = explode(' ', $name, 2);
        $first_name = $name_parts[0];
        $last_name = $name_parts[1] ?? '';
        $stmt_update_user = $conn->prepare("UPDATE users SET first_name = ?, last_name = ?, phone_number = ?, company = ?, address = ?, city = ?, state = ?, zip_code = ? WHERE id = ?");
        $stmt_update_user->bind_param("ssssssssi", $first_name, $last_name, $phone, $company, $address, $city, $state, $zip_code, $user_id);
        $stmt_update_user->execute();
    } else {
        // Create new user account including 'company' column
        $name_parts = explode(' ', $name, 2);
        $first_name = $name_parts[0];
        $last_name = $name_parts[1] ?? '';
        $generated_password = generateToken(8); // Generate a temporary password
        $hashed_password = hashPassword($generated_password);

        $stmt_create_user = $conn->prepare("INSERT INTO users (first_name, last_name, email, phone_number, company, address, city, state, zip_code, password_hash, role, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'customer', 1)");
        $stmt_create_user->bind_param("ssssssssss", $first_name, $last_name, $email, $phone, $company, $address, $city, $state, $zip_code, $hashed_password);
        if (!$stmt_create_user->execute()) {
            throw new Exception("Failed to create user account: " . $stmt_create_user->error);
        }
        $user_id = $conn->insert_id;
    }
    $stmt_check_user->close();

    if (!$user_id) {
        throw new Exception("Could not determine user ID for quote submission.");
    }

    // 3. Prepare data for `quotes` table
    $quote_status = 'pending'; // Default status for new direct submissions
    $main_location = $address . ', ' . $city . ', ' . $state . ' ' . $zip_code;

    // Initialize all specific columns for `quotes` table to NULL/defaults
    $delivery_date = null;
    $pickup_date = null; // This column now exists in quotes table
    $removal_date = null;
    $delivery_time = null;
    $removal_time = null;
    $live_load_needed = 0; // Default to 0
    $is_urgent = 0; // Default to 0
    $driver_instructions = null;
    $customer_type = 'Residential'; // Default, update if explicit selection is added

    // Capture all contact details and general form data to be stored in quote_details JSON
    $full_form_data = [
        'name' => $name,
        'email' => $email,
        'phone' => $phone,
        'company' => $company,
        'address' => $address,
        'city' => $city,
        'state' => $state,
        'zip_code' => $zip_code,
        'service_type' => $service_type
    ];

    if ($service_type === 'equipment_rental') {
        $equipment_details = json_decode($_POST['equipment_details'] ?? '[]', true);
        if (empty($equipment_details)) {
            throw new Exception('Equipment details are required for equipment rental quotes.');
        }
        $full_form_data['equipment_details'] = $equipment_details;
        
        // Extract specific fields for direct columns in 'quotes' table from the first item
        if (!empty($equipment_details[0]['details']['deliveryDate'])) {
            $delivery_date = $equipment_details[0]['details']['deliveryDate'];
        }
        if (!empty($equipment_details[0]['details']['pickupDate'])) {
            $pickup_date = $equipment_details[0]['details']['pickupDate'];
        }
        // Assuming other fields like live_load_needed, is_urgent, driver_instructions
        // would be part of a general section or could be extracted from comments if desired.
        // For now, they use defaults or are set by specific form inputs if available.

    } elseif ($service_type === 'junk_removal') {
        $junk_details = json_decode($_POST['junk_details'] ?? '[]', true);
        if (empty($junk_details) || empty($junk_details['junk_items'])) {
            throw new Exception('Junk items are required for junk removal quotes.');
        }
        $full_form_data['junk_details'] = $junk_details;

        $removal_date = $junk_details['preferred_date'] ?? null;
        $removal_time = $junk_details['preferred_time'] ?? null;
        $driver_instructions = $junk_details['additional_comment'] ?? null; // Map to driver_instructions

        // Assuming live_load_needed and is_urgent could be extracted from junk_details
        // if they are part of the junk removal form. For now, they use defaults.

    } else {
        throw new Exception('Invalid service type provided.');
    }

    $full_quote_details_json = json_encode($full_form_data);

    // Insert into 'quotes' table, now including all new columns
    $stmt_insert_quote = $conn->prepare("INSERT INTO quotes (user_id, service_type, status, customer_type, location, delivery_date, pickup_date, removal_date, delivery_time, removal_time, live_load_needed, is_urgent, driver_instructions, quote_details) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt_insert_quote->bind_param("isssssssssiiss", $user_id, $service_type, $quote_status, $customer_type, $main_location, $delivery_date, $pickup_date, $removal_date, $delivery_time, $removal_time, $live_load_needed, $is_urgent, $driver_instructions, $full_quote_details_json);

    if (!$stmt_insert_quote->execute()) {
        throw new Exception("Failed to save quote request: " . $stmt_insert_quote->error);
    }
    $quote_id = $conn->insert_id;
    $stmt_insert_quote->close();

    // 4. Insert Service-Specific Details into their respective tables
    if ($service_type === 'equipment_rental') {
        $stmt_insert_eq = $conn->prepare("INSERT INTO quote_equipment_details (quote_id, equipment_name, quantity, duration_days, specific_needs, estimated_weight_tons) VALUES (?, ?, ?, ?, ?, ?)");
        foreach ($full_form_data['equipment_details'] as $item) {
            $equipment_name = $item['name'];
            $quantity = $item['quantity'];
            $duration_days = $item['details']['duration'] ?? null;
            $estimated_weight_tons = $item['details']['weight'] ?? null; // From dumpster/container fields

            // Reconstruct specific needs from details for consistency with specific_needs column
            $specific_needs = [];
            if (!empty($item['details']['size'])) $specific_needs[] = "Size: " . $item['details']['size'];
            if (!empty($item['details']['wasteType'])) $specific_needs[] = "Waste Type: " . $item['details']['wasteType'];
            if (!empty($item['details']['projectEventType'])) $specific_needs[] = "Event Type: " . $item['details']['projectEventType'];
            if (!empty($item['details']['numAttendeesWorkers'])) $specific_needs[] = "Attendees/Workers: " . $item['details']['numAttendeesWorkers'];
            if (!empty($item['details']['unitType'])) $specific_needs[] = "Unit Type: " . $item['details']['unitType'];
            if (!empty($item['details']['additionalServices'])) $specific_needs[] = "Add. Services: " . implode(', ', $item['details']['additionalServices']);
            if (!empty($item['details']['purpose'])) $specific_needs[] = "Purpose: " . $item['details']['purpose'];
            if (!empty($item['details']['accessRequirements'])) $specific_needs[] = "Access: " . $item['details']['accessRequirements'];
            if (!empty($item['details']['comments'])) $specific_needs[] = "Comments: " . $item['details']['comments'];
            // Delivery/Pickup dates are now in main quotes table for primary item,
            // but can also be part of specific_needs here if needed for individual equipment details.
            
            $final_specific_needs = implode('; ', $specific_needs);

            $stmt_insert_eq->bind_param("isdssi", $quote_id, $equipment_name, $quantity, $duration_days, $final_specific_needs, $estimated_weight_tons);
            if (!$stmt_insert_eq->execute()) {
                throw new Exception("Failed to insert equipment detail: " . $stmt_insert_eq->error);
            }
        }
        $stmt_insert_eq->close();

    } elseif ($service_type === 'junk_removal') {
        $stmt_insert_junk = $conn->prepare("INSERT INTO junk_removal_details (quote_id, junk_items_json, additional_comment) VALUES (?, ?, ?)");
        $junk_items_json = json_encode($full_form_data['junk_details']['junk_items']);
        $additional_comment = $full_form_data['junk_details']['additional_comment'] ?? null;
        $stmt_insert_junk->bind_param("iss", $quote_id, $junk_items_json, $additional_comment);
        if (!$stmt_insert_junk->execute()) {
            throw new Exception("Failed to insert junk removal details: " . $stmt_insert_junk->error);
        }
        $stmt_insert_junk->close();
    }

    // 5. Send Confirmation Email to Customer
    $customer_name = $name;
    $customer_email = $email;
    $login_link = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://{$_SERVER['HTTP_HOST']}/customer/login.php";
    $dashboard_link = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://{$_SERVER['HTTP_HOST']}/customer/dashboard.php#quotes?quote_id={$quote_id}";
    $company_name = getSystemSetting('company_name') ?? 'Catdump';

    // Email for quote request submission
    $email_subject = "Your {$company_name} Quote Request #Q{$quote_id} Submitted!";
    $email_body = "<p>Dear {$customer_name},</p>";
    $email_body .= "<p>Thank you for submitting your quote request for {$service_type} services. Your Request ID is #Q<strong>{$quote_id}</strong>.</p>";
    $email_body .= "<p>Our team is now reviewing your request and will provide you with the best pricing within 60 minutes during our working hours. We'll notify you once your quote is ready!</p>";
    $email_body .= "<p>You can view the status of your request on your dashboard:</p>";
    $email_body .= "<p style='text-align: center; margin-top: 20px;'><a href='{$dashboard_link}' style='display: inline-block; padding: 10px 20px; background-color: #1a73e8; color: white; text-decoration: none; border-radius: 5px;'>View My Quotes</a></p>";
    
    // Add temporary password info if new user was created
    if (!$user_exists && $generated_password) {
        $email_body .= "<p>We've also created an account for you. Your login details:</p>";
        $email_body .= "<p><strong>Email:</strong> {$customer_email}<br><strong>Temporary Password:</strong> {$generated_password}</p>";
        $email_body .= "<p>Please log in and change your password for security:</p>";
        $email_body .= "<p style='text-align: center; margin-top: 10px;'><a href='{$login_link}' style='display: inline-block; padding: 10px 20px; background-color: #34a853; color: white; text-decoration: none; border-radius: 5px;'>Login Now</a></p>";
    }
    
    $email_body .= "<p style='margin-top: 30px;'>Thank you for choosing {$company_name}!</p>";

    sendEmail($customer_email, $email_subject, $email_body);

    // 6. Notify Admin about the new quote request
    $admin_email_recipient = getSystemSetting('admin_email') ?? 'admin@example.com';
    $admin_subject = "New Quote Request #Q{$quote_id} for {$service_type} from {$name}";
    $admin_body = "A new quote request has been submitted:<br>";
    $admin_body .= "Request ID: #Q{$quote_id}<br>";
    $admin_body .= "Service Type: {$service_type}<br>";
    $admin_body .= "Customer: {$name} ({$email})<br>";
    if(!empty($company)) {
        $admin_body .= "Company: {$company}<br>";
    }
    $admin_body .= "Location: {$main_location}<br>";
    if ($delivery_date) $admin_body .= "Delivery Date: {$delivery_date}<br>";
    if ($pickup_date) $admin_body .= "Pickup Date: {$pickup_date}<br>";
    if ($removal_date) $admin_body .= "Removal Date: {$removal_date}<br>";
    if ($delivery_time) $admin_body .= "Delivery Time: {$delivery_time}<br>";
    if ($removal_time) $admin_body .= "Removal Time: {$removal_time}<br>";
    if ($live_load_needed) $admin_body .= "Live Load Needed: Yes<br>";
    if ($is_urgent) $admin_body .= "Urgent: Yes<br>";
    if ($driver_instructions) $admin_body .= "Driver Instructions: {$driver_instructions}<br>";

    $admin_body .= "View details in admin panel: " . (isset($_SERVER['HTTPS']) ? "https" : "http") . "://{$_SERVER['HTTP_HOST']}/admin/index.php#quotes?quote_id={$quote_id}";
    sendEmail($admin_email_recipient, $admin_subject, $admin_body);

    // 7. Create notification for admin dashboard
    $admin_id = 1; // Assuming Admin user ID is 1
    $notification_message = "New quote request #Q{$quote_id} for {$service_type} from {$name} is pending review.";
    $notification_link = "quotes?quote_id={$quote_id}";
    $stmt_admin_notify = $conn->prepare("INSERT INTO notifications (user_id, type, message, link) VALUES (?, 'new_quote', ?, ?)");
    $stmt_admin_notify->bind_param("iss", $admin_id, $notification_message, $notification_link);
    $stmt_admin_notify->execute();
    $stmt_admin_notify->close();


    $conn->commit();

    echo json_encode(['success' => true, 'message' => 'Your quote request has been submitted successfully!']);

} catch (Exception $e) {
    $conn->rollback();
    error_log("Failed to submit quote: " . $e->getMessage());
    http_response_code(400); // Use 400 for client-side validation errors
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
} finally {
    if ($conn) { // Check if connection exists before closing
        $conn->close();
    }
}
?>