<?php
session_start();
require_once 'config/db.php';
require_once 'config/api-connection.php';
require_once 'includes/voucher-utils.php';
require_once 'includes/form-input.php';

$error = "";

/**
 * Fetch users from EscaPinas API
 * @return array|null Array of users or null on failure
 */
function fetchEscaPinasUsers()
{
    $response = @file_get_contents(ESCAPINAS_API_USERS);
    if (!$response) {
        return null;
    }
    return json_decode($response, true);
}

/**
 * Find user in EscaPinas data by identifier (email or phone)
 * @param array $escapinas_users Array of users from API
 * @param string $identifier Email or phone to search for
 * @return array|null User data or null if not found
 */
function findEscaPinasUser($escapinas_users, $identifier)
{
    if (!$escapinas_users || !is_array($escapinas_users)) {
        return null;
    }

    $identifier_lower = strtolower($identifier);

    foreach ($escapinas_users as $user) {
        $email = isset($user['email']) ? strtolower(trim($user['email'])) : '';
        $phone = isset($user['contact_num']) ? trim($user['contact_num']) : '';

        if ($email === $identifier_lower || $phone === $identifier) {
            return $user;
        }
    }

    return null;
}

/**
 * Create or update user from EscaPinas data
 * @param mysqli $conn Database connection
 * @param array $escapinas_user User data from EscaPinas
 * @param string $password Password to verify
 * @return array|null User session data or null on failure
 */
function syncEscaPinasUser($conn, $escapinas_user, $password)
{
    // Extract user data
    $email = isset($escapinas_user['email']) ? strtolower(trim($escapinas_user['email'])) : '';
    $phone = isset($escapinas_user['contact_num']) ? trim($escapinas_user['contact_num']) : '';
    $password_hash = isset($escapinas_user['password']) ? $escapinas_user['password'] : '';
    $username = isset($escapinas_user['username']) && !empty($escapinas_user['username'])
        ? $escapinas_user['username']
        : 'User_' . time();

    // Verify password
    if (empty($password_hash) || !password_verify($password, $password_hash)) {
        return null;
    }

    // Escape data
    $email_escaped = mysqli_real_escape_string($conn, $email);
    $phone_escaped = !empty($phone) ? mysqli_real_escape_string($conn, $phone) : null;
    $username_escaped = mysqli_real_escape_string($conn, $username);
    $hash_escaped = mysqli_real_escape_string($conn, $password_hash);

    // Check if user exists
    $check_query = "SELECT user_id, user_name, email, role FROM users WHERE email = '$email_escaped'";
    if ($phone_escaped) {
        $check_query .= " OR phone_number = '$phone_escaped'";
    }
    $check_result = executeQuery($check_query);

    if ($check_result && mysqli_num_rows($check_result) > 0) {
        // Update existing user
        $existing_user = mysqli_fetch_assoc($check_result);
        $update_query = "UPDATE users SET password_hash = '$hash_escaped' WHERE user_id = " . $existing_user['user_id'];
        executeQuery($update_query);

        return [
            'user_id' => $existing_user['user_id'],
            'user_name' => $existing_user['user_name'],
            'email' => $existing_user['email'],
            'role' => $existing_user['role']
        ];
    }

    // Create new user
    if ($phone_escaped) {
        $insert_query = "INSERT INTO users (user_name, email, phone_number, password_hash) 
                        VALUES ('$username_escaped', '$email_escaped', '$phone_escaped', '$hash_escaped')";
    } else {
        $insert_query = "INSERT INTO users (user_name, email, password_hash) 
                        VALUES ('$username_escaped', '$email_escaped', '$hash_escaped')";
    }

    $insert_result = executeQuery($insert_query);

    if ($insert_result) {
        $user_id = mysqli_insert_id($conn);
        issueWelcomeVoucher($conn, $user_id);

        return [
            'user_id' => $user_id,
            'user_name' => $username,
            'email' => $email,
            'role' => 'user'
        ];
    }

    // Retry check if insert failed
    $retry_result = executeQuery("SELECT user_id, user_name, email, role FROM users WHERE email = '$email_escaped'");
    if ($retry_result && mysqli_num_rows($retry_result) > 0) {
        $existing_user = mysqli_fetch_assoc($retry_result);
        executeQuery("UPDATE users SET password_hash = '$hash_escaped' WHERE user_id = " . $existing_user['user_id']);

        return [
            'user_id' => $existing_user['user_id'],
            'user_name' => $existing_user['user_name'],
            'email' => $existing_user['email'],
            'role' => $existing_user['role']
        ];
    }

    return null;
}

/**
 * Set user session and redirect
 * @param array $user User data
 */
function loginUser($user)
{
    $_SESSION['user_id'] = $user['user_id'];
    $_SESSION['user_name'] = $user['user_name'];
    $_SESSION['email'] = $user['email'];
    $_SESSION['role'] = $user['role'];
    header('Location: index.php');
    exit;
}

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
                // Issue welcome voucher on first login
                issueWelcomeVoucher($conn, $user['user_id']);

                // Login user
                loginUser($user);
            } else {
                $error = "Invalid credentials. Please try again.";
            }
        } else {
            // User not found locally, check EscaPinas system
            $escapinas_users = fetchEscaPinasUsers();
            $escapinas_user = findEscaPinasUser($escapinas_users, $identifier);

            if ($escapinas_user) {
                $synced_user = syncEscaPinasUser($conn, $escapinas_user, $password);

                if ($synced_user) {
                    loginUser($synced_user);
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