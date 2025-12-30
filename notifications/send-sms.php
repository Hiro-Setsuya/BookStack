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

    // Try cURL first (more reliable), fall back to file_get_contents
    if (function_exists('curl_init')) {
        $ch = curl_init($smsConfig['gateway_url']);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($response === false || !empty($curlError)) {
            error_log('SMS Error (cURL): Failed to send SMS to ' . $recipient);
            error_log('cURL Error: ' . $curlError);
            error_log('Gateway URL: ' . $smsConfig['gateway_url']);
            error_log('Payload: ' . json_encode($payload));
            return false;
        }

        if ($httpCode < 200 || $httpCode >= 300) {
            error_log('SMS Error: Gateway returned status code ' . $httpCode);
            error_log('Response: ' . $response);
            return false;
        }

        error_log('SMS Success: Sent to ' . $recipient . ' (HTTP ' . $httpCode . ')');
        return $response;
    }

    // Fallback to file_get_contents if cURL is not available
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
        $error = error_get_last();
        error_log('SMS Error: Failed to send SMS to ' . $recipient);
        error_log('Gateway URL: ' . $smsConfig['gateway_url']);
        error_log('Error details: ' . ($error ? $error['message'] : 'Unknown error'));
        error_log('Payload: ' . json_encode($payload));
        return false;
    }

    // Check HTTP response code
    if (isset($http_response_header)) {
        $status_line = $http_response_header[0];
        preg_match('/\d{3}/', $status_line, $matches);
        $status_code = isset($matches[0]) ? (int)$matches[0] : 0;

        if ($status_code < 200 || $status_code >= 300) {
            error_log('SMS Error: Gateway returned status code ' . $status_code);
            error_log('Response: ' . $response);
            return false;
        }
    }

    error_log('SMS Success: Sent to ' . $recipient);
    return $response;
}
