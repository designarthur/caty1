<?php
// includes/public_footer.php
// This file holds modals and all shared JavaScript for the public-facing pages.

// Ensure functions are available for getSystemSetting
require_once __DIR__ . '/functions.php';

// Fetch company name from system settings
$companyName = getSystemSetting('company_name');
if (!$companyName) {
    $companyName = 'Catdump'; // Fallback if not set in DB
}

// Fetch admin email from system settings for support email
$supportEmail = getSystemSetting('admin_email');
if (!$supportEmail) {
    $supportEmail = 'info@' . strtolower(str_replace(' ', '', $companyName)) . '.com'; // Dynamic fallback
}

// Placeholder for a phone number as it's not in system settings currently.
$supportPhone = '+1 (555) 123-4567';

?>
    <footer class="bg-gray-900 text-gray-300 py-12">
        <div class="container-box">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-8">
                <div class="col-span-1 md:col-span-2 lg:col-span-2 flex flex-col items-center md:items-start text-center md:text-left">
                    <div class="flex items-center mb-5">
                        <img src="/assets/images/logo.png" alt="<?php echo htmlspecialchars($companyName); ?> Logo" class="h-16 w-16 mr-4 rounded-full shadow-md">
                        <div class="text-5xl font-extrabold text-blue-custom"><?php echo htmlspecialchars($companyName); ?></div>
                    </div>
                    <p class="leading-relaxed text-gray-400 mb-4">Your premier marketplace for fast, easy, and affordable equipment rentals. We connect you with the best local deals for dumpsters, temporary toilets, storage, and heavy machinery, ensuring your projects run smoothly and efficiently.</p>
                    <div class="flex space-x-4 mt-4">
                        <a href="https://facebook.com" target="_blank" aria-label="Facebook" class="text-gray-400 hover:text-white transition duration-200"><i class="fab fa-facebook-f text-2xl"></i></a>
                        <a href="https://twitter.com" target="_blank" aria-label="Twitter" class="text-gray-400 hover:text-white transition duration-200"><i class="fab fa-twitter text-2xl"></i></a>
                        <a href="https://linkedin.com" target="_blank" aria-label="LinkedIn" class="text-gray-400 hover:text-white transition duration-200"><i class="fab fa-linkedin-in text-2xl"></i></a>
                        <a href="https://instagram.com" target="_blank" aria-label="Instagram" class="text-gray-400 hover:text-white transition duration-200"><i class="fab fa-instagram text-2xl"></i></a>
                    </div>
                </div>

                <div class="col-span-1">
                    <h3 class="text-xl font-bold text-white mb-6">Quick Links</h3>
                    <ul class="space-y-4">
                        <li><a href="/index.php" class="text-gray-400 hover:text-blue-custom transition duration-200">Home</a></li>
                        <li><a href="/How-it-works.php" class="text-gray-400 hover:text-blue-custom transition duration-200">How It Works</a></li>
                        <li><a href="/Services/Dumpster-Rentals.php" class="text-gray-400 hover:text-blue-custom transition duration-200">Equipment Rentals</a></li>
                        <li><a href="/Resources/Blog.php" class="text-gray-400 hover:text-blue-custom transition duration-200">Blog/News</a></li>
                        <li><a href="/Resources/Customer-Resources.php" class="text-gray-400 hover:text-blue-custom transition duration-200">Customer Resources</a></li>
                        <li><a href="/Resources/Pricing-Finance.php" class="text-gray-400 hover:text-blue-custom transition duration-200">Pricing & Finance</a></li>
                    </ul>
                </div>

                <div class="col-span-1">
                    <h3 class="text-xl font-bold text-white mb-6">Company & Support</h3>
                    <ul class="space-y-4">
                        <li><a href="/Company/About-Us.php" class="text-gray-400 hover:text-blue-custom transition duration-200">About Us</a></li>
                        <li><a href="/Company/Sustainability.php" class="text-gray-400 hover:text-blue-custom transition duration-200">Sustainability</a></li>
                        <li><a href="/Company/Testimonials.php" class="text-gray-400 hover:text-blue-custom transition duration-200">Testimonials</a></li>
                        <li><a href="/Resources/FAQs.php" class="text-gray-400 hover:text-blue-custom transition duration-200">FAQs</a></li>
                        <li><a href="/Resources/Support-Center.php" class="text-gray-400 hover:text-blue-custom transition duration-200">Support Center</a></li>
                        <li><a href="/Resources/Contact.php" class="text-gray-400 hover:text-blue-custom transition duration-200">Contact Us</a></li>
                    </ul>
                </div>

                <div class="col-span-1">
                    <h3 class="text-xl font-bold text-white mb-6">Stay Updated</h3>
                    <p class="text-gray-400 text-sm mb-4">Subscribe to our newsletter for the latest news, offers, and industry insights!</p>
                    <form id="newsletter-form" class="flex flex-col gap-3">
                        <input type="email" id="newsletter-email" name="email" placeholder="Your email address" aria-label="Email for newsletter" required class="p-3 rounded-lg border border-gray-700 bg-gray-800 text-white placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-blue-custom">
                        <button type="submit" class="btn-primary py-2.5 px-5 text-base shadow-md hover:shadow-lg transition duration-300">Subscribe</button>
                    </form>
                    <p class="text-gray-400 text-sm mt-6 mb-2"><i class="fas fa-map-marker-alt mr-2"></i> 123 Main St, Anytown, USA 12345</p>
                    <p class="text-gray-400 text-sm mb-2"><i class="fas fa-phone mr-2"></i> <?php echo htmlspecialchars($supportPhone); ?></p>
                    <p class="text-gray-400 text-sm mb-2"><i class="fas fa-envelope mr-2"></i> <?php echo htmlspecialchars($supportEmail); ?></p>
                </div>
            </div>
            <div class="border-t border-gray-700 mt-8 pt-8 text-center text-gray-500 text-sm">
                <p>&copy; <?php echo date("Y"); ?> <?php echo htmlspecialchars($companyName); ?>. All rights reserved.</p>
                <p class="mt-2">
                    <a href="/PrivacyPolicy.html" class="hover:text-blue-custom transition duration-200">Privacy Policy</a> |
                    <a href="/Terms and Conditions.html" class="hover:text-blue-custom transition duration-200">Terms & Conditions</a>
                </p>
            </div>
        </div>
    </footer>

    <div id="toast-container" class="fixed bottom-4 right-4 z-[10000] flex flex-col-reverse gap-2"></div>

    <script>
        // Global utility functions for modals and toasts
        function showModal(modalId) {
            const modal = document.getElementById(modalId);
            if (modal) {
                modal.classList.remove('hidden');
                modal.classList.add('flex'); // Ensure it's displayed as flex
                document.body.classList.add('overflow-hidden'); // Prevent scrolling body
            }
        }

        function hideModal(modalId) {
            const modal = document.getElementById(modalId);
            if (modal) {
                modal.classList.add('hidden');
                modal.classList.remove('flex');
                document.body.classList.remove('overflow-hidden'); // Re-enable scrolling
            }
        }

        function showToast(message, type = 'info') {
            const toastContainer = document.getElementById('toast-container') || (() => {
                const div = document.createElement('div');
                div.id = 'toast-container';
                div.className = 'fixed bottom-4 right-4 z-[10000] flex flex-col-reverse gap-2';
                document.body.appendChild(div);
                return div;
            })();

            const toast = document.createElement('div');
            let bgColorClass = 'bg-blue-500'; // Default info
            if (type === 'success') bgColorClass = 'bg-green-500';
            if (type === 'error') bgColorClass = 'bg-red-500';
            if (type === 'warning') bgColorClass = 'bg-orange-500';

            toast.className = `toast px-4 py-2 rounded-lg text-white shadow-lg opacity-0 transform translate-y-full transition-all duration-300 ${bgColorClass}`;
            toast.textContent = message;

            toastContainer.appendChild(toast);

            // Trigger reflow to enable transition
            void toast.offsetWidth;

            toast.classList.add('opacity-100', 'translate-y-0');

            setTimeout(() => {
                toast.classList.remove('opacity-100', 'translate-y-0');
                toast.classList.add('opacity-0', 'translate-y-full');
                toast.addEventListener('transitionend', () => toast.remove());
            }, 3000);
        }

        // Helper functions for video frame extraction (needed for junk removal chat)
        /**
         * Extracts a specified number of frames from a video file.
         * The frames are evenly spaced throughout the video's duration.
         *
         * @param {File} videoFile The video file to process.
         * @param {number} numFrames The number of frames to extract (default is 10).
         * @returns {Promise<string[]>} A promise that resolves with an array of base64-encoded image data URLs (JPEG format).
         */
        function extractFramesFromVideo(videoFile, numFrames = 10) {
            return new Promise((resolve, reject) => {
                const video = document.getElementById('hiddenVideo');
                const canvas = document.getElementById('hiddenCanvas');

                // Ensure the required hidden elements exist in the DOM
                if (!video || !canvas) {
                    return reject(new Error("Required hidden video/canvas elements are not found in the DOM."));
                }

                const context = canvas.getContext('2d');
                const frames = [];
                let framesExtracted = 0;

                video.preload = 'metadata';
                video.muted = true;
                video.src = URL.createObjectURL(videoFile);

                const captureFrame = () => {
                    // Stop if we have enough frames
                    if (framesExtracted >= numFrames) {
                        if (video.src) URL.revokeObjectURL(video.src); // Clean up blob URL
                        resolve(frames);
                        return;
                    }

                    try {
                        // Draw the current video frame to the canvas
                        context.drawImage(video, 0, 0, canvas.width, canvas.height);
                        // Convert canvas to a JPEG data URL and add to the array
                        frames.push(canvas.toDataURL('image/jpeg'));
                        framesExtracted++;

                        // If more frames are needed, seek to the next calculated position
                        if (framesExtracted < numFrames) {
                            const nextTime = (framesExtracted + 1) * (video.duration / (numFrames + 1));
                            video.currentTime = nextTime;
                        } else {
                            // All frames captured, resolve the promise
                            if (video.src) URL.revokeObjectURL(video.src);
                            resolve(frames);
                        }
                    } catch (e) {
                        reject(new Error("Error drawing video frame to canvas: " + e.message));
                    }
                };

                // This event fires when the video's metadata (like duration and dimensions) is loaded
                video.onloadeddata = () => {
                    canvas.width = video.videoWidth;
                    canvas.height = video.videoHeight;
                    // Seek to the first frame position to start the process
                    video.currentTime = (video.duration / (numFrames + 1));
                };

                // This event fires after the video has sought to a new position
                video.onseeked = captureFrame;

                // Handle any errors during video loading
                video.onerror = (e) => {
                    const error = e.target.error;
                    reject(new Error('Error loading video file: ' + (error ? error.message : 'Unknown error')));
                };
            });
        }

        /**
         * Converts a base64 data URL into a Blob object.
         * @param {string} dataurl The base64 data URL.
         * @returns {Blob} The resulting Blob object.
         */
        function dataURLtoBlob(dataurl) {
            const arr = dataurl.split(',');
            const mimeMatch = arr[0].match(/:(.*?);/);
            const mime = mimeMatch ? mimeMatch[1] : 'application/octet-stream';
            const bstr = atob(arr[1]);
            let n = bstr.length;
            const u8arr = new Uint8Array(n);
            while (n--) {
                u8arr[n] = bstr.charCodeAt(n);
            }
            return new Blob([u8arr], {
                type: mime
            });
        }


        // Accordion functionality for FAQs (if still used on public pages)
        document.querySelectorAll('[data-accordion-toggle]').forEach(header => {
            header.addEventListener('click', () => {
                const targetId = header.dataset.accordionToggle;
                const content = document.getElementById(targetId);
                if (content) {
                    const isOpen = content.classList.contains('open');
                    // Close all other open accordions in the same group
                    document.querySelectorAll('.accordion-content.open').forEach(openContent => {
                        if (openContent !== content) {
                            openContent.classList.remove('open');
                            openContent.style.maxHeight = null;
                            openContent.previousElementSibling.classList.remove('active');
                        }
                    });

                    if (isOpen) {
                        content.classList.remove('open');
                        content.style.maxHeight = null;
                        header.classList.remove('active');
                    } else {
                        content.classList.add('open');
                        content.style.maxHeight = content.scrollHeight + "px"; // Set actual height
                        header.classList.add('active');
                    }
                }
            });
        });

        // Newsletter Signup Form (Client-side only)
        const newsletterForm = document.getElementById('newsletter-form');
        if (newsletterForm) {
            newsletterForm.addEventListener('submit', function(event) {
                event.preventDefault(); // Prevent default form submission

                const emailInput = document.getElementById('newsletter-email');
                const email = emailInput.value.trim();

                if (!email) {
                    window.showToast('Please enter your email address.', 'error');
                    return;
                }
                if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
                    window.showToast('Please enter a valid email address.', 'error');
                    return;
                }

                // Simulate subscription process
                window.showToast('Subscribing you to our newsletter...', 'info');

                setTimeout(() => {
                    // In a real application, you would send this email to your backend
                    // for actual subscription (e.g., to Mailchimp, SendGrid, or a database).
                    // For this exercise, we just simulate success.
                    window.showToast('Thank you for subscribing! You\'ll receive our updates soon.', 'success');
                    emailInput.value = ''; // Clear the input field
                }, 1500); // Simulate network delay
            });
        }
    </script>
    <?php include __DIR__ . '/ai_chat_widget.php'; ?>
</body>
</html>
