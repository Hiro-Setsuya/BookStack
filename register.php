<?php
session_start();
require_once 'config/db.php';
require_once 'notifications/send-email.php';

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
    } elseif (strlen($password) < 6) {
        $error = "Password must be at least 6 characters long.";
    } elseif ($password !== $confirm_password) {
        $error = "Passwords do not match.";
    } elseif (!$terms) {
        $error = "You must agree to the Terms of Service and Privacy Policy.";
    } else {
        // Check if username already exists
        $username_escaped = mysqli_real_escape_string($conn, $username);
        $check_username_query = "SELECT user_id FROM users WHERE user_name = '$username_escaped'";
        $result_username = executeQuery($check_username_query);

        // Check if email already exists
        $email_escaped = mysqli_real_escape_string($conn, $email);
        $check_email_query = "SELECT user_id FROM users WHERE email = '$email_escaped'";
        $result_email = executeQuery($check_email_query);

        if (mysqli_num_rows($result_username) > 0) {
            $error = "Username already taken. Please choose another one.";
        } elseif (mysqli_num_rows($result_email) > 0) {
            $error = "Email already registered. Please use another email or login.";
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
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>BookStack</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="style.css">
</head>


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
                        <img src="assets/logo.svg" height="60" width="60" alt="Logo">
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
                        <img src="assets/logo.svg" height="25" width="25" alt="Logo">
                        <span class="fs-4 fw-bold">EduBooks</span>
                    </div>
                    <!-- Header -->
                    <div class="mb-4 text-center text-lg-start">
                        <h2 class="fs-1 fw-bold mb-2">Start your learning journey</h2>
                        <p class="text-muted fs-6">Create an account to access thousands of textbooks.</p>
                    </div>

                    <!-- Alert Messages -->
                    <?php if (!empty($error)): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <strong>Error!</strong> <?php echo htmlspecialchars($error); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($success)): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <strong>Success!</strong> <?php echo htmlspecialchars($success); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <!-- Form -->
                    <form method="POST" action="">
                        <!-- Username Field -->
                        <div class="mb-3">
                            <label class="form-label fw-bold small">Username</label>
                            <div class="position-relative">
                                <input type="text" name="username" class="form-control py-3 ps-5 rounded-3" placeholder="Enter your username" style="height: 56px;" value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>" required />
                                <span class="material-symbols-outlined input-icon">person</span>
                            </div>
                        </div>
                        <!-- Email Field -->
                        <div class="mb-3">
                            <label class="form-label fw-bold small">Email</label>
                            <div class="position-relative">
                                <input type="email" name="email" class="form-control py-3 ps-5 rounded-3" placeholder="your.email@example.com" style="height: 56px;" value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" required />
                                <span class="material-symbols-outlined input-icon">mail</span>
                            </div>
                        </div>
                        <div class="row g-3 mb-3">
                            <!-- Password Field -->
                            <div class="col-12 col-md-6">
                                <label class="form-label fw-bold small">Password</label>
                                <div class="position-relative">
                                    <input type="password" name="password" class="form-control py-3 ps-5 rounded-3" placeholder="••••••••" style="height: 56px;" minlength="6" required />
                                    <span class="material-symbols-outlined input-icon">lock</span>
                                </div>
                            </div>
                            <!-- Confirm Password Field -->
                            <div class="col-12 col-md-6">
                                <label class="form-label fw-bold small">Confirm Password</label>
                                <div class="position-relative">
                                    <input type="password" name="confirm_password" class="form-control py-3 ps-5 rounded-3" placeholder="••••••••" style="height: 56px;" minlength="6" required />
                                    <span class="material-symbols-outlined input-icon">lock_reset</span>
                                </div>
                            </div>
                        </div>
                        <!-- Terms Checkbox -->
                        <div class="form-check mb-4">
                            <input class="form-check-input" type="checkbox" name="terms" id="terms" style="width: 20px; height: 20px;" required />
                            <label class="form-check-label small text-muted ms-2" for="terms">
                                By creating an account, you agree to our <a href="#" class="fw-bold text-reg text-decoration-none">Terms of Service</a> and <a href="#" class="fw-bold text-reg text-decoration-none">Privacy Policy</a>.
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>