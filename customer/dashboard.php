<?php
// customer/dashboard.php - Main Customer Dashboard Page

// Include essential files for session, database, and common functions
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

// Ensure user is logged in. If not, redirect to the login page.
// This is the key change to prevent redirection to unauthorized.php
if (!is_logged_in()) {
    redirect('/customer/login.php');
}

// Fetch company name for display in various parts
$companyName = getSystemSetting('company_name');
if (!$companyName) {
    $companyName = 'Catdump'; // Fallback if not set in DB
}

// User data from session (these are used in header.php and footer.php)
$user_id = $_SESSION['user_id'];
$user_first_name = $_SESSION['user_first_name'];
$user_last_name = $_SESSION['user_last_name'];
$user_email = $_SESSION['user_email'];

// Initialize variables for user data and statistics
$user_full_name = 'N/A';
$user_username = 'N/A'; // Assuming username might be email or a separate field
$user_email = 'N/A';
$user_phone = 'N/A';
$user_address = 'N/A';
$user_city = 'N/A';
$user_state = 'N/A';
$user_zip = 'N/A';

$active_bookings_count = 0;
$pending_quotes_count = 0;
$total_invoices_count = 0;
$paid_invoices_count = 0;
$unpaid_invoices_count = 0;
$partially_paid_invoices_count = 0;
$pending_quotes_for_animation = []; // To hold quotes that are pending for price


// Fetch User Account Information
$stmt_user = $conn->prepare("SELECT first_name, last_name, email, phone_number, address, city, state, zip_code FROM users WHERE id = ?");
$stmt_user->bind_param("i", $user_id);
$stmt_user->execute();
$result_user = $stmt_user->get_result();
if ($user_data = $result_user->fetch_assoc()) {
    $user_full_name = htmlspecialchars(($user_data['first_name'] ?? '') . ' ' . ($user_data['last_name'] ?? ''));
    $user_username = htmlspecialchars($user_data['email'] ?? '');
    $user_email = htmlspecialchars($user_data['email'] ?? '');
    $user_phone = htmlspecialchars($user_data['phone_number'] ?? '');
    $user_address = htmlspecialchars($user_data['address'] ?? '');
    $user_city = htmlspecialchars($user_data['city'] ?? '');
    $user_state = htmlspecialchars($user_data['state'] ?? '');
    $user_zip = htmlspecialchars($user_data['zip_code'] ?? '');
} else {
    // This case should ideally not be hit if user is logged in
    $user_full_name = 'N/A';
    $user_username = 'N/A';
    $user_email = 'N/A';
    $user_phone = 'N/A';
    $user_address = 'N/A';
    $user_city = 'N/A';
    $user_state = 'N/A';
    $user_zip = 'N/A';
}
$stmt_user->close();

// Fetch Quick Statistics: Bookings and Quotes
// Active Bookings (assuming 'delivered', 'in_use', 'awaiting_pickup' as active for customer view)
$stmt_active_bookings = $conn->prepare("SELECT COUNT(*) AS count FROM bookings WHERE user_id = ? AND status IN ('delivered', 'in_use', 'awaiting_pickup')");
$stmt_active_bookings->bind_param("i", $user_id);
$stmt_active_bookings->execute();
$result_active_bookings = $stmt_active_bookings->get_result();
$active_bookings_count = $result_active_bookings->fetch_assoc()['count'];
$stmt_active_bookings->close();

// Pending Quotes (quotes with status 'pending' where admin hasn't added a price yet)
$stmt_pending_quotes = $conn->prepare("SELECT id, service_type, created_at, quote_details, location FROM quotes WHERE user_id = ? AND status = 'pending'");
$stmt_pending_quotes->bind_param("i", $user_id);
$stmt_pending_quotes->execute();
$result_pending_quotes = $stmt_pending_quotes->get_result();
$pending_quotes_count = $result_pending_quotes->num_rows;

// Store pending quotes for display on dashboard
while ($row = $result_pending_quotes->fetch_assoc()) {
    // Add null coalescing for json_decode to prevent deprecation warnings
    $quote_details = json_decode($row['quote_details'] ?? '{}', true);
    $item_desc = 'N/A';
    if ($row['service_type'] == 'equipment_rental' && isset($quote_details['equipment_types'])) {
        $item_desc = implode(', ', $quote_details['equipment_types']);
    } elseif ($row['service_type'] == 'junk_removal' && isset($quote_details['junkRemovalDetails']['junkItems'])) {
        $item_desc_array = [];
        foreach($quote_details['junkRemovalDetails']['junkItems'] as $item) {
            $item_desc_array[] = $item['itemType'] . (isset($item['quantity']) ? ' (Qty: ' . $item['quantity'] . ')' : '');
        }
        $item_desc = implode(', ', $item_desc_array);
    }

    $pending_quotes_for_animation[] = [
        'id' => $row['id'],
        'service_type' => htmlspecialchars(str_replace('_', ' ', $row['service_type'])),
        'created_at' => (new DateTime($row['created_at']))->format('M d, Y H:i A'),
        'item_description' => htmlspecialchars($item_desc),
        'location' => htmlspecialchars($row['location'] ?? 'N/A')
    ];
}
$stmt_pending_quotes->close();

