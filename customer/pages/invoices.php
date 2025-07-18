<?php
// customer/pages/invoices.php

// Ensure session is started and user is logged in
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/db.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/session.php'; // For has_role and user_id
require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/functions.php'; // For CSRF token

if (!is_logged_in()) {
    echo '<div class="text-red-500 text-center p-8">You must be logged in to view this content.</div>';
    exit;
}

if (empty($_SESSION['csrf_token'])) {
    generate_csrf_token();
}
$csrf_token = $_SESSION['csrf_token'];

$user_id = $_SESSION['user_id'];
$invoices = [];
$invoice_detail = null; // To hold data for a single invoice detail view if requested

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

// Check if a specific invoice ID is requested for detail view
$requested_invoice_id = $_GET['invoice_id'] ?? null;
$requested_quote_id = $_GET['quote_id'] ?? null; // For direct link from pending quotes

// If a specific invoice number is requested, fetch its details
if ($requested_invoice_id || $requested_quote_id) {
    $sql = "SELECT
                i.id AS id,
                i.invoice_number AS invoice_number,
                i.amount AS amount,
                i.status AS status,
                i.created_at AS created_at,
                i.due_date AS due_date,
                i.transaction_id AS transaction_id,
                i.payment_method AS payment_method,
                i.discount AS discount,
                i.tax AS tax,
                i.booking_id AS booking_id,
                u.first_name AS first_name,
                u.last_name AS last_name,
                u.email AS email,
                u.address AS address,
                u.city AS city,
                u.state AS state,
                u.zip_code AS zip_code
            FROM invoices i
            JOIN users u ON i.user_id = u.id
            WHERE i.user_id = ?";

    if ($requested_invoice_id) {
        $sql .= " AND i.id = ?";
        $stmt_detail = $conn->prepare($sql);
        $stmt_detail->bind_param("ii", $user_id, $requested_invoice_id);
    } else { // requested_quote_id
        $sql .= " AND i.quote_id = ?";
        $stmt_detail = $conn->prepare($sql);
        $stmt_detail->bind_param("ii", $user_id, $requested_quote_id);
    }

    $stmt_detail->execute();
    $result_detail = $stmt_detail->get_result();
    if ($result_detail->num_rows > 0) {
        $invoice_detail = $result_detail->fetch_assoc();

        // Fetch line items
        $invoice_detail['items'] = [];
        $stmt_items = $conn->prepare("SELECT * FROM invoice_items WHERE invoice_id = ? ORDER BY id ASC");
        $stmt_items->bind_param("i", $invoice_detail['id']);
        $stmt_items->execute();
        $result_items = $stmt_items->get_result();
        while($item_row = $result_items->fetch_assoc()){
            $invoice_detail['items'][] = $item_row;
        }
        $stmt_items->close();

        // Fetch booking start and end dates if a booking_id is associated with the invoice
        if (!empty($invoice_detail['booking_id'])) {
            $stmt_booking_dates = $conn->prepare("SELECT start_date, end_date FROM bookings WHERE id = ?");
            $stmt_booking_dates->bind_param("i", $invoice_detail['booking_id']);
            $stmt_booking_dates->execute();
            $booking_dates = $stmt_booking_dates->get_result()->fetch_assoc();
            $stmt_booking_dates->close();
            if ($booking_dates) {
                $invoice_detail['booking_start_date'] = $booking_dates['start_date'];
                $invoice_detail['booking_end_date'] = $booking_dates['end_date'];
            }
        }

    }
    $stmt_detail->close();
} else {
    // --- Fetch all invoices for the list view with Filters, Search, and Pagination ---
    $base_query = "
        FROM invoices i
        JOIN users u ON i.user_id = u.id
        WHERE i.user_id = ?
    ";

    $where_clauses = [];
    $params = [$user_id];
    $types = "i";

    // Status Filter
    if ($filter_status !== 'all') {
        $where_clauses[] = "i.status = ?";
        $params[] = $filter_status;
        $types .= "s";
    }

    // Search Query (Invoice Number or User Address)
    if (!empty($search_query)) {
        $search_term = '%' . $search_query . '%';
        $where_clauses[] = "(i.invoice_number LIKE ? OR u.first_name LIKE ? OR u.last_name LIKE ?)";
        array_push($params, $search_term, $search_term, $search_term);
        $types .= "sss";
    }

    // Date Range Filter
    if (!empty($start_date_filter)) {
        $where_clauses[] = "DATE(i.created_at) >= ?";
        $params[] = $start_date_filter;
        $types .= "s";
    }
    if (!empty($end_date_filter)) {
        $where_clauses[] = "DATE(i.created_at) <= ?";
        $params[] = $end_date_filter;
        $types .= "s";
    }

    $where_sql = '';
    if (!empty($where_clauses)) {
        $where_sql = " AND " . implode(" AND ", $where_clauses);
    }

    // Get total count for pagination
    $stmt_count = $conn->prepare("SELECT COUNT(*) " . $base_query . $where_sql);
    if (!empty($params)) {
        $stmt_count->bind_param($types, ...$params);
    }
    $stmt_count->execute();
    $total_invoices_count = $stmt_count->get_result()->fetch_assoc()['COUNT(*)'];
    $stmt_count->close();

    $total_pages = ceil($total_invoices_count / $items_per_page);

    // Main query for invoices list - FIX: Qualify ambiguous columns and add user names
    $list_query = "
        SELECT i.id, i.invoice_number, i.amount, i.status, i.created_at, i.due_date, u.first_name, u.last_name
    " . $base_query . $where_sql . "
    ORDER BY i.created_at DESC
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
        $invoices[] = $row;
    }
    $stmt_list->close();
}

