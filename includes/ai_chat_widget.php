<?php
// includes/ai_chat_widget.php
// This widget can be included on any page where you want to provide AI chat functionality.

// The parent page (e.g., index.php, dashboard.php) is responsible for starting the session.
// A session check is not needed here as it's handled by the parent script.
// We just need to ensure functions are available.
require_once __DIR__ . '/functions.php';

// Fetch company name from system settings
$companyName = getSystemSetting('company_name') ?? 'Catdump';

// Check if user is logged in from the pre-existing session
$isUserLoggedIn = isset($_SESSION['user_id']);
?>

<div id="aiChatWidget" class="fixed bottom-0 right-0 w-full md:w-96 h-full md:h-[600px] bg-white border border-gray-300 rounded-lg shadow-xl flex flex-col z-[1000] transform translate-y-full md:translate-x-full transition-all duration-300 ease-in-out">
    <div class="flex items-center justify-between p-4 bg-blue-600 text-white rounded-t-lg cursor-grab">
        <h3 class="text-lg font-semibold flex items-center">
            Chat with AI Assistant
            <span class="ml-3 text-sm font-medium flex items-center">
                <span class="relative flex h-2 w-2">
                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-green-300 opacity-75"></span>
                    <span class="relative inline-flex rounded-full h-2 w-2 bg-green-400"></span>
                </span>
                <span class="ml-1">Online</span>
            </span>
        </h3>
        <button id="closeChat" class="text-white hover:text-gray-200 p-1 rounded-full hover:bg-blue-700 transition">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
        </button>
    </div>

    <div id="chatMessages" class="flex-1 p-4 overflow-y-auto bg-gray-50">
        <div class="flex justify-start mb-2">
            <div class="bg-blue-200 text-blue-800 rounded-lg p-3 max-w-[80%]">
                Hi! I'm your AI assistant from <?php echo htmlspecialchars($companyName); ?>. How can I help you with your project today? You can tell me what you need, or even upload a photo of your project or junk.
            </div>
        </div>
        <div id="typingIndicator" class="flex justify-start mb-2 hidden">
            <div class="bg-gray-200 text-gray-700 rounded-lg p-3 max-w-[80%] animate-pulse">
                Typing...
            </div>
        </div>
    </div>

    <div id="fileUploadPreview" class="p-2 border-t border-gray-200 hidden bg-white">
        <div class="flex items-center space-x-2 text-sm text-gray-600">
            <span id="previewFileName" class="truncate max-w-[calc(100%-40px)]"></span>
            <button id="removeFileButton" class="text-red-500 hover:text-red-700 text-xs font-semibold">Remove</button>
        </div>
    </div>

    <div id="quickReplyButtons" class="p-2 border-t border-gray-200 bg-white flex flex-wrap gap-2 justify-center hidden">
        </div>

    <div class="p-4 border-t border-gray-200 bg-white flex items-center">
        <label for="mediaFileInput" class="cursor-pointer text-gray-500 hover:text-blue-600 mr-2">
            <i class="fas fa-paperclip text-xl"></i>
            <input type="file" id="mediaFileInput" name="media_files[]" accept="image/*,video/*" class="hidden">
        </label>
        <input type="text" id="chatInput" placeholder="Type your message..." class="flex-1 p-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
        <button id="sendMessage" class="ml-2 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500">
            <svg class="w-5 h-5 transform rotate-90" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path></svg>
        </button>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const chatWidget = document.getElementById('aiChatWidget');
        const closeChatButton = document.getElementById('closeChat');
        const chatMessages = document.getElementById('chatMessages');
        const chatInput = document.getElementById('chatInput');
        const sendMessageButton = document.getElementById('sendMessage');
        const typingIndicator = document.getElementById('typingIndicator');
        const mediaFileInput = document.getElementById('mediaFileInput');
        const fileUploadPreview = document.getElementById('fileUploadPreview');
        const previewFileName = document.getElementById('previewFileName');
        const removeFileButton = document.getElementById('removeFileButton');
        const quickReplyButtonsContainer = document.getElementById('quickReplyButtons');

        let isChatOpen = false;
        let selectedFile = null;
        let currentConversationId = null;

        const isUserLoggedIn = <?php echo json_encode($isUserLoggedIn); ?>;

        if (!isUserLoggedIn) {
            sessionStorage.removeItem('conversation_id');
        } else {
            const storedId = sessionStorage.getItem('conversation_id');
            if (storedId) {
                currentConversationId = storedId;
            }
        }

        window.showAIChat = function(initialServiceType = 'general') {
            chatWidget.classList.remove('translate-y-full', 'md:translate-x-full');
            chatWidget.style.transform = 'translateY(0) translateX(0)';

            isChatOpen = true;
            document.body.style.overflow = 'hidden';
            chatInput.focus();

            const storedConversationId = sessionStorage.getItem('conversation_id');
            if (!storedConversationId || initialServiceType === 'general') {
                sessionStorage.removeItem('conversation_id');
                currentConversationId = null;
                chatMessages.innerHTML = `
                    <div class="flex justify-start mb-2">
                        <div class="bg-blue-200 text-blue-800 rounded-lg p-3 max-w-[80%]">
                            Hi! I'm your AI assistant from <?php echo htmlspecialchars($companyName); ?>. How can I help you with your project today? You can tell me what you need, or even upload a photo of your project or junk.
                        </div>
                    </div>
                `;
                sendUserMessageToAI('', initialServiceType);
            } else {
                currentConversationId = storedConversationId;
            }
        };

        closeChatButton.addEventListener('click', function() {
            chatWidget.classList.add('translate-y-full', 'md:translate-x-full');
            chatWidget.style.transform = '';
            isChatOpen = false;
            document.body.style.overflow = '';
            quickReplyButtonsContainer.classList.add('hidden');
            quickReplyButtonsContainer.innerHTML = '';
        });

        function addMessage(sender, message, isHtml = false) {
            const messageElement = document.createElement('div');
            messageElement.classList.add('flex', 'mb-2', sender === 'user' ? 'justify-end' : 'justify-start');
            
            const contentElement = document.createElement('div');
            contentElement.classList.add('rounded-lg', 'p-3', 'max-w-[80%]');
            if (sender === 'user') {
                contentElement.classList.add('bg-blue-500', 'text-white');
            } else {
                contentElement.classList.add('bg-gray-200', 'text-gray-800');
            }

            if (isHtml) {
                contentElement.innerHTML = message;
            } else {
                contentElement.innerHTML = sender === 'assistant' ? marked.parse(message) : message;
            }
            
            messageElement.appendChild(contentElement);
            chatMessages.appendChild(messageElement);
            chatMessages.scrollTop = chatMessages.scrollHeight;
        }

        function displayQuickReplyButtons(buttons) {
            quickReplyButtonsContainer.innerHTML = '';
            if (buttons && buttons.length > 0) {
                quickReplyButtonsContainer.classList.remove('hidden');
                buttons.forEach(buttonData => {
                    const button = document.createElement('button');
                    button.classList.add('px-4', 'py-2', 'bg-gray-200', 'text-gray-800', 'rounded-full', 'hover:bg-gray-300', 'focus:outline-none', 'focus:ring-2', 'focus:ring-gray-400', 'text-sm');
                    button.textContent = buttonData.text;
                    button.onclick = () => {
                        chatInput.value = buttonData.value;
                        sendMessageButton.click();
                    };
                    quickReplyButtonsContainer.appendChild(button);
                });
            } else {
                quickReplyButtonsContainer.classList.add('hidden');
            }
        }

        async function sendUserMessageToAI(message, initialServiceType = null) {
            if (message.trim() !== '') {
                addMessage('user', message);
            }
            chatInput.value = '';
            typingIndicator.classList.remove('hidden'); // Show typing indicator
            quickReplyButtonsContainer.classList.add('hidden');
            quickReplyButtonsContainer.innerHTML = '';

            const formData = new FormData();
            formData.append('message', message);
            if (initialServiceType) {
                formData.append('initial_service_type', initialServiceType);
            }
            
            // Use processed files from aiChatFileInput.processedFiles if available
            const filesToSend = mediaFileInput && mediaFileInput.processedFiles ? mediaFileInput.processedFiles : [];

            if (filesToSend.length > 0) {
                for (const file of filesToSend) {
                    formData.append('media_files[]', file);
                }
                // Clear the processed files array after appending them to FormData
                mediaFileInput.processedFiles = [];
                fileUploadPreview.classList.add('hidden');
                previewFileName.textContent = '';
            }


            if (currentConversationId) {
                formData.append('conversation_id', currentConversationId);
            }

            try {
                const response = await fetch('/api/openai_chat.php', {
                    method: 'POST',
                    body: formData
                });

                const data = await response.json();

                typingIndicator.classList.add('hidden'); // Hide typing indicator

                if (data.success) {
                    addMessage('assistant', data.ai_response);
                    if (data.conversation_id) {
                        sessionStorage.setItem('conversation_id', data.conversation_id);
                        currentConversationId = data.conversation_id;
                    } else if (data.conversation_id === null) {
                        sessionStorage.removeItem('conversation_id');
                        currentConversationId = null;
                    }

                    if (data.suggested_replies && data.suggested_replies.length > 0) {
                        displayQuickReplyButtons(data.suggested_replies);
                    }

                    if (data.redirect_url) {
                        setTimeout(() => {
                            window.location.href = data.redirect_url;
                        }, 1000);
                    }
                } else {
                    addMessage('assistant', 'Error: ' + (data.message || 'Something went wrong.'));
                    displayQuickReplyButtons([
                        {text: "Try again", value: "Please try that again."},
                        {text: "Start over", value: "I'd like to start a new project."}
                    ]);
                }
            } catch (error) {
                console.error('API Error:', error);
                typingIndicator.classList.add('hidden');
                addMessage('assistant', 'Sorry, I\'m having trouble connecting right now. Please try again in a moment.');
                 displayQuickReplyButtons([
                    {text: "Try again", value: "Please try that again."},
                    {text: "Start over", value: "I'd like to start a new project."}
                ]);
            }
        }

        sendMessageButton.addEventListener('click', function() {
            sendUserMessageToAI(chatInput.value);
        });

        chatInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                sendUserMessageToAI(chatInput.value);
            }
        });

        mediaFileInput.addEventListener('change', async function(e) {
            if (e.target.files.length > 0) {
                const files = Array.from(e.target.files);
                let fileNames = files.map(f => f.name).join(', ');
                previewFileName.textContent = `Selected: ${fileNames}`;
                fileUploadPreview.classList.remove('hidden');

                const processedFiles = [];
                for (const file of files) {
                    if (file.type.startsWith('video/')) {
                        window.showToast(`Processing video: ${file.name}...`, 'info');
                        try {
                            // Extract 10 frames from the video
                            const frames = await window.extractFramesFromVideo(file, 10);
                            frames.forEach((frame, index) => {
                                const blob = window.dataURLtoBlob(frame);
                                processedFiles.push(new File([blob], `frame_${file.name}_${index}.jpeg`, { type: 'image/jpeg' }));
                            });
                            window.showToast(`Extracted ${frames.length} frames from ${file.name}.`, 'success');
                        } catch (error) {
                            console.error('Error extracting frames:', error);
                            window.showToast(`Failed to extract frames from ${file.name}. Sending original video.`, 'error');
                            processedFiles.push(file); // Fallback to sending original video if frame extraction fails
                        }
                    } else {
                        processedFiles.push(file); // For images, just push the original file
                    }
                }
                mediaFileInput.processedFiles = processedFiles; // Store processed files
            } else {
                mediaFileInput.processedFiles = [];
                previewFileName.textContent = '';
                fileUploadPreview.classList.add('hidden');
            }
        });

        removeFileButton.addEventListener('click', function() {
            mediaFileInput.value = ''; // Clear file input
            mediaFileInput.processedFiles = []; // Clear processed files
            previewFileName.textContent = '';
            fileUploadPreview.classList.add('hidden');
        });

        if (window.innerWidth >= 768) {
            const chatHeader = chatWidget.querySelector('.p-4:first-child');
            let isDragging = false;
            let currentX;
            let currentY;
            let initialX;
            let initialY;
            let xOffset = 0;
            let yOffset = 0;

            chatHeader.addEventListener("mousedown", dragStart);
            chatWidget.addEventListener("mouseup", dragEnd);
            chatWidget.addEventListener("mousemove", drag);

            function dragStart(e) {
                initialX = e.clientX - xOffset;
                initialY = e.clientY - yOffset;

                if (e.target.closest('#aiChatWidget > div:first-child') === chatHeader) {
                    isDragging = true;
                }
            }

            function dragEnd(e) {
                initialX = currentX;
                initialY = currentY;
                isDragging = false;
            }

            function drag(e) {
                if (isDragging) {
                    e.preventDefault();
                    currentX = e.clientX - initialX;
                    currentY = e.clientY - initialY;

                    xOffset = currentX;
                    yOffset = currentY;

                    setTranslate(currentX, currentY, chatWidget);
                }
            }

            function setTranslate(xPos, yPos, el) {
                el.style.transform = "translate3d(" + xPos + "px, " + yPos + "px, 0)";
            }
        }
    });
</script>