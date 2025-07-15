<?php
// customer/pages/edit-profile.php

// --- Setup & Includes ---
// Start the session if it hasn't been started already.
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
// Include necessary files for database connection, session management, and utility functions.
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../includes/functions.php';

// --- Authorization ---
// Ensure the user is logged in. If not, display an error and stop execution.
if (!is_logged_in()) {
    echo '<div class="text-red-500 text-center p-8">You must be logged in to view this content.</div>';
    exit;
}

// --- Data Fetching ---
// Get the logged-in user's ID from the session.
$user_id = $_SESSION['user_id'];
$user_data = [];

// Prepare and execute a query to fetch the current user's data from the database.
$stmt = $conn->prepare("SELECT first_name, last_name, email, phone_number, address, city, state, zip_code FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $user_data = $result->fetch_assoc();
} else {
    // This is a fallback; it should not be reached if the user is properly logged in.
    echo '<div class="text-red-500 text-center p-8">Could not find user data. Please try logging in again.</div>';
    $stmt->close();
    $conn->close();
    exit;
}
$stmt->close();
$conn->close();

// --- Security ---
// Generate a CSRF token to protect the form from cross-site request forgery attacks.
// This token will be included as a hidden field in the form.
generate_csrf_token();
?>

<h1 class="text-3xl font-bold text-gray-800 mb-8">Edit Profile</h1>

<div class="bg-white p-6 rounded-lg shadow-md border border-blue-200 max-w-2xl mx-auto">
    <form id="edit-profile-form" novalidate>
        <!-- Security: Hidden CSRF token field. This is validated by the API. -->
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">

        <div class="mb-5 text-center">
            <!-- Profile picture placeholder. The initials are generated from user's name. -->
            <img src="https://placehold.co/120x120/E0E7FF/4F46E5?text=<?php echo htmlspecialchars(substr($user_data['first_name'] ?? 'J', 0, 1) . substr($user_data['last_name'] ?? 'D', 0, 1)); ?>" alt="Profile Picture" class="w-32 h-32 rounded-full mx-auto border-4 border-blue-300 object-cover">
            <!-- Note: File upload functionality requires backend logic in the API to handle the file. -->
            <input type="file" id="profile-photo-upload" class="hidden" accept="image/*">
            <label for="profile-photo-upload" class="mt-4 inline-block px-4 py-2 bg-blue-100 text-blue-700 rounded-lg cursor-pointer hover:bg-blue-200 transition-colors duration-200">
                <i class="fas fa-camera mr-2"></i>Upload Photo
            </label>
        </div>

        <!-- Form fields are pre-filled with the user's current data. -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-5 mb-5">
            <div>
                <label for="first-name" class="block text-sm font-medium text-gray-700 mb-2">First Name</label>
                <input type="text" id="first-name" name="first_name" class="w-full p-3 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500" value="<?php echo htmlspecialchars($user_data['first_name'] ?? ''); ?>" required>
            </div>
            <div>
                <label for="last-name" class="block text-sm font-medium text-gray-700 mb-2">Last Name</label>
                <input type="text" id="last-name" name="last_name" class="w-full p-3 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500" value="<?php echo htmlspecialchars($user_data['last_name'] ?? ''); ?>" required>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-5 mb-5">
            <div>
                <label for="email" class="block text-sm font-medium text-gray-700 mb-2">Email Address</label>
                <input type="email" id="email" name="email" class="w-full p-3 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500" value="<?php echo htmlspecialchars($user_data['email'] ?? ''); ?>" required>
            </div>
            <div>
                <label for="phone" class="block text-sm font-medium text-gray-700 mb-2">Phone Number</label>
                <input type="tel" id="phone" name="phone_number" class="w-full p-3 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500" value="<?php echo htmlspecialchars($user_data['phone_number'] ?? ''); ?>" required>
            </div>
        </div>

        <div class="mb-5">
            <label for="address" class="block text-sm font-medium text-gray-700 mb-2">Address</label>
            <input type="text" id="address" name="address" class="w-full p-3 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500" value="<?php echo htmlspecialchars($user_data['address'] ?? ''); ?>" required>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-5 mb-5">
            <div>
                <label for="city" class="block text-sm font-medium text-gray-700 mb-2">City</label>
                <input type="text" id="city" name="city" class="w-full p-3 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500" value="<?php echo htmlspecialchars($user_data['city'] ?? ''); ?>" required>
            </div>
            <div>
                <label for="state" class="block text-sm font-medium text-gray-700 mb-2">State</label>
                <input type="text" id="state" name="state" class="w-full p-3 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500" value="<?php echo htmlspecialchars($user_data['state'] ?? ''); ?>" required>
            </div>
            <div>
                <label for="zip" class="block text-sm font-medium text-gray-700 mb-2">Zip Code</label>
                <input type="text" id="zip" name="zip_code" class="w-full p-3 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500" value="<?php echo htmlspecialchars($user_data['zip_code'] ?? ''); ?>" required>
            </div>
        </div>

        <div class="text-right">
            <button type="submit" class="py-3 px-6 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors duration-200 shadow-lg font-semibold">
                <i class="fas fa-save mr-2"></i>Save Changes
            </button>
        </div>
    </form>
