<?php
session_start();
require_once 'config/db.php';

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

                // Redirect to index.php
                header('Location: index.php');
                exit;
            } else {
                $error = "Invalid credentials. Please try again.";
            }
        } else {
            $error = "Invalid credentials. Please try again.";
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
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" rel="stylesheet" />

    <style>
        :root {
            --bg-dark: #ffffffff;
            --card-bg: rgba(167, 255, 211, 0.8);
            --accent-green: #2ecc71;
            --text-muted: #72817bff;
            --input-bg: #ffffffff;
        }

        body {
            font-family: 'Inter', sans-serif;
            background-color: var(--bg-dark);
            color: #2ecc71;
            min-height: 100vh;
            display: flex;
            align-items: center;
            position: relative;
            overflow-x: hidden;
        }

        body::before {
            content: "";
            position: absolute;
            top: -10%;
            left: -10%;
            width: 50%;
            height: 50%;
            background: radial-gradient(circle, rgba(46, 204, 113, 0.1) 0%, rgba(5, 10, 8, 0) 70%);
            z-index: -1;
        }

        .navbar-brand {
            font-weight: 900;
            color: #28bd66ff;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .login-title {
            font-size: 3rem;
            font-weight: 700;
            line-height: 1.1;
            letter-spacing: -1px;
        }

        .login-title .highlight {
            color: var(--accent-green);
        }

        /* Glassmorphism card effect */
        .login-card {
            background: var(--card-bg);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.05);
            border-radius: 28px;
            padding: 3rem;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
        }

        .form-label {
            font-size: 0.85rem;
            font-weight: 600;
            color: var(--text-muted);
            margin-bottom: 8px;
        }

        .form-control {
            background-color: var(--input-bg);
            border: 1px solid rgba(255, 255, 255, 0.1);
            color: #1b7c43ff;
            border-radius: 12px;
            padding: 12px 16px;
            transition: all 0.3s;
        }

        .form-control:focus {
            background-color: var(--input-bg);
            border-color: var(--accent-green);
            box-shadow: 0 0 0 4px rgba(46, 204, 113, 0.1);
            color: #1b7c43ff;
        }

        .position-relative {
            position: relative !important;
        }

        .btn-toggle-pw {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            padding: 0;
            color: #8a9a94;
            cursor: pointer;
            z-index: 10;
            display: flex;
            align-items: center;
        }

        .btn-toggle-pw:hover {
            color: #2ecc71;
        }

        .btn-primary {
            background-color: var(--accent-green);
            border: none;
            color: #ffffff;
            font-weight: 700;
            border-radius: 12px;
            padding: 14px;
            font-size: 1rem;
            transition: all 0.3s ease;
        }

        .btn-primary:hover {
            background-color: #27ae60 !important;
            color: #ffffff !important;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(46, 204, 113, 0.4);
        }

        .btn-primary:active,
        .btn-primary:active:focus {
            background-color: #229954 !important;
            color: #ffffff !important;
            border-color: #229954 !important;
            transform: translateY(0);
            box-shadow: 0 2px 6px rgba(46, 204, 113, 0.3);
        }

        .btn-primary:focus,
        .btn-primary:focus-visible {
            background-color: var(--accent-green) !important;
            color: #ffffff !important;
            border-color: var(--accent-green) !important;
            box-shadow: 0 0 0 4px rgba(46, 204, 113, 0.2);
        }

        .login-img {
            border-radius: 24px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.4);
            max-width: 70%;
        }

        .footer-link {
            color: var(--text-muted);
            text-decoration: none;
            font-size: 0.85rem;
        }

        .footer-link:hover {
            color: var(--accent-green);
        }
    </style>
</head>

<body>
    <div class="container py-5">
        <div class="position-absolute top-0 start-0 w-100 p-4 d-flex justify-content-between align-items-center px-lg-5 shadow-sm">
            <a href="index.php" class="navbar-brand">
                <img src="assets/logo.svg" height="30" alt="Logo">
                <span>BookStack</span>
            </a>
        </div>

        <div class="row align-items-center g-5 mt-4 mt-lg-0">
            <div class="col-lg-6 d-none d-lg-block">
                <div class="ps-lg-5">
                    <h1 class="login-title mb-4">Your digital library, <br><span class="highlight">unlocked.</span></h1>
                    <p class="text-secondary fs-5 mb-5" style="max-width: 450px;">Access thousands of e-books, research papers, and academic journals anywhere, anytime.</p>

                    <div class="position-relative">
                        <img src="https://images.unsplash.com/photo-1512820790803-83ca734da794?q=80&w=800&auto=format&fit=crop" class="login-img img-fluid" alt="Digital Library">
                    </div>
                </div>
            </div>

            <div class="col-lg-5 offset-lg-1">
                <div class="login-card">
                    <h2 class="fw-bold mb-2">Welcome Back</h2>
                    <p class="text-muted small mb-4">Sign in to continue to your library</p>

                    <?php if (!empty($error)): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <strong>Error!</strong> <?php echo htmlspecialchars($error); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="">
                        <div class="mb-3">
                            <label class="form-label">Email or Phone Number</label>
                            <input type="text" name="identifier" class="form-control" placeholder="Enter email or phone" value="<?php echo isset($_POST['identifier']) ? htmlspecialchars($_POST['identifier']) : ''; ?>" required>
                        </div>
                        <div class="mb-4">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <label class="form-label mb-0">Password</label>
                                <a href="forgot-password.php" class="footer-link">Forgot Password?</a>
                            </div>
                            <div class="position-relative">
                                <input type="password" name="password" id="passwordInput" class="form-control" placeholder="Enter your password" required style="padding-right: 45px;">

                                <button type="button" onclick="togglePassword()" class="btn-toggle-pw">
                                    <span class="material-symbols-outlined" id="eyeIcon">visibility</span>
                                </button>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary w-100 mb-4 shadow">Sign In</button>
                    </form>

                    <div class="text-center">
                        <p class="small text-muted mb-0">Don't have an account? <a href="register.php" class="text-success text-decoration-none fw-bold">Create Account</a></p>
                    </div>
                </div>
            </div>
        </div>
    </div>

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
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js">
    </script>
</body>

</html>