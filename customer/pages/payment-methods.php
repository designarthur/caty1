<?php
// customer/pages/payment-methods.php

// Ensure session is started and user is logged in
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../includes/functions.php'; // Required for CSRF token

if (!is_logged_in()) {
    echo '<div class="text-red-500 text-center p-8">You must be logged in to view this content.</div>';
    exit;
}

generate_csrf_token();
$csrf_token = $_SESSION['csrf_token'];

$user_id = $_SESSION['user_id'];

// Fetch saved payment methods from the database
$saved_payment_methods = [];
// Select all necessary fields to pass to frontend for editing
$stmt = $conn->prepare("SELECT id, stripe_payment_method_id, card_type, last_four, expiration_month, expiration_year, cardholder_name, is_default, billing_address FROM user_payment_methods WHERE user_id = ? ORDER BY is_default DESC, created_at DESC");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
while($row = $result->fetch_assoc()) {
    $expMonth = htmlspecialchars($row['expiration_month'] ?? '');
    $expYearFull = htmlspecialchars($row['expiration_year'] ?? '');
    $expYearLastTwo = substr($expYearFull, -2);
    $row['expiry_display'] = $expMonth . '/' . $expYearLastTwo;

    $row['card_last_four'] = htmlspecialchars($row['last_four'] ?? '');
    $row['raw_expiration_month'] = htmlspecialchars($row['expiration_month'] ?? '');
    $row['raw_expiration_year'] = htmlspecialchars($row['expiration_year'] ?? '');
    $row['raw_billing_address'] = htmlspecialchars($row['billing_address'] ?? '');

    $row['status'] = $row['is_default'] ? 'Default' : 'Active';
    $row['token'] = $row['stripe_payment_method_id']; // Use Stripe Payment Method ID as frontend identifier
    $saved_payment_methods[] = $row;
}
$stmt->close();

$conn->close();
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
                            data-stripe-payment-method-id="<?php echo htmlspecialchars($method['stripe_payment_method_id']); ?>"
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
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
        <input type="hidden" name="action" value="add_method">
        
        <div class="mb-5">
            <label for="cardholder-name" class="block text-sm font-medium text-gray-700 mb-2">Cardholder Name</label>
            <input type="text" id="cardholder-name" name="cardholder_name" class="w-full p-3 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500" required>
        </div>
        
        <div class="mb-5">
            <label for="card-element" class="block text-sm font-medium text-gray-700 mb-2">Credit or Debit Card</label>
            <div id="card-element" class="p-3 border border-gray-300 rounded-lg focus:border-blue-500" style="background-color: #f8f9fa;">
                </div>
            <div id="card-errors" role="alert" class="text-red-500 text-sm mt-2"></div>
        </div>

        <div class="mb-5">
            <label for="billing-address" class="block text-sm font-medium text-gray-700 mb-2">Billing Address (for card verification)</label>
            <input type="text" id="billing-address" name="billing_address" class="w-full p-3 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500" placeholder="123 Example St, City, State, Zip" required>
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
        <h3 class="text-xl font-bold mb-4">Edit Payment Method Details</h3>
        <form id="edit-payment-method-form">
            <input type="hidden" id="edit-method-id" name="id">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
            <input type="hidden" name="action" value="update_method">

            <div class="mb-5">
                <label for="edit-cardholder-name" class="block text-sm font-medium text-gray-700 mb-2">Cardholder Name</label>
                <input type="text" id="edit-cardholder-name" name="cardholder_name" class="w-full p-3 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500" required>
            </div>
            <div class="mb-5">
                <label for="edit-card-number-display" class="block text-sm font-medium text-gray-700 mb-2">Card Number (Last 4)</label>
                <input type="text" id="edit-card-number-display" class="w-full p-3 border border-gray-300 rounded-lg bg-gray-100 cursor-not-allowed" readonly>
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
                <input type="text" id="edit-billing-address" name="billing_address" class="w-full p-3 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500" placeholder="123 Example St, City, State, Zip" required>
            </div>
            <div class="mb-5 flex items-center">
                <input type="checkbox" id="edit-set-default" name="set_default" class="h-4 w-4 text-blue-600 border-gray-300 rounded">
                <label for="edit-set-default" class="ml-2 block text-sm text-gray-900">Set as default payment method</label>
            </div>
            <div class="flex justify-end space-x-4">
                <button type="button" class="px-4 py-2 bg-gray-300 text-gray-800 rounded-lg hover:bg-gray-400" onclick="hideModal('edit-payment-modal')">Cancel</button>
                <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 font-semibold">Save Changes</button>
            </div>
        </form>
    </div>
