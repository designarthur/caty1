<?php
// admin/pages/dashboard.php

// Ensure session is started and user is logged in as admin
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/session.php'; // For has_role and user_id

if (!is_logged_in() || !has_role('admin')) {
    echo '<div class="text-red-500 text-center p-8">Unauthorized access.</div>';
    exit;
}

// Initialize counts
$total_users = 0;
$total_quotes = 0;
$pending_quotes = 0;
$total_bookings = 0;
$active_bookings = 0;
$total_vendors = 0;
$total_equipment_items = 0;

// Fetch statistics
// Total Users
$stmt = $conn->prepare("SELECT COUNT(*) AS count FROM users");
$stmt->execute();
$total_users = $stmt->get_result()->fetch_assoc()['count'];
$stmt->close();

// Total Quotes & Pending Quotes
$stmt = $conn->prepare("SELECT COUNT(*) AS total_quotes, SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) AS pending_quotes FROM quotes");
$stmt->execute();
$result = $stmt->get_result()->fetch_assoc();
$total_quotes = $result['total_quotes'];
$pending_quotes = $result['pending_quotes'];
$stmt->close();

// Total Bookings & Active Bookings (assuming 'scheduled', 'out_for_delivery', 'delivered', 'in_use', 'awaiting_pickup' as active)
$stmt = $conn->prepare("SELECT COUNT(*) AS total_bookings, SUM(CASE WHEN status IN ('scheduled', 'out_for_delivery', 'delivered', 'in_use', 'awaiting_pickup') THEN 1 ELSE 0 END) AS active_bookings FROM bookings");
$stmt->execute();
$result = $stmt->get_result()->fetch_assoc();
$total_bookings = $result['total_bookings'];
$active_bookings = $result['active_bookings'];
$stmt->close();

// Total Vendors
$stmt = $conn->prepare("SELECT COUNT(*) AS count FROM vendors WHERE is_active = TRUE"); // Counting active vendors
$stmt->execute();
$total_vendors = $stmt->get_result()->fetch_assoc()['count'];
$stmt->close();

// Total Equipment Items
$stmt = $conn->prepare("SELECT COUNT(*) AS count FROM equipment WHERE is_active = TRUE"); // Counting active equipment
$stmt->execute();
$total_equipment_items = $stmt->get_result()->fetch_assoc()['count'];
$stmt->close();


// Fetch Recent Quotes (e.g., last 5 pending or newly quoted)
$recent_quotes = [];
$stmt = $conn->prepare("SELECT q.id, q.service_type, q.status, q.created_at, u.first_name, u.last_name FROM quotes q JOIN users u ON q.user_id = u.id ORDER BY q.created_at DESC LIMIT 5");
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $recent_quotes[] = $row;
}
$stmt->close();

// Fetch Recent Bookings (e.g., last 5 created or updated)
$recent_bookings = [];
$stmt = $conn->prepare("SELECT b.id, b.booking_number, b.service_type, b.status, b.created_at, u.first_name, u.last_name FROM bookings b JOIN users u ON b.user_id = u.id ORDER BY b.created_at DESC LIMIT 5");
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $recent_bookings[] = $row;
}
$stmt->close();

$conn->close();

// Helper function for status badges
function getAdminStatusBadgeClass($status) {
    switch ($status) {
        case 'pending': return 'bg-yellow-100 text-yellow-800';
        case 'quoted': return 'bg-blue-100 text-blue-800';
        case 'accepted': return 'bg-green-100 text-green-800';
        case 'rejected': return 'bg-red-100 text-red-800';
        case 'converted_to_booking': return 'bg-purple-100 text-purple-800';
        case 'scheduled': return 'bg-indigo-100 text-indigo-800';
        case 'out_for_delivery': return 'bg-orange-100 text-orange-800';
        case 'delivered': return 'bg-green-100 text-green-800';
        case 'in_use': return 'bg-teal-100 text-teal-800';
        case 'awaiting_pickup': return 'bg-pink-100 text-pink-800';
        case 'completed': return 'bg-gray-100 text-gray-800';
        case 'cancelled': return 'bg-red-100 text-red-800';
        default: return 'bg-gray-100 text-gray-700';
    }
}
?>

<h1 class="text-3xl font-bold text-gray-800 mb-8">Admin Dashboard Overview</h1>