// Fetch the default payment method
$default_payment_method = null;
$stmt_default_method = $conn->prepare("SELECT id, card_type, last_four FROM user_payment_methods WHERE user_id = ? AND is_default = TRUE");
$stmt_default_method->bind_param("i", $user_id);
$stmt_default_method->execute();
$result_default_method = $stmt_default_method->get_result();
if ($result_default_method->num_rows > 0) {
    $default_payment_method = $result_default_method->fetch_assoc();
}
$stmt_default_method->close();


$conn->close();
generate_csrf_token();

// --- Helper Functions ---
function getStatusBadgeClass($status) {
    switch ($status) {
        case 'paid':
            return 'bg-green-100 text-green-800';
        case 'partially_paid':
            return 'bg-yellow-100 text-yellow-800';
        case 'pending':
            return 'bg-red-100 text-red-800';
        case 'cancelled':
            return 'bg-gray-100 text-gray-800';
        default:
            return 'bg-gray-200 text-gray-800';
    }
}
?>


<div id="invoice-list-view" class="<?php echo $invoice_detail ? 'hidden' : ''; ?>">
    <h1 class="text-3xl font-bold text-gray-800 mb-8">Invoices</h1>
    <div class="bg-white p-6 rounded-lg shadow-md border border-blue-200">
        <div class="flex flex-col sm:flex-row justify-between items-center mb-4 gap-3">
            <h2 class="text-xl font-semibold text-gray-700 flex-grow"><i class="fas fa-file-invoice-dollar mr-2 text-blue-600"></i>Your Invoices</h2>
        </div>

        <div class="mb-4 flex flex-col sm:flex-row items-center justify-between gap-3">
            <div class="flex-grow w-full sm:w-auto flex items-center gap-2">
                <input type="text" id="search-input" placeholder="Search invoice # or customer name..."
                       class="p-2 border border-gray-300 rounded-md w-full text-sm"
                       value="<?php echo htmlspecialchars($search_query); ?>"
                       onkeydown="if(event.key === 'Enter') window.applyFilters()">
                <button id="toggle-filters-btn" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors duration-200 shadow-md md:hidden">
                    <i class="fas fa-filter"></i>
                </button>
                 <button id="apply-filters-btn" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors duration-200 shadow-md hidden md:block" onclick="window.applyFilters()">
                    <i class="fas fa-search"></i>
                </button>
            </div>

            <div id="filter-options-section" class="flex-col sm:flex-row gap-3 w-full md:flex hidden">
                <div class="flex items-center gap-2 flex-grow">
                    <label for="status-filter" class="text-sm font-medium text-gray-700">Status:</label>
                    <select id="status-filter" class="p-2 border border-gray-300 rounded-md text-sm flex-grow" onchange="window.applyFilters()">
                        <option value="all" <?php echo $filter_status === 'all' ? 'selected' : ''; ?>>All</option>
                        <option value="pending" <?php echo $filter_status === 'pending' ? 'selected' : ''; ?>>Pending</option>
                        <option value="paid" <?php echo $filter_status === 'paid' ? 'selected' : ''; ?>>Paid</option>
                        <option value="partially_paid" <?php echo $filter_status === 'partially_paid' ? 'selected' : ''; ?>>Partially Paid</option>
                        <option value="cancelled" <?php echo $filter_status === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                    </select>
                </div>
                <div class="flex items-center gap-2 w-full sm:w-auto">
                    <label for="start-date-filter" class="text-sm font-medium text-gray-700">From:</label>
                    <input type="date" id="start-date-filter" value="<?php echo htmlspecialchars($start_date_filter); ?>"
                           class="p-2 border border-gray-300 rounded-md text-sm w-full flex-grow" onchange="window.applyFilters()">
                    <label for="end-date-filter" class="text-sm font-medium text-gray-700">To:</label>
                    <input type="date" id="end-date-filter" value="<?php echo htmlspecialchars($end_date_filter); ?>"
                           class="p-2 border border-gray-300 rounded-md text-sm w-full flex-grow" onchange="window.applyFilters()">
                </div>
                <button onclick="window.applyFilters()" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors duration-200 shadow-md w-full sm:w-auto md:hidden">
                    Apply Filters
                </button>
            </div>
        </div>
        <div class="flex justify-end mb-4">
             <button id="bulk-delete-invoices-btn" class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors duration-200 shadow-md hidden md:inline-flex items-center">
                <i class="fas fa-trash-alt mr-2"></i>Delete Selected
            </button>
        </div>

        <?php if (empty($invoices)): ?>
            <p class="text-center text-gray-500 py-4">No invoices found for the selected filters or search query.</p>
        <?php else: ?>
            <div class="hidden md:block overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                     <thead class="bg-blue-50">
                        <tr>
                            <th class="px-6 py-3 text-left">
                                <input type="checkbox" id="select-all-invoices" class="h-4 w-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Invoice ID</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Customer</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Date</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Amount</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Status</th>
                            <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-700 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($invoices as $invoice): ?>
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <input type="checkbox" class="invoice-checkbox h-4 w-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500" value="<?php echo htmlspecialchars($invoice['id']); ?>">
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900"><?php echo htmlspecialchars($invoice['invoice_number']); ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo htmlspecialchars($invoice['first_name'] . ' ' . $invoice['last_name']); ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo (new DateTime($invoice['created_at']))->format('Y-m-d'); ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">$<?php echo number_format($invoice['amount'], 2); ?></td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo getStatusBadgeClass($invoice['status']); ?>"><?php echo htmlspecialchars(strtoupper(str_replace('_', ' ', (string)($invoice['status'] ?? '')))); ?></span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-right">
                                    <button class="text-blue-600 hover:text-blue-900 view-invoice-details" data-invoice-id="<?php echo htmlspecialchars($invoice['id']); ?>">View</button>
                                    <?php if ($invoice['status'] == 'pending' || $invoice['status'] == 'partially_paid'): ?>
                                        <button class="ml-3 text-green-600 hover:text-green-900 pay-invoice-btn" data-invoice-id="<?php echo htmlspecialchars($invoice['id']); ?>" data-invoice-number="<?php echo htmlspecialchars($invoice['invoice_number']); ?>" data-amount="<?php echo htmlspecialchars($invoice['amount']); ?>">Pay Now</button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div class="md:hidden space-y-4">
                <?php foreach ($invoices as $invoice): ?>
                    <div class="bg-white rounded-lg shadow-md border border-blue-200 p-4 relative">
                        <div class="absolute top-3 right-3 flex space-x-2">
                            <input type="checkbox" class="invoice-checkbox h-4 w-4" value="<?php echo htmlspecialchars($invoice['id']); ?>">
                        </div>
                        <div class="mb-2">
                            <p class="text-sm font-bold text-gray-800">Invoice ID: #<?php echo htmlspecialchars($invoice['invoice_number']); ?></p>
                            <p class="text-xs text-gray-600">Customer: <?php echo htmlspecialchars($invoice['first_name'] . ' ' . $invoice['last_name']); ?></p>
                            <p class="text-xs text-gray-600">Date: <?php echo (new DateTime($invoice['created_at']))->format('Y-m-d'); ?></p>
                        </div>
                        <div class="border-t border-b border-gray-200 py-2 mb-2">
                            <p class="text-lg font-bold text-gray-700"><span class="font-medium">Amount:</span> $<?php echo number_format($invoice['amount'], 2); ?></p>
                            <p class="text-sm text-gray-700"><span class="font-medium">Status:</span>
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo getStatusBadgeClass($invoice['status']); ?>">
                                    <?php echo htmlspecialchars(strtoupper(str_replace('_', ' ', (string)($invoice['status'] ?? '')))); ?>
                                </span>
                            </p>
                        </div>
                        <div class="flex flex-wrap gap-2 justify-end mt-3">
                            <button class="px-3 py-1 bg-blue-100 text-blue-700 rounded-md hover:bg-blue-200 text-xs view-invoice-details" data-invoice-id="<?php echo htmlspecialchars($invoice['id']); ?>">
                                <i class="fas fa-eye mr-1"></i>View
                            </button>
                            <?php if ($invoice['status'] == 'pending' || $invoice['status'] == 'partially_paid'): ?>
                                <button class="px-3 py-1 bg-green-100 text-green-700 rounded-md hover:bg-green-200 text-xs pay-invoice-btn" data-invoice-id="<?php echo htmlspecialchars($invoice['id']); ?>" data-invoice-number="<?php echo htmlspecialchars($invoice['invoice_number']); ?>" data-amount="<?php echo htmlspecialchars($invoice['amount']); ?>">
                                    <i class="fas fa-dollar-sign mr-1"></i>Pay Now
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <nav class="mt-4 flex flex-col sm:flex-row items-center justify-between gap-3">
                <div>
                    <p class="text-sm text-gray-700">
                        Showing <span class="font-medium"><?php echo $offset + 1; ?></span> to
                        <span class="font-medium"><?php echo min($offset + $items_per_page, $total_invoices_count); ?></span> of
                        <span class="font-medium"><?php echo $total_invoices_count; ?></span> results
                    </p>
                </div>
                <div class="flex items-center gap-2">
                    <span class="text-sm font-medium text-gray-700">Invoices per page:</span>
                    <select id="items-per-page-select" onchange="window.applyFilters({page: 1, per_page: this.value})"
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
                        <button onclick="window.applyFilters({page: <?php echo max(1, $current_page - 1); ?>})"
                                class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
                            <span class="sr-only">Previous</span>
                            <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                <path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd" />
                            </svg>
                        </button>
                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                            <button onclick="window.applyFilters({page: <?php echo $i; ?>})"
                                    class="<?php echo $i == $current_page ? 'z-10 bg-blue-50 border-blue-500 text-blue-600' : 'bg-white border-gray-300 text-gray-700 hover:bg-gray-50'; ?> relative inline-flex items-center px-4 py-2 border text-sm font-medium">
                                <?php echo $i; ?>
                            </button>
                        <?php endfor; ?>
                        <button onclick="window.applyFilters({page: <?php echo min($total_pages, $current_page + 1); ?>})"
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

