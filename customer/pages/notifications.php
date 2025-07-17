<?php
// customer/pages/notifications.php

// Ensure session is started and user is logged in
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/session.php'; 

if (!is_logged_in()) {
    echo '<div class="text-red-500 text-center p-8">You must be logged in to view this content.</div>';
    exit;
}

$user_id = $_SESSION['user_id'];
$notifications = [];

// --- Pagination Variables ---
$items_per_page = 25; // Or make this a user setting
$current_page = filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT, ['options' => ['default' => 1, 'min_range' => 1]]);
$offset = ($current_page - 1) * $items_per_page;

// Fetch total count for pagination
$stmt_count = $conn->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ?");
$stmt_count->bind_param("i", $user_id);
$stmt_count->execute();
$total_notifications = $stmt_count->get_result()->fetch_row()[0];
$stmt_count->close();
$total_pages = ceil($total_notifications / $items_per_page);

// Fetch paginated notifications for the logged-in user
$stmt = $conn->prepare("SELECT id, type, message, link, is_read, created_at FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT ? OFFSET ?");
$stmt->bind_param("iii", $user_id, $items_per_page, $offset);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $notifications[] = $row;
}
$stmt->close();
$conn->close();

// Helper function to get icon based on notification type
function getNotificationIcon($type) {
    switch ($type) {
        case 'booking_status_update':
        case 'booking_confirmed':
        case 'junk_removal_confirmed':
        case 'booking_assigned_vendor':
            return 'fas fa-truck text-blue-500';
        case 'new_invoice':
        case 'payment_due':
        case 'payment_received':
        case 'payment_failed':
        case 'partial_payment':
            return 'fas fa-receipt text-green-500';
        case 'new_quote':
        case 'quote_accepted':
        case 'quote_rejected':
            return 'fas fa-file-invoice text-purple-500';
        case 'relocation_request_confirmation':
        case 'swap_request_confirmation':
        case 'pickup_request_confirmation':
            return 'fas fa-tools text-orange-500';
        case 'profile_update':
        case 'password_change':
            return 'fas fa-user-cog text-indigo-500';
        default:
            return 'fas fa-bell text-gray-500';
    }
}
?>

<h1 class="text-3xl font-bold text-gray-800 mb-8">Notifications</h1>

<div class="bg-white p-4 sm:p-6 rounded-lg shadow-md border border-gray-200">
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-4 gap-4">
        <h2 class="text-xl font-semibold text-gray-700 flex items-center"><i class="fas fa-bell mr-2 text-blue-600"></i>Your Alerts & Updates</h2>
        <div class="flex space-x-2">
            <button id="mark-all-read-btn" class="px-4 py-2 bg-blue-100 text-blue-700 rounded-lg hover:bg-blue-200 transition-colors duration-200 text-sm font-medium">
                <i class="fas fa-check-double mr-2"></i>Mark All Read
            </button>
            <button id="delete-all-btn" class="px-4 py-2 bg-red-100 text-red-700 rounded-lg hover:bg-red-200 transition-colors duration-200 text-sm font-medium">
                <i class="fas fa-trash-alt mr-2"></i>Delete All
            </button>
        </div>
    </div>

    <?php if (empty($notifications)): ?>
        <p class="text-gray-600 text-center p-6">You have no notifications yet.</p>
    <?php else: ?>
        <div id="notifications-list" class="space-y-3">
            <?php foreach ($notifications as $notification): ?>
                <div class="notification-item flex items-start p-4 rounded-lg shadow-sm border <?php echo $notification['is_read'] ? 'bg-gray-50 border-gray-200' : 'bg-blue-50 border-blue-200 font-semibold'; ?>" data-id="<?php echo htmlspecialchars($notification['id']); ?>">
                    <div class="flex-shrink-0 mr-4 mt-1">
                        <i class="<?php echo getNotificationIcon($notification['type']); ?> text-xl"></i>
                    </div>
                    <div class="flex-grow">
                        <p class="text-sm text-gray-500 mb-1"><?php echo (new DateTime($notification['created_at']))->format('M d, Y H:i A'); ?></p>
                        <p class="<?php echo $notification['is_read'] ? 'text-gray-700' : 'text-gray-800'; ?> mb-2">
                            <?php echo htmlspecialchars($notification['message']); ?>
                        </p>
                        <?php if (!empty($notification['link'])): ?>
                            <button class="text-blue-600 hover:underline text-sm font-medium view-notification-link" data-link="<?php echo htmlspecialchars($notification['link']); ?>" data-id="<?php echo htmlspecialchars($notification['id']); ?>">
                                View Details <i class="fas fa-arrow-right text-xs ml-1"></i>
                            </button>
                        <?php endif; ?>
                    </div>
                    <div class="flex flex-shrink-0 items-center space-x-2 ml-4">
                        <?php if (!$notification['is_read']): ?>
                            <button class="mark-read-btn text-green-500 hover:text-green-700 text-lg" title="Mark as Read">
                                <i class="fas fa-check-circle"></i>
                            </button>
                        <?php endif; ?>
                        <button class="delete-notification-btn text-red-500 hover:text-red-700 text-lg" title="Delete Notification">
                            <i class="fas fa-times-circle"></i>
                        </button>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        
        <nav class="mt-6 flex items-center justify-between border-t border-gray-200 px-4 py-3 sm:px-6">
            <div class="flex-1 flex justify-between sm:hidden">
                <button onclick="loadCustomerSection('notifications', {page: <?php echo max(1, $current_page - 1); ?>})" class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">Previous</button>
                <button onclick="loadCustomerSection('notifications', {page: <?php echo min($total_pages, $current_page + 1); ?>})" class="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">Next</button>
            </div>
            <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
                <div>
                    <p class="text-sm text-gray-700">
                        Showing <span class="font-medium"><?php echo $offset + 1; ?></span> to <span class="font-medium"><?php echo min($offset + $items_per_page, $total_notifications); ?></span> of <span class="font-medium"><?php echo $total_notifications; ?></span> results
                    </p>
                </div>
                <div>
                    <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px" aria-label="Pagination">
                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                            <button onclick="loadCustomerSection('notifications', {page: <?php echo $i; ?>})" class="<?php echo $i == $current_page ? 'z-10 bg-blue-50 border-blue-500 text-blue-600' : 'bg-white border-gray-300 text-gray-700 hover:bg-gray-50'; ?> relative inline-flex items-center px-4 py-2 border text-sm font-medium">
                                <?php echo $i; ?>
                            </button>
                        <?php endfor; ?>
                    </nav>
                </div>
            </div>
        </nav>
    <?php endif; ?>
