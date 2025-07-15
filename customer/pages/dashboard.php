<?php
// customer/pages/dashboard.php

// Ensure session is started and user is logged in
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/session.php'; // For has_role and user_id

if (!is_logged_in()) {
    // This page is loaded via AJAX, so a redirect here might not work as expected
    // Instead, return an error message or signal for client-side redirect.
    echo '<div class="text-red-500 text-center p-8">You must be logged in to view this content.</div>';
    exit;
}

$user_id = $_SESSION['user_id'];

// Initialize variables for user data and statistics
$user_full_name = 'N/A';
$user_username = 'N/A'; // Assuming username might be email or a separate field
$user_email = 'N/A';
$user_phone = 'N/A';
$user_address = 'N/A';
$user_city = 'N/A';
$user_state = 'N/A';
$user_zip = 'N/A';

$active_bookings_count = 0;
$pending_quotes_count = 0;
$total_invoices_count = 0;
$paid_invoices_count = 0;
$unpaid_invoices_count = 0;
$partially_paid_invoices_count = 0;
$pending_quotes_for_animation = []; // To hold quotes that are pending for price


// Fetch User Account Information
$stmt_user = $conn->prepare("SELECT first_name, last_name, email, phone_number, address, city, state, zip_code FROM users WHERE id = ?");
$stmt_user->bind_param("i", $user_id);
$stmt_user->execute();
$result_user = $stmt_user->get_result();
if ($user_data = $result_user->fetch_assoc()) {
    $user_full_name = htmlspecialchars(($user_data['first_name'] ?? '') . ' ' . ($user_data['last_name'] ?? ''));
    $user_username = htmlspecialchars($user_data['email'] ?? '');
    $user_email = htmlspecialchars($user_data['email'] ?? '');
    $user_phone = htmlspecialchars($user_data['phone_number'] ?? '');
    $user_address = htmlspecialchars($user_data['address'] ?? '');
    $user_city = htmlspecialchars($user_data['city'] ?? '');
    $user_state = htmlspecialchars($user_data['state'] ?? '');
    $user_zip = htmlspecialchars($user_data['zip_code'] ?? '');
} else {
    // This case should ideally not be hit if user is logged in
    $user_full_name = 'N/A';
    $user_username = 'N/A';
    $user_email = 'N/A';
    $user_phone = 'N/A';
    $user_address = 'N/A';
    $user_city = 'N/A';
    $user_state = 'N/A';
    $user_zip = 'N/A';
}
$stmt_user->close();

// Fetch Quick Statistics: Bookings and Quotes
// Active Bookings (assuming 'delivered', 'in_use', 'awaiting_pickup' as active for customer view)
$stmt_active_bookings = $conn->prepare("SELECT COUNT(*) AS count FROM bookings WHERE user_id = ? AND status IN ('delivered', 'in_use', 'awaiting_pickup')");
$stmt_active_bookings->bind_param("i", $user_id);
$stmt_active_bookings->execute();
$result_active_bookings = $stmt_active_bookings->get_result();
$active_bookings_count = $result_active_bookings->fetch_assoc()['count'];
$stmt_active_bookings->close();

// Pending Quotes (quotes with status 'pending' where admin hasn't added a price yet)
$stmt_pending_quotes = $conn->prepare("SELECT id, service_type, created_at, quote_details, location FROM quotes WHERE user_id = ? AND status = 'pending'");
$stmt_pending_quotes->bind_param("i", $user_id);
$stmt_pending_quotes->execute();
$result_pending_quotes = $stmt_pending_quotes->get_result();
$pending_quotes_count = $result_pending_quotes->num_rows;

