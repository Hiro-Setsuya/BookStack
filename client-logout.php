<?php
session_start();

// Check if the confirmation was received via POST
if (isset($_POST['confirm_logout']) && $_POST['confirm_logout'] === 'yes') {
    // Unset all session variables
    unset($_SESSION['user_id']);
    unset($_SESSION['user_logged_in']);
    unset($_SESSION['user_email']);
    unset($_SESSION['user_name']);

    // Destroy the session
    session_destroy();

    // Redirect to login
    header('Location: login.php');
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Logging Out - BookStack</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #2ecc71;
            --primary-hover: #37b278;
            --text-dark: #333333;
            --text-muted: #666666;
            --bg-light: #f8f9fa;
            --border-color: #e9ecef;
            --primary-rgba: rgba(25, 135, 84, 0.9);
            --input-bg: #b7ffd5;
            --card-bg: #e1ffee;
        }

        .bg-primary-custom {
            background-color: var(--primary-color) !important;
        }

        .text-muted-custom {
            color: var(--text-muted) !important;
        }

        .btn-primary-custom {
            background-color: var(--primary-color) !important;
            border-color: var(--primary-color) !important;
            color: white !important;
        }

        .btn-primary-custom:hover {
            background-color: var(--primary-hover) !important;
            border-color: var(--primary-hover) !important;
            color: white !important;
        }

        .text-primary-custom {
            color: var(--primary-color) !important;
        }

        .modal-content {
            border-radius: 1rem !important;
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15) !important;
        }

        .modal-dialog {
            max-width: 28rem !important;
        }

        .icon-container {
            width: 4rem;
            height: 4rem;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background-color: var(--card-bg);
            border-radius: 50%;
            margin-bottom: 1rem;
        }
    </style>
</head>

<body class="bg-light">
    <div class="modal fade show d-block" id="logoutModal" tabindex="-1" style="background-color: rgba(0,0,0,0.5);">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-primary-custom text-white">
                    <h5 class="modal-title fw-bold">
                        <i class="fas fa-sign-out-alt me-2"></i>Confirm Logout
                    </h5>
                    <button type="button" class="btn-close btn-close-white" onclick="window.location.href='../index.php'"></button>
                </div>
                <div class="modal-body text-center">
                    <div class="icon-container mx-auto">
                        <i class="fas fa-sign-out-alt text-primary-custom fs-3"></i>
                    </div>
                    <h5 class="mb-3 fw-semibold">Are you sure you want to logout?</h5>
                    <p class="text-muted-custom mb-0">
                        You will need to log back in to access your account.
                    </p>
                </div>
                <div class="modal-footer">
                    <div class="d-grid gap-2 d-md-flex w-100">
                        <button type="button" class="btn btn-outline-secondary flex-fill py-2"
                            onclick="window.location.href='profile.php'">
                            <i class="fas fa-times me-2"></i>Cancel
                        </button>
                        <form method="POST" class="d-flex flex-fill">
                            <input type="hidden" name="confirm_logout" value="yes">
                            <button type="submit" class="btn btn-primary-custom flex-fill py-2">
                                <i class="fas fa-sign-out-alt me-2"></i>Logout
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php include 'includes/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>