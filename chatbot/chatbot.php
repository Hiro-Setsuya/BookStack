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
  $dataFile = __DIR__ . '/chatbot/data.txt';
  if (file_exists($dataFile)) {
    $data = file_get_contents($dataFile);
  } else {
    $data = "BookStack is an ebook e-commerce platform where users can browse, purchase, and download digital books. Payments are made via PayPal. Users can manage accounts, access purchases instantly, and contact customer support.";
  }

  // System prompt for Stack AI with comprehensive knowledge
  $prompt = "You are Stack AI, BookStack customer support assistant.

CRITICAL RULES - KEEP RESPONSES SHORT:
1. Answer ONLY the specific question asked - don't add extra topics
2. Maximum 3-4 short paragraphs or 5-6 bullet points
3. Use **bold** for key terms like **BookStack**, **PayPal**, **Account Verification**
4. Format with line breaks between sections
5. End with 2-3 brief related questions only

FORMATTING:
• Use bullet points (•) for lists
• Use numbered steps (1. 2. 3.) only for processes
• Add line break between each point
• Keep sentences short and clear

TONE: Friendly, helpful, direct - NO repetition or unnecessary details

Knowledge Base:
$data

User Question:
$message

Brief Answer:";


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
      "num_predict" => 200,        // Reduced for concise answers (was 400)
      "temperature" => 0.3,
      "top_p" => 0.9,
      "num_ctx" => 4096,
      "repeat_penalty" => 1.2,     // Higher to avoid repetition
      "stop" => ["---", "\n\n\n"]  // Stop at separators or too many breaks
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

<!doctype html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Stack AI - ChatBot</title>

  <!-- Google Fonts: Manrope -->
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@200..800&display=swap" rel="stylesheet" />

  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
  <link rel="stylesheet" href="chatbot/style.css">
  <style>
    /* Ensuring the button is a perfect circle */
    .stack-ai-button {
      width: 60px !important;
      height: 60px !important;
      border-radius: 50% !important;
      padding: 0 !important;
      display: flex !important;
      align-items: center;
      justify-content: center;
      bottom: 20px;
      /* Adjust positioning if needed */
      right: 20px;
      position: fixed;
      /* Ensures it floats like a mobile chat bubble */
      z-index: 1000;
    }
  </style>
</head>

<body>

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
          placeholder="Ask about our books, discounts, delivery..."
          onkeypress="handleKeyPress(event)">
        <button class="btn stack-ai-send-btn" onclick="sendStackAIMessage()">
          <i class="bi bi-send"></i>
        </button>
      </div>
      <p class="text-muted text-center mb-0" style="font-size: 11px;">Powered by Stack AI</p>
    </div>
  </div>

  <!-- Chat Button -->
  <button class="btn btn-md stack-ai-button shadow"
    onclick="openStackAIModal()" id="stackAIButton">
    <img src="assets/img/logo/chatbot.svg" height="25" alt="Stack AI" />
  </button>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
  <script src="chatbot/script.js"></script>

</body>

</html>