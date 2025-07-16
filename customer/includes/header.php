<?php
// customer/includes/header.php
// This file assumes a session is already started and user is logged in.
// It uses variables set in the session to display user-specific info.

$userName = $_SESSION['user_first_name'] ?? 'Customer';
// $notificationCount = 0; // This will now be handled client-side by JS

// You might fetch notification count here from the database if you want it real-time
// global $conn;
// if (isset($_SESSION['user_id'])) {
//     $stmt = $conn->prepare("SELECT COUNT(*) AS unread_count FROM notifications WHERE user_id = ? AND is_read = FALSE");
//     $stmt->bind_param("i", $_SESSION['user_id']);
//     $stmt->execute();
//     $result = $stmt->get_result();
//     $row = $result->fetch_assoc();
//     $notificationCount = $row['unread_count'];
//     $stmt->close();
// }
?>

<header class="bg-white p-4 shadow-md flex justify-between items-center sticky top-0 z-10 rounded-b-lg">
    <div id="welcome-prompt" class="text-gray-700 font-semibold text-lg">Welcome back, <?php echo htmlspecialchars($userName); ?>!</div>
    <div class="flex items-center space-x-4">
        <button id="request-quote-btn" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors duration-200 shadow-md flex items-center" onclick="window.showAIChat('create-booking');">
            <i class="fas fa-file-invoice-dollar mr-2"></i>Request a Quote
        </button>

        <button id="notification-bell" class="relative text-gray-600 hover:text-gray-800 text-2xl" onclick="window.loadCustomerSection('notifications')">
            <i class="fas fa-bell"></i>
            <span class="absolute top-0 right-0 inline-flex items-center justify-center px-2 py-1 text-xs font-bold leading-none text-red-100 bg-red-600 rounded-full" style="display: none;"></span>
        </button>
        </div>
</header>

<script>
    // This function fetches the unread notification count and updates the bell icon
    window.updateNotificationBell = async function() {
        try {
            const response = await fetch('/api/customer/notifications.php?action=get_unread_count');
            const data = await response.json();
            const bellSpan = document.querySelector('#notification-bell span'); // Use document.querySelector as this script runs in the main window
            if (data.success && bellSpan) {
                if (data.unread_count > 0) {
                    bellSpan.textContent = data.unread_count;
                    bellSpan.style.display = 'inline-flex';
                } else {
                    bellSpan.style.display = 'none';
                }
            }
        } catch (error) {
            console.error('Error fetching unread notification count:', error);
            const bellSpan = document.querySelector('#notification-bell span');
            if (bellSpan) {
                bellSpan.style.display = 'none'; // Hide bell on error
            }
        }
    };

    // Initial call to update the bell when the header loads
    document.addEventListener('DOMContentLoaded', window.updateNotificationBell);
</script>
