<?php
session_start();
require_once '../config/db.php';
require_once '../config/mail.php'; // Use your centralized mail config

// Admin check
if (!isset($_SESSION['admin_id']) || $_SESSION['admin_role'] !== 'admin') {
    header('Location: login.php');
    exit;
}

// Flash messages helper
function setFlash($message, $type = 'info')
{
    $_SESSION['status_message'] = $message;
    $_SESSION['status_type'] = $type;
}

// Generate verification code
function generateCode()
{
    return 'CONFIRM-' . strtoupper(bin2hex(random_bytes(4)));
}

// Send verification code via email or SMS
function sendVerificationCode($message_id, $user_id, $contact_method, $contact_info, $user_name, $conn)
{
    $verification_code = generateCode();

    // Update the message with verification code
    $stmt = $conn->prepare("UPDATE messages SET verification_code = ?, code_sent_at = NOW() WHERE message_id = ?");
    $stmt->bind_param("si", $verification_code, $message_id);
    if (!$stmt->execute()) {
        setFlash("Error generating verification code.", "danger");
        return false;
    }

    $sent_successfully = false;
    $error_details = '';

    if ($contact_method === 'email') {
        $subject = "BookStack Account Verification Code";
        $body = "
            <h3>Account Verification Required</h3>
            <p>Dear $user_name,</p>
            <p>Your verification code is: <strong>$verification_code</strong></p>
            <p>Please reply to this email with your verification code to complete the process.</p>
            <p>This code is valid for 24 hours.</p>
            <br>
            <p>Best regards,<br>BookStack Team</p>
        ";
        $sent_successfully = sendEmail($contact_info, $subject, $body);
        if (!$sent_successfully) {
            $error_details = "Email sending failed. Check mail configuration.";
        }
    } elseif ($contact_method === 'phone') {
        // Note: SMS sending requires a proper SMS gateway service
        // For now, we'll just generate the code and display it to admin
        // Users can be notified through other means (call, separate app, etc.)

        $sms_message = "BookStack Account Verification: Your code is $verification_code. Reply with this code to verify your account. Valid for 24 hours.";

        // Check if SMS gateway is configured
        $sms_config_file = __DIR__ . '/../config/sms.php';
        $sms_config = file_exists($sms_config_file) ? require($sms_config_file) : null;

        // Try to send SMS if gateway is properly configured
        if ($sms_config && !empty($sms_config['gateway_url']) && $sms_config['gateway_url'] !== 'http://192.168.18.42:8080/messages') {
            require_once '../notifications/send-sms.php';
            $result = sendSMS($contact_info, $sms_message);
            if ($result !== false) {
                $sent_successfully = true;
            } else {
                $error_details = "SMS gateway connection failed. Code generated: $verification_code";
            }
        } else {
            // SMS gateway not configured - show code to admin to manually send
            $sent_successfully = true; // Consider it "sent" since admin will handle it
            $error_details = "SMS gateway not configured. Please manually send this code to the user: $verification_code";
        }
    }

    if ($sent_successfully) {
        setFlash("Verification code sent successfully via " . strtoupper($contact_method) . " to " . htmlspecialchars($contact_info), "success");
    } else {
        $msg = "Code generated but failed to send via " . strtoupper($contact_method) . ". Code: $verification_code";
        if ($error_details) {
            $msg .= " | Error: $error_details";
        }
        setFlash($msg, "warning");
    }
}

// Verify user response - Only mark code as verified, do NOT auto-approve user
function verifyCodeMatch($message_id, $user_id, $conn)
{
    $stmt = $conn->prepare("SELECT verification_code, user_response FROM messages WHERE message_id = ?");
    $stmt->bind_param("i", $message_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $sent_code = strtoupper(trim($row['verification_code']));
        $user_reply = strtoupper(trim($row['user_response']));
        if ($sent_code === $user_reply && !empty($user_reply)) {
            // Only mark the code as verified - DO NOT approve user account yet
            $stmt1 = $conn->prepare("UPDATE messages SET code_verified = 1, status = 'read' WHERE message_id = ?");
            $stmt1->bind_param("i", $message_id);
            if ($stmt1->execute()) {
                setFlash("✓ Code matched and verified! Please use 'Approve' button to verify the user account.", "success");
            } else {
                setFlash("Error updating verification status.", "danger");
            }
        } else {
            setFlash("Code mismatch! Sent: $sent_code, User replied: $user_reply", "warning");
        }
    }
}

