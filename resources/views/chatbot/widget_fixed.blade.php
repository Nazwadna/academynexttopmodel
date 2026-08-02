{{-- Fixed Chatbot Widget - Input yang Pasti Bisa Diketik --}}
@php
    $mindelLogo = asset('img/logo.jpg');
    $chatPostUrl = url('/chatbot/chat');
@endphp

<div id="mindel-chat-root"
     style="position: fixed; bottom: 5.5rem; right: 1.25rem; z-index: 10070; font-family: 'Open Sans', sans-serif;">
    
    {{-- Toggle Button --}}
    <button type="button" id="chatbot-toggle" aria-label="Buka chat Mindel"
            style="background: linear-gradient(135deg, #000 0%, #1a1a1a 100%); color: white; width: 56px; height: 56px; border-radius: 50%; border: 2px solid #30B0C7; cursor: pointer; box-shadow: 0 4px 16px rgba(0,0,0,0.35); display: flex; align-items: center; justify-content: center; padding: 0; overflow: hidden;">
        <img src="{{ $mindelLogo }}" alt="Mindel" width="36" height="36" style="border-radius: 50%; object-fit: cover;">
        <span style="position: absolute; bottom: 2px; right: 2px; width: 12px; height: 12px; background: #25D366; border: 2px solid white; border-radius: 50%;"></span>
    </button>

    {{-- Chat Window --}}
    <div id="chatbot-window"
         style="display: none; position: absolute; bottom: 4.25rem; right: 0; width: 380px; max-width: calc(100vw - 2rem); height: 500px; max-height: min(500px, 75vh); background: #fff; border: 1px solid #e5e5e5; border-radius: 12px; box-shadow: 0 12px 40px rgba(0,0,0,0.2); flex-direction: column; overflow: hidden;">

        {{-- Header --}}
        <div style="display: flex; align-items: center; gap: 10px; padding: 12px 14px; background: linear-gradient(135deg, #000 0%, #1a1a1a 100%); color: #fff; flex-shrink: 0;">
            <img src="{{ $mindelLogo }}" alt="" width="40" height="40" style="border-radius: 50%; object-fit: cover; border: 2px solid #30B0C7; flex-shrink: 0;">
            <div style="min-width: 0;">
                <div style="font-weight: 700; font-size: 15px;">Mindel</div>
                <div style="font-size: 11px; opacity: 0.88;">Personal AI Assistant</div>
                <div id="chatbot-status" style="font-size: 11px; opacity: 0.8; margin-top: 2px;">
                    <span class="status-indicator online" style="display: inline-block; width: 6px; height: 6px; background: #10b981; border-radius: 50%; margin-right: 4px;"></span>
                    Active • Natural conversation
                </div>
            </div>
        </div>

        {{-- Messages Container --}}
        <div id="chat-box"
             style="flex: 1; min-height: 0; padding: 12px; overflow-y: auto; background: #f8f9fa; font-size: 14px; line-height: 1.45;">
            
            {{-- Welcome Message --}}
            <div class="mindel-bot-row" style="display: flex; align-items: flex-end; gap: 8px; margin-bottom: 12px;">
                <img src="{{ $mindelLogo }}" alt="" width="32" height="32" style="border-radius: 50%; object-fit: cover; border: 1px solid #ddd; flex-shrink: 0;">
                <div class="chat-bubble bot-bubble" style="max-width: 78%; padding: 14px 16px; border-radius: 16px 16px 16px 6px; background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%); color: #222; border: 1px solid #e2e8f0; box-shadow: 0 4px 12px rgba(0,0,0,0.05);">
                    <div style="font-weight: 600; color: #1e293b; margin-bottom: 6px;">👋 Halo! Selamat datang di Academy NTM</div>
                    <div style="margin-bottom: 8px; line-height: 1.5;">Perkenalkan, saya <strong style="color: #0ea5e9;">Mindel</strong> - asisten digital pribadi Anda di Academy Next Top Model.</div>
                    <div style="margin-top: 10px; padding: 10px 12px; background: linear-gradient(135deg, #f0f9ff 0%, #e0f7fa 100%); border-radius: 10px; border-left: 3px solid #0ea5e9;">
                        <div style="font-weight: 600; color: #0284c7; margin-bottom: 6px; font-size: 13px;">� Bagaimana saya bisa membantu Anda hari ini?</div>
                        <div style="font-size: 12px; color: #0c4a6e; line-height: 1.6;">
                            📋 <strong>Informasi Program</strong> - Pendaftaran & kurikulum modeling<br>
                            🎯 <strong>Karir Modeling</strong> - Portfolio & audisi<br>
                            📞 <strong>Layanan Academy</strong> - Konsultasi & bimbingan<br>
                            🏆 <strong>Success Stories</strong> - Alumni & pencapaian
                        </div>
                    </div>
                    <div style="margin-top: 10px; font-size: 12px; color: #64748b; font-style: italic;">
                        "Saya didukung oleh teknologi AI terkini untuk memberikan informasi akurat dan personal"
                    </div>
                    <div class="chat-meta" style="margin-top: 10px; font-size: 10px; color: #94a3b8; display: flex; align-items: center; gap: 4px;">
                        <span style="background: linear-gradient(135deg, #0ea5e9, #0284c7); color: white; padding: 2px 6px; border-radius: 4px; font-weight: 600;">AI ASSISTANT</span>
                        <span>Ready to help • Natural conversation</span>
                    </div>
                </div>
            </div>
        </div>

        {{-- Input Area - FIXED --}}
        <form id="mindel-chat-form" action="#" method="post"
              style="display: flex; gap: 8px; padding: 10px 12px; border-top: 1px solid #e5e5e5; background: #fff; flex-shrink: 0; align-items: center;">
            <input type="text" 
                   id="chat-input" 
                   name="q" 
                   autocomplete="off" 
                   placeholder="Ketik pertanyaan Anda di sini..." 
                   style="flex: 1; min-width: 0; padding: 12px 16px; border: 2px solid #e5e5e5; border-radius: 10px; font-size: 15px; font-family: inherit; box-sizing: border-box; background: #fff; color: #222; outline: none; transition: all 0.2s ease;"
                   onfocus="this.style.borderColor='#30B0C7'; this.style.boxShadow='0 0 0 3px rgba(48, 176, 199, 0.1)';"
                   onblur="this.style.borderColor='#e5e5e5'; this.style.boxShadow='none';">
            <button type="submit" id="mindel-send-btn" aria-label="Kirim pesan"
                    style="flex-shrink: 0; width: 48px; height: 48px; border: none; border-radius: 10px; background: linear-gradient(135deg, #30B0C7 0%, #25D366 100%); color: #fff; cursor: pointer; display: flex; align-items: center; justify-content: center; transition: all 0.2s; box-shadow: 0 4px 12px rgba(48, 176, 199, 0.3);">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <path d="M22 2L11 13M22 2l-7 20-4-9-9-4 20-7z"/>
                </svg>
            </button>
        </form>
    </div>
