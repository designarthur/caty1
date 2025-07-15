<?php
// admin/pages/vendors.php

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

$vendors = [];

// Fetch all vendors
$stmt = $conn->prepare("SELECT id, name, contact_person, email, phone_number, address, city, state, zip_code, is_active FROM vendors ORDER BY name ASC");
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $vendors[] = $row;
}
$stmt->close();
$conn->close();

// Helper function for status display
function getVendorStatusBadge($is_active) {
    if ($is_active) {
        return '<span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">Active</span>';
    } else {
        return '<span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">Inactive</span>';
    }
}
?>

<h1 class="text-3xl font-bold text-gray-800 mb-8">Vendor Management</h1>

<div class="bg-white p-6 rounded-lg shadow-md border border-blue-200">
    <h2 class="text-xl font-semibold text-gray-700 mb-4 flex items-center"><i class="fas fa-industry mr-2 text-blue-600"></i>All Registered Vendors</h2>

    <div class="flex justify-end mb-4">
        <button class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors duration-200 shadow-md" onclick="showModal('add-vendor-modal');">
            <i class="fas fa-plus-circle mr-2"></i>Add New Vendor
        </button>
    </div>

    <?php if (empty($vendors)): ?>
        <p class="text-gray-600 text-center p-4">No vendors found in the system.</p>
    <?php else: ?>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-blue-50">
                    <tr>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Name</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Contact Person</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Email</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Phone</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Status</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php foreach ($vendors as $vendor): ?>
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900"><?php echo htmlspecialchars($vendor['name']); ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo htmlspecialchars($vendor['contact_person'] ?? 'N/A'); ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo htmlspecialchars($vendor['email']); ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo htmlspecialchars($vendor['phone_number'] ?? 'N/A'); ?></td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <?php echo getVendorStatusBadge($vendor['is_active']); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <button class="text-indigo-600 hover:text-indigo-900 mr-2 edit-vendor-btn"
                                    data-id="<?php echo htmlspecialchars($vendor['id']); ?>"
                                    data-name="<?php echo htmlspecialchars($vendor['name']); ?>"
                                    data-contact-person="<?php echo htmlspecialchars($vendor['contact_person'] ?? ''); ?>"
                                    data-email="<?php echo htmlspecialchars($vendor['email']); ?>"
                                    data-phone-number="<?php echo htmlspecialchars($vendor['phone_number'] ?? ''); ?>"
                                    data-address="<?php echo htmlspecialchars($vendor['address'] ?? ''); ?>"
                                    data-city="<?php echo htmlspecialchars($vendor['city'] ?? ''); ?>"
                                    data-state="<?php echo htmlspecialchars($vendor['state'] ?? ''); ?>"
                                    data-zip-code="<?php echo htmlspecialchars($vendor['zip_code'] ?? ''); ?>"
                                    data-is-active="<?php echo htmlspecialchars($vendor['is_active'] ? '1' : '0'); ?>">
                                    Edit
                                </button>
                                <button class="text-red-600 hover:text-red-900 delete-vendor-btn" data-id="<?php echo htmlspecialchars($vendor['id']); ?>" data-name="<?php echo htmlspecialchars($vendor['name']); ?>">
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

<div id="add-vendor-modal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center hidden z-50">
    <div class="bg-white p-6 rounded-lg shadow-xl w-11/12 max-w-lg text-gray-800">
        <h3 class="text-xl font-bold mb-4">Add New Vendor</h3>
        <form id="add-vendor-form">
            <div class="mb-4">
                <label for="add-vendor-name" class="block text-sm font-medium text-gray-700">Vendor Name</label>
                <input type="text" id="add-vendor-name" name="name" class="mt-1 p-2 border border-gray-300 rounded-md w-full" required>
            </div>
            <div class="mb-4">
                <label for="add-contact-person" class="block text-sm font-medium text-gray-700">Contact Person</label>
                <input type="text" id="add-contact-person" name="contact_person" class="mt-1 p-2 border border-gray-300 rounded-md w-full">
            </div>
            <div class="mb-4">
                <label for="add-vendor-email" class="block text-sm font-medium text-gray-700">Email</label>
                <input type="email" id="add-vendor-email" name="email" class="mt-1 p-2 border border-gray-300 rounded-md w-full" required>
            </div>
            <div class="mb-4">
                <label for="add-vendor-phone" class="block text-sm font-medium text-gray-700">Phone Number</label>
                <input type="tel" id="add-vendor-phone" name="phone_number" class="mt-1 p-2 border border-gray-300 rounded-md w-full" placeholder="e.g., 123-456-7890">
            </div>
            <div class="mb-4">
                <label for="add-vendor-address" class="block text-sm font-medium text-gray-700">Address</label>
                <input type="text" id="add-vendor-address" name="address" class="mt-1 p-2 border border-gray-300 rounded-md w-full">
            </div>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                <div>
                    <label for="add-vendor-city" class="block text-sm font-medium text-gray-700">City</label>
                    <input type="text" id="add-vendor-city" name="city" class="mt-1 p-2 border border-gray-300 rounded-md w-full">
                </div>
                <div>
                    <label for="add-vendor-state" class="block text-sm font-medium text-gray-700">State</label>
                    <input type="text" id="add-vendor-state" name="state" class="mt-1 p-2 border border-gray-300 rounded-md w-full">
                </div>
                <div>
                    <label for="add-vendor-zip-code" class="block text-sm font-medium text-gray-700">Zip Code</label>
                    <input type="text" id="add-vendor-zip-code" name="zip_code" class="mt-1 p-2 border border-gray-300 rounded-md w-full">
                </div>
            </div>
            <div class="flex items-center mb-6">
                <input type="checkbox" id="add-vendor-is-active" name="is_active" class="h-4 w-4 text-blue-600 border-gray-300 rounded" checked>
                <label for="add-vendor-is-active" class="ml-2 block text-sm text-gray-900">Is Active</label>
            </div>
            <div class="flex justify-end space-x-4">
                <button type="button" class="px-4 py-2 bg-gray-300 text-gray-800 rounded-lg hover:bg-gray-400" onclick="hideModal('add-vendor-modal')">Cancel</button>
                <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 font-semibold">Add Vendor</button>
            </div>
        </form>
    </div>
