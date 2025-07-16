<?php
// admin/index.php - Admin Dashboard Main Page

// Start session and include necessary files
session_start();
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/session.php'; // For is_logged_in() and has_role()
require_once __DIR__ . '/../includes/functions.php'; // For getSystemSetting()

// Redirect if not logged in or not an admin
require_login('admin', '/admin/login.php'); // Redirect to admin login if not admin

// Fetch company name for dynamic display in header/sidebar
$companyName = getSystemSetting('company_name');
if (!$companyName) {
    $companyName = 'Catdump'; // Fallback if not set in DB
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - <?php echo htmlspecialchars($companyName); ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f0f4f8;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        #dashboard-wrapper {
            display: flex;
            flex-grow: 1;
        }
        #main-content-area {
            flex-grow: 1;
            padding: 1.5rem;
            background-color: #f0f4f8;
        }
        .nav-link-desktop.active {
            background-color: #1a73e8; /* Active background for desktop */
            color: white;
        }
        .nav-link-mobile.active {
            background-color: #1a73e8; /* Active background for mobile */
            color: white;
        }
        .custom-scroll::-webkit-scrollbar {
            width: 8px;
        }
        .custom-scroll::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 10px;
        }
        .custom-scroll::-webkit-scrollbar-thumb {
            background: #888;
            border-radius: 10px;
        }
        .custom-scroll::-webkit-scrollbar-thumb:hover {
            background: #555;
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
            transition: all 0.3s ease-out; /* Updated transition property */
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

    </style>
</head>
<body class="antialiased">
    <?php include __DIR__ . '/includes/header.php'; // Corrected path ?>

    <div id="dashboard-wrapper">
        <?php include __DIR__ . '/includes/sidebar.php'; // Corrected path ?>

        <main id="main-content-area" class="p-6 md:p-8">
            <div class="flex items-center justify-center h-full min-h-[300px] text-gray-500 text-lg">
                <i class="fas fa-spinner fa-spin mr-3 text-blue-500 text-3xl"></i> Loading Dashboard...
            </div>
        </main>
    </div>

    <?php include __DIR__ . '/includes/footer.php'; // Corrected path ?>

    <script>
        // --- Global Helper Functions (Toast, Modals) ---
        window.showToast = function(message, type = 'info') {
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
            void toast.offsetWidth; // Trigger reflow
            toast.classList.add('show');
            setTimeout(() => {
                toast.classList.remove('show');
                toast.addEventListener('transitionend', () => toast.remove());
            }, 3000);
        };

        window.showModal = function(modalId) {
            const modal = document.getElementById(modalId);
            if(modal) modal.classList.remove('hidden');
        };

        window.hideModal = function(modalId) {
            const modal = document.getElementById(modalId);
            if(modal) modal.classList.add('hidden');
        };
        
        let confirmationCallback = null;
        window.showConfirmationModal = function(title, message, callback, confirmBtnText = 'Confirm', confirmBtnColor = 'bg-red-600') {
            document.getElementById('admin-confirmation-modal-title').textContent = title;
            document.getElementById('admin-confirmation-modal-message').textContent = message;
            const confirmBtn = document.getElementById('admin-confirmation-modal-confirm');
            confirmBtn.textContent = confirmBtnText;
            confirmBtn.className = `px-4 py-2 text-white rounded-lg hover:opacity-90 ${confirmBtnColor}`; // Directly set class
            confirmationCallback = callback;
            showModal('admin-confirmation-modal');
        };

        document.getElementById('admin-confirmation-modal-confirm').addEventListener('click', () => {
            hideModal('admin-confirmation-modal');
            if (confirmationCallback) confirmationCallback(true);
            confirmationCallback = null;
        });

        document.getElementById('admin-confirmation-modal-cancel').addEventListener('click', () => {
            hideModal('admin-confirmation-modal');
            if (confirmationCallback) confirmationCallback(false);
            confirmationCallback = null;
        });

        // --- Core Navigation and Content Loading Logic ---
        window.loadAdminSection = async function(sectionId, params = {}) {
            const mainContentArea = document.getElementById('main-content-area');
            const navLinks = document.querySelectorAll('.nav-link-desktop, .nav-link-mobile');
            
            if (sectionId === 'logout') {
                showModal('admin-logout-modal');
                return;
            }

            let url = `/admin/pages/${sectionId}.php`;
            const queryString = new URLSearchParams(params).toString();
            if (queryString) url += '?' + queryString;

            try {
                mainContentArea.innerHTML = `
                    <div class="flex items-center justify-center h-full min-h-[300px] text-gray-500 text-lg">
                        <i class="fas fa-spinner fa-spin mr-3 text-blue-500 text-3xl"></i> Loading ${sectionId.replace('-', ' ')}...
                    </div>
                `;
                const response = await fetch(url);
                if (!response.ok) throw new Error(`Failed to load content: ${response.statusText}`);
                mainContentArea.innerHTML = await response.text();

                navLinks.forEach(link => link.classList.toggle('active', link.dataset.section === sectionId));
                history.pushState({ section: sectionId, params }, '', `#${sectionId}${queryString ? '?' + queryString : ''}`);
                
                // Re-run scripts in the loaded content
                mainContentArea.querySelectorAll('script').forEach(oldScript => {
                    const newScript = document.createElement('script');
                    newScript.textContent = oldScript.textContent;
                    // Append and immediately remove to re-execute
                    document.body.appendChild(newScript).parentNode.removeChild(newScript);
                });

                // Update notification bell after content load
                if (window.updateAdminNotificationBell) {
                    window.updateAdminNotificationBell();
                }

            } catch (error) {
                console.error('Error loading admin section:', error);
                mainContentArea.innerHTML = `<div class="text-red-500 p-8 text-center">Failed to load content. Please try again.</div>`;
                showToast(`Failed to load ${sectionId.replace('-', ' ')}`, 'error');
            }
        };

        // --- GLOBAL EVENT DELEGATION ---
        document.addEventListener('click', function(event) {
            const target = event.target;
            const action = target.dataset.action;

            if (action) {
                if (window.adminPageActions && typeof window.adminPageActions[action] === 'function') {
                    window.adminPageActions[action](target);
                }
            }
        });

        document.addEventListener('DOMContentLoaded', () => {
            // Initial page load logic
            const hash = window.location.hash.substring(1).split('?')[0];
            const searchParams = new URLSearchParams(window.location.hash.split('?')[1] || '');
            const params = Object.fromEntries(searchParams.entries());
            const initialSection = hash || 'dashboard';
            window.loadAdminSection(initialSection, params);

            // Sidebar/Nav link listeners
            document.querySelectorAll('.nav-link-desktop, .nav-link-mobile').forEach(link => {
                link.addEventListener('click', function(event) {
                    event.preventDefault();
                    window.loadAdminSection(this.dataset.section);
                });
            });
        });

        window.addEventListener('popstate', (event) => {
            const section = event.state?.section || 'dashboard';
            const params = event.state?.params || {};
            window.loadAdminSection(section, params);
        });
    </script>
</body>
</html>