</div>

<script>
    // This script block will be executed when the page is loaded via AJAX.
    (function() {
        const editProfileForm = document.getElementById('edit-profile-form');
        if (!editProfileForm) return;

        // --- Client-Side Validation Function ---
        function validateForm() {
            // This object will hold any validation errors.
            const errors = {};
            
            // Check for empty fields
            const requiredFields = ['first-name', 'last-name', 'email', 'phone', 'address', 'city', 'state', 'zip'];
            requiredFields.forEach(id => {
                const input = document.getElementById(id);
                if (!input.value.trim()) {
                    errors[id] = 'This field is required.';
                }
            });

            // Validate email format
            const emailInput = document.getElementById('email');
            if (emailInput.value && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(emailInput.value)) {
                errors['email'] = 'Please enter a valid email address.';
            }

            // Validate phone number format (simple regex for common formats)
            const phoneInput = document.getElementById('phone');
            if (phoneInput.value && !/^\(?(\d{3})\)?[-.\s]?(\d{3})[-.\s]?(\d{4})$/.test(phoneInput.value)) {
                errors['phone'] = 'Please enter a valid phone number.';
            }

            // You could add more specific validation for zip code, etc. here.

            return errors;
        }

        // --- Form Submission Handler ---
        editProfileForm.addEventListener('submit', async function(event) {
            event.preventDefault(); // Prevent the default browser form submission.

            // First, run client-side validation.
            const validationErrors = validateForm();
            if (Object.keys(validationErrors).length > 0) {
                // For simplicity, we'll show a single toast with the first error.
                // A more advanced implementation could highlight the specific fields.
                window.showToast(Object.values(validationErrors)[0], 'error');
                return;
            }

            // Show a loading indicator to the user.
            window.showToast('Saving your changes...', 'info');

            // Use the FormData API to easily collect all form fields for the AJAX request.
            const formData = new FormData(this);

            try {
                // Send the form data to the backend API endpoint.
                const response = await fetch('/api/customer/profile.php', {
                    method: 'POST',
                    body: formData
                });

                // Parse the JSON response from the API.
                const result = await response.json();

                if (result.success) {
                    // On success, show a success message.
                    window.showToast(result.message || 'Profile updated successfully!', 'success');
                    
                    // Update the welcome message in the main dashboard header to reflect the name change.
                    // We use `window.parent.document` because this script runs inside an iframe-like content area.
                    const welcomePrompt = window.parent.document.getElementById('welcome-prompt');
                    if (welcomePrompt) {
                        welcomePrompt.textContent = `Welcome back, ${formData.get('first_name')}!`;
                    }
                    
                    // Optionally, you could reload the section to ensure all data is fresh,
                    // though it's not strictly necessary if the API returns all updated data.
                    // window.loadCustomerSection('edit-profile');

                } else {
                    // If the API returns an error, display it to the user.
                    window.showToast(result.message || 'Failed to update profile.', 'error');
                }
            } catch (error) {
                // Catch any network or unexpected errors during the fetch operation.
                console.error('Profile update API Error:', error);
                window.showToast('An unexpected error occurred. Please try again.', 'error');
            }
        });

        // --- Profile Photo Upload Preview ---
        const profilePhotoUpload = document.getElementById('profile-photo-upload');
        if (profilePhotoUpload) {
            profilePhotoUpload.addEventListener('change', function() {
                const file = this.files[0];
                if (file) {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        // Update the src of the img tag to show a preview of the selected photo.
                        document.querySelector('#edit-profile-form img').src = e.target.result;
                        window.showToast('Photo selected. Click "Save Changes" to upload.', 'info');
                    };
                    reader.readAsDataURL(file);
                }
            });
        }
    })();
</script>