// Fetch Invoice Statistics
$stmt_total_invoices = $conn->prepare("SELECT COUNT(*) AS count FROM invoices WHERE user_id = ?");
$stmt_total_invoices->bind_param("i", $user_id);
$stmt_total_invoices->execute();
$total_invoices_count = $stmt_total_invoices->get_result()->fetch_assoc()['count'];
$stmt_total_invoices->close();

$stmt_paid_invoices = $conn->prepare("SELECT COUNT(*) AS count FROM invoices WHERE user_id = ? AND status = 'paid'");
$stmt_paid_invoices->bind_param("i", $user_id);
$stmt_paid_invoices->execute();
$paid_invoices_count = $stmt_paid_invoices->get_result()->fetch_assoc()['count'];
$stmt_paid_invoices->close();

$stmt_unpaid_invoices = $conn->prepare("SELECT COUNT(*) AS count FROM invoices WHERE user_id = ? AND status = 'pending'");
$stmt_unpaid_invoices->bind_param("i", $user_id);
$stmt_unpaid_invoices->execute();
$unpaid_invoices_count = $stmt_unpaid_invoices->get_result()->fetch_assoc()['count'];
$stmt_unpaid_invoices->close();

$stmt_partially_paid_invoices = $conn->prepare("SELECT COUNT(*) AS count FROM invoices WHERE user_id = ? AND status = 'partially_paid'");
$stmt_partially_paid_invoices->bind_param("i", $user_id);
$stmt_partially_paid_invoices->execute();
$partially_paid_invoices_count = $stmt_partially_paid_invoices->get_result()->fetch_assoc()['count'];
$stmt_partially_paid_invoices->close();

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($companyName); ?> Customer Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/marked/marked.min.js"></script>
    <style>
    body {
        font-family: 'Inter', sans-serif;
        background-color: #f0f4f8;
        min-height: 100vh;
        display: flex;
        flex-direction: column;
        color: #2d3748;
        overflow-x: hidden; /* Prevent horizontal scrolling */
    }
    #dashboard-wrapper {
        display: flex;
        flex-grow: 1;
        width: 100%;
    }
    #content-area {
        flex-grow: 1;
        padding: 1.5rem;
        background-color: #f0f4f8;
        overflow-y: auto; /* Enable scrolling for content */
        display: flex; /* Changed to flex for centering modal */
        flex-direction: column; /* Stack content vertically */
    }
    /* Mobile fixed bottom nav padding for main content */
    @media (max-width: 767px) {
        body {
            padding-bottom: 64px; /* Space for the fixed bottom nav on mobile */
        }
    }
    /* Custom scrollbar for content area */
    .custom-scroll::-webkit-scrollbar {
        width: 8px;
    }
    .custom-scroll::-webkit-scrollbar-track {
        background: #c8d3f6; /* Lighter blue for track */
        border-radius: 10px;
    }
    .custom-scroll::-webkit-scrollbar-thumb {
        background: #8498f7; /* Medium blue for thumb */
        border-radius: 10px;
    }
    .custom-scroll::-webkit-scrollbar-thumb:hover {
        background: #6a7ecc; /* Slightly darker blue on hover */
    }

    /* Toast styles */
    #toast-container {
        position: fixed;
        bottom: 1rem;
        right: 1rem;
        z-index: 9999;
        display: flex;
        flex-direction: column-reverse; /* New toasts appear on top */
        gap: 0.5rem;
    }
    .toast {
        padding: 0.75rem 1.25rem;
        border-radius: 0.5rem;
        color: white;
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        opacity: 0;
        transform: translateY(100%);
        transition: opacity 0.3s ease-out, transform 0.3s ease-out;
        min-width: 250px;
        max-width: 350px;
    }
    .toast.show {
        opacity: 1;
        transform: translateY(0);
    }
    .toast.bg-success { background-color: #48bb78; } /* Green */
    .toast.bg-error { background-color: #ef4444; } /* Red */
    .toast.bg-info { background-color: #3b82f6; } /* Blue */
    .toast.bg-warning { background-color: #f59e0b; } /* Orange */

    /* AI Chat Modal Specific Styles */
    #ai-chat-modal {
        transition: all 0.3s ease-in-out; /* Smooth transition for the modal itself */
    }
    /* NEW: This wrapper helps manage sizing and positioning */
    #ai-chat-modal .modal-content {
    width: 100%;
    max-width: 500px;
    height: 70vh; /* Fixed height for desktop */
    max-height: 600px;
    display: flex; /* IMPORTANT: Enables flexbox layout */
    flex-direction: column; /* IMPORTANT: Stacks children vertically */
    transition: all 0.3s ease-in-out;
    background-color: #ffffff;
    border-radius: 1rem;
    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
}
    /* NEW: This ensures the message area scrolls instead of growing the modal */
    #ai-chat-messages {
        flex-grow: 1;
        overflow-y: auto;
    }

    /* Full screen on small devices */
    @media (max-width: 767px) {
        #ai-chat-modal .modal-content {
            width: 100vw;
            height: 100vh;
            margin: 0;
            border-radius: 0;
            max-width: none;
            max-height: none;
        }
    }
    #ai-chat-modal .chat-bubble {
        padding: 0.75rem 1.25rem;
        border-radius: 1.25rem;
        max-w-80%;
        line-height: 1.5;
        word-wrap: break-word;
        box-shadow: 0 2px 5px rgba(0,0,0,0.05);
    }

    #ai-chat-modal .ai-bubble {
        background-color: #e2e8f0; /* Light gray */
        color: #2d3748;
        border-bottom-left-radius: 0.25rem;
        align-self: flex-start;
    }

    #ai-chat-modal .user-bubble {
        background-color: #3b82f6; /* Blue */
        color: white;
        border-bottom-right-radius: 0.25rem;
        align-self: flex-end;
    }

    #ai-chat-modal .typing-indicator {
        display: flex;
        align-items: center;
        gap: 0.25rem;
        padding: 0.75rem 1.25rem;
        background-color: #e2e8f0;
        border-bottom-left-radius: 0.25rem;
        align-self: flex-start;
    }
    #ai-chat-modal .typing-indicator span {
        height: 8px;
        width: 8px;
        background-color: #a0aec0;
        border-radius: 50%;
        animation: bounce 1.4s infinite both;
    }
    #ai-chat-modal .typing-indicator span:nth-child(2) { animation-delay: 0.2s; }
    #ai-chat-modal .typing-indicator span:nth-child(3) { animation-delay: 0.4s; }

    @keyframes bounce {
        0%, 80%, 100% { transform: scale(0); }
        40% { transform: scale(1.0); }
    }
