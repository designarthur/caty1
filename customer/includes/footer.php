<?php
// customer/includes/footer.php
// This file holds modals and all shared JavaScript for the customer dashboard.
// It will dynamically load page content from customer/pages/ via AJAX.
?>

<div id="logout-modal" class="fixed inset-0 bg-black bg-opacity-50 flex items-start justify-center z-50 hidden">
    <div class="bg-white w-full h-full sm:w-11/12 sm:max-w-md sm:h-auto sm:my-8 rounded-t-lg sm:rounded-lg shadow-xl flex flex-col">
        <div class="flex items-center justify-between p-4 bg-gray-100 sm:bg-white border-b sm:border-b-0">
            <h3 class="text-lg sm:text-xl font-bold text-gray-800">Confirm Logout</h3>
            <button class="text-gray-500 hover:text-gray-700 text-xl" onclick="hideModal('logout-modal')">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="flex-1 p-4 sm:p-6 text-gray-800 overflow-y-auto">
            <p class="mb-4 sm:mb-6 text-sm sm:text-base">Are you sure you want to log out?</p>
            <div class="flex justify-end space-x-2 sm:space-x-4">
                <button class="px-4 py-2 bg-gray-300 text-gray-800 rounded-lg hover:bg-gray-400 text-sm sm:text-base min-w-[80px]" onclick="hideModal('logout-modal')">Cancel</button>
                <a href="/customer/logout.php" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 text-sm sm:text-base min-w-[80px]">Logout</a>
            </div>
        </div>
    </div>
</div>

<div id="delete-account-modal" class="fixed inset-0 bg-black bg-opacity-50 flex items-start justify-center z-50 hidden">
    <div class="bg-white w-full h-full sm:w-11/12 sm:max-w-md sm:h-auto sm:my-8 rounded-t-lg sm:rounded-lg shadow-xl flex flex-col">
        <div class="flex items-center justify-between p-4 bg-gray-100 sm:bg-white border-b sm:border-b-0">
            <h3 class="text-lg sm:text-xl font-bold text-red-600">Confirm Account Deletion</h3>
            <button class="text-gray-500 hover:text-gray-700 text-xl" onclick="hideModal('delete-account-modal')">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="flex-1 p-4 sm:p-6 text-gray-800 overflow-y-auto">
            <p class="mb-4 sm:mb-6 text-sm sm:text-base">This action is irreversible. Are you absolutely sure you want to delete your account?</p>
            <div class="flex justify-end space-x-2 sm:space-x-4">
                <button class="px-4 py-2 bg-gray-300 text-gray-800 rounded-lg hover:bg-gray-400 text-sm sm:text-base min-w-[80px]" onclick="hideModal('delete-account-modal')">Cancel</button>
                <button class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 text-sm sm:text-base min-w-[80px]" id="confirm-delete-account">Delete Account</button>
            </div>
        </div>
    </div>
</div>

<div id="payment-success-modal" class="fixed inset-0 bg-black bg-opacity-50 flex items-start justify-center z-50 hidden">
    <div class="bg-white w-full h-full sm:w-11/12 sm:max-w-md sm:h-auto sm:my-8 rounded-t-lg sm:rounded-lg shadow-xl flex flex-col text-center">
        <div class="flex items-center justify-between p-4 bg-gray-100 sm:bg-white border-b sm:border-b-0">
            <h3 class="text-lg sm:text-xl font-bold text-gray-800">Payment Successful!</h3>
            <button class="text-gray-500 hover:text-gray-700 text-xl" onclick="hideModal('payment-success-modal')">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="flex-1 p-4 sm:p-6 text-gray-800 overflow-y-auto">
            <i class="fas fa-check-circle text-green-500 text-4xl sm:text-6xl mb-4"></i>
            <p class="mb-4 sm:mb-6 text-sm sm:text-base">Your payment has been processed successfully.</p>
            <button class="px-4 sm:px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 text-sm sm:text-base min-w-[80px]" onclick="hideModal('payment-success-modal')">Great!</button>
        </div>
    </div>
</div>

