<?php
// admin/pages/junk_removal.php

// --- Setup & Includes ---
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../includes/functions.php'; // Required for generate_csrf_token()

if (!is_logged_in() || !has_role('admin')) {
    echo '<div class="text-red-500 text-center p-8">Unauthorized access.</div>';
    exit;
}

// Generate CSRF token for this page
generate_csrf_token();
$csrf_token = $_SESSION['csrf_token'];


$requests = []; // Renamed from $junk_removal_requests for clarity of content
$request_detail_view_data = null; // Data for single request view

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
$start_date_filter = $_GET['start_date'] ?? ''; // New: Date range filter start date
$end_date_filter = $_GET['end_date'] ?? '';   // New: Date range filter end date


$requested_request_id = filter_input(INPUT_GET, 'request_id', FILTER_VALIDATE_INT);

// Fetch a single request's details if an ID is provided
if ($requested_request_id) {
    $stmt_detail = $conn->prepare("
        SELECT
            q.id, q.status, q.created_at, q.location, q.removal_date, q.removal_time,
            q.live_load_needed, q.is_urgent, q.driver_instructions, q.quoted_price AS estimated_price,
            u.id as user_id, u.first_name, u.last_name, u.email, u.phone_number,
            jrd.junk_items_json, jrd.recommended_dumpster_size, jrd.additional_comment, jrd.media_urls_json
        FROM quotes q
        JOIN users u ON q.user_id = u.id
        LEFT JOIN junk_removal_details jrd ON q.id = jrd.quote_id
        WHERE q.id = ? AND q.service_type = 'junk_removal'
    ");
    $stmt_detail->bind_param("i", $requested_request_id);
    $stmt_detail->execute();
    $result_detail = $stmt_detail->get_result();
    if ($result_detail->num_rows > 0) {
        $request_detail_view_data = $result_detail->fetch_assoc();
        $request_detail_view_data['junk_items_json'] = json_decode($request_detail_view_data['junk_items_json'] ?? '[]', true);
        $request_detail_view_data['media_urls_json'] = json_decode($request_detail_view_data['media_urls_json'] ?? '[]', true);

        // Mark as viewed by admin (this column is in the quotes table)
        $stmt_mark_viewed = $conn->prepare("UPDATE quotes SET is_viewed_by_admin = 1 WHERE id = ?");
        $stmt_mark_viewed->bind_param("i", $requested_request_id);
        $stmt_mark_viewed->execute();
        $stmt_mark_viewed->close();

    }
    $stmt_detail->close();
} else {
    // --- Fetch all junk removal requests for the list view with Filters, Search, and Pagination ---
    $base_query = "
        FROM quotes q
        JOIN users u ON q.user_id = u.id
        LEFT JOIN junk_removal_details jrd ON q.id = jrd.quote_id
        WHERE q.service_type = 'junk_removal'
    ";

    $where_clauses = [];
    $params = [];
    $types = "";

    // Status Filter
    if ($filter_status !== 'all') {
        $where_clauses[] = "q.status = ?";
        $params[] = $filter_status;
        $types .= "s";
    }

    // Search Query (Request ID, Customer Name, Location)
    if (!empty($search_query)) {
        $search_term = '%' . $search_query . '%';
        // Searching q.id as string for LIKE comparison (can be problematic if IDs are purely numeric)
        // Better: cast q.id to CHAR or use = for exact match if ID is always exact search.
        // For LIKE, casting to CHAR might be needed if q.id is int and you search 'JR123'
        $where_clauses[] = "(CAST(q.id AS CHAR) LIKE ? OR u.first_name LIKE ? OR u.last_name LIKE ? OR q.location LIKE ?)";
        $params[] = $search_term;
        $params[] = $search_term;
        $params[] = $search_term;
        $params[] = $search_term;
        $types .= "ssss";
    }

    // Date Range Filter
    if (!empty($start_date_filter)) {
        $where_clauses[] = "DATE(q.created_at) >= ?";
        $params[] = $start_date_filter;
        $types .= "s";
    }
    if (!empty($end_date_filter)) {
        $where_clauses[] = "DATE(q.created_at) <= ?";
        $params[] = $end_date_filter;
        $types .= "s";
    }

    $where_sql = '';
    if (!empty($where_clauses)) {
        $where_sql = " AND " . implode(" AND ", $where_clauses); // Note: AND since base query already has WHERE
    }

    // Get total count for pagination
    $stmt_count = $conn->prepare("SELECT COUNT(*) " . $base_query . $where_sql);
    if (!empty($params)) {
        $stmt_count->bind_param($types, ...$params);
    }
    $stmt_count->execute();
    $total_requests_count = $stmt_count->get_result()->fetch_assoc()['COUNT(*)'];
    $stmt_count->close();

    $total_pages = ceil($total_requests_count / $items_per_page);

    // Main query for requests list
    $list_query = "
        SELECT
            q.id, q.location, q.removal_date AS pickup_date, q.status, q.quoted_price AS estimated_price, q.is_viewed_by_admin,
            u.first_name, u.last_name
    " . $base_query . $where_sql . "
    ORDER BY q.created_at DESC
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
        $requests[] = $row;
    }
    $stmt_list->close();
}

