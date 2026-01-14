<?php
// --- BACKEND: Handle AJAX request for Stack AI Chatbot ---
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['message'])) {

  // Increase PHP timeout slightly (streaming prevents long blocking)
  set_time_limit(120);
  header("Content-Type: text/plain; charset=utf-8");
  header("Cache-Control: no-cache");
  header("X-Accel-Buffering: no"); // Important for streaming

  // Get user message
  $message = trim($_POST['message']);
  if ($message === '') {
    exit("Please enter a question.");
  }

  // Load FULL knowledge base from data.txt
  $dataFile = __DIR__ . '/data.txt';
  if (file_exists($dataFile)) {
    $data = file_get_contents($dataFile);
  } else {
    $data = "BookStack is an ebook e-commerce platform where users can browse, purchase, and download digital books. Payments are made via PayPal. Users can manage accounts, access purchases instantly, and contact customer support.";
  }

  // System prompt for Stack AI with comprehensive knowledge
  $prompt = "You are Stack AI, a helpful BookStack customer support assistant.

  ### CRITICAL RULES:
  • Answer ONLY with the response itself - NO explanations, NO commentary, NO meta-talk
  • DO NOT explain what you're doing or thinking
  • DO NOT say things like 'Here's the answer' or 'Let me help you'
  • Just provide the answer directly

  ### RESPONSE GUIDELINES:
  1. If question is about BookStack/ebooks/accounts/payments/platform → Answer using [Knowledge Base]
  2. If question is personal/off-topic/unrelated to BookStack → Output ONLY this:
     'I am sorry, but I can only assist with questions regarding **BookStack** services and policies. Please contact us at **nullbyte235@gmail.com** for other inquiries.'
  3. Keep answers brief (2-3 paragraphs max)

  ### FORMATTING:
  • Use **bold** for important terms
  • Use bullet points (•) for lists
  • Use numbered steps (1. 2. 3.) for processes
  • Add line breaks between sections

  [Knowledge Base]:
  $data

  User Question: $message

  Answer:";


  // Ultra-fast greeting handling (NO AI CALL)
  $lower = strtolower($message);
  if (in_array($lower, ['hi', 'hello', 'hey', 'good morning', 'good afternoon', 'good evening'])) {
    echo "Hello! 👋 Welcome to **BookStack** support.\n\nI can help with:\n• Account & verification\n• Purchasing ebooks\n• Downloads & payments\n• Vouchers & support\n\nWhat do you need help with?";
    exit;
  }


  // Ollama payload (CONCISE RESPONSE MODE)
  $payload = [
    "model" => "llama3.2:1b",
    "prompt" => $prompt,
    "stream" => true,
    "options" => [
      "num_predict" => 150,        // Shorter responses
      "temperature" => 0.2,        // Lower = more deterministic, less creative
      "top_p" => 0.85,             // More focused sampling
      "num_ctx" => 4096,
      "repeat_penalty" => 1.3,     // Higher to avoid repetition
      "stop" => ["---", "\n\n\n", "Here's", "Let me", "I'll", "However", "Since"]  // Stop meta-talk
    ]

  ];

  // Initialize cURL to Ollama
  $ch = curl_init("http://127.0.0.1:11434/api/generate");

  curl_setopt_array($ch, [
    CURLOPT_POST => true,
    CURLOPT_HTTPHEADER => ["Content-Type: application/json"],
    CURLOPT_POSTFIELDS => json_encode($payload),

    // IMPORTANT: streaming mode
    CURLOPT_RETURNTRANSFER => false,

    // Timeouts (safe with streaming)
    CURLOPT_TIMEOUT => 120,
    CURLOPT_CONNECTTIMEOUT => 5,

    // Windows stability fixes
    CURLOPT_IPRESOLVE => CURL_IPRESOLVE_V4,
    CURLOPT_NOSIGNAL => 1,

    // Stream chunks as soon as Ollama sends tokens
    CURLOPT_WRITEFUNCTION => function ($ch, $chunk) {
      $data = json_decode($chunk, true);

      // Ollama streams JSON lines; extract response tokens
      if (isset($data['response'])) {
        echo $data['response'];
        flush();
      }

      return strlen($chunk);
    }
  ]);

  // Execute streaming request
  curl_exec($ch);

  if (curl_errno($ch)) {
    echo "\n\n[Connection error: " . curl_error($ch) . "]";
  }

  curl_close($ch);
  exit;
}
?>

<!-- Stack AI Chatbot CSS -->
<link rel="stylesheet" href="chatbot/style.css">

<!-- Chat Modal Overlay -->
<div class="stack-ai-overlay" id="chatOverlay" onclick="closeStackAIModal()"></div>

<!-- Right Side Chat Modal -->
<div class="stack-ai-modal" id="chatModal">

  <!-- Header -->
  <div class="stack-ai-header d-flex justify-content-between align-items-center">
    <h5 class="mb-0 fw-bold">Stack AI</h5>
    <button class="btn btn-sm btn-link text-white" onclick="closeStackAIModal()">
      <i class="bi bi-x-lg"></i>
    </button>
  </div>

  <!-- Messages -->
  <div class="stack-ai-messages" id="chatbox"></div>

  <!-- Footer -->
  <div class="stack-ai-footer">
    <div class="d-flex gap-2 mb-2">
      <input type="text" id="userMessage" class="form-control stack-ai-input"
        placeholder="Ask about our website, ebooks, accounts, or payments..."
        onkeypress="handleKeyPress(event)">
      <button class="btn stack-ai-send-btn" onclick="sendStackAIMessage()">
        <i class="bi bi-send"></i>
      </button>
    </div>
    <p class="text-muted text-center mb-3" style="font-size: 11px;">Powered by Stack AI</p>
  </div>
</div>

<!-- Chat Button -->
<button class="btn btn-md stack-ai-button shadow"
  onclick="openStackAIModal()" id="stackAIButton">
  <img src="assets/img/logo/chatbot.svg" height="25" alt="Stack AI" />
</button>

<!-- Stack AI Chatbot Script -->
<script src="chatbot/script.js"></script>