<div id="confirmation-modal" class="fixed inset-0 bg-black bg-opacity-50 flex items-start justify-center z-50 hidden">
    <div class="bg-white w-full h-full sm:w-11/12 sm:max-w-md sm:h-auto sm:my-8 rounded-t-lg sm:rounded-lg shadow-xl flex flex-col">
        <div class="flex items-center justify-between p-4 bg-gray-100 sm:bg-white border-b sm:border-b-0">
            <h3 class="text-lg sm:text-xl font-bold text-gray-800" id="confirmation-modal-title">Confirm Action</h3>
            <button class="text-gray-500 hover:text-gray-700 text-xl" onclick="hideModal('confirmation-modal')">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="flex-1 p-4 sm:p-6 text-gray-800 overflow-y-auto">
            <p class="mb-4 sm:mb-6 text-sm sm:text-base" id="confirmation-modal-message">Are you sure you want to proceed with this action?</p>
            <div class="flex justify-end space-x-2 sm:space-x-4">
                <button class="px-4 py-2 bg-gray-300 text-gray-800 rounded-lg hover:bg-gray-400 text-sm sm:text-base min-w-[80px]" id="confirmation-modal-cancel">Cancel</button>
                <button class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 text-sm sm:text-base min-w-[80px]" id="confirmation-modal-confirm">Confirm</button>
            </div>
        </div>
    </div>
</div>

<div id="tutorial-overlay" class="fixed inset-0 bg-black bg-opacity-70 flex items-start justify-center z-50 hidden">
    <div class="bg-white w-full h-full sm:w-11/12 sm:max-w-3xl sm:h-auto sm:my-8 rounded-t-lg sm:rounded-lg shadow-xl flex flex-col">
        <div class="flex items-center justify-between p-4 bg-gray-100 sm:bg-white border-b sm:border-b-0">
            <h2 class="text-xl sm:text-2xl font-bold text-gray-800" id="tutorial-title">Welcome to Your Dashboard!</h2>
            <button class="text-gray-500 hover:text-gray-700 text-xl" onclick="hideModal('tutorial-overlay')">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="flex-1 p-4 sm:p-8 text-gray-800 overflow-y-auto">
            <p class="text-sm sm:text-base text-gray-700 mb-4 sm:mb-6" id="tutorial-text">
                This short tour will guide you through the key features of your <?php echo htmlspecialchars($companyName); ?> Customer Dashboard.
            </p>
            <div class="flex justify-between items-center">
                <button id="tutorial-prev-btn" class="px-4 py-2 bg-gray-300 text-gray-800 rounded-lg hover:bg-gray-400 text-sm sm:text-base min-w-[80px] hidden">
                    <i class="fas fa-arrow-left mr-2"></i>Previous
                </button>
                <button id="tutorial-next-btn" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 text-sm sm:text-base min-w-[80px]">
                    Next <i class="fas fa-arrow-right ml-2"></i>
                </button>
                <button id="tutorial-end-btn" class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 text-sm sm:text-base min-w-[80px]">
                    End Tutorial
                </button>
            </div>
        </div>
    </div>
</div>

<div id="relocation-request-modal" class="fixed inset-0 bg-black bg-opacity-50 flex items-start justify-center z-50 hidden">
    <div class="bg-white w-full h-full sm:w-11/12 sm:max-w-md sm:h-auto sm:my-8 rounded-t-lg sm:rounded-lg shadow-xl flex flex-col">
        <div class="flex items-center justify-between p-4 bg-gray-100 sm:bg-white border-b sm:border-b-0">
            <h3 class="text-lg sm:text-xl font-bold text-gray-800">Request Relocation</h3>
            <button class="text-gray-500 hover:text-gray-700 text-xl" onclick="hideModal('relocation-request-modal')">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="flex-1 p-4 sm:p-6 text-gray-800 overflow-y-auto">
            <p class="mb-3 sm:mb-4 text-sm sm:text-base">Fixed relocation charge: <span class="font-bold text-blue-600">$40.00</span></p>
            <div class="mb-4 sm:mb-5">
                <label for="relocation-address" class="block text-xs sm:text-sm font-medium text-gray-700 mb-2">New Destination Address</label>
                <input type="text" id="relocation-address" class="w-full p-2 sm:p-3 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500 text-sm sm:text-base" placeholder="Enter new address" required>
            </div>
            <div class="flex justify-end space-x-2 sm:space-x-4">
                <button class="px-4 py-2 bg-gray-300 text-gray-800 rounded-lg hover:bg-gray-400 text-sm sm:text-base min-w-[80px]" onclick="hideModal('relocation-request-modal')">Cancel</button>
                <button class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 text-sm sm:text-base min-w-[80px]" onclick="confirmRelocation()">Confirm Relocation</button>
            </div>
        </div>
    </div>
