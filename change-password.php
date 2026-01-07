<?php
session_start();
require_once 'config/db.php';
require_once 'notifications/send-email.php';

if (
    empty($_SESSION['otp_verified']) ||
    empty($_SESSION['password_reset_allowed'])
) {
    header('Location: forgot-password.php');
    exit;
}

$statusMessage = '';
$statusType = '';

$userEmail = $_SESSION['reset_email'] ?? '';
$userId    = $_SESSION['reset_user_id'] ?? '';
$userName  = $_SESSION['reset_username'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $newPassword     = $_POST['newPassword'] ?? '';
    $confirmPassword = $_POST['confirmPassword'] ?? '';

    if ($newPassword !== $confirmPassword) {
        $statusMessage = 'Passwords do not match.';
        $statusType = 'danger';
    } else {
        $hash = password_hash($newPassword, PASSWORD_DEFAULT);

        if (!empty($userEmail)) {
            $email = mysqli_real_escape_string($conn, $userEmail);
            $query = "UPDATE users SET password_hash='$hash' WHERE email='$email' LIMIT 1";
        } else {
            $id = (int)$userId;
            $query = "UPDATE users SET password_hash='$hash' WHERE user_id=$id LIMIT 1";
        }

        $result = executeQuery($query);

        if (!$result) {
            $statusMessage = mysqli_error($conn);
            $statusType = 'danger';
        } elseif (mysqli_affected_rows($conn) === 1) {

            if (!empty($userEmail)) {
                sendPasswordResetConfirmation($userEmail, $userName);
            }

            session_unset();
            session_destroy();

            header('Location: login.php');
            exit;
        } else {
            $statusMessage = 'Password update failed.';
            $statusType = 'danger';
        }
    }
}
?>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Set New Password - BookStack</title>

    <!-- Google Fonts: Manrope -->
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@200..800&display=swap" rel="stylesheet" />

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        :root {
            --primary-color: #2ecc71;
            --bg-light: #f8fbf9ff;
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

        .card-header-img {
            height: 140px;
            background: linear-gradient(135deg, #f0fcf6ff 0%, #e8f8ebff 100%);
            position: relative;
            overflow: hidden;
        }

        .lock-icon {
            width: 70px;
            height: 70px;
            background: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.08);
        }

        .card {
            max-width: 560px;
            margin: 0 auto;
        }

        .form-label {
            font-size: 0.95rem;
            margin-bottom: 0.6rem;
            color: #2c3e50;
        }

        .form-control {
            height: 52px;
            font-size: 1rem;
            padding: 0.75rem 1rem;
            border-radius: 10px;
            border: 2px solid #e1e8ed;
            transition: all 0.2s ease;
        }

        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 4px rgba(85, 182, 231, 0.1);
        }

        .input-group .btn {
            height: 52px;
            width: 52px;
            border-radius: 0 10px 10px 0;
            border: 2px solid #e1e8ed;
            border-left: none;
        }

        .input-group .form-control {
            border-radius: 10px 0 0 10px;
        }

        .password-strength {
            height: 6px;
            background: #e9efebff;
            border-radius: 3px;
            overflow: hidden;
            margin-top: 12px;
        }

        .strength-bar {
            height: 100%;
            background: var(--primary-color);
            width: 50%;
            transition: width 0.3s ease;
        }

        .requirements-box {
            background-color: #f8fafb;
            border: 2px solid #e8f0f5;
            border-radius: 12px;
            padding: 1.25rem !important;
        }

        .requirements-box li {
            font-size: 0.95rem;
            padding: 0.4rem 0;
            line-height: 1.6;
        }

        .requirements-box i {
            font-size: 1.1rem;
            margin-right: 0.5rem;
        }

        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
            height: 54px;
            font-size: 1.05rem;
            font-weight: 600;
            border-radius: 10px;
            transition: all 0.2s ease;
        }

        .btn-primary:hover {
            background-color: #219451ff;
            border-color: #26a85cff;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(85, 182, 231, 0.3);
        }

        .back-link {
            font-size: 0.95rem;
            padding: 0.75rem;
            display: inline-block;
        }

        .back-link:hover {
            color: var(--primary-color) !important;
        }

        /* Spacing improvements */
        .mb-form {
            margin-bottom: 1.75rem;
        }

        h1 {
            font-size: 1.85rem;
            color: #1a1a1a;
            letter-spacing: -0.02em;
        }

        .text-center p {
            font-size: 1rem;
            line-height: 1.6;
        }

        /* Mobile optimization */
        @media (max-width: 576px) {
            .card-body {
                padding: 2rem 1.5rem !important;
            }

            .form-control {
                height: 48px;
                font-size: 16px;
                /* Prevents zoom on iOS */
            }

            .input-group .btn {
                height: 48px;
                width: 48px;
            }
        }
    </style>
