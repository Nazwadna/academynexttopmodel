function chatbotCsrfToken() {
  return (
    document.querySelector('meta[name="csrf-token"]')?.getAttribute("content") ||
    (typeof window.__CHATBOT_CSRF__ !== "undefined" ? window.__CHATBOT_CSRF__ : "") ||
    ""
  );
}

function chatbotChatUrl() {
  return typeof window.__CHATBOT_CHAT_URL__ !== "undefined"
    ? window.__CHATBOT_CHAT_URL__
    : "http://127.0.0.1:5000/chat";
}

function chatbotHealthUrl() {
  return typeof window.__CHATBOT_HEALTH_URL__ !== "undefined"
    ? window.__CHATBOT_HEALTH_URL__
    : "http://127.0.0.1:5000/health";
}

async function sendMessage() {
  const input = document.getElementById("chat-input");
  const message = input.value.trim();
  if (!message) return;

  appendUserMessage(message);
  input.value = "";

  try {
    const response = await fetch(chatbotChatUrl(), {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
        "Accept": "application/json"
      },
      body: JSON.stringify({ message })
    });

    const data = await response.json();

    if (!response.ok || !data.success) {
      throw new Error(data.error || data.reply || `HTTP error! status: ${response.status}`);
    }

    appendBotMessage({
      reply: data.reply ?? "Tidak ada respons.",
      confidence: data.confidence ?? 0,
      handoff: data.handoff ?? false,
      whatsapp_url: data.whatsapp_url ?? null,
      source: data.source ?? data.intent ?? "chatbot"
    });
  } catch (error) {
    console.error("Chatbot error:", error);
    appendBotMessage({
      reply: "Maaf, terjadi kesalahan. Silakan coba lagi.",
      confidence: 0,
      handoff: false,
      source: "error"
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
        <div>${formatText(data.reply || "Tidak ada respons dari server.")}</div>
        <div class="chat-meta">
          ${Math.round((data.confidence || 0) * 100)}% akurasi
          ${data.source ? "· " + data.source : ""}
        </div>
        ${handoffButton}
      </div>
    </div>
  `;

  chatBox.scrollTop = chatBox.scrollHeight;
}

function formatText(text) {
  return escapeHtml(text)
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
    const response = await fetch(chatbotHealthUrl());
    const data = await response.json();

    const statusElement = document.getElementById("chatbot-status");
    if (statusElement) {
      const mode = data.mode ?? "—";
      const openai = data.openai_available ? "ON" : "OFF";
      const gemini = data.gemini_available ? "ON" : "OFF";

      statusElement.innerHTML = `
        <span class="status-indicator online"></span>
        Mode: ${mode} | OpenAI: ${openai} | Gemini: ${gemini}
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

// (debug removed)
