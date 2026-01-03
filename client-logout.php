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

    <!-- Google Fonts: Manrope -->
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@200..800&display=swap" rel="stylesheet" />

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <style>
        .logout-container {
            /* Changed min-height to account for the space taken by nav and footer */
            min-height: calc(100vh - 200px);
            display: flex;
            align-items: center;
            justify-content: center;
            /* Added 80px top padding to clear the navbar */
            padding: 80px 0 2rem 0;
        }

        .logout-card {
            max-width: 500px;
            width: 100%;
            border-radius: 1rem;
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.1);
            overflow: hidden;
            /* Ensure the card doesn't touch the very top on small screens */
            margin-top: 20px;
        }

        .icon-container {
            width: 5rem;
            height: 5rem;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background-color: rgba(46, 204, 113, 0.1);
            border-radius: 50%;
            margin-bottom: 1.5rem;
        }
    </style>
</head>

<body>
    <?php include 'includes/nav.php'; ?>

    <div class="logout-container">
        <div class="container">
            <div class="logout-card mx-auto">
                <div class="card-header text-white text-center py-3" style="background-color: #2ecc71;">
                    <h5 class="mb-0 fw-bold">
                        <i class="bi bi-box-arrow-right me-2"></i>Confirm Logout
                    </h5>
                </div>
                <div class="card-body text-center p-5">
                    <div class="icon-container mx-auto">
                        <i class="bi bi-box-arrow-right fs-1" style="color: #2ecc71;"></i>
                    </div>
                    <h4 class="mb-3 fw-bold">Are you sure you want to logout?</h4>
                    <p class="text-muted mb-0">
                        You will need to log back in to access your account.
                    </p>
                </div>
                <div class="card-footer bg-white border-top p-4">
                    <div class="d-grid gap-2 d-sm-flex">
                        <a href="profile.php" class="btn btn-outline-secondary flex-fill py-2">
                            <i class="bi bi-x-circle me-2"></i>Cancel
                        </a>
                        <form method="POST" class="d-flex flex-fill">
                            <input type="hidden" name="confirm_logout" value="yes">
                            <button type="submit" class="btn w-100 py-2" style="background-color: #2ecc71; color: white;">
                                <i class="bi bi-box-arrow-right me-2"></i>Logout
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