// Store pending quotes for display on dashboard
while ($row = $result_pending_quotes->fetch_assoc()) {
    // FIX: Add null coalescing for json_decode to prevent deprecation warnings
    $quote_details = json_decode($row['quote_details'] ?? '{}', true);
    $item_desc = 'N/A';
    if ($row['service_type'] == 'equipment_rental' && isset($quote_details['equipment_types'])) {
        $item_desc = implode(', ', $quote_details['equipment_types']);
    } elseif ($row['service_type'] == 'junk_removal' && isset($quote_details['junkRemovalDetails']['junkItems'])) {
        $item_desc_array = [];
        foreach($quote_details['junkRemovalDetails']['junkItems'] as $item) {
            $item_desc_array[] = $item['itemType'] . (isset($item['quantity']) ? ' (Qty: ' . $item['quantity'] . ')' : '');
        }
        $item_desc = implode(', ', $item_desc_array);
    }

    $pending_quotes_for_animation[] = [
        'id' => $row['id'],
        'service_type' => htmlspecialchars(str_replace('_', ' ', $row['service_type'])),
        'created_at' => (new DateTime($row['created_at']))->format('M d, Y H:i A'),
        'item_description' => htmlspecialchars($item_desc),
        'location' => htmlspecialchars($row['location'] ?? 'N/A')
    ];
}
$stmt_pending_quotes->close();

// Fetch Invoice Statistics
$stmt_total_invoices = $conn->prepare("SELECT COUNT(*) AS count FROM invoices WHERE user_id = ?");
$stmt_total_invoices->bind_param("i", $user_id);
$stmt_total_invoices->execute();
$total_invoices_count = $stmt_total_invoices->get_result()->fetch_assoc()['count'];
$stmt_total_invoices->close();

$stmt_paid_invoices = $conn->prepare("SELECT COUNT(*) AS count FROM invoices WHERE user_id = ? AND status = 'paid'");
$stmt_paid_invoices->bind_param("i", $user_id);
$stmt_paid_invoices->execute();
$paid_invoices_count = $stmt_paid_invoices->get_result()->fetch_assoc()['count'];
$stmt_paid_invoices->close();

$stmt_unpaid_invoices = $conn->prepare("SELECT COUNT(*) AS count FROM invoices WHERE user_id = ? AND status = 'pending'");
$stmt_unpaid_invoices->bind_param("i", $user_id);
$stmt_unpaid_invoices->execute();
$unpaid_invoices_count = $stmt_unpaid_invoices->get_result()->fetch_assoc()['count'];
$stmt_unpaid_invoices->close();

$stmt_partially_paid_invoices = $conn->prepare("SELECT COUNT(*) AS count FROM invoices WHERE user_id = ? AND status = 'partially_paid'");
$stmt_partially_paid_invoices->bind_param("i", $user_id);
$stmt_partially_paid_invoices->execute();
$partially_paid_invoices_count = $stmt_partially_paid_invoices->get_result()->fetch_assoc()['count'];
$stmt_partially_paid_invoices->close();

$conn->close();
?>

<h1 class="text-3xl font-bold text-gray-800 mb-8">Customer Dashboard</h1>

<div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
    <div class="bg-white p-6 rounded-lg shadow-md border border-blue-200">
        <h2 class="text-xl font-semibold text-gray-700 mb-4 flex items-center"><i class="fas fa-user-circle mr-2 text-blue-600"></i>Account Information</h2>
        <div class="space-y-3">
            <p class="text-gray-600"><span class="font-medium">Name:</span> <?php echo $user_full_name; ?></p>
            <p class="text-gray-600"><span class="font-medium">Username:</span> <?php echo $user_username; ?></p>
            <p class="text-gray-600"><span class="font-medium">Email:</span> <?php echo $user_email; ?></p>
            <p class="text-gray-600"><span class="font-medium">Phone:</span> <?php echo $user_phone; ?></p>
            <p class="text-gray-600"><span class="font-medium">Address:</span> <?php echo $user_address; ?><?php echo !empty($user_city) ? ', ' . $user_city : ''; ?><?php echo !empty($user_state) ? ', ' . $user_state : ''; ?><?php echo !empty($user_zip) ? ' ' . $user_zip : ''; ?></p>
        </div>
        <div class="mt-6">
            <button class="py-2 px-5 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors duration-200 shadow-md" onclick="window.loadCustomerSection('edit-profile');">
                <i class="fas fa-edit mr-2"></i>Edit Profile
            </button>
        </div>
    </div>

    <div class="bg-white p-6 rounded-lg shadow-md border border-blue-200">
        <h2 class="text-xl font-semibold text-gray-700 mb-4 flex items-center"><i class="fas fa-chart-line mr-2 text-green-600"></i>Quick Statistics</h2>
        <div class="grid grid-cols-2 gap-4 text-center">
            <div class="p-4 bg-blue-50 rounded-lg shadow-sm">
                <p class="text-3xl font-bold text-blue-600"><?php echo $active_bookings_count; ?></p>
                <p class="text-gray-600 text-sm">Active Bookings</p>
            </div>
            <div class="p-4 bg-yellow-50 rounded-lg shadow-sm">
                <p class="text-3xl font-bold text-yellow-600"><?php echo $pending_quotes_count; ?></p>
                <p class="text-gray-600 text-sm">Pending Quotes</p>
            </div>
            <div class="p-4 bg-purple-50 rounded-lg shadow-sm">
                <p class="text-3xl font-bold text-purple-600"><?php echo $total_invoices_count; ?></p>
                <p class="text-gray-600 text-sm">Total Invoices</p>
            </div>
            <div class="p-4 bg-green-50 rounded-lg shadow-sm">
                <p class="text-3xl font-bold text-green-600"><?php echo $paid_invoices_count; ?></p>
                <p class="text-gray-600 text-sm">Paid Invoices</p>
            </div>
            <div class="p-4 bg-red-50 rounded-lg shadow-sm">
                <p class="text-3xl font-bold text-red-600"><?php echo $unpaid_invoices_count; ?></p>
                <p class="text-gray-600 text-sm">Unpaid Invoices</p>
            </div>
            <div class="p-4 bg-orange-50 rounded-lg shadow-sm">
                <p class="text-3xl font-bold text-orange-600"><?php echo $partially_paid_invoices_count; ?></p>
                <p class="text-gray-600 text-sm">Partially Paid</p>
            </div>
        </div>
    </div>
