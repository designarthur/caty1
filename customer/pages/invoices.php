<?php
// customer/pages/invoices.php

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

$user_id = $_SESSION['user_id'];
$invoices = [];
$invoice_detail = null; // To hold data for a single invoice detail view if requested

// Check if a specific invoice ID is requested for detail view
$requested_invoice_id = $_GET['invoice_id'] ?? null;
$requested_quote_id = $_GET['quote_id'] ?? null; // For direct link from pending quotes

// Fetch all invoices for the list
$stmt_all_invoices = $conn->prepare("SELECT id, invoice_number, amount, status, created_at, due_date FROM invoices WHERE user_id = ? ORDER BY created_at DESC");
$stmt_all_invoices->bind_param("i", $user_id);
$stmt_all_invoices->execute();
$result_all_invoices = $stmt_all_invoices->get_result();
while ($row = $result_all_invoices->fetch_assoc()) {
    $invoices[] = $row;
}
$stmt_all_invoices->close();

// If a specific invoice number is requested, fetch its details
if ($requested_invoice_id || $requested_quote_id) { 
    $sql = "SELECT
                i.id, i.invoice_number, i.amount, i.status, i.created_at, i.due_date, i.transaction_id, i.payment_method, i.discount, i.tax, i.booking_id,
                u.first_name, u.last_name, u.email, u.address, u.city, u.state, u.zip_code
            FROM invoices i
            JOIN users u ON i.user_id = u.id
            WHERE i.user_id = ?";

    if ($requested_invoice_id) {
        $sql .= " AND i.id = ?";
        $stmt_detail = $conn->prepare($sql);
        $stmt_detail->bind_param("ii", $user_id, $requested_invoice_id);
    } else { // requested_quote_id
        $sql .= " AND i.quote_id = ?";
        $stmt_detail = $conn->prepare($sql);
        $stmt_detail->bind_param("ii", $user_id, $requested_quote_id);
    }
    
    $stmt_detail->execute();
    $result_detail = $stmt_detail->get_result();
    if ($result_detail->num_rows > 0) {
        $invoice_detail = $result_detail->fetch_assoc();
        
        // Fetch line items
        $invoice_detail['items'] = [];
        $stmt_items = $conn->prepare("SELECT * FROM invoice_items WHERE invoice_id = ?");
        $stmt_items->bind_param("i", $invoice_detail['id']);
        $stmt_items->execute();
        $result_items = $stmt_items->get_result();
        while($item_row = $result_items->fetch_assoc()){
            $invoice_detail['items'][] = $item_row;
        }
        $stmt_items->close();

        // Fetch booking start and end dates if a booking_id is associated with the invoice
        if (!empty($invoice_detail['booking_id'])) {
            $stmt_booking_dates = $conn->prepare("SELECT start_date, end_date FROM bookings WHERE id = ?");
            $stmt_booking_dates->bind_param("i", $invoice_detail['booking_id']);
            $stmt_booking_dates->execute();
            $booking_dates = $stmt_booking_dates->get_result()->fetch_assoc();
            $stmt_booking_dates->close();
            if ($booking_dates) {
                $invoice_detail['booking_start_date'] = $booking_dates['start_date'];
                $invoice_detail['booking_end_date'] = $booking_dates['end_date'];
            }
        }

    }
    $stmt_detail->close();
}


$conn->close();

// Function to get status badge classes (re-used from bookings.php)
function getStatusBadgeClass($status) {
    switch ($status) {
        case 'paid':
            return 'bg-green-100 text-green-800';
        case 'partially_paid':
            return 'bg-yellow-100 text-yellow-800';
        case 'pending':
            return 'bg-red-100 text-red-800';
        case 'cancelled':
            return 'bg-gray-100 text-gray-800';
        default:
            return 'bg-gray-100 text-gray-700';
    }
}
?>

<h1 class="text-3xl font-bold text-gray-800 mb-8">Invoices</h1>

