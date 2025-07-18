<?php
// driver/update_booking.php - Driver-facing booking status update page

// --- Setup & Includes ---
// No session_start() here as this page is accessed via a unique token, not a logged-in session.
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php'; // For generateToken (though not used directly here, good practice)

// Set a default timezone to ensure consistent date calculations
date_default_timezone_set('UTC');

$token = $_GET['token'] ?? '';
$booking_data = null;
$status_history = []; // Initialize status history array
$error_message = '';

if (empty($token)) {
    $error_message = "No access token provided. This link is invalid.";
} else {
    // Fetch booking details using the driver_access_token
    $stmt = $conn->prepare("
        SELECT
            b.id, b.booking_number, b.service_type, b.status, b.start_date, b.end_date,
            b.delivery_location, b.pickup_location, b.delivery_instructions, b.pickup_instructions,
            b.live_load_requested, b.is_urgent, b.junk_details, b.equipment_details,
            v.name AS vendor_name, v.phone_number AS vendor_phone
        FROM bookings b
        LEFT JOIN vendors v ON b.vendor_id = v.id
        WHERE b.driver_access_token = ?
    ");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $booking_data = $result->fetch_assoc();
        // Decode JSON fields if they exist
        $booking_data['junk_details'] = json_decode($booking_data['junk_details'] ?? '{}', true);
        $booking_data['equipment_details'] = json_decode($booking_data['equipment_details'] ?? '[]', true);

        // Fetch status history for the timeline
        $history_stmt = $conn->prepare("SELECT status, status_time, notes FROM booking_status_history WHERE booking_id = ? ORDER BY status_time ASC, id ASC");
        $history_stmt->bind_param("i", $booking_data['id']);
        $history_stmt->execute();
        $history_result = $history_stmt->get_result();
        while ($history_row = $history_result->fetch_assoc()) {
            $status_history[] = $history_row;
        }
        $history_stmt->close();

        // Determine next possible actions based on current status
        $next_actions = [];
        $current_status = $booking_data['status'];

        switch ($current_status) {
            case 'assigned':
                $next_actions[] = ['status' => 'out_for_delivery', 'label' => 'Out for Delivery', 'icon' => 'fa-truck-loading'];
                break;
            case 'out_for_delivery':
                $next_actions[] = ['status' => 'delivered', 'label' => 'Delivered', 'icon' => 'fa-box-open'];
                break;
            case 'delivered':
                if ($booking_data['service_type'] === 'equipment_rental') {
                    $next_actions[] = ['status' => 'in_use', 'label' => 'In Use', 'icon' => 'fa-tools'];
                } else {
                    $next_actions[] = ['status' => 'completed', 'label' => 'Job Completed', 'icon' => 'fa-clipboard-check'];
                }
                break;
            case 'in_use':
                $next_actions[] = ['status' => 'awaiting_pickup', 'label' => 'Awaiting Pickup', 'icon' => 'fa-clock'];
                break;
            case 'awaiting_pickup':
                $next_actions[] = ['status' => 'pickedup', 'label' => 'Picked Up', 'icon' => 'fa-check-circle'];
                break;
            case 'pickedup':
                $next_actions[] = ['status' => 'completed', 'label' => 'Job Completed', 'icon' => 'fa-clipboard-check'];
                break;
            case 'relocation_requested':
                $next_actions[] = ['status' => 'relocated', 'label' => 'Relocated', 'icon' => 'fa-map-marker-alt'];
                break;
            case 'swap_requested':
                $next_actions[] = ['status' => 'swapped', 'label' => 'Swapped', 'icon' => 'fa-exchange-alt'];
                break;
            case 'pending':
            case 'scheduled':
            case 'completed':
            case 'cancelled':
            case 'relocated':
            case 'swapped':
            case 'extended':
                if ($current_status === 'cancelled' || $current_status === 'completed') {
                    $error_message = "This booking is already in a final state: " . ucwords(str_replace('_', ' ', $current_status)) . ". No further actions are available via this link.";
                }
                break;
            default:
                $error_message = "This booking status (" . ucwords(str_replace('_', ' ', $current_status)) . ") does not have defined driver updates via this link.";
                break;
        }
    } else {
        $error_message = "Booking not found or invalid access token.";
        $booking_data = null;
    }
    $stmt->close();
}
$conn->close();

