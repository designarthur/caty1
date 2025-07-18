<?php
// customer/pages/payment-methods.php

// Ensure session is started and user is logged in
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../includes/functions.php'; // Required for getSystemSetting() and other utilities

if (!is_logged_in()) {
    echo '<div class="text-red-500 text-center p-8">You must be logged in to view this content.</div>';
    exit;
}

generate_csrf_token(); // Ensure a token is always generated

$user_id = $_SESSION['user_id'];

// Fetch saved payment methods from the database
$saved_payment_methods = [];
$stmt = $conn->prepare("SELECT id, braintree_payment_token, card_type, last_four, expiration_month, expiration_year, cardholder_name, is_default, billing_address FROM user_payment_methods WHERE user_id = ? ORDER BY is_default DESC, created_at DESC");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
while($row = $result->fetch_assoc()) {
    $expMonth = $row['expiration_month'] ?? '';
    $expYearFull = $row['expiration_year'] ?? '';
    $expYearLastTwo = substr($expYearFull, -2);
    $row['expiry_display'] = htmlspecialchars($expMonth) . '/' . htmlspecialchars($expYearLastTwo);
    $row['card_last_four'] = $row['last_four'] ?? '';
    // Ensure raw data for editing is also passed
    $row['raw_expiration_month'] = $row['expiration_month'] ?? '';
    $row['raw_expiration_year'] = $row['expiration_year'] ?? '';
    $row['raw_billing_address'] = $row['billing_address'] ?? '';

    $saved_payment_methods[] = $row;
}
$stmt->close();
$conn->close();
?>

<script src="https://js.stripe.com/v3/"></script>

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
                        <tr
                            data-id="<?php echo htmlspecialchars($method['id']); ?>"
                            data-stripe-pm-id="<?php echo htmlspecialchars($method['braintree_payment_token'] ?? ''); ?>"
                            data-cardholder-name="<?php echo htmlspecialchars($method['cardholder_name'] ?? ''); ?>"
                            data-last-four="<?php echo htmlspecialchars($method['card_last_four']); ?>"
                            data-exp-month="<?php echo htmlspecialchars($method['raw_expiration_month']); ?>"
                            data-exp-year="<?php echo htmlspecialchars($method['raw_expiration_year']); ?>"
                            data-billing-address="<?php echo htmlspecialchars($method['raw_billing_address']); ?>"
                            data-is-default="<?php echo $method['is_default'] ? 'true' : 'false'; ?>"
                        >
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900"><?php echo htmlspecialchars($method['cardholder_name'] ?? ''); ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo htmlspecialchars($method['card_type'] ?? ''); ?> ending in **** <?php echo htmlspecialchars($method['card_last_four']); ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo $method['expiry_display']; ?></td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo $method['is_default'] ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'; ?>"><?php echo $method['is_default'] ? 'Default' : 'Active'; ?></span>
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
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
        <div class="mb-5">
            <label for="new-cardholder-name" class="block text-sm font-medium text-gray-700 mb-2">Cardholder Name</label>
            <input type="text" id="new-cardholder-name" name="cardholder_name" class="w-full p-3 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500" required>
        </div>
        <div class="mb-5">
            <label for="new-card-element" class="block text-sm font-medium text-gray-700 mb-2">Credit or debit card</label>
            <div id="new-card-element" class="w-full p-3 border border-gray-300 rounded-lg" style="min-height: 40px; padding: 12px;">
                </div>
            <div id="new-card-errors" role="alert" class="text-red-500 text-sm mt-2"></div>
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
            <button type="submit" id="add-card-submit-btn" class="py-3 px-6 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors duration-200 shadow-lg font-semibold">
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
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
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


