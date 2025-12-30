<?php
// --- BACKEND: Handle AJAX request for Stack AI Chatbot ---
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['message'])) {

  // Increase PHP timeout for slow CPU (reduced for safety)
  set_time_limit(90);
  header("Content-Type: text/plain");

  // Get user message
  $message = trim($_POST['message']);
  if ($message === '') {
    exit("Please enter a question.");
  }

  // Load FAQ data - FIXED FILE PATH (removed extra space)
  $data_file = __DIR__ . '/data.txt';
  if (file_exists($data_file)) {

    // Limit data size to prevent timeout (max 2000 chars)
    $data = "BookStack is an ebook e-commerce platform where users can browse, purchase, and download digital books. Payments are via PayPal. Users can manage accounts, download ebooks, and ask Stack AI chabot.";

  } else {
    $data = "BookStack is an ebook e-commerce platform.";
  }

  // System prompt for ebook chatbot - OPTIMIZED for faster response
  $prompt = "
You are Stack AI, a helpful assistant for BookStack ebook store.

BookStack Info:
$data

Rules:
- Answer based on info above
- Be friendly and concise (under 80 words)
- If info not available, say \"Contact support@bookstack.com\"

Question:
$message

Answer:
";

  // Prepare data for Ollama - STREAMING DISABLED to prevent PHP timeout
  $payload = [
    "model" => "llama3.2:1b",
    "prompt" => $prompt,
    "stream" => true,
    "options" => [
      "num_predict" => 80, // Limit response length
      "temperature" => 0.5
    ]
  ];

  // Send to Ollama API (IPv4 forced for Windows compatibility)
  $ch = curl_init("http://127.0.0.1:11434/api/generate");
  curl_setopt_array($ch, [
  CURLOPT_RETURNTRANSFER => false,
  CURLOPT_POST => true,
  CURLOPT_HTTPHEADER => ["Content-Type: application/json"],
  CURLOPT_POSTFIELDS => json_encode($payload),
  CURLOPT_TIMEOUT => 60,
  CURLOPT_CONNECTTIMEOUT => 5,
  CURLOPT_IPRESOLVE => CURL_IPRESOLVE_V4,
  CURLOPT_NOSIGNAL => 1,
  CURLOPT_WRITEFUNCTION => function($ch, $chunk) {
    echo $chunk;
    flush();
    return strlen($chunk);
  }
]);


  $response = curl_exec($ch);

  if (curl_errno($ch)) {
    echo "Connection error: " . htmlspecialchars(curl_error($ch));
    exit;
  }

  $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
  curl_close($ch);

  if ($http_code != 200 || !$response) {
    echo "AI service error. Please try again.";
    exit;
  }

  // Decode Ollama response
  $decoded = json_decode($response, true);

  // Validate AI response
  echo $decoded["response"] ?? "Error: No response from AI. Please try again.";
  exit;
}
?>

<!doctype html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Stack AI - ChatBot</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
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
      <div class="d-flex align-items-center gap-2">
        <h5 class="mb-0 fw-bold">Stack AI</h5>
      </div>
      <button class="btn btn-sm btn-link text-white" onclick="closeStackAIModal()" style="text-decoration: none;">
        <i class="bi bi-x-lg" style="font-size: 20px;"></i>
      </button>
    </div>

    <!-- Messages -->
    <div class="stack-ai-messages" id="chatbox"></div>

    <!-- Footer -->
    <div class="stack-ai-footer">
      <div class="d-flex gap-2 mb-2">
        <input type="text" id="userMessage" class="form-control stack-ai-input" placeholder="Ask about our books, discounts, delivery..." onkeypress="handleKeyPress(event)">
        <button class="btn stack-ai-send-btn" onclick="sendStackAIMessage()">
          <i class="bi bi-send"></i>
        </button>
      </div>
      <p class="text-muted text-center mb-0" style="font-size: 11px;">Powered by Stack AI</p>
    </div>
  </div>

  <!-- Chat Button (Bottom Right) -->
  <button class="btn btn-md btn-green stack-ai-button d-flex align-items-center justify-content-center gap-2 shadow" onclick="openStackAIModal()" id="stackAIButton" title="Ask Stack AI a question">
    <span class="btn-text">Chat with Stack AI</span>
  </button>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js" integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI" crossorigin="anonymous"></script>
  <script src="chatbot/script.js"></script>

</body>

</html>