</div>


<script src="https://js.stripe.com/v3/"></script>
<script>
    (function() {
        // Stripe Public Key (replace with your actual public key from environment variable)
        const stripe = Stripe('pk_test_TYooMQauvdPbR5nWYBgDqgHw'); // This should come from your .env file via PHP
        const elements = stripe.elements();

        // Custom styling for Stripe Elements
        const style = {
            base: {
                color: '#2d3748',
                fontFamily: 'Inter, sans-serif',
                fontSize: '16px',
                '::placeholder': {
                    color: '#a0aec0',
                },
            },
            invalid: {
                color: '#ef4444',
                iconColor: '#ef4444',
            },
        };

        // Create an instance of the card Element.
        const cardElement = elements.create('card', { style: style });

        // Add an instance of the card Element into the `card-element` div.
        const cardElementContainer = document.getElementById('card-element');
        if (cardElementContainer) {
            cardElement.mount(cardElementContainer);
        }

        // Handle real-time validation errors from the card Element.
        cardElement.on('change', function(event) {
            const displayError = document.getElementById('card-errors');
            if (event.error) {
                displayError.textContent = event.error.message;
            } else {
                displayError.textContent = '';
            }
        });

        // --- Add Payment Method Form Handling ---
        const addPaymentMethodForm = document.getElementById('add-payment-method-form');
        if (addPaymentMethodForm) {
            addPaymentMethodForm.addEventListener('submit', async function(event) {
                event.preventDefault();

                const cardholderName = document.getElementById('cardholder-name').value.trim();
                const billingAddress = document.getElementById('billing-address').value.trim();
                const setDefault = document.getElementById('set-default').checked;

                if (!cardholderName || !billingAddress) {
                    window.showToast('Please fill in all required fields.', 'error');
                    return;
                }

                window.showToast('Adding new payment method...', 'info');

                try {
                    const { paymentMethod, error } = await stripe.createPaymentMethod({
                        type: 'card',
                        card: cardElement,
                        billing_details: {
                            name: cardholderName,
                            address: {
                                line1: billingAddress.split(',')[0].trim(),
                                city: billingAddress.split(',')[1] ? billingAddress.split(',')[1].trim() : '',
                                state: billingAddress.split(',')[2] ? billingAddress.split(',')[2].trim().split(' ')[0] : '',
                                postal_code: billingAddress.split(',')[2] ? billingAddress.split(',')[2].trim().split(' ')[1] : '',
                                country: 'US', // Assuming US for now, could be dynamic
                            },
                        },
                    });

                    if (error) {
                        const displayError = document.getElementById('card-errors');
                        displayError.textContent = error.message;
                        window.showToast(error.message, 'error');
                    } else {
                        // Send the paymentMethod.id to your server
                        const formData = new FormData();
                        formData.append('action', 'add_method');
                        formData.append('csrf_token', '<?php echo htmlspecialchars($csrf_token); ?>');
                        formData.append('payment_method_id', paymentMethod.id);
                        formData.append('cardholder_name', cardholderName);
                        formData.append('billing_address', billingAddress);
                        formData.append('set_default', setDefault ? 'true' : 'false');
                        // Also send card details for local storage (last4, brand, expiry)
                        formData.append('card_type', paymentMethod.card.brand);
                        formData.append('last_four', paymentMethod.card.last4);
                        formData.append('expiration_month', paymentMethod.card.exp_month);
                        formData.append('expiration_year', paymentMethod.card.exp_year);

                        const response = await fetch('/api/customer/payment_methods.php', {
                            method: 'POST',
                            body: formData
                        });

                        const result = await response.json();

                        if (result.success) {
                            window.showToast(result.message || 'Payment method added successfully!', 'success');
                            addPaymentMethodForm.reset();
                            cardElement.clear(); // Clear the card element
                            window.loadCustomerSection('payment-methods'); // Reload the section
                        } else {
                            window.showToast(result.message || 'Failed to add payment method.', 'error');
                        }
                    }
                } catch (error) {
                    console.error('Add payment method API Error:', error);
                    window.showToast('An error occurred while adding payment method. Please try again.', 'error');
                }
            });
        }

        // --- Edit Payment Method Form Handling ---
        const editPaymentMethodForm = document.getElementById('edit-payment-method-form');
        if (editPaymentMethodForm) {
            editPaymentMethodForm.addEventListener('submit', async function(event) {
                event.preventDefault();

                const methodId = document.getElementById('edit-method-id').value;
                const cardholderName = document.getElementById('edit-cardholder-name').value.trim();
                const expirationMonth = document.getElementById('edit-expiry-month').value.trim();
                const expirationYear = document.getElementById('edit-expiry-year').value.trim();
                const billingAddress = document.getElementById('edit-billing-address').value.trim();
                const setDefault = document.getElementById('edit-set-default').checked;

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
                formData.append('action', 'update_method');
                formData.append('csrf_token', '<?php echo htmlspecialchars($csrf_token); ?>');
                formData.append('id', methodId);
                formData.append('cardholder_name', cardholderName);
                formData.append('expiration_month', expirationMonth);
                formData.append('expiration_year', expirationYear);
                formData.append('billing_address', billingAddress);
                formData.append('set_default', setDefault ? 'true' : 'false');

                try {
                    const response = await fetch('/api/customer/payment_methods.php', {
                        method: 'POST',
                        body: formData
                    });

                    const result = await response.json();

                    if (result.success) {
                        window.showToast(result.message || 'Payment method updated successfully!', 'success');
                        window.hideModal('edit-payment-modal');
                        window.loadCustomerSection('payment-methods');
                    } else {
                        window.showToast(result.message || 'Failed to update payment method.', 'error');
                    }
                } catch (error) {
                    console.error('Update payment method API Error:', error);
                    window.showToast('An error occurred while updating payment method. Please try again.', 'error');
                }
            });
        }

        // Helper for expiry date validation
        function isValidExpiryDate(month, year) {
            if (!/^(0[1-9]|1[0-2])$/.test(month) || !/^\d{4}$/.test(year)) {
                return false;
            }
            const currentYear = new Date().getFullYear();
            const currentMonth = new Date().getMonth() + 1;
            const expMonth = parseInt(month, 10);
            const expYear = parseInt(year, 10);

            if (expYear < currentYear || (expYear === currentYear && expMonth < currentMonth)) {
                return false;
            }
            return true;
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
                document.getElementById('edit-expiry-year').value = row.dataset.expYear;
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
                            formData.append('csrf_token', '<?php echo htmlspecialchars($csrf_token); ?>');
                            formData.append('id', methodId);

                            try {
                                const response = await fetch('/api/customer/payment_methods.php', {
                                    method: 'POST',
                                    body: formData
                                });
                                const result = await response.json();
                                if (result.success) {
                                    window.showToast(result.message || 'Default payment method updated!', 'success');
                                    window.loadCustomerSection('payment-methods');
                                } else {
                                    window.showToast(result.message || 'Failed to set default payment method.', 'error');
                                }
                            } catch (error) {
                                console.error('Set default payment method API Error:', error);
                                window.showToast('An error occurred. Please try again.', 'error');
                            }
                        }
                    },
                    'Set Default',
                    'bg-green-600'
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
                            formData.append('csrf_token', '<?php echo htmlspecialchars($csrf_token); ?>');
                            formData.append('id', methodId);

                            try {
                                const response = await fetch('/api/customer/payment_methods.php', {
                                    method: 'POST',
                                    body: formData
                                });
                                const result = await response.json();
                                if (result.success) {
                                    window.showToast(result.message || 'Payment method deleted!', 'success');
                                    window.loadCustomerSection('payment-methods');
                                } else {
                                    window.showToast(result.message || 'Failed to delete payment method.', 'error');
                                }
                            } catch (error) {
                                console.error('Delete payment method API Error:', error);
                                window.showToast('An error occurred. Please try again.', 'error');
                            }
                        }
                    },
                    'Delete',
                    'bg-red-600'
                );
            }
        });
    })();
</script>
