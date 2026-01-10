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
        "Welcome to **Stack AI**! 👋\n\nI can help with:\n• Account setup & verification\n• Purchasing & downloads\n• Vouchers & payments\n• Technical support\n\nWhat do you need help with?"
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

  // Enhanced formatting for better display
  let formattedText = text
    // First, normalize excessive spaces
    .replace(/[ \t]+/g, " ")
    // Convert **bold** to <strong>
    .replace(/\*\*(.*?)\*\*/g, "<strong>$1</strong>")
    // Fix numbered lists that have line breaks between number and text
    .replace(/(\d+)\.\s*\n/g, "$1. ")
    // Ensure proper line breaks exist after sentences
    .replace(/\. ([A-Z•\d])/g, ".\n$1")
    // Convert bullet points with proper spacing
    .replace(/\n• /g, "\n\n• ")
    // Convert numbered lists with proper spacing (single break before, keep text together)
    .replace(/\n(\d+)\. /g, "\n\n$1. ")
    // Convert all line breaks to <br>
    .replace(/\n/g, "<br>")
    // Clean up multiple consecutive <br> tags (max 2)
    .replace(/(<br>\s*){3,}/g, "<br><br>")
    // Add spacing before sections that start with emojis
    .replace(/(📚|❓|✓|✗|📧|📱|₱|🎯)/g, "<br>$1")
    // Clean leading/trailing breaks
    .replace(/^(<br>)+|(<br>)+$/g, "");

  bubble.innerHTML = formattedText;

  messageDiv.appendChild(bubble);
  box.appendChild(messageDiv);
  box.scrollTop = box.scrollHeight;
}

// Show typing indicator
function showTypingIndicator() {
  // Prevent duplicate typing indicators
  if (document.getElementById("typing")) return;

  const box = document.getElementById("chatbox");

  const typingDiv = document.createElement("div");
  typingDiv.className = "message bot";
  typingDiv.id = "typing";

  typingDiv.innerHTML = `
    <div class="msg-bubble" style="padding:10px 14px;">
      <div class="typing-indicator">
        <span class="typing-dot"></span>
        <span class="typing-dot"></span>
        <span class="typing-dot"></span>
      </div>
    </div>
  `;

  box.appendChild(typingDiv);

  // Force render + scroll
  requestAnimationFrame(() => {
    box.scrollTop = box.scrollHeight;
  });

  console.log("Typing indicator shown");
}

// Remove typing indicator
function removeTypingIndicator() {
  let typing = document.getElementById("typing");
  if (typing) {
    typing.remove();
    console.log("Typing indicator removed");
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

  // Force browser to paint before fetch starts
  requestAnimationFrame(() => {
    startFetch(msg);
  });
}

// Fetch logic extracted for better rendering
function startFetch(msg) {
  // Use root-relative path that works from any page
  const chatbotUrl = "/BookStack/chatbot/chatbot.php";

  // Create abort controller for timeout
  const controller = new AbortController();
  const timeoutId = setTimeout(() => controller.abort(), 180000); // 3 min timeout

  // Send message to backend via AJAX with streaming support
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

      // For streaming responses, read the body as it arrives
      const reader = res.body.getReader();
      const decoder = new TextDecoder();
      let receivedText = "";
      let firstChunkReceived = false;

      // Create a message bubble for the bot response
      let box = document.getElementById("chatbox");
      let messageDiv = document.createElement("div");
      messageDiv.className = "message bot";
      let bubble = document.createElement("div");
      bubble.className = "msg-bubble";
      messageDiv.appendChild(bubble);

      function readChunk() {
        reader.read().then(({ done, value }) => {
          if (done) {
            // Stream complete
            return;
          }

          // Remove typing indicator on first chunk
          if (!firstChunkReceived) {
            removeTypingIndicator();
            box.appendChild(messageDiv);
            firstChunkReceived = true;
          }

          // Decode and accumulate text
          const chunk = decoder.decode(value, { stream: true });
          receivedText += chunk;

          // Format and display accumulated text with proper formatting
          let formattedText = receivedText
            .replace(/[ \t]+/g, " ")
            .replace(/\*\*(.*?)\*\*/g, "<strong>$1</strong>")
            // Fix numbered lists that have line breaks between number and text
            .replace(/(\d+)\.\s*\n/g, "$1. ")
            .replace(/\. ([A-Z•\d])/g, ".\n$1")
            .replace(/\n• /g, "\n\n• ")
            .replace(/\n(\d+)\. /g, "\n\n$1. ")
            .replace(/\n/g, "<br>")
            .replace(/(<br>\s*){3,}/g, "<br><br>")
            .replace(/(📚|❓|✓|✗|📧|📱|₱|🎯)/g, "<br>$1")
            .replace(/^(<br>)+|(<br>)+$/g, "");

          bubble.innerHTML = formattedText;
          box.scrollTop = box.scrollHeight;

          // Continue reading
          readChunk();
        });
      }

      readChunk();
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
  const selectors = [
    ".navbar",
    "header",
    ".site-header",
    ".topbar",
    "#topbar",
    ".main-header",
  ];
  let topElem = null;
  for (const sel of selectors) {
    const el = document.querySelector(sel);
    if (!el) continue;
    const style = window.getComputedStyle(el);
    // prefer fixed/sticky or elements at top of page
    if (
      style.position === "fixed" ||
      style.position === "sticky" ||
      el.getBoundingClientRect().top === 0
    ) {
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

  document.documentElement.style.setProperty(
    "--stack-ai-top",
    topHeight + "px"
  );
}

// Run at load and on resize
document.addEventListener("DOMContentLoaded", () => {
  setChatTopOffset();
  // Also recalc after a short delay in case dynamic header rendering (e.g., after JS makes it fixed)
  setTimeout(setChatTopOffset, 300);
});

window.addEventListener("resize", setChatTopOffset);