</div>

<div class="bg-white p-6 rounded-lg shadow-md border border-blue-200 mb-8">
    <h2 class="text-xl font-semibold text-gray-700 mb-4 flex items-center"><i class="fas fa-hourglass-half mr-2 text-orange-600"></i>Pending Quotes for Pricing</h2>
    <?php if (empty($pending_quotes_for_animation)): ?>
        <p class="text-gray-600">You currently have no pending quotes awaiting pricing from our team. Create a new service request!</p>
    <?php else: ?>
        <div class="space-y-4">
            <?php foreach ($pending_quotes_for_animation as $quote): ?>
                <div class="flex items-center justify-between p-4 bg-yellow-50 rounded-lg shadow-sm border border-yellow-200">
                    <div>
                        <p class="font-medium text-gray-700">Request ID: <span class="font-semibold text-yellow-800">#Q<?php echo $quote['id']; ?></span></p>
                        <p class="text-sm text-gray-600">Service: <?php echo $quote['service_type']; ?> (<?php echo $quote['item_description']; ?>)</p>
                        <p class="text-xs text-gray-500">Submitted: <?php echo $quote['created_at']; ?> at <?php echo $quote['location']; ?></p>
                    </div>
                    <div class="flex items-center space-x-2">
                        <span class="text-orange-600 font-semibold text-sm">
                            <i class="fas fa-spinner fa-spin mr-1"></i> Our team is working on your best price!
                        </span>
                        <button class="px-3 py-1 bg-yellow-300 text-yellow-800 rounded-lg hover:bg-yellow-400 text-xs" onclick="window.loadCustomerSection('quotes', {quote_id: <?php echo $quote['id']; ?>});">
    View Request
</button>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>


<div class="bg-white p-6 rounded-lg shadow-md border border-blue-200">
    <h2 class="text-xl font-semibold text-gray-700 mb-4 flex items-center"><i class="fas fa-robot mr-2 text-teal-600"></i>Equipment & Junk Removal via AI Chat</h2>
    <p class="text-gray-600 mb-4">Start a conversation with our AI assistant to book new equipment or request junk removal services quickly.</p>
    <div class="flex flex-wrap gap-4">
        <button class="py-2 px-5 bg-teal-600 text-white rounded-lg hover:bg-teal-700 transition-colors duration-200 shadow-md" onclick="showAIChat('create-booking');">
            <i class="fas fa-calendar-plus mr-2"></i>New Equipment Booking
        </button>
        <button class="py-2 px-5 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition-colors duration-200 shadow-md" onclick="showAIChat('junk-removal-service');">
            <i class="fas fa-dumpster-fire mr-2"></i>Request Junk Removal
        </button>
    </div>
</div>