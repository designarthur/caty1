<?php
// customer/pages/quotes.php

// Ensure session is started and user is logged in as a customer
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../includes/functions.php';

if (!is_logged_in() || !has_role('customer')) {
    echo '<div class="text-red-500 text-center p-8">Unauthorized access.</div>';
    exit;
}

generate_csrf_token();
$csrf_token = $_SESSION['csrf_token'];

$user_id = $_SESSION['user_id'];
$quotes = [];
// Renamed for clarity: this is for fetching a *single* quote's details if a quote_id is present in GET
$requested_quote_id_for_detail = $_GET['quote_id'] ?? null; 
$quote_detail_view_data = null; // Will hold data for the single detail view


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

// --- Data Fetching Logic ---
if ($requested_quote_id_for_detail) {
    // Fetch data for the detail view of a single quote
    $stmt_detail = $conn->prepare("
        SELECT
            q.id, q.service_type, q.status, q.created_at, q.location, q.quoted_price, q.admin_notes, q.customer_type,
            q.delivery_date, q.delivery_time, q.removal_date, q.removal_time, q.live_load_needed, q.is_urgent, q.driver_instructions,
            q.daily_rate, q.swap_charge, q.relocation_charge, q.discount, q.tax, q.is_swap_included, q.is_relocation_included
        FROM quotes q
        WHERE q.user_id = ? AND q.id = ?
    ");
    $stmt_detail->bind_param("ii", $user_id, $requested_quote_id_for_detail);
    $stmt_detail->execute();
    $result_detail = $stmt_detail->get_result();
    if ($result_detail->num_rows > 0) {
        $quote_detail_view_data = $result_detail->fetch_assoc();

        // Fetch related equipment or junk details for the single quote being viewed
        if ($quote_detail_view_data['service_type'] === 'equipment_rental') {
            $stmt_eq = $conn->prepare("SELECT equipment_name, quantity, duration_days, specific_needs FROM quote_equipment_details WHERE quote_id = ?");
            $stmt_eq->bind_param("i", $requested_quote_id_for_detail);
            $stmt_eq->execute();
            $quote_detail_view_data['equipment_details'] = $stmt_eq->get_result()->fetch_all(MYSQLI_ASSOC);
            $stmt_eq->close();
        } elseif ($quote_detail_view_data['service_type'] === 'junk_removal') {
            $stmt_junk = $conn->prepare("SELECT junk_items_json, recommended_dumpster_size, additional_comment, media_urls_json FROM junk_removal_details WHERE quote_id = ?");
            $stmt_junk->bind_param("i", $requested_quote_id_for_detail);
            $stmt_junk->execute();
            $junk_result = $stmt_junk->get_result()->fetch_assoc();
            if($junk_result) {
                $quote_detail_view_data['junk_details'] = $junk_result;
                $quote_detail_view_data['junk_details']['junk_items_json'] = json_decode($junk_result['junk_items_json'] ?? '[]', true);
                $quote_detail_view_data['junk_details']['media_urls_json'] = json_decode($junk_result['media_urls_json'] ?? '[]', true);
            }
            $stmt_junk->close();
        }
    }
    $stmt_detail->close();

} else {
    // --- Fetch all quotes for the list view with Filters, Search, and Pagination ---
    $base_query = "FROM quotes q WHERE q.user_id = ?";
    $params = [$user_id];
    $types = "i";

    if ($filter_status !== 'all') {
        $base_query .= " AND q.status = ?";
        $params[] = $filter_status;
        $types .= "s";
    }

    if (!empty($search_query)) {
        $search_term = '%' . $search_query . '%';
        $base_query .= " AND (CAST(q.id AS CHAR) LIKE ? OR q.location LIKE ?)";
        $params[] = $search_term;
        $params[] = $search_term;
        $types .= "ss";
    }

    // Get total count for pagination
    $stmt_count = $conn->prepare("SELECT COUNT(*) " . $base_query);
    $stmt_count->bind_param($types, ...$params);
    $stmt_count->execute();
    $total_quotes = $stmt_count->get_result()->fetch_row()[0];
    $stmt_count->close();
    $total_pages = ceil($total_quotes / $items_per_page);


    $query = "SELECT
                q.id, q.service_type, q.status, q.created_at, q.location, q.quoted_price, q.admin_notes, q.customer_type,
                q.delivery_date, q.delivery_time, q.removal_date, q.removal_time, q.live_load_needed, q.is_urgent, q.driver_instructions,
                q.daily_rate, q.swap_charge, q.relocation_charge, q.discount, q.tax, q.is_swap_included, q.is_relocation_included
            " . $base_query . "
            ORDER BY q.created_at DESC
            LIMIT ? OFFSET ?";

    $params[] = $items_per_page;
    $params[] = $offset;
    $types .= "ii";

    $stmt = $conn->prepare($query);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();

    $quote_ids_for_list = []; // Renamed to avoid conflict with potential detail data
    while ($row = $result->fetch_assoc()) {
        $quotes[$row['id']] = $row;
        $quote_ids_for_list[] = $row['id'];
    }
    $stmt->close();

    // Now, fetch all related equipment and junk details for the quotes in the *list view*
    if (!empty($quote_ids_for_list)) {
        $in_clause = implode(',', array_fill(0, count($quote_ids_for_list), '?'));
        
        // Fetch equipment details for list items
        $eq_query = "SELECT quote_id, equipment_name, quantity, duration_days, specific_needs FROM quote_equipment_details WHERE quote_id IN ($in_clause)";
        $eq_stmt = $conn->prepare($eq_query);
        $eq_stmt->bind_param(str_repeat('i', count($quote_ids_for_list)), ...$quote_ids_for_list);
        $eq_stmt->execute();
        $eq_result = $eq_stmt->get_result();
        while ($eq_row = $eq_result->fetch_assoc()) {
            if (!isset($quotes[$eq_row['quote_id']]['equipment_details'])) {
                $quotes[$eq_row['quote_id']]['equipment_details'] = [];
            }
            $quotes[$eq_row['quote_id']]['equipment_details'][] = $eq_row;
        }
        $eq_stmt->close();
        
        // Fetch junk details for list items
        $junk_query = "SELECT quote_id, junk_items_json, recommended_dumpster_size, additional_comment, media_urls_json FROM junk_removal_details WHERE quote_id IN ($in_clause)";
        $junk_stmt = $conn->prepare($junk_query);
        $junk_stmt->bind_param(str_repeat('i', count($quote_ids_for_list)), ...$quote_ids_for_list);
        $junk_stmt->execute();
        $junk_result = $junk_stmt->get_result();
        while ($junk_row = $junk_result->fetch_assoc()) {
            $quotes[$junk_row['quote_id']]['junk_details'] = $junk_row;
            $quotes[$junk_row['quote_id']]['junk_details']['junk_items_json'] = json_decode($junk_row['junk_items_json'] ?? '[]', true);
            $quotes[$junk_row['quote_id']]['junk_details']['media_urls_json'] = json_decode($junk_row['media_urls_json'] ?? '[]', true);
        }
        $junk_stmt->close();
    }
}


$conn->close();

function getCustomerStatusBadgeClass($status) {
    switch ($status) {
        case 'pending': return 'bg-yellow-100 text-yellow-800';
        case 'quoted': return 'bg-blue-100 text-blue-800';
        case 'accepted': return 'bg-green-100 text-green-800';
        case 'rejected': return 'bg-red-100 text-red-800';
        case 'converted_to_booking': return 'bg-purple-100 text-purple-800';
        case 'customer_draft': return 'bg-gray-200 text-gray-700'; // New status for drafts
        default: return 'bg-gray-100 text-gray-700';
    }
}
?>

<h1 class="text-3xl font-bold text-gray-800 mb-8">My Quotes</h1>

<div id="quotes-list-section" class="<?php echo $requested_quote_id_for_detail ? 'hidden' : ''; ?>">

    <div class="bg-white p-6 rounded-lg shadow-md border border-blue-200">
        <div class="flex flex-col md:flex-row justify-between items-center mb-4 gap-4">
            <h2 class="text-xl font-semibold text-gray-700"><i class="fas fa-file-invoice mr-2 text-blue-600"></i>Your Quote Requests</h2>
            <div class="flex items-center gap-2">
                <input type="text" id="search-input" placeholder="Search by ID or Location..." class="p-2 border border-gray-300 rounded-md w-full md:w-auto text-sm" value="<?php echo htmlspecialchars($search_query); ?>" onkeydown="if(event.key === 'Enter') applyFilters()">
                <button onclick="applyFilters()" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700"><i class="fas fa-search"></i></button>
            </div>
        </div>
        <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
            <div class="flex items-center gap-2">
                <label for="status-filter" class="text-sm font-medium text-gray-700">Status:</label>
                <select id="status-filter" onchange="applyFilters()"
                        class="p-2 border border-gray-300 rounded-md text-sm">
                    <option value="all" <?php echo $filter_status === 'all' ? 'selected' : ''; ?>>All</option>
                    <option value="customer_draft" <?php echo $filter_status === 'customer_draft' ? 'selected' : ''; ?>>Draft</option>
                    <option value="pending" <?php echo $filter_status === 'pending' ? 'selected' : ''; ?>>Pending</option>
                    <option value="quoted" <?php echo $filter_status === 'quoted' ? 'selected' : ''; ?>>Quoted</option>
                    <option value="accepted" <?php echo $filter_status === 'accepted' ? 'selected' : ''; ?>>Accepted</option>
                    <option value="rejected" <?php echo $filter_status === 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                    <option value="converted_to_booking" <?php echo $filter_status === 'converted_to_booking' ? 'selected' : ''; ?>>Converted to Booking</option>
                </select>
            </div>
            <button id="bulk-delete-quotes-btn" class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 hidden md:inline-flex items-center">
                <i class="fas fa-trash-alt mr-2"></i>Delete Selected
            </button>
        </div>

        <?php if (empty($quotes)): ?>
            <p class="text-gray-600 text-center p-4">You have not submitted any quote requests yet.</p>
        <?php else: ?>
            <div class="hidden md:block overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-blue-50">
                        <tr>
                            <th class="px-6 py-3"><input type="checkbox" id="select-all-quotes" class="h-4 w-4 text-blue-600 border-gray-300 rounded"></th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Quote ID</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Service Type</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Location</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Submitted On</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Status</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($quotes as $quote): ?>
                            <tr class="quote-row">
                                <td class="px-6 py-4"><input type="checkbox" class="quote-checkbox h-4 w-4" value="<?php echo $quote['id']; ?>"></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">#Q<?php echo htmlspecialchars($quote['id']); ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo htmlspecialchars(ucwords(str_replace('_', ' ', $quote['service_type']))); ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo htmlspecialchars($quote['location']); ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo (new DateTime($quote['created_at']))->format('Y-m-d H:i'); ?></td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo getCustomerStatusBadgeClass($quote['status']); ?>">
                                        <?php echo htmlspecialchars(strtoupper(str_replace('_', ' ', $quote['status']))); ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <button class="text-blue-600 hover:text-blue-900 view-quote-request-btn" data-id="<?php echo htmlspecialchars($quote['id']); ?>">
                                        <i class="fas fa-eye mr-1"></i>View Request
                                    </button>
                                    <?php if ($quote['status'] === 'customer_draft' && $quote['service_type'] === 'junk_removal'): ?>
                                        <button class="ml-3 text-orange-600 hover:text-orange-900" onclick="window.loadCustomerSection('junk-removal', { quote_id: <?php echo $quote['id']; ?> });">
                                            <i class="fas fa-edit mr-1"></i>Edit Draft
                                        </button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <tr id="quote-details-<?php echo htmlspecialchars($quote['id']); ?>" class="quote-details-row bg-gray-50 hidden">
                                <td colspan="7" class="px-6 py-4">
                                    <div class="p-4 border border-gray-200 rounded-lg shadow-sm">
                                        <h3 class="text-lg font-bold text-gray-800 mb-4">Details for Quote #Q<?php echo htmlspecialchars($quote['id']); ?></h3>

                                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm text-gray-700 mb-4">
                                            <div>
                                                <p><span class="font-medium">Customer Type:</span> <?php echo htmlspecialchars($quote['customer_type'] ?? 'N/A'); ?></p>
                                                <p><span class="font-medium">Requested Date:</span> <?php echo htmlspecialchars($quote['delivery_date'] ?? $quote['removal_date'] ?? 'N/A'); ?></p>
                                                <p><span class="font-medium">Requested Time:</span> <?php echo htmlspecialchars($quote['delivery_time'] ?? $quote['removal_time'] ?? 'N/A'); ?></p>
                                                <p><span class="font-medium">Live Load Needed:</span> <?php echo $quote['live_load_needed'] ? 'Yes' : 'No'; ?></p>
                                                <p><span class="font-medium">Urgent Request:</span> <?php echo $quote['is_urgent'] ? 'Yes' : 'No'; ?></p>
                                            </div>
                                            <div>
                                                <p><span class="font-medium">Driver Instructions:</span> <?php echo htmlspecialchars($quote['driver_instructions'] ?? 'None provided.'); ?></p>
                                            </div>
                                        </div>

                                        <?php if ($quote['service_type'] === 'equipment_rental' && !empty($quote['equipment_details'])): ?>
                                            <h4 class="text-md font-semibold text-gray-700 mb-2">Equipment Details:</h4>
                                            <ul class="list-disc list-inside space-y-2 pl-4">
                                                <?php
                                                $rental_start_date = $quote['delivery_date'] ?? null;
                                                $max_duration_days = 0;
                                                foreach ($quote['equipment_details'] as $item) {
                                                    if (isset($item['duration_days']) && $item['duration_days'] > $max_duration_days) {
                                                        $max_duration_days = $item['duration_days'];
                                                    }
                                                }
                                                $rental_end_date = null;
                                                if ($rental_start_date && $max_duration_days > 0) {
                                                    try {
                                                        $start_dt = new DateTime($rental_start_date);
                                                        $end_dt = clone $start_dt; // Clone to avoid modifying original DateTime object
                                                        $end_dt->modify("+$max_duration_days days");
                                                        $rental_end_date = $end_dt->format('Y-m-d');
                                                    } catch (Exception $e) {
                                                        error_log("Date calculation error for quote ID {$quote['id']}: " . $e->getMessage());
                                                    }
                                                }
                                                ?>
                                                <?php if ($rental_start_date): ?>
                                                    <p><span class="font-medium">Rental Start Date:</span> <?php echo htmlspecialchars($rental_start_date); ?></p>
                                                <?php endif; ?>
                                                <?php if ($rental_end_date): ?>
                                                    <p><span class="font-medium">Rental End Date:</span> <?php echo htmlspecialchars($rental_end_date); ?></p>
                                                <?php endif; ?>
                                                <?php if ($max_duration_days > 0): ?>
                                                    <p><span class="font-medium">Duration:</span> <?php echo htmlspecialchars($max_duration_days); ?> Days</p>
                                                <?php endif; ?>

                                                <?php foreach ($quote['equipment_details'] as $item): ?>
                                                    <li>
                                                        <strong><?php echo htmlspecialchars($item['quantity']); ?>x</strong> <?php echo htmlspecialchars($item['equipment_name']); ?>
                                                        <?php if (isset($item['duration_days'])): ?>
                                                            (for <?php echo htmlspecialchars($item['duration_days']); ?> days)
                                                        <?php endif; ?>
                                                        <?php if (!empty($item['specific_needs'])): ?>
                                                            <p class="text-xs text-gray-600 pl-5"> - Needs: <?php echo htmlspecialchars($item['specific_needs']); ?></p>
                                                        <?php endif; ?>
                                                    </li>
                                                <?php endforeach; ?>
                                            </ul>
                                        <?php elseif ($quote['service_type'] === 'junk_removal' && !empty($quote['junk_details'])): ?>
                                            <h4 class="text-md font-semibold text-gray-700 mb-2">Junk Removal Details:</h4>
                                            <ul class="list-disc list-inside space-y-2 pl-4">
                                                <?php if (!empty($quote['junk_details']['junk_items_json'])): ?>
                                                    <?php foreach ($quote['junk_details']['junk_items_json'] as $item): ?>
                                                        <li><?php echo htmlspecialchars($item['itemType'] ?? 'N/A'); ?> (Qty: <?php echo htmlspecialchars($item['quantity'] ?? 'N/A'); ?>)</li>
                                                    <?php endforeach; ?>
                                                <?php else: ?>
                                                    <li>No specific junk items listed.</li>
                                                <?php endif; ?>
                                                <?php if (!empty($quote['junk_details']['recommended_dumpster_size'])): ?>
                                                    <li>Recommended Dumpster Size: <?php echo htmlspecialchars($quote['junk_details']['recommended_dumpster_size']); ?></li>
                                                <?php endif; ?>
                                                <?php if (!empty($quote['junk_details']['additional_comment'])): ?>
                                                    <li>Additional Comments: <?php echo htmlspecialchars($quote['junk_details']['additional_comment']); ?></li>
                                                <?php endif; ?>
                                            </ul>
                                            <?php if (!empty($quote['junk_details']['media_urls_json'])): ?>
                                                <h4 class="text-md font-semibold text-gray-700 mt-4 mb-2">Uploaded Media:</h4>
                                                <div class="grid grid-cols-2 md:grid-cols-3 gap-2">
                                                    <?php foreach ($quote['junk_details']['media_urls_json'] as $media_url): ?>
                                                        <?php $fileExtension = pathinfo($media_url, PATHINFO_EXTENSION); ?>
                                                        <?php if (in_array(strtolower($fileExtension), ['jpg', 'jpeg', 'png', 'gif'])): ?>
                                                            <img src="<?php echo htmlspecialchars($media_url); ?>" class="w-full h-24 object-cover rounded-lg cursor-pointer" onclick="showImageModal('<?php echo htmlspecialchars($media_url); ?>')">
                                                        <?php elseif (in_array(strtolower($fileExtension), ['mp4', 'webm', 'ogg'])): ?>
                                                            <video src="<?php echo htmlspecialchars($media_url); ?>" controls class="w-full h-24 object-cover rounded-lg"></video>
                                                        <?php endif; ?>
                                                    <?php endforeach; ?>
                                                </div>
                                            <?php endif; ?>
                                        <?php endif; ?>

                                        <?php if ($quote['status'] === 'quoted' || $quote['status'] === 'accepted' || $quote['status'] === 'converted_to_booking'): ?>
                                            <?php
                                                // Calculate the final price for display and action
                                                $final_quoted_price = ($quote['quoted_price'] ?? 0) - ($quote['discount'] ?? 0) + ($quote['tax'] ?? 0);
                                                $final_quoted_price = max(0, $final_quoted_price); // Ensure it's not negative
                                            ?>
                                            <div class="mt-6 pt-4 border-t border-gray-200">
                                                <h4 class="text-lg font-bold text-gray-800 mb-2">Our Quotation:</h4>
                                                <p class="text-gray-700 mb-2"><span class="font-medium">Base Quoted Price:</span> <span class="text-gray-600 text-lg">$<?php echo number_format($quote['quoted_price'] ?? 0, 2); ?></span></p>
                                                
                                                <?php if (!empty($quote['daily_rate']) && $quote['daily_rate'] > 0): ?>
                                                    <p class="text-gray-700 mb-2"><span class="font-medium">Daily Rate (for extensions):</span> $<?php echo number_format($quote['daily_rate'], 2); ?></p>
                                                <?php endif; ?>

                                                <?php if (!empty($quote['relocation_charge']) && $quote['relocation_charge'] > 0): ?>
                                                    <p class="text-gray-700 mb-2"><span class="font-medium">Relocation Charge:</span> $<?php echo number_format($quote['relocation_charge'], 2); ?> (<?php echo ($quote['is_relocation_included'] ?? false) ? 'Included in base price' : 'Additional charge'; ?>)</p>
                                                <?php endif; ?>

                                                <?php if (!empty($quote['swap_charge']) && $quote['swap_charge'] > 0): ?>
                                                    <p class="text-gray-700 mb-2"><span class="font-medium">Swap Charge:</span> $<?php echo number_format($quote['swap_charge'], 2); ?> (<?php echo ($quote['is_swap_included'] ?? false) ? 'Included in base price' : 'Additional charge'; ?>)</p>
                                                <?php endif; ?>

                                                <?php if (!empty($quote['discount']) && $quote['discount'] > 0): ?>
                                                    <p class="text-gray-700 mb-2"><span class="font-medium">Discount:</span> -$<?php echo number_format($quote['discount'], 2); ?></p>
                                                <?php endif; ?>

                                                <?php if (!empty($quote['tax']) && $quote['tax'] > 0): ?>
                                                    <p class="text-gray-700 mb-2"><span class="font-medium">Tax:</span> $<?php echo number_format($quote['tax'], 2); ?></p>
                                                <?php endif; ?>

                                                <?php if (!empty($quote['admin_notes'])): ?>
                                                    <p class="text-gray-700 mb-4"><span class="font-medium">Notes from our team:</span> <?php echo nl2br(htmlspecialchars($quote['admin_notes'])); ?></p>
                                                <?php endif; ?>

                                                <p class="text-gray-700 mb-2 mt-4 text-right"><span class="font-bold text-xl">Final Total:</span> <span class="text-green-600 text-2xl font-bold">$<?php echo number_format($final_quoted_price, 2); ?></span></p>

                                                <?php if ($quote['status'] === 'quoted'): ?>
                                                    <div class="flex space-x-3 mt-4">
                                                        <button class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 accept-quote-btn" data-id="<?php echo htmlspecialchars($quote['id']); ?>" data-price="<?php echo htmlspecialchars($final_quoted_price); ?>">
                                                            <i class="fas fa-check-circle mr-2"></i>Accept Quote
                                                        </button>
                                                        <button class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 reject-quote-btn" data-id="<?php echo htmlspecialchars($quote['id']); ?>">
                                                            <i class="fas fa-times-circle mr-2"></i>Reject Quote
                                                        </button>
                                                    </div>
                                                <?php elseif ($quote['status'] === 'accepted'): ?>
                                                    <div class="mt-4 p-3 bg-green-50 text-green-700 border border-green-200 rounded-lg text-center font-medium">
                                                        <i class="fas fa-info-circle mr-2"></i>This quote has been accepted.
                                                    </div>
                                                <?php elseif ($quote['status'] === 'converted_to_booking'): ?>
                                                    <div class="mt-4 p-3 bg-purple-50 text-purple-700 border border-purple-200 rounded-lg text-center font-medium">
                                                        <i class="fas fa-check-double mr-2"></i>This quote has been converted to a booking. You can view it in your bookings.
                                                        <br><button class="text-purple-600 hover:underline mt-2" onclick="loadCustomerSection('bookings')">Go to Bookings</button>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        <?php elseif ($quote['status'] === 'pending'): ?>
                                            <div class="mt-6 pt-4 border-t border-gray-200 p-3 bg-yellow-50 text-yellow-700 border border-yellow-200 rounded-lg text-center font-medium">
                                                <i class="fas fa-hourglass-half mr-2"></i>Your quote request is pending. Our team will provide a quotation soon.
                                            </div>
                                        <?php elseif ($quote['status'] === 'rejected'): ?>
                                            <div class="mt-6 pt-4 border-t border-gray-200 p-3 bg-red-50 text-red-700 border border-red-200 rounded-lg text-center font-medium">
                                                <i class="fas fa-ban mr-2"></i>This quote request has been rejected.
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <div class="md:hidden space-y-4">
                <?php foreach ($quotes as $quote): ?>
                    <?php
                        // Determine relevant details for the card based on service type
                        $item_description = 'N/A';
                        if ($quote['service_type'] === 'equipment_rental' && !empty($quote['equipment_details'])) {
                            $item_types = array_column($quote['equipment_details'], 'equipment_name');
                            $item_description = implode(', ', $item_types);
                        } elseif ($quote['service_type'] === 'junk_removal' && !empty($quote['junk_details']['junk_items_json'])) {
                            $item_types = array_column($quote['junk_details']['junk_items_json'], 'itemType');
                            $item_description = implode(', ', $item_types);
                        }

                        // Calculate final price for display
                        $final_quoted_price = ($quote['quoted_price'] ?? 0) - ($quote['discount'] ?? 0) + ($quote['tax'] ?? 0);
                        $final_quoted_price = max(0, $final_quoted_price);
                    ?>
                    <div class="bg-white rounded-lg shadow-md border border-blue-200 p-4 relative">
                        <div class="absolute top-3 right-3 flex space-x-2">
                            <input type="checkbox" class="quote-checkbox h-4 w-4" value="<?php echo $quote['id']; ?>">
                        </div>
                        <div class="mb-2">
                            <p class="text-sm font-bold text-gray-800">Quote ID: #Q<?php echo htmlspecialchars($quote['id']); ?></p>
                            <p class="text-xs text-gray-600">Service: <?php echo htmlspecialchars(ucwords(str_replace('_', ' ', $quote['service_type']))); ?></p>
                        </div>
                        <div class="border-t border-b border-gray-200 py-2 mb-2">
                            <p class="text-sm text-gray-700"><span class="font-medium">Items:</span> <?php echo htmlspecialchars($item_description); ?></p>
                            <p class="text-sm text-gray-700"><span class="font-medium">Location:</span> <?php echo htmlspecialchars($quote['location']); ?></p>
                            <p class="text-sm text-gray-700"><span class="font-medium">Status:</span> 
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo getCustomerStatusBadgeClass($quote['status']); ?>">
                                    <?php echo htmlspecialchars(strtoupper(str_replace('_', ' ', $quote['status']))); ?>
                                </span>
                            </p>
                            <?php if ($quote['status'] === 'quoted' || $quote['status'] === 'accepted' || $quote['status'] === 'converted_to_booking'): ?>
                                <p class="text-lg font-bold text-green-600 mt-2">Total: $<?php echo number_format($final_quoted_price, 2); ?></p>
                            <?php endif; ?>
                        </div>
                        <div class="flex flex-wrap gap-2 justify-end mt-3">
                            <button class="px-3 py-1 bg-blue-100 text-blue-700 rounded-md hover:bg-blue-200 text-xs view-quote-request-btn" data-id="<?php echo htmlspecialchars($quote['id']); ?>">
                                <i class="fas fa-eye mr-1"></i>Details
                            </button>
                            <?php if ($quote['status'] === 'quoted'): ?>
                                <button class="px-3 py-1 bg-green-100 text-green-700 rounded-md hover:bg-green-200 text-xs accept-quote-btn" data-id="<?php echo htmlspecialchars($quote['id']); ?>" data-price="<?php echo htmlspecialchars($final_quoted_price); ?>">
                                    <i class="fas fa-check-circle mr-1"></i>Accept
                                </button>
                                <button class="px-3 py-1 bg-red-100 text-red-700 rounded-md hover:bg-red-200 text-xs reject-quote-btn" data-id="<?php echo htmlspecialchars($quote['id']); ?>">
                                    <i class="fas fa-times-circle mr-1"></i>Reject
                                </button>
                            <?php elseif ($quote['status'] === 'customer_draft' && $quote['service_type'] === 'junk_removal'): ?>
                                <button class="px-3 py-1 bg-orange-100 text-orange-700 rounded-md hover:bg-orange-200 text-xs" onclick="window.loadCustomerSection('junk-removal', { quote_id: <?php echo $quote['id']; ?> });">
                                    <i class="fas fa-edit mr-1"></i>Edit Draft
                                </button>
                            <?php elseif ($quote['status'] === 'converted_to_booking'): ?>
                                <button class="px-3 py-1 bg-purple-100 text-purple-700 rounded-md hover:bg-purple-200 text-xs" onclick="loadCustomerSection('bookings')">
                                    <i class="fas fa-book-open mr-1"></i>View Booking
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <nav class="mt-4 flex flex-col md:flex-row items-center justify-between gap-4">
                <div>
                    <p class="text-sm text-gray-700">
                        Showing <span class="font-medium"><?php echo $offset + 1; ?></span> to <span class="font-medium"><?php echo min($offset + $items_per_page, $total_quotes); ?></span> of <span class="font-medium"><?php echo $total_quotes; ?></span> results
                    </p>
                </div>
                <div class="flex items-center gap-2">
                    <span class="text-sm font-medium text-gray-700">Per Page:</span>
                    <select onchange="loadCustomerSection('quotes', {page: 1, per_page: this.value, search: '<?php echo htmlspecialchars($search_query); ?>', status: document.getElementById('status-filter').value})" class="p-2 border border-gray-300 rounded-md text-sm">
                        <?php foreach ($items_per_page_options as $option): ?>
                            <option value="<?php echo $option; ?>" <?php echo $items_per_page == $option ? 'selected' : ''; ?>><?php echo $option; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px" aria-label="Pagination">
                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                            <button onclick="loadCustomerSection('quotes', {page: <?php echo $i; ?>, per_page: <?php echo $items_per_page; ?>, search: '<?php echo htmlspecialchars($search_query); ?>', status: document.getElementById('status-filter').value})" class="<?php echo $i == $current_page ? 'z-10 bg-blue-50 border-blue-500 text-blue-600' : 'bg-white border-gray-300 text-gray-500 hover:bg-gray-50'; ?> relative inline-flex items-center px-4 py-2 border text-sm font-medium">
                                <?php echo $i; ?>
                            </button>
                        <?php endfor; ?>
                    </nav>
                </div>
            </nav>
        <?php endif; ?>
    </div>
</div>

<div id="quote-detail-view" class="<?php echo $requested_quote_id_for_detail ? '' : 'hidden'; ?>">
    <?php if ($quote_detail_view_data): ?>
        <button class="mb-6 px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 back-to-quotes-list-btn">
            <i class="fas fa-arrow-left mr-2"></i>Back to All Quotes
        </button>
        
        <div class="bg-white p-6 rounded-lg shadow-md border border-blue-200">
            <h2 class="text-2xl font-bold text-gray-800 mb-6">Quote #Q<?php echo htmlspecialchars($quote_detail_view_data['id']); ?> Details</h2>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm text-gray-700 mb-4 pb-4 border-b">
                <div>
                    <p><span class="font-medium">Service Type:</span> <?php echo htmlspecialchars(ucwords(str_replace('_', ' ', $quote_detail_view_data['service_type']))); ?></p>
                    <p><span class="font-medium">Status:</span> 
                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo getCustomerStatusBadgeClass($quote_detail_view_data['status']); ?>">
                            <?php echo htmlspecialchars(strtoupper(str_replace('_', ' ', $quote_detail_view_data['status']))); ?>
                        </span>
                    </p>
                    <p><span class="font-medium">Customer Type:</span> <?php echo htmlspecialchars($quote_detail_view_data['customer_type'] ?? 'N/A'); ?></p>
                    <p><span class="font-medium">Location:</span> <?php echo htmlspecialchars($quote_detail_view_data['location']); ?></p>
                    <p><span class="font-medium">Requested Date:</span> <?php echo htmlspecialchars($quote_detail_view_data['delivery_date'] ?? $quote_detail_view_data['removal_date'] ?? 'N/A'); ?></p>
                    <p><span class="font-medium">Requested Time:</span> <?php echo htmlspecialchars($quote_detail_view_data['delivery_time'] ?? $quote_detail_view_data['removal_time'] ?? 'N/A'); ?></p>
                </div>
                <div>
                    <p><span class="font-medium">Submitted On:</span> <?php echo (new DateTime($quote_detail_view_data['created_at']))->format('Y-m-d H:i'); ?></p>
                    <p><span class="font-medium">Live Load Needed:</span> <?php echo $quote_detail_view_data['live_load_needed'] ? 'Yes' : 'No'; ?></p>
                    <p><span class="font-medium">Urgent Request:</span> <?php echo $quote_detail_view_data['is_urgent'] ? 'Yes' : 'No'; ?></p>
                    <p><span class="font-medium">Driver Instructions:</span> <?php echo htmlspecialchars($quote_detail_view_data['driver_instructions'] ?? 'None provided.'); ?></p>
                </div>
            </div>

            <?php if ($quote_detail_view_data['service_type'] === 'equipment_rental' && !empty($quote_detail_view_data['equipment_details'])): ?>
                <h4 class="text-md font-semibold text-gray-700 mb-2">Equipment Details:</h4>
                <ul class="list-disc list-inside space-y-2 pl-4">
                    <?php
                    $rental_start_date = $quote_detail_view_data['delivery_date'] ?? null;
                    $max_duration_days = 0;
                    foreach ($quote_detail_view_data['equipment_details'] as $item) {
                        if (isset($item['duration_days']) && $item['duration_days'] > $max_duration_days) {
                            $max_duration_days = $item['duration_days'];
                        }
                    }
                    $rental_end_date = null;
                    if ($rental_start_date && $max_duration_days > 0) {
                        try {
                            $start_dt = new DateTime($rental_start_date);
                            $end_dt = clone $start_dt;
                            $end_dt->modify("+$max_duration_days days");
                            $rental_end_date = $end_dt->format('Y-m-d');
                        } catch (Exception $e) {
                            error_log("Date calculation error for detail view quote ID {$quote_detail_view_data['id']}: " . $e->getMessage());
                        }
                    }
                    ?>
                    <?php if ($rental_start_date): ?>
                        <p><span class="font-medium">Rental Start Date:</span> <?php echo htmlspecialchars($rental_start_date); ?></p>
                    <?php endif; ?>
                    <?php if ($rental_end_date): ?>
                        <p><span class="font-medium">Rental End Date:</span> <?php echo htmlspecialchars($rental_end_date); ?></p>
                    <?php endif; ?>
                    <?php if ($max_duration_days > 0): ?>
                        <p><span class="font-medium">Duration:</span> <?php echo htmlspecialchars($max_duration_days); ?> Days</p>
                    <?php endif; ?>

                    <?php foreach ($quote_detail_view_data['equipment_details'] as $item): ?>
                        <li>
                            <strong><?php echo htmlspecialchars($item['quantity']); ?>x</strong> <?php echo htmlspecialchars($item['equipment_name']); ?>
                            <?php if (isset($item['duration_days'])): ?>
                                (for <?php echo htmlspecialchars($item['duration_days']); ?> days)
                            <?php endif; ?>
                            <?php if (!empty($item['specific_needs'])): ?>
                                <p class="text-xs text-gray-600 pl-5"> - Needs: <?php echo htmlspecialchars($item['specific_needs']); ?></p>
                            <?php endif; ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php elseif ($quote_detail_view_data['service_type'] === 'junk_removal' && !empty($quote_detail_view_data['junk_details'])): ?>
                <h4 class="text-md font-semibold text-gray-700 mb-2">Junk Removal Details:</h4>
                <ul class="list-disc list-inside space-y-2 pl-4">
                    <?php if (!empty($quote_detail_view_data['junk_details']['junk_items_json'])): ?>
                        <?php foreach ($quote_detail_view_data['junk_details']['junk_items_json'] as $item): ?>
                            <li><?php echo htmlspecialchars($item['itemType'] ?? 'N/A'); ?> (Qty: <?php echo htmlspecialchars($item['quantity'] ?? 'N/A'); ?>)</li>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <li>No specific junk items listed.</li>
                    <?php endif; ?>
                    <?php if (!empty($quote_detail_view_data['junk_details']['recommended_dumpster_size'])): ?>
                        <li>Recommended Dumpster Size: <?php echo htmlspecialchars($quote_detail_view_data['junk_details']['recommended_dumpster_size']); ?></li>
                    <?php endif; ?>
                    <?php if (!empty($quote_detail_view_data['junk_details']['additional_comment'])): ?>
                        <li>Additional Comments: <?php echo htmlspecialchars($quote_detail_view_data['additional_comment']); ?></li>
                    <?php endif; ?>
                </ul>
                <?php if (!empty($quote_detail_view_data['junk_details']['media_urls_json'])): ?>
                    <h4 class="text-md font-semibold text-gray-700 mt-4 mb-2">Uploaded Media:</h4>
                    <div class="grid grid-cols-2 md:grid-cols-3 gap-2">
                        <?php foreach ($quote_detail_view_data['junk_details']['media_urls_json'] as $media_url): ?>
                            <?php $fileExtension = pathinfo($media_url, PATHINFO_EXTENSION); ?>
                            <?php if (in_array(strtolower($fileExtension), ['jpg', 'jpeg', 'png', 'gif'])): ?>
                                <img src="<?php echo htmlspecialchars($media_url); ?>" class="w-full h-24 object-cover rounded-lg cursor-pointer" onclick="showImageModal('<?php echo htmlspecialchars($media_url); ?>')">
                            <?php elseif (in_array(strtolower($fileExtension), ['mp4', 'webm', 'ogg'])): ?>
                                <video src="<?php echo htmlspecialchars($media_url); ?>" controls class="w-full h-24 object-cover rounded-lg"></video>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            <?php endif; ?>

            <?php if ($quote_detail_view_data['status'] === 'quoted' || $quote_detail_view_data['status'] === 'accepted' || $quote_detail_view_data['status'] === 'converted_to_booking'): ?>
                <?php
                    $final_quoted_price = ($quote_detail_view_data['quoted_price'] ?? 0) - ($quote_detail_view_data['discount'] ?? 0) + ($quote_detail_view_data['tax'] ?? 0);
                    $final_quoted_price = max(0, $final_quoted_price);
                ?>
                <div class="mt-6 pt-4 border-t border-gray-200">
                    <h4 class="text-lg font-bold text-gray-800 mb-2">Our Quotation:</h4>
                    <p class="text-gray-700 mb-2"><span class="font-medium">Base Quoted Price:</span> <span class="text-gray-600 text-lg">$<?php echo number_format($quote_detail_view_data['quoted_price'] ?? 0, 2); ?></span></p>
                    
                    <?php if (!empty($quote_detail_view_data['daily_rate']) && $quote_detail_view_data['daily_rate'] > 0): ?>
                        <p class="text-gray-700 mb-2"><span class="font-medium">Daily Rate (for extensions):</span> $<?php echo number_format($quote_detail_view_data['daily_rate'], 2); ?></p>
                    <?php endif; ?>

                    <?php if (!empty($quote_detail_view_data['relocation_charge']) && $quote_detail_view_data['relocation_charge'] > 0): ?>
                        <p class="text-gray-700 mb-2"><span class="font-medium">Relocation Charge:</span> $<?php echo number_format($quote_detail_view_data['relocation_charge'], 2); ?> (<?php echo ($quote_detail_view_data['is_relocation_included'] ?? false) ? 'Included in base price' : 'Additional charge'; ?>)</p>
                    <?php endif; ?>

                    <?php if (!empty($quote_detail_view_data['swap_charge']) && $quote_detail_view_data['swap_charge'] > 0): ?>
                        <p class="text-gray-700 mb-2"><span class="font-medium">Swap Charge:</span> $<?php echo number_format($quote_detail_view_data['swap_charge'], 2); ?> (<?php echo ($quote_detail_view_data['is_swap_included'] ?? false) ? 'Included in base price' : 'Additional charge'; ?>)</p>
                    <?php endif; ?>

                    <?php if (!empty($quote_detail_view_data['discount']) && $quote_detail_view_data['discount'] > 0): ?>
                        <p class="text-gray-700 mb-2"><span class="font-medium">Discount:</span> -$<?php echo number_format($quote_detail_view_data['discount'], 2); ?></p>
                    <?php endif; ?>

                    <?php if (!empty($quote_detail_view_data['tax']) && $quote_detail_view_data['tax'] > 0): ?>
                        <p class="text-gray-700 mb-2"><span class="font-medium">Tax:</span> $<?php echo number_format($quote_detail_view_data['tax'], 2); ?></p>
                    <?php endif; ?>

                    <?php if (!empty($quote_detail_view_data['admin_notes'])): ?>
                        <p class="text-gray-700 mb-4"><span class="font-medium">Notes from our team:</span> <?php echo nl2br(htmlspecialchars($quote_detail_view_data['admin_notes'])); ?></p>
                    <?php endif; ?>

                    <p class="text-gray-700 mb-2 mt-4 text-right"><span class="font-bold text-xl">Final Total:</span> <span class="text-green-600 text-2xl font-bold">$<?php echo number_format($final_quoted_price, 2); ?></span></p>

                    <?php if ($quote_detail_view_data['status'] === 'quoted'): ?>
                        <div class="flex flex-col sm:flex-row space-y-2 sm:space-y-0 sm:space-x-3 mt-4">
                            <button class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 accept-quote-btn" data-id="<?php echo htmlspecialchars($quote_detail_view_data['id']); ?>" data-price="<?php echo htmlspecialchars($final_quoted_price); ?>">
                                <i class="fas fa-check-circle mr-2"></i>Accept Quote
                            </button>
                            <button class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 reject-quote-btn" data-id="<?php echo htmlspecialchars($quote_detail_view_data['id']); ?>">
                                <i class="fas fa-times-circle mr-2"></i>Reject Quote
                            </button>
                        </div>
                    <?php elseif ($quote_detail_view_data['status'] === 'accepted'): ?>
                        <div class="mt-4 p-3 bg-green-50 text-green-700 border border-green-200 rounded-lg text-center font-medium">
                            <i class="fas fa-info-circle mr-2"></i>This quote has been accepted.
                        </div>
                    <?php elseif ($quote_detail_view_data['status'] === 'converted_to_booking'): ?>
                        <div class="mt-4 p-3 bg-purple-50 text-purple-700 border border-purple-200 rounded-lg text-center font-medium">
                            <i class="fas fa-check-double mr-2"></i>This quote has been converted to a booking. You can view it in your bookings.
                            <br><button class="text-purple-600 hover:underline mt-2" onclick="loadCustomerSection('bookings')">Go to Bookings</button>
                        </div>
                    <?php endif; ?>
                </div>
            <?php elseif ($quote_detail_view_data['status'] === 'pending'): ?>
                <div class="mt-6 pt-4 border-t border-gray-200 p-3 bg-yellow-50 text-yellow-700 border border-yellow-200 rounded-lg text-center font-medium">
                    <i class="fas fa-hourglass-half mr-2"></i>Your quote request is pending. Our team will provide a quotation soon.
                </div>
            <?php elseif ($quote_detail_view_data['status'] === 'rejected'): ?>
                <div class="mt-6 pt-4 border-t border-gray-200 p-3 bg-red-50 text-red-700 border border-red-200 rounded-lg text-center font-medium">
                    <i class="fas fa-ban mr-2"></i>This quote request has been rejected.
                </div>
            <?php elseif ($quote_detail_view_data['status'] === 'customer_draft' && $quote_detail_view_data['service_type'] === 'junk_removal'): ?>
                 <div class="mt-6 pt-4 border-t border-gray-200 p-3 bg-orange-50 text-orange-700 border border-orange-200 rounded-lg text-center font-medium">
                    <i class="fas fa-edit mr-2"></i>This is a draft. You can edit it from the Junk Removal section.
                    <br><button class="text-orange-600 hover:underline mt-2" onclick="loadCustomerSection('junk-removal', { quote_id: <?php echo $quote_detail_view_data['id']; ?> });">Edit Draft</button>
                </div>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <p class="text-center text-gray-600 py-8">Quote details not found or invalid quote ID.</p>
    <?php endif; ?>
</div>

<div id="image-modal" class="fixed inset-0 bg-black bg-opacity-75 flex items-center justify-center hidden z-50">
    <button class="absolute top-4 right-4 text-white text-4xl" onclick="hideModal('image-modal')">&times;</button>
    <img id="image-modal-content" src="" class="max-w-full max-h-[90%] object-contain">
</div>

<script>
    (function() {
        // Expose function globally for image modals
        function showImageModal(imageUrl) {
            document.getElementById('image-modal-content').src = imageUrl;
            window.showModal('image-modal');
        }

        // Helper function to get safe filter values
        const getSafeFilterValues = () => {
            const statusEl = document.getElementById('status-filter');
            const searchEl = document.getElementById('search-input');
            const perPageEl = document.querySelector('select[onchange^="loadCustomerSection"]'); // Target the select by its onchange

            // Use the current PHP value as the default to maintain state
            const defaultPerPage = <?php echo json_encode($items_per_page); ?>;

            return {
                status: statusEl ? statusEl.value : 'all',
                search: searchEl ? searchEl.value : '',
                per_page: perPageEl ? perPageEl.value : defaultPerPage
            };
        };


        // Main function to load customer quotes with filters and pagination
        window.loadCustomerQuotes = function(params = {}) {
            const filters = getSafeFilterValues();
            const newParams = {
                page: params.page || filters.page || 1, // Prioritize passed page, then current filter page, then 1
                per_page: params.per_page || filters.per_page,
                status: params.status || filters.status,
                search: params.search || filters.search,
                quote_id: params.quote_id || '' // For detail view
            };
            window.loadCustomerSection('quotes', newParams);
        };

        // Apply filters function, typically called by UI changes
        window.applyFilters = function(params = {}) {
            const currentPage = params.page || 1; // Always reset to page 1 for new filters
            const filters = getSafeFilterValues(); // Get current filter values
            window.loadCustomerQuotes({
                page: currentPage,
                status: filters.status,
                search: filters.search,
                per_page: filters.per_page // Ensure per_page is also passed
            });
        };

        // Function to hide detail view and show list view
        window.hideQuoteDetails = function() {
            window.loadCustomerQuotes({}); // Load list view
        };

        const csrfToken = '<?php echo $csrf_token; ?>';

        // Event listener for opening/closing quote details (table view) or navigating to detail page (card view)
        document.querySelectorAll('.view-quote-request-btn').forEach(button => {
            button.addEventListener('click', function() {
                const quoteId = this.dataset.id;
                const detailsRow = document.getElementById(`quote-details-${quoteId}`);
                
                // Check screen width
                if (window.innerWidth >= 768) { // Desktop/Tablet View
                    if (detailsRow) {
                        detailsRow.classList.toggle('hidden');
                        this.innerHTML = detailsRow.classList.contains('hidden') ? '<i class="fas fa-eye mr-1"></i>View Request' : '<i class="fas fa-eye-slash mr-1"></i>Hide Details';
                        if (!detailsRow.classList.contains('hidden')) {
                            detailsRow.scrollIntoView({ behavior: 'smooth', block: 'start' });
                        }
                    }
                } else { // Mobile View
                    // For mobile, navigate to the dedicated detail view
                    window.loadCustomerSection('quotes', { quote_id: quoteId });
                }
            });
        });

        // Event listener for "Back to All Quotes" button in the detail view
        document.querySelector('.back-to-quotes-list-btn')?.addEventListener('click', function() {
            window.hideQuoteDetails();
        });


        // Handle initial load based on URL hash (if a specific quote_id is present)
        const urlParams = new URLSearchParams(window.location.search);
        const initialQuoteId = urlParams.get('quote_id');
        if (initialQuoteId) {
            // For desktop/tablet, this part handles the row expansion if directly linked
            const initialDetailsRow = document.getElementById(`quote-details-${initialQuoteId}`);
            if (initialDetailsRow) {
                if (window.innerWidth >= 768) {
                    initialDetailsRow.classList.remove('hidden');
                    initialDetailsRow.scrollIntoView({ behavior: 'smooth', block: 'start' });
                    const viewButton = document.querySelector(`.view-quote-request-btn[data-id="${initialQuoteId}"]`);
                    if (viewButton) viewButton.innerHTML = '<i class="fas fa-eye-slash mr-1"></i>Hide Details';
                }
                // The mobile view case is handled by PHP showing/hiding #quotes-list-section and #quote-detail-view
                // based on $requested_quote_id_for_detail.
            }
        }

        // Event listeners for Accept/Reject buttons
        document.querySelectorAll('.accept-quote-btn, .reject-quote-btn').forEach(button => {
            button.addEventListener('click', function() {
                const isAccept = this.classList.contains('accept-quote-btn');
                const quoteId = this.dataset.id;
                const action = isAccept ? 'accept_quote' : 'reject_quote';
                const title = isAccept ? 'Accept Quote' : 'Reject Quote';
                const price_display = this.dataset.price;
                const message = isAccept ? `Are you sure you want to accept this quote for $${price_display}? This will proceed to payment.` : 'Are you sure you want to reject this quote? This action cannot be undone.';
                const confirmColor = isAccept ? 'bg-green-600' : 'bg-red-600';

                window.showConfirmationModal(title, message, async (confirmed) => {
                    if (confirmed) {
                        window.showToast('Processing...', 'info');
                        const formData = new FormData();
                        formData.append('action', action);
                        formData.append('quote_id', quoteId);
                        formData.append('csrf_token', csrfToken);
                        if (isAccept) {
                             formData.append('final_price', price_display);
                        }

                        try {
                            const response = await fetch('/api/customer/quotes.php', { method: 'POST', body: formData });
                            const result = await response.json();
                            if (result.success) {
                                window.showToast(result.message, 'success');
                                if (isAccept && result.invoice_id) {
                                    window.loadCustomerSection('invoices', { invoice_id: result.invoice_id });
                                } else {
                                    // Reload current section to update status or go back to list
                                    window.loadCustomerSection('quotes', { quote_id: quoteId }); 
                                }
                            } else {
                                window.showToast(result.message, 'error');
                            }
                        } catch (error) {
                            window.showToast('An unexpected error occurred.', 'error');
                        }
                    }
                }, title, confirmColor);
            });
        });

        // Bulk Delete Functionality (for both table and card view checkboxes)
        const selectAllCheckbox = document.getElementById('select-all-quotes');
        const bulkDeleteBtn = document.getElementById('bulk-delete-quotes-btn');
        const quoteCheckboxes = document.querySelectorAll('.quote-checkbox');

        function toggleBulkDeleteBtnVisibility() {
            // Check if any checkbox is checked
            const anyChecked = Array.from(quoteCheckboxes).some(cb => cb.checked);
            if (bulkDeleteBtn) { // Ensure button exists
                bulkDeleteBtn.classList.toggle('hidden', !anyChecked);
            }
        }

        // Event listener for "Select All" checkbox
        if(selectAllCheckbox) {
            selectAllCheckbox.addEventListener('change', (e) => {
                quoteCheckboxes.forEach(cb => cb.checked = e.target.checked);
                toggleBulkDeleteBtnVisibility();
            });
        }

        // Event listeners for individual checkboxes
        quoteCheckboxes.forEach(cb => {
            cb.addEventListener('change', () => {
                // If any individual checkbox is unchecked, uncheck "Select All"
                if (selectAllCheckbox && !cb.checked) {
                    selectAllCheckbox.checked = false;
                }
                toggleBulkDeleteBtnVisibility();
            });
        });

        // Event listener for "Delete Selected" button
        if(bulkDeleteBtn) {
            bulkDeleteBtn.addEventListener('click', () => {
                const selectedIds = Array.from(document.querySelectorAll('.quote-checkbox:checked')).map(cb => cb.value);
                if (selectedIds.length === 0) {
                    window.showToast('Please select quotes to delete.', 'warning');
                    return;
                }
                
                window.showConfirmationModal('Delete Quotes', `Are you sure you want to delete ${selectedIds.length} quote(s)? This action cannot be undone.`, async (confirmed) => {
                    if (confirmed) {
                        window.showToast('Deleting...', 'info');
                        const formData = new FormData();
                        formData.append('action', 'delete_bulk');
                        formData.append('csrf_token', csrfToken);
                        selectedIds.forEach(id => formData.append('quote_ids[]', id));

                        try {
                            const response = await fetch('/api/customer/quotes.php', { method: 'POST', body: formData });
                            const result = await response.json();
                            if(result.success) {
                                window.showToast(result.message, 'success');
                                window.loadCustomerSection('quotes'); // Reload the quotes page
                            } else {
                                window.showToast(result.message, 'error');
                            }
                        } catch (error) {
                            window.showToast('An unexpected error occurred.', 'error');
                        }
                    }
                }, 'Delete', 'bg-red-600');
            });
        }

        // Ensure initial state of bulk delete button is correct on page load
        // This runs when the section is loaded via AJAX
        document.addEventListener('DOMContentLoaded', () => {
            if (selectAllCheckbox) selectAllCheckbox.checked = false; // Reset on load
            toggleBulkDeleteBtnVisibility(); // Set initial visibility
        });


    })(); // End IIFE for quotes.php specific script
</script>