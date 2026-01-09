<?php
session_start();

// Check if the confirmation was received via POST
if (isset($_POST['confirm_logout']) && $_POST['confirm_logout'] === 'yes') {
    // Unset all session variables
    unset($_SESSION['admin_id']);
    unset($_SESSION['admin_logged_in']);
    unset($_SESSION['admin_email']);
    unset($_SESSION['admin_name']);

    // Destroy the session
    session_destroy();

    // Redirect to login
    header('Location: login.php');
    exit();
}

$title = 'Logging Out';
$extraStyles = '<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">';
include '../includes/head.php';
?>

<body class="bg-light">
    <div class="modal fade show d-block" id="logoutModal" tabindex="-1" style="background-color: rgba(0,0,0,0.5);">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-primary-custom text-white">
                    <h5 class="modal-title fw-bold">
                        <i class="fas fa-sign-out-alt me-2"></i>Confirm Logout
                    </h5>
                    <button type="button" class="btn-close btn-close-white" onclick="window.location.href='dashboard.php'"></button>
                </div>
                <div class="modal-body text-center">
                    <div class="icon-container mx-auto">
                        <i class="fas fa-sign-out-alt text-primary-custom fs-3"></i>
                    </div>
                    <h5 class="mb-3 fw-semibold">Are you sure you want to logout?</h5>
                    <p class="text-muted-custom mb-0">
                        You will need to log back in to access the admin panel.
                    </p>
                </div>
                <div class="modal-footer">
                    <div class="d-grid gap-2 d-md-flex w-100">
                        <button type="button" class="btn btn-outline-secondary flex-fill py-2"
                            onclick="window.location.href='dashboard.php'">
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>