<?php
session_start();
require_once 'notifications/send-sms.php';

$statusMessage = '';
$statusType = '';
$showVerification = false;
$verificationFailed = false;
$attemptsRemaining = 3;

// Check if user is in cooldown period
function isInCooldown()
{
    if (isset($_SESSION['cooldown_until'])) {
        if (time() < $_SESSION['cooldown_until']) {
            return true;
        } else {
            // Cooldown expired, clear it
            unset($_SESSION['cooldown_until']);
            $_SESSION['otp_attempts'] = 0;
            return false;
        }
    }
    return false;
}

// Get remaining cooldown time in seconds
function getCooldownRemaining()
{
    if (isset($_SESSION['cooldown_until'])) {
        $remaining = $_SESSION['cooldown_until'] - time();
        return max(0, $remaining);
    }
    return 0;
}

$userEmail = "student@gmail.com";
// $userPhone = "639123456789"; // Example phone number in Philippines format receiving SMS
$userPhone = "639623489331"; // Example phone number in Philippines format receiving SMS

// Handle resending OTP
if (isset($_GET['resend']) && isset($_SESSION['delivery_method'])) {
    // Check if user is in cooldown
    if (isInCooldown()) {
        $cooldownRemaining = getCooldownRemaining();
        $minutes = floor($cooldownRemaining / 60);
        $seconds = $cooldownRemaining % 60;
        $statusMessage = sprintf('Too many failed attempts. Please wait %d:%02d before trying again.', $minutes, $seconds);
        $statusType = 'danger';
        $showVerification = true;
    } else {
        $deliveryMethod = $_SESSION['delivery_method'];

        // Generate new OTP
        $otp = rand(100000, 999999);
        $_SESSION['otp'] = $otp;
        $_SESSION['otp_expiry'] = time() + 600; // 10 minutes
        $_SESSION['otp_attempts'] = 0;

        if ($deliveryMethod === 'sms') {
            // Send SMS
            $message = "Your BookStack OTP is: $otp. Valid for 10 minutes. Do not share this code.";
            $result = sendSMS($userPhone, $message);

            if ($result !== false) {
                $statusMessage = 'New OTP sent to your phone successfully!';
                $statusType = 'success';
                $showVerification = true;
            } else {
                $statusMessage = 'Failed to send OTP via SMS. Please try again.';
                $statusType = 'danger';
                $showVerification = true;
            }
        } else {
            // Send Email (placeholder - implement email sending)
            $statusMessage = 'New OTP sent to your email successfully!';
            $statusType = 'success';
            $showVerification = true;
            // TODO: Implement email sending using send-email.php
        }
    }
}

// Handle changing delivery method (Try another way)
if (isset($_GET['change_method'])) {
    unset($_SESSION['otp']);
    unset($_SESSION['otp_expiry']);
    unset($_SESSION['otp_attempts']);
    unset($_SESSION['delivery_method']);
    header('Location: forgot-password.php');
    exit;
}

// Handle OTP verification
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['verifyOTP'])) {
    // Check if user is in cooldown
    if (isInCooldown()) {
        $cooldownRemaining = getCooldownRemaining();
        $minutes = floor($cooldownRemaining / 60);
        $seconds = $cooldownRemaining % 60;
        $statusMessage = sprintf('Too many failed attempts. Please wait %d:%02d before trying again.', $minutes, $seconds);
        $statusType = 'danger';
        $verificationFailed = true;
        $showVerification = true;
    } else {
        $enteredOTP = '';
        for ($i = 1; $i <= 6; $i++) {
            $enteredOTP .= $_POST['otp' . $i] ?? '';
        }

        if (isset($_SESSION['otp']) && isset($_SESSION['otp_expiry'])) {
            if (time() > $_SESSION['otp_expiry']) {
                $statusMessage = 'OTP has expired. Please request a new code.';
                $statusType = 'danger';
                $verificationFailed = true;
                $showVerification = true;
            } elseif ($enteredOTP == $_SESSION['otp']) {
                // Successful verification - clear attempts and cooldown
                $_SESSION['otp_attempts'] = 0;
                unset($_SESSION['cooldown_until']);
                $statusMessage = 'Verification successful! Redirecting...';
                $statusType = 'success';
                // Redirect to reset password page
                // header('Location: reset-password.php');
                // exit;
            } else {
                $verificationFailed = true;
                $showVerification = true;
                if (!isset($_SESSION['otp_attempts'])) {
                    $_SESSION['otp_attempts'] = 0;
                }
                $_SESSION['otp_attempts']++;
                $attemptsRemaining = 3 - $_SESSION['otp_attempts'];

                // After 3 failed attempts, set cooldown for 1 minute
                if ($_SESSION['otp_attempts'] >= 3) {
                    $_SESSION['cooldown_until'] = time() + 60; // 1 minute cooldown
                    $statusMessage = 'Too many failed attempts. Please wait 1 minute before trying again.';
                } else {
                    $statusMessage = "Invalid verification code. You have $attemptsRemaining attempt(s) remaining.";
                }
                $statusType = 'danger';
            }
        } else {
            $statusMessage = 'No OTP found. Please request a new code.';
            $statusType = 'danger';
        }
    }
}

