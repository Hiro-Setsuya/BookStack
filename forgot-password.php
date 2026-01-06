<?php
session_start();
require_once 'config/db.php';
require_once 'notifications/send-sms.php';
require_once 'notifications/send-email.php';

$statusMessage = '';
$statusType = '';
$showVerification = false;
$verificationFailed = false;
$attemptsRemaining = 3;
$showAccountInput = true; // New flag to show account input form

// Clear old session data if user is starting fresh (not from form submission or redirect)
if (
    !isset($_POST['identifyAccount']) && !isset($_POST['sendOTP']) && !isset($_POST['verifyOTP'])
    && !isset($_GET['resend']) && !isset($_GET['change_method']) && !isset($_GET['start_over'])
) {
    // User is arriving fresh from login page, clear ALL old password reset sessions
    unset($_SESSION['otp']);
    unset($_SESSION['otp_expiry']);
    unset($_SESSION['otp_attempts']);
    unset($_SESSION['delivery_method']);
    unset($_SESSION['reset_email']);
    unset($_SESSION['reset_phone']);
    unset($_SESSION['reset_username']);
    unset($_SESSION['reset_user_id']);
    unset($_SESSION['otp_verified']);
    unset($_SESSION['password_reset_allowed']);
    unset($_SESSION['cooldown_until']);
}

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

// Handle account identification
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['identifyAccount'])) {
    $identifier = trim($_POST['identifier'] ?? '');

    if (empty($identifier)) {
        $statusMessage = 'Please enter your email, phone number, or username.';
        $statusType = 'danger';
    } else {
        // Format phone number if it looks like a phone (09XXXXXXXXX)
        $identifier_formatted = $identifier;
        if (preg_match('/^09[0-9]{9}$/', $identifier)) {
            // Convert 09XX to 639XX for database lookup
            $identifier_formatted = '63' . substr($identifier, 1);
        }

        // Search for user in database
        $identifier_escaped = mysqli_real_escape_string($conn, $identifier_formatted);
        $query = "SELECT user_id, user_name, email, phone_number 
                  FROM users 
                  WHERE email = '$identifier_escaped' 
                     OR phone_number = '$identifier_escaped' 
                     OR user_name = '$identifier_escaped'";

        $result = executeQuery($query);

        if ($result && mysqli_num_rows($result) > 0) {
            $user = mysqli_fetch_assoc($result);

            // Store user info in session
            $_SESSION['reset_email'] = $user['email'];
            // Only store phone if it exists and is not empty
            $_SESSION['reset_phone'] = !empty($user['phone_number']) ? $user['phone_number'] : null;
            $_SESSION['reset_username'] = $user['user_name'];
            $_SESSION['reset_user_id'] = $user['user_id'];

            // Set variables immediately for display
            $userEmail = $user['email'];
            $userPhone = !empty($user['phone_number']) ? $user['phone_number'] : null;

            $showAccountInput = false; // Hide account input, show delivery method selection
        } else {
            $statusMessage = 'Account not found. Please check your email, phone number, or username.';
            $statusType = 'danger';
        }
    }
}

// Check if user info is already in session
if (isset($_SESSION['reset_email']) && !isset($_POST['identifyAccount'])) {
    $showAccountInput = false;
    $userEmail = $_SESSION['reset_email'];
    $userPhone = $_SESSION['reset_phone'] ?? null;
}

// Initialize if not set
if (!isset($userEmail)) {
    $userEmail = '';
    $userPhone = '';
}

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