$conn->close();

// Helper function for status badges
function getJunkRemovalStatusBadgeClass($status) {
    switch ($status) {
        case 'pending': return 'bg-yellow-100 text-yellow-800';
        case 'quoted': return 'bg-blue-100 text-blue-800';
        case 'accepted': return 'bg-green-100 text-green-800'; // Assuming accepted might be a status for JR
        case 'scheduled': return 'bg-purple-100 text-purple-800';
        case 'completed': return 'bg-gray-100 text-gray-800'; // Changed to gray for completed
        case 'cancelled': return 'bg-red-100 text-red-800';
        default: return 'bg-gray-100 text-gray-700'; // For unknown or draft statuses
    }
}
?>

<div id="junk-removal-list-section" class="<?php echo $request_detail_view_data ? 'hidden' : ''; ?>">
    <h1 class="text-3xl font-bold text-gray-800 mb-8">Junk Removal Requests</h1>
    <div class="bg-white p-6 rounded-lg shadow-md border border-blue-200">
        <div class="flex justify-between items-center mb-4">
            <h2 class="text-xl font-semibold text-gray-700"><i class="fas fa-trash-alt mr-2 text-blue-600"></i>All Junk Removal Requests</h2>
             <button id="bulk-delete-junk-btn" class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors duration-200 shadow-md hidden">
                <i class="fas fa-trash-alt mr-2"></i>Delete Selected
            </button>
        </div>

        <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
            <div class="flex items-center gap-2">
                <label for="status-filter" class="text-sm font-medium text-gray-700">Status:</label>
                <select id="status-filter" onchange="applyJunkRemovalFilters()"
                        class="p-2 border border-gray-300 rounded-md text-sm">
                    <option value="all" <?php echo $filter_status === 'all' ? 'selected' : ''; ?>>All</option>
                    <option value="pending" <?php echo $filter_status === 'pending' ? 'selected' : ''; ?>>Pending</option>
                    <option value="quoted" <?php echo $filter_status === 'quoted' ? 'selected' : ''; ?>>Quoted</option>
                    <option value="accepted" <?php echo $filter_status === 'accepted' ? 'selected' : ''; ?>>Accepted</option>
                    <option value="scheduled" <?php echo $filter_status === 'scheduled' ? 'selected' : ''; ?>>Scheduled</option>
                    <option value="completed" <?php echo $filter_status === 'completed' ? 'selected' : ''; ?>>Completed</option>
                    <option value="cancelled" <?php echo $filter_status === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                </select>
            </div>

            <div class="flex items-center gap-2">
                <label for="start-date-filter" class="text-sm font-medium text-gray-700">From:</label>
                <input type="date" id="start-date-filter" value="<?php echo htmlspecialchars($start_date_filter); ?>"
                       class="p-2 border border-gray-300 rounded-md text-sm" onchange="applyJunkRemovalFilters()">
                <label for="end-date-filter" class="text-sm font-medium text-gray-700">To:</label>
                <input type="date" id="end-date-filter" value="<?php echo htmlspecialchars($end_date_filter); ?>"
                       class="p-2 border border-gray-300 rounded-md text-sm" onchange="applyJunkRemovalFilters()">
            </div>

            <div class="flex-grow max-w-sm">
                <input type="text" id="search-input" placeholder="Search by Request ID, Customer, Location..."
                       class="p-2 border border-gray-300 rounded-md w-full text-sm"
                       value="<?php echo htmlspecialchars($search_query); ?>"
                       onkeydown="if(event.key === 'Enter') applyJunkRemovalFilters()">
            </div>
            <button onclick="applyJunkRemovalFilters()" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors duration-200 shadow-md text-sm">
                Search
            </button>
        </div>

        <?php if (empty($requests)): ?>
            <p class="text-gray-600 text-center p-4">No junk removal requests found for the selected filters or search query.</p>
        <?php else: ?>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-blue-50">
                        <tr>
                            <th class="px-6 py-3 text-left">
                                <input type="checkbox" id="select-all-junk" class="h-4 w-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Request ID</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Customer</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Location</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Pickup Date</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Status</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($requests as $request): ?>
                            <tr class="<?php echo $request['is_viewed_by_admin'] ? '' : 'bg-blue-50 font-bold'; ?>">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <input type="checkbox" class="junk-checkbox h-4 w-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500" value="<?php echo htmlspecialchars($request['id']); ?>">
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">#JR<?php echo htmlspecialchars($request['id']); ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo htmlspecialchars($request['first_name'] . ' ' . $request['last_name']); ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo htmlspecialchars($request['location']); ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo htmlspecialchars($request['pickup_date']); ?></td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo getJunkRemovalStatusBadgeClass($request['status']); ?>">
                                        <?php echo htmlspecialchars(strtoupper(str_replace('_', ' ', $request['status']))); ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <button class="text-blue-600 hover:text-blue-900 view-request-details-btn" data-id="<?php echo htmlspecialchars($request['id']); ?>">View Details</button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <nav class="mt-4 flex items-center justify-between">
                <div class="flex-1 flex justify-between sm:hidden">
                    <button onclick="loadAdminJunkRemoval({page: <?php echo max(1, $current_page - 1); ?>, per_page: <?php echo $items_per_page; ?>, status: '<?php echo $filter_status; ?>', search: '<?php echo htmlspecialchars($search_query); ?>', start_date: '<?php echo htmlspecialchars($start_date_filter); ?>', end_date: '<?php echo htmlspecialchars($end_date_filter); ?>'})"
                           class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                        Previous
                    </button>
                    <button onclick="loadAdminJunkRemoval({page: <?php echo min($total_pages, $current_page + 1); ?>, per_page: <?php echo $items_per_page; ?>, status: '<?php echo $filter_status; ?>', search: '<?php echo htmlspecialchars($search_query); ?>', start_date: '<?php echo htmlspecialchars($start_date_filter); ?>', end_date: '<?php echo htmlspecialchars($end_date_filter); ?>'})"
                           class="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                        Next
                    </button>
                </div>
                <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
                    <div>
                        <p class="text-sm text-gray-700">
                            Showing <span class="font-medium"><?php echo $offset + 1; ?></span> to
                            <span class="font-medium"><?php echo min($offset + $items_per_page, $total_requests_count); ?></span> of
                            <span class="font-medium"><?php echo $total_requests_count; ?></span> results
                        </p>
                    </div>
                    <div>
                        <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px" aria-label="Pagination">
                            <button onclick="loadAdminJunkRemoval({page: <?php echo max(1, $current_page - 1); ?>, per_page: <?php echo $items_per_page; ?>, status: '<?php echo $filter_status; ?>', search: '<?php echo htmlspecialchars($search_query); ?>', start_date: '<?php echo htmlspecialchars($start_date_filter); ?>', end_date: '<?php echo htmlspecialchars($end_date_filter); ?>'})"
                                   class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
                                <span class="sr-only">Previous</span>
                                <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                    <path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd" />
                                </svg>
                            </button>
                            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                <button onclick="loadAdminJunkRemoval({page: <?php echo $i; ?>, per_page: <?php echo $items_per_page; ?>, status: '<?php echo $filter_status; ?>', search: '<?php echo htmlspecialchars($search_query); ?>', start_date: '<?php echo htmlspecialchars($start_date_filter); ?>', end_date: '<?php echo htmlspecialchars($end_date_filter); ?>'})"
                                       class="<?php echo $i == $current_page ? 'z-10 bg-blue-50 border-blue-500 text-blue-600' : 'bg-white border-gray-300 text-gray-700 hover:bg-gray-50'; ?> relative inline-flex items-center px-4 py-2 border text-sm font-medium">
                                    <?php echo $i; ?>
                                </button>
                            <?php endfor; ?>
                            <button onclick="loadAdminJunkRemoval({page: <?php echo min($total_pages, $current_page + 1); ?>, per_page: <?php echo $items_per_page; ?>, status: '<?php echo $filter_status; ?>', search: '<?php echo htmlspecialchars($search_query); ?>', start_date: '<?php echo htmlspecialchars($start_date_filter); ?>', end_date: '<?php echo htmlspecialchars($end_date_filter); ?>'})"
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
                    <span class="text-sm font-medium text-gray-700">Requests per page:</span>
                    <select onchange="loadAdminJunkRemoval({page: 1, per_page: this.value, status: '<?php echo $filter_status; ?>', search: '<?php echo htmlspecialchars($search_query); ?>', start_date: '<?php echo htmlspecialchars($start_date_filter); ?>', end_date: '<?php echo htmlspecialchars($end_date_filter); ?>'})"
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
</div>