</div>

<div id="swap-request-modal" class="fixed inset-0 bg-black bg-opacity-50 flex items-start justify-center z-50 hidden">
    <div class="bg-white w-full h-full sm:w-11/12 sm:max-w-md sm:h-auto sm:my-8 rounded-t-lg sm:rounded-lg shadow-xl flex flex-col">
        <div class="flex items-center justify-between p-4 bg-gray-100 sm:bg-white border-b sm:border-b-0">
            <h3 class="text-lg sm:text-xl font-bold text-gray-800">Request Equipment Swap</h3>
            <button class="text-gray-500 hover:text-gray-700 text-xl" onclick="hideModal('swap-request-modal')">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="flex-1 p-4 sm:p-6 text-gray-800 overflow-y-auto">
            <p class="mb-3 sm:mb-4 text-sm sm:text-base">Fixed swap charge: <span class="font-bold text-blue-600">$30.00</span></p>
            <p class="mb-4 sm:mb-6 text-sm sm:text-base">Are you sure you want to request an equipment swap for this booking?</p>
            <div class="flex justify-end space-x-2 sm:space-x-4">
                <button class="px-4 py-2 bg-gray-300 text-gray-800 rounded-lg hover:bg-gray-400 text-sm sm:text-base min-w-[80px]" onclick="hideModal('swap-request-modal')">Cancel</button>
                <button class="px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 text-sm sm:text-base min-w-[80px]" onclick="confirmSwap()">Confirm Swap</button>
            </div>
        </div>
    </div>
</div>

<div id="pickup-request-modal" class="fixed inset-0 bg-black bg-opacity-50 flex items-start justify-center z-50 hidden">
    <div class="bg-white w-full h-full sm:w-11/12 sm:max-w-md sm:h-auto sm:my-8 rounded-t-lg sm:rounded-lg shadow-xl flex flex-col">
        <div class="flex items-center justify-between p-4 bg-gray-100 sm:bg-white border-b sm:border-b-0">
            <h3 class="text-lg sm:text-xl font-bold text-gray-800">Schedule Pickup</h3>
            <button class="text-gray-500 hover:text-gray-700 text-xl" onclick="hideModal('pickup-request-modal')">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="flex-1 p-4 sm:p-6 text-gray-800 overflow-y-auto">
            <div class="mb-4 sm:mb-5">
                <label for="pickup-date" class="block text-xs sm:text-sm font-medium text-gray-700 mb-2">Preferred Pickup Date</label>
                <input type="date" id="pickup-date" class="w-full p-2 sm:p-3 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500 text-sm sm:text-base" required>
            </div>
            <div class="mb-4 sm:mb-5">
                <label for="pickup-time" class="block text-xs sm:text-sm font-medium text-gray-700 mb-2">Preferred Pickup Time</label>
                <input type="time" id="pickup-time" class="w-full p-2 sm:p-3 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500 text-sm sm:text-base" required>
            </div>
            <div class="flex justify-end space-x-2 sm:space-x-4">
                <button class="px-4 py-2 bg-gray-300 text-gray-800 rounded-lg hover:bg-gray-400 text-sm sm:text-base min-w-[80px]" onclick="hideModal('pickup-request-modal')">Cancel</button>
                <button class="px-4 py-2 bg-teal-600 text-white rounded-lg hover:bg-teal-700 text-sm sm:text-base min-w-[80px]" onclick="confirmPickup()">Schedule Pickup</button>
            </div>
        </div>
    </div>
