<?php
// admin/pages/users.php

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

$current_admin_id = $_SESSION['user_id'];
$users = [];

// Fetch all users
$stmt = $conn->prepare("SELECT id, first_name, last_name, email, phone_number, role, created_at, address, city, state, zip_code FROM users ORDER BY created_at DESC");
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $users[] = $row;
}
$stmt->close();
$conn->close();
?>

<h1 class="text-3xl font-bold text-gray-800 mb-8">User Management</h1>

<div class="bg-white p-6 rounded-lg shadow-md border border-blue-200">
    <h2 class="text-xl font-semibold text-gray-700 mb-4 flex items-center"><i class="fas fa-users mr-2 text-blue-600"></i>All System Users</h2>

    <div class="flex justify-end mb-4">
        <button class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors duration-200 shadow-md" onclick="showModal('add-user-modal');">
            <i class="fas fa-user-plus mr-2"></i>Add New User
        </button>
    </div>

    <?php if (empty($users)): ?>
        <p class="text-gray-600 text-center p-4">No users found in the system.</p>
    <?php else: ?>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-blue-50">
                    <tr>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Name</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Email</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Phone</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Role</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Created At</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php foreach ($users as $user): ?>
                        <tr data-id="<?php echo htmlspecialchars($user['id']); ?>">
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900"><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo htmlspecialchars($user['email']); ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo htmlspecialchars($user['phone_number'] ?? ''); ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo htmlspecialchars(ucfirst($user['role'])); ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo (new DateTime($user['created_at']))->format('Y-m-d H:i'); ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <button class="text-indigo-600 hover:text-indigo-900 mr-2 edit-user-btn"
                                    data-id="<?php echo htmlspecialchars($user['id']); ?>"
                                    data-first-name="<?php echo htmlspecialchars($user['first_name']); ?>"
                                    data-last-name="<?php echo htmlspecialchars($user['last_name']); ?>"
                                    data-email="<?php echo htmlspecialchars($user['email']); ?>"
                                    data-phone="<?php echo htmlspecialchars($user['phone_number'] ?? ''); ?>"
                                    data-address="<?php echo htmlspecialchars($user['address'] ?? ''); ?>"
                                    data-city="<?php echo htmlspecialchars($user['city'] ?? ''); ?>"
                                    data-state="<?php echo htmlspecialchars($user['state'] ?? ''); ?>"
                                    data-zip-code="<?php echo htmlspecialchars($user['zip_code'] ?? ''); ?>"
                                    data-role="<?php echo htmlspecialchars($user['role']); ?>">
                                    Edit
                                </button>
                                <button class="text-blue-600 hover:text-blue-900 mr-2 reset-password-btn" data-id="<?php echo htmlspecialchars($user['id']); ?>" data-name="<?php echo htmlspecialchars($user['first_name']); ?>">
                                    Reset Pass
                                </button>
                                <?php if ((int)$user['id'] !== (int)$current_admin_id): // Prevent admin from deleting self ?>
                                    <button class="text-red-600 hover:text-red-900 delete-user-btn" data-id="<?php echo htmlspecialchars($user['id']); ?>" data-name="<?php echo htmlspecialchars($user['first_name']); ?>">
                                        Delete
                                    </button>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<div id="add-user-modal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center hidden z-50">
    <div class="bg-white p-6 rounded-lg shadow-xl w-11/12 max-w-lg text-gray-800">
        <h3 class="text-xl font-bold mb-4">Add New User</h3>
        <form id="add-user-form">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                <div>
                    <label for="add-first-name" class="block text-sm font-medium text-gray-700">First Name</label>
                    <input type="text" id="add-first-name" name="first_name" class="mt-1 p-2 border border-gray-300 rounded-md w-full" required>
                </div>
                <div>
                    <label for="add-last-name" class="block text-sm font-medium text-gray-700">Last Name</label>
                    <input type="text" id="add-last-name" name="last_name" class="mt-1 p-2 border border-gray-300 rounded-md w-full" required>
                </div>
            </div>
            <div class="mb-4">
                <label for="add-email" class="block text-sm font-medium text-gray-700">Email</label>
                <input type="email" id="add-email" name="email" class="mt-1 p-2 border border-gray-300 rounded-md w-full" required>
            </div>
            <div class="mb-4">
                <label for="add-phone-number" class="block text-sm font-medium text-gray-700">Phone Number</label>
                <input type="tel" id="add-phone-number" name="phone_number" class="mt-1 p-2 border border-gray-300 rounded-md w-full" placeholder="e.g., 123-456-7890" required>
            </div>
             <div class="mb-4">
                <label for="add-password" class="block text-sm font-medium text-gray-700">Password</label>
                <input type="password" id="add-password" name="password" class="mt-1 p-2 border border-gray-300 rounded-md w-full" required minlength="8">
                <p class="text-xs text-gray-500 mt-1">Min 8 characters. Will be emailed to user.</p>
            </div>
            <div class="mb-4">
                <label for="add-role" class="block text-sm font-medium text-gray-700">Role</label>
                <select id="add-role" name="role" class="mt-1 p-2 border border-gray-300 rounded-md w-full">
                    <option value="customer">Customer</option>
                    <option value="admin">Admin</option>
                    <option value="vendor">Vendor</option>
                </select>
            </div>
            <div class="mb-4">
                <label for="add-address" class="block text-sm font-medium text-gray-700">Address</label>
                <input type="text" id="add-address" name="address" class="mt-1 p-2 border border-gray-300 rounded-md w-full">
            </div>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                <div>
                    <label for="add-city" class="block text-sm font-medium text-gray-700">City</label>
                    <input type="text" id="add-city" name="city" class="mt-1 p-2 border border-gray-300 rounded-md w-full">
                </div>
                <div>
                    <label for="add-state" class="block text-sm font-medium text-gray-700">State</label>
                    <input type="text" id="add-state" name="state" class="mt-1 p-2 border border-gray-300 rounded-md w-full">
                </div>
                <div>
                    <label for="add-zip-code" class="block text-sm font-medium text-gray-700">Zip Code</label>
                    <input type="text" id="add-zip-code" name="zip_code" class="mt-1 p-2 border border-gray-300 rounded-md w-full">
                </div>
            </div>
            <div class="flex justify-end space-x-4">
                <button type="button" class="px-4 py-2 bg-gray-300 text-gray-800 rounded-lg hover:bg-gray-400" onclick="hideModal('add-user-modal')">Cancel</button>
                <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 font-semibold">Add User</button>
            </div>
        </form>
    </div>