// Handle form submission for sending OTP
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['sendOTP'])) {
    $deliveryMethod = $_POST['deliveryMethod'] ?? 'email';

    // Generate OTP
    $otp = rand(100000, 999999);
    $_SESSION['otp'] = $otp;
    $_SESSION['otp_expiry'] = time() + 600; // 10 minutes
    $_SESSION['otp_attempts'] = 0;
    $_SESSION['delivery_method'] = $deliveryMethod;

    if ($deliveryMethod === 'sms') {
        // Send SMS
        $message = "Your BookStack OTP is: $otp. Valid for 10 minutes. Do not share this code.";
        $result = sendSMS($userPhone, $message);

        if ($result !== false) {
            $statusMessage = 'OTP sent to your phone successfully!';
            $statusType = 'success';
            $showVerification = true;
        } else {
            $statusMessage = 'Failed to send OTP via SMS. Please try again or use email.';
            $statusType = 'danger';
        }
    } else {
        // Send Email (placeholder - implement email sending)
        $statusMessage = 'OTP sent to your email successfully!';
        $statusType = 'success';
        $showVerification = true;
        // TODO: Implement email sending using send-email.php
    }
}

// Check if we should show verification based on session
if (isset($_SESSION['otp']) && isset($_SESSION['otp_expiry']) && !isset($_POST['sendOTP'])) {
    if (time() <= $_SESSION['otp_expiry']) {
        $showVerification = true;
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Forgot Password - BookStack</title>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet" />

    <style>
        :root {
            /* BookStack Brand Colors from Image */
            --primary-color: #198754;
            --primary-hover: #146c43;
            --text-dark: #333333;
            --text-muted: #666666;
            --bg-light: #f8f9fa;
            --border-color: #e9ecef;
        }

        body {
            font-family: 'Inter', sans-serif;
            background-color: var(--bg-light);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        .navbar {
            background-color: white;
            border-bottom: 1px solid var(--border-color);
        }

        .navbar-brand {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            color: #198754;
            font-weight: 800;
            font-size: 1.5rem;
            text-decoration: none;
        }

        .navbar-brand:hover {
            color: var(--text-dark);
        }

        .logo-img {
            width: 40px;
            height: 40px;
            object-fit: contain;
        }

        .nav-link {
            color: var(--text-muted);
            font-weight: 500;
            font-size: 0.875rem;
        }

        .nav-link:hover {
            color: var(--text-dark);
        }

        main {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem 1rem;
        }

        .forgot-password-card {
            max-width: 480px;
            width: 100%;
            background: white;
            border-radius: 1rem;
            border: 1px solid var(--border-color);
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }

        .icon-wrapper {
            width: 56px;
            height: 56px;
            border-radius: 50%;
            background-color: rgba(76, 153, 230, 0.1);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--primary-color);
            margin: 0 auto 1rem;
        }

        .icon-wrapper .material-symbols-outlined {
            font-size: 28px;
        }

        .card-title {
            font-size: 2rem;
            font-weight: 700;
            color: var(--text-dark);
            margin-bottom: 0.5rem;
        }

        .card-description {
            color: var(--text-muted);
            font-size: 0.9375rem;
            max-width: 320px;
            margin: 0 auto;
        }

        .form-label {
            font-weight: 600;
            font-size: 0.875rem;
            color: var(--text-dark);
            margin-bottom: 0.5rem;
        }

        .input-group-icon {
            position: absolute;
            left: 12px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-muted);
            z-index: 10;
            pointer-events: none;
        }

        .input-group-icon .material-symbols-outlined {
            font-size: 20px;
        }

        .form-control {
            padding-left: 2.5rem;
            height: 48px;
            border-radius: 0.75rem;
            border: 1px solid #dce0e5;
            font-size: 1rem;
        }

        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.25rem rgba(76, 153, 230, 0.25);
        }

        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
            height: 48px;
            font-weight: 700;
            font-size: 1rem;
            border-radius: 0.75rem;
        }

        .btn-primary:hover {
            background-color: var(--primary-hover);
            border-color: var(--primary-hover);
        }

        .btn-primary:focus {
            box-shadow: 0 0 0 0.25rem rgba(76, 153, 230, 0.3);
        }

        .btn-primary:disabled {
            background-color: #9ca3af;
            border-color: #9ca3af;
            cursor: not-allowed;
            opacity: 0.7;
        }

        .back-link {
            background: none;
            border: none;
            color: var(--text-muted);
            font-weight: 600;
            font-size: 0.875rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            transition: color 0.2s;
        }

        .back-link:hover {
            color: var(--text-dark);
        }

        .back-link:hover .material-symbols-outlined {
            transform: translateX(-4px);
        }

        .back-link .material-symbols-outlined {
            font-size: 20px;
            transition: transform 0.2s;
        }

        .card-footer-custom {
            background-color: var(--bg-footer);
            border-top: 1px solid var(--border-color);
            padding: 1rem 2rem;
            text-align: center;
            font-size: 0.75rem;
            color: var(--text-muted);
            border-radius: 0 0 1rem 1rem;
        }

        .card-footer-custom a {
            color: var(--primary-color);
            text-decoration: none;
        }

        .card-footer-custom a:hover {
            text-decoration: underline;
        }

        .form-check-input:checked {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }

        .form-check-input:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.25rem rgba(76, 153, 230, 0.25);
        }

        .delivery-option {
            padding: 1rem;
            border: 2px solid var(--border-color);
            border-radius: 0.75rem;
            cursor: pointer;
            transition: all 0.2s;
        }

        .delivery-option:hover {
            border-color: var(--primary-color);
            background-color: rgba(76, 153, 230, 0.05);
        }

        .delivery-option.active {
            border-color: var(--primary-color);
            background-color: #f0fdf4;
        }

        .delivery-option .form-check-input {
            margin-top: 0.25rem;
        }

        .delivery-option-label {
            display: flex;
            align-items: start;
            gap: 0.75rem;
            cursor: pointer;
        }

        .delivery-option-icon {
            color: var(--primary-color);
        }

        .delivery-option-text h6 {
            font-size: 0.9375rem;
            font-weight: 600;
            color: var(--text-dark);
            margin-bottom: 0.25rem;
        }

        .delivery-option-text p {
            font-size: 0.8125rem;
            color: var(--text-muted);
            margin-bottom: 0;
        }

        .masked-info {
            font-family: 'Courier New', monospace;
            font-size: 0.875rem;
            color: var(--text-dark);
            font-weight: 600;
            margin-top: 0.25rem;
        }

        /* OTP Input Styles */
        .otp-input {
            width: 40px;
            height: 48px;
            text-align: center;
            font-size: 1.25rem;
            font-weight: 700;
            border: none;
            border-bottom: 2px solid var(--border-color);
            border-radius: 0;
            background: transparent;
            transition: border-color 0.2s;
        }

        .otp-input:focus {
            outline: none;
            border-bottom-color: var(--primary-color);
            box-shadow: none;
        }

        .otp-input.error {
            border-bottom-color: #dc3545;
        }

        .otp-input.error:focus {
            border-bottom-color: #dc3545;
        }

        .otp-input:disabled {
            background-color: #f3f4f6;
            color: #9ca3af;
            cursor: not-allowed;
            opacity: 0.6;
        }

        @media (min-width: 576px) {
            .card-title {
                font-size: 2rem;
            }

            .otp-input {
                width: 48px;
                height: 56px;
            }
        }

        .error-box {
            background-color: rgba(220, 53, 69, 0.1);
            border: 1px solid rgba(220, 53, 69, 0.2);
            border-radius: 0.5rem;
            padding: 0.75rem;
        }

        .success-icon-wrapper {
            width: 64px;
            height: 64px;
            border-radius: 50%;
            background-color: rgba(220, 53, 69, 0.1);
            display: flex;
            align-items: center;
            justify-content: center;
            color: #dc3545;
            margin: 0 auto 1rem;
        }

        .success-icon-wrapper.success {
            background-color: rgba(25, 135, 84, 0.1);
            color: #198754;
        }
    </style>
