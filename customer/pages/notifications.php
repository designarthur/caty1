<?php
// customer/pages/notifications.php

// Ensure session is started and user is logged in
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/session.php'; // For has_role and user_id

if (!is_logged_in()) {
    echo '<div class="text-red-500 text-center p-8">You must be logged in to view this content.</div>';
    exit;
}

$user_id = $_SESSION['user_id'];
$notifications = [];

// Fetch all notifications for the logged-in user
$stmt = $conn->prepare("SELECT id, type, message, link, is_read, created_at FROM notifications WHERE user_id = ? ORDER BY created_at DESC");
$stmt->bind_param("i", $user_id);
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
        case 'new_quote': return 'fas fa-file-invoice';
        case 'quote_accepted': return 'fas fa-check-circle';
        case 'quote_rejected': return 'fas fa-times-circle';
        case 'payment_due': return 'fas fa-exclamation-triangle';
        case 'payment_received': return 'fas fa-dollar-sign';
        case 'booking_status_update': return 'fas fa-truck-moving';
        case 'system_message': return 'fas fa-info-circle';
        case 'new_invoice': return 'fas fa-receipt'; // Assuming this might be used, though backend uses payment_due now
        default: return 'fas fa-bell';
    }
}

// Helper for status badge
function getNotificationStatusBadge($is_read) {
    if ($is_read) {
        return '<span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-800">Read</span>';
    } else {
        return '<span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800">Unread</span>';
    }
}
?>

<h1 class="text-3xl font-bold text-gray-800 mb-8">Notifications</h1>

