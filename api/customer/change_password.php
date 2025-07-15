<?php
// customer/pages/change-password.php

// --- Setup & Includes ---
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../includes/functions.php'; // Needed for CSRF functions

// --- Authorization ---
if (!is_logged_in()) {
    echo '<div class="text-red-500 text-center p-8">You must be logged in to view this content.</div>';
    exit;
}

// **THE FIX - Step 1**: Generate the CSRF token before the form is displayed.
generate_csrf_token();
?>

<h1 class="text-3xl font-bold text-gray-800 mb-8">Change Password</h1>

<div class="bg-white p-6 rounded-lg shadow-md border border-blue-200 max-w-xl mx-auto">
    <form id="change-password-form">
        <!-- **THE FIX - Step 2**: Add the hidden input field to include the token in the form submission. -->
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">

        <div class="mb-5">
            <label for="current-password" class="block text-sm font-medium text-gray-700 mb-2">Current Password</label>
            <input type="password" id="current-password" name="current_password" class="w-full p-3 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500" required autocomplete="current-password">
        </div>
        <div class="mb-5">
            <label for="new-password" class="block text-sm font-medium text-gray-700 mb-2">New Password</label>
            <input type="password" id="new-password" name="new_password" class="w-full p-3 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500" required autocomplete="new-password">
        </div>
        <div class="mb-5">
            <label for="confirm-password" class="block text-sm font-medium text-gray-700 mb-2">Confirm New Password</label>
            <input type="password" id="confirm-password" name="confirm_password" class="w-full p-3 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500" required autocomplete="new-password">
        </div>
        <div class="text-right">
            <button type="submit" class="py-3 px-6 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors duration-200 shadow-lg font-semibold">
                <i class="fas fa-key mr-2"></i>Update Password
            </button>
        </div>
    </form>
</div>

<script>
    // The JavaScript for this page remains the same as before.
    // It will now automatically pick up and send the new csrf_token field when the form is submitted.
    const changePasswordForm = document.getElementById('change-password-form');
    if (changePasswordForm) {
        changePasswordForm.addEventListener('submit', async function(event) {
            event.preventDefault();
            
            // Client-side validation...
            const newPassword = document.getElementById('new-password').value;
            const confirmPassword = document.getElementById('confirm-password').value;
            if (newPassword !== confirmPassword) {
                showToast('New password and confirmation do not match.', 'error');
                return;
            }
            
            showToast('Updating password...', 'info');
            const formData = new FormData(this);

            try {
                const response = await fetch('/api/customer/change_password.php', {
                    method: 'POST',
                    body: formData
                });
                const result = await response.json();
                if (result.success) {
                    showToast(result.message, 'success');
                    changePasswordForm.reset();
                } else {
                    showToast(result.message, 'error');
                }
            } catch (error) {
                showToast('An error occurred. Please try again.', 'error');
            }
        });
    }
</script>