// Approve verification manually
function approveVerification($message_id, $user_id, $conn)
{
    $stmt1 = $conn->prepare("UPDATE users SET is_account_verified = TRUE WHERE user_id = ?");
    $stmt1->bind_param("i", $user_id);
    if ($stmt1->execute()) {
        $stmt2 = $conn->prepare("UPDATE messages SET status = 'resolved', code_verified = TRUE WHERE message_id = ?");
        $stmt2->bind_param("i", $message_id);
        $stmt2->execute();
        setFlash("User account verified successfully!", "success");
    } else {
        setFlash("Error verifying account.", "danger");
    }
}

// Reject verification
function rejectVerification($message_id, $conn)
{
    $stmt = $conn->prepare("UPDATE messages SET status = 'resolved' WHERE message_id = ?");
    $stmt->bind_param("i", $message_id);
    $stmt->execute();
    setFlash("Verification request rejected.", "info");
}

// Handle POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['send_verification_code'])) {
        sendVerificationCode(
            (int)$_POST['message_id'],
            (int)$_POST['user_id'],
            $_POST['contact_method'],
            $_POST['contact_info'],
            $_POST['user_name'],
            $conn
        );
    } elseif (isset($_POST['verify_code_match'])) {
        verifyCodeMatch((int)$_POST['message_id'], (int)$_POST['user_id'], $conn);
    } elseif (isset($_POST['approve_verification'])) {
        approveVerification((int)$_POST['message_id'], (int)$_POST['user_id'], $conn);
    } elseif (isset($_POST['reject_verification'])) {
        rejectVerification((int)$_POST['message_id'], $conn);
    }

    header('Location: manage-verification.php');
    exit;
}

// Handle refresh button click (to check emails)
if (isset($_GET['refresh'])) {
    // Get the base URL for the current site
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'];
    $base_url = $protocol . '://' . $host;

    // Use absolute URL for the API call
    $api_url = $base_url . dirname(dirname($_SERVER['SCRIPT_NAME'])) . '/api/check-email.php';

    // Use cURL to call the check-email.php script
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $api_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_MAXREDIRS, 3);

    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);

    if ($error) {
        setFlash("Curl error: " . $error, "danger");
    } else if ($http_code !== 200) {
        setFlash("HTTP error: " . $http_code . " - Response: " . substr($response, 0, 100), "danger");
    } else {
        $result = json_decode($response, true);

        if ($result && isset($result['status'])) {
            if ($result['status'] === 'success') {
                setFlash("Email check completed: " . $result['message'], "success");
            } else {
                setFlash("Email check failed: " . ($result['message'] ?? 'Unknown error'), "danger");
            }
        } else {
            setFlash("Invalid response format: " . substr($response, 0, 100), "danger");
        }
    }

    // Redirect to prevent resubmission
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

// Fetch verification requests - Only messages that were created as verification requests
$query = "
SELECT m.message_id, m.user_id, m.contact_method, m.contact_info, m.subject, m.content, m.status,
       m.created_at, m.verification_code, m.code_sent_at, m.user_response, m.responded_at, m.code_verified,
       u.user_name, u.email, u.phone_number, u.is_account_verified
FROM messages m
INNER JOIN users u ON m.user_id = u.user_id
WHERE m.subject LIKE '%Account Verification Request%'
ORDER BY CASE WHEN m.status = 'pending' THEN 0 ELSE 1 END, m.created_at DESC
";

$result = executeQuery($query);
$verification_requests = $result ? mysqli_fetch_all($result, MYSQLI_ASSOC) : [];

