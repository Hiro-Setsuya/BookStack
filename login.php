<?php
session_start();
require_once 'config/db.php';
require_once 'includes/voucher-utils.php';
require_once 'includes/form-input.php';

$error = "";

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $identifier = trim($_POST['identifier']); // Can be email or phone
    $password = $_POST['password'];

    // Validation
    if (empty($identifier) || empty($password)) {
        $error = "All fields are required.";
    } else {
        // Check if identifier is email or phone number
        $identifier_escaped = mysqli_real_escape_string($conn, $identifier);

        // Query to check both email and phone_number
        $query = "SELECT user_id, user_name, email, phone_number, password_hash, role 
                  FROM users 
                  WHERE email = '$identifier_escaped' OR phone_number = '$identifier_escaped'";

        $result = executeQuery($query);

        if ($result && mysqli_num_rows($result) > 0) {
            $user = mysqli_fetch_assoc($result);

            // Verify password
            if (password_verify($password, $user['password_hash'])) {
                // Set session variables
                $_SESSION['user_id'] = $user['user_id'];
                $_SESSION['user_name'] = $user['user_name'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['role'] = $user['role'];

                // Issue welcome voucher on first login (if not already issued)
                issueWelcomeVoucher($conn, $user['user_id']);

                // Redirect to index.php
                header('Location: index.php');
                exit;
            } else {
                $error = "Invalid credentials. Please try again.";
            }
        } else {
            // User not found locally, check EscaPinas system
            $escapinas_api_url = "http://192.168.1.10/EscaPinas/frontend/integs/api/users1.php";
            $escapinas_response = @file_get_contents($escapinas_api_url);

            if ($escapinas_response) {
                $escapinas_users = json_decode($escapinas_response, true);
                if ($escapinas_users && is_array($escapinas_users)) {
                    foreach ($escapinas_users as $escapinas_user) {
                        $escapinas_email = isset($escapinas_user['email']) ? strtolower(trim($escapinas_user['email'])) : '';
                        $escapinas_phone = isset($escapinas_user['contact_num']) ? trim($escapinas_user['contact_num']) : '';

                        // Check if identifier matches email or phone from EscaPinas
                        if ($escapinas_email === strtolower($identifier) || $escapinas_phone === $identifier) {
                            // Verify password against EscaPinas hash
                            $escapinas_hash = isset($escapinas_user['password']) ? $escapinas_user['password'] : '';

                            if (!empty($escapinas_hash) && password_verify($password, $escapinas_hash)) {
                                // Valid EscaPinas user, check if already exists in BookStack
                                $escapinas_username = isset($escapinas_user['username']) && !empty($escapinas_user['username']) ? mysqli_real_escape_string($conn, $escapinas_user['username']) : 'User_' . time();
                                $escapinas_email_escaped = mysqli_real_escape_string($conn, $escapinas_email);
                                $escapinas_hash_escaped = mysqli_real_escape_string($conn, $escapinas_hash);
                                $escapinas_phone_escaped = !empty($escapinas_phone) ? mysqli_real_escape_string($conn, $escapinas_phone) : NULL;

                                // Check if user already exists by email or phone
                                $check_query = "SELECT user_id, user_name, email, role FROM users WHERE email = '$escapinas_email_escaped'";
                                if ($escapinas_phone_escaped) {
                                    $check_query .= " OR phone_number = '$escapinas_phone_escaped'";
                                }
                                $check_result = executeQuery($check_query);

                                if ($check_result && mysqli_num_rows($check_result) > 0) {
                                    // User already exists, update password hash and log them in
                                    $existing_user = mysqli_fetch_assoc($check_result);

                                    // Update password hash to match EscaPinas
                                    $update_query = "UPDATE users SET password_hash = '$escapinas_hash_escaped' WHERE user_id = " . $existing_user['user_id'];
                                    executeQuery($update_query);

                                    $_SESSION['user_id'] = $existing_user['user_id'];
                                    $_SESSION['user_name'] = $existing_user['user_name'];
                                    $_SESSION['email'] = $existing_user['email'];
                                    $_SESSION['role'] = $existing_user['role'];

                                    header('Location: index.php');
                                    exit;
                                } else {
                                    // Create new user in BookStack
                                    if ($escapinas_phone_escaped) {
                                        $insert_query = "INSERT INTO users (user_name, email, phone_number, password_hash) VALUES ('$escapinas_username', '$escapinas_email_escaped', '$escapinas_phone_escaped', '$escapinas_hash_escaped')";
                                    } else {
                                        $insert_query = "INSERT INTO users (user_name, email, password_hash) VALUES ('$escapinas_username', '$escapinas_email_escaped', '$escapinas_hash_escaped')";
                                    }

                                    $insert_result = executeQuery($insert_query);

                                    if ($insert_result) {
                                        $user_id = mysqli_insert_id($conn);

                                        // Set session variables
                                        $_SESSION['user_id'] = $user_id;
                                        $_SESSION['user_name'] = $escapinas_username;
                                        $_SESSION['email'] = $escapinas_email;
                                        $_SESSION['role'] = 'user';

                                        // Issue welcome voucher
                                        issueWelcomeVoucher($conn, $user_id);

                                        // Redirect to index.php
                                        header('Location: index.php');
                                        exit;
                                    } else {
                                        // Insert failed, possibly duplicate - try to find existing user
                                        $retry_check = "SELECT user_id, user_name, email, role FROM users WHERE email = '$escapinas_email_escaped'";
                                        $retry_result = executeQuery($retry_check);

                                        if ($retry_result && mysqli_num_rows($retry_result) > 0) {
                                            $existing_user = mysqli_fetch_assoc($retry_result);

                                            // Update password and log in
                                            $update_query = "UPDATE users SET password_hash = '$escapinas_hash_escaped' WHERE user_id = " . $existing_user['user_id'];
                                            executeQuery($update_query);

                                            $_SESSION['user_id'] = $existing_user['user_id'];
                                            $_SESSION['user_name'] = $existing_user['user_name'];
                                            $_SESSION['email'] = $existing_user['email'];
                                            $_SESSION['role'] = $existing_user['role'];

                                            header('Location: index.php');
                                            exit;
                                        }
                                    }
                                }
                            }
                            break;
                        }
                    }
                }
            }

            $error = "Invalid credentials. Please try again.";
        }
    }
}

