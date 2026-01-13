<?php
session_start();
require_once 'config/db.php';
require_once 'config/api-connection.php';
require_once 'notifications/send-email.php';
require_once 'includes/form-input.php';

$error = "";
$success = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $terms = isset($_POST['terms']) ? true : false;

    // Validation
    if (empty($username) || empty($email) || empty($password) || empty($confirm_password)) {
        $error = "All fields are required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email format.";
    } elseif (strlen($password) < 8) {
        $error = "Password must be at least 8 characters long.";
    } elseif (!preg_match('/[A-Z]/', $password)) {
        $error = "Password must contain at least one uppercase letter.";
    } elseif (!preg_match('/[0-9]/', $password)) {
        $error = "Password must contain at least one number.";
    } elseif ($password !== $confirm_password) {
        $error = "Passwords do not match.";
    } elseif (!$terms) {
        $error = "You must agree to the Terms of Service and Privacy Policy.";
    } else {
        // Escape inputs first
        $username_escaped = mysqli_real_escape_string($conn, $username);
        $email_escaped = mysqli_real_escape_string($conn, $email);

        // Check if email already exists in BookStack
        $check_email_query = "SELECT user_id FROM users WHERE email = '$email_escaped'";
        $result_email = executeQuery($check_email_query);

        if (mysqli_num_rows($result_email) > 0) {
            $error = "Email already registered in BookStack. Please use another email or log in.";
        } else {
            // Check if email exists in EscaPinas system
            $email_exists_in_escapinas = false;
            $escapinas_response = @file_get_contents(ESCAPINAS_API_USERS);

            if ($escapinas_response) {
                $escapinas_users = json_decode($escapinas_response, true);
                if ($escapinas_users && is_array($escapinas_users)) {
                    foreach ($escapinas_users as $escapinas_user) {
                        if (isset($escapinas_user['email']) && strtolower(trim($escapinas_user['email'])) === strtolower($email)) {
                            $email_exists_in_escapinas = true;
                            break;
                        }
                    }
                }
            }

            if ($email_exists_in_escapinas) {
                $error = "Email already registered in EscaPinas system. Please log in instead.";
            } else {
                // Check if username already exists
                $check_username_query = "SELECT user_id FROM users WHERE user_name = '$username_escaped'";
                $result_username = executeQuery($check_username_query);

                if (mysqli_num_rows($result_username) > 0) {
                    $error = "Username already taken. Please choose another one.";
                } else {
                    // Hash password and insert user
                    $password_hash = password_hash($password, PASSWORD_DEFAULT);
                    $password_hash_escaped = mysqli_real_escape_string($conn, $password_hash);

                    $insert_query = "INSERT INTO users (user_name, email, password_hash) VALUES ('$username_escaped', '$email_escaped', '$password_hash_escaped')";

                    if (executeQuery($insert_query)) {
                        // Get the newly created user's ID
                        $user_id = mysqli_insert_id($conn);

                        // Get user role (default is 'user')
                        $role_query = "SELECT role FROM users WHERE user_id = '$user_id'";
                        $role_result = executeQuery($role_query);
                        $user_role = 'user';
                        if ($role_result && mysqli_num_rows($role_result) > 0) {
                            $user_data = mysqli_fetch_assoc($role_result);
                            $user_role = $user_data['role'];
                        }

                        // Set session variables to log the user in automatically
                        $_SESSION['user_id'] = $user_id;
                        $_SESSION['user_name'] = $username;
                        $_SESSION['email'] = $email;
                        $_SESSION['role'] = $user_role;

                        // Send welcome email
                        sendWelcomeEmail($email, $username);

                        $success = "Registration successful! Check your email for welcome message. Redirecting to home...";
                        header("refresh:2;url=index.php");
                    } else {
                        $error = "Registration failed: " . mysqli_error($conn);
                    }
                }
            }
        }
    }
}

$title = 'Register';
$extraStyles = '<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet" />' . "\n";
ob_start();
renderFloatingInputStyles();
$extraStyles .= ob_get_clean();
include 'includes/head.php';
?>

