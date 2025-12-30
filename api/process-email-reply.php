<?php

/**
 * Email Reply Webhook Handler
 * 
 * This file processes incoming email replies from users responding to verification requests.
 * Configure your email service (SendGrid, Mailgun, AWS SES, etc.) to POST to this endpoint.
 */

require_once '../config/db.php';

// Log incoming requests for debugging
$log_file = __DIR__ . '/../logs/email_replies.log';
$log_data = [
    'timestamp' => date('Y-m-d H:i:s'),
    'method' => $_SERVER['REQUEST_METHOD'],
    'post' => $_POST,
    'raw_input' => file_get_contents('php://input')
];
@file_put_contents($log_file, json_encode($log_data) . "\n", FILE_APPEND);

// Parse incoming email based on provider
$from_email = '';
$reply_text = '';

// SendGrid Inbound Parse format
if (isset($_POST['from'])) {
    // Extract email from "Name <email@domain.com>" format
    preg_match('/<(.+?)>/', $_POST['from'], $email_matches);
    $from_email = $email_matches[1] ?? $_POST['from'];
    $reply_text = $_POST['text'] ?? $_POST['html'] ?? '';
}
// Mailgun format
elseif (isset($_POST['sender'])) {
    $from_email = $_POST['sender'];
    $reply_text = $_POST['body-plain'] ?? $_POST['stripped-text'] ?? '';
}
// Generic JSON format
else {
    $data = json_decode(file_get_contents('php://input'), true);
    if ($data) {
        $from_email = $data['from'] ?? $data['sender'] ?? $data['email'] ?? '';
        $reply_text = $data['text'] ?? $data['body'] ?? $data['message'] ?? '';
    }
}

// Clean email address
$from_email = trim(strtolower($from_email));
$reply_text = trim($reply_text);

if (empty($from_email) || empty($reply_text)) {
    error_log("Email reply webhook: Missing email or text");
    http_response_code(400);
    exit;
}

// Extract verification code (6 digits)
preg_match('/\b\d{6}\b/', $reply_text, $code_matches);
$verification_code = $code_matches[0] ?? null;

if (!$verification_code) {
    error_log("Email reply webhook: No verification code found in message from $from_email");
    http_response_code(200); // Still accept but don't process
    exit;
}

// Sanitize inputs
$from_email = mysqli_real_escape_string($conn, $from_email);
$reply_text = mysqli_real_escape_string($conn, $reply_text);
$verification_code = mysqli_real_escape_string($conn, $verification_code);

// Find matching verification request
$query = "
    SELECT m.message_id, m.verification_code, u.user_name, u.email
    FROM messages m
    INNER JOIN users u ON m.user_id = u.user_id
    WHERE u.email = '$from_email' 
    AND m.verification_code = '$verification_code'
    AND m.subject LIKE '%Account Verification Request%'
    AND m.status = 'pending'
    AND m.user_response IS NULL
    ORDER BY m.created_at DESC
    LIMIT 1
";

$result = executeQuery($query);

if ($result && mysqli_num_rows($result) === 1) {
    $message = mysqli_fetch_assoc($result);

    // Update with user response
    $update_query = "
        UPDATE messages 
        SET user_response = '$reply_text',
            responded_at = NOW()
        WHERE message_id = {$message['message_id']}
    ";

    if (executeQuery($update_query)) {
        error_log("Email reply processed: User {$message['user_name']} responded to verification request");

        // Optional: Send confirmation email
        require_once '../notifications/send-email.php';
        $confirmation_subject = "Verification Response Received - BookStack";
        $confirmation_body = "
        <div style='font-family: Arial, sans-serif; padding: 20px;'>
            <h2>Thank You!</h2>
            <p>Hi {$message['user_name']},</p>
            <p>We've received your verification response. Our admin team will review it shortly.</p>
            <p>You'll be notified once your account is verified.</p>
            <br>
            <p>Best regards,<br><strong>BookStack Team</strong></p>
        </div>
        ";
        sendEmail($from_email, $confirmation_subject, $confirmation_body);

        http_response_code(200);
        echo json_encode(['status' => 'success', 'message' => 'Response recorded']);
    } else {
        error_log("Email reply webhook: Failed to update database");
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => 'Database update failed']);
    }
} else {
    error_log("Email reply webhook: No matching verification request found for $from_email with code $verification_code");
    http_response_code(200); // Accept but don't process
    echo json_encode(['status' => 'ignored', 'message' => 'No matching verification request']);
}