<div id="junk-removal-detail-section" class="<?php echo $request_detail_view_data ? '' : 'hidden'; ?>">
    <button class="mb-6 px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300" onclick="window.loadAdminSection('junk_removal')">
        <i class="fas fa-arrow-left mr-2"></i>Back to All Requests
    </button>

    <?php if ($request_detail_view_data): ?>
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <div class="lg:col-span-2">
                <div class="bg-white p-6 rounded-lg shadow-md border border-blue-200">
                    <div class="flex justify-between items-start">
                        <h2 class="text-2xl font-bold text-gray-800 mb-4">Request #JR<?php echo htmlspecialchars($request_detail_view_data['id']); ?> Details</h2>
                        <span class="px-3 py-1 rounded-full text-sm font-semibold <?php echo getJunkRemovalStatusBadgeClass($request_detail_view_data['status']); ?>">
                            <?php echo htmlspecialchars(strtoupper(str_replace('_', ' ', $request_detail_view_data['status']))); ?>
                        </span>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm text-gray-700 mb-6 pb-4 border-b">
                        <div>
                            <p><span class="font-medium">Customer:</span> <?php echo htmlspecialchars($request_detail_view_data['first_name'] . ' ' . $request_detail_view_data['last_name']); ?></p>
                            <p><span class="font-medium">Email:</span> <?php echo htmlspecialchars($request_detail_view_data['email']); ?></p>
                            <p><span class="font-medium">Phone:</span> <?php echo htmlspecialchars($request_detail_view_data['phone_number']); ?></p>
                            <p><span class="font-medium">Location:</span> <?php echo htmlspecialchars($request_detail_view_data['location']); ?></p>
                        </div>
                        <div>
                            <p><span class="font-medium">Requested Pickup Date:</span> <?php echo htmlspecialchars($request_detail_view_data['removal_date']); ?></p>
                            <p><span class="font-medium">Requested Pickup Time:</span> <?php echo htmlspecialchars($request_detail_view_data['removal_time']); ?></p>
                            <p><span class="font-medium">Estimated Price:</span> <?php echo $request_detail_view_data['estimated_price'] ? '$' . number_format($request_detail_view_data['estimated_price'], 2) : 'N/A'; ?></p>
                            <p><span class="font-medium">Submitted On:</span> <?php echo (new DateTime($request_detail_view_data['created_at']))->format('Y-m-d H:i'); ?></p>
                        </div>
                    </div>

                    <h3 class="text-lg font-semibold text-gray-800 mb-3">Junk Items Description</h3>
                    <?php if (!empty($request_detail_view_data['junk_items_json'])): ?>
                        <div class="overflow-x-auto mb-6">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Item Type</th>
                                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase w-24">Qty</th>
                                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Est. Dims</th>
                                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Est. Wt.</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($request_detail_view_data['junk_items_json'] as $item): ?>
                                        <tr>
                                            <td class="p-2 text-sm text-gray-900"><?php echo htmlspecialchars($item['itemType'] ?? 'Unknown Item'); ?></td>
                                            <td class="p-2 text-sm text-gray-600"><?php echo htmlspecialchars($item['quantity'] ?? 'N/A'); ?></td>
                                            <td class="p-2 text-sm text-gray-600"><?php echo htmlspecialchars($item['estDimensions'] ?? 'N/A'); ?></td>
                                            <td class="p-2 text-sm text-gray-600"><?php echo htmlspecialchars($item['estWeight'] ?? 'N/A'); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <p class="text-gray-600 mb-6">No specific junk items listed.</p>
                    <?php endif; ?>

                    <?php if (!empty($request_detail_view_data['recommended_dumpster_size'])): ?>
                        <p class="text-sm text-gray-700 mb-2"><span class="font-medium">Recommended Dumpster Size:</span> <?php echo htmlspecialchars($request_detail_view_data['recommended_dumpster_size']); ?></p>
                    <?php endif; ?>
                    <?php if (!empty($request_detail_view_data['additional_comment'])): ?>
                        <p class="text-sm text-gray-700 mb-4"><span class="font-medium">Additional Comments:</span> <?php echo nl2br(htmlspecialchars($request_detail_view_data['additional_comment'])); ?></p>
                    <?php endif; ?>

                    <?php if (!empty($request_detail_view_data['media_urls_json'])): ?>
                        <h4 class="text-md font-semibold text-gray-700 mt-4 mb-2">Uploaded Media:</h4>
                        <div class="grid grid-cols-2 md:grid-cols-3 gap-2">
                            <?php foreach ($request_detail_view_data['media_urls_json'] as $media_url): ?>
                                <?php $fileExtension = pathinfo($media_url, PATHINFO_EXTENSION); ?>
                                <?php if (in_array(strtolower($fileExtension), ['jpg', 'jpeg', 'png', 'gif'])): ?>
                                    <img src="<?php echo htmlspecialchars($media_url); ?>" class="w-full h-24 object-cover rounded-lg cursor-pointer" onclick="showImageModal('<?php echo htmlspecialchars($media_url); ?>')">
                                <?php elseif (in_array(strtolower($fileExtension), ['mp4', 'webm', 'ogg'])): ?>
                                    <video src="<?php echo htmlspecialchars($media_url); ?>" controls class="w-full h-24 object-cover rounded-lg"></video>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="lg:col-span-1">
                <div class="bg-white p-6 rounded-lg shadow-md border border-gray-200 sticky top-24">
                    <h3 class="text-xl font-bold text-gray-800 mb-4">Actions</h3>
                    <?php if ($request_detail_view_data['status'] === 'pending' || $request_detail_view_data['status'] === 'customer_draft'): ?>
                        <form id="quote-request-form">
                            <input type="hidden" name="request_id" value="<?php echo htmlspecialchars($request_detail_view_data['id']); ?>">
                            <input type="hidden" name="action" value="set_price_and_status">
                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                            <div class="mb-4">
                                <label for="estimated_price" class="block text-sm font-medium text-gray-700">Estimated Price ($)</label>
                                <input type="number" id="estimated_price" name="estimated_price" step="0.01" min="0" class="mt-1 p-2 border border-gray-300 rounded-md w-full" value="<?php echo htmlspecialchars($request_detail_view_data['estimated_price'] ?? ''); ?>" required>
                            </div>
                            <div class="mb-4">
                                <label for="update_status" class="block text-sm font-medium text-gray-700">Update Status To:</label>
                                <select id="update_status" name="status" class="mt-1 p-2 border border-gray-300 rounded-md w-full" required>
                                    <option value="quoted">Quoted</option>
                                    <option value="cancelled">Cancel Request</option>
                                </select>
                            </div>
                            <button type="submit" class="w-full px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 font-semibold">Submit Quote / Update Status</button>
                        </form>
                    <?php elseif (in_array($request_detail_view_data['status'], ['quoted', 'scheduled', 'completed', 'cancelled'])): ?>
                        <div class="mb-4">
                            <label for="update_status" class="block text-sm font-medium text-gray-700">Update Status To:</label>
                            <select id="update_status_select_general" name="status" class="mt-1 p-2 border border-gray-300 rounded-md w-full" data-request-id="<?php echo htmlspecialchars($request_detail_view_data['id']); ?>" data-csrf-token="<?php echo htmlspecialchars($csrf_token); ?>">
                                <option value="<?php echo $request_detail_view_data['status']; ?>" selected disabled><?php echo htmlspecialchars(ucwords(str_replace('_', ' ', $request_detail_view_data['status']))); ?></option>
                                <?php if ($request_detail_view_data['status'] === 'quoted' || $request_detail_view_data['status'] === 'accepted'): ?>
                                    <option value="scheduled">Scheduled</option>
                                <?php endif; ?>
                                <?php if (in_array($request_detail_view_data['status'], ['quoted', 'scheduled'])): ?>
                                    <option value="completed">Completed</option>
                                <?php endif; ?>
                                <?php if (!in_array($request_detail_view_data['status'], ['completed', 'cancelled'])): ?>
                                    <option value="cancelled">Cancelled</option>
                                <?php endif; ?>
                            </select>
                            <button type="button" id="update-status-btn-general" class="w-full px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 font-semibold mt-3">Update Status</button>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    <?php else: ?>
        <p class="text-red-500 text-center p-8">The requested junk removal request could not be found.</p>
    <?php endif; ?>
