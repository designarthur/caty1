<?php
// customer/pages/payment-methods.php

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

// Fetch saved payment methods from the database
$saved_payment_methods = [];
// Select all necessary fields to pass to frontend for editing
$stmt = $conn->prepare("SELECT id, braintree_payment_token, card_type, last_four, expiration_month, expiration_year, cardholder_name, is_default, billing_address FROM user_payment_methods WHERE user_id = ? ORDER BY is_default DESC, created_at DESC");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
while($row = $result->fetch_assoc()) {
    // Ensure expiration_month and expiration_year are not null before substr/htmlspecialchars
    $expMonth = htmlspecialchars($row['expiration_month'] ?? '');
    // Using ?? '' before substr for safety, then htmlspecialchars for output
    $expYearFull = htmlspecialchars($row['expiration_year'] ?? '');
    $expYearLastTwo = substr($expYearFull, -2);
    $row['expiry_display'] = $expMonth . '/' . $expYearLastTwo;

    // Ensure last_four is not null for card_last_four display
    $row['card_last_four'] = htmlspecialchars($row['last_four'] ?? '');
    // Add raw expiry parts for populating edit form
    $row['raw_expiration_month'] = htmlspecialchars($row['expiration_month'] ?? '');
    $row['raw_expiration_year'] = htmlspecialchars($row['expiration_year'] ?? '');
    $row['raw_billing_address'] = htmlspecialchars($row['billing_address'] ?? '');

    $row['status'] = $row['is_default'] ? 'Default' : 'Active'; // Convert boolean to string status
    $row['token'] = $row['braintree_payment_token']; // Use Braintree token as frontend identifier
    $saved_payment_methods[] = $row;
}
$stmt->close();

// Close DB connection if not needed further on this page
// $conn->close(); // Keep connection open until script ends
?>

<h1 class="text-3xl font-bold text-gray-800 mb-8">Payment Methods</h1>

<div class="bg-white p-6 rounded-lg shadow-md border border-blue-200 mb-8">
    <h2 class="text-xl font-semibold text-gray-700 mb-4 flex items-center"><i class="fas fa-credit-card mr-2 text-blue-600"></i>Saved Payment Methods</h2>
    <?php if (empty($saved_payment_methods)): ?>
        <p class="text-gray-600 text-center p-4">You have no saved payment methods. Add one below!</p>
    <?php else: ?>
        <div class="overflow-x-auto">
            <table id="saved-payment-methods-table" class="min-w-full divide-y divide-gray-200">
                <thead class="bg-blue-50">
                    <tr>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Cardholder Name</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Card Details</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Expiration</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Status</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php foreach ($saved_payment_methods as $method): ?>
                        <tr data-id="<?php echo htmlspecialchars($method['id']); ?>"
                            data-cardholder-name="<?php echo htmlspecialchars($method['cardholder_name']); ?>"
                            data-last-four="<?php echo htmlspecialchars($method['card_last_four']); ?>"
                            data-exp-month="<?php echo htmlspecialchars($method['raw_expiration_month']); ?>"
                            data-exp-year="<?php echo htmlspecialchars($method['raw_expiration_year']); ?>"
                            data-billing-address="<?php echo htmlspecialchars($method['raw_billing_address']); ?>"
                            data-is-default="<?php echo htmlspecialchars($method['is_default'] ? 'true' : 'false'); ?>">
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900"><?php echo htmlspecialchars($method['cardholder_name']); ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo htmlspecialchars($method['card_type']); ?> ending in **** <?php echo htmlspecialchars($method['card_last_four']); ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo htmlspecialchars($method['expiry_display']); ?></td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo $method['status'] === 'Default' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'; ?>"><?php echo htmlspecialchars($method['status']); ?></span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                <button class="text-indigo-600 hover:text-indigo-900 mr-2 edit-payment-btn" data-id="<?php echo htmlspecialchars($method['id']); ?>">Edit</button>
                                <?php if (!$method['is_default']): ?>
                                    <button class="text-green-600 hover:text-green-900 mr-2 set-default-payment-btn" data-id="<?php echo htmlspecialchars($method['id']); ?>">Set Default</button>
                                <?php endif; ?>
                                <button class="text-red-600 hover:text-red-900 delete-payment-btn" data-id="<?php echo htmlspecialchars($method['id']); ?>">Delete</button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<div class="bg-white p-6 rounded-lg shadow-md border border-blue-200 max-w-2xl mx-auto">
    <h2 class="text-xl font-semibold text-gray-700 mb-4 flex items-center"><i class="fas fa-plus-circle mr-2 text-green-600"></i>Add New Payment Method</h2>
    <form id="add-payment-method-form">
        <div class="mb-5">
            <label for="new-cardholder-name" class="block text-sm font-medium text-gray-700 mb-2">Cardholder Name</label>
            <input type="text" id="new-cardholder-name" name="cardholder_name" class="w-full p-3 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500" required>
        </div>
        <!-- Reverted to standard input fields for card number and CVV -->
        <div class="mb-5">
            <label for="new-card-number" class="block text-sm font-medium text-gray-700 mb-2">Card Number</label>
            <input type="text" id="new-card-number" name="card_number" class="w-full p-3 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500" placeholder="**** **** **** ****" required pattern="[0-9\s]{13,19}" maxlength="19">
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-5">
            <div>
                <label for="new-expiry-date" class="block text-sm font-medium text-gray-700 mb-2">Expiration Date (MM/YY)</label>
                <input type="text" id="new-expiry-date" name="expiry_date" class="w-full p-3 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500" placeholder="MM/YY" required pattern="(0[1-9]|1[0-2])\/[0-9]{2}">
            </div>
            <div>
                <label for="new-cvv" class="block text-sm font-medium text-gray-700 mb-2">CVV</label>
                <input type="text" id="new-cvv" name="cvv" class="w-full p-3 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500" placeholder="***" required pattern="[0-9]{3,4}">
            </div>
        </div>
        <div class="mb-5">
            <label for="new-billing-address" class="block text-sm font-medium text-gray-700 mb-2">Billing Address</label>
            <input type="text" id="new-billing-address" name="billing_address" class="w-full p-3 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500" placeholder="123 Example St, City, State, Zip" required>
        </div>
        <div class="mb-5 flex items-center">
            <input type="checkbox" id="set-default" name="set_default" class="h-4 w-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
            <label for="set-default" class="ml-2 block text-sm text-gray-900">Set as default payment method</label>
        </div>
        <div class="text-right">
            <button type="submit" class="py-3 px-6 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors duration-200 shadow-lg font-semibold">
                <i class="fas fa-plus mr-2"></i>Add Payment Method
            </button>
        </div>
    </form>