<div id="invoice-detail-view" class="bg-white p-6 rounded-lg shadow-md border border-blue-200 mt-8 <?php echo $invoice_detail ? '' : 'hidden'; ?>">
    <button class="mb-4 px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300" onclick="window.hideInvoiceDetails()">
        <i class="fas fa-arrow-left mr-2"></i>Back to Invoices
    </button>
    <?php if ($invoice_detail): ?>
        <div class="flex justify-between items-start">
            <h2 class="text-2xl font-bold text-gray-800 mb-6" id="detail-invoice-number">Invoice Details for #<?php echo htmlspecialchars($invoice_detail['invoice_number']); ?></h2>
            <a href="/api/customer/download.php?type=invoice&id=<?php echo htmlspecialchars($invoice_detail['id']); ?>" target="_blank" class="px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700 text-sm">
                <i class="fas fa-file-pdf mr-2"></i>Download PDF
            </a>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
            <div>
                <p class="text-gray-600"><span class="font-medium">Invoice Date:</span> <span id="detail-invoice-date"><?php echo (new DateTime($invoice_detail['created_at']))->format('Y-m-d'); ?></span></p>
                <p class="text-gray-600"><span class="font-medium">Due Date:</span> <?php echo $invoice_detail['due_date'] ? (new DateTime($invoice_detail['due_date']))->format('Y-m-d') : 'N/A'; ?></p>
                <p class="text-gray-600"><span class="font-medium">Status:</span> <span id="detail-invoice-status" class="font-semibold <?php echo getStatusBadgeClass($invoice_detail['status']); ?>"><?php echo htmlspecialchars(strtoupper(str_replace('_', ' ', (string)($invoice_detail['status'] ?? '')))); ?></span></p>
                <p class="text-gray-600"><span class="font-medium">Transaction ID:</span> <?php echo htmlspecialchars($invoice_detail['transaction_id'] ?? 'N/A'); ?></p>
                <p class="text-gray-600"><span class="font-medium">Payment Method:</span> <?php echo htmlspecialchars($invoice_detail['payment_method'] ?? 'N/A'); ?></p>
                <?php if (!empty($invoice_detail['booking_start_date'])): ?>
                    <p class="text-gray-600"><span class="font-medium">Rental Start Date:</span> <?php echo (new DateTime($invoice_detail['booking_start_date']))->format('Y-m-d'); ?></p>
                <?php endif; ?>
                <?php if (!empty($invoice_detail['booking_end_date'])): ?>
                    <p class="text-gray-600"><span class="font-medium">Rental End Date:</span> <?php echo (new DateTime($invoice_detail['booking_end_date']))->format('Y-m-d'); ?></p>
                <?php endif; ?>
            </div>
            <div>
                <p class="text-gray-600"><span class="font-medium">Billed To:</span> <?php echo htmlspecialchars($invoice_detail['first_name'] . ' ' . $invoice_detail['last_name']); ?></p>
                <p class="text-gray-600"><span class="font-medium">Address:</span> <?php echo htmlspecialchars($invoice_detail['address'] . ', ' . $invoice_detail['city'] . ', ' . $invoice_detail['state'] . ' ' . $invoice_detail['zip_code']); ?></p>
                <p class="text-gray-600"><span class="font-medium">Email:</span> <?php echo htmlspecialchars($invoice_detail['email']); ?></p>
            </div>
        </div>

        <h3 class="text-xl font-semibold text-gray-700 mb-4">Items</h3>
        <div class="overflow-x-auto mb-6">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-blue-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Description</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Quantity</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Unit Price</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Total</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php
                    $subtotal = 0;
                    if(!empty($invoice_detail['items'])) {
                        foreach ($invoice_detail['items'] as $item){
                            $subtotal += $item['total'];
                            echo '<tr>';
                            echo '<td class="px-6 py-4 text-sm text-gray-900">' . htmlspecialchars($item['description']) . '</td>';
                            echo '<td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">' . htmlspecialchars($item['quantity']) . '</td>';
                            echo '<td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">$' . number_format($item['unit_price'], 2) . '</td>';
                            echo '<td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">$' . number_format($item['total'], 2) . '</td>';
                            echo '</tr>';
                        }
                    }
                    ?>
                </tbody>
            </table>
        </div>

        <div class="flex justify-end mt-4">
            <div class="w-full md:w-1/2 space-y-2 text-gray-700">
                <div class="flex justify-between"><span class="font-medium">Subtotal:</span> <span>$<?php echo number_format($subtotal, 2); ?></span></div>
                <div class="flex justify-between text-red-500"><span class="font-medium">Discount:</span> <span>-$<?php echo number_format($invoice_detail['discount'], 2); ?></span></div>
                <div class="flex justify-between"><span class="font-medium">Tax:</span> <span>$<?php echo number_format($invoice_detail['tax'], 2); ?></span></div>
                <div class="flex justify-between text-xl font-bold border-t pt-2 border-gray-300"><span class="font-medium">Grand Total:</span> <span id="detail-grand-total-display" class="text-blue-700">$<?php echo number_format($invoice_detail['amount'], 2); ?></span></div>
            </div>
        </div>

