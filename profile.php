<?php
session_start();
require_once 'config/db.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$statusMessage = '';
$statusType = '';

// Handle form submission for profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $user_name = mysqli_real_escape_string($conn, $_POST['user_name']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $phone_number = mysqli_real_escape_string($conn, $_POST['phone_number']);

    // Check if email already exists for another user
    $check_query = "SELECT user_id FROM users WHERE email = '$email' AND user_id != $user_id";
    $check_result = executeQuery($check_query);

    if (mysqli_num_rows($check_result) > 0) {
        $statusMessage = 'Email address is already in use by another account.';
        $statusType = 'danger';
    } else {
        $update_query = "UPDATE users SET user_name = '$user_name', email = '$email', phone_number = '$phone_number' WHERE user_id = $user_id";
        $result = executeQuery($update_query);

        if ($result) {
            $statusMessage = 'Profile updated successfully!';
            $statusType = 'success';
        } else {
            $statusMessage = 'Error updating profile: ' . mysqli_error($conn);
            $statusType = 'danger';
        }
    }
}

// Fetch user data from database
$query = "SELECT user_id, user_name, email, phone_number, role, is_account_verified, created_at FROM users WHERE user_id = $user_id";
$result = executeQuery($query);

if ($result && mysqli_num_rows($result) > 0) {
    $user = mysqli_fetch_assoc($result);
} else {
    header('Location: login.php');
    exit;
}

// Get user initials for avatar
$name_parts = explode(' ', $user['user_name']);
$initials = strtoupper(substr($name_parts[0], 0, 1));
if (isset($name_parts[1])) {
    $initials .= strtoupper(substr($name_parts[1], 0, 1));
}

// Get member since date
$member_since = date('F Y', strtotime($user['created_at']));
?>
<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Account Settings - BookStack</title>

    <!-- Google Fonts: Manrope -->
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@200..800&display=swap" rel="stylesheet" />

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>