</div>

<div id="edit-vendor-modal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center hidden z-50">
    <div class="bg-white p-6 rounded-lg shadow-xl w-11/12 max-w-lg text-gray-800">
        <h3 class="text-xl font-bold mb-4">Edit Vendor</h3>
        <form id="edit-vendor-form">
            <input type="hidden" id="edit-vendor-id" name="vendor_id">
            <div class="mb-4">
                <label for="edit-vendor-name" class="block text-sm font-medium text-gray-700">Vendor Name</label>
                <input type="text" id="edit-vendor-name" name="name" class="mt-1 p-2 border border-gray-300 rounded-md w-full" required>
            </div>
            <div class="mb-4">
                <label for="edit-contact-person" class="block text-sm font-medium text-gray-700">Contact Person</label>
                <input type="text" id="edit-contact-person" name="contact_person" class="mt-1 p-2 border border-gray-300 rounded-md w-full">
            </div>
            <div class="mb-4">
                <label for="edit-vendor-email" class="block text-sm font-medium text-gray-700">Email</label>
                <input type="email" id="edit-vendor-email" name="email" class="mt-1 p-2 border border-gray-300 rounded-md w-full" required>
            </div>
            <div class="mb-4">
                <label for="edit-vendor-phone" class="block text-sm font-medium text-gray-700">Phone Number</label>
                <input type="tel" id="edit-vendor-phone" name="phone_number" class="mt-1 p-2 border border-gray-300 rounded-md w-full" placeholder="e.g., 123-456-7890">
            </div>
            <div class="mb-4">
                <label for="edit-vendor-address" class="block text-sm font-medium text-gray-700">Address</label>
                <input type="text" id="edit-vendor-address" name="address" class="mt-1 p-2 border border-gray-300 rounded-md w-full">
            </div>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                <div>
                    <label for="edit-vendor-city" class="block text-sm font-medium text-gray-700">City</label>
                    <input type="text" id="edit-vendor-city" name="city" class="mt-1 p-2 border border-gray-300 rounded-md w-full">
                </div>
                <div>
                    <label for="edit-vendor-state" class="block text-sm font-medium text-gray-700">State</label>
                    <input type="text" id="edit-vendor-state" name="state" class="mt-1 p-2 border border-gray-300 rounded-md w-full">
                </div>
                <div>
                    <label for="edit-vendor-zip-code" class="block text-sm font-medium text-gray-700">Zip Code</label>
                    <input type="text" id="edit-vendor-zip-code" name="zip_code" class="mt-1 p-2 border border-gray-300 rounded-md w-full">
                </div>
            </div>
            <div class="flex items-center mb-6">
                <input type="checkbox" id="edit-vendor-is-active" name="is_active" class="h-4 w-4 text-blue-600 border-gray-300 rounded">
                <label for="edit-vendor-is-active" class="ml-2 block text-sm text-gray-900">Is Active</label>
            </div>
            <div class="flex justify-end space-x-4">
                <button type="button" class="px-4 py-2 bg-gray-300 text-gray-800 rounded-lg hover:bg-gray-400" onclick="hideModal('edit-vendor-modal')">Cancel</button>
                <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 font-semibold">Save Changes</button>
            </div>
        </form>
    </div>
</div>