</div>

<div id="image-modal" class="fixed inset-0 bg-black bg-opacity-75 flex items-center justify-center hidden z-50">
    <button class="absolute top-4 right-4 text-white text-4xl" onclick="hideModal('image-modal')">&times;</button>
    <img id="image-modal-content" src="" class="max-w-full max-h-[90%] object-contain">
</div>

<script>
    // Function to load the admin junk removal section with updated parameters
    function loadAdminJunkRemoval(params = {}) {
        const currentParams = new URLSearchParams(window.location.search);
        const newParams = {
            page: currentParams.get('page') || 1,
            per_page: currentParams.get('per_page') || <?php echo $items_per_page; ?>,
            status: currentParams.get('status') || 'all',
            search: currentParams.get('search') || '',
            start_date: currentParams.get('start_date') || '',
            end_date: currentParams.get('end_date') || '',
            ...params
        };
        window.loadAdminSection('junk_removal', newParams);
    }

    // Function to apply filters and search
    function applyJunkRemovalFilters() {
        const statusFilter = document.getElementById('status-filter').value;
        const searchInput = document.getElementById('search-input').value;
        const startDateFilter = document.getElementById('start-date-filter').value;
        const endDateFilter = document.getElementById('end-date-filter').value;
        loadAdminJunkRemoval({ page: 1, status: statusFilter, search: searchInput, start_date: startDateFilter, end_date: endDateFilter });
    }

    // Function to show individual request details
    function showJunkRemovalDetails(requestId) {
        loadAdminJunkRemoval({ request_id: requestId });
    }

    // --- Bulk Delete Functionality ---
    const selectAllJunkCheckbox = document.getElementById('select-all-junk');
    const bulkDeleteJunkBtn = document.getElementById('bulk-delete-junk-btn');

    function toggleBulkDeleteButtonVisibility() {
        const anyChecked = document.querySelectorAll('.junk-checkbox:checked').length > 0;
        if (bulkDeleteJunkBtn) { // Ensure button exists
            bulkDeleteJunkBtn.classList.toggle('hidden', !anyChecked);
        }
    }

    if (selectAllJunkCheckbox) {
        selectAllJunkCheckbox.addEventListener('change', function() {
            document.querySelectorAll('.junk-checkbox').forEach(checkbox => {
                checkbox.checked = this.checked;
            });
            toggleBulkDeleteButtonVisibility();
        });
    }

    document.body.addEventListener('change', function(event) {
        if (event.target.classList.contains('junk-checkbox')) {
            if (selectAllJunkCheckbox && !event.target.checked) {
                selectAllJunkCheckbox.checked = false;
            }
            toggleBulkDeleteButtonVisibility();
        }
    });

    if (bulkDeleteJunkBtn) {
        bulkDeleteJunkBtn.addEventListener('click', function() {
            const selectedIds = Array.from(document.querySelectorAll('.junk-checkbox:checked')).map(cb => cb.value);
            if (selectedIds.length === 0) {
                window.showToast('Please select at least one request to delete.', 'warning');
                return;
            }

            window.showConfirmationModal(
                'Delete Selected Requests',
                `Are you sure you want to delete ${selectedIds.length} selected junk removal request(s)? This action cannot be undone.`,
                async (confirmed) => {
                    if (confirmed) {
                        window.showToast('Deleting requests...', 'info');
                        const formData = new FormData();
                        // This action will delete quotes that are service_type 'junk_removal'
                        formData.append('action', 'delete_bulk'); // Re-use existing quotes bulk delete API
                        formData.append('csrf_token', '<?php echo htmlspecialchars($csrf_token); ?>'); // Add CSRF token here
                        selectedIds.forEach(id => formData.append('quote_ids[]', id)); // Send as quote_ids

                        try {
                            const response = await fetch('/api/admin/quotes.php', { // Target the quotes API
                                method: 'POST',
                                body: formData
                            });
                            const result = await response.json();
                            if (result.success) {
                                window.showToast(result.message, 'success');
                                loadAdminJunkRemoval(); // Reload list after deletion
                            } else {
                                window.showToast('Error: ' + result.message, 'error');
                            }
                        } catch (error) {
                            showToast('An unexpected error occurred during bulk delete.', 'error');
                            console.error('Bulk delete junk removal requests API Error:', error);
                        }
                    }
                },
                'Delete Selected',
                'bg-red-600'
            );
        });
    }


    // --- Detail View Actions ---
    document.body.addEventListener('click', function(event) {
        const target = event.target.closest('button');
        if (!target) return;

        if (target.classList.contains('view-request-details-btn')) {
            const requestId = target.dataset.id;
            showJunkRemovalDetails(requestId);
        }

        // Image modal for junk removal media
        if (event.target.tagName === 'IMG' && event.target.closest('#junk-removal-detail-section .grid.gap-2')) {
            showImageModal(event.target.src);
        }
    });

    // Image modal function
    function showImageModal(imageUrl) {
        document.getElementById('image-modal-content').src = imageUrl;
        window.showModal('image-modal');
    }

    // Handle form submission for quoting/updating status
    const quoteRequestForm = document.getElementById('quote-request-form');
    if (quoteRequestForm) {
        quoteRequestForm.addEventListener('submit', async function(event) {
            event.preventDefault();
            const formData = new FormData(this);
            // The API for junk removal updates is api/admin/junk_removal.php
            // but for setting price and status, we need to interact with the 'quotes' table
            // so we will re-target this to api/admin/quotes.php and use 'submit_quote' action
            // Or a new action in api/admin/junk_removal.php to update quote properties.
            // For now, let's assume api/admin/quotes.php can handle 'submit_quote' with relevant fields
            // and adapt to junk removal specific data.
            formData.append('service_type', 'junk_removal'); // Ensure service_type is sent
            formData.append('quoted_price', formData.get('estimated_price')); // Map estimated_price to quoted_price
            formData.delete('estimated_price'); // Remove redundant field if needed

            window.showConfirmationModal(
                'Confirm Action',
                `Are you sure you want to ${formData.get('status') === 'quoted' ? 'quote this request' : 'cancel this request'}?`,
                async (confirmed) => {
                    if (confirmed) {
                        window.showToast('Processing request...', 'info');
                        try {
                            // Target the quotes API as it handles updating the main quote status and price
                            const response = await fetch('/api/admin/quotes.php', {
                                method: 'POST',
                                body: formData
                            });
                            const result = await response.json();
                            if (result.success) {
                                window.showToast(result.message, 'success');
                                showJunkRemovalDetails(formData.get('request_id')); // Reload detail view
                            } else {
                                window.showToast('Error: ' + result.message, 'error');
                            }
                        } catch (error) {
                            console.error('Junk removal quote submission API Error:', error);
                            window.showToast('An unexpected error occurred.', 'error');
                        }
                    }
                }
            );
        });
    }

    // Handle status update from 'quoted' or 'scheduled' state
    document.body.addEventListener('click', async function(event) {
        const target = event.target;
        if (target.id === 'update-status-btn-general') { // General button for status updates in detail view
            const selectElement = document.getElementById('update_status_select_general');
            const requestId = selectElement.dataset.requestId;
            const newStatus = selectElement.value;
            const csrfToken = selectElement.dataset.csrfToken;

            window.showConfirmationModal(
                'Confirm Status Update',
                `Are you sure you want to change the status to "${selectElement.options[selectElement.selectedIndex].text}"?`,
                async (confirmed) => {
                    if (confirmed) {
                        window.showToast('Updating status...', 'info');
                        const formData = new FormData();
                        formData.append('action', 'update_status');
                        // Use 'quote_id' as the API for quotes expects it (Junk Removal requests are quotes)
                        formData.append('quote_id', requestId);
                        formData.append('status', newStatus);
                        formData.append('csrf_token', csrfToken);

                        try {
                            // Target the quotes API as it handles updating the main quote status
                            const response = await fetch('/api/admin/quotes.php', {
                                method: 'POST',
                                body: formData
                            });
                            const result = await response.json();
                            if (result.success) {
                                window.showToast(result.message, 'success');
                                showJunkRemovalDetails(requestId);
                            } else {
                                window.showToast('Error: ' + result.message, 'error');
                            }
                        } catch (error) {
                            console.error('Update status API Error:', error);
                            window.showToast('An unexpected error occurred.', 'error');
                        }
                    }
                }
            );
        }
    });