</head>

<body>
    <?php include 'includes/nav.php'; ?>

    <!-- Main Content -->
    <main class="flex-grow-1 d-flex align-items-center justify-content-center py-5">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-12 col-md-8 col-lg-5">
                    <div class="card shadow-sm border-0 rounded-3">
                        <!-- Header Image -->
                        <div class="card-header-img d-flex align-items-center justify-content-center">
                            <div class="lock-icon">
                                <i class="bi bi-lock-fill fs-2" style="color: var(--primary-color);"></i>
                            </div>
                        </div>

                        <div class="card-body p-4 p-md-5">
                            <!-- Heading -->
                            <div class="text-center mb-5">
                                <h1 class="fw-bold mb-3">Set new password</h1>
                                <p class="text-muted mb-0">Your new password must be different from previously used passwords.</p>
                            </div>

                            <!-- Status Message -->
                            <?php
                            $success_message = ($statusType === 'success' && !empty($statusMessage)) ? $statusMessage : '';
                            $error_message = ($statusType === 'danger' && !empty($statusMessage)) ? $statusMessage : '';
                            $warning_message = ($statusType === 'warning' && !empty($statusMessage)) ? $statusMessage : '';
                            $info_message = ($statusType === 'info' && !empty($statusMessage)) ? $statusMessage : '';
                            include 'includes/notification.php';
                            ?>

                            <!-- Form -->
                            <form method="POST" action="" id="resetPasswordForm">
                                <!-- Password Field -->
                                <div class="mb-form">
                                    <label for="new-password" class="form-label fw-semibold">New Password</label>
                                    <div class="input-group">
                                        <input type="password" class="form-control" id="new-password" name="newPassword" placeholder="Enter at least 8 characters" autocomplete="new-password" required>
                                        <button class="btn btn-outline-secondary" type="button" id="togglePassword" aria-label="Toggle password visibility">
                                            <i class="bi bi-eye-slash"></i>
                                        </button>
                                    </div>
                                    <!-- Password Strength -->
                                    <div class="password-strength">
                                        <div class="strength-bar" id="strengthBar"></div>
                                    </div>
                                    <small class="text-muted d-block mt-2">Strength: <span id="strengthText" class="text-secondary fw-semibold">-</span></small>
                                </div>

                                <!-- Confirm Password Field -->
                                <div class="mb-form">
                                    <label for="confirm-password" class="form-label fw-semibold">Confirm Password</label>
                                    <div class="input-group">
                                        <input type="password" class="form-control" id="confirm-password" name="confirmPassword" placeholder="Re-enter your password" autocomplete="new-password" required>
                                        <button class="btn btn-outline-secondary" type="button" id="toggleConfirmPassword" aria-label="Toggle password visibility">
                                            <i class="bi bi-eye-slash"></i>
                                        </button>
                                    </div>
                                    <small id="matchMessage" class="d-block mt-2"></small>
                                </div>

                                <!-- Requirements Box -->
                                <div class="requirements-box mb-4">
                                    <p class="text-uppercase fw-semibold text-secondary small mb-3">Password Requirements:</p>
                                    <ul class="list-unstyled mb-0">
                                        <li class="d-flex align-items-center" id="req-length">
                                            <i class="bi bi-circle text-secondary"></i>
                                            <span>At least 8 characters long</span>
                                        </li>
                                        <li class="d-flex align-items-center" id="req-uppercase">
                                            <i class="bi bi-circle text-secondary"></i>
                                            <span>Contains uppercase letter (A-Z)</span>
                                        </li>
                                        <li class="d-flex align-items-center" id="req-number">
                                            <i class="bi bi-circle text-secondary"></i>
                                            <span>Contains number (0-9)</span>
                                        </li>
                                    </ul>
                                </div>

                                <!-- Submit Button -->
                                <button type="submit" name="resetPassword" class="btn btn-primary w-100 mb-3" id="submitBtn">
                                    Reset Password
                                </button>

                                <!-- Back Link -->
                                <div class="text-center">
                                    <a href="login.php" class="back-link text-decoration-none text-muted">
                                        <i class="bi bi-arrow-left"></i> Back to login
                                    </a>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <?php include 'includes/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Password strength checker
        const passwordInput = document.getElementById('new-password');
        const confirmInput = document.getElementById('confirm-password');
        const strengthBar = document.getElementById('strengthBar');
        const strengthText = document.getElementById('strengthText');
        const matchMessage = document.getElementById('matchMessage');

        // Requirements elements
        const reqLength = document.getElementById('req-length');
        const reqUppercase = document.getElementById('req-uppercase');
        const reqNumber = document.getElementById('req-number');

        // Check password requirements
        function checkRequirements(password) {
            const requirements = {
                length: password.length >= 8,
                uppercase: /[A-Z]/.test(password),
                number: /[0-9]/.test(password)
            };

            // Update length requirement
            updateRequirement(reqLength, requirements.length);

            // Update uppercase requirement
            updateRequirement(reqUppercase, requirements.uppercase);

            // Update number requirement
            updateRequirement(reqNumber, requirements.number);

            return requirements;
        }

        // Update individual requirement
        function updateRequirement(element, isMet) {
            const icon = element.querySelector('i');
            if (isMet) {
                icon.className = 'bi bi-check-circle-fill text-success';
                element.querySelector('span').classList.add('text-success');
            } else {
                icon.className = 'bi bi-circle text-secondary';
                element.querySelector('span').classList.remove('text-success');
            }
        }

        // Calculate password strength
        function calculateStrength(password, requirements) {
            if (password.length === 0) {
                strengthBar.style.width = '0%';
                strengthBar.style.backgroundColor = '#e9ecef';
                strengthText.textContent = '-';
                strengthText.className = 'text-secondary fw-semibold';
                return;
            }

            let strength = 0;
            if (requirements.length) strength += 33;
            if (requirements.uppercase) strength += 33;
            if (requirements.number) strength += 34;

            // Update strength bar
            strengthBar.style.width = strength + '%';

            // Update color and text based on strength
            if (strength < 50) {
                strengthBar.style.backgroundColor = '#dc3545';
                strengthText.textContent = 'Weak';
                strengthText.className = 'text-danger fw-semibold';
            } else if (strength < 100) {
                strengthBar.style.backgroundColor = '#ffc107';
                strengthText.textContent = 'Medium';
                strengthText.className = 'text-warning fw-semibold';
            } else {
                strengthBar.style.backgroundColor = '#28a745';
                strengthText.textContent = 'Strong';
                strengthText.className = 'text-success fw-semibold';
            }
        }

        // Check if passwords match
        function checkPasswordMatch() {
            const password = passwordInput.value;
            const confirm = confirmInput.value;

            if (confirm.length === 0) {
                matchMessage.textContent = '';
                matchMessage.className = '';
                return;
            }

            if (password === confirm) {
                matchMessage.textContent = '✓ Passwords match';
                matchMessage.className = 'text-success small';
            } else {
                matchMessage.textContent = '✗ Passwords do not match';
                matchMessage.className = 'text-danger small';
            }
        }

        // Password input event listener
        passwordInput.addEventListener('input', function() {
            const password = this.value;
            const requirements = checkRequirements(password);
            calculateStrength(password, requirements);
            checkPasswordMatch();
        });

        // Confirm password input event listener
        confirmInput.addEventListener('input', checkPasswordMatch);

        // Form validation before submit
        document.getElementById('resetPasswordForm').addEventListener('submit', function(e) {
            const password = passwordInput.value;
            const confirm = confirmInput.value;

            // Check if passwords match
            if (password !== confirm) {
                e.preventDefault();
                alert('Passwords do not match!');
                return false;
            }

            // Check password requirements
            if (password.length < 8) {
                e.preventDefault();
                alert('Password must be at least 8 characters long!');
                return false;
            }

            if (!/[A-Z]/.test(password)) {
                e.preventDefault();
                alert('Password must contain at least one uppercase letter!');
                return false;
            }

            if (!/[0-9]/.test(password)) {
                e.preventDefault();
                alert('Password must contain at least one number!');
                return false;
            }

            // Disable submit button to prevent double submission
            document.getElementById('submitBtn').disabled = true;
            document.getElementById('submitBtn').innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Updating...';
        });

        // Toggle password visibility
        document.getElementById('togglePassword').addEventListener('click', function() {
            const passwordField = document.getElementById('new-password');
            const icon = this.querySelector('i');
            if (passwordField.type === 'password') {
                passwordField.type = 'text';
                icon.classList.remove('bi-eye-slash');
                icon.classList.add('bi-eye');
            } else {
                passwordField.type = 'password';
                icon.classList.remove('bi-eye');
                icon.classList.add('bi-eye-slash');
            }
        });

        document.getElementById('toggleConfirmPassword').addEventListener('click', function() {
            const passwordField = document.getElementById('confirm-password');
            const icon = this.querySelector('i');
            if (passwordField.type === 'password') {
                passwordField.type = 'text';
                icon.classList.remove('bi-eye-slash');
                icon.classList.add('bi-eye');
            } else {
                passwordField.type = 'password';
                icon.classList.remove('bi-eye');
                icon.classList.add('bi-eye-slash');
            }
        });
    </script>
</body>

</html>