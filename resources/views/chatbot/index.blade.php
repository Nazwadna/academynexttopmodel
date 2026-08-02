<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>AI Chatbot - Academy NTM</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        .chat-messages {
            height: calc(100vh - 200px);
            overflow-y: auto;
        }
        .typing-indicator {
            display: none;
        }
        .typing-indicator.active {
            display: flex;
        }
        .typing-dot {
            animation: typing 1.4s infinite;
        }
        .typing-dot:nth-child(2) {
            animation-delay: 0.2s;
        }
        .typing-dot:nth-child(3) {
            animation-delay: 0.4s;
        }
        @keyframes typing {
            0%, 60%, 100% {
                transform: translateY(0);
            }
            30% {
                transform: translateY(-10px);
            }
        }
        .message-fade-in {
            animation: fadeIn 0.3s ease-in;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>
<body class="bg-gray-100">
    <!-- Header -->
    <header class="bg-white shadow-sm border-b">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">
                <div class="flex items-center">
                    <i data-lucide="bot" class="w-8 h-8 text-blue-600 mr-3"></i>
                    <h1 class="text-xl font-semibold text-gray-900">AI Assistant</h1>
                </div>
                <div class="flex items-center space-x-4">
                    <span id="status-indicator" class="flex items-center text-sm">
                        <span class="w-2 h-2 bg-green-500 rounded-full mr-2"></span>
                        <span class="text-gray-600">Online</span>
                    </span>
                    <button onclick="clearChat()" class="text-gray-500 hover:text-gray-700">
                        <i data-lucide="trash-2" class="w-5 h-5"></i>
                    </button>
                </div>
            </div>
        </div>
    </header>

    <!-- Main Chat Container -->
    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
        <div class="bg-white rounded-lg shadow-lg overflow-hidden">
            <!-- Chat Messages Area -->
            <div id="chat-messages" class="chat-messages p-6 bg-gray-50">
                <!-- Welcome Message -->
                <div class="flex items-start space-x-3 mb-4 message-fade-in">
                    <div class="flex-shrink-0 w-8 h-8 bg-blue-600 rounded-full flex items-center justify-center">
                        <i data-lucide="bot" class="w-5 h-5 text-white"></i>
                    </div>
                    <div class="flex-1">
                        <div class="bg-white rounded-lg p-4 shadow-sm">
                            <p class="text-gray-800">Hello! I'm your AI assistant. How can I help you today?</p>
                        </div>
                        <div class="text-xs text-gray-500 mt-1">Just now</div>
                    </div>
                </div>
            </div>

            <!-- Typing Indicator -->
            <div id="typing-indicator" class="typing-indicator px-6 pb-4">
                <div class="flex items-start space-x-3">
                    <div class="flex-shrink-0 w-8 h-8 bg-blue-600 rounded-full flex items-center justify-center">
                        <i data-lucide="bot" class="w-5 h-5 text-white"></i>
                    </div>
                    <div class="bg-white rounded-lg p-4 shadow-sm">
                        <div class="flex space-x-1">
                            <div class="w-2 h-2 bg-gray-400 rounded-full typing-dot"></div>
                            <div class="w-2 h-2 bg-gray-400 rounded-full typing-dot"></div>
                            <div class="w-2 h-2 bg-gray-400 rounded-full typing-dot"></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Input Area -->
            <div class="border-t bg-white p-4">
                <div class="flex items-end space-x-3">
                    <div class="flex-1">
                        <textarea 
                            id="message-input" 
                            rows="2" 
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent resize-none"
                            placeholder="Type your message..."
                            onkeydown="handleKeyPress(event)"></textarea>
                    </div>
                    <button 
                        onclick="sendMessage()" 
                        class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors flex items-center space-x-2">
                        <i data-lucide="send" class="w-5 h-5"></i>
                        <span>Send</span>
                    </button>
                </div>
                <div class="mt-2 text-xs text-gray-500">
                    Press Enter to send, Shift+Enter for new line
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="mt-6 bg-white rounded-lg shadow p-4">
            <h3 class="text-sm font-medium text-gray-700 mb-3">Quick Actions</h3>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-2">
                <button onclick="sendQuickMessage('Hello')" class="text-sm bg-gray-100 hover:bg-gray-200 px-3 py-2 rounded-lg transition-colors">
                    Say Hello
                </button>
                <button onclick="sendQuickMessage('Tell me about your academy')" class="text-sm bg-gray-100 hover:bg-gray-200 px-3 py-2 rounded-lg transition-colors">
                    About Academy
                </button>
                <button onclick="sendQuickMessage('Show me portfolio')" class="text-sm bg-gray-100 hover:bg-gray-200 px-3 py-2 rounded-lg transition-colors">
                    View Portfolio
                </button>
                <button onclick="sendQuickMessage('I need help')" class="text-sm bg-gray-100 hover:bg-gray-200 px-3 py-2 rounded-lg transition-colors">
                    Get Help
                </button>
            </div>
        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
    <script>
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        if (csrfToken) {
            axios.defaults.headers.common['X-CSRF-TOKEN'] = csrfToken;
        }

        // Initialize Lucide icons
        lucide.createIcons();

        let isTyping = false;

        // Check chatbot health on load
        checkHealth();

        function checkHealth() {
            axios.get('/chatbot/health')
                .then(response => {
                    updateStatus('Online', 'green');
                })
                .catch(error => {
                    updateStatus('Offline', 'red');
                });
        }

        function updateStatus(status, color) {
            const indicator = document.getElementById('status-indicator');
            const dot = indicator.querySelector('span:first-child');
            const text = indicator.querySelector('span:last-child');
            
            dot.className = `w-2 h-2 bg-${color}-500 rounded-full mr-2`;
            text.textContent = status;
        }

        function sendMessage() {
            const input = document.getElementById('message-input');
            const message = input.value.trim();
            
            if (!message || isTyping) return;
            
            // Add user message to chat
            addMessage(message, 'user');
            input.value = '';
            
            // Show typing indicator
            showTypingIndicator();
            
            // Send to backend with advanced features
            axios.post('/chatbot/chat', { 
                message: message,
                user_id: getUserId(),
                include_context: true
            })
                .then(response => {
                    hideTypingIndicator();
                    if (response.data.success) {
                        addMessage(response.data.response, 'bot', response.data);
                        updateChatInfo(response.data);
                    } else {
                        addMessage('Sorry, I encountered an error. Please try again.', 'bot');
                    }
                })
                .catch(error => {
                    hideTypingIndicator();
                    addMessage('Sorry, I\'m having trouble connecting. Please try again later.', 'bot');
                    console.error('Chat error:', error);
                });
        }

        function addMessage(text, sender, metadata = null) {
            const messagesContainer = document.getElementById('chat-messages');
            const timestamp = new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
            
            const messageDiv = document.createElement('div');
            messageDiv.className = `flex items-start space-x-3 mb-4 message-fade-in ${sender === 'user' ? 'justify-end' : ''}`;
            
            if (sender === 'user') {
                messageDiv.innerHTML = `
                    <div class="flex-1 text-right">
                        <div class="bg-blue-600 text-white rounded-lg p-4 shadow-sm inline-block">
                            <p>${escapeHtml(text)}</p>
                        </div>
                        <div class="text-xs text-gray-500 mt-1">${timestamp}</div>
                    </div>
                    <div class="flex-shrink-0 w-8 h-8 bg-gray-600 rounded-full flex items-center justify-center">
                        <i data-lucide="user" class="w-5 h-5 text-white"></i>
                    </div>
                `;
            } else {
                let confidenceBadge = '';
                let entitiesInfo = '';
                
                if (metadata) {
                    // Confidence indicator
                    if (metadata.confidence !== undefined) {
                        const confidence = Math.round(metadata.confidence * 100);
                        const confidenceColor = confidence > 80 ? 'green' : confidence > 60 ? 'yellow' : 'red';
                        confidenceBadge = `
                            <div class="flex items-center mt-2">
                                <span class="text-xs bg-${confidenceColor}-100 text-${confidenceColor}-800 px-2 py-1 rounded-full">
                                    ${confidence}% confidence
                                </span>
                                <span class="text-xs text-gray-500 ml-2">Intent: ${metadata.intent || 'unknown'}</span>
                            </div>
                        `;
                    }
                    
                    // Entities display
                    if (metadata.entities && Object.keys(metadata.entities).length > 0) {
                        const entityBadges = Object.entries(metadata.entities).map(([type, values]) => 
                            `<span class="text-xs bg-blue-100 text-blue-800 px-2 py-1 rounded-full mr-1">${type}: ${values.join(', ')}</span>`
                        ).join('');
                        entitiesInfo = `<div class="flex flex-wrap mt-2">${entityBadges}</div>`;
                    }
                }
                
                messageDiv.innerHTML = `
                    <div class="flex-shrink-0 w-8 h-8 bg-blue-600 rounded-full flex items-center justify-center">
                        <i data-lucide="bot" class="w-5 h-5 text-white"></i>
                    </div>
                    <div class="flex-1">
                        <div class="bg-white rounded-lg p-4 shadow-sm">
                            <p class="text-gray-800">${escapeHtml(text)}</p>
                            ${confidenceBadge}
                            ${entitiesInfo}
                        </div>
                        <div class="text-xs text-gray-500 mt-1">${timestamp}</div>
                    </div>
                `;
            }
            
            messagesContainer.appendChild(messageDiv);
            messagesContainer.scrollTop = messagesContainer.scrollHeight;
            
            // Re-initialize Lucide icons for new elements
            lucide.createIcons();
        }

        function getUserId() {
            let userId = localStorage.getItem('chatbot_user_id');
            if (!userId) {
                userId = 'user_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);
                localStorage.setItem('chatbot_user_id', userId);
            }
            return userId;
        }

        function updateChatInfo(metadata) {
            // Update status indicator based on confidence
            if (metadata.confidence !== undefined) {
                const confidence = metadata.confidence;
                const indicator = document.getElementById('status-indicator');
                const dot = indicator.querySelector('span:first-child');
                const text = indicator.querySelector('span:last-child');
                
                if (confidence > 0.8) {
                    dot.className = 'w-2 h-2 bg-green-500 rounded-full mr-2';
                    text.textContent = 'High Confidence';
                } else if (confidence > 0.6) {
                    dot.className = 'w-2 h-2 bg-yellow-500 rounded-full mr-2';
                    text.textContent = 'Medium Confidence';
                } else {
                    dot.className = 'w-2 h-2 bg-red-500 rounded-full mr-2';
                    text.textContent = 'Low Confidence';
                }
            }
        }

        function showTypingIndicator() {
            isTyping = true;
            document.getElementById('typing-indicator').classList.add('active');
            const messagesContainer = document.getElementById('chat-messages');
            messagesContainer.scrollTop = messagesContainer.scrollHeight;
        }

        function hideTypingIndicator() {
            isTyping = false;
            document.getElementById('typing-indicator').classList.remove('active');
        }

        function clearChat() {
            const messagesContainer = document.getElementById('chat-messages');
            messagesContainer.innerHTML = `
                <div class="flex items-start space-x-3 mb-4 message-fade-in">
                    <div class="flex-shrink-0 w-8 h-8 bg-blue-600 rounded-full flex items-center justify-center">
                        <i data-lucide="bot" class="w-5 h-5 text-white"></i>
                    </div>
                    <div class="flex-1">
                        <div class="bg-white rounded-lg p-4 shadow-sm">
                            <p class="text-gray-800">Chat cleared. How can I help you today?</p>
                        </div>
                        <div class="text-xs text-gray-500 mt-1">Just now</div>
                    </div>
                </div>
            `;
            lucide.createIcons();
        }

        function sendQuickMessage(message) {
            document.getElementById('message-input').value = message;
            sendMessage();
        }

        function handleKeyPress(event) {
            if (event.key === 'Enter' && !event.shiftKey) {
                event.preventDefault();
                sendMessage();
            }
        }

        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        // Auto-check health every 30 seconds
        setInterval(checkHealth, 30000);
    </script>
</body>
</html>