<div id="invoice-list" class="<?php echo $invoice_detail ? 'hidden' : ''; ?>">
    <div class="bg-white p-6 rounded-lg shadow-md border border-blue-200">
        <?php if (empty($invoices)): ?>
            <div class="text-center text-gray-600 p-4">You have no invoices yet.</div>
        <?php else: ?>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-blue-50">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Invoice ID</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Date</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Amount</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Status</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($invoices as $invoice): ?>
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900"><?php echo htmlspecialchars($invoice['invoice_number']); ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo (new DateTime($invoice['created_at']))->format('Y-m-d'); ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">$<?php echo number_format($invoice['amount'], 2); ?></td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo getStatusBadgeClass($invoice['status']); ?>"><?php echo htmlspecialchars(strtoupper(str_replace('_', ' ', $invoice['status']))); ?></span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    <button class="text-blue-600 hover:text-blue-900 view-invoice-details" data-invoice-id="<?php echo htmlspecialchars($invoice['id']); ?>">View</button>
                                    <?php if ($invoice['status'] == 'pending' || $invoice['status'] == 'partially_paid'): ?>
                                        <button class="ml-3 text-green-600 hover:text-green-900 pay-invoice-btn" data-invoice-id="<?php echo htmlspecialchars($invoice['id']); ?>" data-amount="<?php echo htmlspecialchars($invoice['amount']); ?>">Pay Now</button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<div id="invoice-detail-view" class="bg-white p-6 rounded-lg shadow-md border border-blue-200 mt-8 <?php echo $invoice_detail ? '' : 'hidden'; ?>">
    <button class="mb-4 px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300" onclick="window.hideInvoiceDetails()">
        <i class="fas fa-arrow-left mr-2"></i>Back to Invoices
    </button>
    <?php if ($invoice_detail): ?>
        <div class="flex justify-between items-start">
            <h2 class="text-2xl font-bold text-gray-800 mb-6" id="detail-invoice-number">Invoice Details for #<?php echo htmlspecialchars($invoice_detail['invoice_number']); ?></h2>
            <a href="/api/customer/download.php?type=invoice&id=<?php echo htmlspecialchars($invoice_detail['id']); ?>" target="_blank" class="px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700 text-sm">
                <i class="fas fa-file-pdf mr-2"></i>Download PDF
            </a>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
            <div>
                <p class="text-gray-600"><span class="font-medium">Invoice Date:</span> <span id="detail-invoice-date"><?php echo (new DateTime($invoice_detail['created_at']))->format('Y-m-d'); ?></span></p>
                <p class="text-gray-600"><span class="font-medium">Due Date:</span> <?php echo $invoice_detail['due_date'] ? (new DateTime($invoice_detail['due_date']))->format('Y-m-d') : 'N/A'; ?></p>
                <p class="text-gray-600"><span class="font-medium">Status:</span> <span id="detail-invoice-status" class="font-semibold <?php echo getStatusBadgeClass($invoice_detail['status']); ?>"><?php echo htmlspecialchars(strtoupper(str_replace('_', ' ', $invoice_detail['status']))); ?></span></p>
                <p class="text-gray-600"><span class="font-medium">Transaction ID:</span> <?php echo htmlspecialchars($invoice_detail['transaction_id'] ?? 'N/A'); ?></p>
                <p class="text-gray-600"><span class="font-medium">Payment Method:</span> <?php echo htmlspecialchars($invoice_detail['payment_method'] ?? 'N/A'); ?></p>
                <?php if (!empty($invoice_detail['booking_start_date'])): ?>
                    <p class="text-gray-600"><span class="font-medium">Rental Start Date:</span> <?php echo (new DateTime($invoice_detail['booking_start_date']))->format('Y-m-d'); ?></p>
                <?php endif; ?>
                <?php if (!empty($invoice_detail['booking_end_date'])): ?>
                    <p class="text-gray-600"><span class="font-medium">Rental End Date:</span> <?php echo (new DateTime($invoice_detail['booking_end_date']))->format('Y-m-d'); ?></p>
                <?php endif; ?>
            </div>
            <div>
                <p class="text-gray-600"><span class="font-medium">Billed To:</span> <?php echo htmlspecialchars($invoice_detail['first_name'] . ' ' . $invoice_detail['last_name']); ?></p>
                <p class="text-gray-600"><span class="font-medium">Address:</span> <?php echo htmlspecialchars($invoice_detail['address'] . ', ' . $invoice_detail['city'] . ', ' . $invoice_detail['state'] . ' ' . $invoice_detail['zip_code']); ?></p>
                <p class="text-gray-600"><span class="font-medium">Email:</span> <?php echo htmlspecialchars($invoice_detail['email']); ?></p>
            </div>
        </div>

        <h3 class="text-xl font-semibold text-gray-700 mb-4">Items</h3>
        <div class="overflow-x-auto mb-6">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-blue-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Description</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Quantity</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Unit Price</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Total</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php
                    $subtotal = 0;
                    if(!empty($invoice_detail['items'])) {
                        foreach ($invoice_detail['items'] as $item){
                            $subtotal += $item['total'];
                            echo '<tr>';
                            echo '<td class="px-6 py-4 text-sm text-gray-900">' . htmlspecialchars($item['description']) . '</td>';
                            echo '<td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">' . htmlspecialchars($item['quantity']) . '</td>';
                            echo '<td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">$' . number_format($item['unit_price'], 2) . '</td>';
                            echo '<td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">$' . number_format($item['total'], 2) . '</td>';
                            echo '</tr>';
                        }
                    }
                    ?>
                </tbody>
            </table>
        </div>

        <div class="flex justify-end mt-4">
            <div class="w-full md:w-1/2 space-y-2 text-gray-700">
                <div class="flex justify-between"><span class="font-medium">Subtotal:</span> <span>$<?php echo number_format($subtotal, 2); ?></span></div>
                <div class="flex justify-between text-red-500"><span class="font-medium">Discount:</span> <span>-$<?php echo number_format($invoice_detail['discount'], 2); ?></span></div>
                <div class="flex justify-between"><span class="font-medium">Tax:</span> <span>$<?php echo number_format($invoice_detail['tax'], 2); ?></span></div>
                <div class="flex justify-between text-xl font-bold border-t pt-2 border-gray-300"><span class="font-medium">Grand Total:</span> <span class="text-blue-700">$<?php echo number_format($invoice_detail['amount'], 2); ?></span></div>
            </div>
        </div>

        <div id="payment-actions" class="flex justify-end mt-6">
            <?php if ($invoice_detail['status'] == 'pending' || $invoice_detail['status'] == 'partially_paid'): ?>
                <button class="py-2 px-5 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors duration-200 show-payment-form-btn pay-now-detail-btn" data-invoice-id="<?php echo htmlspecialchars($invoice_detail['id']); ?>" data-amount="<?php echo htmlspecialchars($invoice_detail['amount']); ?>">
                <i class="fas fa-dollar-sign mr-2"></i>Pay Now
            </button>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <p class="text-center text-gray-600">Invoice details not found or invalid invoice ID.</p>
    <?php endif; ?>