</div>

{{-- CSS --}}
<style>
    #mindel-chat-root #chatbot-toggle:hover {
        transform: scale(1.1);
        box-shadow: 0 8px 25px rgba(0,0,0,0.5);
    }
    
    #mindel-chat-root .chat-bubble.bot-bubble a.wa-btn {
        display: inline-flex;
        align-items: center;
        background: linear-gradient(135deg, #25D366 0%, #128C7E 100%);
        color: #fff;
        text-decoration: none;
        padding: 8px 12px;
        border-radius: 8px;
        font-weight: 600;
        font-size: 13px;
        margin-top: 8px;
        box-shadow: 0 4px 12px rgba(37, 211, 102, 0.3);
        transition: all 0.3s ease;
    }
    
    #mindel-chat-root .chat-bubble.bot-bubble a.wa-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 16px rgba(37, 211, 102, 0.4);
    }
    
    #mindel-chat-root #mindel-send-btn:hover:not(:disabled) {
        transform: translateY(-1px);
        box-shadow: 0 6px 16px rgba(48, 176, 199, 0.4);
    }
    
    #mindel-chat-root #mindel-send-btn:disabled {
        opacity: 0.6;
        cursor: not-allowed;
        transform: none;
    }
    
    #mindel-chat-root .user-row .chat-bubble.user-bubble {
        max-width: 80%;
        padding: 10px 14px;
        border-radius: 14px 14px 4px 14px;
        background: linear-gradient(135deg, #000 0%, #1a1a1a 100%);
        color: #fff;
        font-size: 14px;
        word-wrap: break-word;
        box-shadow: 0 2px 8px rgba(0,0,0,0.15);
    }
    
    #mindel-chat-root .mindel-bot-row {
        animation: slideInUp 0.4s ease-out;
    }
    
    @keyframes slideInUp {
        from {
            opacity: 0;
            transform: translateY(20px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
    
    #mindel-chat-root ::-webkit-scrollbar {
        width: 6px;
    }
    
    #mindel-chat-root ::-webkit-scrollbar-track {
        background: #f1f1f1;
    }
    
    #mindel-chat-root ::-webkit-scrollbar-thumb {
        background: #c1c1c1;
        border-radius: 3px;
    }
    
    #mindel-chat-root ::-webkit-scrollbar-thumb:hover {
        background: #a8a8a8;
    }
