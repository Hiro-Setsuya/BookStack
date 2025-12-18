<?php

$smsConfig = require __DIR__ . '/../config/sms.php';

function sendSMS($recipient, $message)
{
    global $smsConfig;

    // Validate phone number
    if (empty($recipient)) {
        error_log('SMS Error: Empty recipient phone number');
        return false;
    }

    // Ensure phone number starts with 63 for Philippines
    $recipient = preg_replace('/[^0-9]/', '', $recipient);
    if (!preg_match('/^63/', $recipient)) {
        if (preg_match('/^0/', $recipient)) {
            $recipient = '63' . substr($recipient, 1);
        } else {
            $recipient = '63' . $recipient;
        }
    }

    $payload = [
        "phoneNumbers" => [$recipient],
        "message" => $message,
    ];

    $headers = [
        'Content-Type: application/json',
        'Authorization: Basic ' . base64_encode(
            $smsConfig['username'] . ':' . $smsConfig['password']
        ),
    ];

    $options = [
        'http' => [
            'method'  => 'POST',
            'header'  => implode("\r\n", $headers),
            'content' => json_encode($payload),
            'timeout' => 10,
            'ignore_errors' => true
        ]
    ];

    $context = stream_context_create($options);
    $response = @file_get_contents($smsConfig['gateway_url'], false, $context);

    // Log for debugging
    if ($response === false) {
        error_log('SMS Error: Failed to send SMS to ' . $recipient);
        error_log('Gateway URL: ' . $smsConfig['gateway_url']);
        return false;
    }

    error_log('SMS Success: Sent to ' . $recipient);
    return $response;
}