</div>

<script>
    // These functions are specific to invoices.php and are made global for onclick attributes.
    window.showInvoiceDetails = function(invoiceId) {
        window.loadCustomerSection('invoices', { invoice_id: invoiceId });
    };

    window.hideInvoiceDetails = function() {
        window.loadCustomerSection('invoices');
        history.replaceState(null, '', '#invoices'); // Adjust URL hash
    };

    window.hidePaymentForm = function() {
        const invoiceDetailView = document.getElementById('invoice-detail-view');
        const isDetailViewVisible = !invoiceDetailView.classList.contains('hidden');

        document.getElementById('payment-form-view').classList.add('hidden');
        if (isDetailViewVisible) {
            invoiceDetailView.classList.remove('hidden');
        } else {
            window.loadCustomerSection('invoices');
            history.replaceState(null, '', '#invoices');
        }
    };

    // --- IIFE for Invoice Page Specific JavaScript ---
    (function() {
        const paymentForm = document.getElementById('payment-form');
        const cardholderNameInput = document.getElementById('cardholder-name');
        const cardNumberInput = document.getElementById('card-number');
        const expiryDateInput = document.getElementById('expiry-date');
        const cvvInput = document.getElementById('cvv');
        const billingAddressInput = document.getElementById('billing-address');
        const saveCardSection = document.getElementById('save-card-section');
        const saveNewCardCheckbox = document.getElementById('save-new-card');
        const setNewCardDefaultSection = document.getElementById('set-new-card-default-section');
        const paymentMethodTokenInput = document.getElementById('payment-method-token-hidden');

        function resetPaymentForm() {
            paymentForm.reset();
            saveCardSection.classList.add('hidden');
            setNewCardDefaultSection.classList.add('hidden');
            paymentMethodTokenInput.value = '';
            paymentForm.dataset.originalDetails = '';
            cardNumberInput.value = '';
            cvvInput.value = '';
            [cardNumberInput, cvvInput].forEach(el => {
                el.disabled = false;
                el.placeholder = el.id === 'card-number' ? '**** **** **** ****' : '***';
                el.classList.remove('bg-gray-100');
            });
        }

        function populateWithDefault(method) {
            cardholderNameInput.value = method.cardholder_name;
            billingAddressInput.value = method.billing_address;
            expiryDateInput.value = `${method.expiration_month}/${method.expiration_year.slice(-2)}`;
            paymentMethodTokenInput.value = method.braintree_payment_token;

            cardNumberInput.value = `**** **** **** ${method.last_four}`;
            cardNumberInput.disabled = true;
            cvvInput.placeholder = "***";
            cvvInput.disabled = true;
            [cardNumberInput, cvvInput].forEach(el => el.classList.add('bg-gray-100'));

            const originalDetails = {
                cardholder_name: method.cardholder_name,
                billing_address: method.billing_address,
                expiry_date: `${method.expiration_month}/${method.expiration_year.slice(-2)}`,
            };
            paymentForm.dataset.originalDetails = JSON.stringify(originalDetails);
        }

        function checkForChanges() {
            const originalDetails = JSON.parse(paymentForm.dataset.originalDetails || '{}');
            if (Object.keys(originalDetails).length === 0) return;

            const currentDetails = {
                cardholder_name: cardholderNameInput.value.trim(),
                billing_address: billingAddressInput.value.trim(),
                expiry_date: expiryDateInput.value.trim()
            };

            const hasChanged = JSON.stringify(originalDetails) !== JSON.stringify(currentDetails);

            if (hasChanged) {
                saveCardSection.classList.remove('hidden');
                if (cardNumberInput.disabled) {
                    cardNumberInput.disabled = false;
                    cardNumberInput.value = '';
                    cardNumberInput.placeholder = 'Enter new card number';
                    cvvInput.disabled = false;
                    cvvInput.value = '';
                    cvvInput.placeholder = '***';
                    [cardNumberInput, cvvInput].forEach(el => el.classList.remove('bg-gray-100'));
                    paymentMethodTokenInput.value = '';
                }
            } else {
                saveCardSection.classList.add('hidden');
            }
        }

        window.showPaymentForm = async function(invoiceId, amount) {
            resetPaymentForm();

            document.getElementById('payment-invoice-id').textContent = 'ID ' + invoiceId;
            document.getElementById('payment-form-invoice-id-hidden').value = invoiceId;
            document.getElementById('payment-amount').value = parseFloat(amount).toFixed(2);

            document.getElementById('invoice-detail-view').classList.add('hidden');
            document.getElementById('invoice-list').classList.add('hidden');
            document.getElementById('payment-form-view').classList.remove('hidden');

            try {
                const response = await fetch('/api/customer/payment_methods.php?action=get_default_method');
                const result = await response.json();
                if (result.success && result.method) {
                    populateWithDefault(result.method);
                }
            } catch (error) {
                console.warn('Could not fetch default payment method:', error);
            }
        };

        [cardholderNameInput, expiryDateInput, billingAddressInput].forEach(input => {
            input.addEventListener('input', checkForChanges);
        });

        saveNewCardCheckbox.addEventListener('change', function() {
            if (this.checked) {
                setNewCardDefaultSection.classList.remove('hidden');
            } else {
                setNewCardDefaultSection.classList.add('hidden');
            }
        });
        
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

        if (paymentForm && !paymentForm.dataset.listenerAttached) {
            paymentForm.addEventListener('submit', async function(event) {
                event.preventDefault();
                
                const cardNumber = cardNumberInput.value.trim();
                const expiryDate = expiryDateInput.value.trim();
                const cvv = cvvInput.value.trim();

                if (!cardNumberInput.disabled) {
                     if (!/^\d{13,16}$/.test(cardNumber.replace(/\s/g, ''))) {
                        window.showToast('Please enter a valid card number (13-16 digits).', 'error');
                        return;
                    }
                     if (!/^\d{3,4}$/.test(cvv)) {
                        window.showToast('Please enter a valid CVV (3 or 4 digits).', 'error');
                        return;
                    }
                }

                const expiryParts = expiryDate.split('/');
                if (expiryParts.length !== 2 || !isValidExpiryDate(expiryParts[0], '20' + expiryParts[1])) {
                    window.showToast('Please enter a valid expiration date (MM/YY) that is not expired.', 'error');
                    return;
                }

                window.showToast('Processing payment...', 'info');

                try {
                    const formData = new FormData(this);
                    const response = await fetch('/api/payments.php', {
                        method: 'POST',
                        body: formData
                    });

                    const result = await response.json();

                    if (result.success) {
                        window.hidePaymentForm();
                        window.showToast(result.message || 'Payment successful!', 'success');
                        window.loadCustomerSection('bookings', { booking_id: result.booking_id });
                    } else {
                        window.showToast('Payment failed: ' + result.message, 'error');
                    }
                } catch (error) {
                    console.error('Payment API Error:', error);
                    window.showToast('An error occurred during payment. Please try again.', 'error');
                }
            });
            paymentForm.dataset.listenerAttached = 'true';
        }

        document.body.addEventListener('click', function(event) {
            const payButton = event.target.closest('.pay-invoice-btn, .pay-now-detail-btn');
            if (payButton) {
                window.showPaymentForm(payButton.dataset.invoiceId, payButton.dataset.amount);
            }
            
            const viewButton = event.target.closest('.view-invoice-details');
            if(viewButton){
                 window.showInvoiceDetails(viewButton.dataset.invoiceId);
            }
        });

    })(); // End IIFE
</script>

<?php include __DIR__ . '/../../includes/ai_chat_widget.php'; ?>

<style>
    /* Custom scroll for better mobile experience */
    .custom-scroll {
        scrollbar-width: thin;
        scrollbar-color: #a0aec0 #edf2f7;
    }
    .custom-scroll::-webkit-scrollbar {
        width: 6px;
    }
    .custom-scroll::-webkit-scrollbar-track {
        background: #edf2f7;
    }
    .custom-scroll::-webkit-scrollbar-thumb {
        background-color: #a0aec0;
        border-radius: 3px;
    }
</style>