<?php
// admin/pages/bookings.php

// --- Setup & Includes ---
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/session.php';

if (!is_logged_in() || !has_role('admin')) {
    echo '<div class="text-red-500 text-center p-8">Unauthorized access.</div>';
    exit;
}

// --- Data Fetching Variables ---
$bookings = [];
$booking_detail_view_data = null;
$vendors = [];

// --- Pagination & Filter Variables ---
$items_per_page_options = [10, 25, 50, 100];
$items_per_page = filter_input(INPUT_GET, 'per_page', FILTER_VALIDATE_INT);
if (!in_array($items_per_page, $items_per_page_options)) {
    $items_per_page = 25; // Default items per page
}

$current_page = filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT);
if (!$current_page || $current_page < 1) {
    $current_page = 1;
}
$offset = ($current_page - 1) * $items_per_page;

$filter_status = $_GET['status'] ?? 'all'; // Default filter status
$search_query = trim($_GET['search'] ?? ''); // Search query

$requested_booking_id = filter_input(INPUT_GET, 'booking_id', FILTER_VALIDATE_INT);

// Set a default timezone to ensure consistent date calculations
date_default_timezone_set('UTC');

// Fetch all vendors for the "Assign Vendor" dropdown
$stmt_vendors = $conn->prepare("SELECT id, name FROM vendors WHERE is_active = TRUE ORDER BY name ASC");
$stmt_vendors->execute();
$result_vendors = $stmt_vendors->get_result();
while ($row = $result_vendors->fetch_assoc()) {
    $vendors[] = $row;
}
$stmt_vendors->close();