</style>
</head>
<body class="flex flex-col md:flex-row min-h-screen">

    <script>
        // --- Global Helper Functions (defined upfront to ensure availability) ---
        function showModal(modalId) {
            document.getElementById(modalId).classList.remove('hidden');
        }

        function hideModal(modalId) {
            document.getElementById(modalId).classList.add('hidden');
        }

        function showToast(message, type = 'info') {
            const toastContainer = document.getElementById('toast-container') || (() => {
                const div = document.createElement('div');
                div.id = 'toast-container';
                div.className = 'fixed bottom-4 right-4 z-50 space-y-2';
                document.body.appendChild(div);
                return div;
            })();

            const toast = document.createElement('div');
            let bgColorClass = 'bg-info';
            if (type === 'success') bgColorClass = 'bg-success';
            if (type === 'error') bgColorClass = 'bg-error';
            if (type === 'warning') bgColorClass = 'bg-warning';

            toast.className = `toast ${bgColorClass}`;
            toast.textContent = message;

            toastContainer.appendChild(toast);

            // Trigger reflow to enable transition
            void toast.offsetWidth;

            toast.classList.add('show');

            setTimeout(() => {
                toast.classList.remove('show');
                toast.addEventListener('transitionend', () => toast.remove());
            }, 3000);
        }

        // Custom Confirmation Modal Logic - Must be global
        let confirmationCallback = null;
        function showConfirmationModal(title, message, callback, confirmBtnText = 'Confirm', confirmBtnColor = 'bg-red-600') {
            document.getElementById('confirmation-modal-title').textContent = title;
            document.getElementById('confirmation-modal-message').textContent = message;
            const confirmBtn = document.getElementById('confirmation-modal-confirm');
            confirmBtn.textContent = confirmBtnText;
            confirmBtn.classList.remove('bg-red-600', 'bg-green-600', 'bg-blue-600', 'bg-orange-600', 'bg-indigo-600', 'bg-purple-600', 'bg-teal-600');
            confirmBtn.classList.add(confirmBtnColor);
            
            confirmationCallback = callback;
            showModal('confirmation-modal');
        }

        // --- Core Navigation and Content Loading Logic for Customer Dashboard ---
        // Defined here to ensure it's always globally available before any AJAX content is loaded.
        const contentArea = document.getElementById('content-area'); // Will be set after DOMContentLoaded
        const navLinksDesktop = document.querySelectorAll('.nav-link-desktop'); // Will be set after DOMContentLoaded
        const navLinksMobile = document.querySelectorAll('.nav-link-mobile'); // Will be set after DOMContentLoaded

        window.loadCustomerSection = async function(sectionId, params = {}) {
        const currentContentArea = document.getElementById('content-area');
        const currentNavLinksDesktop = document.querySelectorAll('.nav-link-desktop');
        const currentNavLinksMobile = document.querySelectorAll('.nav-link-mobile');

        let url = `/customer/pages/${sectionId}.php`;
        let queryString = new URLSearchParams(params).toString();
        if (queryString) {
            url += '?' + queryString;
        }

        // Handle special cases first
        if (sectionId === 'logout') {
            showModal('logout-modal');
            return;
        } else if (sectionId === 'delete-account') {
            showModal('delete-account-modal');
            return;
        } else if (sectionId === 'tutorial') { // NEW CONDITION ADDED HERE
            showModal('tutorial-overlay'); // Show the tutorial modal
            history.pushState({ section: sectionId, params: params }, '', `#${sectionId}`); // Update URL hash
            console.log('Tutorial modal opened!'); // For debugging
            return; // Exit the function to prevent loading a .php file
        }

        try {
            // Show a loading indicator in content area
            if (currentContentArea) {
                currentContentArea.innerHTML = `
                    <div class="flex items-center justify-center h-full min-h-[300px] text-gray-500 text-lg">
                        <i class="fas fa-spinner fa-spin mr-3 text-blue-500 text-2xl"></i> Loading ${sectionId.replace('-', ' ')}...
                    </div>
                `;
            }

            const response = await fetch(url);
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            const htmlContent = await response.text();
            if (currentContentArea) {
                currentContentArea.innerHTML = htmlContent;
            }

            // Update active class for desktop links
            currentNavLinksDesktop.forEach(link => link.classList.remove('bg-blue-700', 'text-white'));
            const activeLinkDesktop = document.querySelector(`.nav-link-desktop[data-section="${sectionId}"]`);
            if (activeLinkDesktop) {
                activeLinkDesktop.classList.add('bg-blue-700', 'text-white');
            }

            // Update active class for mobile links
            currentNavLinksMobile.forEach(link => link.classList.remove('bg-blue-700', 'text-white'));
            const activeLinkMobile = document.querySelector(`.nav-link-mobile[data-section="${sectionId}"]`);
            if (activeLinkMobile) {
                activeLinkMobile.classList.add('bg-blue-700', 'text-white');
            }

            // Push state to history for back/forward navigation
            history.pushState({ section: sectionId, params: params }, '', `#${sectionId}`);

            // Re-run scripts in the loaded content
            if (currentContentArea) {
                currentContentArea.querySelectorAll('script').forEach(oldScript => {
                    const newScript = document.createElement('script');
                    Array.from(oldScript.attributes).forEach(attr => newScript.setAttribute(attr.name, attr.value));
                    newScript.appendChild(document.createTextNode(oldScript.innerHTML));
                    oldScript.parentNode.replaceChild(newScript, oldScript);
                });
            }

            // Call updateNotificationBell after content is loaded and scripts re-run
            if (window.updateNotificationBell) {
                window.updateNotificationBell();
            }

        } catch (error) {
            console.error('Error loading customer section:', error);
            if (currentContentArea) {
                currentContentArea.innerHTML = `
                    <div class="flex flex-col items-center justify-center h-full min-h-[300px] text-red-500 text-lg">
                        <i class="fas fa-exclamation-triangle mr-3 text-red-600 text-2xl"></i>
                        Failed to load section: ${sectionId.replace('-', ' ')}. Please try again.
                        <p class="text-sm text-gray-500 mt-2">Details: ${error.message}</p>
                    </div>
                `;
            }
            showToast(`Failed to load ${sectionId.replace('-', ' ')}`, 'error');
        }
    };

        // --- AI Chat Logic for Service Request Button (Made Global) ---
        window.showAIChat = async function(serviceType) {
            // Get references to elements *inside* the function, ensuring they are present in the DOM.
            const aiChatModal = document.getElementById('ai-chat-modal');
            const aiChatTitle = document.getElementById('ai-chat-title');
            const aiChatMessagesDiv = document.getElementById('ai-chat-messages');
            const aiChatInput = document.getElementById('ai-chat-input');
            const aiChatSendBtn = document.getElementById('ai-chat-send-btn');
            const aiChatFileInput = document.getElementById('ai-chat-file-input');
            const aiChatSelectedFilesDisplay = document.getElementById('ai-chat-selected-files');
            const aiChatCameraBtn = document.getElementById('ai-chat-camera-btn');
            const fileUploadSection = document.getElementById('ai-chat-file-upload-section');


            if (!aiChatModal || !aiChatTitle || !aiChatMessagesDiv || !aiChatInput || !aiChatSendBtn || !aiChatCameraBtn || !fileUploadSection) {
                console.error('AI Chat Modal elements not found in DOM.');
                window.showToast('AI chat components not loaded. Please try refreshing the page.', 'error');
                return;
            }


            aiChatMessagesDiv.innerHTML = ''; // Clear previous messages
            aiChatInput.value = '';
            aiChatInput.disabled = false;
            aiChatSendBtn.disabled = false;
            if (aiChatFileInput) aiChatFileInput.value = null; // Clear selected files
            if (aiChatSelectedFilesDisplay) aiChatSelectedFilesDisplay.textContent = ''; // Clear selected files display


            // Enable/disable file input based on service type
            if (serviceType === 'junk-removal-service') {
                fileUploadSection.classList.remove('hidden'); // Show for junk removal
                aiChatCameraBtn.classList.remove('hidden');
                if (aiChatFileInput) aiChatFileInput.setAttribute('accept', 'image/*,video/*'); // Accept images and videos
            } else {
                fileUploadSection.classList.add('hidden'); // Hide for other services
                aiChatCameraBtn.classList.add('hidden');
                if (aiChatFileInput) aiChatFileInput.removeAttribute('accept');
            }


            let initialAIMessage = "";
            if (serviceType === 'create-booking') {
                aiChatTitle.textContent = 'AI Assistant - Equipment Rental';
                initialAIMessage = "Hello! I can help you create a new equipment booking. Are you looking for a dumpster, temporary toilet, storage container, or handwash station? Is this for residential or commercial use?";
            } else if (serviceType === 'junk-removal-service') {
                aiChatTitle.textContent = 'AI Assistant - Junk Removal';
                initialAIMessage = "Hello! I can help you with junk removal. Please describe the items you need removed, or even better, upload some images or a short video!";
            }
            addAIChatMessage(initialAIMessage, 'ai', aiChatMessagesDiv);

            // Add event listener for AI chat modal's send button
            aiChatSendBtn.onclick = () => {
                const message = aiChatInput.value.trim();
                const files = aiChatFileInput ? aiChatFileInput.files : []; // Get selected files

                if (message || files.length > 0) {
                    addAIChatMessage(message, 'user');
                    aiChatInput.value = '';
                    if (aiChatFileInput) aiChatFileInput.value = null; // Clear selected files after sending
                    if (aiChatSelectedFilesDisplay) aiChatSelectedFilesDisplay.textContent = ''; // Clear selected files display
                    sendAIChatMessageToApi(message, serviceType, files);
                }
            };
            aiChatInput.onkeydown = (event) => {
                if (event.key === 'Enter' && !event.shiftKey) {
                    event.preventDefault();
                    aiChatSendBtn.click();
                }
            };

            // Event listener for file input change
            if (aiChatFileInput) {
                aiChatFileInput.onchange = async () => {
                    if (aiChatFileInput.files.length > 0) {
                        const files = Array.from(aiChatFileInput.files);
                        let fileNames = files.map(f => f.name).join(', ');
                        if (aiChatSelectedFilesDisplay) aiChatSelectedFilesDisplay.textContent = `Selected: ${fileNames}`;

                        // --- Video Frame Extraction Logic ---
                        const processedFiles = [];
                        for (const file of files) {
                            if (file.type.startsWith('video/')) {
                                showToast(`Processing video: ${file.name}...`, 'info');
                                try {
                                    const frames = await extractFramesFromVideo(file, 10); // Extracts 10 frames
                                    frames.forEach((frame, index) => {
                                        // Convert data URL to Blob/File object to send via FormData
                                        const blob = dataURLtoBlob(frame);
                                        processedFiles.push(new File([blob], `frame_${file.name}_${index}.jpeg`, { type: 'image/jpeg' }));
                                    });
                                    showToast(`Extracted ${frames.length} frames from video.`, 'success');
                                } catch (error) {
                                    console.error('Error extracting frames:', error);
                                    showToast(`Failed to extract frames from ${file.name}.`, 'error');
                                    processedFiles.push(file); // Send original video if frame extraction fails
                                }
                            } else {
                                processedFiles.push(file); // For images, just push the original file
                            }
                        }
                        // Replace original files with processed ones (frames for video)
                        // This requires re-assigning to a new FileList or handling directly in send function
                        // For now, I'll update the sendAIChatMessageToApi call to use `processedFiles`
                        // and ensure `aiChatFileInput.files` is only used for display.
                        aiChatFileInput.processedFiles = processedFiles; // Attach to input element for later retrieval
                    } else {
                        if (aiChatSelectedFilesDisplay) aiChatSelectedFilesDisplay.textContent = '';
                        if (aiChatFileInput) aiChatFileInput.processedFiles = [];
                    }
                };
            }
            showModal('ai-chat-modal');
        };

        function addAIChatMessage(message, sender, ) {
    const aiChatMessagesDiv = document.getElementById('ai-chat-messages');
    const messageDiv = document.createElement('div');
    messageDiv.classList.add('chat-bubble', sender === 'user' ? 'user-bubble' : 'ai-bubble');
    // Use innerHTML and marked.parse() for rendering markdown
    messageDiv.innerHTML = `<span class="font-semibold ${sender === 'user' ? 'text-green-200' : 'text-blue-800'}">${sender === 'user' ? 'You' : 'AI'}:</span> ${marked.parse(message)}`;
    aiChatMessagesDiv.appendChild(messageDiv);
    aiChatMessagesDiv.scrollTop = aiChatMessagesDiv.scrollHeight;
}

        // --- Video Frame Extraction Function ---
        function extractFramesFromVideo(videoFile, numFrames) {
            return new Promise((resolve, reject) => {
                const video = document.getElementById('hiddenVideo'); // Use the hidden video element
                const canvas = document.getElementById('hiddenCanvas'); // Use the hidden canvas element
                const context = canvas.getContext('2d');
                const frames = [];

                video.preload = 'metadata';
                video.muted = true; // Mute video during processing
                video.src = URL.createObjectURL(videoFile);

                video.onloadedmetadata = () => {
                    canvas.width = video.videoWidth;
                    canvas.height = video.videoHeight;

                    const duration = video.duration;
                    const interval = duration / (numFrames + 1); // Get frames evenly spaced

                    let framesExtracted = 0;
                    const captureFrame = () => {
                        if (framesExtracted < numFrames) {
                            context.drawImage(video, 0, 0, canvas.width, canvas.height);
                            frames.push(canvas.toDataURL('image/jpeg')); // JPEG format
                            framesExtracted++;
                            video.currentTime += interval; // Move to next frame time
                        } else {
                            URL.revokeObjectURL(video.src); // Clean up
                            resolve(frames);
                        }
                    };

                    video.onseeked = captureFrame; // Capture frame after seeking
                    video.onerror = (e) => reject(new Error('Error seeking video: ' + e.message));

                    // Start seeking to capture frames
                    video.currentTime = interval;
                };

                video.onerror = (e) => reject(new Error('Error loading video: ' + e.message));
            });
        }

        // Helper function to convert Data URL to Blob (for FormData)
        function dataURLtoBlob(dataurl) {
            const arr = dataurl.split(',');
            const mime = arr[0].match(/:(.*?);/)[1];
            const bstr = atob(arr[1]);
            let n = bstr.length;
            const u8arr = new Uint8Array(n);
            while (n--) {
                u8arr[n] = bstr.charCodeAt(n);
            }
            return new Blob([u8arr], { type: mime });
        }


        async function sendAIChatMessageToApi(message, serviceType, files = []) {
            const aiChatInput = document.getElementById('ai-chat-input');
            const aiChatSendBtn = document.getElementById('ai-chat-send-btn');
            const aiChatMessagesDiv = document.getElementById('ai-chat-messages');
            const aiChatFileInput = document.getElementById('ai-chat-file-input');

            // Show loading dots
            const loadingDiv = document.createElement('div');
            loadingDiv.classList.add('typing-indicator'); // Use typing indicator for loading
            loadingDiv.innerHTML = '<span></span><span></span><span></span>';
            aiChatMessagesDiv.appendChild(loadingDiv);
            aiChatMessagesDiv.scrollTop = aiChatMessagesDiv.scrollHeight;
            aiChatInput.disabled = true;
            aiChatSendBtn.disabled = true;

            const formData = new FormData();
            formData.append('message', message);
            formData.append('initial_service_type', serviceType);

            // Use processed files from aiChatFileInput.processedFiles if available
            const filesToSend = aiChatFileInput && aiChatFileInput.processedFiles ? aiChatFileInput.processedFiles : files;

            // Append files to FormData
            if (filesToSend.length > 0) {
                for (let i = 0; i < filesToSend.length; i++) {
                    formData.append('media_files[]', filesToSend[i]);
                }
            }

            try {
                const response = await fetch('/api/openai_chat.php', {
                    method: 'POST',
                    body: formData
                });
                const data = await response.json();

                // Remove loading dots
                aiChatMessagesDiv.removeChild(loadingDiv); 

                addAIChatMessage(data.ai_response, 'ai');

                if (data.is_info_collected) {
                    hideModal('ai-chat-modal'); // Hide the chat modal
                    showToast("Redirecting to your quote preview...", 'success');
                    
                    // Redirect to the junk removal page with the new quote ID
                    const urlParams = new URLSearchParams(new URL(data.redirect_url, window.location.origin).search);
                    const quoteId = urlParams.get('quote_id');
                    
                    setTimeout(() => {
                        window.loadCustomerSection('junk-removal', { quote_id: quoteId });
                    }, 1500); // Delay for toast visibility
                    
                } else {
                    aiChatInput.disabled = false;
                    aiChatSendBtn.disabled = false;
                    aiChatInput.focus();
                }

            } catch (error) {
                console.error('Error in AI chat:', error);
                aiChatMessagesDiv.removeChild(loadingDiv);
                addAIChatMessage('Sorry, there was an error processing your request. Please try again.', 'ai');
                aiChatInput.disabled = false;
                aiChatSendBtn.disabled = false;
            }
        }

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


    <?php include __DIR__ . '/includes/sidebar.php'; // Includes the sidebar navigation ?>

    <div class="flex-1 flex flex-col">
        <?php include __DIR__ . '/includes/header.php'; // Includes the main content top bar ?>

        <main id="content-area" class="flex-1 p-8 overflow-y-auto custom-scroll">
            <div class="flex items-center justify-center h-full min-h-[300px] text-gray-500 text-lg">
                <i class="fas fa-spinner fa-spin mr-3 text-blue-500 text-2xl"></i> Loading Dashboard...
            </div>
        </main>
    </div>

    <div id="toast-container"></div>

    <?php include __DIR__ . '/includes/footer.php'; // Includes modals and global JS functions ?>
    
    <video id="hiddenVideo" style="display:none;" controls></video>
    <canvas id="hiddenCanvas" style="display:none;"></canvas>



    <script>
        // --- Event Listeners and Initial Load Logic for Customer Dashboard ---
        // This script block runs AFTER all HTML content and PHP includes are processed.

        // Add event listeners for confirmation modal buttons (if footer loaded them as hidden)
        document.addEventListener('DOMContentLoaded', () => {
            const confirmBtn = document.getElementById('confirmation-modal-confirm');
            const cancelBtn = document.getElementById('confirmation-modal-cancel');
            if (confirmBtn && !confirmBtn.dataset.listenerAdded) { // Prevent adding multiple listeners
                confirmBtn.addEventListener('click', () => {
                    hideModal('confirmation-modal');
                    if (confirmationCallback) {
                        confirmationCallback(true);
                    }
                    confirmationCallback = null;
                });
                confirmBtn.dataset.listenerAdded = 'true';
            }
            if (cancelBtn && !cancelBtn.dataset.listenerAdded) {
                cancelBtn.addEventListener('click', () => {
                    hideModal('confirmation-modal');
                    if (confirmationCallback) {
                        confirmationCallback(false);
                    }
                    confirmationCallback = null;
                });
                cancelBtn.dataset.listenerAdded = 'true';
            }
        });


        // Add event listeners to navigation links (desktop and mobile)
        document.addEventListener('DOMContentLoaded', () => {
            document.querySelectorAll('.nav-link-desktop, .nav-link-mobile').forEach(link => {
                link.addEventListener('click', function(event) {
                    event.preventDefault(); // Prevent default link behavior
                    const section = this.dataset.section;
                    window.loadCustomerSection(section);
                });
            });
        });


        // Handle browser back/forward buttons
        window.addEventListener('popstate', (event) => {
            if (event.state && event.state.section) {
                window.loadCustomerSection(event.state.section, event.state.params);
            } else {
                window.loadCustomerSection('dashboard'); // Default to dashboard if no state
            }
        });

        // Initial page load based on URL hash or default to dashboard
        document.addEventListener('DOMContentLoaded', () => {
            const initialHash = window.location.hash.substring(1);
            if (initialHash) {
                const urlParams = new URLSearchParams(window.location.search);
                const params = Object.fromEntries(urlParams.entries()); // Convert URLSearchParams to object
                window.loadCustomerSection(initialHash, params);
            } else {
                window.loadCustomerSection('dashboard'); // Default page
            }
            
            // Show welcome prompt on first visit (using sessionStorage)
            if (!sessionStorage.getItem('welcomeShown')) {
                const userName = "<?php echo $_SESSION['user_first_name'] ?? 'Customer'; ?>";
                showToast(`Welcome back, ${userName}! Explore your dashboard.`, 'info');
                sessionStorage.setItem('welcomeShown', 'true');
            }
        });

        // --- Service Request Dropdown Logic (from header.php) ---
        document.addEventListener('DOMContentLoaded', function() {
            const serviceRequestBtn = document.getElementById('service-request-btn');
            const serviceRequestDropdown = document.getElementById('service-request-dropdown');

            if(serviceRequestBtn) {
                serviceRequestBtn.addEventListener('click', function() {
                    serviceRequestDropdown.classList.toggle('hidden');
                });

                document.addEventListener('click', function(event) {
                    if (!serviceRequestBtn.contains(event.target) && !serviceRequestDropdown.contains(event.target)) {
                        serviceRequestDropdown.classList.add('hidden');
                    }
                });
            }
        });

        // --- Tutorial Logic (from footer.php) ---
        // These global functions are already defined above, but the logic to trigger them needs to be re-attached or exist.
        // Assuming tutorialSteps, tutorialOverlay, etc. are global variables or fetched within the footer script.
        // The event listeners for tutorial buttons will be handled by the script re-execution in footer.php when it's loaded.
        // Ensure that the 'start-tutorial-btn' has an onclick that calls window.startTutorial()
        // Or re-attach the event listener here.

        document.addEventListener('DOMContentLoaded', () => {
            const startTutorialBtn = document.getElementById('start-tutorial-btn');
            if (startTutorialBtn && !startTutorialBtn.dataset.listenerAdded) {
                startTutorialBtn.addEventListener('click', window.startTutorial);
                startTutorialBtn.dataset.listenerAdded = 'true';
            }
        });
        
        
        // --- Event Delegation for Dynamically Loaded Content ---
        // Attaches a single listener to the static content area
        document.addEventListener('DOMContentLoaded', () => { // Ensure this part of the script runs once after the main DOM is ready
            const contentAreaElement = document.getElementById('content-area'); // Get the main content area element

            if (contentAreaElement) {
                contentAreaElement.addEventListener('click', function(event) {
                    // Handle "View Invoice Details" button click (from list)
                    if (event.target.closest('.view-invoice-details')) {
                        const button = event.target.closest('.view-invoice-details');
                        if (typeof window.showInvoiceDetails === 'function') {
                            window.showInvoiceDetails(button.dataset.invoiceId);
                        }
                    }
                    // Handle "Pay Now" button click (from list or detail page)
                    else if (event.target.closest('.show-payment-form-btn')) { // This class is on both list and detail page buttons
                        const button = event.target.closest('.show-payment-form-btn');
                        if (typeof window.showPaymentForm === 'function') {
                            window.showPaymentForm(button.dataset.invoiceId, button.dataset.amount);
                        }
                    }
                    // Handle "Back to Invoice Details" button click (from payment form)
                    else if (event.target.closest('#payment-form-view button.bg-gray-200')) { // Targeting the back button specifically
                        // Assuming this button's onclick is now removed, or we just need its class/id
                        // It currently has onclick="hidePaymentForm()" which should now be handled by delegation
                        // Make sure its onclick is removed in invoices.php (if it isn't already handled by delegation below)
                        // For consistency, let's keep it handled by delegation.
                        if (typeof window.hidePaymentForm === 'function') {
                            window.hidePaymentForm();
                        }
                    }
                });
            }

            // Also, update direct onclicks if they still exist for "Back to Invoice Details" or "Back to Invoices" from payment form
            // Ensure these use the global functions directly or are handled by delegation
            // For example: <button onclick="window.hidePaymentForm()">
            // Needs to be: <button class="mb-4 px-4 py-2 bg-gray-200 ... " onclick="window.hideInvoiceDetails()">

            // Similarly for the payment form's back button:
            // <button class="mb-4 px-4 py-2 bg-gray-200 ... " onclick="window.hidePaymentForm()">

            // Let's update invoices.php one last time to fix these onclicks to use window.
            // Or, better, use data attributes and delegate. For now, let's fix the specific onclicks directly.
        });
    </script>
</body>
</html>