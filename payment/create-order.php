<?php
session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/paypal.php';
header('Content-Type: application/json');

if (!isset($_SESSION['checkout_total'])) {
    http_response_code(400);
    echo json_encode(["error" => "No checkout total found in session"]);
    exit;
}

$amount = $_SESSION['checkout_total'];

$accessToken = generateAccessToken(PAYPAL_CLIENT_ID, PAYPAL_SECRET);
if (!$accessToken) {
    http_response_code(500);
    echo json_encode(["error" => "Failed to generate access token"]);
    exit;
}

$data = [
    "intent" => "CAPTURE",
    "purchase_units" => [[
        "amount" => [
            "currency_code" => "PHP",
            "value" => number_format((float)$amount, 2, '.', '')
        ],
        "description" => "BookStack Purchase"
    ]]
];

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "https://api-m.sandbox.paypal.com/v2/checkout/orders");
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Content-Type: application/json",
    "Authorization: Bearer $accessToken"
]);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

$response = curl_exec($ch);
if (!$response) {
    http_response_code(500);
    echo json_encode(["error" => "CURL ERROR: " . curl_error($ch)]);
    curl_close($ch);
    exit;
}
curl_close($ch);

$responseData = json_decode($response, true);
if (isset($responseData['id'])) {
    echo json_encode(['id' => $responseData['id']]);
} else {
    http_response_code(500);
    echo json_encode(["error" => "PayPal API Order Creation Failed", "details" => $responseData]);
}
exit;