<script>
    (function() {
        let stripeInstance;
        let elementsInstance;
        let newCardElementInstance;

        function getCsrfToken() {
            return document.querySelector('input[name="csrf_token"]').value;
        }

        function initializeStripeElements() {
            if (typeof Stripe === 'undefined') { setTimeout(initializeStripeElements, 100); return; }
            const stripePublishableKey = '<?php echo $_ENV['STRIPE_PUBLISHABLE_KEY'] ?? getenv('STRIPE_PUBLISHABLE_KEY'); ?>';
            if (!stripePublishableKey || stripePublishableKey.includes('your_publishable_key')) {
                console.error('Stripe Publishable Key is not configured.');
                window.showToast('Payment system not fully configured.', 'error');
                return;
            }
            stripeInstance = Stripe(stripePublishableKey);
            elementsInstance = stripeInstance.elements();
            const style = { base: { color: '#32325d', fontFamily: 'Inter, sans-serif', fontSmoothing: 'antialiased', fontSize: '16px', '::placeholder': { color: '#aab7c4' } }, invalid: { color: '#fa755a', iconColor: '#fa755a' } };
            newCardElementInstance = elementsInstance.create('card', { style: style });
            newCardElementInstance.mount('#new-card-element');
            newCardElementInstance.addEventListener('change', (event) => {
                document.getElementById('new-card-errors').textContent = event.error ? event.error.message : '';
            });
        }
        initializeStripeElements();

        async function handleApiRequest(formData) {
            try {
                const response = await fetch('/api/customer/payment_methods.php', { method: 'POST', body: formData });
                const result = await response.json();
                if (result.success) {
                    window.showToast(result.message || 'Action completed successfully!', 'success');
                    window.loadCustomerSection('payment-methods');
                } else {
                    window.showToast(result.message || 'An unknown error occurred.', 'error');
                }
            } catch (error) {
                console.error('API Request Error:', error);
                window.showToast('A critical error occurred. Please contact support.', 'error');
            }
        }

        document.getElementById('add-payment-method-form')?.addEventListener('submit', async function(event) {
            event.preventDefault();
            const submitBtn = event.target.querySelector('button[type="submit"]');
            submitBtn.disabled = true;
            window.showToast('Adding card...', 'info');

            const { paymentMethod, error } = await stripeInstance.createPaymentMethod({
                type: 'card',
                card: newCardElementInstance,
                billing_details: { name: document.getElementById('new-cardholder-name').value.trim() },
            });

            if (error) {
                window.showToast('Failed to add card: ' + error.message, 'error');
                submitBtn.disabled = false;
                return;
            }
            
            const formData = new FormData(event.target);
            formData.set('action', 'add_method');
            formData.set('payment_method_id', paymentMethod.id);
            await handleApiRequest(formData);
            submitBtn.disabled = false;
        });
        
        document.getElementById('edit-payment-method-form')?.addEventListener('submit', async function(event) {
            event.preventDefault();
            window.showToast('Saving changes...', 'info');
            const formData = new FormData(event.target);
            formData.set('action', 'update_method');
            await handleApiRequest(formData);
            window.hideModal('edit-payment-modal');
        });

        document.addEventListener('click', async function(event) {
            const csrfToken = getCsrfToken();
            if (!csrfToken) {
                window.showToast('Security token missing. Please refresh.', 'error');
                return;
            }

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

            if (event.target.classList.contains('set-default-payment-btn')) {
                const methodId = event.target.dataset.id;
                window.showConfirmationModal('Set as Default?', 'Set this card as your default payment method?',
                    async (confirmed) => {
                        if (confirmed) {
                            const formData = new FormData();
                            formData.append('action', 'set_default');
                            formData.append('id', methodId);
                            formData.append('csrf_token', csrfToken);
                            await handleApiRequest(formData);
                        }
                    }, 'Set Default', 'bg-green-600');
            }

            if (event.target.classList.contains('delete-payment-btn')) {
                const row = event.target.closest('tr');
                const methodId = row.dataset.id;
                const stripePmId = row.dataset.stripePmId;
                window.showConfirmationModal('Delete Method?', 'Are you sure you want to delete this payment method?',
                    async (confirmed) => {
                        if (confirmed) {
                            const formData = new FormData();
                            formData.append('action', 'delete_method');
                            formData.append('id', methodId);
                            formData.append('stripe_pm_id', stripePmId);
                            formData.append('csrf_token', csrfToken);
                            await handleApiRequest(formData);
                        }
                    }, 'Delete', 'bg-red-600');
            }
        });
    })();
</script>