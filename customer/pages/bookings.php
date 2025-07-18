<?php
// customer/pages/bookings.php

// Ensure session is started and user is logged in as a customer
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
// Using $_SERVER['DOCUMENT_ROOT'] for absolute paths for robustness
require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/db.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/session.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/functions.php';

if (!is_logged_in()) {
    echo '<div class="text-red-500 text-center p-8">You must be logged in to view this content.</div>';
    exit;
}

$user_id = $_SESSION['user_id'];
$booking_detail = null;
$bookings_list = [];

$requested_booking_id = filter_input(INPUT_GET, 'booking_id', FILTER_VALIDATE_INT);

// Set a default timezone to ensure consistent date calculations
date_default_timezone_set('UTC');

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
$start_date_filter = $_GET['start_date'] ?? '';
$end_date_filter = $_GET['end_date'] ?? '';


if ($requested_booking_id) {
    // --- ENHANCED DATA FETCHING FOR DETAIL VIEW ---
    // Fetching all relevant data including charges from the original quote
    $stmt = $conn->prepare("
        SELECT
            b.id, b.booking_number, b.service_type, b.start_date, b.end_date, b.status,
            b.delivery_location, b.pickup_location, b.delivery_instructions, b.pickup_instructions,
            b.total_price AS initial_price,
            q.swap_charge, q.relocation_charge, q.daily_rate, q.is_swap_included, q.is_relocation_included,
            r.id as review_id,
            ext.id AS extension_request_id, ext.status AS extension_request_status,
            ext_inv.id AS extension_invoice_id, ext_inv.status AS extension_invoice_status
        FROM bookings b
        LEFT JOIN invoices i ON b.invoice_id = i.id
        LEFT JOIN quotes q ON i.quote_id = q.id
        LEFT JOIN reviews r ON b.id = r.booking_id AND r.user_id = b.user_id
        LEFT JOIN booking_extension_requests ext ON b.id = ext.booking_id
        LEFT JOIN invoices ext_inv ON ext.invoice_id = ext_inv.id
        WHERE b.id = ? AND b.user_id = ?
        ORDER BY ext.created_at DESC -- Order by created_at to get the latest extension request if multiple exist
        LIMIT 1
    ");
    $stmt->bind_param("ii", $requested_booking_id, $user_id);
    $stmt->execute();
    $booking_detail = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($booking_detail) {
        // Calculate remaining days, safely handling potential NULL or empty end_date
        if (in_array($booking_detail['status'], ['delivered', 'in_use', 'awaiting_pickup']) && 
            !empty($booking_detail['end_date'])) { // The !empty check prevents processing if it's null or empty string
            try {
                $endDate = new DateTime($booking_detail['end_date']);
                $today = new DateTime('today'); // Use 'today' to ignore the time part
                
                if ($endDate >= $today) {
                    $interval = $today->diff($endDate);
                    $booking_detail['remaining_days'] = $interval->days;
                } else {
                    $booking_detail['remaining_days'] = 0;
                }
            } catch (Exception $e) {
                // Log the exception if needed, and set a fallback
                error_log("DateTime conversion error for booking ID {$requested_booking_id}: {$e->getMessage()}");
                $booking_detail['remaining_days'] = 'N/A';
            }
        } else {
            $booking_detail['remaining_days'] = 'N/A'; // No remaining days if status is not applicable or end_date is missing
        }


        // Fetch status history for the timeline
        $history_stmt = $conn->prepare("SELECT status, status_time, notes FROM booking_status_history WHERE booking_id = ? ORDER BY status_time ASC, id ASC");
        $history_stmt->bind_param("i", $booking_detail['id']);
        $history_stmt->execute();
        $history_result = $history_stmt->get_result();
        $booking_detail['status_history'] = [];
        while ($history_row = $history_result->fetch_assoc()) {
            $booking_detail['status_history'][] = $history_row;
        }
        $history_stmt->close();

        // Fetch equipment details from original quote
        $eq_stmt = $conn->prepare("SELECT qed.* FROM quote_equipment_details qed JOIN quotes q ON qed.quote_id = q.id JOIN invoices i ON q.id = i.quote_id JOIN bookings b ON i.id = b.invoice_id WHERE b.id = ?");
        $eq_stmt->bind_param("i", $booking_detail['id']);
        $eq_stmt->execute();
        $eq_result = $eq_stmt->get_result();
        $booking_detail['equipment_items'] = [];
        while($eq_row = $eq_result->fetch_assoc()){
            $booking_detail['equipment_items'][] = $eq_row;
        }
        $eq_stmt->close();
        
        // Fetch additional charges
        $charge_stmt = $conn->prepare("SELECT charge_type, amount, description, created_at FROM booking_charges WHERE booking_id = ?");
        $charge_stmt->bind_param("i", $booking_detail['id']);
        $charge_stmt->execute();
        $charge_result = $charge_stmt->get_result();
        $booking_detail['additional_charges'] = [];
        while($charge_row = $charge_result->fetch_assoc()){
            $booking_detail['additional_charges'][] = $charge_row;
        }
        $charge_stmt->close();

    }
} else {
    // --- Fetch Data for List View ---
    $base_query = "FROM bookings b WHERE b.user_id = ?";
    $params = [$user_id];
    $types = "i";

    // Status Filter
    if ($filter_status !== 'all') {
        $base_query .= " AND b.status = ?";
        $params[] = $filter_status;
        $types .= "s";
    }

    // Search Query (Booking # or Location)
    if (!empty($search_query)) {
        $search_term = '%' . $search_query . '%';
        $base_query .= " AND (b.booking_number LIKE ? OR b.delivery_location LIKE ?)";
        $params[] = $search_term;
        $params[] = $search_term;
        $types .= "ss";
    }

    // Date Range Filter
    if (!empty($start_date_filter)) {
        $base_query .= " AND DATE(b.start_date) >= ?";
        $params[] = $start_date_filter;
        $types .= "s";
    }
    if (!empty($end_date_filter)) {
        $base_query .= " AND DATE(b.start_date) <= ?";
        $params[] = $end_date_filter;
        $types .= "s";
    }

    // Get total count for pagination
    $stmt_count = $conn->prepare("SELECT COUNT(*) " . $base_query);
    if (!empty($params)) {
        $stmt_count->bind_param($types, ...$params);
    }
    $stmt_count->execute();
    $total_bookings_count = $stmt_count->get_result()->fetch_assoc()['COUNT(*)'];
    $stmt_count->close();

    $total_pages = ceil($total_bookings_count / $items_per_page);

    // Main query for bookings list
    $list_query = "SELECT id, booking_number, service_type, start_date, status " . $base_query . " ORDER BY created_at DESC LIMIT ? OFFSET ?";

    $params[] = $items_per_page;
    $params[] = $offset;
    $types .= "ii"; // Add types for LIMIT and OFFSET

    $stmt = $conn->prepare($list_query);
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $bookings_list[] = $row;
    }
    $stmt->close();
}

