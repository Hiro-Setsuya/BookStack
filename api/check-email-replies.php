<?php

/**
 * Email Reply IMAP Checker for Verification Codes
 * Actively polls Gmail inbox for replies to verification emails
 * Run this via cron job or manually to check for new replies
 */

require_once '../config/db.php';

// IMAP Configuration for Gmail
define('IMAP_HOST', '{imap.gmail.com:993/imap/ssl}INBOX');
define('IMAP_USERNAME', 'nullbyte235@gmail.com');
define('IMAP_PASSWORD', 'mije slqy qkpo gwvy'); // Gmail App Password

// Log file for debugging
$log_file = __DIR__ . '/../logs/email_replies.log';

function logMessage($message)
{
    global $log_file;
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents($log_file, "[$timestamp] $message\n", FILE_APPEND);
    error_log("Email Reply: $message");
}

// Check if running via command line or web
$is_cli = php_sapi_name() === 'cli';

if (!$is_cli) {
    // If called via web, output JSON
    header('Content-Type: application/json');
}

try {
    logMessage("Starting email reply check...");

    // Connect to IMAP
    $inbox = imap_open(IMAP_HOST, IMAP_USERNAME, IMAP_PASSWORD);

    if (!$inbox) {
        throw new Exception('Cannot connect to Gmail: ' . imap_last_error());
    }

    logMessage("Connected to Gmail successfully");

    // Search for UNREAD emails only to avoid reprocessing
    $emails = imap_search($inbox, 'UNSEEN');

    if (!$emails) {
        logMessage("No unread emails found");
        imap_close($inbox);

        if (!$is_cli) {
            echo json_encode(['status' => 'success', 'message' => 'No unread emails', 'processed' => 0]);
        }
        exit;
    }

    logMessage("Found " . count($emails) . " unread email(s)");

    $processed_count = 0;
    $updated_count = 0;

    // Process each unread email
    foreach ($emails as $email_number) {
        $processed_count++;

        // Get email overview
        $overview = imap_fetch_overview($inbox, $email_number, 0);
        $header = imap_headerinfo($inbox, $email_number);

        // Get sender email
        $from = $header->from[0];
        $sender_email = strtolower($from->mailbox . '@' . $from->host);

        // Get subject
        $subject = isset($overview[0]->subject) ? $overview[0]->subject : '';

        // Get email body - handle different MIME structures
        $structure = imap_fetchstructure($inbox, $email_number);
        $body = '';

        if (isset($structure->parts) && count($structure->parts)) {
            // Multipart email (reply with quoted text)
            for ($i = 0; $i < count($structure->parts); $i++) {
                $part = $structure->parts[$i];
                // Get text/plain part (part 1)
                if ($part->subtype == 'PLAIN') {
                    $body = imap_fetchbody($inbox, $email_number, $i + 1);

                    // Decode based on encoding
                    if ($part->encoding == 3) { // base64
                        $body = base64_decode($body);
                    } elseif ($part->encoding == 4) { // quoted-printable
                        $body = quoted_printable_decode($body);
                    }
                    break;
                }
            }
        } else {
            // Simple email
            $body = imap_fetchbody($inbox, $email_number, 1);
            if ($structure->encoding == 3) {
                $body = base64_decode($body);
            } elseif ($structure->encoding == 4) {
                $body = quoted_printable_decode($body);
            }
        }

        // Remove quoted text (anything after "On ... wrote:" or "> " lines)
        $body = preg_split('/On .* wrote:|^>/m', $body)[0];

        // Clean up body text
        $body = strip_tags($body);
        $body = trim($body);

        logMessage("Processing email from: $sender_email, Subject: $subject");
        logMessage("Body preview: " . substr($body, 0, 100));

        // Look for verification code pattern in body (CONFIRM-XXXXXX or 6 digits)
        $verification_code = null;

        // Try to find CONFIRM-XXXXXX pattern
        if (preg_match('/CONFIRM-[A-Z0-9]{2,10}/i', $body, $matches)) {
            $verification_code = strtoupper($matches[0]);
            logMessage("Found verification code: $verification_code");
        }
        // Try to find 6-digit code
        elseif (preg_match('/\b\d{6}\b/', $body, $matches)) {
            $verification_code = $matches[0];
            logMessage("Found 6-digit code: $verification_code");
        }

        if (!$verification_code) {
            logMessage("No verification code found in email body");
            // Mark as read to avoid reprocessing
            imap_setflag_full($inbox, $email_number, "\\Seen");
            continue;
        }

        // Send immediate thank you reply
        require_once '../config/mail.php';
        $thank_you_subject = "Thank You - Verification Response Received";
        $thank_you_body = "
            <h3>Thank you for your response!</h3>
            <p>We have received your verification code: <strong>$verification_code</strong></p>
            <p>Our admin team will review and verify your account shortly.</p>
            <br>
            <p>Best regards,<br>BookStack Team</p>
        ";

        sendEmail($sender_email, $thank_you_subject, $thank_you_body);
        logMessage("Immediate thank you email sent to: $sender_email");

        // Find matching pending verification request for this email
        $sender_email_escaped = mysqli_real_escape_string($GLOBALS['conn'], $sender_email);

        $query = "
            SELECT m.message_id, m.verification_code, u.user_name, u.email, m.contact_info, m.user_response
            FROM messages m
            INNER JOIN users u ON m.user_id = u.user_id
            WHERE (
                LOWER(u.email) = '$sender_email_escaped'
                OR LOWER(m.contact_info) = '$sender_email_escaped'
            )
            AND m.subject LIKE '%Account Verification Request%'
            AND m.contact_method = 'email'
            AND m.status = 'pending'
            ORDER BY m.created_at DESC
            LIMIT 1
        ";

        logMessage("Searching for pending verification from: $sender_email");

        $result = executeQuery($query);

        if ($result && mysqli_num_rows($result) === 1) {
            $message = mysqli_fetch_assoc($result);

            logMessage("Found pending request - Message ID: {$message['message_id']}, User: {$message['user_name']}");

            // Check if already processed
            if (!empty($message['user_response'])) {
                logMessage("Already processed - user_response exists: {$message['user_response']}");
                imap_setflag_full($inbox, $email_number, "\\Seen");
                continue;
            }

            // Update with user response
            $code_escaped = mysqli_real_escape_string($GLOBALS['conn'], $verification_code);

            $update_query = "
                UPDATE messages 
                SET user_response = '$code_escaped',
                    responded_at = NOW()
                WHERE message_id = {$message['message_id']}
            ";

            if (executeQuery($update_query)) {
                $updated_count++;
                logMessage("Email reply recorded: User {$message['user_name']} responded with: $verification_code");

                // Mark email as read after successful processing
                imap_setflag_full($inbox, $email_number, "\\Seen");
            } else {
                logMessage("Failed to update database for message_id: {$message['message_id']}");
            }
        } else {
            logMessage("No matching pending verification found for: $sender_email");
            // Mark as read to avoid reprocessing
            imap_setflag_full($inbox, $email_number, "\\Seen");
        }
    }
    imap_close($inbox);

    logMessage("Email processing complete. Processed: $processed_count, Updated: $updated_count");

    if (!$is_cli) {
        echo json_encode([
            'status' => 'success',
            'message' => 'Email replies processed',
            'processed' => $processed_count,
            'updated' => $updated_count
        ]);
    } else {
        echo "Email replies processed: $processed_count checked, $updated_count updated\n";
    }
} catch (Exception $e) {
    $error_message = "Error: " . $e->getMessage();
    logMessage($error_message);

    if (!$is_cli) {
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => $error_message]);
    } else {
        echo $error_message . "\n";
    }
}