<body>
    <?php include 'includes/nav.php'; ?>

    <div class="container account-container">
        <div class="row">
            <div class="col-lg-3 mb-4">
                <div class="sidebar-section-label mb-3">Account</div>
                <nav class="nav flex-column mb-4">
                    <a class="sidebar-link active" href="profile.php"><i class="bi bi-person me-2"></i> General Profile</a>
                    <a class="sidebar-link" href="my-ebooks.php"><i class="bi bi-book me-2"></i> My E-Books</a>
                </nav>

                <div class="sidebar-section-label mb-3">Preferences</div>
                <nav class="nav flex-column">
                    <a class="sidebar-link" href="about.php"><i class="bi bi-info-circle me-2"></i> About</a>
                    <div class="sidebar-link d-flex justify-content-between align-items-center">
                        <span><i class="bi bi-moon me-2"></i> Dark Mode</span>
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox">
                        </div>
                    </div>
                </nav>

                <div class="mt-5">
                    <a href="client-logout.php" class="sidebar-link text-danger fw-semibold"><i class="bi bi-box-arrow-left me-2"></i> Log Out</a>
                </div>
            </div>

            <div class="col-lg-9">
                <div class="profile-header mb-4">
                    <h2>Account Settings</h2>
                    <p>Manage your personal information and preferences.</p>
                </div>

                <!-- Status Message -->
                <?php
                $success_message = ($statusType === 'success' && !empty($statusMessage)) ? $statusMessage : '';
                $error_message = ($statusType === 'danger' && !empty($statusMessage)) ? $statusMessage : '';
                $warning_message = ($statusType === 'warning' && !empty($statusMessage)) ? $statusMessage : '';
                $info_message = ($statusType === 'info' && !empty($statusMessage)) ? $statusMessage : '';
                include 'includes/notification.php';
                ?>

                <div class="card profile-card p-4 mb-4">
                    <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
                        <div class="d-flex align-items-center gap-3">
                            <div class="position-relative">
                                <div class="rounded-circle d-flex align-items-center justify-content-center" style="width: 80px; height: 80px; background: linear-gradient(135deg, #2ecc71 0%, #27a961 100%); color: white; font-size: 2rem; font-weight: 700;"><?= htmlspecialchars($initials) ?></div>
                            </div>
                            <div>
                                <h5 class="mb-1 fw-bold d-flex align-items-center gap-2">
                                    <?= htmlspecialchars($user['user_name']) ?>
                                    <?php if ($user['is_account_verified']): ?>
                                        <i class="bi bi-patch-check-fill text-primary" title="Verified Account" style="font-size: 1.2rem;"></i>
                                    <?php endif; ?>
                                </h5>
                                <p class="mb-2 text-muted small"><?= htmlspecialchars($user['email']) ?></p>
                                <div class="d-flex gap-2 flex-wrap">
                                    <span class="badge bg-success">Member since <?= $member_since ?></span>
                                    <?php if ($user['is_account_verified']): ?>
                                        <span class="badge bg-primary"><i class="bi bi-check-circle me-1"></i>Verified</span>
                                    <?php else: ?>
                                        <span class="badge bg-warning text-dark"><i class="bi bi-exclamation-circle me-1"></i>Unverified</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <form method="POST" action="">
                    <div class="card profile-card p-4 bg-white">
                        <div class="d-flex justify-content-between align-items-center mb-4 border-bottom pb-3">
                            <h5 class="fw-bold mb-0">Personal Information</h5>
                        </div>
                        <div class="row g-4">
                            <div class="col-md-12">
                                <label class="form-label small fw-semibold">Username</label>
                                <input type="text" name="user_name" class="form-control form-control-custom" value="<?= htmlspecialchars($user['user_name']) ?>" placeholder="Full Name" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold">Email Address</label>
                                <div class="input-group">
                                    <span class="input-group-text border-0 bg-light"><i class="bi bi-envelope text-muted"></i></span>
                                    <input type="email" name="email" class="form-control form-control-custom border-start-0" value="<?= htmlspecialchars($user['email']) ?>" placeholder="Email" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold">Phone Number</label>
                                <div class="input-group">
                                    <span class="input-group-text border-0 bg-light"><i class="bi bi-telephone text-muted"></i></span>
                                    <input type="text" name="phone_number" class="form-control form-control-custom border-start-0" value="<?= htmlspecialchars($user['phone_number'] ?? '') ?>" placeholder="Phone Number">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold">User ID</label>
                                <input type="text" class="form-control form-control-custom" value="#<?= htmlspecialchars($user['user_id']) ?>" readonly disabled>
                            </div>
                            <div class="col-md-12">
                                <label class="form-label small fw-semibold">Account Status</label>
                                <div class="d-flex align-items-center gap-3 p-3 rounded" style="background-color: <?= $user['is_account_verified'] ? '#d1f2eb' : '#fff3cd' ?>;">
                                    <?php if ($user['is_account_verified']): ?>
                                        <i class="bi bi-shield-check text-success" style="font-size: 2rem;"></i>
                                        <div class="flex-grow-1">
                                            <strong class="text-success">Account Verified</strong>
                                            <p class="mb-0 small text-muted">Your account is verified and you can purchase ebooks.</p>
                                        </div>
                                    <?php else: ?>
                                        <i class="bi bi-shield-exclamation text-warning" style="font-size: 2rem;"></i>
                                        <div class="flex-grow-1">
                                            <strong class="text-warning">Account Not Verified</strong>
                                            <p class="mb-0 small text-muted">Please verify your account to purchase ebooks. Click the button to request verification.</p>
                                        </div>
                                        <a href="request-verification.php" class="btn btn-primary btn-sm">
                                            <i class="bi bi-shield-check me-1"></i>Verify Now
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <div class="d-flex justify-content-between align-items-center mt-5 pt-4 border-top">
                            <a href="#" class="text-danger small fw-semibold text-decoration-none">Deactivate Account</a>
                            <div class="gap-2 d-flex">
                                <a href="profile.php" class="btn btn-outline-secondary">Cancel</a>
                                <button type="submit" name="update_profile" class="btn btn-green px-4">Save Changes</button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <?php include 'includes/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>