</div>

<div id="edit-payment-modal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center hidden z-50">
    <div class="bg-white p-6 rounded-lg shadow-xl w-11/12 max-w-md text-gray-800">
        <h3 class="text-xl font-bold mb-4">Edit Payment Method</h3>
        <form id="edit-payment-method-form">
            <input type="hidden" id="edit-method-id" name="id">
            <div class="mb-5">
                <label for="edit-cardholder-name" class="block text-sm font-medium text-gray-700 mb-2">Cardholder Name</label>
                <input type="text" id="edit-cardholder-name" name="cardholder_name" class="w-full p-3 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500" required>
            </div>
            <div class="mb-5">
                <label for="edit-card-number-display" class="block text-sm font-medium text-gray-700 mb-2">Card Number (Last 4)</label>
                <input type="text" id="edit-card-number-display" class="w-full p-3 border border-gray-300 rounded-lg bg-gray-100 cursor-not-allowed" readonly>
                <p class="text-xs text-gray-500 mt-1">Card number cannot be changed. To change card details, please add a new method.</p>
            </div>
             <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-5">
                <div>
                    <label for="edit-expiry-month" class="block text-sm font-medium text-gray-700 mb-2">Expiration Month (MM)</label>
                    <input type="text" id="edit-expiry-month" name="expiration_month" class="w-full p-3 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500" placeholder="MM" required pattern="(0[1-9]|1[0-2])">
                </div>
                <div>
                    <label for="edit-expiry-year" class="block text-sm font-medium text-gray-700 mb-2">Expiration Year (YYYY)</label>
                    <input type="text" id="edit-expiry-year" name="expiration_year" class="w-full p-3 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500" placeholder="YYYY" required pattern="[0-9]{4}">
                </div>
            </div>
            <div class="mb-5">
                <label for="edit-billing-address" class="block text-sm font-medium text-gray-700 mb-2">Billing Address</label>
                <input type="text" id="edit-billing-address" name="billing_address" class="w-full p-3 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500" required>
            </div>
            <div class="mb-5 flex items-center">
                <input type="checkbox" id="edit-set-default" name="set_default" class="h-4 w-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                <label for="edit-set-default" class="ml-2 block text-sm text-gray-900">Set as default payment method</label>
            </div>
            <div class="flex justify-end space-x-4">
                <button type="button" class="px-4 py-2 bg-gray-300 text-gray-800 rounded-lg hover:bg-gray-400" onclick="hideModal('edit-payment-modal')">Cancel</button>
                <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 font-semibold">Save Changes</button>
            </div>
        </form>
    </div>
</div>