<script>
    // Client-side phone number validation
    function isValidPhoneNumber(phone) {
        const re = /^\(?(\d{3})\)?[-\s]?(\d{3})[-\s]?(\d{4})$/;
        return re.test(phone);
    }

    // --- Add Vendor Form Submission ---
    const addVendorForm = document.getElementById('add-vendor-form');
    if (addVendorForm) {
        addVendorForm.addEventListener('submit', async function(event) {
            event.preventDefault();

            const formData = new FormData(this);
            formData.append('action', 'add_vendor');

            const email = document.getElementById('add-vendor-email').value.trim();
            const phone = document.getElementById('add-vendor-phone').value.trim();

            if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
                showToast('Please enter a valid email address.', 'error');
                return;
            }
            if (phone && !isValidPhoneNumber(phone)) { // Phone is optional, validate if provided
                showToast('Please enter a valid phone number (e.g., 123-456-7890).', 'error');
                return;
            }

            showToast('Adding new vendor...', 'info');

            try {
                const response = await fetch('/api/admin/vendors.php', {
                    method: 'POST',
                    body: formData
                });
                const result = await response.json();

                if (result.success) {
                    showToast(result.message, 'success');
                    addVendorForm.reset();
                    hideModal('add-vendor-modal');
                    window.loadAdminSection('vendors'); // Reload vendor list
                } else {
                    showToast(result.message, 'error');
                }
            } catch (error) {
                console.error('Add vendor API Error:', error);
                showToast('An error occurred. Please try again.', 'error');
            }
        });
    }

    // --- Edit Vendor Form Submission ---
    const editVendorForm = document.getElementById('edit-vendor-form');
    if (editVendorForm) {
        editVendorForm.addEventListener('submit', async function(event) {
            event.preventDefault();

            const formData = new FormData(this);
            formData.append('action', 'update_vendor');

            const email = document.getElementById('edit-vendor-email').value.trim();
            const phone = document.getElementById('edit-vendor-phone').value.trim();

            if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
                showToast('Please enter a valid email address.', 'error');
                return;
            }
            if (phone && !isValidPhoneNumber(phone)) { // Phone is optional, validate if provided
                showToast('Please enter a valid phone number (e.g., 123-456-7890).', 'error');
                return;
            }

            showToast('Saving vendor changes...', 'info');

            try {
                const response = await fetch('/api/admin/vendors.php', {
                    method: 'POST',
                    body: formData
                });
                const result = await response.json();

                if (result.success) {
                    showToast(result.message, 'success');
                    hideModal('edit-vendor-modal');
                    window.loadAdminSection('vendors'); // Reload vendor list
                } else {
                    showToast(result.message, 'error');
                }
            } catch (error) {
                console.error('Edit vendor API Error:', error);
                showToast('An error occurred. Please try again.', 'error');
            }
        });
    }

    // --- Event Listeners for Table Buttons (Edit, Delete) ---
    document.addEventListener('click', function(event) {
        // Edit Vendor button
        if (event.target.classList.contains('edit-vendor-btn')) {
            const button = event.target;
            document.getElementById('edit-vendor-id').value = button.dataset.id;
            document.getElementById('edit-vendor-name').value = button.dataset.name;
            document.getElementById('edit-contact-person').value = button.dataset.contactPerson;
            document.getElementById('edit-vendor-email').value = button.dataset.email;
            document.getElementById('edit-vendor-phone').value = button.dataset.phoneNumber;
            document.getElementById('edit-vendor-address').value = button.dataset.address;
            document.getElementById('edit-vendor-city').value = button.dataset.city;
            document.getElementById('edit-vendor-state').value = button.dataset.state;
            document.getElementById('edit-vendor-zip-code').value = button.dataset.zipCode;
            document.getElementById('edit-vendor-is-active').checked = (button.dataset.isActive === '1');

            showModal('edit-vendor-modal');
        }

        // Delete Vendor button
        if (event.target.classList.contains('delete-vendor-btn')) {
            const vendorId = event.target.dataset.id;
            const vendorName = event.target.dataset.name;

            showConfirmationModal(
                'Delete Vendor',
                `Are you sure you want to delete "${vendorName}"? This action cannot be undone and will disassociate this vendor from any bookings.`,
                async (confirmed) => {
                    if (confirmed) {
                        showToast(`Deleting ${vendorName}...`, 'info');
                        const formData = new FormData();
                        formData.append('action', 'delete_vendor');
                        formData.append('vendor_id', vendorId);

                        try {
                            const response = await fetch('/api/admin/vendors.php', {
                                method: 'POST',
                                body: formData
                            });
                            const result = await response.json();

                            if (result.success) {
                                showToast(result.message, 'success');
                                window.loadAdminSection('vendors'); // Reload vendor list
                            } else {
                                showToast(result.message, 'error');
                            }
                        } catch (error) {
                            console.error('Delete vendor API Error:', error);
                            showToast('An error occurred. Please try again.', 'error');
                        }
                    }
                },
                'Delete Vendor',
                'bg-red-600'
            );
        }
    });
</script>