<body>
    <div class="container-fluid min-vh-100">
        <div class="row min-vh-100">
            <!-- Left Side: Hero Image & Branding (Hidden on mobile/tablet) -->
            <div class="col-lg-6 d-none d-lg-flex main-section align-items-center justify-content-center">
                <div class="main-bg"></div>
                <div class="main-overlay"></div>
                <!-- Hero Content -->
                <div class="main-content px-5" style="max-width: 600px;">
                    <div class="d-flex align-items-center gap-3 mb-4">
                        <img src="assets/img/logo/logo.svg" height="60" width="60" alt="Logo">
                        <h2 class="fs-2 fw-bold mb-0">BookStack</h2>
                    </div>
                    <h1 class="display-4 fw-bold mb-4">Expand your mind, <br />one page at a time.</h1>
                    <p class="fs-5 mb-5 opacity-75">Programming isn't about what you know; it's about what you can figure out.</p>
                </div>
            </div>
            <!-- Right Side: Registration Form -->
            <div class="col-12 col-lg-6 d-flex flex-column align-items-center justify-content-center p-4 p-lg-5">
                <div class="w-100" style="max-width: 520px;">
                    <!-- Mobile Logo (Visible only on smaller screens) -->
                    <div class="d-flex d-lg-none align-items-center gap-2 mb-4 justify-content-center">
                        <img src="assets/img/logo/logo.svg" height="25" width="25" alt="Logo">
                        <span class="fs-4 fw-bold">BookStack</span>
                    </div>
                    <!-- Header -->
                    <div class="mb-4 text-center text-lg-start">
                        <h2 class="fs-1 fw-bold mb-2">Start your learning journey</h2>
                        <p class="text-muted fs-6">Create an account to access thousands of textbooks.</p>
                    </div>

                    <!-- Alert Messages -->
                    <?php
                    $error_message = !empty($error) ? $error : '';
                    $success_message = !empty($success) ? $success : '';
                    include 'includes/notification.php';
                    ?>

                    <!-- Form -->
                    <form method="POST" action="">
                        <!-- Username Field -->
                        <?php renderFloatingInput([
                            'type' => 'text',
                            'name' => 'username',
                            'id' => 'username',
                            'label' => 'Username',
                            'placeholder' => 'Choose a unique username',
                            'value' => $_POST['username'] ?? '',
                            'required' => true,
                            'autocomplete' => 'username'
                        ]); ?>
                        <!-- Email Field -->
                        <?php renderFloatingInput([
                            'type' => 'email',
                            'name' => 'email',
                            'id' => 'email',
                            'label' => 'Email Address',
                            'placeholder' => 'yourname@example.com',
                            'value' => $_POST['email'] ?? '',
                            'required' => true,
                            'autocomplete' => 'email',
                            'class' => 'mb-1'
                        ]); ?>
                        <small id="emailFeedback" class="d-block mb-3 ms-1"></small>

                        <div class="row g-3">
                            <!-- Password Field -->
                            <div class="col-12">
                                <div class="position-relative">
                                    <?php renderFloatingInput([
                                        'type' => 'password',
                                        'name' => 'password',
                                        'id' => 'password',
                                        'label' => 'Password',
                                        'placeholder' => 'At least 8 characters',
                                        'required' => true,
                                        'autocomplete' => 'new-password',
                                        'minlength' => 8,
                                        'class' => 'mb-0',
                                        'attributes' => ['style' => 'padding-right: 45px;']
                                    ]); ?>
                                    <button type="button" onclick="togglePassword('password', 'eyeIconPassword')" class="btn-toggle-pw">
                                        <span class="material-symbols-outlined" id="eyeIconPassword">visibility</span>
                                    </button>
                                </div>
                                <!-- Password Strength Bar -->
                                <div class="mt-2" style="height: 6px; background: #e9ecef; border-radius: 3px; overflow: hidden;">
                                    <div id="strengthBar" style="height: 100%; width: 0%; transition: all 0.3s ease; background: #e9ecef;"></div>
                                </div>
                                <small class="text-muted d-block mt-2 ms-1" style="font-size: 0.8rem;">Password Strength: <span id="strengthText" class="fw-semibold">Not set</span></small>

                                <!-- Password Requirements -->
                                <div class="mt-3 p-3" style="background: linear-gradient(135deg, #f8f9fa 0%, #ffffff 100%); border-radius: 10px; border: 1px solid #dee2e6; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">
                                    <p class="fw-semibold mb-2" style="font-size: 0.8rem; color: #495057; letter-spacing: 0.3px;"><span class="material-symbols-outlined" style="font-size: 14px; vertical-align: middle; margin-right: 4px;">info</span>Requirements</p>
                                    <ul class="list-unstyled mb-0" style="font-size: 0.85rem; line-height: 1.8;">
                                        <li class="d-flex align-items-center" id="req-length">
                                            <span class="material-symbols-outlined" style="font-size: 18px; margin-right: 10px; color: #adb5bd; transition: all 0.2s;">radio_button_unchecked</span>
                                            <span style="color: #6c757d; transition: all 0.2s;">At least 8 characters</span>
                                        </li>
                                        <li class="d-flex align-items-center" id="req-uppercase">
                                            <span class="material-symbols-outlined" style="font-size: 18px; margin-right: 10px; color: #adb5bd; transition: all 0.2s;">radio_button_unchecked</span>
                                            <span style="color: #6c757d; transition: all 0.2s;">One uppercase letter (A-Z)</span>
                                        </li>
                                        <li class="d-flex align-items-center" id="req-number">
                                            <span class="material-symbols-outlined" style="font-size: 18px; margin-right: 10px; color: #adb5bd; transition: all 0.2s;">radio_button_unchecked</span>
                                            <span style="color: #6c757d; transition: all 0.2s;">One number (0-9)</span>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                        <!-- Confirm Password Field -->
                        <div class="mb-2 mt-3">
                            <div class="position-relative">
                                <?php renderFloatingInput([
                                    'type' => 'password',
                                    'name' => 'confirm_password',
                                    'id' => 'confirm_password',
                                    'label' => 'Confirm Password',
                                    'placeholder' => 'Re-enter your password',
                                    'required' => true,
                                    'autocomplete' => 'new-password',
                                    'minlength' => 8,
                                    'class' => 'mb-1',
                                    'attributes' => ['style' => 'padding-right: 45px;']
                                ]); ?>
                                <button type="button" onclick="togglePassword('confirm_password', 'eyeIconConfirm')" class="btn-toggle-pw">
                                    <span class="material-symbols-outlined" id="eyeIconConfirm">visibility</span>
                                </button>
                            </div>
                            <small id="matchMessage" class="d-block mt-2 ms-1" style="font-size: 0.85rem; min-height: 20px;"></small>
                        </div>
                        <!-- Terms Checkbox -->
                        <div class="mb-4 d-flex align-items-center" style="padding: 16px; background: #f8f9fa; border-radius: 10px; border: 1px solid #dee2e6;">
                            <input class="form-check-input" type="checkbox" name="terms" id="terms" style="width: 20px; height: 20px; flex-shrink: 0; cursor: pointer; margin-top: 2px;" required />
                            <label class="form-check-label small text-muted ms-3" for="terms" style="cursor: pointer; line-height: 1.5;">
                                By creating an account, you agree to our <a href="terms-condition.php" class="fw-bold text-reg text-decoration-none">Terms of Service</a> and <a href="terms-condition.php" class="fw-bold text-reg text-decoration-none">Privacy Policy</a>.
                            </label>
                        </div>
                        <!-- Submit Button -->
                        <button type="submit" class="btn btn-green w-100 py-3 fw-bold fs-6 shadow">
                            Create Account
                        </button>
                    </form>
                    <!-- Footer Link -->
                    <div class="text-center mt-4">
                        <p class="text-muted">
                            Already have an account?
                            <a href="login.php" class="fw-bold text-reg text-decoration-none">Log in</a>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php include 'includes/footer.php'; ?>

    <script>
        function togglePassword(inputId, iconId) {
            const passwordInput = document.getElementById(inputId);
            const eyeIcon = document.getElementById(iconId);

            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                eyeIcon.textContent = 'visibility_off';
            } else {
                passwordInput.type = 'password';
                eyeIcon.textContent = 'visibility';
            }
        }

        // Email validation
        const emailInput = document.getElementById('email');
        const emailFeedback = document.getElementById('emailFeedback');
        let emailTimeout;

        emailInput.addEventListener('input', function() {
            clearTimeout(emailTimeout);
            const email = this.value.trim();

            if (email.length === 0) {
                emailFeedback.textContent = '';
                emailFeedback.className = '';
                emailInput.classList.remove('is-invalid', 'is-valid');
                return;
            }

            // Basic email format check
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(email)) {
                emailFeedback.textContent = '✗ Invalid email format';
                emailFeedback.className = 'text-danger small';
                emailInput.classList.add('is-invalid');
                emailInput.classList.remove('is-valid');
                return;
            }

            // Check if email exists (debounced)
            emailTimeout = setTimeout(function() {
                fetch('api/check-email-exists.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: 'email=' + encodeURIComponent(email)
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.exists) {
                            emailFeedback.textContent = '✗ Email already registered';
                            emailFeedback.className = 'text-danger small';
                            emailInput.classList.add('is-invalid');
                            emailInput.classList.remove('is-valid');
                        } else if (data.valid) {
                            emailFeedback.textContent = '✓ Email available';
                            emailFeedback.className = 'text-success small';
                            emailInput.classList.remove('is-invalid');
                            emailInput.classList.add('is-valid');
                        }
                    })
                    .catch(error => {
                        console.error('Error checking email:', error);
                    });
            }, 500);
        });

        // Password strength validation
        const passwordInput = document.getElementById('password');
        const confirmInput = document.getElementById('confirm_password');
        const strengthBar = document.getElementById('strengthBar');
        const strengthText = document.getElementById('strengthText');
        const matchMessage = document.getElementById('matchMessage');

        const reqLength = document.getElementById('req-length');
        const reqUppercase = document.getElementById('req-uppercase');
        const reqNumber = document.getElementById('req-number');

        function checkRequirements(password) {
            const requirements = {
                length: password.length >= 8,
                uppercase: /[A-Z]/.test(password),
                number: /[0-9]/.test(password)
            };

            updateRequirement(reqLength, requirements.length);
            updateRequirement(reqUppercase, requirements.uppercase);
            updateRequirement(reqNumber, requirements.number);

            return requirements;
        }

        function updateRequirement(element, isMet) {
            const icon = element.querySelector('.material-symbols-outlined');
            const span = element.querySelector('span:last-child');
            if (isMet) {
                icon.textContent = 'check_circle';
                icon.style.color = '#1fd26a';
                icon.style.transform = 'scale(1.1)';
                span.style.color = '#1fd26a';
                span.style.fontWeight = '500';
            } else {
                icon.textContent = 'radio_button_unchecked';
                icon.style.color = '#adb5bd';
                icon.style.transform = 'scale(1)';
                span.style.color = '#6c757d';
                span.style.fontWeight = '400';
            }
        }

        function calculateStrength(password, requirements) {
            if (password.length === 0) {
                strengthBar.style.width = '0%';
                strengthBar.style.backgroundColor = '#e9ecef';
                strengthText.textContent = 'Not set';
                strengthText.className = 'fw-semibold text-muted';
                return;
            }

            let strength = 0;
            if (requirements.length) strength += 33;
            if (requirements.uppercase) strength += 33;
            if (requirements.number) strength += 34;

            strengthBar.style.width = strength + '%';

            if (strength < 50) {
                strengthBar.style.backgroundColor = '#dc3545';
                strengthText.textContent = 'Weak';
                strengthText.className = 'text-danger fw-semibold';
            } else if (strength < 100) {
                strengthBar.style.backgroundColor = '#ffc107';
                strengthText.textContent = 'Medium';
                strengthText.className = 'text-warning fw-semibold';
            } else {
                strengthBar.style.backgroundColor = '#1fd26a';
                strengthText.textContent = 'Strong';
                strengthText.className = 'text-success fw-semibold';
            }
        }

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

        passwordInput.addEventListener('input', function() {
            const requirements = checkRequirements(this.value);
            calculateStrength(this.value, requirements);
            checkPasswordMatch();
        });

        confirmInput.addEventListener('input', checkPasswordMatch);
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>