$title = 'Login';
$extraStyles = '<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" rel="stylesheet" />' . "\n";
ob_start();
renderFloatingInputStyles();
$extraStyles .= ob_get_clean();
include 'includes/head.php';
?>

<body>
    <div class="container py-5">
        <div class="position-absolute top-0 start-0 w-100 p-4 d-flex justify-content-between align-items-center px-lg-5 shadow-sm">
            <a href="index.php" class="navbar-brand">
                <img src="assets/img/logo/logo.svg" height="30" alt="Logo">
                <span>BookStack</span>
            </a>
        </div>

        <div class="row align-items-center g-5 mt-4 mt-lg-0">
            <div class="col-lg-6 d-none d-lg-block">
                <div class="ps-lg-5">
                    <h1 class="login-title mb-4">Your digital library, <br><span class="highlight">unlocked.</span></h1>
                    <p class="text-secondary fs-5 mb-5" style="max-width: 450px;">Access variety of e-books about Technologies, Programming Languange, and UI/UX Designs anywhere, anytime.</p>

                    <div class="position-relative">
                        <img src="https://www.monergism.com/sites/default/files/content_images/e-books.jpg" class="login-img img-fluid" alt="Digital Library">
                    </div>
                </div>
            </div>

            <div class="col-lg-5 offset-lg-1">
                <div class="login-card">
                    <h2 class="fw-bold mb-2">Welcome Back</h2>
                    <p class="text-muted small mb-4">Log in to access your library</p>

                    <?php
                    $error_message = !empty($error) ? $error : '';
                    include 'includes/notification.php';
                    ?>

                    <form method="POST" action="">
                        <?php renderFloatingInput([
                            'type' => 'text',
                            'name' => 'identifier',
                            'id' => 'identifier',
                            'label' => 'Email or Phone Number',
                            'placeholder' => 'Email or Phone Number',
                            'value' => $_POST['identifier'] ?? '',
                            'required' => true,
                            'autocomplete' => 'username',
                            'input_class' => !empty($error) ? 'is-invalid' : ''
                        ]); ?>

                        <div class="position-relative mb-2">
                            <?php renderFloatingInput([
                                'type' => 'password',
                                'name' => 'password',
                                'id' => 'passwordInput',
                                'label' => 'Password',
                                'placeholder' => 'Password',
                                'required' => true,
                                'autocomplete' => 'current-password',
                                'class' => 'mb-0',
                                'input_class' => !empty($error) ? 'is-invalid' : '',
                                'attributes' => ['style' => 'padding-right: 45px;']
                            ]); ?>

                            <button type="button" onclick="togglePassword()" class="btn-toggle-pw">
                                <span class="material-symbols-outlined" id="eyeIcon">visibility</span>
                            </button>
                        </div>

                        <div class="d-flex justify-content-end mb-2">
                            <a href="forgot-password.php" class="text-decoration-none" style="color: #6c757d; font-size: 0.875rem; transition: color 0.2s;">Forgot Password?</a>
                        </div>

                        <button type="submit" class="btn btn-login w-100 mb-4 shadow">Log In</button>
                    </form>

                    <div class="text-center">
                        <p class="small text-muted mb-0">Don't have an account? <a href="register.php" class="text-success text-decoration-none fw-bold">Create Account</a></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php include 'includes/footer.php'; ?>

    <script>
        function togglePassword() {
            const passwordInput = document.getElementById('passwordInput');
            const eyeIcon = document.getElementById('eyeIcon');

            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                eyeIcon.textContent = 'visibility_off';
            } else {
                passwordInput.type = 'password';
                eyeIcon.textContent = 'visibility';
            }
        }
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js">
    </script>
</body>

</html>