</div>

<div id="payment-form-view" class="bg-white p-6 rounded-lg shadow-md border border-blue-200 hidden mt-8">
    <button class="mb-4 px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300" onclick="window.hidePaymentForm()">
        <i class="fas fa-arrow-left mr-2"></i>Back to Invoice Details
    </button>
    <h2 class="text-2xl font-bold text-gray-800 mb-6">Process Payment for <span id="payment-invoice-id"></span></h2>

    <form id="payment-form" data-original-details="">
        <input type="hidden" name="action" value="process_payment">
        <input type="hidden" name="invoice_id" id="payment-form-invoice-id-hidden">
        <input type="hidden" name="payment_method_token" id="payment-method-token-hidden">

        <div class="mb-5">
            <label for="cardholder-name" class="block text-sm font-medium text-gray-700 mb-2">Cardholder Name</label>
            <input type="text" id="cardholder-name" name="cardholder_name" class="w-full p-3 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500" placeholder="John Doe" required>
        </div>
        <div class="mb-5">
            <label for="card-number" class="block text-sm font-medium text-gray-700 mb-2">Card Number</label>
            <input type="text" id="card-number" name="card_number" class="w-full p-3 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500" placeholder="**** **** **** ****" required pattern="[0-9\s]{13,19}" maxlength="19">
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-5">
            <div>
                <label for="expiry-date" class="block text-sm font-medium text-gray-700 mb-2">Expiration Date (MM/YY)</label>
                <input type="text" id="expiry-date" name="expiry_date" class="w-full p-3 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500" placeholder="MM/YY" required pattern="(0[1-9]|1[0-2])\/[0-9]{2}">
            </div>
            <div>
                <label for="cvv" class="block text-sm font-medium text-gray-700 mb-2">CVV</label>
                <input type="text" id="cvv" name="cvv" class="w-full p-3 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500" placeholder="***" required pattern="[0-9]{3,4}">
            </div>
        </div>
        <div class="mb-5">
            <label for="billing-address" class="block text-sm font-medium text-gray-700 mb-2">Billing Address</label>
            <input type="text" id="billing-address" name="billing_address" class="w-full p-3 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500" placeholder="123 Example St, City, State, Zip" required>
        </div>

        <div id="save-card-section" class="mb-5 p-4 bg-blue-50 border border-blue-200 rounded-lg hidden">
             <p class="text-sm text-blue-800 mb-3">You've updated your card details. Would you like to save this as a new payment method?</p>
             <div class="flex items-center mb-2">
                 <input type="checkbox" id="save-new-card" name="save_new_card" class="h-4 w-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                 <label for="save-new-card" class="ml-2 block text-sm text-gray-900">Save this new card for future use</label>
             </div>
             <div id="set-new-card-default-section" class="flex items-center hidden ml-6">
                 <input type="checkbox" id="set-new-card-default" name="set_new_card_default" class="h-4 w-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                 <label for="set-new-card-default" class="ml-2 block text-sm text-gray-900">Also make this my default payment method</label>
             </div>
        </div>

        <div class="mb-5">
            <label for="payment-amount" class="block text-sm font-medium text-gray-700 mb-2">Amount to Pay</label>
            <input type="number" id="payment-amount" name="amount" class="w-full p-3 border border-gray-300 rounded-lg bg-gray-100 focus:ring-blue-500 focus:border-blue-500" step="0.01" value="0.00" readonly>
        </div>
        <button type="submit" class="w-full py-3 px-4 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors duration-200 shadow-lg font-semibold">
            <i class="fas fa-dollar-sign mr-2"></i>Confirm Payment
        </button>
    </form>
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