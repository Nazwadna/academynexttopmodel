<!-- Chatbot Widget -->
<div id="chat-container" class="fixed bottom-6 right-6 z-50 transition-all duration-300">
    <!-- Chat Button -->
    <button id="chatbot-toggle" class="bg-[#000000] hover:bg-[#30B0C7] text-white rounded-full p-4 shadow-lg transition-all duration-300 transform hover:scale-110 border-2 border-[#30B0C7]">
        <i data-lucide="message-circle" class="w-6 h-6"></i>
        <span class="absolute -top-1 -right-1 w-3 h-3 bg-green-500 rounded-full animate-pulse border-2 border-white"></span>
    </button>
    
    <!-- Chat Window -->
    <div id="chatbot-window" class="hidden absolute bottom-20 right-0 w-96 h-[500px] bg-white rounded-lg shadow-2xl overflow-hidden flex flex-col border-2 border-[#C4C4C4]">
        <!-- Header -->
        <div class="chat-header">
            <span>Mindel - Admin Model</span>
            <span id="chatbot-status">
                <span class="status-indicator online"></span>
                Online
            </span>
        </div>
        
        <!-- Messages Container -->
        <div id="chat-box" class="flex-1 p-4 bg-[#FFF5F2] overflow-y-auto">
            <!-- Welcome Message -->
            <div class="chat-row bot-row">
                <div class="chat-bubble bot-bubble">
                    <div>Halo! Saya Mindel, Admin Model dari Academy Next Top Model. Ada yang bisa saya bantu?</div>
                    <div class="chat-meta">100% akurasi · system</div>
                </div>
            </div>
        </div>
        
        <!-- Quick Actions -->
        <div class="quick-actions">
            <button class="quick-btn" onclick="quickAction('register')">Daftar</button>
            <button class="quick-btn" onclick="quickAction('portfolio')">Portfolio</button>
            <button class="quick-btn" onclick="quickAction('programs')">Programs</button>
            <button class="quick-btn" onclick="quickAction('contact')">Contact</button>
        </div>
        
        <!-- Input Area -->
        <div class="chat-input-container">
            <input type="text" id="chat-input" placeholder="Tanyakan pada Mindel..." autocomplete="off">
            <button id="send-btn" onclick="sendMessage()">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M22 2L11 13M22 2l-7 20-4-9-9-4 20-7z"/>
                </svg>
            </button>
        </div>
    </div>
</div>

<!-- Include Hybrid Chatbot CSS and JS with cache busting -->
<link rel="stylesheet" href="{{ asset('chatbot/style.css') }}?v=4">
<script>
window.__CHATBOT_CSRF__ = @json(csrf_token());
window.__CHATBOT_CHAT_URL__ = @json(url('/chatbot/chat'));
window.__CHATBOT_HEALTH_URL__ = @json(url('/chatbot/health'));
</script>
<script src="{{ asset('chatbot/chat.js') }}?v=4"></script>

<script>
// Simple toggle function - no conflicts
document.addEventListener('DOMContentLoaded', function() {
    
    // Toggle chatbot window
    document.getElementById('chatbot-toggle').addEventListener('click', function() {
        const window = document.getElementById('chatbot-window');
        const button = document.getElementById('chatbot-toggle');
        
        if (window.classList.contains('hidden')) {
            window.classList.remove('hidden');
            button.innerHTML = '<i data-lucide="x" class="w-6 h-6"></i>';
            lucide.createIcons();
            
            // Focus input
            setTimeout(() => {
                document.getElementById('chat-input').focus();
            }, 300);
        } else {
            window.classList.add('hidden');
            button.innerHTML = '<i data-lucide="message-circle" class="w-6 h-6"></i><span class="absolute -top-1 -right-1 w-3 h-3 bg-green-500 rounded-full animate-pulse border-2 border-white"></span>';
            lucide.createIcons();
        }
    });

    // Handle Enter key
    document.getElementById('chat-input').addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            sendMessage();
        }
    });
});
</script>
