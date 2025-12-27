// Open chat modal
function openStackAIModal() {
  document.getElementById("chatModal").classList.add("active");
  document.getElementById("chatOverlay").classList.add("active");
  document.body.style.overflow = "hidden";
  document.getElementById("userMessage").focus();

  // Show welcome message if first time
  if (!sessionStorage.getItem("stackAIWelcome")) {
    setTimeout(() => {
      addStackAIMessage(
        "Welcome to Stack AI! 👋 I'm here to help you with any questions about BookStack's ebooks, purchases, recommendations, and more. What can I help you with today?"
      );
      sessionStorage.setItem("stackAIWelcome", "true");
    }, 300);
  }
}

// Close chat modal
function closeStackAIModal() {
  document.getElementById("chatModal").classList.remove("active");
  document.getElementById("chatOverlay").classList.remove("active");
  document.body.style.overflow = "auto";
}

// Add user message to chat
function addUserMessage(text) {
  let box = document.getElementById("chatbox");
  let messageDiv = document.createElement("div");
  messageDiv.className = "message user";

  let bubble = document.createElement("div");
  bubble.className = "msg-bubble";
  bubble.textContent = text;

  messageDiv.appendChild(bubble);
  box.appendChild(messageDiv);
  box.scrollTop = box.scrollHeight;
}

// Add bot message to chat
function addStackAIMessage(text) {
  let box = document.getElementById("chatbox");
  let messageDiv = document.createElement("div");
  messageDiv.className = "message bot";

  let bubble = document.createElement("div");
  bubble.className = "msg-bubble";
  bubble.textContent = text;

  messageDiv.appendChild(bubble);
  box.appendChild(messageDiv);
  box.scrollTop = box.scrollHeight;
}

// Show typing indicator
function showTypingIndicator() {
  let box = document.getElementById("chatbox");
  let typingDiv = document.createElement("div");
  typingDiv.className = "message bot";
  typingDiv.id = "typing";

  typingDiv.innerHTML = `
    <div class="typing-indicator">
      <span class="typing-dot"></span>
      <span class="typing-dot"></span>
      <span class="typing-dot"></span>
    </div>
  `;

  box.appendChild(typingDiv);
  box.scrollTop = box.scrollHeight;
}

// Remove typing indicator
function removeTypingIndicator() {
  let typing = document.getElementById("typing");
  if (typing) {
    typing.remove();
  }
}

// Send message to backend
function sendStackAIMessage() {
  let msg = document.getElementById("userMessage").value.trim();

  if (!msg) {
    return;
  }

  // Add user message to chat
  addUserMessage(msg);
  document.getElementById("userMessage").value = "";

  // Show typing indicator
  showTypingIndicator();

  // Use root-relative path that works from any page
  const chatbotUrl = "/BookStack/chatbot/chatbot.php";

  // Create abort controller for timeout
  const controller = new AbortController();
  const timeoutId = setTimeout(() => controller.abort(), 180000); // 3 min timeout

  // Send message to backend via AJAX
  fetch(chatbotUrl, {
    method: "POST",
    headers: {
      "Content-Type": "application/x-www-form-urlencoded",
    },
    body: "message=" + encodeURIComponent(msg),
    signal: controller.signal,
  })
    .then((res) => {
      clearTimeout(timeoutId);
      if (!res.ok) {
        throw new Error(`HTTP error! status: ${res.status}`);
      }
      return res.text();
    })
    .then((data) => {
      // Remove typing indicator
      removeTypingIndicator();

      // Clean response from any extra whitespace
      data = data.trim();

      // Add bot response
      addStackAIMessage(data);
    })
    .catch((error) => {
      // Remove typing indicator
      removeTypingIndicator();

      // Show error message with details
      console.error("Chatbot Error:", error);
      addStackAIMessage(
        "Sorry, I'm having trouble connecting right now. Please try again or contact support@bookstack.com"
      );
    });
}

// Handle Enter key press to send message
function handleKeyPress(event) {
  if (event.key === "Enter" && !event.shiftKey) {
    event.preventDefault();
    sendStackAIMessage();
  }
}

// Optional: Close modal with Escape key
document.addEventListener("keydown", function (event) {
  if (event.key === "Escape") {
    closeStackAIModal();
  }
});

// --- New: compute top offset from navbar/header and set CSS variable ---
// Finds a header/navbar element and sets --stack-ai-top to its height (px).
function setChatTopOffset() {
  // selectors to try (ordered): common navbar/header selectors
  const selectors = ['.navbar', 'header', '.site-header', '.topbar', '#topbar', '.main-header'];
  let topElem = null;
  for (const sel of selectors) {
    const el = document.querySelector(sel);
    if (!el) continue;
    const style = window.getComputedStyle(el);
    // prefer fixed/sticky or elements at top of page
    if (style.position === 'fixed' || style.position === 'sticky' || el.getBoundingClientRect().top === 0) {
      topElem = el;
      break;
    }
    // otherwise pick first header-like element
    if (!topElem) topElem = el;
  }

  let topHeight = 0;
  if (topElem) {
    topHeight = Math.ceil(topElem.getBoundingClientRect().height);
  }

  // Set a small minimum if needed (0 ok)
  if (!topHeight) topHeight = 0;

  document.documentElement.style.setProperty('--stack-ai-top', topHeight + 'px');
}

// Run at load and on resize
document.addEventListener('DOMContentLoaded', () => {
  setChatTopOffset();
  // Also recalc after a short delay in case dynamic header rendering (e.g., after JS makes it fixed)
  setTimeout(setChatTopOffset, 300);
});

window.addEventListener('resize', setChatTopOffset);