<!-- Removed Braintree SDK scripts -->
<script>
    // IIFE to encapsulate the script and prevent global variable conflicts
    (function() {
        // Removed braintreeInstance and initializeBraintree function

        // Client-side validation for expiration date (MM/YY)
        function isValidExpiryDate(month, year) {
            if (!/^(0[1-9]|1[0-2])$/.test(month) || !/^\d{4}$/.test(year)) {
                return false;
            }

            const currentYear = new Date().getFullYear();
            const currentMonth = new Date().getMonth() + 1; // Month is 0-indexed

            const expMonth = parseInt(month, 10);
            const expYear = parseInt(year, 10);

            if (expYear < currentYear) {
                return false; // Expired year
            }
            if (expYear === currentYear && expMonth < currentMonth) {
                return false; // Expired month in current year
            }
            return true;
        }

        // --- Add Payment Method Form Handling ---
        const addPaymentMethodForm = document.getElementById('add-payment-method-form');
        if (addPaymentMethodForm) {
            addPaymentMethodForm.addEventListener('submit', async function(event) {
                event.preventDefault(); // Prevent default form submission

                const cardholderName = document.getElementById('new-cardholder-name').value.trim();
                const cardNumber = document.getElementById('new-card-number').value.trim();
                const expiryDate = document.getElementById('new-expiry-date').value.trim(); // MM/YY format
                const cvv = document.getElementById('new-cvv').value.trim();
                const billingAddress = document.getElementById('new-billing-address').value.trim();
                const setDefault = document.getElementById('set-default').checked;

                // Client-side validation
                if (!cardholderName || !cardNumber || !expiryDate || !cvv || !billingAddress) {
                    window.showToast('Please fill in all fields.', 'error');
                    return;
                }
                if (!/^\d{13,16}$/.test(cardNumber.replace(/\s/g, ''))) { // Remove spaces for validation
                    window.showToast('Please enter a valid card number (13-16 digits).', 'error');
                    return;
                }

                const expiryParts = expiryDate.split('/');
                if (expiryParts.length !== 2 || !isValidExpiryDate(expiryParts[0], '20' + expiryParts[1])) { // Assuming YY is 2-digit, convert to 4
                    window.showToast('Please enter a valid expiration date (MM/YY) that is not expired.', 'error');
                    return;
                }
                if (!/^\d{3,4}$/.test(cvv)) {
                    window.showToast('Please enter a valid CVV (3 or 4 digits).', 'error');
                    return;
                }

                window.showToast('Adding new payment method...', 'info');

                const formData = new FormData(this); // Get all form data
                formData.append('action', 'add_method');
                // No payment_method_nonce needed as Braintree is removed from client-side
                formData.append('cardholder_name', cardholderName);
                formData.append('card_number', cardNumber); // Send raw card number
                formData.append('expiry_date', expiryDate); // Send raw expiry date
                formData.append('cvv', cvv); // Send raw CVV
                formData.append('billing_address', billingAddress);
                formData.append('set_default', setDefault ? 'on' : 'off');

                try {
                    const response = await fetch('/api/customer/payment_methods.php', {
                        method: 'POST',
                        body: formData
                    });

                    const result = await response.json();

                    if (result.success) {
                        window.showToast(result.message || 'Payment method added successfully!', 'success');
                        addPaymentMethodForm.reset(); // Clear form
                        window.loadCustomerSection('payment-methods'); // Reload the section to show the new method in the list
                    } else {
                        window.showToast(result.message || 'Failed to add payment method.', 'error');
                    }
                } catch (error) {
                    console.error('Add payment method API Error:', error);
                    window.showToast('An error occurred while adding payment method. Please try again.', 'error');
                }
            });
        }

        // --- Edit Payment Method Form Handling (NEW) ---
        const editPaymentMethodForm = document.getElementById('edit-payment-method-form');
        if (editPaymentMethodForm) {
            editPaymentMethodForm.addEventListener('submit', async function(event) {
                event.preventDefault();

                const methodId = document.getElementById('edit-method-id').value;
                const cardholderName = document.getElementById('edit-cardholder-name').value.trim();
                const expirationMonth = document.getElementById('edit-expiry-month').value.trim();
                const expirationYear = document.getElementById('edit-expiry-year').value.trim(); // YYYY format
                const billingAddress = document.getElementById('edit-billing-address').value.trim();
                const setDefault = document.getElementById('edit-set-default').checked;

                // Client-side validation for edit form
                if (!cardholderName || !expirationMonth || !expirationYear || !billingAddress) {
                    window.showToast('Please fill in all fields.', 'error');
                    return;
                }
                if (!isValidExpiryDate(expirationMonth, expirationYear)) {
                    window.showToast('Please enter a valid expiration date (MM/YYYY) that is not expired.', 'error');
                    return;
                }

                window.showToast('Saving changes...', 'info');

                const formData = new FormData();
                formData.append('action', 'update_method'); // New action for the API
                formData.append('id', methodId);
                formData.append('cardholder_name', cardholderName);
                formData.append('expiration_month', expirationMonth);
                formData.append('expiration_year', expirationYear);
                formData.append('billing_address', billingAddress);
                formData.append('set_default', setDefault ? 'on' : 'off'); // Send 'on' or 'off' as expected by PHP

                try {
                    const response = await fetch('/api/customer/payment_methods.php', {
                        method: 'POST',
                        body: formData
                    });

                    const result = await response.json();

                    if (result.success) {
                        window.showToast(result.message || 'Payment method updated successfully!', 'success');
                        window.hideModal('edit-payment-modal'); // Hide the modal on success
                        window.loadCustomerSection('payment-methods'); // Reload to reflect changes
                    } else {
                        window.showToast(result.message || 'Failed to update payment method.', 'error');
                    }
                } catch (error) {
                    console.error('Update payment method API Error:', error);
                    window.showToast('An error occurred while updating payment method. Please try again.', 'error');
                }
            });
        }

        // --- Event listeners for table buttons (Edit, Set Default, Delete) ---
        document.addEventListener('click', async function(event) {
            // Handle "Edit" button click
            if (event.target.classList.contains('edit-payment-btn')) {
                const row = event.target.closest('tr');
                document.getElementById('edit-method-id').value = row.dataset.id;
                document.getElementById('edit-cardholder-name').value = row.dataset.cardholderName;
                document.getElementById('edit-card-number-display').value = '**** **** **** ' + row.dataset.lastFour;
                document.getElementById('edit-expiry-month').value = row.dataset.expMonth;
                document.getElementById('edit-expiry-year').value = row.dataset.expYear; // Full 4-digit year
                document.getElementById('edit-billing-address').value = row.dataset.billingAddress;
                document.getElementById('edit-set-default').checked = (row.dataset.isDefault === 'true');
                window.showModal('edit-payment-modal');
            }

            // Handle "Set Default" button click
            if (event.target.classList.contains('set-default-payment-btn')) {
                const methodId = event.target.dataset.id;
                window.showConfirmationModal(
                    'Set Default Payment Method',
                    'Are you sure you want to set this as your default payment method?',
                    async (confirmed) => {
                        if (confirmed) {
                            window.showToast('Setting default payment method...', 'info');
                            const formData = new FormData();
                            formData.append('action', 'set_default');
                            formData.append('id', methodId);

                            try {
                                const response = await fetch('/api/customer/payment_methods.php', {
                                    method: 'POST',
                                    body: formData
                                });
                                const result = await response.json();
                                if (result.success) {
                                    window.showToast(result.message || 'Default payment method updated!', 'success');
                                    window.loadCustomerSection('payment-methods'); // Reload to reflect changes
                                } else {
                                    window.showToast(result.message || 'Failed to set default payment method.', 'error');
                                }
                            } catch (error) {
                                console.error('Set default payment method API Error:', error);
                                window.showToast('An error occurred. Please try again.', 'error');
                            }
                        }
                    },
                    'Set Default', // Confirm button text
                    'bg-green-600' // Confirm button color
                );
            }

            // Handle "Delete" button click
            if (event.target.classList.contains('delete-payment-btn')) {
                const methodId = event.target.dataset.id;
                window.showConfirmationModal(
                    'Delete Payment Method',
                    'Are you sure you want to delete this payment method? This action cannot be undone.',
                    async (confirmed) => {
                        if (confirmed) {
                            window.showToast('Deleting payment method...', 'info');
                            const formData = new FormData();
                            formData.append('action', 'delete_method');
                            formData.append('id', methodId);

                            try {
                                const response = await fetch('/api/customer/payment_methods.php', {
                                    method: 'POST',
                                    body: formData
                                });
                                const result = await response.json();
                                if (result.success) {
                                    window.showToast(result.message || 'Payment method deleted!', 'success');
                                    window.loadCustomerSection('payment-methods'); // Reload to reflect changes
                                } else {
                                    window.showToast(result.message || 'Failed to delete payment method.', 'error');
                                    }
                                } catch (error) {
                                    console.error('Delete payment method API Error:', error);
                                    window.showToast('An error occurred. Please try again.', 'error');
                                }
                            }
                        },
                        'Delete', // Confirm button text
                        'bg-red-600' // Confirm button color
                    );
                }
            });
        })(); // End of IIFE