</div>

<div id="edit-user-modal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center hidden z-50">
    <div class="bg-white p-6 rounded-lg shadow-xl w-11/12 max-w-lg text-gray-800">
        <h3 class="text-xl font-bold mb-4">Edit User</h3>
        <form id="edit-user-form">
            <input type="hidden" id="edit-user-id" name="user_id">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                <div>
                    <label for="edit-first-name" class="block text-sm font-medium text-gray-700">First Name</label>
                    <input type="text" id="edit-first-name" name="first_name" class="mt-1 p-2 border border-gray-300 rounded-md w-full" required>
                </div>
                <div>
                    <label for="edit-last-name" class="block text-sm font-medium text-gray-700">Last Name</label>
                    <input type="text" id="edit-last-name" name="last_name" class="mt-1 p-2 border border-gray-300 rounded-md w-full" required>
                </div>
            </div>
            <div class="mb-4">
                <label for="edit-email" class="block text-sm font-medium text-gray-700">Email</label>
                <input type="email" id="edit-email" name="email" class="mt-1 p-2 border border-gray-300 rounded-md w-full" required>
            </div>
            <div class="mb-4">
                <label for="edit-phone-number" class="block text-sm font-medium text-gray-700">Phone Number</label>
                <input type="tel" id="edit-phone-number" name="phone_number" class="mt-1 p-2 border border-gray-300 rounded-md w-full" placeholder="e.g., 123-456-7890" required>
            </div>
            <div class="mb-4">
                <label for="edit-role" class="block text-sm font-medium text-gray-700">Role</label>
                <select id="edit-role" name="role" class="mt-1 p-2 border border-gray-300 rounded-md w-full" <?php echo 'data-current-admin-id="' . $current_admin_id . '"'; ?>>
                    <option value="customer">Customer</option>
                    <option value="admin">Admin</option>
                    <option value="vendor">Vendor</option>
                </select>
            </div>
            <div class="mb-4">
                <label for="edit-address" class="block text-sm font-medium text-gray-700">Address</label>
                <input type="text" id="edit-address" name="address" class="mt-1 p-2 border border-gray-300 rounded-md w-full">
            </div>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                <div>
                    <label for="edit-city" class="block text-sm font-medium text-gray-700">City</label>
                    <input type="text" id="edit-city" name="city" class="mt-1 p-2 border border-gray-300 rounded-md w-full">
                </div>
                <div>
                    <label for="edit-state" class="block text-sm font-medium text-gray-700">State</label>
                    <input type="text" id="edit-state" name="state" class="mt-1 p-2 border border-gray-300 rounded-md w-full">
                </div>
                <div>
                    <label for="edit-zip-code" class="block text-sm font-medium text-gray-700">Zip Code</label>
                    <input type="text" id="edit-zip-code" name="zip_code" class="mt-1 p-2 border border-gray-300 rounded-md w-full">
                </div>
            </div>
            <div class="flex justify-end space-x-4">
                <button type="button" class="px-4 py-2 bg-gray-300 text-gray-800 rounded-lg hover:bg-gray-400" onclick="hideModal('edit-user-modal')">Cancel</button>
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

    // --- Add User Form Submission ---
    const addUserForm = document.getElementById('add-user-form');
    if (addUserForm) {
        addUserForm.addEventListener('submit', async function(event) {
            event.preventDefault();

            const formData = new FormData(this);
            formData.append('action', 'add_user'); // Add action for the API

            const email = document.getElementById('add-email').value;
            const phone = document.getElementById('add-phone-number').value;
            const password = document.getElementById('add-password').value;

            if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
                showToast('Please enter a valid email address.', 'error');
                return;
            }
            if (!isValidPhoneNumber(phone)) {
                showToast('Please enter a valid phone number (e.g., 123-456-7890).', 'error');
                return;
            }
            if (password.length < 8) {
                showToast('Password must be at least 8 characters long.', 'error');
                return;
            }

            showToast('Adding new user...', 'info');

            try {
                const response = await fetch('/api/admin/users.php', {
                    method: 'POST',
                    body: formData
                });
                const result = await response.json();

                if (result.success) {
                    showToast(result.message, 'success');
                    addUserForm.reset();
                    hideModal('add-user-modal');
                    window.loadAdminSection('users'); // Reload user list
                } else {
                    showToast(result.message, 'error');
                }
            } catch (error) {
                console.error('Add user API Error:', error);
                showToast('An error occurred. Please try again.', 'error');
            }
        });
    }

    // --- Edit User Form Submission ---
    const editUserForm = document.getElementById('edit-user-form');
    if (editUserForm) {
        editUserForm.addEventListener('submit', async function(event) {
            event.preventDefault();

            const formData = new FormData(this);
            formData.append('action', 'update_user'); // Add action for the API

            const email = document.getElementById('edit-email').value;
            const phone = document.getElementById('edit-phone-number').value;
            const userId = document.getElementById('edit-user-id').value;
            const selectedRole = document.getElementById('edit-role').value;
            const currentAdminId = document.getElementById('edit-role').dataset.currentAdminId;


            if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
                showToast('Please enter a valid email address.', 'error');
                return;
            }
            if (!isValidPhoneNumber(phone)) {
                showToast('Please enter a valid phone number (e.g., 123-456-7890).', 'error');
                return;
            }

            if (userId === currentAdminId && selectedRole !== 'admin') {
                showToast('You cannot change your own role to non-admin.', 'error');
                return;
            }

            showToast('Saving user changes...', 'info');

            try {
                const response = await fetch('/api/admin/users.php', {
                    method: 'POST',
                    body: formData
                });
                const result = await response.json();

                if (result.success) {
                    showToast(result.message, 'success');
                    hideModal('edit-user-modal');
                    window.loadAdminSection('users'); // Reload user list
                } else {
                    showToast(result.message, 'error');
                }
            } catch (error) {
                console.error('Edit user API Error:', error);
                showToast('An error occurred. Please try again.', 'error');
            }
        });
    }

    // --- Event Listeners for Table Buttons (Edit, Reset Pass, Delete) ---
    document.addEventListener('click', function(event) {
        // Edit User button
        if (event.target.classList.contains('edit-user-btn')) {
            const button = event.target;
            const userId = button.dataset.id;
            const firstName = button.dataset.firstName;
            const lastName = button.dataset.lastName;
            const email = button.dataset.email;
            const phone = button.dataset.phone;
            const address = button.dataset.address;
            const city = button.dataset.city;
            const state = button.dataset.state;
            const zipCode = button.dataset.zipCode;
            const role = button.dataset.role;

            document.getElementById('edit-user-id').value = userId;
            document.getElementById('edit-first-name').value = firstName;
            document.getElementById('edit-last-name').value = lastName;
            document.getElementById('edit-email').value = email;
            document.getElementById('edit-phone-number').value = phone;
            document.getElementById('edit-address').value = address;
            document.getElementById('edit-city').value = city;
            document.getElementById('edit-state').value = state;
            document.getElementById('edit-zip-code').value = zipCode;
            document.getElementById('edit-role').value = role;

            // Disable role change for current admin if it's their own account
            const editRoleSelect = document.getElementById('edit-role');
            const currentAdminId = editRoleSelect.dataset.currentAdminId;
            if (userId === currentAdminId) {
                editRoleSelect.disabled = true; // Prevents changing own role
            } else {
                editRoleSelect.disabled = false;
            }


            showModal('edit-user-modal');
        }

        // Reset Password button
        if (event.target.classList.contains('reset-password-btn')) {
            const userId = event.target.dataset.id;
            const userName = event.target.dataset.name;

            showConfirmationModal(
                'Reset Password',
                `Are you sure you want to reset the password for ${userName}? A new temporary password will be emailed to them.`,
                async (confirmed) => {
                    if (confirmed) {
                        showToast(`Resetting password for ${userName}...`, 'info');
                        const formData = new FormData();
                        formData.append('action', 'reset_password');
                        formData.append('user_id', userId);

                        try {
                            const response = await fetch('/api/admin/users.php', {
                                method: 'POST',
                                body: formData
                            });
                            const result = await response.json();

                            if (result.success) {
                                showToast(result.message, 'success');
                            } else {
                                showToast(result.message, 'error');
                            }
                        } catch (error) {
                            console.error('Reset password API Error:', error);
                            showToast('An error occurred. Please try again.', 'error');
                        }
                    }
                },
                'Reset Password',
                'bg-blue-600'
            );
        }

        // Delete User button
        if (event.target.classList.contains('delete-user-btn')) {
            const userId = event.target.dataset.id;
            const userName = event.target.dataset.name;
            const currentAdminId = <?php echo json_encode($current_admin_id); ?>;

            if (parseInt(userId) === parseInt(currentAdminId)) {
                showToast('You cannot delete your own admin account.', 'error');
                return;
            }

            showConfirmationModal(
                'Delete User',
                `Are you sure you want to delete user ${userName}? This action cannot be undone and will remove all associated data (quotes, bookings, etc.).`,
                async (confirmed) => {
                    if (confirmed) {
                        showToast(`Deleting user ${userName}...`, 'info');
                        const formData = new FormData();
                        formData.append('action', 'delete_user');
                        formData.append('user_id', userId);

                        try {
                            const response = await fetch('/api/admin/users.php', {
                                method: 'POST',
                                body: formData
                            });
                            const result = await response.json();

                            if (result.success) {
                                showToast(result.message, 'success');
                                window.loadAdminSection('users'); // Reload user list
                            } else {
                                showToast(result.message, 'error');
                            }
                        } catch (error) {
                            console.error('Delete user API Error:', error);
                            showToast('An error occurred. Please try again.', 'error');
                        }
                    }
                },
                'Delete User',
                'bg-red-600'
            );
        }
    });
</script>