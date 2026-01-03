<?php

/**
 * SMS Reply Webhook Handler
 * 
 * This file processes incoming SMS replies from users responding to verification requests.
 * Configure your SMS gateway (Twilio, Vonage, etc.) to POST to this endpoint.
 */

require_once '../config/db.php';

// Log incoming requests for debugging
$log_file = __DIR__ . '/../logs/sms_replies.log';
$log_data = [
    'timestamp' => date('Y-m-d H:i:s'),
    'method' => $_SERVER['REQUEST_METHOD'],
    'post' => $_POST,
    'get' => $_GET,
    'raw_input' => file_get_contents('php://input'),
    'headers' => getallheaders()
];
$log_entry = json_encode($log_data) . "\n";
@file_put_contents($log_file, $log_entry, FILE_APPEND);

// Also log to PHP error log for easier debugging
error_log("SMS Webhook received: " . json_encode(['method' => $_SERVER['REQUEST_METHOD'], 'from' => $_POST['from'] ?? 'unknown']));

// Parse incoming SMS based on provider
$from_number = '';
$message_body = '';

// SMS Forwarder App format (Priority - most common for personal use)
$raw_input = file_get_contents('php://input');
$json_data = json_decode($raw_input, true);

if ($json_data && isset($json_data['from']) && isset($json_data['text'])) {
    // SMS Forwarder JSON format
    $from_number = $json_data['from'];
    $message_body = $json_data['text'];
}
// SMS Forwarder alternative format
elseif (isset($_POST['from']) && isset($_POST['text'])) {
    $from_number = $_POST['from'];
    $message_body = $_POST['text'];
}
// Twilio format
elseif (isset($_POST['From']) && isset($_POST['Body'])) {
    $from_number = $_POST['From'];
    $message_body = $_POST['Body'];
}
// Vonage/Nexmo format
elseif (isset($_GET['msisdn']) && isset($_GET['text'])) {
    $from_number = $_GET['msisdn'];
    $message_body = $_GET['text'];
}
// Generic POST format
elseif (isset($_POST['from']) && isset($_POST['message'])) {
    $from_number = $_POST['from'];
    $message_body = $_POST['message'];
}

// Normalize phone number (remove non-digits)
$from_number = preg_replace('/[^0-9]/', '', $from_number);
$message_body = trim($message_body);

if (empty($from_number) || empty($message_body)) {
    error_log("SMS reply webhook: Missing phone number or message");
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Missing phone number or message']);
    exit;
}

error_log("SMS Webhook: Processing reply from $from_number: $message_body");

// Sanitize inputs
$message_body_escaped = mysqli_real_escape_string($conn, $message_body);

// Find any pending verification request from this phone number
$query = "
    SELECT m.message_id, m.verification_code, u.user_name, u.phone_number, m.contact_info
    FROM messages m
    INNER JOIN users u ON m.user_id = u.user_id
    WHERE (
        REPLACE(REPLACE(REPLACE(u.phone_number, '+', ''), '-', ''), ' ', '') LIKE '%$from_number%'
        OR REPLACE(REPLACE(REPLACE(m.contact_info, '+', ''), '-', ''), ' ', '') LIKE '%$from_number%'
    )
    AND m.subject LIKE '%Account Verification Request%'
    AND m.contact_method = 'phone'
    AND m.status = 'pending'
    ORDER BY m.created_at DESC
    LIMIT 1
";

error_log("SMS Webhook: Running query to find pending request for $from_number");

$result = executeQuery($query);

if ($result && mysqli_num_rows($result) === 1) {
    $message = mysqli_fetch_assoc($result);

    error_log("SMS Webhook: Found matching request - Message ID: {$message['message_id']}, User: {$message['user_name']}");

    // Update with user response (store whatever they sent)
    $update_query = "
        UPDATE messages 
        SET user_response = '$message_body_escaped',
            responded_at = NOW()
        WHERE message_id = {$message['message_id']}
    ";

    if (executeQuery($update_query)) {
        error_log("SMS reply recorded: User {$message['user_name']} responded to verification request with: $message_body");

        // Send thank you confirmation SMS
        require_once '../notifications/send-sms.php';
        $confirmation = "Thank you, {$message['user_name']}! Your verification response has been received. Our admin will review it shortly.\n\n- BookStack Team";
        sendSMS($from_number, $confirmation);

        http_response_code(200);
        echo json_encode([
            'status' => 'success',
            'message' => 'Response recorded successfully',
            'message_id' => $message['message_id'],
            'user' => $message['user_name']
        ]);

        // For Twilio, send TwiML response
        if (isset($_POST['From']) && strpos($_SERVER['HTTP_USER_AGENT'], 'TwilioProxy') !== false) {
            header('Content-Type: text/xml');
            echo '<?xml version="1.0" encoding="UTF-8"?>
            <Response>
                <Message>' . htmlspecialchars($confirmation) . '</Message>
            </Response>';
        }
    } else {
        error_log("SMS reply webhook: Failed to update database for message_id: {$message['message_id']}");
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => 'Database update failed']);
    }
} else {
    $row_count = $result ? mysqli_num_rows($result) : 0;
    error_log("SMS reply webhook: No matching pending verification found for $from_number (Found $row_count rows)");

    // Send polite response even if no match found
    require_once '../notifications/send-sms.php';
    sendSMS($from_number, "Thank you for your message! If you're trying to verify your BookStack account, please make sure you have a pending verification request first.\n\n- BookStack Team");

    http_response_code(200);
    echo json_encode([
        'status' => 'no_match',
        'message' => 'No pending verification request found',
        'phone' => $from_number,
        'rows_found' => $row_count
    ]);
}
