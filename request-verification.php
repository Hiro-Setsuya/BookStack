<?php
session_start();
require_once 'config/db.php';
require_once 'notifications/send-email.php';
require_once 'notifications/send-sms.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];

// Get status messages from session (after redirect)
$statusMessage = $_SESSION['status_message'] ?? '';
$statusType = $_SESSION['status_type'] ?? '';

// Clear status messages from session
unset($_SESSION['status_message']);
unset($_SESSION['status_type']);

// Get user data
$query = "SELECT user_id, user_name, email, phone_number, is_account_verified FROM users WHERE user_id = $user_id";
$result = executeQuery($query);
$user = mysqli_fetch_assoc($result);

// Check if already verified
if ($user['is_account_verified']) {
    header('Location: profile.php');
    exit;
}

// Handle verification request submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['request_verification'])) {
    $method = mysqli_real_escape_string($conn, $_POST['verification_method']);

    if ($method === 'email') {
        $contact_info = $user['email'];
        $verification_code = strtoupper(substr(md5(uniqid(rand(), true)), 0, 6));

        // Send verification email
        $subject = "Account Verification Request - BookStack";
        $message = "
        <!DOCTYPE html>
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: linear-gradient(135deg, #2ecc71 0%, #27ae60 100%); color: white; padding: 30px; text-align: center; border-radius: 10px 10px 0 0; }
                .content { background: #f9f9f9; padding: 30px; border-radius: 0 0 10px 10px; }
                .code-box { border: 2px solid #2ecc71; border-radius: 8px; padding: 20px; text-align: center; margin: 20px 0; }
                .verify-code { font-size: 28px; font-weight: bold; color: #2ecc71; letter-spacing: 4px; }
                .footer { text-align: center; margin-top: 20px; color: #666; font-size: 12px; }
                .btn { display: inline-block; padding: 12px 30px; background: #2ecc71; color: white; text-decoration: none; border-radius: 5px; margin: 20px 0; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1 style='margin: 0;'>BookStack</h1>
                    <p style='margin: 10px 0 0 0;'>Account Verification Request</p>
                </div>
                <div class='content'>
                    <p>Hi {$user['user_name']},</p>
                    <p>You have requested to verify your BookStack account. Please reply to this email with the following confirmation code:</p>
                    
                    <div class='code-box'>
                        <div class='verify-code'>CONFIRM-$verification_code</div>
                    </div>
                    
                    <p><strong>Instructions:</strong></p>
                    <ol>
                        <li>Reply to this email</li>
                        <li>In the subject or body, type: <strong>CONFIRM-$verification_code</strong></li>
                        <li>Send the email</li>
                    </ol>
                    
                    <p>Our admin team will review your request and verify your account within 24-48 hours.</p>
                    
                    <p>If you didn't request this verification, please ignore this email.</p>
                    
                    <p>Best regards,<br><strong>BookStack Team</strong></p>
                </div>
                <div class='footer'>
                    <p>This is an automated message, please do not reply directly.</p>
                    <p>&copy; " . date('Y') . " BookStack. All rights reserved.</p>
                </div>
            </div>
        </body>
        </html>
        ";

        $emailSent = sendEmail($contact_info, $subject, $message);
    } elseif ($method === 'phone') {
        $contact_info = $user['phone_number'];
        $verification_code = strtoupper(substr(md5(uniqid(rand(), true)), 0, 6));

        // Send SMS
        $sms_message = "BookStack Account Verification: Reply 'CONFIRM-$verification_code' to verify your account. Valid for 24 hours.";
        $smsSent = sendSMS($contact_info, $sms_message);
    }

    // Store verification request in messages table
    $subject = "Account Verification Request - User #$user_id";
    $content = "User: {$user['user_name']} (ID: $user_id)\n";
    $content .= "Email: {$user['email']}\n";
    $content .= "Phone: {$user['phone_number']}\n";
    $content .= "Verification Method: " . strtoupper($method) . "\n";
    $content .= "Verification Code: CONFIRM-$verification_code\n";
    $content .= "Status: Pending Admin Approval\n";
    $content .= "Requested at: " . date('Y-m-d H:i:s');

    $subject_escaped = mysqli_real_escape_string($conn, $subject);
    $content_escaped = mysqli_real_escape_string($conn, $content);
    $contact_info_escaped = mysqli_real_escape_string($conn, $contact_info);

    $insert_query = "INSERT INTO messages (user_id, contact_method, contact_info, subject, content, status, verification_code, code_sent_at) 
                     VALUES ($user_id, '$method', '$contact_info_escaped', '$subject_escaped', '$content_escaped', 'pending', 'CONFIRM-$verification_code', NOW())";

    if (executeQuery($insert_query)) {
        $_SESSION['status_message'] = "Verification request sent successfully! Please check your $method and follow the instructions. Our admin team will review your request.";
        $_SESSION['status_type'] = 'success';
    } else {
        $_SESSION['status_message'] = "Error sending verification request. Please try again.";
        $_SESSION['status_type'] = 'danger';
    }

    // Redirect to prevent form resubmission on refresh
    header('Location: request-verification.php');
    exit;
}

$title = 'Request Account Verification';
$extraStyles = '<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<style>
        :root {
            --primary-color: #2ecc71;
            --bg-light: #f8fafb;
        }

        body {
            background-color: var(--bg-light);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            font-size: 16px;
            line-height: 1.6;
        }

        .logo-svg {
            width: 36px;
            height: 36px;
            color: var(--primary-color);
        }

        .verification-card {
            max-width: 700px;
            margin: 0 auto;
        }

        .method-card {
            border: 2px solid #e1e8ed;
            border-radius: 12px;
            padding: 2rem;
            transition: all 0.3s ease;
            cursor: pointer;
            position: relative;
        }

        .method-card:hover {
            border-color: var(--primary-color);
            box-shadow: 0 4px 12px rgba(85, 182, 231, 0.2);
        }

        .method-card input[type="radio"] {
            position: absolute;
            top: 20px;
            right: 20px;
            width: 24px;
            height: 24px;
            cursor: pointer;
        }

        .method-card.selected {
            border-color: var(--primary-color);
            background-color: #f0f8fc;
        }

        .method-icon {
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, var(--primary-color) 0%, #249e57ff 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.8rem;
            margin-bottom: 1rem;
        }

        .btn-verify {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
            color: white;
            padding: 0.75rem 2rem;
            font-size: 1.05rem;
            font-weight: 600;
            border-radius: 10px;
            transition: all 0.2s ease;
        }

        .btn-verify:hover {
            background-color: #37b278;
            border-color: #37b278;
            color: white;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(102, 231, 85, 0.3);
        }

        .text-green {
            color: var(--primary-color) !important;
        }

        .info-box {
            background-color: #e7ffefff;
            border-left: 4px solid var(--primary-color);
            padding: 1rem 1.25rem;
            border-radius: 8px;
            margin: 1.5rem 0;
        }

        .steps-list {
            list-style: none;
            padding: 0;
            counter-reset: steps;
        }

        .steps-list li {
            counter-increment: steps;
            padding: 0.75rem 0;
            padding-left: 3rem;
            position: relative;
        }

        .steps-list li::before {
            content: counter(steps);
            position: absolute;
            left: 0;
            top: 0.75rem;
            width: 32px;
            height: 32px;
            background: var(--primary-color);
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 0.9rem;
        }

        .btn-green {
            background-color: var(--primary-color) !important;
            border-color: var(--primary-color) !important;
            color: white !important;
            font-weight: 600;
            transition: all 0.2s;
        }

        .btn-green:hover {
            background-color: var(--primary-hover) !important;
            border-color: var(--primary-hover) !important;
            color: green !important;
        }
    </style>';
include 'includes/head.php';
?>

<body>
    <!-- Header -->
    <?php include 'includes/nav.php'; ?>

    <!-- Main Content -->
    <main class="flex-grow-1 d-flex align-items-center justify-content-center py-5">
        <div class="container mt-5">
            <div class="row justify-content-center">
                <div class="col-12 col-md-10">
                    <div class="verification-card">
                        <div class="text-center mb-5">
                            <div class="mb-3">
                                <i class="bi bi-shield-check" style="font-size: 4rem; color: var(--primary-color);"></i>
                            </div>
                            <h1 class="fw-bold mb-3">Request Account Verification</h1>
                            <p class="text-muted">Choose how you'd like to verify your account. You'll receive instructions and need to confirm your request.</p>
                        </div>

                        <!-- Status Message -->
                        <?php
                        $success_message = ($statusType === 'success' && !empty($statusMessage)) ? $statusMessage : '';
                        $error_message = ($statusType === 'danger' && !empty($statusMessage)) ? $statusMessage : '';
                        $warning_message = ($statusType === 'warning' && !empty($statusMessage)) ? $statusMessage : '';
                        $info_message = ($statusType === 'info' && !empty($statusMessage)) ? $statusMessage : '';
                        include 'includes/notification.php';
                        ?>

                        <form method="POST" action="" id="verificationForm">
                            <div class="row g-4 mb-4">
                                <!-- Email Verification -->
                                <div class="col-md-6">
                                    <div class="method-card" onclick="selectMethod('email')">
                                        <input type="radio" name="verification_method" value="email" id="email_method" required>
                                        <div class="method-icon">
                                            <i class="bi bi-envelope-fill"></i>
                                        </div>
                                        <h5 class="fw-bold mb-2">Email Verification</h5>
                                        <p class="text-muted mb-2">We'll send verification instructions to:</p>
                                        <p class="fw-semibold text-green mb-0 text-truncate" title="<?= htmlspecialchars($user['email']) ?>"><?= htmlspecialchars($user['email']) ?></p>
                                    </div>
                                </div>

                                <!-- Phone Verification -->
                                <div class="col-md-6">
                                    <div class="method-card" onclick="selectMethod('phone')">
                                        <input type="radio" name="verification_method" value="phone" id="phone_method" required>
                                        <div class="method-icon">
                                            <i class="bi bi-phone-fill"></i>
                                        </div>
                                        <h5 class="fw-bold mb-2">SMS Verification</h5>
                                        <p class="text-muted mb-2">We'll send verification code to:</p>
                                        <p class="fw-semibold text-green mb-0"><?= htmlspecialchars($user['phone_number'] ?: 'No phone number provided') ?></p>
                                    </div>
                                </div>
                            </div>

                            <!-- Information Box -->
                            <div class="info-box">
                                <h6 class="fw-bold mb-3"><i class="bi bi-info-circle me-2"></i>How it works:</h6>
                                <ol class="steps-list mb-0">
                                    <li>Choose your preferred verification method above</li>
                                    <li>You'll receive a confirmation code via email or SMS</li>
                                    <li>Reply with the code as instructed</li>
                                    <li>Our admin team will review and approve your request</li>
                                    <li>You'll be notified once your account is verified</li>
                                </ol>
                            </div>

                            <!-- Submit Button -->
                            <div class="d-flex gap-3 justify-content-center mt-4">
                                <a href="profile.php" class="btn btn-outline-secondary px-4">Cancel</a>
                                <button type="submit" name="request_verification" class="btn btn-green">
                                    <i class="bi bi-send-fill me-2"></i>Request Verification
                                </button>
                            </div>
                        </form>

                        <!-- Additional Info -->
                        <div class="text-center mt-4">
                            <p class="text-muted small">
                                <i class="bi bi-clock me-1"></i>
                                Verification requests are typically processed within 24-48 hours
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <?php include 'includes/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function selectMethod(method) {
            // Remove selected class from all cards
            document.querySelectorAll('.method-card').forEach(card => {
                card.classList.remove('selected');
            });

            // Add selected class to clicked card
            event.currentTarget.classList.add('selected');

            // Check the radio button
            document.getElementById(method + '_method').checked = true;
        }

        // Form validation
        document.getElementById('verificationForm').addEventListener('submit', function(e) {
            const selectedMethod = document.querySelector('input[name="verification_method"]:checked');

            if (!selectedMethod) {
                e.preventDefault();
                alert('Please select a verification method');
                return false;
            }

            // Confirm submission
            const methodName = selectedMethod.value === 'email' ? 'email' : 'SMS';
            if (!confirm(`Are you sure you want to request verification via ${methodName}?`)) {
                e.preventDefault();
                return false;
            }
        });
    </script>
</body>

</html>