// Helper function for status badge class
function getDriverStatusBadgeClass($status) {
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
        case 'pickedup': return 'bg-cyan-100 text-cyan-800';
        default: return 'bg-gray-100 text-gray-700';
    }
}

// Helper function to get appropriate button classes based on status
function getActionButtonClass($status) {
    switch ($status) {
        case 'out_for_delivery': return 'bg-purple-600 hover:bg-purple-700 text-white';
        case 'delivered': return 'bg-green-600 hover:bg-green-700 text-white';
        case 'in_use': return 'bg-teal-600 hover:bg-teal-700 text-white';
        case 'awaiting_pickup': return 'bg-pink-600 hover:bg-pink-700 text-white';
        case 'pickedup': return 'bg-cyan-600 hover:bg-cyan-700 text-white';
        case 'completed': return 'bg-gray-700 hover:bg-gray-800 text-white'; // Darker for completion
        case 'relocated': return 'bg-lime-600 hover:bg-lime-700 text-white';
        case 'swapped': return 'bg-emerald-600 hover:bg-emerald-700 text-white';
        default: return 'bg-blue-600 hover:bg-blue-700 text-white'; // Default primary
    }
}

// Helper function for timeline icons (reusing from admin/pages/bookings.php)
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
        case 'pickedup': return 'fa-truck-ramp-box'; // Specific icon for pickedup
        default: return 'fa-info-circle';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Driver Booking Update</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f3f4f6;
        }
        .status-button {
            transition: all 0.3s ease;
            transform: scale(1);
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        .status-button:hover {
            transform: scale(1.02);
            box-shadow: 0 6px 8px rgba(0, 0, 0, 0.15);
        }
        .status-button:active {
            transform: scale(0.98);
        }
        .toast {
            position: fixed;
            bottom: 1rem;
            left: 50%;
            transform: translateX(-50%);
            padding: 0.75rem 1.5rem;
            border-radius: 0.5rem;
            color: white;
            font-weight: 600;
            opacity: 0;
            transition: opacity 0.3s ease-in-out;
            z-index: 1000;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }
        .toast.show {
            opacity: 1;
        }
        .toast.success { background-color: #10b981; } /* Green-500 */
        .toast.error { background-color: #ef4444; } /* Red-500 */
        .toast.info { background-color: #3b82f6; } /* Blue-500 */
        .modal {
            display: none; /* Hidden by default */
            position: fixed; /* Stay in place */
            z-index: 100; /* Sit on top */
            left: 0;
            top: 0;
            width: 100%; /* Full width */
            height: 100%; /* Full height */
            overflow: auto; /* Enable scroll if needed */
            background-color: rgba(0,0,0,0.4); /* Black w/ opacity */
            align-items: center;
            justify-content: center;
        }
        .modal-content {
            background-color: #fefefe;
            margin: auto;
            padding: 20px;
            border: 1px solid #888;
            width: 80%;
            max-width: 500px;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.2);
        }
        /* Timeline specific styles */
        .timeline {
            position: relative;
            margin: 0 auto;
            padding: 20px 0;
        }
        .timeline::before {
            content: '';
            position: absolute;
            left: 50%;
            top: 0;
            width: 2px;
            height: 100%;
            background: #cbd5e0; /* gray-300 */
            transform: translateX(-50%);
        }
        .timeline-item {
            display: flex;
            justify-content: space-between;
            margin-bottom: 20px;
            position: relative;
        }
        .timeline-item:last-child {
            margin-bottom: 0;
        }
        .timeline-item-content {
            width: calc(50% - 20px);
            padding: 15px;
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
            position: relative;
        }
        .timeline-item:nth-child(odd) .timeline-item-content {
            text-align: right;
        }
        .timeline-item:nth-child(even) .timeline-item-content {
            text-align: left;
            margin-left: calc(50% + 20px);
        }
        .timeline-item-content::after {
            content: '';
            position: absolute;
            top: 20px;
            width: 0;
            height: 0;
            border-style: solid;
        }
        .timeline-item:nth-child(odd) .timeline-item-content::after {
            border-width: 10px 0 10px 10px;
            border-color: transparent transparent transparent #fff;
            right: -10px;
        }
        .timeline-item:nth-child(even) .timeline-item-content::after {
            border-width: 10px 10px 10px 0;
            border-color: transparent #fff transparent transparent;
            left: -10px;
        }
        .timeline-item-dot {
            position: absolute;
            left: 50%;
            top: 20px;
            width: 20px;
            height: 20px;
            background: #2563eb; /* blue-600 */
            border-radius: 50%;
            transform: translateX(-50%);
            z-index: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 0.75rem;
        }
        .timeline-item-dot.current {
            background: #10b981; /* green-500 */
            animation: pulse 1.5s infinite;
        }
        @keyframes pulse {
            0% { box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.4); }
            70% { box-shadow: 0 0 0 10px rgba(16, 185, 129, 0); }
            100% { box-shadow: 0 0 0 0 rgba(16, 185, 129, 0); }
        }
        .timeline-item-date {
            font-size: 0.85rem;
            color: #6b7280; /* gray-500 */
            margin-bottom: 5px;
        }
        .timeline-item-status {
            font-weight: 600;
            color: #1f2937; /* gray-800 */
        }
        .timeline-item-notes {
            font-size: 0.9rem;
            color: #4b5563; /* gray-700 */
            margin-top: 5px;
        }

        /* Responsive adjustments for timeline */
        @media (max-width: 768px) {
            .timeline::before {
                left: 20px;
                transform: translateX(0);
            }
            .timeline-item {
                flex-direction: column;
                align-items: flex-start;
            }
            .timeline-item-content {
                width: calc(100% - 40px);
                margin-left: 40px;
                text-align: left;
            }
            .timeline-item-content::after {
                border-width: 10px 10px 10px 0;
                border-color: transparent #fff transparent transparent;
                left: 30px;
                right: auto;
                top: 20px;
            }
            .timeline-item:nth-child(odd) .timeline-item-content {
                text-align: left;
            }
            .timeline-item:nth-child(odd) .timeline-item-content::after {
                left: 30px;
                right: auto;
                border-width: 10px 10px 10px 0;
                border-color: transparent #fff transparent transparent;
            }
            .timeline-item-dot {
                left: 20px;
                transform: translateX(-50%);
            }
        }
    </style>
</head>
<body class="bg-gray-100 min-h-screen flex items-center justify-center p-4">

    <div class="bg-white p-8 rounded-lg shadow-xl w-full max-w-2xl border border-blue-200">
        <?php if ($booking_data): ?>
            <h1 class="text-3xl font-bold text-gray-800 mb-6 text-center">Booking #BK-<?php echo htmlspecialchars($booking_data['booking_number']); ?></h1>

            <!-- What's Next? (Buttons) Section - Moved to Top -->
            <div class="mb-8 pb-6 border-b border-gray-200 text-center">
                <h3 class="text-2xl font-bold text-gray-800 mb-6">What's Next?</h3>
                <div class="flex flex-wrap justify-center gap-4">
                    <?php if (!empty($next_actions)): ?>
                        <?php foreach ($next_actions as $action): ?>
                            <button
                                class="status-button px-6 py-3 rounded-xl font-bold text-lg shadow-lg
                                <?php echo getActionButtonClass($action['status']); ?>"
                                data-status="<?php echo htmlspecialchars($action['status']); ?>"
                                data-booking-id="<?php echo htmlspecialchars($booking_data['id']); ?>"
                                data-token="<?php echo htmlspecialchars($token); ?>"
                                data-label="<?php echo htmlspecialchars($action['label']); ?>">
                                <i class="fas <?php echo htmlspecialchars($action['icon']); ?> mr-2"></i>
                                <?php echo htmlspecialchars($action['label']); ?>
                            </button>
                        <?php endforeach; ?>
                    <?php endif; ?>

                    <?php
                    // Display "Share Live Location" button if the booking is not in a final/cancelled state
                    $active_statuses_for_location = ['assigned', 'out_for_delivery', 'delivered', 'in_use', 'awaiting_pickup', 'pickedup', 'relocation_requested', 'swap_requested'];
                    if (in_array($booking_data['status'], $active_statuses_for_location)):
                    ?>
                        <button
                            id="share-location-btn"
                            class="status-button px-6 py-3 rounded-xl text-white font-bold text-lg shadow-lg bg-gray-700 hover:bg-gray-800">
                            <i class="fas fa-map-marker-alt mr-2"></i>Share Live Location
                        </button>
                    <?php endif; ?>

                    <?php if (empty($next_actions) && !in_array($booking_data['status'], $active_statuses_for_location)): ?>
                        <p class="text-gray-600 text-lg">No further actions available for this booking status.</p>
                    <?php endif; ?>
                </div>
            </div>
            <!-- End What's Next? Section -->

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8 pb-6 border-b border-gray-200">
                <div>
                    <p class="text-gray-600 mb-2"><span class="font-semibold text-gray-700">Service Type:</span> <?php echo htmlspecialchars(ucwords(str_replace('_', ' ', $booking_data['service_type']))); ?></p>
                    <p class="text-gray-600 mb-2"><span class="font-semibold text-gray-700">Current Status:</span> <span class="px-3 py-1 rounded-full text-sm font-semibold <?php echo getDriverStatusBadgeClass($booking_data['status']); ?>"><?php echo htmlspecialchars(strtoupper(str_replace('_', ' ', $booking_data['status']))); ?></span></p>
                    <p class="text-gray-600 mb-2"><span class="font-semibold text-gray-700">Start Date:</span> <?php echo htmlspecialchars($booking_data['start_date']); ?></p>
                    <?php if (!empty($booking_data['end_date'])): ?>
                        <p class="text-gray-600 mb-2"><span class="font-semibold text-gray-700">End Date:</span> <?php echo htmlspecialchars($booking_data['end_date']); ?></p>
                    <?php endif; ?>
                    <p class="text-gray-600 mb-2"><span class="font-semibold text-gray-700">Delivery Location:</span> <?php echo htmlspecialchars($booking_data['delivery_location']); ?></p>
                    <?php if (!empty($booking_data['delivery_instructions'])): ?>
                        <p class="text-gray-600 mb-2"><span class="font-semibold text-gray-700">Delivery Instructions:</span> <?php echo htmlspecialchars($booking_data['delivery_instructions']); ?></p>
                    <?php endif; ?>
                </div>
                <div>
                    <?php if (!empty($booking_data['pickup_location'])): ?>
                        <p class="text-gray-600 mb-2"><span class="font-semibold text-gray-700">Pickup Location:</span> <?php echo htmlspecialchars($booking_data['pickup_location']); ?></p>
                    <?php endif; ?>
                    <?php if (!empty($booking_data['pickup_instructions'])): ?>
                        <p class="text-gray-600 mb-2"><span class="font-semibold text-gray-700">Pickup Instructions:</span> <?php echo htmlspecialchars($booking_data['pickup_instructions']); ?></p>
                    <?php endif; ?>
                    <p class="text-gray-600 mb-2"><span class="font-semibold text-gray-700">Live Load Requested:</span> <?php echo $booking_data['live_load_requested'] ? 'Yes' : 'No'; ?></p>
                    <p class="text-gray-600 mb-2"><span class="font-semibold text-gray-700">Urgent Request:</span> <?php echo $booking_data['is_urgent'] ? 'Yes' : 'No'; ?></p>
                    <?php if (!empty($booking_data['vendor_name'])): ?>
                        <p class="text-gray-600 mb-2"><span class="font-semibold text-gray-700">Assigned Vendor:</span> <?php echo htmlspecialchars($booking_data['vendor_name']); ?></p>
                        <p class="text-gray-600 mb-2"><span class="font-semibold text-gray-700">Vendor Phone:</span> <?php echo htmlspecialchars($booking_data['vendor_phone'] ?? 'N/A'); ?></p>
                    <?php endif; ?>
                </div>
            </div>

            <?php if ($booking_data['service_type'] === 'equipment_rental' && !empty($booking_data['equipment_details'])): ?>
                <h3 class="text-xl font-semibold text-gray-700 mb-4">Equipment Details</h3>
                <ul class="list-disc list-inside space-y-2 pl-4 mb-8">
                    <?php foreach ($booking_data['equipment_details'] as $item): ?>
                        <li><strong><?php echo htmlspecialchars($item['quantity']); ?>x</strong> <?php echo htmlspecialchars($item['equipment_name']); ?> (<?php echo htmlspecialchars($item['duration_days']); ?> days)</li>
                        <?php if (!empty($item['specific_needs'])): ?>
                            <p class="text-xs text-gray-600 ml-4">- Needs: <?php echo htmlspecialchars($item['specific_needs']); ?></p>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </ul>
            <?php elseif ($booking_data['service_type'] === 'junk_removal' && !empty($booking_data['junk_details'])): ?>
                <h3 class="text-xl font-semibold text-gray-700 mb-4">Junk Removal Details</h3>
                <ul class="list-disc list-inside space-y-2 pl-4 mb-8">
                    <?php if (!empty($booking_data['junk_details']['junkItems'])): ?>
                        <?php foreach ($booking_data['junk_details']['junkItems'] as $item): ?>
                            <li><?php echo htmlspecialchars($item['itemType'] ?? 'N/A'); ?> (Qty: <?php echo htmlspecialchars($item['quantity'] ?? 'N/A'); ?>)</li>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <li>No specific junk items detailed.</li>
                    <?php endif; ?>
                    <?php if (!empty($booking_data['junk_details']['recommendedDumpsterSize'])): ?>
                        <li>Recommended Dumpster Size: <?php echo htmlspecialchars($booking_data['junk_details']['recommendedDumpsterSize']); ?></li>
                    <?php endif; ?>
                    <?php if (!empty($booking_data['junk_details']['additionalComment'])): ?>
                        <li>Additional Comments: <?php echo htmlspecialchars($booking_data['junk_details']['additionalComment']); ?></li>
                    <?php endif; ?>
                    <?php if (!empty($booking_data['junk_details']['media_urls'])): ?>
                        <h5 class="text-md font-semibold text-gray-700 mt-2">Uploaded Media:</h5>
                        <div class="grid grid-cols-2 md:grid-cols-3 gap-2 mt-2">
                            <?php foreach ($booking_data['junk_details']['media_urls'] as $media_url): ?>
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
            <?php endif; ?>

            <!-- Timeline View -->
            <?php if (!empty($status_history)): ?>
                <div class="mt-8 pt-6 border-t border-gray-200">
                    <h3 class="text-2xl font-bold text-gray-800 mb-6 text-center">Booking History</h3>
                    <div class="timeline">
                        <?php foreach ($status_history as $index => $history_item):
                            $is_current = ($history_item['status'] === $booking_data['status']);
                        ?>
                            <div class="timeline-item">
                                <div class="timeline-item-dot <?php echo $is_current ? 'current' : ''; ?>">
                                    <i class="fas <?php echo getTimelineIconClass($history_item['status']); ?>"></i>
                                </div>
                                <div class="timeline-item-content">
                                    <div class="timeline-item-date">
                                        <?php echo (new DateTime($history_item['status_time']))->format('M d, Y h:i A'); ?>
                                    </div>
                                    <div class="timeline-item-status">
                                        <?php echo htmlspecialchars(ucwords(str_replace('_', ' ', $history_item['status']))); ?>
                                    </div>
                                    <?php if (!empty($history_item['notes'])): ?>
                                        <div class="timeline-item-notes">
                                            <?php echo htmlspecialchars($history_item['notes']); ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

        <?php else: ?>
            <div class="text-center p-8 bg-red-50 border border-red-200 rounded-lg">
                <h1 class="text-2xl font-bold text-red-800 mb-4">Access Denied!</h1>
                <p class="text-red-700"><?php echo htmlspecialchars($error_message); ?></p>
                <p class="text-red-700 mt-2">Please ensure you have the correct and valid link.</p>
            </div>
        <?php endif; ?>
    </div>

    <!-- Toast Notification -->
    <div id="toast" class="toast"></div>

    <!-- Image Modal -->
    <div id="image-modal" class="modal">
        <div class="modal-content p-0 relative">
            <span class="absolute top-2 right-2 text-gray-700 text-3xl font-bold cursor-pointer" onclick="hideModal('image-modal')">&times;</span>
            <img id="modal-image" src="" alt="Full size image" class="w-full h-auto max-h-[80vh] object-contain rounded-lg">
        </div>
    </div>

    <!-- Custom Confirmation Modal -->
    <div id="custom-confirmation-modal" class="modal">
        <div class="modal-content">
            <h3 id="confirmation-modal-title" class="text-xl font-bold mb-4 text-gray-800"></h3>
            <p id="confirmation-modal-message" class="mb-6 text-gray-700"></p>
            <div class="flex justify-end space-x-4">
                <button type="button" class="px-4 py-2 bg-gray-300 text-gray-800 rounded-lg hover:bg-gray-400" onclick="hideModal('custom-confirmation-modal'); if(window.currentConfirmationCallback) window.currentConfirmationCallback(false);">Cancel</button>
                <button type="button" id="confirmation-modal-confirm-btn" class="px-4 py-2 text-white rounded-lg font-semibold"></button>
            </div>
        </div>
    </div>

<script>
    // Global function to show toast messages
    function showToast(message, type = 'info') {
        const toast = document.getElementById('toast');
        toast.textContent = message;
        toast.className = `toast show ${type}`; // Reset classes and add new ones
        setTimeout(() => {
            toast.classList.remove('show');
        }, 3000); // Hide after 3 seconds
    }

    // Global function to show image modal
    function showImageModal(imageUrl) {
        const modal = document.getElementById('image-modal');
        const modalImage = document.getElementById('modal-image');
        modalImage.src = imageUrl;
        modal.style.display = 'flex'; // Use flex to center
    }

    // Global function to hide any modal
    function hideModal(modalId) {
        document.getElementById(modalId).style.display = 'none';
    }

    let currentConfirmationCallback = null; // To hold the callback function

    function showCustomConfirmation(title, message, callback, confirmButtonClass = 'bg-blue-600') {
        const modal = document.getElementById('custom-confirmation-modal');
        const titleElement = document.getElementById('confirmation-modal-title');
        const messageElement = document.getElementById('confirmation-modal-message');
        const confirmButton = document.getElementById('confirmation-modal-confirm-btn');

        titleElement.textContent = title;
        messageElement.textContent = message;
        confirmButton.className = `px-4 py-2 text-white rounded-lg font-semibold ${confirmButtonClass}`;
        confirmButton.textContent = title; // Use the title as button text

        currentConfirmationCallback = callback;

        confirmButton.onclick = () => {
            hideModal('custom-confirmation-modal');
            if (currentConfirmationCallback) currentConfirmationCallback(true);
        };

        modal.style.display = 'flex';
    }


    document.addEventListener('click', function(event) {
        const target = event.target.closest('button');
        if (!target) return;

        // Handle status update buttons
        if (target.classList.contains('status-button') && target.dataset.status) {
            const newStatus = target.dataset.status;
            const bookingId = target.dataset.bookingId;
            const token = target.dataset.token;
            const label = target.dataset.label;
            // Removed isDestructive as "Cancel Booking" is replaced

            let confirmationMessage = `Are you sure you want to change the status to "${label}"?`;

            showCustomConfirmation(
                label, // Title for the modal, also used as button text
                confirmationMessage,
                async (confirmed) => {
                    if (confirmed) {
                        showToast(`Updating status to ${label}...`, 'info');
                        try {
                            const formData = new FormData();
                            formData.append('booking_id', bookingId);
                            formData.append('new_status', newStatus);
                            formData.append('token', token); // Pass the token for validation

                            const response = await fetch('/api/driver/update_status.php', {
                                method: 'POST',
                                body: formData
                            });
                            const result = await response.json();

                            if (result.success) {
                                showToast(result.message, 'success');
                                // Reload the page to reflect the new status and actions
                                window.location.reload();
                            } else {
                                showToast('Error: ' + result.message, 'error');
                            }
                        } catch (error) {
                            console.error('Status update error:', error);
                            showToast('An unexpected error occurred.', 'error');
                        }
                    }
                },
                // Pass the dynamically determined class for the confirm button
                target.classList.value.split(' ').filter(cls => cls.startsWith('bg-') || cls.startsWith('hover:bg-')).join(' ')
            );
        }

        // Handle "Share Live Location" button
        if (target.id === 'share-location-btn') {
            const bookingId = <?php echo json_encode($booking_data['id'] ?? null); ?>;
            const token = <?php echo json_encode($token); ?>;

            if (!bookingId || !token) {
                showToast('Booking data missing for location sharing.', 'error');
                return;
            }

            showToast('Getting your location...', 'info');

            if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition(async (position) => {
                    const latitude = position.coords.latitude;
                    const longitude = position.coords.longitude;

                    showToast('Sharing location...', 'info');

                    try {
                        const formData = new FormData();
                        formData.append('booking_id', bookingId);
                        formData.append('token', token);
                        formData.append('latitude', latitude);
                        formData.append('longitude', longitude);

                        const response = await fetch('/api/driver/share_location.php', {
                            method: 'POST',
                            body: formData
                        });
                        const result = await response.json();

                        if (result.success) {
                            showToast(result.message, 'success');
                        } else {
                            showToast('Error sharing location: ' + result.message, 'error');
                        }
                    } catch (error) {
                        console.error('Share location API error:', error);
                        showToast('An unexpected error occurred while sharing location.', 'error');
                    }
                }, (error) => {
                    // Handle geolocation errors
                    let errorMessage = 'Unable to retrieve your location.';
                    switch(error.code) {
                        case error.PERMISSION_DENIED:
                            errorMessage = 'Location access denied. Please enable location services for this site.';
                            break;
                        case error.POSITION_UNAVAILABLE:
                            errorMessage = 'Location information is unavailable.';
                            break;
                        case error.TIMEOUT:
                            errorMessage = 'The request to get user location timed out.';
                            break;
                        case error.UNKNOWN_ERROR:
                            errorMessage = 'An unknown error occurred.';
                            break;
                    }
                    showToast(errorMessage, 'error');
                    console.error('Geolocation error:', error);
                }, {
                    enableHighAccuracy: true,
                    timeout: 10000,
                    maximumAge: 0
                });
            } else {
                showToast('Geolocation is not supported by your browser.', 'error');
            }
        }
    });

</script>
</body>
</html>