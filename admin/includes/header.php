<?php
// admin/includes/header.php
// This file assumes a session is already started and user is logged in as admin.
// It uses variables set in the session to display user-specific info.

$userName = $_SESSION['user_first_name'] ?? 'Admin';
// $notificationCount is now dynamically fetched via JavaScript
?>

<header class="bg-white p-4 shadow-md flex justify-between items-center sticky top-0 z-10 rounded-b-lg">
    <div class="text-gray-700 font-semibold text-lg">Welcome, <?php echo htmlspecialchars($userName); ?>!</div>
    <div class="flex items-center space-x-4">
        <button id="admin-notification-bell" class="relative text-gray-600 hover:text-gray-800 text-2xl" onclick="loadAdminSection('notifications');">
            <i class="fas fa-bell"></i>
            <span id="admin-notification-count" class="absolute top-0 right-0 inline-flex items-center justify-center px-2 py-1 text-xs font-bold leading-none text-red-100 bg-red-600 rounded-full" style="display: none;">0</span>
        </button>
        <button class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors duration-200 shadow-md" onclick="showModal('admin-logout-modal');">
            <i class="fas fa-sign-out-alt mr-2"></i>Logout
        </button>
    </div>
</header>

<script>
    // This function fetches the unread notification count and updates the bell icon
    window.updateAdminNotificationBell = async function() {
        try {
            const response = await fetch('/api/admin/notifications.php?action=get_unread_count');
            const data = await response.json();
            const bellCountSpan = document.getElementById('admin-notification-count');
            if (data.success && bellCountSpan) {
                if (data.unread_count > 0) {
                    bellCountSpan.textContent = data.unread_count;
                    bellCountSpan.style.display = 'inline-flex';
                } else {
                    bellCountSpan.style.display = 'none';
                }
            }
        } catch (error) {
            console.error('Error fetching unread admin notification count:', error);
            const bellCountSpan = document.getElementById('admin-notification-count');
            if (bellCountSpan) {
                bellCountSpan.style.display = 'none'; // Hide count on error
            }
        }
    };

    // Call update function on initial load
    document.addEventListener('DOMContentLoaded', window.updateAdminNotificationBell);
</script>