<div class="bg-white p-6 rounded-lg shadow-md border border-blue-200">
    <h2 class="text-xl font-semibold text-gray-700 mb-4 flex items-center"><i class="fas fa-bell mr-2 text-blue-600"></i>Your Alerts & Updates</h2>

    <div class="flex justify-end space-x-2 mb-4">
        <button class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors duration-200 shadow-md mark-all-read-btn">
            <i class="fas fa-check-double mr-2"></i>Mark All Read
        </button>
        <button class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors duration-200 shadow-md delete-all-notifications-btn">
            <i class="fas fa-trash-alt mr-2"></i>Delete All
        </button>
    </div>

    <?php if (empty($notifications)): ?>
        <p class="text-gray-600 text-center p-4">You have no notifications yet.</p>
    <?php else: ?>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-blue-50">
                    <tr>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Type</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Message</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Date</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Status</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php foreach ($notifications as $notification): ?>
                        <tr class="<?php echo $notification['is_read'] ? 'text-gray-500' : 'text-gray-900 font-medium'; ?>" data-id="<?php echo htmlspecialchars($notification['id']); ?>">
                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                <i class="<?php echo getNotificationIcon($notification['type']); ?> mr-2 <?php echo $notification['is_read'] ? 'text-gray-400' : 'text-blue-500'; ?>"></i>
                                <?php echo htmlspecialchars(ucwords(str_replace('_', ' ', $notification['type']))); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                <?php echo htmlspecialchars($notification['message']); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                <?php echo (new DateTime($notification['created_at']))->format('Y-m-d H:i'); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                <?php echo getNotificationStatusBadge($notification['is_read']); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <?php if (!empty($notification['link'])): ?>
                                    <button class="text-blue-600 hover:text-blue-900 mr-2 view-notification-details-btn" data-link="<?php echo htmlspecialchars($notification['link']); ?>" data-id="<?php echo htmlspecialchars($notification['id']); ?>">
                                        View Details
                                    </button>
                                <?php endif; ?>
                                <?php if (!$notification['is_read']): ?>
                                    <button class="text-green-600 hover:text-green-900 mr-2 mark-as-read-btn" data-id="<?php echo htmlspecialchars($notification['id']); ?>">
                                        Mark Read
                                    </button>
                                <?php endif; ?>
                                <button class="text-red-600 hover:text-red-900 delete-notification-btn" data-id="<?php echo htmlspecialchars($notification['id']); ?>">
                                    Delete
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<script>
    // This script runs when notifications.php content is loaded into the main-content-area
    // It is wrapped in an IIFE to prevent variable re-declaration issues.
    (function() { // Start IIFE

        // Function to update the notification bell count in the header
        function updateNotificationBellCount() {
            fetch('/api/customer/notifications.php?action=get_unread_count', {
                method: 'POST', // Use POST for API calls generally
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams({ action: 'get_unread_count' }).toString()
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const bellSpan = window.parent.document.querySelector('#notification-bell span');
                    if (data.unread_count > 0) {
                        if (!bellSpan) { // Create span if it doesn't exist
                            const newSpan = window.parent.document.createElement('span');
                            newSpan.className = 'absolute top-0 right-0 inline-flex items-center justify-center px-2 py-1 text-xs font-bold leading-none text-red-100 bg-red-600 rounded-full';
                            window.parent.document.getElementById('notification-bell').appendChild(newSpan);
                            bellSpan = newSpan; // Update reference
                        }
                        bellSpan.textContent = data.unread_count;
                        bellSpan.style.display = 'inline-flex';
                    } else {
                        if (bellSpan) {
                            bellSpan.style.display = 'none';
                        }
                    }
                }
            })
            .catch(error => console.error('Error fetching unread count:', error));
        }

        // --- Event Delegation for Notification Actions ---
        const notificationsContainer = document.querySelector('.bg-white.p-6.rounded-lg.shadow-md.border.border-blue-200');

        if (notificationsContainer) {
            notificationsContainer.addEventListener('click', async function(event) {
                let targetButton = null;
                let action = '';
                let notificationId = null;

                // Determine which button was clicked via event delegation
                if (event.target.closest('.view-notification-details-btn')) {
                    targetButton = event.target.closest('.view-notification-details-btn');
                    action = 'view_details';
                    notificationId = targetButton.dataset.id;
                } else if (event.target.closest('.mark-as-read-btn')) {
                    targetButton = event.target.closest('.mark-as-read-btn');
                    action = 'mark_read';
                    notificationId = targetButton.dataset.id;
                } else if (event.target.closest('.delete-notification-btn')) {
                    targetButton = event.target.closest('.delete-notification-btn');
                    action = 'delete';
                    notificationId = targetButton.dataset.id;
                } else if (event.target.closest('.mark-all-read-btn')) {
                    targetButton = event.target.closest('.mark-all-read-btn');
                    action = 'mark_read';
                    notificationId = 'all'; // Special ID for all
                } else if (event.target.closest('.delete-all-notifications-btn')) {
                    targetButton = event.target.closest('.delete-all-notifications-btn');
                    action = 'delete';
                    notificationId = 'all'; // Special ID for all
                }

                if (!action) {
                    return; // No recognized action
                }

                if (action === 'view_details') {
                    // Mark as read immediately on click, then navigate
                    try {
                        const markReadResponse = await fetch('/api/customer/notifications.php', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                            body: new URLSearchParams({ action: 'mark_read', id: notificationId }).toString()
                        });
                        const markReadResult = await markReadResponse.json();
                        if (markReadResult.success) {
                            // Update bell count in parent frame
                            updateNotificationBellCount();
                            // Reload this section to update the specific notification's status visually
                            window.loadCustomerSection('notifications');
                        } else {
                            window.showToast(markReadResult.message, 'error');
                        }
                    } catch (error) {
                        console.error('Error marking notification as read during view:', error);
                        window.showToast('Failed to mark notification as read.', 'error');
                    }
                    // Navigate to the linked page
                    const link = targetButton.dataset.link;
                    if (link) {
                        // Assuming links are relative to customer/dashboard.php (e.g., invoices?invoice_id=...)
                        const parts = link.split('?');
                        const page = parts[0];
                        const params = {};
                        if (parts.length > 1) {
                            new URLSearchParams(parts[1]).forEach((value, key) => {
                                params[key] = value;
                            });
                        }
                        window.loadCustomerSection(page, params);
                    }
                    return; // Exit after handling view_details
                }

                // Handle Mark Read / Delete actions via confirmation modal
                let confirmMessage = '';
                let confirmTitle = '';
                let confirmBtnColor = 'bg-blue-600';
                if (action === 'mark_read') {
                    confirmTitle = (notificationId === 'all') ? 'Mark All Read' : 'Mark as Read';
                    confirmMessage = (notificationId === 'all') ? 'Are you sure you want to mark all notifications as read?' : 'Are you sure you want to mark this notification as read?';
                    confirmBtnColor = 'bg-green-600';
                } else if (action === 'delete') {
                    confirmTitle = (notificationId === 'all') ? 'Delete All Notifications' : 'Delete Notification';
                    confirmMessage = (notificationId === 'all') ? 'Are you sure you want to delete all notifications? This action cannot be undone.' : 'Are you sure you want to delete this notification? This action cannot be undone.';
                    confirmBtnColor = 'bg-red-600';
                }

                window.showConfirmationModal(
                    confirmTitle,
                    confirmMessage,
                    async (confirmed) => {
                        if (confirmed) {
                            window.showToast(`${confirmTitle} in progress...`, 'info');
                            const formData = new FormData();
                            formData.append('action', action);
                            formData.append('id', notificationId);

                            try {
                                const response = await fetch('/api/customer/notifications.php', {
                                    method: 'POST',
                                    body: formData
                                });
                                const result = await response.json();

                                if (result.success) {
                                    window.showToast(result.message, 'success');
                                    // Update notification bell count in header after successful action
                                    updateNotificationBellCount();
                                    // Reload notification list to reflect changes
                                    window.loadCustomerSection('notifications');
                                } else {
                                    window.showToast(result.message, 'error');
                                }
                            } catch (error) {
                                console.error(`Error ${action} notification:`, error);
                                window.showToast(`An error occurred during ${action} notification.`, 'error');
                            }
                        }
                    },
                    confirmTitle, // Use action title for confirm button text
                    confirmBtnColor
                );
            });
        }

        // Initial call to update bell count when page loads
        updateNotificationBellCount();

    })(); // End IIFE
</script>