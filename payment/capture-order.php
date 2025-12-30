<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../config/paypal.php';

$orderId = $_GET['orderID'] ?? null;
if (!$orderId) {
    http_response_code(400);
    echo json_encode(["error" => "Missing orderID"]);
    exit;
}

$accessToken = generateAccessToken(PAYPAL_CLIENT_ID, PAYPAL_SECRET);
if (!$accessToken) {
    http_response_code(500);
    echo json_encode(["error" => "Failed to generate access token"]);
    exit;
}

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "https://api-m.sandbox.paypal.com/v2/checkout/orders/$orderId/capture");
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Content-Type: application/json",
    "Authorization: Bearer $accessToken"
]);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

$captureResponse = curl_exec($ch);
if (!$captureResponse) {
    http_response_code(500);
    echo json_encode(["error" => "CURL CAPTURE ERROR: " . curl_error($ch)]);
    curl_close($ch);
    exit;
}

curl_close($ch);
$responseData = json_decode($captureResponse, true);

if (isset($responseData['status']) && ($responseData['status'] === 'COMPLETED' || $responseData['status'] === 'COMPLETED')) {
    echo json_encode([
        "status" => "success",
        "orderID" => $orderId,
        "payer" => $responseData['payer'] ?? []
    ]);
} else {
    http_response_code(500);
    echo json_encode([
        "status" => "failed",
        "details" => $responseData
    ]);
}

exit;