// Handle starting over (new account)
if (isset($_GET['start_over'])) {
    unset($_SESSION['otp']);
    unset($_SESSION['otp_expiry']);
    unset($_SESSION['otp_attempts']);
    unset($_SESSION['delivery_method']);
    unset($_SESSION['reset_email']);
    unset($_SESSION['reset_phone']);
    unset($_SESSION['reset_username']);
    unset($_SESSION['reset_user_id']);
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
                $_SESSION['otp_verified'] = true; // Mark OTP as verified
                $_SESSION['password_reset_allowed'] = true; // Allow password reset

                // Redirect to change password page
                header('Location: change-password.php');
                exit;
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
        // Send Email
        $result = sendOTPEmail($userEmail, $otp, $_SESSION['reset_username'] ?? '');

        if ($result) {
            $statusMessage = 'OTP sent to your email successfully!';
            $statusType = 'success';
            $showVerification = true;
        } else {
            $statusMessage = 'Failed to send OTP via email. Please try again or use SMS.';
            $statusType = 'danger';
        }
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

    <!-- Google Fonts: Manrope -->
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@200..800&display=swap" rel="stylesheet" />

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">

    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="style.css">
</head>

<body>
    <?php include 'includes/nav.php'; ?>

    <?php
    // Mask email: show first 2 chars and domain
    if (!empty($userEmail)) {
        $emailParts = explode('@', $userEmail);
        $length = strlen($emailParts[0]);
        $maskedEmail = substr($emailParts[0], 0, 2)
            . str_repeat('*', max($length - 2, 1))
            . '@' . $emailParts[1];
    } else {
        $maskedEmail = '';
    }

    if (!empty($userPhone)) {
        $maskedPhone = substr($userPhone, 0, 3) . str_repeat('*', strlen($userPhone) - 7) . substr($userPhone, -4);
    } else {
        $maskedPhone = '';
    }
    ?>

    <main class="pt-5 mt-5">
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

                <?php if ($showAccountInput): ?>
                    <!-- Account Identification Form -->
                    <?php
                    $success_message = ($statusType === 'success' && !empty($statusMessage)) ? $statusMessage : '';
                    $error_message = ($statusType === 'danger' && !empty($statusMessage)) ? $statusMessage : '';
                    $warning_message = ($statusType === 'warning' && !empty($statusMessage)) ? $statusMessage : '';
                    $info_message = ($statusType === 'info' && !empty($statusMessage)) ? $statusMessage : '';
                    include 'includes/notification.php';
                    ?>

                    <form method="POST" action="forgot-password.php">
                        <div class="mb-4">
                            <label for="identifier" class="form-label d-flex align-items-center gap-2 mb-3">
                                <span class="material-symbols-outlined" style="font-size: 20px; color: var(--primary-color);">account_circle</span>
                                <span>Find Your Account</span>
                            </label>
                            <div class="position-relative mb-3">
                                <span class="input-icon-wrapper">
                                    <span class="material-symbols-outlined">search</span>
                                </span>
                                <input
                                    type="text"
                                    class="form-control form-control-lg ps-5"
                                    id="identifier"
                                    name="identifier"
                                    placeholder="Email, Phone, or Username"
                                    required
                                    autofocus>
                            </div>
                        </div>

                        <button type="submit" name="identifyAccount" class="btn btn-primary w-100 mb-3">
                            <span class="material-symbols-outlined" style="font-size: 20px; vertical-align: middle; margin-right: 8px;">arrow_forward</span>
                            Continue
                        </button>
                    </form>

                <?php elseif ($showVerification): ?>
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
                        <?php
                        $success_message = ($statusType === 'success') ? $statusMessage : '';
                        $error_message = ($statusType === 'danger') ? $statusMessage : '';
                        $warning_message = ($statusType === 'warning') ? $statusMessage : '';
                        $info_message = ($statusType === 'info') ? $statusMessage : '';
                        include 'includes/notification.php';
                        ?>
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
                                &nbsp;|&nbsp;
                                <a href="forgot-password.php?start_over=1" class="text-decoration-none" style="color: var(--text-muted); font-weight: 500;">
                                    <span class="material-symbols-outlined" style="font-size: 16px; vertical-align: middle;">restart_alt</span>
                                    Different account
                                </a>
                            </p>
                        </div>
                    </form>

                <?php else: ?>
                    <!-- Delivery Method Selection -->
                    <?php
                    $success_message = ($statusType === 'success' && !empty($statusMessage)) ? $statusMessage : '';
                    $error_message = ($statusType === 'danger' && !empty($statusMessage)) ? $statusMessage : '';
                    $warning_message = ($statusType === 'warning' && !empty($statusMessage)) ? $statusMessage : '';
                    $info_message = ($statusType === 'info' && !empty($statusMessage)) ? $statusMessage : '';
                    include 'includes/notification.php';
                    ?>

                    <form method="POST" action="forgot-password.php">
                        <div class="mb-4">
                            <label class="form-label">Send verification code to:</label>
                            <div class="row g-3">
                                <?php if (!empty($maskedEmail)): ?>
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
                                <?php endif; ?>

                                <?php if (!empty($maskedPhone)): ?>
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
                                <?php endif; ?>
                            </div>
                        </div>

                        <button type="submit" name="sendOTP" class="btn btn-primary w-100 mb-3">
                            Send Verification Code
                        </button>

                        <div class="text-center">
                            <a href="forgot-password.php?start_over=1" class="text-decoration-none text-muted small">
                                <span class="material-symbols-outlined" style="font-size: 16px; vertical-align: middle;">restart_alt</span>
                                Use a different account
                            </a>
                        </div>
                    </form>
                <?php endif; ?>

                <div class="text-center pt-2">
                    <a href="login.php" class="back-link text-decoration-none">
                        <span class="material-symbols-outlined">arrow_back</span>
                        <span>Back to Login</span>
                    </a>
                </div>
            </div>


        </div>
    </main>
    <?php include 'includes/footer.php'; ?>

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