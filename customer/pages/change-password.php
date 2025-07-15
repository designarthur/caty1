<?php
// customer/pages/change-password.php

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

// No direct data fetching needed for form pre-fill, but user ID is for update
$user_id = $_SESSION['user_id'];
?>

<h1 class="text-3xl font-bold text-gray-800 mb-8">Change Password</h1>

<div class="bg-white p-6 rounded-lg shadow-md border border-blue-200 max-w-xl mx-auto">
    <form id="change-password-form">
        <div class="mb-5">
            <label for="current-password" class="block text-sm font-medium text-gray-700 mb-2">Current Password</label>
            <input type="password" id="current-password" name="current_password" class="w-full p-3 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500" required>
        </div>
        <div class="mb-5">
            <label for="new-password" class="block text-sm font-medium text-gray-700 mb-2">New Password</label>
            <input type="password" id="new-password" name="new_password" class="w-full p-3 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500" required>
        </div>
        <div class="mb-5">
            <label for="confirm-password" class="block text-sm font-medium text-gray-700 mb-2">Confirm New Password</label>
            <input type="password" id="confirm-password" name="confirm_password" class="w-full p-3 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500" required>
        </div>
        <div class="text-right">
            <button type="submit" class="py-3 px-6 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors duration-200 shadow-lg font-semibold">
                <i class="fas fa-key mr-2"></i>Update Password
            </button>
        </div>
    </form>
</div>

<script>
    const changePasswordForm = document.getElementById('change-password-form');
    if (changePasswordForm) {
        changePasswordForm.addEventListener('submit', async function(event) {
            event.preventDefault(); // Prevent default form submission

            const currentPassword = document.getElementById('current-password').value;
            const newPassword = document.getElementById('new-password').value;
            const confirmPassword = document.getElementById('confirm-password').value;

            // Client-side validation
            if (!currentPassword || !newPassword || !confirmPassword) {
                showToast('All password fields are required.', 'error');
                return;
            }
            if (newPassword !== confirmPassword) {
                showToast('New password and confirmation do not match.', 'error');
                return;
            }
            if (newPassword.length < 8) {
                showToast('New password must be at least 8 characters long.', 'error');
                return;
            }
            if (newPassword === currentPassword) {
                showToast('New password cannot be the same as the current password.', 'warning');
                return;
            }

            showToast('Updating password...', 'info');

            const formData = new FormData();
            formData.append('current_password', currentPassword);
            formData.append('new_password', newPassword);

            try {
                // Pointing to the API endpoint for changing password
                const response = await fetch('/api/customer/change_password.php', {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();

                if (result.success) {
                    showToast(result.message || 'Password changed successfully!', 'success');
                    // Clear the form fields on success
                    changePasswordForm.reset();
                } else {
                    showToast(result.message || 'Failed to change password.', 'error');
                }
            } catch (error) {
                console.error('Password change API Error:', error);
                showToast('An error occurred during password change. Please try again.', 'error');
            }
        });
    }
</script>