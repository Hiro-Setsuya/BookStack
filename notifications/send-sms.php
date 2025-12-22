<?php

/**
 * SMS Helper Functions
 * 
 * This file provides reusable SMS sending functionality.
 * Simply include this file and call sendSMS() whenever you need to send a message.
 */

/**
 * Send SMS message via gateway
 * 
 * @param string $recipient Phone number (will be formatted to 63XXXXXXXXX for Philippines)
 * @param string $message The message content to send
 * @return mixed Response from gateway on success, false on failure
 * 
 * @example
 * require_once 'notifications/send-sms.php';
 * $result = sendSMS('639123456789', 'Your OTP is: 123456');
 */
function sendSMS($recipient, $message)
{
    // Load SMS configuration
    $smsConfig = require __DIR__ . '/../config/sms.php';

    // Validate inputs
    if (empty($recipient)) {
        error_log('SMS Error: Empty recipient phone number');
        return false;
    }

    if (empty($message)) {
        error_log('SMS Error: Empty message content');
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

    // Prepare payload
    $payload = [
        "phoneNumbers" => [$recipient],
        "message" => $message,
    ];

    // Prepare headers with authentication
    $headers = [
        'Content-Type: application/json',
        'Authorization: Basic ' . base64_encode(
            $smsConfig['username'] . ':' . $smsConfig['password']
        ),
    ];

    // Configure HTTP request
    $options = [
        'http' => [
            'method'  => 'POST',
            'header'  => implode("\r\n", $headers),
            'content' => json_encode($payload),
            'timeout' => 10,
            'ignore_errors' => true
        ]
    ];

    // Send request
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
