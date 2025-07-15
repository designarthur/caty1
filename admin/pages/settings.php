<?php
// admin/pages/settings.php

// Ensure session is started and user is logged in as admin
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/session.php'; // For has_role and user_id
require_once __DIR__ . '/../../includes/functions.php'; // For getSystemSetting()

if (!is_logged_in() || !has_role('admin')) {
    echo '<div class="text-red-500 text-center p-8">Unauthorized access.</div>';
    exit;
}

// Fetch all relevant settings
$company_name = getSystemSetting('company_name');
$admin_email = getSystemSetting('admin_email');
$global_tax_rate = getSystemSetting('global_tax_rate');
$global_service_fee = getSystemSetting('global_service_fee');


// Fallback values if settings are not yet in DB
$company_name = $company_name ?? 'Your Company Name';
$admin_email = $admin_email ?? 'admin@example.com';
$global_tax_rate = $global_tax_rate ?? '0';
$global_service_fee = $global_service_fee ?? '0';


$conn->close();
?>

<h1 class="text-3xl font-bold text-gray-800 mb-8">System Settings</h1>

<div class="bg-white p-6 rounded-lg shadow-md border border-blue-200 max-w-2xl mx-auto">
    <form id="system-settings-form">
        <div class="space-y-6">
            <div>
                <h2 class="text-xl font-semibold text-gray-700 mb-4 flex items-center"><i class="fas fa-cogs mr-2 text-blue-600"></i>General Settings</h2>
                <div class="mb-4">
                    <label for="company-name" class="block text-sm font-medium text-gray-700">Company Name</label>
                    <input type="text" id="company-name" name="company_name" class="mt-1 p-2 border border-gray-300 rounded-md w-full" value="<?php echo htmlspecialchars($company_name); ?>" required>
                </div>
                <div class="mb-4">
                    <label for="admin-email" class="block text-sm font-medium text-gray-700">Admin Email Recipient (for notifications)</label>
                    <input type="email" id="admin-email" name="admin_email" class="mt-1 p-2 border border-gray-300 rounded-md w-full" value="<?php echo htmlspecialchars($admin_email); ?>" required>
                </div>
            </div>

            <div class="border-t pt-6">
                <h2 class="text-xl font-semibold text-gray-700 mb-4 flex items-center"><i class="fas fa-dollar-sign mr-2 text-green-600"></i>Financial Settings</h2>
                 <div class="mb-4">
                    <label for="global_tax_rate" class="block text-sm font-medium text-gray-700">Global Tax Rate (%)</label>
                    <input type="number" id="global_tax_rate" name="global_tax_rate" step="0.01" min="0" class="mt-1 p-2 border border-gray-300 rounded-md w-full" value="<?php echo htmlspecialchars($global_tax_rate); ?>" required>
                    <p class="text-xs text-gray-500 mt-1">This rate will be suggested when creating new quotes/invoices.</p>
                </div>
                 <div class="mb-4">
                    <label for="global_service_fee" class="block text-sm font-medium text-gray-700">Global Service Fee ($)</label>
                    <input type="number" id="global_service_fee" name="global_service_fee" step="0.01" min="0" class="mt-1 p-2 border border-gray-300 rounded-md w-full" value="<?php echo htmlspecialchars($global_service_fee); ?>" required>
                     <p class="text-xs text-gray-500 mt-1">This flat fee will be suggested as a line item on new quotes/invoices.</p>
                </div>
            </div>
        </div>

        <div class="flex justify-end mt-8">
            <button type="submit" class="px-5 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors duration-200 shadow-md font-semibold">
                <i class="fas fa-save mr-2"></i>Save Settings
            </button>
        </div>
    </form>
</div>

<script>
    // --- System Settings Form Submission ---
    const systemSettingsForm = document.getElementById('system-settings-form');
    if (systemSettingsForm) {
        systemSettingsForm.addEventListener('submit', async function(event) {
            event.preventDefault();

            const formData = new FormData(this);
            formData.append('action', 'update_settings');

            const companyName = document.getElementById('company-name').value.trim();
            const adminEmail = document.getElementById('admin-email').value.trim();

            if (!companyName) {
                showToast('Company Name cannot be empty.', 'error');
                return;
            }
            if (!adminEmail || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(adminEmail)) {
                showToast('Please enter a valid Admin Email address.', 'error');
                return;
            }

            showToast('Saving settings...', 'info');

            try {
                const response = await fetch('/api/admin/settings.php', {
                    method: 'POST',
                    body: formData
                });
                const result = await response.json();

                if (result.success) {
                    showToast(result.message, 'success');
                    // Reload the section to confirm new values are displayed
                    window.loadAdminSection('settings');
                } else {
                    showToast(result.message, 'error');
                }
            } catch (error) {
                console.error('Update settings API Error:', error);
                showToast('An error occurred. Please try again.', 'error');
            }
        });
    }
</script>