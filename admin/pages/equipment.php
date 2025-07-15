<?php
// admin/pages/equipment.php

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

$equipment_items = [];

// Fetch all equipment items
$stmt = $conn->prepare("SELECT id, name, type, size_capacity, description, daily_rate, image_url, is_active FROM equipment ORDER BY name ASC");
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $equipment_items[] = $row;
}
$stmt->close();
$conn->close();

// Helper function for status display
function getActiveStatusBadge($is_active) {
    if ($is_active) {
        return '<span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">Active</span>';
    } else {
        return '<span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">Inactive</span>';
    }
}
?>

<h1 class="text-3xl font-bold text-gray-800 mb-8">Equipment Management</h1>

<div class="bg-white p-6 rounded-lg shadow-md border border-blue-200">
    <h2 class="text-xl font-semibold text-gray-700 mb-4 flex items-center"><i class="fas fa-dumpster mr-2 text-blue-600"></i>All Equipment Items</h2>

    <div class="flex justify-end mb-4">
        <button class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors duration-200 shadow-md" onclick="showModal('add-equipment-modal');">
            <i class="fas fa-plus-circle mr-2"></i>Add New Equipment
        </button>
    </div>

    <?php if (empty($equipment_items)): ?>
        <p class="text-gray-600 text-center p-4">No equipment found in the system.</p>
    <?php else: ?>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-blue-50">
                    <tr>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Name</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Type</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Size/Capacity</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Daily Rate</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Status</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php foreach ($equipment_items as $item): ?>
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900"><?php echo htmlspecialchars($item['name']); ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo htmlspecialchars($item['type']); ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo htmlspecialchars($item['size_capacity'] ?? 'N/A'); ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">$<?php echo number_format($item['daily_rate'], 2); ?></td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <?php echo getActiveStatusBadge($item['is_active']); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <button class="text-indigo-600 hover:text-indigo-900 mr-2 edit-equipment-btn"
                                    data-id="<?php echo htmlspecialchars($item['id']); ?>"
                                    data-name="<?php echo htmlspecialchars($item['name']); ?>"
                                    data-type="<?php echo htmlspecialchars($item['type']); ?>"
                                    data-size-capacity="<?php echo htmlspecialchars($item['size_capacity'] ?? ''); ?>"
                                    data-description="<?php echo htmlspecialchars($item['description'] ?? ''); ?>"
                                    data-daily-rate="<?php echo htmlspecialchars($item['daily_rate']); ?>"
                                    data-image-url="<?php echo htmlspecialchars($item['image_url'] ?? ''); ?>"
                                    data-is-active="<?php echo htmlspecialchars($item['is_active'] ? '1' : '0'); ?>">
                                    Edit
                                </button>
                                <button class="text-red-600 hover:text-red-900 delete-equipment-btn" data-id="<?php echo htmlspecialchars($item['id']); ?>" data-name="<?php echo htmlspecialchars($item['name']); ?>">
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

