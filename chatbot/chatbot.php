<?php
// --- BACKEND:  Handle AJAX request for Stack AI Chatbot ---
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['message'])) {

  // Increase PHP timeout for slow CPU
  set_time_limit(180);

  $message = $_POST['message'];

  // Load FAQ data - FIXED FILE PATH (removed extra space)
  $data_file = __DIR__ . '/data.txt';
  if (file_exists($data_file)) {
    $data = file_get_contents($data_file);
    // Limit data size to prevent timeout (max 3000 chars)
    if (strlen($data) > 3000) {
      $data = substr($data, 0, 3000) . "\n... [truncated for performance]";
    }
  } else {
    $data = "No reference data available.";
  }

  // System prompt for ebook chatbot - OPTIMIZED for faster response
  $prompt = "You are Stack AI, a helpful assistant for BookStack ebook store.\n\nBookStack Info:\n$data\n\nRules: Answer based on info above. Be friendly and concise (under 100 words). If info not available, say \"Contact support@bookstack.com\".\n\nQuestion: $message\nAnswer:";

  // Prepare data for Ollama - USE STREAMING for faster response
  $payload = json_encode([
    "model" => "llama3.2:latest",
    "prompt" => $prompt,
    "stream" => false, // Keep false for now, but increased timeout
    "options" => [
      "num_predict" => 150, // Limit response length
      "temperature" => 0.7
    ]
  ]);

  // Quick connection check first
  $check = @file_get_contents("http://127.0.0.1:11434/api/version");
  if ($check === false) {
    echo "AI service is not running. Please start Ollama and try again.";
    exit;
  }

  // Send to Ollama API
  $ch = curl_init("http://127.0.0.1:11434/api/generate");
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
  curl_setopt($ch, CURLOPT_POST, true);
  curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Content-Type: application/json"
  ]);
  curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
  curl_setopt($ch, CURLOPT_TIMEOUT, 60); // Reduced from 180
  curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
  curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
  curl_setopt($ch, CURLOPT_NOSIGNAL, 1); // Prevent timeout issues

  $response = curl_exec($ch);
  $curl_error = curl_error($ch);
  $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
  curl_close($ch);

  if ($curl_error) {
    echo "Connection error: " . htmlspecialchars($curl_error);
    exit;
  }

  if ($http_code != 200) {
    echo "AI service error (HTTP $http_code). Please try again.";
    exit;
  }

  $data = json_decode($response, true);

  echo $data["response"] ?? "Error: No response from AI.  Please try again.";
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
        <span style="font-size: 24px;">🤖</span>
        <h5 class="mb-0 fw-bold">Stack AI Assistant</h5>
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
  <button class="btn btn-md stack-ai-button d-flex align-items-center justify-content-center gap-2 shadow" onclick="openStackAIModal()" id="stackAIButton" title="Ask Stack AI a question">
    <span class="btn-text">Ask with Stack AI</span>
    <span>💬</span>
  </button>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js" integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI" crossorigin="anonymous"></script>
  <script src="chatbot/script.js"></script>

</body>

</html>