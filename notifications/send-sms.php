<?php
$gatewayUrl = "http://192.168.18.39:8080/messages"; // full endpoint
$username = "sms";
$password = "lickmyez";
$recipient = "+639123456789"; // Replace with the actual recipient phone number

// Generate OTP
$otp = rand(100000, 999999);
$message = "Your OTP code is: " . $otp . ". Do not share this code with anyone.";

// Prepare payload
$payload = [
    "phoneNumbers" => [$recipient],
    "message" => $message,
];

$options = [
    'http' => [
        'method' => 'POST',
        'header' => [
            'Content-Type: application/json',
            'Authorization: Basic ' . base64_encode("$username:$password"),
        ],
        'content' => json_encode($payload),
    ]
];

$context = stream_context_create($options);
$response = file_get_contents($gatewayUrl, false, $context);

// Display results
echo "<h3>OTP:</h3>";
echo "<p>Recipient: <strong>$recipient</strong></p>";
echo "<p>OTP Code: <strong>$otp</strong></p>";
echo "<h4>Response:</h4>";
echo "<pre>" . htmlspecialchars($response) . "</pre>";
