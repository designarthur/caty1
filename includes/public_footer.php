<?php
// includes/public_footer.php
// This file holds modals and all shared JavaScript for the public-facing pages.
?>
    <footer class="bg-gray-800 text-white py-12">
        <div class="container-box">
            <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-4 gap-8">
                <div class="col-span-1">
                    <h3 class="text-xl font-bold mb-4"><?php echo htmlspecialchars($companyName); ?></h3>
                    <p class="text-gray-400 text-sm mb-4">Your trusted partner for efficient equipment rentals and waste management solutions. Powered by AI, designed for simplicity.</p>
                    <div class="flex space-x-4">
                        <a href="#" class="text-gray-400 hover:text-white"><i class="fab fa-facebook-f"></i></a>
                        <a href="#" class="text-gray-400 hover:text-white"><i class="fab fa-twitter"></i></a>
                        <a href="#" class="text-gray-400 hover:text-white"><i class="fab fa-linkedin-in"></i></a>
                        <a href="#" class="text-gray-400 hover:text-white"><i class="fab fa-instagram"></i></a>
                    </div>
                </div>

                <div class="col-span-1">
                    <h3 class="text-xl font-bold mb-4">Quick Links</h3>
                    <ul class="space-y-2">
                        <li><a href="/" class="text-gray-400 hover:text-white text-sm">Home</a></li>
                        <li><a href="/Services/Dumpster-Rentals.php" class="text-gray-400 hover:text-white text-sm">Dumpster Rentals</a></li>
                        <li><a href="/Services/Junk-Removal.php" class="text-gray-400 hover:text-white text-sm">Junk Removal</a></li>
                        <li><a href="/How-it-works.php" class="text-gray-400 hover:text-white text-sm">How It Works</a></li>
                        <li><a href="/Company/About-Us.php" class="text-gray-400 hover:text-white text-sm">About Us</a></li>
                        <li><a href="/Resources/Contact.php" class="text-gray-400 hover:text-white text-sm">Contact</a></li>
                    </ul>
                </div>

                <div class="col-span-1">
                    <h3 class="text-xl font-bold mb-4">Resources</h3>
                    <ul class="space-y-2">
                        <li><a href="/Resources/FAQs.php" class="text-gray-400 hover:text-white text-sm">FAQs</a></li>
                        <li><a href="/Resources/Blog.php" class="text-gray-400 hover:text-white text-sm">Blog</a></li>
                        <li><a href="/Resources/Customer-Resources.php" class="text-gray-400 hover:text-white text-sm">Customer Resources</a></li>
                        <li><a href="/Resources/Pricing-Finance.php" class="text-gray-400 hover:text-white text-sm">Pricing & Finance</a></li>
                        <li><a href="/PrivacyPolicy.html" class="text-gray-400 hover:text-white text-sm">Privacy Policy</a></li>
                        <li><a href="/Terms and Conditions.html" class="text-gray-400 hover:text-white text-sm">Terms & Conditions</a></li>
                    </ul>
                </div>

                <div class="col-span-1">
                    <h3 class="text-xl font-bold mb-4">Contact Us</h3>
                    <p class="text-gray-400 text-sm mb-2"><i class="fas fa-map-marker-alt mr-2"></i> 123 Main St, Anytown, USA 12345</p>
                    <p class="text-gray-400 text-sm mb-2"><i class="fas fa-phone mr-2"></i> +1 (555) 123-4567</p>
                    <p class="text-gray-400 text-sm mb-2"><i class="fas fa-envelope mr-2"></i> info@<?php echo strtolower(str_replace(' ', '', $companyName)); ?>.com</p>
                </div>
            </div>
            <div class="border-t border-gray-700 mt-8 pt-8 text-center text-gray-500 text-sm">
                &copy; <?php echo date("Y"); ?> <?php echo htmlspecialchars($companyName); ?>. All rights reserved.
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
        // These need to be globally available for the AI chat widget
        window.extractFramesFromVideo = function(videoFile, numFrames = 1) {
            return new Promise((resolve, reject) => {
                const video = document.getElementById('hiddenVideo');
                const canvas = document.getElementById('hiddenCanvas');
                const ctx = canvas.getContext('2d');

                video.src = URL.createObjectURL(videoFile);
                video.load();

                video.onloadeddata = () => {
                    const frames = [];
                    const interval = video.duration / (numFrames + 1); // Distribute frames evenly

                    canvas.width = video.videoWidth;
                    canvas.height = video.videoHeight;

                    let framesExtracted = 0;
                    const captureFrame = () => {
                        if (framesExtracted < numFrames) {
                            ctx.drawImage(video, 0, 0, canvas.width, canvas.height);
                            frames.push(canvas.toDataURL('image/jpeg', 0.8)); // Adjust quality as needed
                            framesExtracted++;
                            video.currentTime += interval;
                        } else {
                            video.pause();
                            URL.revokeObjectURL(video.src);
                            resolve(frames);
                        }
                    };

                    video.onseeked = () => {
                        captureFrame(); // Capture frame after seeking
                    };

                    video.onerror = (e) => {
                        reject(new Error('Error loading video for frame extraction: ' + video.error.message));
                    };

                    // Start capturing from the beginning or a suitable initial point
                    video.currentTime = 0.1; // Start slightly after 0 to avoid blank frames
                };

                video.onerror = (e) => {
                    reject(new Error('Error loading video file: ' + e.message));
                };
            });
        };

        window.dataURLtoBlob = function(dataurl) {
            const arr = dataurl.split(',');
            const mimeMatch = arr[0].match(/:(.*?);/);
            const mime = mimeMatch ? mimeMatch[1] : 'application/octet-stream';
            const bstr = atob(arr[1]);
            let n = bstr.length;
            const u8arr = new Uint8Array(n);
            while (n--) {
                u8arr[n] = bstr.charCodeAt(n);
            }
            return new Blob([u8arr], { type: mime });
        };

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

    </script>
    <?php include __DIR__ . '/ai_chat_widget.php'; ?>
</body>
</html>