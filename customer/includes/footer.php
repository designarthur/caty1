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

<div id="tutorial-overlay" class="fixed inset-0 bg-black bg-opacity-70 flex items-start justify-center z-50 hidden tutorial-animate">
    <div class="bg-white w-full h-full sm:w-11/12 sm:max-w-3xl sm:h-auto sm:my-8 rounded-t-lg sm:rounded-lg shadow-xl flex flex-col tutorial-content-animate">
        <div class="flex items-center justify-between p-4 bg-gradient-to-r from-blue-600 to-indigo-700 text-white rounded-t-lg">
            <h2 class="text-xl sm:text-2xl font-bold flex items-center" id="tutorial-title">Welcome to Your Dashboard!</h2>
            <button class="text-white hover:text-gray-200 text-xl" onclick="hideModal('tutorial-overlay')">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="flex-1 p-4 sm:p-8 text-gray-800 overflow-y-auto">
            <div id="tutorial-step-content" class="fade-in">
                <p class="text-sm sm:text-base text-gray-700 mb-4 sm:mb-6" id="tutorial-text">
                    This short tour will guide you through the key features of your <?php echo htmlspecialchars($companyName); ?> Customer Dashboard.
                </p>
                </div>
            <div class="flex justify-between items-center mt-6">
                <button id="tutorial-prev-btn" class="px-4 py-2 bg-gray-300 text-gray-800 rounded-lg hover:bg-gray-400 text-sm sm:text-base min-w-[80px] hidden opacity-0 pointer-events-none transition-all duration-300">
                    <i class="fas fa-arrow-left mr-2"></i>Previous
                </button>
                <button id="tutorial-next-btn" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 text-sm sm:text-base min-w-[80px] transition-all duration-300">
                    Next <i class="fas fa-arrow-right ml-2"></i>
                </button>
                <button id="tutorial-end-btn" class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 text-sm sm:text-base min-w-[80px] hidden opacity-0 transition-all duration-300">
                    End Tutorial
                </button>
            </div>
            <div class="text-center mt-4 text-sm text-gray-500" id="tutorial-progress">
                Step 1 of 7
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
    // --- Global Helper Functions (UNCHANGED) ---
    function showModal(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) modal.classList.remove('hidden');
    }

    function hideModal(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) modal.classList.add('hidden');
    }
    // ... other helper functions like showToast, showConfirmationModal ...

    // --- NEW: Tutorial Initialization Function ---
    function initializeTutorial() {
        // console.log("initializeTutorial() called."); // Debugging
        const tutorialNextBtn = document.getElementById('tutorial-next-btn');
        // If the button doesn't exist or already has a listener, do nothing.
        // This check prevents re-attaching listeners on subsequent initializeTutorial calls (e.g., from AJAX re-runs).
        if (!tutorialNextBtn || tutorialNextBtn.dataset.listenerAttached === 'true') {
            // console.log("Tutorial buttons already initialized or not found yet."); // Debugging
            return;
        }

        const tutorialSteps = [
            { title: "Welcome to Your Dashboard!", text: "This short tour will guide you through the key features of your <?php echo htmlspecialchars($companyName); ?> Customer Dashboard." },
            { title: "1. Account Information", text: "This section displays your personal details. You can update this information by clicking the 'Edit Profile' button." },
            { title: "2. Quick Statistics", text: "Get an at-a-glance overview of your active bookings, pending quotes, and invoice statuses." },
            { title: "3. Pending Quotes for Pricing", text: "Here you'll see any service requests you've submitted that are awaiting a quote from our team." },
            { title: "4. New Requests via AI Chat", text: "Easily start a new equipment booking or junk removal request using our smart AI assistant." },
            { title: "5. Main Navigation Menu", text: "On the left (or bottom on mobile), you'll find quick links to manage your Quotes, Bookings, Invoices, and more." },
            { title: "You're All Set!", text: "That's it for the tour! Feel free to explore your dashboard." }
        ];

        let currentTutorialStep = 0;

        // Get references to tutorial elements once during initialization
        const tutorialTitle = document.getElementById('tutorial-title');
        const tutorialText = document.getElementById('tutorial-text');
        const tutorialStepContent = document.getElementById('tutorial-step-content');
        const tutorialPrevBtn = document.getElementById('tutorial-prev-btn');
        const tutorialEndBtn = document.getElementById('tutorial-end-btn');
        const tutorialProgress = document.getElementById('tutorial-progress');

        // Verify all elements are found. If not, log error and return.
        if (!tutorialTitle || !tutorialText || !tutorialStepContent || !tutorialPrevBtn || !tutorialNextBtn || !tutorialEndBtn || !tutorialProgress) {
            console.error("Critical tutorial UI elements not found. Tutorial will not function.", {
                tutorialTitle: tutorialTitle, tutorialText: tutorialText, tutorialStepContent: tutorialStepContent,
                tutorialPrevBtn: tutorialPrevBtn, tutorialNextBtn: tutorialNextBtn, tutorialEndBtn: tutorialEndBtn,
                tutorialProgress: tutorialProgress
            });
            return; // Exit if elements are missing
        }
        // console.log("All tutorial UI elements successfully referenced."); // Debugging

        const updateTutorialUI = () => {
            // console.log("updateTutorialUI called. Current step:", currentTutorialStep); // Debugging

            // Apply fade-out effect for current content
            tutorialStepContent.classList.remove('fade-in');
            tutorialStepContent.classList.add('fade-out');

            setTimeout(() => {
                // Update content
                tutorialTitle.textContent = tutorialSteps[currentTutorialStep].title;
                tutorialText.textContent = tutorialSteps[currentTutorialStep].text;
                tutorialProgress.textContent = `Step ${currentTutorialStep + 1} of ${tutorialSteps.length}`;

                // Handle Previous button visibility and interactivity
                if (currentTutorialStep === 0) {
                    tutorialPrevBtn.classList.add('hidden', 'opacity-0');
                    tutorialPrevBtn.style.pointerEvents = 'none'; // Disable clicks
                } else {
                    tutorialPrevBtn.classList.remove('hidden', 'opacity-0');
                    tutorialPrevBtn.style.pointerEvents = 'auto'; // Enable clicks
                }

                // Handle Next and End buttons visibility and interactivity
                if (currentTutorialStep === tutorialSteps.length - 1) {
                    // Last step: Hide Next, show End
                    tutorialNextBtn.classList.add('hidden', 'opacity-0', 'pointer-events-none');
                    tutorialEndBtn.classList.remove('hidden', 'opacity-0');
                    tutorialEndBtn.style.pointerEvents = 'auto'; // Enable clicks
                } else {
                    // Not last step: Show Next, hide End
                    tutorialNextBtn.classList.remove('hidden', 'opacity-0', 'pointer-events-none');
                    tutorialNextBtn.style.pointerEvents = 'auto'; // Enable clicks
                    tutorialEndBtn.classList.add('hidden', 'opacity-0');
                    tutorialEndBtn.style.pointerEvents = 'none'; // Disable clicks
                }

                // Apply fade-in effect after content update
                tutorialStepContent.classList.remove('fade-out');
                tutorialStepContent.classList.add('fade-in');
            }, 300); // This delay should match the CSS fade-out transition duration
        };

        // Make window.startTutorial globally available
        window.startTutorial = function() {
            // console.log("window.startTutorial() called."); // Debugging
            currentTutorialStep = 0; // Reset to the first step
            updateTutorialUI(); // Load content for the first step
            showModal('tutorial-overlay'); // Show the modal
        };
        
        // Attach event listeners for tutorial navigation buttons
        // Attach these listeners ONLY ONCE during the first initialization
        // Use event delegation for robustness for 'Next', 'Previous', 'End Tutorial' buttons
        document.addEventListener('click', function(event) {
            if (event.target.closest('#tutorial-next-btn')) {
                // console.log('Next button clicked! Current step:', currentTutorialStep); // Debugging
                if (currentTutorialStep < tutorialSteps.length - 1) {
                    currentTutorialStep++;
                    updateTutorialUI();
                }
            } else if (event.target.closest('#tutorial-prev-btn')) {
                // console.log('Previous button clicked! Current step:', currentTutorialStep); // Debugging
                if (currentTutorialStep > 0) {
                    currentTutorialStep--;
                    updateTutorialUI();
                }
            } else if (event.target.closest('#tutorial-end-btn')) {
                // console.log('End Tutorial button clicked!'); // Debugging
                hideModal('tutorial-overlay');
                currentTutorialStep = 0; // Reset for next time
            }
        });

        // Mark the initializeTutorial function as completed for future checks if needed
        // This is done via the dataset.listenerAttached on tutorialNextBtn.
    }

    // --- Run the initialization function ---
    // Execute initialization logic after DOM is fully loaded.
    // A slight delay is added to ensure all elements are rendered, especially useful for dynamic content.
    document.addEventListener('DOMContentLoaded', () => {
        setTimeout(initializeTutorial, 100); // 100ms delay
        // console.log("DOMContentLoaded fired. initializeTutorial scheduled.");
    });


    // --- Other Global Request functions for Modals (Relocation, Swap, Pickup) ---
    window.confirmRelocation = function() {
        const newAddress = document.getElementById('relocation-address').value;
        if (newAddress) {
            hideModal('relocation-request-modal');
            showToast(`Relocation to "${newAddress}" requested successfully! Charges: $40.00 (Dummy)`, 'success');
        } else {
            showToast('Please enter a new destination address.', 'error');
        }
    }

    window.confirmSwap = function() {
        hideModal('swap-request-modal');
        showToast('Equipment swap requested successfully! Charges: $30.00 (Dummy)', 'success');
    }

    window.confirmPickup = function() {
        const pickupDate = document.getElementById('pickup-date').value;
        const pickupTime = document.getElementById('pickup-time').value;
        if (pickupDate && pickupTime) {
            hideModal('pickup-request-modal');
            showToast(`Pickup scheduled for ${pickupDate} at ${pickupTime}. (Dummy)`, 'success');
        } else {
            showToast('Please select a preferred pickup date and time.', 'error');
        }
    }

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

    /* Tutorial Modal Enhancements for Extraordinary UI */
    .tutorial-animate {
        backdrop-filter: blur(5px); /* Subtle blur behind modal */
        /* opacity and transform are handled by showModal/hideModal which toggles 'hidden' */
    }
    .tutorial-content-animate {
        transform: translateY(20px);
        opacity: 0;
        transition: transform 0.3s ease-out, opacity 0.3s ease-out;
        box-shadow: 0 20px 40px rgba(0, 0, 0, 0.2);
    }
    /* When modal is shown, make content visible */
    #tutorial-overlay:not(.hidden) .tutorial-content-animate {
        transform: translateY(0);
        opacity: 1;
    }

    .tutorial-header {
        border-bottom: 1px solid #e2e8f0;
    }

    #tutorial-title {
        color: white; /* Text color for the gradient header */
        text-shadow: 1px 1px 2px rgba(0,0,0,0.2);
    }

    /* Fade transition for content inside the tutorial step */
    #tutorial-step-content {
        transition: opacity 0.3s ease-in-out, transform 0.3s ease-in-out; /* Apply transition directly to the element */
    }
    #tutorial-step-content.fade-in {
        opacity: 1;
        transform: translateY(0);
    }
    #tutorial-step-content.fade-out {
        opacity: 0;
        transform: translateY(-10px); /* Move slightly up as it fades out */
    }

    /* Button Animations (already present, ensuring they align with new logic) */
    .tutorial-next-btn,
    .tutorial-prev-btn,
    .tutorial-end-btn {
        transition: all 0.2s ease-in-out;
        box-shadow: 0 4px 10px rgba(0,0,0,0.1);
    }

    .tutorial-next-btn:hover {
        transform: translateY(-2px) scale(1.02);
        box-shadow: 0 6px 15px rgba(0,0,0,0.15);
    }
    .tutorial-prev-btn:hover {
        transform: translateY(-2px) scale(1.02);
        box-shadow: 0 6px 15px rgba(0,0,0,0.15);
    }
    .tutorial-end-btn:hover {
        transform: translateY(-2px) scale(1.02);
        box-shadow: 0 6px 15px rgba(0,0,0,0.15);
    }

    /* Make buttons visually disabled when hidden/inactive, and prevent clicks */
    .opacity-0.pointer-events-none {
        cursor: not-allowed;
    }
</style>