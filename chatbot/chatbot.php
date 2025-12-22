<?php
// --- BACKEND:  Handle AJAX request for Stack AI Chatbot ---
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['message'])) {

  $message = $_POST['message'];

  // Load FAQ data
  $data_file = __DIR__ . '/data. txt';
  if (file_exists($data_file)) {
    $data = file_get_contents($data_file);
  } else {
    $data = "No reference data available. ";
  }

  // System prompt for ebook chatbot
  $prompt =
    "You are Stack AI, a friendly and helpful AI assistant for BookStack, an ebook e-commerce platform. 

  You have access to comprehensive information about our ebook store below.  

  Important guidelines:
  • Answer questions ONLY based on the provided BookStack information.  
  • If the user's question is related to BookStack services, use ONLY the dataset to answer.
  • If the dataset does NOT contain the needed information, respond with:  \"I don't have information about that.  Please contact our support team at support@bookstack.com for more details.\"
  • Always be friendly, professional, and helpful.
  • Do NOT mention or reference the dataset unless the user directly asks about it.
  • Keep responses natural, friendly, and concise (under 150 words when possible).
  • If user asks about account issues, direct them to support@bookstack.com or phone 1-800-BOOKSTACK.  
  • Suggest relevant ebooks or categories when appropriate.  
  • For technical issues, provide step-by-step solutions when possible.

  --- BEGIN BOOKSTACK INFORMATION ---
  $data
  --- END BOOKSTACK INFORMATION ---

  User Question: $message";

  // Prepare data for Ollama
  $payload = json_encode([
    "model" => "llama3.2: latest",
    "prompt" => $prompt,
    "stream" => false
  ]);

  // Send to Ollama API
  $ch = curl_init("http://127.0.0.1:11434/api/generate");
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
  curl_setopt($ch, CURLOPT_POST, true);
  curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Content-Type: application/json"
  ]);
  curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
  curl_setopt($ch, CURLOPT_TIMEOUT, 60);

  $response = curl_exec($ch);
  $curl_error = curl_error($ch);
  curl_close($ch);

  if ($curl_error) {
    echo "Sorry, I'm having trouble connecting.  Please try again or contact support@bookstack.com";
    exit;
  }

  $data = json_decode($response, true);

  echo $data["response"] ?? "Error:  No response from AI.  Please try again.";
  exit;
}
?>

<! doctype html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Stack AI - ChatBot</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
  <link rel="stylesheet" href="style.css">
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
  <button class="btn btn-lg stack-ai-button d-flex align-items-center justify-content-center gap-2 shadow" onclick="openStackAIModal()" id="stackAIButton" title="Ask Stack AI a question">
    <span class="btn-text">Ask with Stack AI</span>
    <span>💬</span>
  </button>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js" integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI" crossorigin="anonymous"></script>
  <script src="script.js"></script>

</body>

</html>