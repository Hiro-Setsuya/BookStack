<?php

/**
 * SMS Reply Webhook Handler for SMS Forwarder App
 * 
 * This webhook processes incoming SMS from SMS Forwarder app and handles:
 * 1. Account verification responses
 * 2. Password reset verification codes
 * 3. General customer support messages
 * 
 * SMS Forwarder App Setup:
 * 1. Install SMS Forwarder app on Android phone
 * 2. Configure webhook URL: https://yourdomain.com/api/process-sms-reply.php
 * 3. Set method to POST
 * 4. Enable JSON format
 * 5. Test with a sample SMS
 */

require_once '../config/db.php';

// Enable error logging
ini_set('display_errors', 0);
ini_set('log_errors', 1);
error_reporting(E_ALL);

// Log all incoming requests for debugging
$log_file = __DIR__ . '/../logs/sms_webhook.log';
if (!file_exists(dirname($log_file))) {
    @mkdir(dirname($log_file), 0755, true);
}

$log_data = [
    'timestamp' => date('Y-m-d H:i:s'),
    'method' => $_SERVER['REQUEST_METHOD'],
    'remote_addr' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
    'post' => $_POST,
    'get' => $_GET,
    'raw_input' => file_get_contents('php://input'),
    'headers' => function_exists('getallheaders') ? getallheaders() : []
];
@file_put_contents($log_file, json_encode($log_data, JSON_PRETTY_PRINT) . "\n\n", FILE_APPEND);

// Parse incoming SMS data from SMS Forwarder app
$raw_input = file_get_contents('php://input');
$json_data = json_decode($raw_input, true);

$from_number = '';
$message_body = '';
$received_at = date('Y-m-d H:i:s');

// SMS Forwarder App formats
if ($json_data) {
    // Format 1: {"from": "639123456789", "text": "message content"}
    if (isset($json_data['from']) && isset($json_data['text'])) {
        $from_number = $json_data['from'];
        $message_body = $json_data['text'];
    }
    // Format 2: {"phone": "639123456789", "message": "message content"}
    elseif (isset($json_data['phone']) && isset($json_data['message'])) {
        $from_number = $json_data['phone'];
        $message_body = $json_data['message'];
    }
}
// Fallback to POST parameters
elseif (isset($_POST['from']) && isset($_POST['text'])) {
    $from_number = $_POST['from'];
    $message_body = $_POST['text'];
} elseif (isset($_POST['phone']) && isset($_POST['message'])) {
    $from_number = $_POST['phone'];
    $message_body = $_POST['message'];
}

// Normalize phone number (convert to 639XXXXXXXXX format)
$from_number = preg_replace('/[^0-9]/', '', $from_number);
if (preg_match('/^0/', $from_number)) {
    $from_number = '63' . substr($from_number, 1);
}
$message_body = trim($message_body);

// Validate inputs
if (empty($from_number) || empty($message_body)) {
    error_log("SMS Webhook Error: Missing data - From: $from_number, Message: $message_body");
    http_response_code(400);
    echo json_encode([
        'status' => 'error',
        'message' => 'Missing phone number or message content'
    ]);
    exit;
}

error_log("SMS Webhook: Received message from $from_number: $message_body");

// Sanitize message for database
$message_body_escaped = mysqli_real_escape_string($conn, $message_body);
$message_upper = strtoupper($message_body);

// ==================================================
// SCENARIO 1: Password Reset Verification Code
// ==================================================
// Check if this is a verification code for password reset (6-digit code)
if (preg_match('/^\d{6}$/', $message_body)) {
    $verification_code = $message_body;

    // Find matching password reset request
    $reset_query = "
        SELECT m.message_id, m.user_id, u.user_name, u.email
        FROM messages m
        INNER JOIN users u ON m.user_id = u.user_id
        WHERE m.verification_code = '$verification_code'
        AND m.subject LIKE '%Password Reset%'
        AND m.contact_method = 'phone'
        AND m.status = 'pending'
        AND m.code_sent_at >= DATE_SUB(NOW(), INTERVAL 15 MINUTE)
        AND REPLACE(REPLACE(REPLACE(u.phone_number, '+', ''), '-', ''), ' ', '') = '$from_number'
        LIMIT 1
    ";

    $reset_result = executeQuery($reset_query);

    if ($reset_result && mysqli_num_rows($reset_result) > 0) {
        $reset_data = mysqli_fetch_assoc($reset_result);

        // Mark code as verified
        $update_query = "
            UPDATE messages 
            SET code_verified = TRUE,
                user_response = '$message_body_escaped',
                responded_at = NOW(),
                status = 'read'
            WHERE message_id = {$reset_data['message_id']}
        ";

        if (executeQuery($update_query)) {
            error_log("SMS Webhook: Password reset code verified for user {$reset_data['user_name']}");

            // Send confirmation SMS
            require_once '../notifications/send-sms.php';
            sendSMS($from_number, "✓ Verification code confirmed! You can now reset your password.\n\n- BookStack");

            http_response_code(200);
            echo json_encode([
                'status' => 'success',
                'type' => 'password_reset_verified',
                'user' => $reset_data['user_name']
            ]);
            exit;
        }
    }
}