</div>

<script>
    (function() {
        // Function to update the notification bell count in the header
        // This function is now defined globally in customer/includes/header.php
        // so it no longer needs to be defined here.

        // --- Event Delegation for Notification Actions ---
        const notificationsContainer = document.getElementById('notifications-list');
        if (notificationsContainer) {
            notificationsContainer.addEventListener('click', async function(event) {
                const button = event.target.closest('button');
                if (!button) return;

                const notificationItem = button.closest('.notification-item');
                const notificationId = notificationItem.dataset.id;
                let action = '';

                if (button.classList.contains('mark-read-btn')) action = 'mark_read';
                else if (button.classList.contains('delete-notification-btn')) action = 'delete';
                else if (button.classList.contains('view-notification-link')) {
                    // Mark as read first, then navigate
                    const formData = new FormData();
                    formData.append('action', 'mark_read');
                    formData.append('id', notificationId);
                    await fetch('/api/customer/notifications.php', { method: 'POST', body: formData });
                    
                    const link = button.dataset.link;
                    const urlParts = link.split('?');
                    const section = urlParts[0];
                    const params = Object.fromEntries(new URLSearchParams(urlParts[1] || ''));
                    window.loadCustomerSection(section, params);
                    return;
                }

                if (action) {
                    const formData = new FormData();
                    formData.append('action', action);
                    formData.append('id', notificationId);
                    
                    try {
                        const response = await fetch('/api/customer/notifications.php', { method: 'POST', body: formData });
                        const result = await response.json();
                        if (result.success) {
                            window.showToast(result.message, 'success');
                            window.loadCustomerSection('notifications'); // Reload to reflect changes
                        } else {
                            window.showToast(result.message, 'error');
                        }
                    } catch (error) {
                         window.showToast('An error occurred.', 'error');
                    }
                }
            });
        }
        
        // --- Bulk Action Buttons ---
        document.getElementById('mark-all-read-btn')?.addEventListener('click', () => handleBulkAction('mark_read', 'Mark all as read?'));
        document.getElementById('delete-all-btn')?.addEventListener('click', () => handleBulkAction('delete', 'Are you sure you want to delete all notifications? This cannot be undone.'));

        function handleBulkAction(action, message) {
            window.showConfirmationModal('Confirm Action', message, async (confirmed) => {
                if(confirmed) {
                    const formData = new FormData();
                    formData.append('action', action);
                    formData.append('id', 'all');
                    
                    try {
                        const response = await fetch('/api/customer/notifications.php', { method: 'POST', body: formData });
                        const result = await response.json();
                         if (result.success) {
                            window.showToast(result.message, 'success');
                            window.loadCustomerSection('notifications');
                        } else {
                            window.showToast(result.message, 'error');
                        }
                    } catch (error) {
                        window.showToast('An error occurred.', 'error');
                    }
                }
            });
        }
        
        // Initial call to update bell count when the page loads
        // This call is now redundant as it's handled globally by customer/includes/header.php
        // updateNotificationBell(); 
    })();
</script>