</style>

{{-- JavaScript - FIXED --}}
<script>
(function () {
    'use strict';
    var MINDEL_AVATAR = @json($mindelLogo);
    // Proxy lewat Laravel (/chatbot/chat) agar cocok dengan XAMPP + CSRF
    var CHAT_URL = @json($chatPostUrl);
    window.__CHATBOT_CSRF__ = @json(csrf_token());

    function csrfHeader() {
        var meta = document.querySelector('meta[name="csrf-token"]');
        return (meta && meta.getAttribute('content')) || window.__CHATBOT_CSRF__ || '';
    }

    function bootMindelChat() {
        
        var chatBtn = document.getElementById('chatbot-toggle');
        var chatWindow = document.getElementById('chatbot-window');
        var chatInput = document.getElementById('chat-input');
        var chatBox = document.getElementById('chat-box');
        var form = document.getElementById('mindel-chat-form');
        var sendBtn = document.getElementById('mindel-send-btn');

        if (!chatBtn || !chatWindow || !chatInput || !chatBox || !form) {
            console.error('❌ Mindel Chat: Elements not found');
            return;
        }


        // Test input field
        chatInput.addEventListener('input', function() {
        });

        // Toggle functionality
        chatBtn.addEventListener('click', function () {
            var open = chatWindow.style.display === 'none' || chatWindow.style.display === '';
            chatWindow.style.display = open ? 'flex' : 'none';
            if (open) {
                chatWindow.style.flexDirection = 'column';
                setTimeout(function () { 
                    chatInput.focus(); 
                    chatBox.scrollTop = chatBox.scrollHeight;
                }, 100);
            }
        });

        // Utility functions
        function escapeHtml(text) {
            var div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        function formatText(text) {
            return escapeHtml(text)
                .replace(/\n\n\*\*Langkah \d+: (.*?)\*\*/g, '<div class="step-header"><strong>📍 Langkah $1:</strong></div>')
                .replace(/\n\n\*\*(.*?)\*\*/g, '<div class="section-header"><strong>📋 $1</strong></div>')
                .replace(/\n- (.*?)$/gm, '<div class="bullet-point">• $1</div>')
                .replace(/\n(\d+)\. (.*?)$/gm, '<div class="numbered-step"><strong>$1.</strong> $2</div>')
                .replace(/\n/g, '<br>');
        }

        function appendUserMessage(text) {
            chatBox.innerHTML +=
                '<div class="chat-row user-row" style="display:flex;justify-content:flex-end;margin-bottom:12px;animation:slideInUp 0.3s ease-out;">' +
                '<div class="chat-bubble user-bubble">' + escapeHtml(text) + '</div></div>';
            chatBox.scrollTop = chatBox.scrollHeight;
        }

        function appendBotMessage(data) {
            var handoff = '';
            if (data.handoff && data.whatsapp_url) {
                handoff =
                    '<div style="margin-top:12px;"><a class="wa-btn" href="' + escapeHtml(data.whatsapp_url) +
                    '" target="_blank" rel="noopener">💬 Hubungi Admin WhatsApp</a></div>';
            }
            
            var src = data.source ? ' · ' + escapeHtml(String(data.source)) : '';
            var conf = Math.round((data.confidence || 0) * 100);
            var confidenceColor = conf >= 80 ? '#10b981' : conf >= 60 ? '#f59e0b' : '#ef4444';
            var confidenceIcon = conf >= 80 ? '✨' : conf >= 60 ? '⚡' : '💭';
            
            // Natural conversation indicators
            var conversationStyle = '<div style="margin-top:8px;display:flex;gap:6px;align-items:center;">' +
                '<span style="background:linear-gradient(135deg,#f0f9ff,#e0f7fa);color:#0284c7;padding:3px 8px;border-radius:12px;font-size:11px;font-weight:500;border:1px solid #bae6fd;">💬 Natural Response</span>' +
                '<span style="font-size:11px;color:#64748b;font-style:italic;">Personalized for you</span>' +
                '</div>';
            
            chatBox.innerHTML +=
                '<div class="mindel-bot-row" style="display:flex;align-items:flex-end;gap:8px;margin-bottom:16px;animation:slideInUp 0.4s ease-out;">' +
                '<img src="' + escapeHtml(MINDEL_AVATAR) + '" alt="" width="36" height="36" style="border-radius:50%;object-fit:cover;border:1px solid #e2e8f0;flex-shrink:0;box-shadow:0 2px 8px rgba(0,0,0,0.1);">' +
                '<div class="chat-bubble bot-bubble" style="max-width:80%;padding:16px 18px;border-radius:20px 20px 20px 8px;background:linear-gradient(135deg,#ffffff 0%, #f8fafc 100%);color:#1e293b;border:1px solid #e2e8f0;box-shadow:0 4px 16px rgba(0,0,0,0.08);">' +
                '<div style="line-height:1.6;margin-bottom:8px;">' + formatText(data.reply || 'Maaf, saya sedang tidak bisa merespons. Silakan coba lagi.') + '</div>' +
                conversationStyle +
                '<div class="chat-meta" style="margin-top:12px;font-size:10px;color:#94a3b8;display:flex;align-items:center;gap:8px;flex-wrap:wrap;">' +
                '<span style="color:' + confidenceColor + ';font-weight:500;background:' + (conf >= 80 ? '#f0fdf4' : conf >= 60 ? '#fffbeb' : '#fef2f2') + ';padding:2px 6px;border-radius:8px;">' + confidenceIcon + ' ' + conf + '% relevant</span>' +
                (src ? '<span style="background:#f1f5f9;color:#475569;padding:2px 6px;border-radius:6px;">' + src + '</span>' : '') +
                '<span style="background:#e0f2fe;color:#0284c7;padding:2px 6px;border-radius:6px;font-weight:500;">AI Assistant</span>' +
                '</div>' +
                handoff + '</div></div>';
            chatBox.scrollTop = chatBox.scrollHeight;
        }

        var sending = false;
        var token = csrfHeader();

        // Enhanced send function
        window.mindelChatSend = async function () {
            var message = chatInput.value.trim();
            if (!message || sending) {
                return;
            }

            
            sending = true;
            if (sendBtn) sendBtn.disabled = true;
            
            // Add user message immediately
            appendUserMessage(message);
            chatInput.value = '';

            // Add typing indicator
            var typingDiv = document.createElement('div');
            typingDiv.className = 'mindel-bot-row';
            typingDiv.innerHTML = 
                '<img src="' + escapeHtml(MINDEL_AVATAR) + '" width="36" height="36" style="border-radius:50%;object-fit:cover;border:1px solid #ddd;flex-shrink:0;">' +
                '<div class="chat-bubble bot-bubble" style="padding:14px 16px;background:#f0f0f0;border:1px solid #e0e0e0;">' +
                '<div style="display:flex;align-items:center;gap:8px;color:#666;">' +
                '<div style="width:8px;height:8px;border-radius:50%;background:#30B0C7;animation:typing 1.4s infinite;"></div>' +
                '<div style="width:8px;height:8px;border-radius:50%;background:#30B0C7;animation:typing 1.4s 0.2s infinite;"></div>' +
                '<div style="width:8px;height:8px;border-radius:50%;background:#30B0C7;animation:typing 1.4s 0.4s infinite;"></div>' +
                '<span style="margin-left:8px;">Mindel AI berpikir...</span>' +
                '</div></div>';
            chatBox.appendChild(typingDiv);
            chatBox.scrollTop = chatBox.scrollHeight;

            try {
                var res = await fetch(CHAT_URL, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': token,
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    credentials: 'same-origin',
                    body: JSON.stringify({
                        message: message
                    })
                });
                
                var data = await res.json().catch(function () { return {}; });
                
                // Remove typing indicator
                if (typingDiv.parentNode) {
                    typingDiv.parentNode.removeChild(typingDiv);
                }
                
                if (!res.ok) {
                    throw new Error('HTTP ' + res.status);
                }
                
                appendBotMessage({
                    reply: data.response || data.reply,
                    confidence: data.confidence,
                    handoff: data.handoff,
                    whatsapp_url: data.whatsapp_url,
                    source: data.source || data.intent
                });
                
            } catch (e) {
                console.error('❌ Send error:', e);
                
                // Remove typing indicator
                if (typingDiv.parentNode) {
                    typingDiv.parentNode.removeChild(typingDiv);
                }
                
                appendBotMessage({
                    reply: '� Mohon maaf, saya sedang mengalami gangguan teknis sementara. Silakan coba beberapa saat lagi atau hubungi tim kami langsung melalui WhatsApp untuk bantuan segera. Terima kasih atas pengertian Anda.',
                    confidence: 0,
                    source: 'technical_issue'
                });
            } finally {
                sending = false;
                if (sendBtn) sendBtn.disabled = false;
                chatInput.focus();
            }
        };

        // Event listeners
        
        form.addEventListener('submit', function (e) {
            e.preventDefault();
            window.mindelChatSend();
        });

        chatInput.addEventListener('keydown', function (e) {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                window.mindelChatSend();
            }
        });

        // Test input focus
        chatInput.addEventListener('focus', function() {
        });

    }

    // Initialize
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', bootMindelChat);
    } else {
        bootMindelChat();
    }
})();

// Add enhanced animations
var style = document.createElement('style');
style.textContent = `
    @keyframes typing {
        0%, 60%, 100% { transform: scale(1); opacity: 0.7; }
        30% { transform: scale(1.2); opacity: 1; }
    }
    
    @keyframes pulse {
        0%, 100% { transform: scale(1); opacity: 1; }
        50% { transform: scale(1.05); opacity: 0.8; }
    }
    
    .mindel-bot-row {
        animation: slideInUp 0.5s cubic-bezier(0.25, 0.46, 0.45, 0.94);
    }
    
    .chat-bubble.bot-bubble {
        transition: all 0.3s ease;
    }
    
    .chat-bubble.bot-bubble:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(0,0,0,0.12);
    }
`;
document.head.appendChild(style);
</script>
