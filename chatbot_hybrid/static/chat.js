async function sendMessage() {
  const input = document.getElementById("chat-input");
  const message = input.value.trim();
  if (!message) return;

  appendUserMessage(message);
  input.value = "";

  try {
    const response = await fetch("http://127.0.0.1:5000/chat", {
      method: "POST",
      headers: {
        "Content-Type": "application/json"
      },
      body: JSON.stringify({ message })
    });

    const data = await response.json();
    appendBotMessage(data);
  } catch (error) {
    appendBotMessage({
      reply: "Terjadi kesalahan saat menghubungkan chatbot.",
      confidence: 0,
      handoff: false
    });
  }
}

function appendUserMessage(text) {
  const chatBox = document.getElementById("chat-box");
  chatBox.innerHTML += `
    <div class="chat-row user-row">
      <div class="chat-bubble user-bubble">${escapeHtml(text)}</div>
    </div>
  `;
  chatBox.scrollTop = chatBox.scrollHeight;
}

function appendBotMessage(data) {
  const chatBox = document.getElementById("chat-box");

  let replyText = data.reply ?? data.response ?? "Tidak ada respons.";

  if (typeof replyText === "object" && replyText !== null) {
    if (replyText.message) {
      replyText = replyText.message;
    } else if (replyText.reply) {
      replyText = replyText.reply;
    } else if (replyText.name && replyText.status) {
      replyText = `Model ${replyText.name} saat ini berstatus ${replyText.status}.`;
    } else {
      replyText = JSON.stringify(replyText);
    }
  }

  let handoffButton = "";
  if (data.handoff && data.whatsapp_url) {
    handoffButton = `
      <div class="chat-extra">
        <a href="${data.whatsapp_url}" target="_blank" class="wa-btn">
          Hubungi Admin WhatsApp
        </a>
      </div>
    `;
  }

  chatBox.innerHTML += `
    <div class="chat-row bot-row">
      <div class="chat-bubble bot-bubble">
        <div>${formatText(replyText)}</div>
        <div class="chat-meta">
          ${Math.round((data.confidence || 0) * 100)}% akurasi
          ${data.source ? "  " + data.source : ""}
        </div>
        ${handoffButton}
      </div>
    </div>
  `;
  chatBox.scrollTop = chatBox.scrollHeight;
}

function formatText(text) {
  return escapeHtml(text)
    .replace(/\n\n\*\*Langkah \d+: (.*?)\*\*/g, '<div class="step-header"><strong>Langkah $1:</strong></div>')
    .replace(/\n\n\*\*(.*?)\*\*/g, '<div class="section-header"><strong>$1</strong></div>')
    .replace(/\n- (.*?)$/gm, '<div class="bullet-point">• $1</div>')
    .replace(/\n(\d+)\. (.*?)$/gm, '<div class="numbered-step">$1. $2</div>')
    .replace(/\n/g, "<br>");
}

function escapeHtml(text) {
  const div = document.createElement("div");
  div.innerText = text;
  return div.innerHTML;
}

document.addEventListener("DOMContentLoaded", function () {
  const input = document.getElementById("chat-input");
  if (input) {
    input.addEventListener("keypress", function (e) {
      if (e.key === "Enter") {
        sendMessage();
      }
    });
  }

  // Check chatbot health on load
  checkChatbotHealth();
});

async function checkChatbotHealth() {
  try {
    const response = await fetch("http://127.0.0.1:5000/health");
    const data = await response.json();
    console.log("Chatbot Health:", data);
    
    // Update status indicator if exists
    const statusElement = document.getElementById("chatbot-status");
    if (statusElement) {
      statusElement.innerHTML = `
        <span class="status-indicator ${data.status === 'ok' ? 'online' : 'offline'}"></span>
        Mode: ${data.mode} | OpenAI: ${data.openai_available ? '✅' : '❌'} | Gemini: ${data.gemini_available ? '✅' : '❌'}
      `;
    }
  } catch (error) {
    console.error("Health check failed:", error);
  }
}

// Quick action buttons
function quickAction(action) {
  const quickMessages = {
    'register': 'Saya ingin mendaftar di Academy Next Top Model',
    'portfolio': 'Bagaimana cara membuat portofolio modeling',
    'programs': 'Program apa saja yang tersedia di Academy Next Top Model',
    'contact': 'Saya ingin menghubungi admin'
  };
  
  if (quickMessages[action]) {
    document.getElementById("chat-input").value = quickMessages[action];
    sendMessage();
  }
}