// Get flash messages
$statusMessage = $_SESSION['status_message'] ?? '';
$statusType = $_SESSION['status_type'] ?? 'info';

// Clear flash messages
unset($_SESSION['status_message']);
unset($_SESSION['status_type']);

$title = 'Manage Verifications';
include '../includes/head.php';
?>

<body>

    <?php $currentPage = 'verifications';
    include '../includes/admin-nav.php'; ?>
    <?php
    $success_message = ($statusType === 'success' && !empty($statusMessage)) ? $statusMessage : '';
    $error_message = ($statusType === 'danger' && !empty($statusMessage)) ? $statusMessage : '';
    $warning_message = ($statusType === 'warning' && !empty($statusMessage)) ? $statusMessage : '';
    $info_message = ($statusType === 'info' && !empty($statusMessage)) ? $statusMessage : '';
    include '../includes/notification.php';
    ?>

    <div class="mb-4">
        <div class="d-flex justify-content-between align-items-start">
            <div>
                <h5 class="fw-bold mb-0">Manage Verifications</h5>
                <p class="text-muted small mb-0">Review and approve user verification requests.</p>
            </div>
            <div>
                <a href="?refresh=1" class="btn btn-outline-primary btn-sm">
                    <i class="bi bi-envelope-check me-2"></i>Check Email
                </a>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4 text-center">
        <div class="col-12 col-md-4">
            <div class="card stat-card p-4">
                <p class="text-muted small mb-1">Pending Requests</p>
                <h4 class="fw-bold mb-0 text-warning"><?= count(array_filter($verification_requests, fn($r) => $r['status'] === 'pending')) ?></h4>
            </div>
        </div>
        <div class="col-12 col-md-4">
            <div class="card stat-card p-4">
                <p class="text-muted small mb-1">Approved</p>
                <h4 class="fw-bold mb-0 text-success"><?= count(array_filter($verification_requests, fn($r) => $r['status'] === 'resolved' && $r['is_account_verified'])) ?></h4>
            </div>
        </div>
        <div class="col-12 col-md-4">
            <div class="card stat-card p-4">
                <p class="text-muted small mb-1">Total Requests</p>
                <h4 class="fw-bold mb-0 text-secondary"><?= count($verification_requests) ?></h4>
            </div>
        </div>
    </div>

    <!-- Verification Requests -->
    <?php if (empty($verification_requests)): ?>
        <div class="card data-card border-0 shadow-sm">
            <div class="card-body">
                <div class="text-center py-5">
                    <i class="bi bi-inbox" style="font-size: 4rem; color: #d1d5db;"></i>
                    <h5 class="mt-3">No verification requests yet</h5>
                    <p class="text-muted">Verification requests from users will appear here</p>
                </div>
            </div>
        </div>
    <?php else: ?>
        <?php foreach ($verification_requests as $request):
            $name_parts = explode(' ', $request['user_name']);
            $initials = strtoupper(substr($name_parts[0], 0, 1));
            if (isset($name_parts[1])) {
                $initials .= strtoupper(substr($name_parts[1], 0, 1));
            }
        ?>
            <div class="card data-card mb-3">
                <div class="card-body p-4">
                    <div class="row">
                        <div class="col-md-8">
                            <!-- User Header -->
                            <div class="d-flex align-items-center mb-3">
                                <div class="user-avatar me-3"><?= $initials ?></div>
                                <div class="flex-grow-1">
                                    <h6 class="mb-1 fw-bold">
                                        <?= htmlspecialchars($request['user_name']) ?>
                                        <?php if ($request['is_account_verified']): ?>
                                            <i class="bi bi-patch-check-fill text-success ms-1" title="Verified"></i>
                                        <?php endif; ?>
                                    </h6>
                                    <div class="d-flex gap-2 flex-wrap">
                                        <span class="verification-badge <?= $request['status'] === 'pending' ? 'badge-pending' : 'badge-resolved' ?>">
                                            <i class="bi bi-<?= $request['status'] === 'pending' ? 'hourglass-split' : 'check-circle' ?> me-1"></i>
                                            <?= ucfirst($request['status']) ?>
                                        </span>
                                        <span class="verification-badge" style="background: #dbeafe; color: #1e40af;">
                                            <i class="bi bi-<?= $request['contact_method'] === 'email' ? 'envelope' : 'phone' ?> me-1"></i>
                                            <?= ucfirst($request['contact_method']) ?>
                                        </span>
                                    </div>
                                </div>
                                <small class="text-muted">
                                    <?= date('M d, Y', strtotime($request['created_at'])) ?>
                                </small>
                            </div>

                            <!-- User Information -->
                            <div class="mb-3">
                                <div class="info-row">
                                    <span class="info-label"><i class="bi bi-person-badge me-2"></i>User ID:</span>
                                    <span class="info-value">#<?= $request['user_id'] ?></span>
                                </div>
                                <div class="info-row">
                                    <span class="info-label"><i class="bi bi-envelope me-2"></i>Email:</span>
                                    <span class="info-value"><?= htmlspecialchars($request['email']) ?></span>
                                </div>
                                <div class="info-row">
                                    <span class="info-label"><i class="bi bi-phone me-2"></i>Phone:</span>
                                    <span class="info-value"><?= htmlspecialchars($request['phone_number'] ?: 'Not provided') ?></span>
                                </div>
                            </div>

                            <!-- Verification Details -->
                            <div class="mb-3">
                                <label class="form-label fw-bold small text-muted">VERIFICATION DETAILS</label>
                                <div class="bg-light p-3 rounded">
                                    <pre class="mb-0" style="white-space: pre-wrap; font-size: 0.875rem;"><?= htmlspecialchars($request['content']) ?></pre>
                                </div>
                            </div>

                            <!-- Code Sent to User -->
                            <?php if (!empty($request['verification_code'])): ?>
                                <div class="mb-3">
                                    <label class="form-label fw-bold small text-muted">CODE SENT TO USER</label>
                                    <div class="verification-code-box">
                                        <?= htmlspecialchars($request['verification_code']) ?>
                                    </div>
                                    <small class="text-muted">
                                        <i class="bi bi-clock"></i> Sent: <?= date('M d, Y h:i A', strtotime($request['code_sent_at'])) ?> via <?= htmlspecialchars($request['contact_info']) ?>
                                    </small>
                                </div>
                            <?php endif; ?>

                            <!-- User Response -->
                            <?php if (!empty($request['user_response'])): ?>
                                <?php
                                $sent_code = strtoupper(trim($request['verification_code']));
                                $user_reply = strtoupper(trim($request['user_response']));
                                $codes_match = ($sent_code === $user_reply);
                                ?>
                                <div>
                                    <label class="form-label fw-bold small text-muted">USER RESPONSE</label>
                                    <div class="p-3 rounded" style="background: <?= $codes_match ? '#f0fdf4' : '#fef3c7' ?>; border: 2px solid <?= $codes_match ? '#10b981' : '#f59e0b' ?>;">
                                        <p class="mb-2 fw-bold" style="font-family: 'Courier New', monospace; color: <?= $codes_match ? '#10b981' : '#f59e0b' ?>;">
                                            <?= nl2br(htmlspecialchars($request['user_response'])) ?>
                                        </p>
                                        <small class="text-muted">
                                            <i class="bi bi-clock"></i> <?= date('M d, Y h:i A', strtotime($request['responded_at'])) ?>
                                            <?php if ($request['code_verified']): ?>
                                                <span class="badge bg-success ms-2"><i class="bi bi-check-circle-fill"></i> Verified</span>
                                            <?php elseif ($codes_match): ?>
                                                <span class="badge bg-success ms-2"><i class="bi bi-check-circle"></i> Match!</span>
                                            <?php else: ?>
                                                <span class="badge bg-warning ms-2"><i class="bi bi-exclamation-triangle"></i> No Match</span>
                                            <?php endif; ?>
                                        </small>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- Action Buttons -->
                        <div class="col-md-4">
                            <?php if ($request['status'] === 'pending' && !$request['is_account_verified']): ?>
                                <div class="d-grid gap-2">
                                    <!-- Verify Code Match Button (does NOT approve user) -->
                                    <?php if (!empty($request['verification_code']) && !empty($request['user_response']) && !$request['code_verified']): ?>
                                        <form method="POST" action="">
                                            <input type="hidden" name="message_id" value="<?= $request['message_id'] ?>">
                                            <input type="hidden" name="user_id" value="<?= $request['user_id'] ?>">
                                            <button type="submit" name="verify_code_match" class="btn btn-info w-100" onclick="return confirm('Verify if user response matches?\n\nSent: <?= htmlspecialchars($request['verification_code']) ?>\nReply: <?= htmlspecialchars($request['user_response']) ?>\n\nNote: This only verifies the code. You still need to click Approve to activate the account.')">
                                                <i class="bi bi-shield-check me-2"></i>Verify Code Match
                                            </button>
                                        </form>
                                    <?php endif; ?>

                                    <!-- Send/Resend Code Button -->
                                    <?php if (empty($request['verification_code'])): ?>
                                        <form method="POST" action="">
                                            <input type="hidden" name="message_id" value="<?= $request['message_id'] ?>">
                                            <input type="hidden" name="user_id" value="<?= $request['user_id'] ?>">
                                            <input type="hidden" name="contact_method" value="<?= $request['contact_method'] ?>">
                                            <input type="hidden" name="contact_info" value="<?= $request['contact_info'] ?>">
                                            <input type="hidden" name="user_name" value="<?= $request['user_name'] ?>">
                                            <button type="submit" name="send_verification_code" class="btn btn-warning w-100" onclick="return confirm('Send verification code to <?= htmlspecialchars($request['contact_info']) ?>?')">
                                                <i class="bi bi-send-fill me-2"></i>Send Code
                                            </button>
                                        </form>
                                    <?php elseif (!empty($request['user_response'])): ?>
                                        <div class="alert alert-success mb-2 small">
                                            <i class="bi bi-check-circle-fill me-1"></i> User responded
                                        </div>
                                    <?php else: ?>
                                        <div class="alert alert-info mb-2 small">
                                            <i class="bi bi-info-circle-fill me-1"></i> Waiting for response
                                        </div>
                                    <?php endif; ?>

                                    <!-- Approve Button -->
                                    <form method="POST" action="">
                                        <input type="hidden" name="message_id" value="<?= $request['message_id'] ?>">
                                        <input type="hidden" name="user_id" value="<?= $request['user_id'] ?>">
                                        <button type="submit" name="approve_verification" class="btn btn-primary w-100" onclick="return confirm('Manually approve <?= htmlspecialchars($request['user_name']) ?>?')">
                                            <i class="bi bi-check-circle me-2"></i>Approve
                                        </button>
                                    </form>

                                    <!-- Reject Button -->
                                    <form method="POST" action="">
                                        <input type="hidden" name="message_id" value="<?= $request['message_id'] ?>">
                                        <button type="submit" name="reject_verification" class="btn btn-danger w-100" onclick="return confirm('Reject this request?')">
                                            <i class="bi bi-x-circle me-2"></i>Reject
                                        </button>
                                    </form>
                                </div>
                            <?php elseif ($request['is_account_verified']): ?>
                                <div class="alert alert-success text-center">
                                    <i class="bi bi-shield-check fs-2 d-block mb-2"></i>
                                    <strong>Verified</strong>
                                </div>
                            <?php else: ?>
                                <div class="alert alert-secondary text-center">
                                    <i class="bi bi-info-circle fs-2 d-block mb-2"></i>
                                    <strong>Processed</strong>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
    </main>
    </div>
    </div>

    <?php include '../includes/admin-footer.php'; ?>