<div id="add-equipment-modal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center hidden z-50">
    <div class="bg-white p-6 rounded-lg shadow-xl w-11/12 max-w-lg text-gray-800">
        <h3 class="text-xl font-bold mb-4">Add New Equipment</h3>
        <form id="add-equipment-form">
            <div class="mb-4">
                <label for="add-name" class="block text-sm font-medium text-gray-700">Equipment Name</label>
                <input type="text" id="add-name" name="name" class="mt-1 p-2 border border-gray-300 rounded-md w-full" required>
            </div>
            <div class="mb-4">
                <label for="add-type" class="block text-sm font-medium text-gray-700">Type</label>
                <select id="add-type" name="type" class="mt-1 p-2 border border-gray-300 rounded-md w-full" required>
                    <option value="">Select Type</option>
                    <option value="Dumpster">Dumpster</option>
                    <option value="Temporary Toilet">Temporary Toilet</option>
                    <option value="Storage Container">Storage Container</option>
                    <option value="Handwash Station">Handwash Station</option>
                </select>
            </div>
            <div class="mb-4">
                <label for="add-size-capacity" class="block text-sm font-medium text-gray-700">Size/Capacity (e.g., 10-yard, 40 ft)</label>
                <input type="text" id="add-size-capacity" name="size_capacity" class="mt-1 p-2 border border-gray-300 rounded-md w-full">
            </div>
            <div class="mb-4">
                <label for="add-description" class="block text-sm font-medium text-gray-700">Description</label>
                <textarea id="add-description" name="description" rows="3" class="mt-1 p-2 border border-gray-300 rounded-md w-full"></textarea>
            </div>
            <div class="mb-4">
                <label for="add-daily-rate" class="block text-sm font-medium text-gray-700">Daily Rate ($)</label>
                <input type="number" id="add-daily-rate" name="daily_rate" step="0.01" min="0" class="mt-1 p-2 border border-gray-300 rounded-md w-full" required>
            </div>
            <div class="mb-4">
                <label for="add-image-url" class="block text-sm font-medium text-gray-700">Image URL</label>
                <input type="url" id="add-image-url" name="image_url" class="mt-1 p-2 border border-gray-300 rounded-md w-full">
            </div>
            <div class="flex items-center mb-6">
                <input type="checkbox" id="add-is-active" name="is_active" class="h-4 w-4 text-blue-600 border-gray-300 rounded">
                <label for="add-is-active" class="ml-2 block text-sm text-gray-900">Is Active</label>
            </div>
            <div class="flex justify-end space-x-4">
                <button type="button" class="px-4 py-2 bg-gray-300 text-gray-800 rounded-lg hover:bg-gray-400" onclick="hideModal('add-equipment-modal')">Cancel</button>
                <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 font-semibold">Add Equipment</button>
            </div>
        </form>
    </div>
</div>

<div id="edit-equipment-modal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center hidden z-50">
    <div class="bg-white p-6 rounded-lg shadow-xl w-11/12 max-w-lg text-gray-800">
        <h3 class="text-xl font-bold mb-4">Edit Equipment</h3>
        <form id="edit-equipment-form">
            <input type="hidden" id="edit-equipment-id" name="equipment_id">
            <div class="mb-4">
                <label for="edit-name" class="block text-sm font-medium text-gray-700">Equipment Name</label>
                <input type="text" id="edit-name" name="name" class="mt-1 p-2 border border-gray-300 rounded-md w-full" required>
            </div>
            <div class="mb-4">
                <label for="edit-type" class="block text-sm font-medium text-gray-700">Type</label>
                <select id="edit-type" name="type" class="mt-1 p-2 border border-gray-300 rounded-md w-full" required>
                    <option value="Dumpster">Dumpster</option>
                    <option value="Temporary Toilet">Temporary Toilet</option>
                    <option value="Storage Container">Storage Container</option>
                    <option value="Handwash Station">Handwash Station</option>
                </select>
            </div>
            <div class="mb-4">
                <label for="edit-size-capacity" class="block text-sm font-medium text-gray-700">Size/Capacity</label>
                <input type="text" id="edit-size-capacity" name="size_capacity" class="mt-1 p-2 border border-gray-300 rounded-md w-full">
            </div>
            <div class="mb-4">
                <label for="edit-description" class="block text-sm font-medium text-gray-700">Description</label>
                <textarea id="edit-description" name="description" rows="3" class="mt-1 p-2 border border-gray-300 rounded-md w-full"></textarea>
            </div>
            <div class="mb-4">
                <label for="edit-daily-rate" class="block text-sm font-medium text-gray-700">Daily Rate ($)</label>
                <input type="number" id="edit-daily-rate" name="daily_rate" step="0.01" min="0" class="mt-1 p-2 border border-gray-300 rounded-md w-full" required>
            </div>
            <div class="mb-4">
                <label for="edit-image-url" class="block text-sm font-medium text-gray-700">Image URL</label>
                <input type="url" id="edit-image-url" name="image_url" class="mt-1 p-2 border border-gray-300 rounded-md w-full">
            </div>
            <div class="flex items-center mb-6">
                <input type="checkbox" id="edit-is-active" name="is_active" class="h-4 w-4 text-blue-600 border-gray-300 rounded">
                <label for="edit-is-active" class="ml-2 block text-sm text-gray-900">Is Active</label>
            </div>
            <div class="flex justify-end space-x-4">
                <button type="button" class="px-4 py-2 bg-gray-300 text-gray-800 rounded-lg hover:bg-gray-400" onclick="hideModal('edit-equipment-modal')">Cancel</button>
                <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 font-semibold">Save Changes</button>
            </div>
        </form>
    </div>
