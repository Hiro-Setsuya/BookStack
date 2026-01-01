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

  // LIGHTWEIGHT context (DO NOT load full data.txt for every request)
  // This prevents first-token delays and freezing
  $data = "BookStack is an ebook e-commerce platform where users can browse, purchase, and download digital books. Payments are made via PayPal. Users can manage accounts, access purchases instantly, and contact customer support.";

  // System prompt for Stack AI (optimized for speed)
  $prompt = "You are Stack AI, BookStack customer support.

Rules:
- Reply in ONE sentence only.
- Max 20 words.
- Be friendly and direct.
- If unclear, ask ONE short question.
- No greetings unless the user greets first.

Context:
$data

User:
$message

Answer:";


  // Ultra-fast greeting handling (NO AI CALL)
  $lower = strtolower($message);
  if (in_array($lower, ['hi', 'hello', 'hey', 'good morning', 'good afternoon', 'good evening'])) {
    echo "Hello! 👋 How can I help you today?";
    exit;
  }


  // Ollama payload (STREAMING ENABLED)
  $payload = [
    "model" => "llama3.2:1b",
    "prompt" => $prompt,
    "stream" => true,
    "options" => [
      "num_predict" => 35,     // HARD CAP (this is key)
      "temperature" => 0.2,    // less thinking
      "top_p" => 0.85,
      "num_ctx" => 768,        // smaller context = faster
      "repeat_penalty" => 1.1
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
  <button class="btn btn-md btn-green stack-ai-button shadow"
    onclick="openStackAIModal()" id="stackAIButton">
    Chat with Stack AI
  </button>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
  <script src="chatbot/script.js"></script>

</body>

</html>