// Fetch a single booking's details if an ID is provided
if ($requested_booking_id) {
    $stmt_detail = $conn->prepare("
        SELECT
            b.id, b.booking_number, b.service_type, b.status, b.start_date, b.end_date,
            b.delivery_location, b.pickup_location, b.delivery_instructions, b.pickup_instructions,
            b.total_price, b.created_at, b.live_load_requested, b.is_urgent,
            b.equipment_details, b.junk_details, b.vendor_id, b.pickup_date, b.pickup_time,
            u.id as user_id, u.first_name, u.last_name, u.email, u.phone_number, u.address, u.city, u.state, u.zip_code,
            inv.invoice_number, inv.amount AS invoice_amount, inv.status AS invoice_status,
            q.id AS quote_id, q.daily_rate, q.swap_charge AS quote_swap_charge, q.relocation_charge AS quote_relocation_charge,
            q.is_swap_included, q.is_relocation_included,
            v.name AS vendor_name, v.email AS vendor_email, v.phone_number AS vendor_phone,
            ext.id AS extension_request_id, ext.requested_days AS extension_requested_days, ext.status AS extension_status
        FROM bookings b
        JOIN users u ON b.user_id = u.id
        LEFT JOIN invoices inv ON b.invoice_id = inv.id
        LEFT JOIN quotes q ON inv.quote_id = q.id
        LEFT JOIN vendors v ON b.vendor_id = v.id
        LEFT JOIN booking_extension_requests ext ON b.id = ext.booking_id AND ext.status = 'pending'
        WHERE b.id = ?
    ");
    $stmt_detail->bind_param("i", $requested_booking_id);
    $stmt_detail->execute();
    $result_detail = $stmt_detail->get_result();
    if ($result_detail->num_rows > 0) {
        $booking_detail_view_data = $result_detail->fetch_assoc();
        $booking_detail_view_data['equipment_details'] = json_decode($booking_detail_view_data['equipment_details'] ?? '[]', true);
        $booking_detail_view_data['junk_details'] = json_decode($booking_detail_view_data['junk_details'] ?? '{}', true);

        // Fetch additional charges
        $charge_stmt = $conn->prepare("SELECT charge_type, amount, description, created_at, invoice_id FROM booking_charges WHERE booking_id = ?");
        $charge_stmt->bind_param("i", $booking_detail_view_data['id']);
        $charge_stmt->execute();
        $charge_result = $charge_stmt->get_result();
        $booking_detail_view_data['additional_charges'] = [];
        while($charge_row = $charge_result->fetch_assoc()){
            $booking_detail_view_data['additional_charges'][] = $charge_row;
        }
        $charge_stmt->close();

        // Fetch status history for the timeline
        $history_stmt = $conn->prepare("SELECT status, status_time, notes FROM booking_status_history WHERE booking_id = ? ORDER BY status_time ASC, id ASC");
        $history_stmt->bind_param("i", $booking_detail_view_data['id']);
        $history_stmt->execute();
        $history_result = $history_stmt->get_result();
        $booking_detail_view_data['status_history'] = [];
        while ($history_row = $history_result->fetch_assoc()) {
            $booking_detail_view_data['status_history'][] = $history_row;
        }
        $history_stmt->close();


        // Safely calculate remaining days, handling potential NULL end_date
        if ($booking_detail_view_data && 
            in_array($booking_detail_view_data['status'], ['delivered', 'in_use', 'awaiting_pickup']) && 
            !empty($booking_detail_view_data['end_date'])) {
             try {
                $endDate = new DateTime($booking_detail_view_data['end_date']);
                $today = new DateTime('today');
                if ($endDate >= $today) {
                    $interval = $today->diff($endDate);
                    $booking_detail_view_data['remaining_days'] = $interval->days;
                } else {
                    $booking_detail_view_data['remaining_days'] = 0;
                }
            } catch (Exception $e) {
                error_log("DateTime conversion error for booking ID {$requested_booking_id}: {$e->getMessage()}");
                $booking_detail_view_data['remaining_days'] = 'N/A';
            }
        } else {
            $booking_detail_view_data['remaining_days'] = 'N/A';
        }
    }
    $stmt_detail->close();
} else {
    // --- Fetch all bookings for the list view with Filters, Search, and Pagination ---
    $base_query = "
        FROM bookings b
        JOIN users u ON b.user_id = u.id
        LEFT JOIN vendors v ON b.vendor_id = v.id
    ";

    $where_clauses = [];
    $params = [];
    $types = "";

    // Status Filter
    if ($filter_status !== 'all') {
        $where_clauses[] = "b.status = ?";
        $params[] = $filter_status;
        $types .= "s";
    }

    // Search Query
    if (!empty($search_query)) {
        $search_term = '%' . $search_query . '%';
        $where_clauses[] = "(b.booking_number LIKE ? OR u.first_name LIKE ? OR u.last_name LIKE ? OR b.delivery_location LIKE ?)";
        $params[] = $search_term;
        $params[] = $search_term;
        $params[] = $search_term;
        $params[] = $search_term;
        $types .= "ssss";
    }

    $where_sql = '';
    if (!empty($where_clauses)) {
        $where_sql = " WHERE " . implode(" AND ", $where_clauses);
    }

    // Get total count for pagination
    $stmt_count = $conn->prepare("SELECT COUNT(*) " . $base_query . $where_sql);
    if (!empty($params)) {
        $stmt_count->bind_param($types, ...$params);
    }
    $stmt_count->execute();
    $total_bookings_count = $stmt_count->get_result()->fetch_assoc()['COUNT(*)'];
    $stmt_count->close();

    $total_pages = ceil($total_bookings_count / $items_per_page);

    // Main query for bookings list
    $list_query = "
        SELECT
            b.id, b.booking_number, b.service_type, b.status, b.start_date,
            u.first_name, u.last_name, v.name AS vendor_name,
            (SELECT COUNT(*) FROM booking_extension_requests ext WHERE ext.booking_id = b.id AND ext.status = 'pending') AS pending_extensions
    " . $base_query . $where_sql . "
    ORDER BY pending_extensions DESC, b.created_at DESC
    LIMIT ? OFFSET ?";

    $params[] = $items_per_page;
    $params[] = $offset;
    $types .= "ii"; // Add types for LIMIT and OFFSET

    $stmt_list = $conn->prepare($list_query);
    if (!empty($params)) {
        $stmt_list->bind_param($types, ...$params);
    }
    $stmt_list->execute();
    $result_list = $stmt_list->get_result();
    while ($row = $result_list->fetch_assoc()) {
        $bookings[] = $row;
    }
    $stmt_list->close();
}

$conn->close();

// --- Helper Functions ---
function getAdminStatusBadgeClass($status) {
    switch ($status) {
        case 'pending': return 'bg-yellow-100 text-yellow-800';
        case 'scheduled': return 'bg-blue-100 text-blue-800';
        case 'assigned': return 'bg-indigo-100 text-indigo-800';
        case 'out_for_delivery': return 'bg-purple-100 text-purple-800';
        case 'delivered': return 'bg-green-100 text-green-800';
        case 'in_use': return 'bg-teal-100 text-teal-800';
        case 'awaiting_pickup': return 'bg-pink-100 text-pink-800';
        case 'completed': return 'bg-gray-100 text-gray-800';
        case 'cancelled': return 'bg-red-100 text-red-800';
        case 'relocation_requested': return 'bg-orange-100 text-orange-800';
        case 'swap_requested': return 'bg-fuchsia-100 text-fuchsia-800';
        case 'relocated': return 'bg-lime-100 text-lime-800';
        case 'swapped': return 'bg-emerald-100 text-emerald-800';
        case 'extended': return 'bg-cyan-100 text-cyan-800';
        default: return 'bg-gray-100 text-gray-700';
    }
}
function getTimelineIconClass($status) {
     switch ($status) {
        case 'pending': case 'scheduled': return 'fa-calendar-alt';
        case 'assigned': return 'fa-user-check';
        case 'out_for_delivery': return 'fa-truck';
        case 'delivered': return 'fa-box-open';
        case 'in_use': return 'fa-tools';
        case 'awaiting_pickup': return 'fa-clock';
        case 'completed': return 'fa-check-circle';
        case 'cancelled': return 'fa-times-circle';
        case 'relocation_requested': return 'fa-map-marker-alt';
        case 'swap_requested': return 'fa-exchange-alt';
        case 'relocated': return 'fa-truck-moving';
        case 'swapped': return 'fa-sync-alt';
        case 'extended': return 'fa-calendar-plus';
        default: return 'fa-info-circle';
    }
}
?>

<h1 class="text-3xl font-bold text-gray-800 mb-8">Booking Management</h1>

<div class="bg-white p-6 rounded-lg shadow-md border border-blue-200 <?php echo $booking_detail_view_data ? 'hidden' : ''; ?>" id="bookings-list-section">
    <div class="flex justify-between items-center mb-4">
        <h2 class="text-xl font-semibold text-gray-700"><i class="fas fa-book-open mr-2 text-blue-600"></i>All System Bookings</h2>
         <button id="bulk-delete-bookings-btn" class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors duration-200 shadow-md hidden">
            <i class="fas fa-trash-alt mr-2"></i>Delete Selected
        </button>
    </div>

    <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
        <div class="flex items-center gap-2">
            <label for="status-filter" class="text-sm font-medium text-gray-700">Status:</label>
            <select id="status-filter" onchange="applyFilters()"
                    class="p-2 border border-gray-300 rounded-md text-sm">
                <option value="all" <?php echo $filter_status === 'all' ? 'selected' : ''; ?>>All</option>
                <option value="pending" <?php echo $filter_status === 'pending' ? 'selected' : ''; ?>>Pending</option>
                <option value="scheduled" <?php echo $filter_status === 'scheduled' ? 'selected' : ''; ?>>Scheduled</option>
                <option value="assigned" <?php echo $filter_status === 'assigned' ? 'selected' : ''; ?>>Assigned</option>
                <option value="out_for_delivery" <?php echo $filter_status === 'out_for_delivery' ? 'selected' : ''; ?>>Out for Delivery</option>
                <option value="delivered" <?php echo $filter_status === 'delivered' ? 'selected' : ''; ?>>Delivered</option>
                <option value="in_use" <?php echo $filter_status === 'in_use' ? 'selected' : ''; ?>>In Use</option>
                <option value="awaiting_pickup" <?php echo $filter_status === 'awaiting_pickup' ? 'selected' : ''; ?>>Awaiting Pickup</option>
                <option value="completed" <?php echo $filter_status === 'completed' ? 'selected' : ''; ?>>Completed</option>
                <option value="cancelled" <?php echo $filter_status === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                <option value="relocation_requested" <?php echo $filter_status === 'relocation_requested' ? 'selected' : ''; ?>>Relocation Requested</option>
                <option value="swap_requested" <?php echo $filter_status === 'swap_requested' ? 'selected' : ''; ?>>Swap Requested</option>
                <option value="relocated" <?php echo $filter_status === 'relocated' ? 'selected' : ''; ?>>Relocated</option>
                <option value="swapped" <?php echo $filter_status === 'swapped' ? 'selected' : ''; ?>>Swapped</option>
                <option value="extended" <?php echo $filter_status === 'extended' ? 'selected' : ''; ?>>Extended</option>
            </select>
        </div>

        <div class="flex-grow max-w-sm">
            <input type="text" id="search-input" placeholder="Search by booking #, email, address..."
                   class="p-2 border border-gray-300 rounded-md w-full text-sm"
                   value="<?php echo htmlspecialchars($search_query); ?>"
                   onkeydown="if(event.key === 'Enter') applyFilters()">
        </div>
        <button onclick="applyFilters()" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors duration-200 shadow-md text-sm">
            Search
        </button>
    </div>

    <?php if (empty($bookings)): ?>
        <p class="text-gray-600 text-center p-4">No bookings found for the selected filters or search query.</p>
    <?php else: ?>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-blue-50">
                    <tr>
                        <th class="px-6 py-3 text-left">
                            <input type="checkbox" id="select-all-bookings" class="h-4 w-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Booking ID</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Customer</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Service Type</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Vendor</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Date</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Status</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php foreach ($bookings as $booking): ?>
                        <tr class="<?php echo $booking['pending_extensions'] > 0 ? 'bg-yellow-50' : '' ?>">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <input type="checkbox" class="booking-checkbox h-4 w-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500" value="<?php echo htmlspecialchars($booking['id']); ?>">
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                #BK-<?php echo htmlspecialchars($booking['booking_number']); ?>
                                <?php if ($booking['pending_extensions'] > 0): ?>
                                    <span class="ml-2 text-yellow-600" title="Pending Extension Request"><i class="fas fa-hourglass-half"></i></span>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo htmlspecialchars($booking['first_name'] . ' ' . $booking['last_name']); ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo htmlspecialchars(ucwords(str_replace('_', ' ', $booking['service_type']))); ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo htmlspecialchars($booking['vendor_name'] ?? 'N/A'); ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo (new DateTime($booking['start_date']))->format('Y-m-d'); ?></td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo getAdminStatusBadgeClass($booking['status']); ?>">
                                    <?php echo htmlspecialchars(strtoupper(str_replace('_', ' ', $booking['status']))); ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <button class="text-blue-600 hover:text-blue-900 view-booking-details-btn" data-id="<?php echo htmlspecialchars($booking['id']); ?>">View</button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <nav class="mt-4 flex items-center justify-between">
            <div class="flex-1 flex justify-between sm:hidden">
                <button onclick="loadAdminSection('bookings', {page: <?php echo max(1, $current_page - 1); ?>, per_page: <?php echo $items_per_page; ?>, status: '<?php echo $filter_status; ?>', search: '<?php echo htmlspecialchars($search_query); ?>'})"
                       class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                    Previous
                </button>
                <button onclick="loadAdminSection('bookings', {page: <?php echo min($total_pages, $current_page + 1); ?>, per_page: <?php echo $items_per_page; ?>, status: '<?php echo $filter_status; ?>', search: '<?php echo htmlspecialchars($search_query); ?>'})"
                       class="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                    Next
                </button>
            </div>
            <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
                <div>
                    <p class="text-sm text-gray-700">
                        Showing <span class="font-medium"><?php echo $offset + 1; ?></span> to
                        <span class="font-medium"><?php echo min($offset + $items_per_page, $total_bookings_count); ?></span> of
                        <span class="font-medium"><?php echo $total_bookings_count; ?></span> results
                    </p>
                </div>
                <div>
                    <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px" aria-label="Pagination">
                        <button onclick="loadAdminSection('bookings', {page: <?php echo max(1, $current_page - 1); ?>, per_page: <?php echo $items_per_page; ?>, status: '<?php echo $filter_status; ?>', search: '<?php echo htmlspecialchars($search_query); ?>'})"
                               class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
                            <span class="sr-only">Previous</span>
                            <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                <path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd" />
                            </svg>
                        </button>
                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                            <button onclick="loadAdminSection('bookings', {page: <?php echo $i; ?>, per_page: <?php echo $items_per_page; ?>, status: '<?php echo $filter_status; ?>', search: '<?php echo htmlspecialchars($search_query); ?>'})"
                                   class="<?php echo $i == $current_page ? 'z-10 bg-blue-50 border-blue-500 text-blue-600' : 'bg-white border-gray-300 text-gray-700 hover:bg-gray-50'; ?> relative inline-flex items-center px-4 py-2 border text-sm font-medium">
                                <?php echo $i; ?>
                            </button>
                        <?php endfor; ?>
                        <button onclick="loadAdminSection('bookings', {page: <?php echo min($total_pages, $current_page + 1); ?>, per_page: <?php echo $items_per_page; ?>, status: '<?php echo $filter_status; ?>', search: '<?php echo htmlspecialchars($search_query); ?>'})"
                               class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
                            <span class="sr-only">Next</span>
                            <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
                            </svg>
                        </button>
                    </nav>
                </div>
            </div>
            <div class="hidden sm:flex items-center gap-2 ml-4">
                <span class="text-sm font-medium text-gray-700">Bookings per page:</span>
                <select onchange="loadAdminSection('bookings', {page: 1, per_page: this.value, status: '<?php echo $filter_status; ?>', search: '<?php echo htmlspecialchars($search_query); ?>'})"
                        class="p-2 border border-gray-300 rounded-md text-sm">
                    <?php foreach ($items_per_page_options as $option): ?>
                        <option value="<?php echo $option; ?>" <?php echo $items_per_page == $option ? 'selected' : ''; ?>>
                            <?php echo $option; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </nav>

    <?php endif; ?>
</div>

<div id="booking-detail-view" class="bg-white p-6 rounded-lg shadow-md border border-blue-200 mt-8 <?php echo $booking_detail_view_data ? '' : 'hidden'; ?>">
    <button class="mb-4 px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300" onclick="hideBookingDetails()">
        <i class="fas fa-arrow-left mr-2"></i>Back to Bookings
    </button>
    <?php if ($booking_detail_view_data): ?>
        <h2 class="text-2xl font-bold text-gray-800 mb-6">Booking #BK-<?php echo htmlspecialchars($booking_detail_view_data['booking_number']); ?> Details</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6 pb-6 border-b border-gray-200">
            <div>
                <p class="text-gray-600"><span class="font-medium">Customer:</span> <?php echo htmlspecialchars($booking_detail_view_data['first_name'] . ' ' . $booking_detail_view_data['last_name']); ?></p>
                <p class="text-gray-600"><span class="font-medium">Customer Email:</span> <?php echo htmlspecialchars($booking_detail_view_data['email']); ?></p>
                <p class="text-gray-600"><span class="font-medium">Customer Phone:</span> <?php echo htmlspecialchars($booking_detail_view_data['phone_number']); ?></p>
                <p class="text-gray-600"><span class="font-medium">Customer Address:</span> <?php echo htmlspecialchars($booking_detail_view_data['address'] . ', ' . $booking_detail_view_data['city'] . ', ' . $booking_detail_view_data['state'] . ' ' . $booking_detail_view_data['zip_code']); ?></p>
                <p class="text-gray-600 mt-2"><a href="#" onclick="loadAdminSection('users', {user_id: '<?php echo $booking_detail_view_data['user_id'] ?? ''; ?>'}); return false;" class="text-blue-600 hover:underline"><i class="fas fa-external-link-alt mr-1"></i>View Customer Profile</a></p>
            </div>
            <div>
                <p class="text-gray-600"><span class="font-medium">Service Type:</span> <?php echo htmlspecialchars(ucwords(str_replace('_', ' ', $booking_detail_view_data['service_type']))); ?></p>
                <p class="text-gray-600"><span class="font-medium">Current Status:</span> <span class="px-2 py-1 rounded-full text-xs font-semibold <?php echo getAdminStatusBadgeClass($booking_detail_view_data['status']); ?>"><?php echo htmlspecialchars(strtoupper(str_replace('_', ' ', $booking_detail_view_data['status']))); ?></span></p>
                <p class="text-gray-600"><span class="font-medium">Start Date:</span> <?php echo htmlspecialchars($booking_detail_view_data['start_date']); ?></p>
                 <p class="text-gray-600"><span class="font-medium">End Date:</span> <?php echo htmlspecialchars($booking_detail_view_data['end_date'] ?? 'N/A'); ?>
                    <?php if (isset($booking_detail_view_data['remaining_days']) && $booking_detail_view_data['remaining_days'] !== 'N/A'): ?>
                        <span class="font-bold <?php echo $booking_detail_view_data['remaining_days'] < 3 ? 'text-red-500' : 'text-green-500'; ?>">
                            (<?php echo $booking_detail_view_data['remaining_days']; ?> days remaining)
                        </span>
                    <?php endif; ?>
                 </p>
                 <p class="text-gray-600"><span class="font-medium">Delivery Location:</span> <?php echo htmlspecialchars($booking_detail_view_data['delivery_location']); ?></p>
                <p class="text-gray-600"><span class="font-medium">Delivery Instructions:</span> <?php echo htmlspecialchars($booking_detail_view_data['delivery_instructions'] ?? 'None'); ?></p>
                <?php if (!empty($booking_detail_view_data['pickup_date'])): ?>
                    <p class="text-gray-600"><span class="font-medium">Scheduled Pickup Date:</span> <?php echo htmlspecialchars($booking_detail_view_data['pickup_date']); ?></p>
                    <p class="text-gray-600"><span class="font-medium">Scheduled Pickup Time:</span> <?php echo htmlspecialchars($booking_detail_view_data['pickup_time']); ?></p>
                    <p class="text-gray-600"><span class="font-medium">Pickup Location:</span> <?php echo htmlspecialchars($booking_detail_view_data['pickup_location'] ?? 'Same as delivery'); ?></p>
                    <p class="text-gray-600"><span class="font-medium">Pickup Instructions:</span> <?php echo htmlspecialchars($booking_detail_view_data['pickup_instructions'] ?? 'None'); ?></p>
                <?php endif; ?>
                 <p class="text-gray-600"><span class="font-medium">Live Load Requested:</span> <?php echo $booking_detail_view_data['live_load_requested'] ? 'Yes' : 'No'; ?></p>
                <p class="text-gray-600"><span class="font-medium">Urgent Request:</span> <?php echo $booking_detail_view_data['is_urgent'] ? 'Yes' : 'No'; ?></p>
            </div>
        </div>

        <h3 class="text-xl font-semibold text-gray-700 mb-4">Service Details</h3>
        <?php if ($booking_detail_view_data['service_type'] === 'equipment_rental'): ?>
            <h4 class="font-semibold mt-4 mb-2 text-gray-800">Equipment Booked:</h4>
            <?php if (!empty($booking_detail_view_data['equipment_details'])): ?>
                <ul class="list-disc list-inside space-y-2 pl-4">
                    <?php foreach ($booking_detail_view_data['equipment_details'] as $item): ?>
                        <li><strong><?php echo htmlspecialchars($item['quantity']); ?>x</strong> <?php echo htmlspecialchars($item['equipment_name']); ?> (<?php echo htmlspecialchars($item['duration_days']); ?> days)</li>
                        <?php if (!empty($item['specific_needs'])): ?>
                            <p class="text-xs text-gray-600 ml-4">- Needs: <?php echo htmlspecialchars($item['specific_needs']); ?></p>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </ul>
            <?php else: ?>
                <p class="text-gray-600">No specific equipment details found for this booking.</p>
            <?php endif; ?>
        <?php elseif ($booking_detail_view_data['service_type'] === 'junk_removal'): ?>
            <h4 class="font-semibold mt-4 mb-2 text-gray-800">Junk Removal Details:</h4>
            <?php if (!empty($booking_detail_view_data['junk_details'])): ?>
                <ul class="list-disc list-inside space-y-2 pl-4">
                    <?php if (!empty($booking_detail_view_data['junk_details']['junkItems'])): ?>
                        <?php foreach ($booking_detail_view_data['junk_details']['junkItems'] as $item): ?>
                            <li><?php echo htmlspecialchars($item['itemType'] ?? 'N/A'); ?> (Qty: <?php echo htmlspecialchars($item['quantity'] ?? 'N/A'); ?>)</li>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <li>No specific junk items detailed.</li>
                    <?php endif; ?>
                    <?php if (!empty($booking_detail_view_data['junk_details']['recommendedDumpsterSize'])): ?>
                        <li>Recommended Dumpster Size: <?php echo htmlspecialchars($booking_detail_view_data['junk_details']['recommendedDumpsterSize']); ?></li>
                    <?php endif; ?>
                    <?php if (!empty($booking_detail_view_data['junk_details']['additionalComment'])): ?>
                        <li>Additional Comments: <?php echo htmlspecialchars($booking_detail_view_data['junk_details']['additionalComment']); ?></li>
                    <?php endif; ?>
                    <?php if (!empty($booking_detail_view_data['junk_details']['media_urls'])): ?>
                        <h5 class="text-md font-semibold text-gray-700 mt-2">Uploaded Media:</h5>
                        <div class="grid grid-cols-2 md:grid-cols-3 gap-2 mt-2">
                            <?php foreach ($booking_detail_view_data['junk_details']['media_urls'] as $media_url): ?>
                                <?php $fileExtension = pathinfo($media_url, PATHINFO_EXTENSION); ?>
                                <?php if (in_array(strtolower($fileExtension), ['jpg', 'jpeg', 'png', 'gif'])): ?>
                                    <img src="<?php echo htmlspecialchars($media_url); ?>" class="w-full h-24 object-cover rounded-lg cursor-pointer" onclick="showImageModal('<?php echo htmlspecialchars($media_url); ?>')">
                                <?php elseif (in_array(strtolower($fileExtension), ['mp4', 'webm', 'ogg'])): ?>
                                    <video src="<?php echo htmlspecialchars($media_url); ?>" controls class="w-full h-24 object-cover rounded-lg"></video>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </ul>
            <?php else: ?>
                <p class="text-gray-600">No specific junk removal details found for this booking.</p>
            <?php endif; ?>
        <?php endif; ?>

        <h3 class="text-xl font-semibold text-gray-700 mb-4 mt-6">Financial Summary</h3>
        <div class="space-y-2 text-gray-700">
            <div class="flex justify-between"><span>Initial Booking Price:</span><span>$<?php echo number_format($booking_detail_view_data['total_price'], 2); ?></span></div>
            <?php
            $total_additional_charges = 0;
            foreach ($booking_detail_view_data['additional_charges'] as $charge):
                $total_additional_charges += $charge['amount'];
            ?>
                <div class="flex justify-between text-sm text-gray-500 pl-4">
                    <span><?php echo htmlspecialchars(ucwords(str_replace('_', ' ', $charge['charge_type']))); ?>:</span>
                    <span>$<?php echo number_format($charge['amount'], 2); ?>
                        <?php if(!empty($charge['invoice_id'])): ?>
                            (<a href="#" onclick="loadAdminSection('invoices', {invoice_id: <?php echo $charge['invoice_id']; ?>}); return false;" class="text-blue-500 hover:underline">View Invoice</a>)
                        <?php endif; ?>
                    </span>
                </div>
                <?php if (!empty($charge['description'])): ?>
                    <p class="text-xs text-gray-500 ml-4">- <?php echo htmlspecialchars($charge['description']); ?></p>
                <?php endif; ?>
            <?php endforeach; ?>
            <div class="flex justify-between font-bold border-t pt-2 mt-2">
                <span>Total Billed:</span>
                <span>$<?php echo number_format($booking_detail_view_data['total_price'] + $total_additional_charges, 2); ?></span>
            </div>
        </div>

        <h3 class="text-xl font-semibold text-gray-700 mb-4 mt-6">Status History</h3>
        <ol class="relative border-l-2 border-blue-200 ml-3">
            <?php foreach ($booking_detail_view_data['status_history'] as $history): ?>
                <li class="mb-6 ml-6">
                    <span class="absolute flex items-center justify-center w-8 h-8 bg-blue-500 rounded-full -left-4 ring-4 ring-white">
                        <i class="fas <?php echo getTimelineIconClass($history['status']); ?> text-white"></i>
                    </span>
                    <div class="ml-4">
                        <h4 class="text-md font-semibold text-gray-900"><?php echo htmlspecialchars(ucwords(str_replace('_', ' ', $history['status']))); ?></h4>
                        <time class="block mb-1 text-xs font-normal text-gray-400"><?php echo (new DateTime($history['status_time']))->format('F j, Y, g:i A'); ?></time>
                        <?php if(!empty($history['notes'])): ?>
                            <p class="text-sm font-normal text-gray-500"><?php echo htmlspecialchars($history['notes']); ?></p>
                        <?php endif; ?>
                    </div>
                </li>
            <?php endforeach; ?>
        </ol>

    <?php else: ?>
        <p class="text-center text-gray-600">Booking details not found or invalid ID.</p>
    <?php endif; ?>
</div>

<div id="add-charge-modal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center hidden z-50">
    <div class="bg-white p-6 rounded-lg shadow-xl w-11/12 max-w-lg text-gray-800">
        <h3 class="text-xl font-bold mb-4">Add Additional Charge</h3>
        <form id="add-charge-form">
            <input type="hidden" name="booking_id" id="add-charge-booking-id">
            <div class="mb-4">
                <label for="charge-type" class="block text-sm font-medium text-gray-700">Charge Type</label>
                <select id="charge-type" name="charge_type" class="mt-1 p-2 border border-gray-300 rounded-md w-full" required>
                    <option value="">Select a type</option>
                    <option value="tonnage_overage">Tonnage Overage</option>
                    <option value="rental_extension">Rental Extension</option>
                    <option value="damage_fee">Damage Fee</option>
                    <option value="other">Other</option>
                </select>
            </div>
            <div class="mb-4">
                <label for="charge-amount" class="block text-sm font-medium text-gray-700">Amount ($)</label>
                <input type="number" id="charge-amount" name="amount" step="0.01" min="0.01" class="mt-1 p-2 border border-gray-300 rounded-md w-full" required>
            </div>
            <div class="mb-4">
                <label for="charge-description" class="block text-sm font-medium text-gray-700">Description</label>
                <textarea id="charge-description" name="description" rows="3" class="mt-1 p-2 border border-gray-300 rounded-md w-full" placeholder="e.g., Overage: 1.5 tons @ $50/ton" required></textarea>
            </div>
            <div class="flex justify-end space-x-4">
                <button type="button" class="px-4 py-2 bg-gray-300 text-gray-800 rounded-lg hover:bg-gray-400" onclick="hideModal('add-charge-modal')">Cancel</button>
                <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 font-semibold">Generate Invoice</button>
            </div>
        </form>
    </div>
</div>

<div id="approve-extension-modal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center hidden z-50">
    <div class="bg-white p-6 rounded-lg shadow-xl w-11/12 max-w-lg text-gray-800">
        <h3 class="text-xl font-bold mb-4">Approve Rental Extension</h3>
        <form id="approve-extension-form">
            <input type="hidden" name="booking_id" id="approve-extension-booking-id">
            <input type="hidden" name="extension_request_id" id="approve-extension-request-id">
            <div class="mb-4">
                <label for="extension-days" class="block text-sm font-medium text-gray-700">Extension Days (Requested)</label>
                <input type="number" id="extension-days" name="extension_days" min="1" class="mt-1 p-2 border border-gray-300 rounded-md w-full bg-gray-100" readonly>
            </div>
            <div class="mb-4">
                 <label for="extension-price-option" class="block text-sm font-medium text-gray-700">Pricing Method</label>
                 <select id="extension-price-option" class="mt-1 p-2 border border-gray-300 rounded-md w-full">
                     <option value="daily_rate">Use Daily Rate</option>
                     <option value="custom_total">Set Custom Total Price</option>
                 </select>
            </div>
            <div class="mb-4" id="daily-rate-section">
                <label for="daily-rate" class="block text-sm font-medium text-gray-700">Daily Rate ($)</label>
                <input type="number" id="daily-rate" name="daily_rate" step="0.01" min="0.01" class="mt-1 p-2 border border-gray-300 rounded-md w-full">
            </div>
            <div class="mb-4 hidden" id="custom-total-section">
                 <label for="custom-total-price" class="block text-sm font-medium text-gray-700">Custom Total Price ($)</label>
                 <input type="number" id="custom-total-price" name="custom_total_price" step="0.01" min="0.01" class="mt-1 p-2 border border-gray-300 rounded-md w-full">
            </div>

            <div class="p-4 bg-yellow-50 border border-yellow-200 rounded-lg mb-5 text-center">
                <p class="text-sm text-yellow-700">Total Extension Cost:</p>
                <p id="extension-total-cost" class="text-2xl font-bold text-yellow-800">$0.00</p>
            </div>
            <div class="flex justify-end space-x-4">
                <button type="button" class="px-4 py-2 bg-gray-300 text-gray-800 rounded-lg hover:bg-gray-400" onclick="hideModal('approve-extension-modal')">Cancel</button>
                <button type="submit" class="px-4 py-2 bg-yellow-500 text-white rounded-lg hover:bg-yellow-600 font-semibold">Approve & Generate Invoice</button>
            </div>
        </form>
    </div>
</div>


<script>
    // Function to load the admin bookings section with updated parameters
    function loadAdminBookings(params = {}) {
        const currentParams = new URLSearchParams(window.location.search);
        const newParams = {
            page: currentParams.get('page') || 1,
            per_page: currentParams.get('per_page') || <?php echo $items_per_page; ?>,
            status: currentParams.get('status') || 'all',
            search: currentParams.get('search') || '',
            ...params
        };
        window.loadAdminSection('bookings', newParams);
    }

    // Function to apply filters and search
    function applyFilters() {
        const statusFilter = document.getElementById('status-filter').value;
        const searchInput = document.getElementById('search-input').value;
        loadAdminBookings({ page: 1, status: statusFilter, search: searchInput });
    }

    function showBookingDetails(bookingId) {
        loadAdminBookings({ booking_id: bookingId });
    }

    function hideBookingDetails() {
        loadAdminBookings({ booking_id: '' }); // Clear booking_id to show list
    }

    // --- Bulk Delete Functionality ---
    const selectAllBookingsCheckbox = document.getElementById('select-all-bookings');
    const bulkDeleteBookingsBtn = document.getElementById('bulk-delete-bookings-btn');

    function toggleBulkDeleteButtonVisibility() {
        const anyChecked = document.querySelectorAll('.booking-checkbox:checked').length > 0;
        if (bulkDeleteBookingsBtn) {
            bulkDeleteBookingsBtn.classList.toggle('hidden', !anyChecked);
        }
    }

    if (selectAllBookingsCheckbox) {
        selectAllBookingsCheckbox.addEventListener('change', function() {
            document.querySelectorAll('.booking-checkbox').forEach(checkbox => {
                checkbox.checked = this.checked;
            });
            toggleBulkDeleteButtonVisibility();
        });
    }

    document.body.addEventListener('change', function(event) {
        if (event.target.classList.contains('booking-checkbox')) {
            if (selectAllBookingsCheckbox && !event.target.checked) {
                selectAllBookingsCheckbox.checked = false;
            }
            toggleBulkDeleteButtonVisibility();
        }
    });

    if (bulkDeleteBookingsBtn) {
        bulkDeleteBookingsBtn.addEventListener('click', function() {
            const selectedIds = Array.from(document.querySelectorAll('.booking-checkbox:checked')).map(cb => cb.value);
            if (selectedIds.length === 0) {
                window.showToast('Please select at least one booking to delete.', 'warning');
                return;
            }

            window.showConfirmationModal(
                'Delete Selected Bookings',
                `Are you sure you want to delete ${selectedIds.length} selected booking(s)? This action cannot be undone and will delete associated data.`,
                async (confirmed) => {
                    if (confirmed) {
                        window.showToast('Deleting bookings...', 'info');
                        const formData = new FormData();
                        formData.append('action', 'delete_bulk'); // Assuming an API action for bulk delete
                        selectedIds.forEach(id => formData.append('booking_ids[]', id));

                        try {
                            // You'll need to create this /api/admin/bookings.php endpoint if it doesn't exist
                            // or add a 'delete_bulk' action to an existing one.
                            const response = await fetch('/api/admin/bookings.php', {
                                method: 'POST',
                                body: formData
                            });
                            const result = await response.json();
                            if (result.success) {
                                window.showToast(result.message, 'success');
                                loadAdminBookings(); // Reload list after deletion
                            } else {
                                window.showToast('Error: ' + result.message, 'error');
                            }
                        } catch (error) {
                            showToast('An unexpected error occurred during bulk delete.', 'error');
                            console.error('Bulk delete bookings API Error:', error);
                        }
                    }
                },
                'Delete Selected',
                'bg-red-600'
            );
        });
    }

    // --- Detail View Actions ---
    document.addEventListener('click', function(event) {
        const target = event.target.closest('button');
        if (!target) return;

        if (target.classList.contains('view-booking-details-btn')) {
            const bookingId = target.dataset.id;
            showBookingDetails(bookingId);
        }

        if (target.id === 'update-booking-status-btn') {
            const bookingId = target.dataset.id;
            const statusSelect = document.getElementById('booking-status-select');
            const newStatus = statusSelect.value;
            const newStatusText = statusSelect.options[statusSelect.selectedIndex].text;

            window.showConfirmationModal(
                'Confirm Status Change',
                `Are you sure you want to change the status to "${newStatusText}"?`,
                async (confirmed) => {
                    if (confirmed) {
                        window.showToast('Updating status...', 'info');
                        const formData = new FormData();
                        formData.append('action', 'update_status');
                        formData.append('booking_id', bookingId);
                        formData.append('status', newStatus);

                        try {
                            const response = await fetch('/api/admin/bookings.php', {
                                method: 'POST',
                                body: formData
                            });
                            const result = await response.json();
                            if (result.success) {
                                window.showToast(result.message, 'success');
                                showBookingDetails(bookingId);
                            } else {
                                window.showToast('Error: ' + result.message, 'error');
                            }
                        } catch (error) {
                            window.showToast('An unexpected error occurred.', 'error');
                            console.error('Update status error:', error);
                        }
                    }
                },
                'Update Status',
                'bg-blue-600'
            );
        }

        if (target.id === 'assign-vendor-btn') {
            const bookingId = target.dataset.id;
            const vendorSelect = document.getElementById('assign-vendor-select');
            const newVendorId = vendorSelect.value;

            if (!newVendorId) {
                window.showToast('Please select a vendor.', 'warning');
                return;
            }

            window.showConfirmationModal(
                'Confirm Vendor Assignment',
                'Are you sure you want to assign this vendor to the booking?',
                async (confirmed) => {
                    if(confirmed) {
                        window.showToast('Assigning vendor...', 'info');
                         const formData = new FormData();
                         formData.append('action', 'assign_vendor');
                         formData.append('booking_id', bookingId);
                         formData.append('vendor_id', newVendorId);

                         try {
                            const response = await fetch('/api/admin/bookings.php', {
                                method: 'POST',
                                body: formData
                            });
                            const result = await response.json();
                            if(result.success){
                                window.showToast(result.message, 'success');
                                showBookingDetails(bookingId);
                            } else {
                                window.showToast('Error: ' + result.message, 'error');
                            }
                         } catch(error) {
                            window.showToast('An unexpected error occurred.', 'error');
                         }
                    }
                },
                'Assign Vendor',
                'bg-green-600'
            );
        }

        if (target.id === 'add-charge-btn') {
            const bookingId = target.dataset.id;
            document.getElementById('add-charge-booking-id').value = bookingId;
            document.getElementById('add-charge-form').reset();
            window.showModal('add-charge-modal');
        }

        if (target.id === 'approve-extension-btn') {
            const bookingId = target.dataset.id;
            const dailyRate = target.dataset.dailyRate;
            const requestedDays = target.dataset.requestedDays;
            const requestId = target.dataset.requestId;

            document.getElementById('approve-extension-booking-id').value = bookingId;
            document.getElementById('approve-extension-request-id').value = requestId;
            document.getElementById('extension-days').value = requestedDays;
            document.getElementById('daily-rate').value = dailyRate;
            
            // Trigger calculation
            const dailyRateInput = document.getElementById('daily-rate');
            const event = new Event('input', { bubbles: true, cancelable: true });
            dailyRateInput.dispatchEvent(event);

            window.showModal('approve-extension-modal');
        }
    });

    const addChargeForm = document.getElementById('add-charge-form');
    if (addChargeForm) {
        addChargeForm.addEventListener('submit', async function(event) {
            event.preventDefault();
            const formData = new FormData(this);
            formData.append('action', 'add_charge');

            if (!formData.get('charge_type') || !formData.get('amount') || !formData.get('description')) {
                window.showToast('All fields are required.', 'error');
                return;
            }

            window.showToast('Adding charge and generating invoice...', 'info');

            try {
                const response = await fetch('/api/admin/bookings.php', {
                    method: 'POST',
                    body: formData
                });
                const result = await response.json();
                if (result.success) {
                    window.showToast(result.message, 'success');
                    window.hideModal('add-charge-modal');
                    showBookingDetails(formData.get('booking_id'));
                } else {
                    window.showToast('Error: ' + result.message, 'error');
                }
            } catch (error) {
                window.showToast('An unexpected error occurred.', 'error');
            }
        });
    }

    const approveExtensionForm = document.getElementById('approve-extension-form');
    if (approveExtensionForm) {
        const daysInput = document.getElementById('extension-days');
        const rateInput = document.getElementById('daily-rate');
        const customPriceInput = document.getElementById('custom-total-price');
        const costDisplay = document.getElementById('extension-total-cost');
        const pricingOption = document.getElementById('extension-price-option');
        const dailyRateSection = document.getElementById('daily-rate-section');
        const customTotalSection = document.getElementById('custom-total-section');

        function calculateExtensionCost() {
            if (pricingOption.value === 'daily_rate') {
                const days = parseInt(daysInput.value) || 0;
                const rate = parseFloat(rateInput.value) || 0;
                costDisplay.textContent = `$${(days * rate).toFixed(2)}`;
            } else {
                 const customPrice = parseFloat(customPriceInput.value) || 0;
                 costDisplay.textContent = `$${customPrice.toFixed(2)}`;
            }
        }
        
        pricingOption.addEventListener('change', function() {
            if(this.value === 'daily_rate'){
                dailyRateSection.classList.remove('hidden');
                customTotalSection.classList.add('hidden');
            } else {
                dailyRateSection.classList.add('hidden');
                customTotalSection.classList.remove('hidden');
            }
            calculateExtensionCost();
        });

        daysInput.addEventListener('input', calculateExtensionCost);
        rateInput.addEventListener('input', calculateExtensionCost);
        customPriceInput.addEventListener('input', calculateExtensionCost);

        approveExtensionForm.addEventListener('submit', async function(event) {
            event.preventDefault();
            const formData = new FormData(this);
            formData.append('action', 'approve_extension');
            formData.append('pricing_option', pricingOption.value);

            if (!formData.get('extension_days')) {
                window.showToast('Extension days are required.', 'error');
                return;
            }

            window.showToast('Approving extension and generating invoice...', 'info');
            try {
                const response = await fetch('/api/admin/bookings.php', {
                    method: 'POST',
                    body: formData
                });
                const result = await response.json();
                if (result.success) {
                    window.showToast(result.message, 'success');
                    window.hideModal('approve-extension-modal');
                    showBookingDetails(formData.get('booking_id'));
                } else {
                    window.showToast('Error: ' + result.message, 'error');
                }
            } catch (error) {
                window.showToast('An unexpected error occurred.', 'error');
            }
        });
    }

</script>