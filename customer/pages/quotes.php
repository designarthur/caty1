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
$expanded_quote_id = $_GET['quote_id'] ?? null;

// The main query remains the same as it fetches the parent quote record
$query = "SELECT
            q.id, q.service_type, q.status, q.created_at, q.location, q.quoted_price, q.admin_notes, q.customer_type,
            q.delivery_date, q.delivery_time, q.removal_date, q.removal_time, q.live_load_needed, q.is_urgent, q.driver_instructions,
            q.daily_rate, q.swap_charge, q.relocation_charge, q.discount, q.tax, q.is_swap_included, q.is_relocation_included
          FROM
            quotes q
          WHERE
            q.user_id = ?
          ORDER BY q.created_at DESC";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

$quote_ids = [];
while ($row = $result->fetch_assoc()) {
    $quotes[$row['id']] = $row;
    $quote_ids[] = $row['id'];
}
$stmt->close();

// Now, fetch all related equipment and junk details for the retrieved quotes
if (!empty($quote_ids)) {
    $in_clause = implode(',', array_fill(0, count($quote_ids), '?'));
    
    // Fetch equipment details
    $eq_query = "SELECT quote_id, equipment_name, quantity, duration_days, specific_needs FROM quote_equipment_details WHERE quote_id IN ($in_clause)";
    $eq_stmt = $conn->prepare($eq_query);
    $eq_stmt->bind_param(str_repeat('i', count($quote_ids)), ...$quote_ids);
    $eq_stmt->execute();
    $eq_result = $eq_stmt->get_result();
    while ($eq_row = $eq_result->fetch_assoc()) {
        if (!isset($quotes[$eq_row['quote_id']]['equipment_details'])) {
            $quotes[$eq_row['quote_id']]['equipment_details'] = [];
        }
        $quotes[$eq_row['quote_id']]['equipment_details'][] = $eq_row;
    }
    $eq_stmt->close();
    
    // Fetch junk details
    $junk_query = "SELECT quote_id, junk_items_json, recommended_dumpster_size, additional_comment, media_urls_json FROM junk_removal_details WHERE quote_id IN ($in_clause)";
    $junk_stmt = $conn->prepare($junk_query);
    $junk_stmt->bind_param(str_repeat('i', count($quote_ids)), ...$quote_ids);
    $junk_stmt->execute();
    $junk_result = $junk_stmt->get_result();
    while ($junk_row = $junk_result->fetch_assoc()) {
        $quotes[$junk_row['quote_id']]['junk_details'] = $junk_row;
        $quotes[$junk_row['quote_id']]['junk_details']['junk_items_json'] = json_decode($junk_row['junk_items_json'] ?? '[]', true);
        $quotes[$junk_row['quote_id']]['junk_details']['media_urls_json'] = json_decode($junk_row['media_urls_json'] ?? '[]', true);
    }
    $junk_stmt->close();
}


$conn->close();

function getCustomerStatusBadgeClass($status) {
    switch ($status) {
        case 'pending': return 'bg-yellow-100 text-yellow-800';
        case 'quoted': return 'bg-blue-100 text-blue-800';
        case 'accepted': return 'bg-green-100 text-green-800';
        case 'rejected': return 'bg-red-100 text-red-800';
        case 'converted_to_booking': return 'bg-purple-100 text-purple-800';
        default: return 'bg-gray-100 text-gray-700';
    }
}
?>

<h1 class="text-3xl font-bold text-gray-800 mb-8">My Quotes</h1>

<div class="bg-white p-6 rounded-lg shadow-md border border-blue-200">
    <h2 class="text-xl font-semibold text-gray-700 mb-4 flex items-center"><i class="fas fa-file-invoice mr-2 text-blue-600"></i>Your Quote Requests</h2>

    <?php if (empty($quotes)): ?>
        <p class="text-gray-600 text-center p-4">You have not submitted any quote requests yet.</p>
    <?php else: ?>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-blue-50">
                    <tr>
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
                            </td>
                        </tr>
                        <tr id="quote-details-<?php echo htmlspecialchars($quote['id']); ?>" class="quote-details-row bg-gray-50 hidden">
                            <td colspan="6" class="px-6 py-4">
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
                                                    $end_dt = $start_dt->modify("+$max_duration_days days");
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
    <?php endif; ?>
</div>

<div id="image-modal" class="fixed inset-0 bg-black bg-opacity-75 flex items-center justify-center hidden z-50">
    <button class="absolute top-4 right-4 text-white text-4xl" onclick="hideModal('image-modal')">&times;</button>
    <img id="image-modal-content" src="" class="max-w-full max-h-[90%] object-contain">
</div>

<script>
    (function() {
        function showImageModal(imageUrl) {
            document.getElementById('image-modal-content').src = imageUrl;
            window.showModal('image-modal');
        }

        document.querySelectorAll('.view-quote-request-btn').forEach(button => {
            button.addEventListener('click', function() {
                const quoteId = this.dataset.id;
                const detailsRow = document.getElementById(`quote-details-${quoteId}`);
                detailsRow.classList.toggle('hidden');
                this.innerHTML = detailsRow.classList.contains('hidden') ? '<i class="fas fa-eye mr-1"></i>View Request' : '<i class="fas fa-eye-slash mr-1"></i>Hide Details';
            });
        });

        const urlParams = new URLSearchParams(window.location.search);
        const initialQuoteId = urlParams.get('quote_id');
        if (initialQuoteId) {
            const initialDetailsRow = document.getElementById(`quote-details-${initialQuoteId}`);
            if (initialDetailsRow) {
                initialDetailsRow.classList.remove('hidden');
                initialDetailsRow.scrollIntoView({ behavior: 'smooth', block: 'start' });
                const viewButton = document.querySelector(`.view-quote-request-btn[data-id="${initialQuoteId}"]`);
                if (viewButton) viewButton.innerHTML = '<i class="fas fa-eye-slash mr-1"></i>Hide Details';
            }
        }

        const csrfToken = '<?php echo $csrf_token; ?>';

        document.querySelectorAll('.accept-quote-btn, .reject-quote-btn').forEach(button => {
            button.addEventListener('click', function() {
                const isAccept = this.classList.contains('accept-quote-btn');
                const quoteId = this.dataset.id;
                const action = isAccept ? 'accept_quote' : 'reject_quote';
                const title = isAccept ? 'Accept Quote' : 'Reject Quote';
                // Pass the data-price which now holds the final calculated total
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
                        // If accepting, also send the actual final price for backend validation/use if needed
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
    })();
</script>