<div id="payment-actions" class="flex justify-end mt-6">
    <?php if ($invoice_detail['status'] == 'pending' || $invoice_detail['status'] == 'partially_paid'): ?>
        <button class="py-2 px-5 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors duration-200 show-payment-form-btn pay-now-detail-btn"
                data-invoice-id="<?php echo htmlspecialchars($invoice_detail['id']); ?>"
                data-invoice-number="<?php echo htmlspecialchars($invoice_detail['invoice_number']); ?>"
                data-amount="<?php echo htmlspecialchars(number_format((float)($invoice_detail['amount'] ?? 0), 2, '.', '')); ?>">
            <i class="fas fa-dollar-sign mr-2"></i>Pay Now
        </button>
    <?php endif; ?>
</div>
    <?php else: ?>
        <p class="text-center text-gray-600 py-8">Invoice details not found or invalid invoice ID.</p>
    <?php endif; ?>
</div>

<div id="payment-form-view" class="bg-white p-6 rounded-lg shadow-md border border-blue-200 mt-8 hidden">
    <button class="mb-4 px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 back-to-details-btn" onclick="window.hideInvoiceDetails()"><i class="fas fa-arrow-left mr-2"></i>Back to Invoice</button>
    <h2 class="text-2xl font-bold text-gray-800 mb-6">Pay Invoice <span id="payment-invoice-id-display"></span></h2>
    <form id="payment-form">
        <input type="hidden" name="invoice_id" id="payment-form-invoice-id">
        <input type="hidden" name="invoice_number" id="payment-form-invoice-number">
        <input type="hidden" name="amount" id="payment-form-amount">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
        <input type="hidden" id="original-payment-method-id-hidden" name="original_payment_method_id" value="<?php echo htmlspecialchars($default_payment_method['id'] ?? ''); ?>">

        <?php if ($default_payment_method): ?>
            <div class="mb-5 p-4 bg-blue-50 border border-blue-200 rounded-lg">
                <p class="text-sm font-medium text-gray-700 mb-2">Default Payment Method:</p>
                <div class="flex items-center">
                    <i class="fab fa-cc-<?php echo strtolower(htmlspecialchars($default_payment_method['card_type'])); ?> text-2xl mr-3 text-blue-600"></i>
                    <div>
                        <p class="font-semibold text-gray-800"><?php echo htmlspecialchars($default_payment_method['card_type']); ?> ending in **** <?php echo htmlspecialchars($default_payment_method['last_four']); ?></p>
                        <a href="#" onclick="loadCustomerSection('payment-methods')" class="text-xs text-blue-600 hover:underline">Change default card</a>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <div class="mb-5 p-4 bg-yellow-50 border border-yellow-300 rounded-lg text-yellow-800 text-sm">
                <i class="fas fa-exclamation-triangle mr-2"></i>
                You have no default payment method set up. Please <a href="#" onclick="loadCustomerSection('payment-methods')" class="font-bold underline">add a payment method</a> to proceed.
            </div>
        <?php endif; ?>

        <div class="mb-5">
            <label for="payment-amount" class="block text-sm font-medium text-gray-700 mb-2">Amount to Pay</label>
            <input type="number" id="payment-amount" class="w-full p-3 border border-gray-300 rounded-lg bg-gray-100" step="0.01" value="0.00" readonly>
        </div>
        <button type="submit" id="submit-payment-btn" class="w-full py-3 px-4 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors duration-200 shadow-lg font-semibold <?php echo !$default_payment_method ? 'opacity-50 cursor-not-allowed' : ''; ?>" <?php echo !$default_payment_method ? 'disabled' : ''; ?>>
            <i class="fas fa-dollar-sign mr-2"></i>Confirm Payment
        </button>
    </form>