<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6 mb-8">
    <div class="bg-white p-6 rounded-lg shadow-md border border-blue-200 text-center">
        <div class="text-blue-600 mb-3"><i class="fas fa-users fa-3x"></i></div>
        <p class="text-4xl font-bold text-gray-800"><?php echo $total_users; ?></p>
        <p class="text-gray-600">Total Users</p>
    </div>
    <div class="bg-white p-6 rounded-lg shadow-md border border-yellow-200 text-center">
        <div class="text-yellow-600 mb-3"><i class="fas fa-file-invoice fa-3x"></i></div>
        <p class="text-4xl font-bold text-gray-800"><?php echo $total_quotes; ?></p>
        <p class="text-gray-600">Total Quotes (<?php echo $pending_quotes; ?> Pending)</p>
        <a href="#" onclick="loadAdminSection('quotes'); return false;" class="text-sm text-blue-600 hover:underline">Manage Quotes</a>
    </div>
    <div class="bg-white p-6 rounded-lg shadow-md border border-green-200 text-center">
        <div class="text-green-600 mb-3"><i class="fas fa-book-open fa-3x"></i></div>
        <p class="text-4xl font-bold text-gray-800"><?php echo $total_bookings; ?></p>
        <p class="text-gray-600">Total Bookings (<?php echo $active_bookings; ?> Active)</p>
        <a href="#" onclick="loadAdminSection('bookings'); return false;" class="text-sm text-blue-600 hover:underline">Manage Bookings</a>
    </div>
    <div class="bg-white p-6 rounded-lg shadow-md border border-purple-200 text-center">
        <div class="text-purple-600 mb-3"><i class="fas fa-industry fa-3x"></i></div>
        <p class="text-4xl font-bold text-gray-800"><?php echo $total_vendors; ?></p>
        <p class="text-gray-600">Active Vendors</p>
        <a href="#" onclick="loadAdminSection('vendors'); return false;" class="text-sm text-blue-600 hover:underline">Manage Vendors</a>
    </div>
    <div class="bg-white p-6 rounded-lg shadow-md border border-teal-200 text-center">
        <div class="text-teal-600 mb-3"><i class="fas fa-dumpster fa-3x"></i></div>
        <p class="text-4xl font-bold text-gray-800"><?php echo $total_equipment_items; ?></p>
        <p class="text-gray-600">Active Equipment Types</p>
        <a href="#" onclick="loadAdminSection('equipment'); return false;" class="text-sm text-blue-600 hover:underline">Manage Equipment</a>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
    <div class="bg-white p-6 rounded-lg shadow-md border border-blue-200">
        <h2 class="text-xl font-semibold text-gray-700 mb-4 flex items-center"><i class="fas fa-history mr-2 text-blue-600"></i>Recent Quotes</h2>
        <?php if (empty($recent_quotes)): ?>
            <p class="text-gray-600 text-center">No recent quotes.</p>
        <?php else: ?>
            <ul class="divide-y divide-gray-200">
                <?php foreach ($recent_quotes as $quote): ?>
                    <li class="py-3 flex justify-between items-center">
                        <div>
                            <p class="font-medium text-gray-800">#Q<?php echo htmlspecialchars($quote['id']); ?> - <?php echo htmlspecialchars($quote['first_name'] . ' ' . $quote['last_name']); ?></p>
                            <p class="text-sm text-gray-600"><?php echo ucwords(str_replace('_', ' ', $quote['service_type'])); ?> Request</p>
                            <p class="text-xs text-gray-500"><?php echo (new DateTime($quote['created_at']))->format('M d, Y H:i A'); ?></p>
                        </div>
                        <div class="flex items-center space-x-2">
                            <span class="px-2 py-1 text-xs font-semibold rounded-full <?php echo getAdminStatusBadgeClass($quote['status']); ?>">
                                <?php echo htmlspecialchars(strtoupper(str_replace('_', ' ', $quote['status']))); ?>
                            </span>
                            <a href="#" onclick="loadAdminSection('quotes', {quote_id: <?php echo $quote['id']; ?>}); return false;" class="text-blue-600 hover:text-blue-800 text-sm">View</a>
                        </div>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>

    <div class="bg-white p-6 rounded-lg shadow-md border border-green-200">
        <h2 class="text-xl font-semibold text-gray-700 mb-4 flex items-center"><i class="fas fa-calendar-check mr-2 text-green-600"></i>Recent Bookings</h2>
        <?php if (empty($recent_bookings)): ?>
            <p class="text-gray-600 text-center">No recent bookings.</p>
        <?php else: ?>
            <ul class="divide-y divide-gray-200">
                <?php foreach ($recent_bookings as $booking): ?>
                    <li class="py-3 flex justify-between items-center">
                        <div>
                            <p class="font-medium text-gray-800">#BK-<?php echo htmlspecialchars($booking['booking_number']); ?> - <?php echo htmlspecialchars($booking['first_name'] . ' ' . $booking['last_name']); ?></p>
                            <p class="text-sm text-gray-600"><?php echo ucwords(str_replace('_', ' ', $booking['service_type'])); ?> Booking</p>
                            <p class="text-xs text-gray-500"><?php echo (new DateTime($booking['created_at']))->format('M d, Y H:i A'); ?></p>
                        </div>
                        <div class="flex items-center space-x-2">
                            <span class="px-2 py-1 text-xs font-semibold rounded-full <?php echo getAdminStatusBadgeClass($booking['status']); ?>">
                                <?php echo htmlspecialchars(strtoupper(str_replace('_', ' ', $booking['status']))); ?>
                            </span>
                            <a href="#" onclick="loadAdminSection('bookings', {booking_id: <?php echo $booking['id']; ?>}); return false;" class="text-blue-600 hover:text-blue-800 text-sm">View</a>
                        </div>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>
</div>