</head>

<body>
    <nav class="navbar navbar-expand-lg navbar-light shadow-sm">
        <div class="container-fluid px-4 px-lg-5">
            <a class="navbar-brand" href="#">
                <img src="assets/logo.svg" alt="BookStack Logo" class="logo-img">
                BookStack
            </a>
            <div class="d-none d-sm-flex">
                <a class="nav-link px-3" href="#">Help</a>
            </div>
        </div>
    </nav>

    <?php
    // Mask email: show first 2 chars and domain
    $emailParts = explode('@', $userEmail);
    $length = strlen($emailParts[0]);
    $maskedEmail = substr($emailParts[0], 0, 2)
        . str_repeat('*', max($length - 2, 1))
        . '@' . $emailParts[1];
    $maskedPhone = substr($userPhone, 0, 3) . str_repeat('*', strlen($userPhone) - 7) . substr($userPhone, -4);
    ?>

    <main>
        <div class="forgot-password-card">
            <div class="card-body p-4 p-md-5">
                <!-- Icon & Heading -->
                <div class="text-center mb-4">
                    <div class="icon-wrapper">
                        <span class="material-symbols-outlined">lock_reset</span>
                    </div>
                    <h1 class="card-title">Forgot Password?</h1>
                    <p class="card-description">
                        No worries, we'll send you a verification code. Choose where you'd like to receive it.
                    </p>
                </div>

                <?php if ($showVerification): ?>
                    <!-- Verification Code Section -->
                    <div class="text-center mb-4">
                        <div class="<?php echo $verificationFailed ? 'success-icon-wrapper' : 'success-icon-wrapper success'; ?>">
                            <span class="material-symbols-outlined" style="font-size: 2rem;">
                                <?php echo $verificationFailed ? 'gpp_bad' : 'mark_email_read'; ?>
                            </span>
                        </div>
                        <h2 class="h4 fw-bold mb-2">
                            <?php echo $verificationFailed ? 'Verification Failed' : 'Verify Your Code'; ?>
                        </h2>
                        <p class="text-muted">
                            <?php echo $verificationFailed
                                ? 'The code you entered is incorrect or has expired. Please check and try again.'
                                : 'Enter the 6-digit code sent to your ' . ($_SESSION['delivery_method'] ?? 'email'); ?>
                        </p>
                    </div>

                    <?php if (!empty($statusMessage) && $verificationFailed): ?>
                        <div class="error-box d-flex align-items-start gap-2 mb-4">
                            <span class="material-symbols-outlined text-danger" style="font-size: 1.25rem;">error</span>
                            <p class="text-danger mb-0 small fw-medium">
                                <?php echo htmlspecialchars($statusMessage); ?>
                            </p>
                        </div>
                    <?php elseif (!empty($statusMessage)): ?>
                        <div class="alert alert-<?php echo $statusType; ?> alert-dismissible fade show" role="alert">
                            <?php echo htmlspecialchars($statusMessage); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>

                    <?php if (isInCooldown()): ?>
                        <div class="alert alert-warning d-flex align-items-start gap-2 mb-4">
                            <span class="material-symbols-outlined" style="font-size: 1.25rem;">schedule</span>
                            <div>
                                <strong>Account Temporarily Locked</strong>
                                <p class="mb-0 small">Too many failed attempts. Please wait <span id="cooldownDisplay">
                                        <?php
                                        $cooldownRemaining = getCooldownRemaining();
                                        $minutes = floor($cooldownRemaining / 60);
                                        $seconds = $cooldownRemaining % 60;
                                        echo sprintf('%d:%02d', $minutes, $seconds);
                                        ?></span> before trying again.</p>
                            </div>
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="forgot-password.php" id="verifyForm">
                        <!-- OTP Input Fields -->
                        <div class="d-flex justify-content-center gap-2 mb-4">
                            <?php for ($i = 1; $i <= 6; $i++): ?>
                                <input
                                    type="text"
                                    name="otp<?php echo $i; ?>"
                                    id="otp<?php echo $i; ?>"
                                    class="otp-input <?php echo $verificationFailed ? 'error' : ''; ?>"
                                    maxlength="1"
                                    pattern="[0-9]"
                                    inputmode="numeric"
                                    required
                                    autocomplete="off">
                            <?php endfor; ?>
                        </div>

                        <button type="submit" name="verifyOTP" class="btn btn-primary w-100 mb-3" id="verifyBtn" <?php echo isInCooldown() ? 'disabled' : ''; ?>>
                            <?php
                            if (isInCooldown()) {
                                $cooldownRemaining = getCooldownRemaining();
                                $minutes = floor($cooldownRemaining / 60);
                                $seconds = $cooldownRemaining % 60;
                                echo sprintf('Wait %d:%02d', $minutes, $seconds);
                            } else {
                                echo 'Verify Code';
                            }
                            ?>
                        </button>

                        <div class="text-center mb-3">
                            <p class="text-muted small mb-2">
                                Didn't receive the code?
                                <a href="#" id="resendLink" class="text-primary fw-bold text-decoration-none" style="pointer-events: none; opacity: 0.6;">
                                    <span id="resendText">Resend in <span id="timerCount">00:30</span></span>
                                </a>
                            </p>
                            <p class="text-muted small mb-0">
                                <a href="forgot-password.php?change_method=1" class="text-decoration-none" style="color: var(--text-muted); font-weight: 500;">
                                    <span class="material-symbols-outlined" style="font-size: 16px; vertical-align: middle;">swap_horiz</span>
                                    Try another way
                                </a>
                            </p>
                        </div>
                    </form>

                <?php else: ?>
                    <!-- Delivery Method Selection -->
                    <?php if (!empty($statusMessage)): ?>
                        <div class="alert alert-<?php echo $statusType; ?> alert-dismissible fade show" role="alert">
                            <?php echo htmlspecialchars($statusMessage); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="forgot-password.php">
                        <div class="mb-4">
                            <label class="form-label">Send verification code to:</label>
                            <div class="row g-3">
                                <div class="col-12">
                                    <div class="delivery-option" id="emailOption">
                                        <label class="delivery-option-label">
                                            <input
                                                class="form-check-input"
                                                type="radio"
                                                name="deliveryMethod"
                                                id="radioEmail"
                                                value="email"
                                                required>
                                            <span class="delivery-option-icon">
                                                <span class="material-symbols-outlined">mail</span>
                                            </span>
                                            <div class="delivery-option-text flex-grow-1">
                                                <h6>Email</h6>
                                                <p>Send code to your email</p>
                                                <div class="masked-info"><?php echo $maskedEmail; ?></div>
                                            </div>
                                        </label>
                                    </div>
                                </div>
                                <div class="col-12">
                                    <div class="delivery-option" id="smsOption">
                                        <label class="delivery-option-label">
                                            <input
                                                class="form-check-input"
                                                type="radio"
                                                name="deliveryMethod"
                                                id="radioSms"
                                                value="sms"
                                                required>
                                            <span class="delivery-option-icon">
                                                <span class="material-symbols-outlined">sms</span>
                                            </span>
                                            <div class="delivery-option-text flex-grow-1">
                                                <h6>SMS</h6>
                                                <p>Send code to your phone</p>
                                                <div class="masked-info"><?php echo $maskedPhone; ?></div>
                                            </div>
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <button type="submit" name="sendOTP" class="btn btn-primary w-100 mb-3">
                            Send Verification Code
                        </button>
                    </form>
                <?php endif; ?>

                <div class="text-center pt-2">
                    <a href="login.php" class="back-link text-decoration-none">
                        <span class="material-symbols-outlined">arrow_back</span>
                        <span>Back to Login</span>
                    </a>
                </div>
            </div>

            <div class="card-footer-custom">
                <p class="mb-0">
                    Need help? <a href="#">Contact Support</a>
                </p>
            </div>
        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Handle delivery method selection
        const emailOption = document.getElementById('emailOption');
        const smsOption = document.getElementById('smsOption');
        const radioEmail = document.getElementById('radioEmail');
        const radioSms = document.getElementById('radioSms');

        if (emailOption && smsOption) {
            // Click handlers for delivery options
            emailOption.addEventListener('click', function() {
                radioEmail.checked = true;
                emailOption.classList.add('active');
                smsOption.classList.remove('active');
            });

            smsOption.addEventListener('click', function() {
                radioSms.checked = true;
                smsOption.classList.add('active');
                emailOption.classList.remove('active');
            });

            // Radio button change handlers
            radioEmail.addEventListener('change', function() {
                if (this.checked) {
                    emailOption.classList.add('active');
                    smsOption.classList.remove('active');
                }
            });

            radioSms.addEventListener('change', function() {
                if (this.checked) {
                    smsOption.classList.add('active');
                    emailOption.classList.remove('active');
                }
            });
        }

        // OTP Input Auto-focus and Navigation
        const otpInputs = document.querySelectorAll('.otp-input');
        if (otpInputs.length > 0) {
            otpInputs.forEach((input, index) => {
                // Auto-focus next input
                input.addEventListener('input', function(e) {
                    if (this.value.length === 1) {
                        // Remove error class on input
                        this.classList.remove('error');
                        // Move to next input
                        if (index < otpInputs.length - 1) {
                            otpInputs[index + 1].focus();
                        }
                    }
                });

                // Handle backspace
                input.addEventListener('keydown', function(e) {
                    if (e.key === 'Backspace' && this.value === '') {
                        if (index > 0) {
                            otpInputs[index - 1].focus();
                        }
                    }
                });

                // Allow only numbers
                input.addEventListener('keypress', function(e) {
                    if (!/[0-9]/.test(e.key)) {
                        e.preventDefault();
                    }
                });

                // Handle paste
                input.addEventListener('paste', function(e) {
                    e.preventDefault();
                    const pastedData = e.clipboardData.getData('text').replace(/\D/g, '');
                    if (pastedData.length === 6) {
                        otpInputs.forEach((inp, idx) => {
                            inp.value = pastedData[idx] || '';
                            inp.classList.remove('error');
                        });
                        otpInputs[5].focus();
                    }
                });
            });

            // Auto-focus first input
            otpInputs[0].focus();
        }

        // Resend Code Timer
        const resendLink = document.getElementById('resendLink');
        const resendText = document.getElementById('resendText');
        const timerCount = document.getElementById('timerCount');

        if (resendLink && timerCount) {
            let timeLeft = 30;
            let countdownInterval;

            // Update timer display
            function updateTimer() {
                const minutes = Math.floor(timeLeft / 60);
                const seconds = timeLeft % 60;
                timerCount.textContent = `${String(minutes).padStart(2, '0')}:${String(seconds).padStart(2, '0')}`;
            }

            // Start countdown
            function startCountdown() {
                countdownInterval = setInterval(function() {
                    timeLeft--;

                    if (timeLeft >= 0) {
                        updateTimer();
                    } else {
                        clearInterval(countdownInterval);
                        resendText.textContent = 'Resend Code';
                        resendLink.style.pointerEvents = 'auto';
                        resendLink.style.opacity = '1';
                    }
                }, 1000);
            }

            // Handle resend click
            resendLink.addEventListener('click', function(e) {
                e.preventDefault();
                if (timeLeft <= 0) {
                    // Redirect to resend
                    window.location.href = 'forgot-password.php?resend=1';
                }
            });

            // Initial display
            updateTimer();
            startCountdown();
        }

        // Cooldown Timer for Verify Button
        const verifyBtn = document.getElementById('verifyBtn');
        const cooldownDisplay = document.getElementById('cooldownDisplay');
        <?php if (isInCooldown()): ?>
            let cooldownTimeLeft = <?php echo getCooldownRemaining(); ?>;

            function updateCooldownButton() {
                if (cooldownTimeLeft > 0) {
                    const minutes = Math.floor(cooldownTimeLeft / 60);
                    const seconds = cooldownTimeLeft % 60;
                    const timeString = `${minutes}:${String(seconds).padStart(2, '0')}`;
                    verifyBtn.textContent = `Wait ${timeString}`;
                    verifyBtn.disabled = true;

                    // Update cooldown display if it exists
                    if (cooldownDisplay) {
                        cooldownDisplay.textContent = timeString;
                    }
                } else {
                    clearInterval(cooldownInterval);
                    // Refresh the page to clear cooldown state
                    window.location.reload();
                }
            }

            // Disable OTP inputs during cooldown
            if (cooldownTimeLeft > 0 && otpInputs.length > 0) {
                otpInputs.forEach(input => {
                    input.disabled = true;
                });
            }

            // Disable resend link during cooldown
            if (resendLink && cooldownTimeLeft > 0) {
                resendLink.style.pointerEvents = 'none';
                resendLink.style.opacity = '0.6';
                resendText.innerHTML = 'Resend blocked due to cooldown';
            }

            const cooldownInterval = setInterval(function() {
                cooldownTimeLeft--;
                updateCooldownButton();
            }, 1000);

            updateCooldownButton();
        <?php endif; ?>
    </script>
</body>

</html>