</div>

<script>
(function() {
    window.loadCustomerInvoices = function(params = {}) {
        const currentParams = new URLSearchParams(window.location.hash.split('?')[1] || '');
        const newParams = {
            page: params.page || (currentParams.get('page') ? parseInt(currentParams.get('page')) : 1),
            per_page: params.per_page || (currentParams.get('per_page') ? parseInt(currentParams.get('per_page')) : 25),
            status: params.status || currentParams.get('status') || 'all',
            search: params.search || '',
            start_date: currentParams.get('start_date') || '',
            end_date: currentParams.get('end_date') || '',
            ...params
        };
        window.loadCustomerSection('invoices', newParams);
    };

    window.showInvoiceDetails = function(invoiceId) {
        window.loadCustomerInvoices({ invoice_id: invoiceId });
    };

    window.hideInvoiceDetails = function() {
        window.loadCustomerInvoices({});
    };

    window.showPaymentForm = function(invoiceId, invoiceNumber, amountFromCallSite) {
        document.getElementById('payment-form').reset();
        
        document.getElementById('payment-invoice-id-display').textContent = `#${invoiceNumber}`;
        document.getElementById('payment-form-invoice-id').value = invoiceId;
        document.getElementById('payment-form-invoice-number').value = invoiceNumber;
        const parsedAmount = parseFloat(amountFromCallSite);
        if (!isNaN(parsedAmount) && parsedAmount >= 0) {
            const formattedAmount = parsedAmount.toFixed(2);
            document.getElementById('payment-form-amount').value = formattedAmount;
            document.getElementById('payment-amount').value = formattedAmount;
        }

        document.getElementById('invoice-list-view').classList.add('hidden');
        document.getElementById('invoice-detail-view').classList.add('hidden');
        document.getElementById('payment-form-view').classList.remove('hidden');
    };
    
    async function handlePaymentFormSubmit(event) {
        event.preventDefault();
        const form = event.target;
        const formData = new FormData(form);
        formData.append('action', 'pay_invoice');
        
        window.showToast('Processing payment...', 'info');

        try {
            const response = await fetch('/api/customer/invoices.php', { method: 'POST', body: formData });
            const result = await response.json();
            if (result.success) {
                window.showToast('Payment successful!', 'success');
                window.loadCustomerInvoices({ invoice_id: formData.get('invoice_id') });
            } else {
                window.showToast(result.message, 'error');
            }
        } catch (error) {
            window.showToast('Payment error. Please try again.', 'error');
            console.error(error);
        }
    }

    window.applyFilters = function(params = {}) {
        const page = params.page || 1;
        window.loadCustomerInvoices({
            page,
            per_page: document.getElementById('items-per-page-select') ? document.getElementById('items-per-page-select').value : 25,
            status: document.getElementById('status-filter') ? document.getElementById('status-filter').value : 'all',
            search: document.getElementById('search-input') ? document.getElementById('search-input').value : '',
            start_date: document.getElementById('start-date-filter') ? document.getElementById('start-date-filter').value : '',
            end_date: document.getElementById('end-date-filter') ? document.getElementById('end-date-filter').value : ''
        });
    }

    document.body.addEventListener('click', function(event) {
        const payButton = event.target.closest('.pay-invoice-btn, .pay-now-detail-btn');
        if (payButton) {
            const invoiceId = payButton.dataset.invoiceId;
            const invoiceNumber = payButton.dataset.invoiceNumber;
            const amount = payButton.dataset.amount;
            window.showPaymentForm(invoiceId, invoiceNumber, amount);
            return;
        }

        const viewButton = event.target.closest('.view-invoice-details');
        if (viewButton) {
            window.showInvoiceDetails(viewButton.dataset.invoiceId);
            return;
        }
    });

    const paymentForm = document.getElementById('payment-form');
    if (paymentForm) {
        paymentForm.addEventListener('submit', handlePaymentFormSubmit);
    }
})();
</script>