$conn->close();
generate_csrf_token(); 

// --- Helper Functions ---
function getStatusBadgeClass($status) {
    switch ($status) {
        case 'pending':
        case 'scheduled':
            return 'bg-blue-100 text-blue-800';
        case 'assigned':
        case 'out_for_delivery':
            return 'bg-purple-100 text-purple-800';
        case 'delivered':
        case 'in_use':
            return 'bg-teal-100 text-teal-800';
        case 'awaiting_pickup':
            return 'bg-yellow-100 text-yellow-800';
        case 'completed':
            return 'bg-green-100 text-green-800';
        case 'cancelled':
            return 'bg-red-100 text-red-800';
        case 'relocation_requested':
            return 'bg-indigo-100 text-indigo-800';
        case 'swap_requested':
            return 'bg-pink-100 text-pink-800';
        case 'relocated':
            return 'bg-lime-100 text-lime-800';
        case 'swapped':
            return 'bg-emerald-100 text-emerald-800';
        case 'extended':
            return 'bg-cyan-100 text-cyan-800';
        default:
            return 'bg-gray-100 text-gray-700';
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

<div id="booking-list-view" class="<?php echo $requested_booking_id ? 'hidden' : ''; ?>">
    <h1 class="text-3xl font-bold text-gray-800 mb-8">My Bookings</h1>
    <div class="bg-white p-6 rounded-lg shadow-md border border-gray-200">
        <div class="flex flex-col sm:flex-row justify-between items-center mb-4 gap-3">
            <h2 class="text-xl font-semibold text-gray-700 flex-grow"><i class="fas fa-book-open mr-2 text-blue-600"></i>All Your Bookings</h2>
        </div>

        <div class="mb-4 flex flex-col sm:flex-row items-center justify-between gap-3">
            <div class="flex-grow w-full sm:w-auto flex items-center gap-2">
                <input type="text" id="search-input" placeholder="Search booking # or address..."
                    class="p-2 border border-gray-300 rounded-md w-full text-sm"
                    value="<?php echo htmlspecialchars($search_query); ?>"
                    onkeydown="if(event.key === 'Enter') applyFilters()">
                <button id="toggle-filters-btn" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors duration-200 shadow-md md:hidden">
                    <i class="fas fa-filter"></i>
                </button>
                 <button onclick="applyFilters()" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors duration-200 shadow-md hidden md:block">
                    <i class="fas fa-search"></i>
                </button>
            </div>

            <div id="filter-options-section" class="flex-col sm:flex-row gap-3 w-full md:flex hidden">
                <div class="flex items-center gap-2 flex-grow">
                    <label for="status-filter" class="text-sm font-medium text-gray-700">Status:</label>
                    <select id="status-filter" onchange="applyFilters()"
                            class="p-2 border border-gray-300 rounded-md text-sm flex-grow">
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
                <div class="flex items-center gap-2 w-full sm:w-auto">
                    <label for="start-date-filter" class="text-sm font-medium text-gray-700">From:</label>
                    <input type="date" id="start-date-filter" value="<?php echo htmlspecialchars($start_date_filter); ?>"
                           class="p-2 border border-gray-300 rounded-md text-sm w-full flex-grow" onchange="applyFilters()">
                    <label for="end-date-filter" class="text-sm font-medium text-gray-700">To:</label>
                    <input type="date" id="end-date-filter" value="<?php echo htmlspecialchars($end_date_filter); ?>"
                           class="p-2 border border-gray-300 rounded-md text-sm w-full flex-grow" onchange="applyFilters()">
                </div>
                <button onclick="applyFilters()" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors duration-200 shadow-md w-full sm:w-auto md:hidden">
                    Apply Filters
                </button>
            </div>
        </div>
        <div class="flex justify-end mb-4">
             <button id="bulk-delete-bookings-btn" class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors duration-200 shadow-md hidden md:inline-flex items-center">
                <i class="fas fa-trash-alt mr-2"></i>Delete Selected
            </button>
        </div>

        <?php if (empty($bookings_list)): ?>
            <p class="text-center text-gray-500 py-4">No bookings found for the selected filters or search query.</p>
        <?php else: ?>
            <div class="hidden md:block overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                     <thead class="bg-blue-50">
                        <tr>
                            <th class="px-6 py-3 text-left">
                                <input type="checkbox" id="select-all-bookings" class="h-4 w-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Booking #</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Service</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($bookings_list as $booking): ?>
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <input type="checkbox" class="booking-checkbox h-4 w-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500" value="<?php echo htmlspecialchars($booking['id']); ?>">
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-gray-900"><?php echo htmlspecialchars($booking['booking_number']); ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600"><?php echo htmlspecialchars(ucwords(str_replace('_', ' ', $booking['service_type']))); ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600"><?php echo htmlspecialchars($booking['start_date']); ?></td>
                                <td class="px-6 py-4 whitespace-nowrap"><span class="px-2 py-1 text-xs font-semibold rounded-full <?php echo getStatusBadgeClass($booking['status']); ?>"><?php echo htmlspecialchars(ucwords(str_replace('_', ' ', $booking['status']))); ?></span></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-right">
                                    <button class="text-blue-600 hover:underline view-details-btn" data-booking-id="<?php echo $booking['id']; ?>">View Details</button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div class="md:hidden space-y-4">
                <?php foreach ($bookings_list as $booking): ?>
                    <div class="bg-white rounded-lg shadow-md border border-blue-200 p-4 relative">
                        <div class="absolute top-3 right-3 flex space-x-2">
                            <input type="checkbox" class="booking-checkbox h-4 w-4" value="<?php echo $booking['id']; ?>">
                        </div>
                        <div class="mb-2">
                            <p class="text-sm font-bold text-gray-800">Booking ID: #<?php echo htmlspecialchars($booking['booking_number']); ?></p>
                            <p class="text-xs text-gray-600">Service: <?php echo htmlspecialchars(ucwords(str_replace('_', ' ', $booking['service_type']))); ?></p>
                        </div>
                        <div class="border-t border-b border-gray-200 py-2 mb-2">
                            <p class="text-sm text-gray-700"><span class="font-medium">Date:</span> <?php echo htmlspecialchars($booking['start_date']); ?></p>
                            <p class="text-sm text-gray-700"><span class="font-medium">Status:</span> 
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo getStatusBadgeClass($booking['status']); ?>">
                                    <?php echo htmlspecialchars(ucwords(str_replace('_', ' ', $booking['status']))); ?>
                                </span>
                            </p>
                        </div>
                        <div class="flex justify-end mt-3">
                            <button class="px-3 py-1 bg-blue-100 text-blue-700 rounded-md hover:bg-blue-200 text-xs view-details-btn" data-booking-id="<?php echo $booking['id']; ?>">
                                <i class="fas fa-eye mr-1"></i>View Details
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <nav class="mt-4 flex flex-col sm:flex-row items-center justify-between gap-3">
                <div>
                    <p class="text-sm text-gray-700">
                        Showing <span class="font-medium"><?php echo $offset + 1; ?></span> to <span class="font-medium"><?php echo min($offset + $items_per_page, $total_bookings_count); ?></span> of <span class="font-medium"><?php echo $total_bookings_count; ?></span> results
                    </p>
                </div>
                <div class="flex items-center gap-2">
                    <span class="text-sm font-medium text-gray-700">Bookings per page:</span>
                    <select id="items-per-page-select" onchange="applyFilters({page: 1, per_page: this.value})"
                            class="p-2 border border-gray-300 rounded-md text-sm">
                        <?php foreach ($items_per_page_options as $option): ?>
                            <option value="<?php echo $option; ?>" <?php echo $items_per_page == $option ? 'selected' : ''; ?>>
                                <?php echo $option; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px" aria-label="Pagination">
                        <button onclick="applyFilters({page: <?php echo max(1, $current_page - 1); ?>})"
                                class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
                            <span class="sr-only">Previous</span>
                            <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                <path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd" />
                            </svg>
                        </button>
                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                            <button onclick="applyFilters({page: <?php echo $i; ?>})"
                                    class="<?php echo $i == $current_page ? 'z-10 bg-blue-50 border-blue-500 text-blue-600' : 'bg-white border-gray-300 text-gray-700 hover:bg-gray-50'; ?> relative inline-flex items-center px-4 py-2 border text-sm font-medium">
                                <?php echo $i; ?>
                            </button>
                        <?php endfor; ?>
                        <button onclick="applyFilters({page: <?php echo min($total_pages, $current_page + 1); ?>})"
                                class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
                            <span class="sr-only">Next</span>
                            <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
                            </svg>
                        </button>
                    </nav>
                </div>
            </nav>
        <?php endif; ?>
    </div>
</div>

<div id="booking-detail-view" class="<?php echo $requested_booking_id ? '' : 'hidden'; ?>">
    <?php if ($booking_detail): ?>
        <button class="mb-6 px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 back-to-list-btn">
            <i class="fas fa-arrow-left mr-2"></i>Back to All Bookings
        </button>
        
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <div class="lg:col-span-1 space-y-6">
                 <div class="bg-white p-6 rounded-lg shadow-md border border-gray-200">
                    <h3 class="text-xl font-bold text-gray-800 mb-2">Booking #<?php echo htmlspecialchars($booking_detail['booking_number']); ?></h3>
                    <p class="text-gray-500 mb-4">Current Status:</p>
                    <span class="text-lg font-bold px-3 py-1 rounded-full <?php echo getStatusBadgeClass($booking_detail['status']); ?>">
                        <?php echo htmlspecialchars(ucwords(str_replace('_', ' ', $booking_detail['status']))); ?>
                    </span>
                    
                    <?php if (isset($booking_detail['remaining_days']) && $booking_detail['remaining_days'] !== 'N/A'): ?>
                        <div class="mt-4 pt-4 border-t border-gray-200">
                            <p class="text-gray-500 mb-2">Time Remaining:</p>
                            <div class="text-center p-4 rounded-lg <?php echo $booking_detail['remaining_days'] < 3 ? 'bg-red-100' : 'bg-green-100'; ?>">
                                <div class="text-4xl font-bold <?php echo $booking_detail['remaining_days'] < 3 ? 'text-red-600' : 'text-green-600'; ?>">
                                    <?php echo $booking_detail['remaining_days']; ?>
                                </div>
                                <div class="text-sm font-medium <?php echo $booking_detail['remaining_days'] < 3 ? 'text-red-700' : 'text-green-700'; ?>">
                                    Days Remaining
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="bg-white p-6 rounded-lg shadow-md border border-gray-200">
                    <h3 class="text-xl font-semibold text-gray-700 mb-4">Status Timeline</h3>
                    <ol class="relative border-l-2 border-blue-200">
                        <?php foreach ($booking_detail['status_history'] as $history): ?>
                        <li class="ml-6 mb-6">
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
                </div>

                 <?php if (in_array($booking_detail['status'], ['delivered', 'in_use', 'awaiting_pickup'])): // Show service requests for active rentals ?>
                    <div class="bg-white p-6 rounded-lg shadow-md border border-gray-200">
                        <h3 class="text-xl font-semibold text-gray-700 mb-4">Service Requests</h3>
                        <div class="space-y-3">
                             <button class="w-full px-4 py-3 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-colors duration-200 shadow-md request-relocation-btn"
                                     data-booking-id="<?php echo $booking_detail['id']; ?>"
                                     data-charge="<?php echo htmlspecialchars($booking_detail['relocation_charge'] ?? '0.00'); ?>"
                                     data-is-included="<?php echo htmlspecialchars($booking_detail['is_relocation_included'] ? 'true' : 'false'); ?>">
                                 <i class="fas fa-truck-loading mr-2"></i>Request Relocation
                             </button>
                             <button class="w-full px-4 py-3 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition-colors duration-200 shadow-md request-swap-btn"
                                     data-booking-id="<?php echo $booking_detail['id']; ?>"
                                     data-charge="<?php echo htmlspecialchars($booking_detail['swap_charge'] ?? '0.00'); ?>"
                                     data-is-included="<?php echo htmlspecialchars($booking_detail['is_swap_included'] ? 'true' : 'false'); ?>">
                                 <i class="fas fa-exchange-alt mr-2"></i>Request Swap
                             </button>
                             <?php if ($booking_detail['extension_request_id'] && $booking_detail['extension_request_status'] === 'pending'): ?>
                                <div class="p-3 text-center bg-yellow-100 text-yellow-800 rounded-lg">
                                    <i class="fas fa-hourglass-half mr-2"></i>Your extension request is pending admin approval.
                                </div>
                             <?php elseif ($booking_detail['extension_request_id'] && $booking_detail['extension_request_status'] === 'approved' && $booking_detail['extension_invoice_id'] && $booking_detail['extension_invoice_status'] === 'pending'): ?>
                                <button class="w-full px-4 py-3 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors duration-200 shadow-md animate-pulse"
                                        onclick="window.loadCustomerSection('invoices', { invoice_id: <?php echo $booking_detail['extension_invoice_id']; ?> });">
                                    <i class="fas fa-file-invoice-dollar mr-2"></i>Extension Approved! Pay Invoice
                                </button>
                             <?php else: ?>
                                <button class="w-full px-4 py-3 bg-orange-500 text-white rounded-lg hover:bg-orange-600 transition-colors duration-200 shadow-md request-extension-btn"
                                        data-booking-id="<?php echo $booking_detail['id']; ?>"
                                        data-daily-rate="<?php echo htmlspecialchars($booking_detail['daily_rate'] ?? '0.00'); ?>">
                                    <i class="fas fa-calendar-plus mr-2"></i>Request Extension
                                </button>
                             <?php endif; ?>
                             <button class="w-full px-4 py-3 bg-teal-600 text-white rounded-lg hover:bg-teal-700 transition-colors duration-200 shadow-md schedule-pickup-btn"
                                     data-booking-id="<?php echo $booking_detail['id']; ?>">
                                 <i class="fas fa-calendar-check mr-2"></i>Schedule Pickup
                             </button>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <div class="lg:col-span-2 space-y-6">
                <div class="bg-white p-6 rounded-lg shadow-md border border-gray-200">
                     <h3 class="text-xl font-semibold text-gray-700 mb-4">Service Details</h3>
                     <div class="space-y-3">
                        <p><strong>Service Type:</strong> <?php echo htmlspecialchars(ucwords(str_replace('_', ' ', $booking_detail['service_type']))); ?></p>
                        <p><strong>Rental Start:</strong> <?php echo (new DateTime($booking_detail['start_date']))->format('F j, Y'); ?></p>
                        <p><strong>Rental End:</strong> <?php echo !empty($booking_detail['end_date']) ? (new DateTime($booking_detail['end_date']))->format('F j, Y') : 'N/A'; ?></p>
                        <p><strong>Delivery Location:</strong> <?php echo htmlspecialchars($booking_detail['delivery_location']); ?></p>
                        <?php if(!empty($booking_detail['delivery_instructions'])): ?>
                             <p><strong>Instructions:</strong> <?php echo htmlspecialchars($booking_detail['delivery_instructions']); ?></p>
                        <?php endif; ?>
                     </div>
                     <?php if(!empty($booking_detail['equipment_items'])): ?>
                        <h4 class="font-semibold mt-4 mb-2 text-gray-800">Equipment on Site:</h4>
                        <ul class="list-disc list-inside space-y-2 pl-4">
                            <?php foreach ($booking_detail['equipment_items'] as $item): ?>
                                <li><strong><?php echo htmlspecialchars($item['quantity']); ?>x</strong> <?php echo htmlspecialchars($item['equipment_name']); ?></li>
                            <?php endforeach; ?>
                        </ul>
                     <?php endif; ?>
                </div>

                 <div class="bg-white p-6 rounded-lg shadow-md border border-gray-200">
                    <h3 class="text-xl font-semibold text-gray-700 mb-4">Financial Summary</h3>
                    <div class="space-y-2 text-gray-700">
                        <div class="flex justify-between"><span>Initial Booking Cost:</span><span>$<?php echo number_format($booking_detail['initial_price'], 2); ?></span></div>
                        <?php 
                        $total_additional = 0;
                        foreach($booking_detail['additional_charges'] as $charge) {
                            $total_additional += $charge['amount'];
                            echo '<div class="flex justify-between text-sm text-gray-500 pl-4"><span>' . htmlspecialchars(ucwords(str_replace('_', ' ', $charge['charge_type']))) . ':</span><span>$' . number_format($charge['amount'], 2) . '</span></div>';
                        }
                        ?>
                        <div class="flex justify-between font-bold border-t pt-2 mt-2"><span>Total Billed:</span><span>$<?php echo number_format($booking_detail['initial_price'] + $total_additional, 2); ?></span></div>
                    </div>
                </div>
                
                <?php if ($booking_detail['status'] === 'completed'): ?>
                    <div class="bg-white p-6 rounded-lg shadow-md border border-gray-200">
                        <h3 class="text-xl font-semibold text-gray-700 mb-4">Leave a Review</h3>
                        <?php if ($booking_detail['review_id']): ?>
                            <p class="text-green-600 font-semibold"><i class="fas fa-check-circle mr-2"></i>Thank you! You have already submitted a review for this booking.</p>
                        <?php else: ?>
                            <form id="review-form">
                                <input type="hidden" name="booking_id" value="<?php echo $booking_detail['id']; ?>">
                                <div class="mb-4">
                                    <label class="block text-gray-700 mb-2">Your Rating:</label>
                                    <div class="star-rating flex items-center text-3xl text-gray-300">
                                        <i class="fas fa-star cursor-pointer" data-value="1"></i>
                                        <i class="fas fa-star cursor-pointer" data-value="2"></i>
                                        <i class="fas fa-star cursor-pointer" data-value="3"></i>
                                        <i class="fas fa-star cursor-pointer" data-value="4"></i>
                                        <i class="fas fa-star cursor-pointer" data-value="5"></i>
                                        <input type="hidden" name="rating" id="rating-value" required>
                                    </div>
                                </div>
                                <div class="mb-4">
                                    <label for="review_text" class="block text-gray-700 mb-2">Your Comments (Optional):</label>
                                    <textarea id="review_text" name="review_text" rows="4" class="w-full p-2 border border-gray-300 rounded-lg"></textarea>
                                </div>
                                <button type="submit" class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">Submit Review</button>
                            </form>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

    <?php else: ?>
        <p class="text-center text-red-500 py-8">The requested booking was not found.</p>
    <?php endif; ?>
</div>

<div id="extension-request-modal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center hidden z-50">
    <div class="bg-white p-6 rounded-lg shadow-xl w-11/12 max-w-md text-gray-800">
        <h3 class="text-xl font-bold mb-4">Request Rental Extension</h3>
        <form id="extension-form">
            <input type="hidden" name="action" value="request_extension">
            <input type="hidden" name="booking_id" id="extension-booking-id">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
            <div class="mb-5">
                <label for="extension-days" class="block text-sm font-medium text-gray-700 mb-2">Number of Additional Days</label>
                <input type="number" id="extension-days" name="extension_days" class="w-full p-3 border border-gray-300 rounded-lg" min="1" required>
            </div>
            <div class="p-4 bg-blue-50 border border-blue-200 rounded-lg mb-5 text-center">
                <p class="text-sm text-blue-700">Estimated Cost:</p>
                <p id="extension-cost-display" class="text-2xl font-bold text-blue-800">$0.00</p>
                <p class="text-xs text-blue-600">Based on a daily rate of <span id="extension-daily-rate-display">$0.00</span></p>
            </div>
            <div class="flex justify-end space-x-4">
                <button type="button" class="px-4 py-2 bg-gray-300 text-gray-800 rounded-lg hover:bg-gray-400" onclick="hideModal('extension-request-modal')">Cancel</button>
                <button type="submit" class="px-4 py-2 bg-orange-500 text-white rounded-lg hover:bg-orange-600">Submit Extension Request</button>
            </div>
        </form>
    </div>
</div>

<div id="relocation-request-modal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center hidden z-50">
    <div class="bg-white p-6 rounded-lg shadow-xl w-11/12 max-w-md text-gray-800">
        <h3 class="text-xl font-bold mb-4">Request Relocation</h3>
        <p id="relocation-charge-text" class="mb-4">A one-time charge for this service will be applied: <span class="font-bold text-blue-600" id="relocation-charge-display">$0.00</span></p>
        <form id="relocation-form">
            <input type="hidden" name="action" value="request_relocation">
            <input type="hidden" name="booking_id" id="relocation-booking-id">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
            <div class="mb-5">
                <label for="relocation-address" class="block text-sm font-medium text-gray-700 mb-2">New Destination Address</label>
                <input type="text" id="relocation-address" name="new_address" class="w-full p-3 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500" placeholder="Enter new full address" required>
            </div>
            <div class="flex justify-end space-x-4">
                <button type="button" class="px-4 py-2 bg-gray-300 text-gray-800 rounded-lg hover:bg-gray-400" onclick="hideModal('relocation-request-modal')">Cancel</button>
                <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700">Proceed to Payment</button>
            </div>
        </form>
    </div>
</div>

<div id="swap-request-modal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center hidden z-50">
    <div class="bg-white p-6 rounded-lg shadow-xl w-11/12 max-w-md text-gray-800">
        <h3 class="text-xl font-bold mb-4">Request Swap</h3>
        <p id="swap-charge-text" class="mb-6">A one-time charge of <span class="font-bold text-purple-600" id="swap-charge-display">$0.00</span> will be applied to swap your equipment. Are you sure you want to proceed?</p>
        <form id="swap-form">
            <input type="hidden" name="action" value="request_swap">
            <input type="hidden" name="booking_id" id="swap-booking-id">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
            <div class="flex justify-end space-x-4">
                <button type="button" class="px-4 py-2 bg-gray-300 text-gray-800 rounded-lg hover:bg-gray-400" onclick="hideModal('swap-request-modal')">Cancel</button>
                <button type="submit" class="px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700">Yes, Proceed to Payment</button>
            </div>
        </form>
    </div>
</div>

<div id="pickup-request-modal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center hidden z-50">
    <div class="bg-white p-6 rounded-lg shadow-xl w-11/12 max-w-md text-gray-800">
        <h3 class="text-xl font-bold mb-4">Schedule Pickup</h3>
        <p class="mb-4 text-sm text-gray-600">Please select your preferred date and time for pickup. Our team will confirm availability.</p>
        <form id="pickup-form">
            <input type="hidden" name="action" value="schedule_pickup">
            <input type="hidden" name="booking_id" id="pickup-booking-id">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
            <div class="mb-5">
                <label for="pickup-date" class="block text-sm font-medium text-gray-700 mb-2">Preferred Date</label>
                <input type="date" id="pickup-date" name="pickup_date" class="w-full p-3 border border-gray-300 rounded-lg" required min="<?php echo date('Y-m-d'); ?>">
            </div>
             <div class="mb-5">
                <label for="pickup-time" class="block text-sm font-medium text-gray-700 mb-2">Preferred Time</label>
                <input type="time" id="pickup-time" name="pickup_time" class="w-full p-3 border border-gray-300 rounded-lg" required>
            </div>
            <div class="flex justify-end space-x-4">
                <button type="button" class="px-4 py-2 bg-gray-300 text-gray-800 rounded-lg hover:bg-gray-400" onclick="hideModal('pickup-request-modal')">Cancel</button>
                <button type="submit" class="px-4 py-2 bg-teal-600 text-white rounded-lg hover:bg-teal-700">Schedule Pickup</button>
            </div>
        </form>
    </div>
</div>

<script>
(function() {
    const contentArea = document.getElementById('content-area');
    if (!contentArea) return;

    // Helper function to get safe filter values
    const getSafeFilterValues = () => {
        const statusEl = document.getElementById('status-filter');
        const searchEl = document.getElementById('search-input');
        const startDateEl = document.getElementById('start-date-filter');
        const endDateEl = document.getElementById('end-date-filter');
        const perPageEl = document.getElementById('items-per-page-select');

        // Use the current PHP value as the default to maintain state
        const defaultPerPage = <?php echo json_encode($items_per_page); ?>;

        return {
            status: statusEl ? statusEl.value : 'all',
            search: searchEl ? searchEl.value : '',
            start_date: startDateEl ? startDateEl.value : '',
            end_date: endDateEl ? endDateEl.value : '',
            per_page: perPageEl ? perPageEl.value : defaultPerPage
        };
    };

    window.loadCustomerBookings = function(params = {}) {
        const filters = getSafeFilterValues();
        const newParams = {
            page: filters.page, // Keep current page from filter values
            per_page: filters.per_page,
            status: filters.status,
            search: filters.search,
            start_date: filters.start_date,
            end_date: filters.end_date,
            ...params // Override with any new parameters passed directly
        };
        window.loadCustomerSection('bookings', newParams);
    };

    window.applyFilters = function(params = {}) {
        // Reset to page 1 when applying new filters, unless a specific page is requested
        const currentPage = params.page || 1;
        const filters = getSafeFilterValues();
        window.loadCustomerBookings({
            page: currentPage,
            status: filters.status,
            search: filters.search,
            start_date: filters.start_date,
            end_date: filters.end_date,
            per_page: filters.per_page // Ensure per_page is also passed
        });
    };

    function showBookingDetails(bookingId) {
        window.loadCustomerBookings({ booking_id: bookingId });
    }

    function hideBookingDetails() {
        window.loadCustomerBookings({ booking_id: '' }); // Clear booking_id to show list
    }

    const callBookingApi = async (form) => {
        const formData = new FormData(form);
        const actionText = (formData.get('action') || 'action').replace(/_/g, ' ');
        window.showToast(`Submitting ${actionText}...`, 'info');

        try {
            const response = await fetch('/api/customer/bookings.php', { method: 'POST', body: formData });
            const result = await response.json();
            
            if (result.success) {
                window.showToast(result.message, 'success');
                // For extension requests, we now wait for admin approval, so we just reload the booking detail view.
                hideModal('extension-request-modal');
                hideModal('relocation-request-modal');
                hideModal('swap-request-modal');
                hideModal('pickup-request-modal');
                window.loadCustomerBookings({ booking_id: formData.get('booking_id') }); // Reload to show updated details/status
            } else {
                window.showToast(result.message || `Failed to submit ${actionText}.`, 'error');
            }
        } catch (error) {
            console.error('API Error:', error);
            window.showToast('An unexpected error occurred.', 'error');
        } finally {
            // Ensure all modals are hidden regardless of success/failure
            ['extension-request-modal', 'relocation-request-modal', 'swap-request-modal', 'pickup-request-modal'].forEach(id => hideModal(id));
        }
    };

    // --- Star Rating Logic ---
    const starRatingContainer = contentArea.querySelector('.star-rating');
    if(starRatingContainer) {
        const stars = starRatingContainer.querySelectorAll('.fa-star');
        const ratingInput = document.getElementById('rating-value');
        
        stars.forEach(star => {
            star.addEventListener('mouseover', function() {
                stars.forEach(s => s.classList.remove('text-yellow-400'));
                for (let i = 0; i < this.dataset.value; i++) {
                    stars[i].classList.add('text-yellow-400');
                }
            });
            star.addEventListener('mouseout', function() {
                stars.forEach(s => s.classList.remove('text-yellow-400'));
                if(ratingInput.value){
                    for (let i = 0; i < ratingInput.value; i++) {
                        stars[i].classList.add('text-yellow-400');
                    }
                }
            });
            star.addEventListener('click', function() {
                ratingInput.value = this.dataset.value;
                stars.forEach(s => s.classList.remove('text-yellow-400'));
                 for (let i = 0; i < ratingInput.value; i++) {
                    stars[i].classList.add('text-yellow-400');
                }
            });
        });
    }

    // Review Form Submission
    const reviewForm = document.getElementById('review-form');
    if (reviewForm) {
        reviewForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            formData.append('csrf_token', '<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>'); // Add CSRF token
            if (!formData.get('rating')) {
                window.showToast('Please select a star rating.', 'error');
                return;
            }
            window.showToast('Submitting your review...', 'info');
            try {
                const response = await fetch('/api/customer/reviews.php', { method: 'POST', body: formData });
                const result = await response.json();
                if(result.success) {
                    window.showToast(result.message, 'success');
                    window.loadCustomerBookings({ booking_id: formData.get('booking_id') }); // Reload to show thank you message
                } else {
                     window.showToast(result.message, 'error');
                }
            } catch (error) {
                 window.showToast('An error occurred while submitting your review.', 'error');
            }
        });
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
                        formData.append('action', 'delete_bulk'); // Action handled by api/customer/bookings.php
                        formData.append('csrf_token', '<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>'); // Add CSRF token
                        selectedIds.forEach(id => formData.append('booking_ids[]', id));

                        try {
                            const response = await fetch('/api/customer/bookings.php', {
                                method: 'POST',
                                body: formData
                            });
                            const result = await response.json();
                            if (result.success) {
                                window.showToast(result.message, 'success');
                                window.loadCustomerBookings(); // Reload list after deletion
                            } else {
                                window.showToast('Error: ' + result.message, 'error');
                            }
                        } catch (error) {
                            window.showToast('An unexpected error occurred during bulk delete.', 'error');
                            console.error('Bulk delete bookings API Error:', error);
                        }
                    }
                },
                'Delete Selected',
                'bg-red-600'
            );
        });
    }

    contentArea.addEventListener('click', function(event) {
        const target = event.target.closest('button');
        if (!target) return;

        if (target.classList.contains('view-details-btn')) {
            showBookingDetails(target.dataset.bookingId);
        }
        if (target.classList.contains('back-to-list-btn')) {
            hideBookingDetails();
        }

        // Toggle filter section visibility on mobile
        if (target.id === 'toggle-filters-btn') {
            const filterOptionsSection = document.getElementById('filter-options-section');
            if (filterOptionsSection) {
                filterOptionsSection.classList.toggle('hidden');
                filterOptionsSection.classList.toggle('flex'); // Also toggle flex for layout
                // On toggle, if showing, remove block from filter inputs if present and ensure full width
                if (filterOptionsSection.classList.contains('flex')) {
                    const dateInputs = filterOptionsSection.querySelectorAll('input[type="date"]');
                    dateInputs.forEach(input => {
                        input.classList.add('w-full', 'flex-grow'); // Ensure they take full width
                    });
                }
            }
        }
        
        const bookingId = target.dataset.bookingId;

        if (target.classList.contains('request-relocation-btn')) {
            const isIncluded = target.dataset.isIncluded === 'true';
            const charge = parseFloat(target.dataset.charge || '0').toFixed(2);
            document.getElementById('relocation-booking-id').value = bookingId;
            
            // Target the specific paragraph by its new ID
            const chargeTextParagraph = document.getElementById('relocation-charge-text');
            const relocationForm = document.getElementById('relocation-form'); // Get form reference
            const submitButton = relocationForm.querySelector('button[type="submit"]');

            if (isIncluded) {
                chargeTextParagraph.innerHTML = `This service is included in your original quote.`;
                submitButton.textContent = 'Submit Request';
                submitButton.classList.remove('bg-indigo-600', 'hover:bg-indigo-700');
                submitButton.classList.add('bg-green-600', 'hover:bg-green-600');
            } else {
                chargeTextParagraph.innerHTML = `A one-time charge for this service will be applied: <span class="font-bold text-blue-600">$${charge}</span>`;
                submitButton.textContent = 'Proceed to Payment';
                submitButton.classList.remove('bg-green-600', 'hover:bg-green-700');
                submitButton.classList.add('bg-indigo-600', 'hover:bg-indigo-700');
            }

            window.showModal('relocation-request-modal');
        }
        if (target.classList.contains('request-swap-btn')) {
            const isIncluded = target.dataset.isIncluded === 'true';
            const charge = parseFloat(target.dataset.charge || '0').toFixed(2);
            document.getElementById('swap-booking-id').value = bookingId;

            // Target the specific paragraph by its new ID
            const chargeTextParagraph = document.getElementById('swap-charge-text');
            const swapForm = document.getElementById('swap-form'); // Get form reference
            const submitButton = swapForm.querySelector('button[type="submit"]');

            if (isIncluded) {
                chargeTextParagraph.innerHTML = `This service is included in your original quote. Are you sure you want to proceed with the swap?`;
                submitButton.textContent = 'Submit Request';
                submitButton.classList.remove('bg-purple-600', 'hover:bg-purple-700');
                submitButton.classList.add('bg-green-600', 'hover:bg-green-600');
            } else {
                chargeTextParagraph.innerHTML = `A one-time charge of <span class="font-bold text-purple-600">$${charge}</span> will be applied to swap your equipment. Are you sure you want to proceed?`;
                submitButton.textContent = 'Yes, Proceed to Payment';
                submitButton.classList.remove('bg-green-600', 'hover:bg-green-700');
                submitButton.classList.add('bg-purple-600', 'hover:bg-purple-700');
            }

            window.showModal('swap-request-modal');
        }
        if (target.classList.contains('schedule-pickup-btn')) {
            document.getElementById('pickup-booking-id').value = bookingId;
            // Set min date for pickup-date input
            const today = new Date().toISOString().split('T')[0];
            document.getElementById('pickup-date').min = today;
            window.showModal('pickup-request-modal');
        }
        if (target.classList.contains('request-extension-btn')) {
            const dailyRate = parseFloat(target.dataset.dailyRate || '0');
            if(dailyRate <= 0){
                window.showToast('Extension is not available for this item as a daily rate is not set. Please contact support.', 'error');
                return;
            }
            document.getElementById('extension-booking-id').value = bookingId;
            document.getElementById('extension-daily-rate-display').textContent = `$${dailyRate.toFixed(2)}`;
            const extensionDaysInput = document.getElementById('extension-days');
            extensionDaysInput.value = 1; // Default to 1 day
            document.getElementById('extension-cost-display').textContent = `$${dailyRate.toFixed(2)}`;
            window.showModal('extension-request-modal');
        }
    });

    // Cost calculation for extension modal
    const extensionDaysInput = document.getElementById('extension-days');
    if(extensionDaysInput){
        extensionDaysInput.addEventListener('input', function() {
            const days = parseInt(this.value) || 0;
            const dailyRateString = document.getElementById('extension-daily-rate-display').textContent.replace('$', '');
            const dailyRate = parseFloat(dailyRateString || '0');
            const totalCost = days * dailyRate;
            document.getElementById('extension-cost-display').textContent = `$${totalCost.toFixed(2)}`;
        });
    }

    // Attach form submission listeners using delegation or direct attachment after content load
    const extensionForm = document.getElementById('extension-form');
    if(extensionForm) extensionForm.addEventListener('submit', function(e) { e.preventDefault(); callBookingApi(this); });
    
    const relocationForm = document.getElementById('relocation-form');
    if(relocationForm) relocationForm.addEventListener('submit', function(e) { e.preventDefault(); callBookingApi(this); });
    
    const swapForm = document.getElementById('swap-form');
    if(swapForm) swapForm.addEventListener('submit', function(e) { e.preventDefault(); callBookingApi(this); });
    
    const pickupForm = document.getElementById('pickup-form');
    if(pickupForm) pickupForm.addEventListener('submit', function(e) { e.preventDefault(); callBookingApi(this); });

    // Initial check for bulk delete button visibility on page load
    document.addEventListener('DOMContentLoaded', toggleBulkDeleteButtonVisibility);


})();
</script>