</div>

<script>
    // --- Add Equipment Form Submission ---
    const addEquipmentForm = document.getElementById('add-equipment-form');
    if (addEquipmentForm) {
        addEquipmentForm.addEventListener('submit', async function(event) {
            event.preventDefault();

            const formData = new FormData(this);
            formData.append('action', 'add_equipment');

            const dailyRate = document.getElementById('add-daily-rate').value;
            if (parseFloat(dailyRate) <= 0) {
                showToast('Daily rate must be greater than 0.', 'error');
                return;
            }

            showToast('Adding new equipment...', 'info');

            try {
                const response = await fetch('/api/admin/equipment.php', {
                    method: 'POST',
                    body: formData
                });
                const result = await response.json();

                if (result.success) {
                    showToast(result.message, 'success');
                    addEquipmentForm.reset();
                    hideModal('add-equipment-modal');
                    window.loadAdminSection('equipment'); // Reload equipment list
                } else {
                    showToast(result.message, 'error');
                }
            } catch (error) {
                console.error('Add equipment API Error:', error);
                showToast('An error occurred. Please try again.', 'error');
            }
        });
    }

    // --- Edit Equipment Form Submission ---
    const editEquipmentForm = document.getElementById('edit-equipment-form');
    if (editEquipmentForm) {
        editEquipmentForm.addEventListener('submit', async function(event) {
            event.preventDefault();

            const formData = new FormData(this);
            formData.append('action', 'update_equipment');

            const dailyRate = document.getElementById('edit-daily-rate').value;
            if (parseFloat(dailyRate) <= 0) {
                showToast('Daily rate must be greater than 0.', 'error');
                return;
            }

            showToast('Saving equipment changes...', 'info');

            try {
                const response = await fetch('/api/admin/equipment.php', {
                    method: 'POST',
                    body: formData
                });
                const result = await response.json();

                if (result.success) {
                    showToast(result.message, 'success');
                    hideModal('edit-equipment-modal');
                    window.loadAdminSection('equipment'); // Reload equipment list
                } else {
                    showToast(result.message, 'error');
                }
            } catch (error) {
                console.error('Edit equipment API Error:', error);
                showToast('An error occurred. Please try again.', 'error');
            }
        });
    }

    // --- Event Listeners for Table Buttons (Edit, Delete) ---
    document.addEventListener('click', function(event) {
        // Edit Equipment button
        if (event.target.classList.contains('edit-equipment-btn')) {
            const button = event.target;
            document.getElementById('edit-equipment-id').value = button.dataset.id;
            document.getElementById('edit-name').value = button.dataset.name;
            document.getElementById('edit-type').value = button.dataset.type;
            document.getElementById('edit-size-capacity').value = button.dataset.sizeCapacity;
            document.getElementById('edit-description').value = button.dataset.description;
            document.getElementById('edit-daily-rate').value = button.dataset.dailyRate;
            document.getElementById('edit-image-url').value = button.dataset.imageUrl;
            document.getElementById('edit-is-active').checked = (button.dataset.isActive === '1');

            showModal('edit-equipment-modal');
        }

        // Delete Equipment button
        if (event.target.classList.contains('delete-equipment-btn')) {
            const equipmentId = event.target.dataset.id;
            const equipmentName = event.target.dataset.name;

            showConfirmationModal(
                'Delete Equipment',
                `Are you sure you want to delete "${equipmentName}"? This action cannot be undone.`,
                async (confirmed) => {
                    if (confirmed) {
                        showToast(`Deleting ${equipmentName}...`, 'info');
                        const formData = new FormData();
                        formData.append('action', 'delete_equipment');
                        formData.append('equipment_id', equipmentId);

                        try {
                            const response = await fetch('/api/admin/equipment.php', {
                                method: 'POST',
                                body: formData
                            });
                            const result = await response.json();

                            if (result.success) {
                                showToast(result.message, 'success');
                                window.loadAdminSection('equipment'); // Reload equipment list
                            } else {
                                showToast(result.message, 'error');
                            }
                        } catch (error) {
                            console.error('Delete equipment API Error:', error);
                            showToast('An error occurred. Please try again.', 'error');
                        }
                    }
                },
                'Delete Equipment',
                'bg-red-600'
            );
        }
    });
</script>