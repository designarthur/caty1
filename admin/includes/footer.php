<?php
// admin/includes/footer.php
// This file holds modals and all shared JavaScript for the admin dashboard.
// It will dynamically load page content from admin/pages/ via AJAX.
?>

    <div id="admin-logout-modal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center hidden z-50">
        <div class="bg-white p-6 rounded-lg shadow-xl w-96 text-gray-800">
            <h3 class="text-xl font-bold mb-4">Confirm Logout</h3>
            <p class="mb-6">Are you sure you want to log out from the Admin Panel?</p>
            <div class="flex justify-end space-x-4">
                <button class="px-4 py-2 bg-gray-300 text-gray-800 rounded-lg hover:bg-gray-400" onclick="hideModal('admin-logout-modal')">Cancel</button>
                <a href="/admin/logout.php" class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700">Logout</a>
            </div>
        </div>
    </div>

    <div id="admin-confirmation-modal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center hidden z-50">
        <div class="bg-white p-6 rounded-lg shadow-xl w-96 text-gray-800">
            <h3 class="text-xl font-bold mb-4" id="admin-confirmation-modal-title">Confirm Action</h3>
            <p class="mb-6" id="admin-confirmation-modal-message">Are you sure you want to proceed with this action?</p>
            <div class="flex justify-end space-x-4">
                <button class="px-4 py-2 bg-gray-300 text-gray-800 rounded-lg hover:bg-gray-400" id="admin-confirmation-modal-cancel">Cancel</button>
                <button class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700" id="admin-confirmation-modal-confirm">Confirm</button>
            </div>
        </div>
    </div>

    <div id="toast-container"></div>

    <script>
        // Global functions showToast, showModal, hideModal, and showConfirmationModal are already
        // defined in admin/index.php and made globally accessible via `window.functionName`.
        // So, no need to redefine them here. This file now primarily serves as a container
        // for modals that are loaded on every admin page.

        // The JavaScript that interacts with these modals (e.g., event listeners for confirm/cancel
        // buttons within the modals) is already in admin/index.php.

        // Any modals specific to a particular page (e.g., 'Add User' modal for users.php)
        // should be placed directly within that page's HTML, not here.
    </script>