// ==================================================
// SCENARIO 2: Account Verification Response
// ==================================================
$query = "
    SELECT m.message_id, m.user_id, m.verification_code, u.user_name, u.email, u.phone_number, m.contact_info
    FROM messages m
    INNER JOIN users u ON m.user_id = u.user_id
    WHERE (
        REPLACE(REPLACE(REPLACE(u.phone_number, '+', ''), '-', ''), ' ', '') = '$from_number'
        OR REPLACE(REPLACE(REPLACE(m.contact_info, '+', ''), '-', ''), ' ', '') = '$from_number'
    )
    AND m.subject LIKE '%Account Verification Request%'
    AND m.contact_method = 'phone'
    AND m.status IN ('pending', 'read')
    AND m.verification_code IS NOT NULL
    AND m.code_verified = FALSE
    ORDER BY m.created_at DESC
    LIMIT 1
";

$result = executeQuery($query);

if ($result && mysqli_num_rows($result) === 1) {
    $message = mysqli_fetch_assoc($result);

    // Check if response matches verification code (auto-verify)
    $sent_code = strtoupper(trim($message['verification_code']));
    $user_reply = strtoupper(trim($message_body));
    $code_matches = ($sent_code === $user_reply);

    // Update with user response
    $update_query = "
        UPDATE messages 
        SET user_response = '$message_body_escaped',
            responded_at = '$received_at',
            status = 'read'";

    // If code matches, auto-verify
    if ($code_matches) {
        $update_query .= ", code_verified = TRUE";

        // Also verify the user account
        $verify_user_query = "UPDATE users SET is_account_verified = TRUE WHERE user_id = {$message['user_id']}";
        executeQuery($verify_user_query);

        error_log("SMS Webhook: Code matched! Auto-verified user {$message['user_name']} (ID: {$message['user_id']})");
    }

    $update_query .= " WHERE message_id = {$message['message_id']}";

    if (executeQuery($update_query)) {
        error_log("SMS Webhook: Account verification response recorded for {$message['user_name']}");

        // Send confirmation SMS
        require_once '../notifications/send-sms.php';
        if ($code_matches) {
            sendSMS($from_number, "✅ Verification successful, {$message['user_name']}! Your account has been verified. You can now access all features.\n\n- BookStack");
        } else {
            sendSMS($from_number, "Thank you, {$message['user_name']}! 📝 Your response has been received. Our admin will review it shortly.\n\n- BookStack");
        }

        http_response_code(200);
        echo json_encode([
            'status' => 'success',
            'type' => 'account_verification',
            'message' => $code_matches ? 'Code verified automatically' : 'Response recorded successfully',
            'message_id' => $message['message_id'],
            'user' => $message['user_name'],
            'auto_verified' => $code_matches
        ]);
        exit;
    }
}

// ==================================================
// SCENARIO 3: General Customer Support Message
// ==================================================
// ==================================================
// SCENARIO 3: General Customer Support Message
// ==================================================
// Try to find user by phone number
$user_query = "
    SELECT user_id, user_name, email 
    FROM users 
    WHERE REPLACE(REPLACE(REPLACE(phone_number, '+', ''), '-', ''), ' ', '') = '$from_number'
    LIMIT 1
";
$user_result = executeQuery($user_query);
$user_data = $user_result && mysqli_num_rows($user_result) > 0 ? mysqli_fetch_assoc($user_result) : null;

// Create a new message entry for admin to see
$subject = $user_data ? "Customer Message from {$user_data['user_name']}" : "General SMS Inquiry";
$user_id = $user_data ? $user_data['user_id'] : null;
$user_id_sql = $user_id ? $user_id : 'NULL';

$insert_query = "
    INSERT INTO messages (user_id, contact_method, contact_info, subject, content, status, created_at)
    VALUES (
        $user_id_sql,
        'phone',
        '$from_number',
        '$subject',
        '$message_body_escaped',
        'pending',
        '$received_at'
    )
";

if (executeQuery($insert_query)) {
    $message_id = mysqli_insert_id($conn);
    error_log("SMS Webhook: General message saved (ID: $message_id) from $from_number");

    // Send auto-reply
    require_once '../notifications/send-sms.php';
    $reply = $user_data
        ? "Hi {$user_data['user_name']}! 👋 Thanks for reaching out. We've received your message and will get back to you soon.\n\n- BookStack Support"
        : "Hello! Thanks for contacting BookStack. 📚 We've received your message and will respond shortly.\n\n- BookStack Support";

    sendSMS($from_number, $reply);

    http_response_code(200);
    echo json_encode([
        'status' => 'success',
        'type' => 'customer_support',
        'message' => 'Message saved successfully',
        'message_id' => $message_id,
        'user' => $user_data['user_name'] ?? 'Guest'
    ]);
} else {
    error_log("SMS Webhook: Failed to save message from $from_number");
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Failed to save message'
    ]);
}
