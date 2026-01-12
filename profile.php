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
    $user_name = mysqli_real_escape_string($conn, trim($_POST['user_name']));
    $email = mysqli_real_escape_string($conn, trim($_POST['email']));
    $phone_number = trim($_POST['phone_number']);

    // Validate email format
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $statusMessage = 'Please enter a valid email address.';
        $statusType = 'danger';
    } else {
        // Process phone number - remove spaces, dashes, and + sign
        $phone_number = preg_replace('/[\s\-\+]/', '', $phone_number);

        // Convert phone number starting with 0 to 63 format (09XXXXXXXXX -> 639XXXXXXXXX)
        if (!empty($phone_number) && preg_match('/^0\d{10}$/', $phone_number)) {
            $phone_number = '63' . substr($phone_number, 1);
        }

        // Validate phone number format if provided
        if (!empty($phone_number)) {
            // Check if it starts with 63 and has 12 digits total (639XXXXXXXXX)
            if (!preg_match('/^63\d{10}$/', $phone_number)) {
                $statusMessage = 'Invalid phone number format. Please use Philippine format: 639XXXXXXXXX, +63 9XX XXX XXXX, or 09XXXXXXXXX';
                $statusType = 'danger';
            } else {
                $phone_number_sql = "'$phone_number'";
            }
        } else {
            $phone_number_sql = 'NULL';
        }

        // Only proceed if no validation errors
        if (empty($statusMessage)) {
            // Check if username already exists for another user
            $check_username_query = "SELECT user_id FROM users WHERE user_name = '$user_name' AND user_id != $user_id";
            $check_username_result = executeQuery($check_username_query);

            // Check if email already exists for another user
            $check_email_query = "SELECT user_id FROM users WHERE email = '$email' AND user_id != $user_id";
            $check_email_result = executeQuery($check_email_query);

            // Check if phone number already exists for another user (only if not empty)
            if (!empty($phone_number)) {
                $check_phone_query = "SELECT user_id FROM users WHERE phone_number = '$phone_number' AND user_id != $user_id";
                $check_phone_result = executeQuery($check_phone_query);
            } else {
                $check_phone_result = false;
            }

            if (mysqli_num_rows($check_username_result) > 0) {
                $statusMessage = 'Username is already taken by another account.';
                $statusType = 'danger';
            } elseif (mysqli_num_rows($check_email_result) > 0) {
                $statusMessage = 'Email address is already in use by another account.';
                $statusType = 'danger';
            } elseif ($check_phone_result && mysqli_num_rows($check_phone_result) > 0) {
                $statusMessage = 'Phone number is already in use by another account.';
                $statusType = 'danger';
            } else {
                $update_query = "UPDATE users SET user_name = '$user_name', email = '$email', phone_number = $phone_number_sql WHERE user_id = $user_id";
                $result = executeQuery($update_query);

                if ($result) {
                    $_SESSION['user_name'] = $user_name;

                    $statusMessage = 'Profile updated successfully!';
                    $statusType = 'success';
                } else {
                    $statusMessage = 'Error updating profile: ' . mysqli_error($conn);
                    $statusType = 'danger';
                }
            }
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

$title = 'Account Settings';
$extraStyles = '<style>
    /* Ensure formActions visibility is controlled via the `.editing` class on the form */
    #profileForm #formActions {
        display: none !important;
    }

    #profileForm.editing #formActions {
        display: flex !important;
    }
</style>';
include 'includes/head.php';
?>

<body>
    <?php include 'includes/nav.php'; ?>

    <div class="container account-container py-4">
        <div class="row">
            <?php include 'includes/client-sidebar.php'; ?>

            <div class="col-lg-9">
                <div class="profile-header mb-4 text-center text-lg-start">
                    <h2 class="fw-bold">Account Settings</h2>
                    <p class="text-muted">Manage your personal information and preferences.</p>
                </div>

                <?php
                if (isset($_SESSION['error_message'])) {
                    $error_message = $_SESSION['error_message'];
                    unset($_SESSION['error_message']);
                } else {
                    $error_message = ($statusType === 'danger' && !empty($statusMessage)) ? $statusMessage : '';
                }
                $success_message = ($statusType === 'success' && !empty($statusMessage)) ? $statusMessage : '';
                $warning_message = ($statusType === 'warning' && !empty($statusMessage)) ? $statusMessage : '';
                $info_message = ($statusType === 'info' && !empty($statusMessage)) ? $statusMessage : '';
                include 'includes/notification.php';
                ?>

                <div class="card profile-card p-3 p-md-4 mb-4 border-0 shadow-sm">
                    <div class="d-flex align-items-center flex-column flex-sm-row gap-3 text-center text-sm-start">
                        <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0"
                            style="width: 80px; height: 80px; background: linear-gradient(135deg, #2ecc71 0%, #27a961 100%); color: white; font-size: 2rem; font-weight: 700;">
                            <?= htmlspecialchars($initials) ?>
                        </div>
                        <div class="flex-grow-1">
                            <h5 class="mb-1 fw-bold d-flex align-items-center justify-content-center justify-content-sm-start gap-2">
                                <?= htmlspecialchars($user['user_name']) ?>
                                <?php if ($user['is_account_verified']): ?>
                                    <i class="bi bi-patch-check-fill text-primary" title="Verified Account"></i>
                                <?php endif; ?>
                            </h5>
                            <p class="mb-2 text-muted small text-break"><?= htmlspecialchars($user['email']) ?></p>
                            <div class="d-flex gap-2 flex-wrap justify-content-center justify-content-sm-start">
                                <span class="badge bg-secondary">#<?= htmlspecialchars($user['user_id']) ?></span>
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

                <form method="POST" action="" id="profileForm">
                    <div class="card profile-card p-3 p-md-4 border-0 shadow-sm bg-white">
                        <div class="mb-4 border-bottom pb-3 d-flex justify-content-between align-items-center">
                            <h5 class="fw-bold mb-0">Personal Information</h5>
                            <button type="button" id="editBtn" class="btn btn-outline-primary btn-sm">
                                <i class="bi bi-pencil me-1"></i> Edit
                            </button>
                        </div>
                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label small fw-semibold">Username</label>
                                <input type="text" name="user_name" id="user_name" class="form-control form-control-custom" value="<?= htmlspecialchars($user['user_name']) ?>" required disabled>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold">Email Address</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light border-end-0"><i class="bi bi-envelope text-muted"></i></span>
                                    <input type="email" name="email" id="email" class="form-control form-control-custom" value="<?= htmlspecialchars($user['email']) ?>" placeholder="example@email.com" required disabled>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold">Phone Number</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light border-end-0"><i class="bi bi-telephone text-muted"></i></span>
                                    <input type="text" name="phone_number" id="phone_number" class="form-control form-control-custom" value="<?= htmlspecialchars($user['phone_number'] ?? '') ?>" placeholder="+63 9XX XXX XXXX" maxlength="17" disabled>
                                </div>
                            </div>

                            <div class="col-12">
                                <label class="form-label small fw-semibold">Account Status</label>
                                <div class="d-flex align-items-center flex-column flex-sm-row gap-3 p-3 rounded" style="background-color: <?= $user['is_account_verified'] ? '#d1f2eb' : '#fff3cd' ?>;">
                                    <?php if ($user['is_account_verified']): ?>
                                        <i class="bi bi-shield-check text-success fs-2"></i>
                                        <div class="text-center text-sm-start">
                                            <strong class="text-success d-block">Account Verified</strong>
                                            <p class="mb-0 small text-muted">Your account is verified and you can purchase ebooks.</p>
                                        </div>
                                    <?php else: ?>
                                        <i class="bi bi-shield-exclamation text-warning fs-2"></i>
                                        <div class="text-center text-sm-start flex-grow-1">
                                            <strong class="text-warning d-block">Account Not Verified</strong>
                                            <p class="mb-0 small text-muted">Please verify your account to purchase ebooks.</p>
                                        </div>
                                        <a href="request-verification.php" class="btn btn-primary btn-sm mt-2 mt-sm-0">Verify Now</a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <div id="formActions" class="d-flex flex-column flex-md-row justify-content-between align-items-center mt-5 pt-4 border-top gap-3" style="display: none;">
                            <div class="d-flex gap-2 w-100 w-md-auto order-1 order-md-2">
                                <button type="button" id="cancelBtn" class="btn btn-outline-secondary flex-grow-1">Cancel</button>
                                <button type="submit" name="update_profile" class="btn btn-green px-md-4 flex-grow-1">Save Changes</button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <?php include 'includes/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js" integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI" crossorigin="anonymous"></script>

    <script>
        // Profile Edit Mode Functionality
        const editBtn = document.getElementById('editBtn');
        const cancelBtn = document.getElementById('cancelBtn');
        const formActions = document.getElementById('formActions');
        const userNameInput = document.getElementById('user_name');
        const emailInput = document.getElementById('email');
        const phoneInput = document.getElementById('phone_number');

        // Store original values
        const originalValues = {
            user_name: userNameInput.value,
            email: emailInput.value,
            phone_number: phoneInput.value
        };

        // Format phone number for display (639XXXXXXXXX -> +63 9XX XXX XXXX)
        function formatPhoneDisplay(phone) {
            if (!phone || phone.trim() === '') return '';

            // Remove all non-digits
            let digits = phone.replace(/\D/g, '');

            // If it starts with 63 and has 12 digits
            if (digits.length === 12 && digits.startsWith('63')) {
                return '+63 ' + digits.substring(2, 5) + ' ' + digits.substring(5, 8) + ' ' + digits.substring(8);
            }

            return phone;
        }

        // Format phone number on page load
        if (phoneInput.value) {
            phoneInput.value = formatPhoneDisplay(phoneInput.value);
            originalValues.phone_number = phoneInput.value;
        }

        // Auto-format phone number as user types
        phoneInput.addEventListener('input', function(e) {
            let value = e.target.value;
            let cursorPosition = e.target.selectionStart;

            // Remove all non-digits except +
            let cleaned = value.replace(/[^\d+]/g, '');

            // If starts with +63 or 63
            if (cleaned.startsWith('+63')) {
                cleaned = cleaned.substring(3);
            } else if (cleaned.startsWith('63')) {
                cleaned = cleaned.substring(2);
            } else if (cleaned.startsWith('0')) {
                // Convert 09XXXXXXXXX to 9XXXXXXXXX
                cleaned = cleaned.substring(1);
            } else if (cleaned.startsWith('+')) {
                cleaned = cleaned.substring(1);
            }

            // Remove any remaining non-digits
            cleaned = cleaned.replace(/\D/g, '');

            // Limit to 10 digits (after 63)
            cleaned = cleaned.substring(0, 10);

            // Format as +63 9XX XXX XXXX
            let formatted = '';
            if (cleaned.length > 0) {
                formatted = '+63 ' + cleaned.substring(0, 3);
                if (cleaned.length > 3) {
                    formatted += ' ' + cleaned.substring(3, 6);
                }
                if (cleaned.length > 6) {
                    formatted += ' ' + cleaned.substring(6, 10);
                }
            }

            e.target.value = formatted;
        });

        // Validate email format
        function isValidEmail(email) {
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            return emailRegex.test(email);
        }

        // Validate phone number format
        function isValidPhone(phone) {
            if (!phone || phone.trim() === '') return true; // Empty is valid

            // Remove all non-digits
            let digits = phone.replace(/\D/g, '');

            // Should be 12 digits starting with 63
            return digits.length === 12 && digits.startsWith('63');
        }

        // Enable edit mode
        editBtn.addEventListener('click', function() {
            userNameInput.disabled = false;
            emailInput.disabled = false;
            phoneInput.disabled = false;

            // Use class toggle on the form to control visibility (more robust)
            profileForm.classList.add('editing');
            editBtn.style.display = 'none';

            // Focus on first field
            userNameInput.focus();
        });

        // Cancel edit mode
        cancelBtn.addEventListener('click', function() {
            // Restore original values
            userNameInput.value = originalValues.user_name;
            emailInput.value = originalValues.email;
            phoneInput.value = originalValues.phone_number;

            // Disable fields
            userNameInput.disabled = true;
            emailInput.disabled = true;
            phoneInput.disabled = true;

            // Toggle buttons and remove editing state
            editBtn.style.display = 'block';
            profileForm.classList.remove('editing');

            // Clear any validation messages
            emailInput.setCustomValidity('');
            phoneInput.setCustomValidity('');
        });

        // Form validation before submission
        const profileForm = document.getElementById('profileForm');
        profileForm.addEventListener('submit', function(e) {
            // Clear previous validation messages
            emailInput.setCustomValidity('');
            phoneInput.setCustomValidity('');

            // Validate email
            if (!isValidEmail(emailInput.value)) {
                e.preventDefault();
                emailInput.setCustomValidity('Please enter a valid email address.');
                emailInput.reportValidity();
                return false;
            }

            // Validate phone number
            if (!isValidPhone(phoneInput.value)) {
                e.preventDefault();
                phoneInput.setCustomValidity('Please enter a valid Philippine phone number (639XXXXXXXXX).');
                phoneInput.reportValidity();
                return false;
            }

            // Enable fields before form submission to ensure values are sent
            userNameInput.disabled = false;
            emailInput.disabled = false;
            phoneInput.disabled = false;
        });
    </script>
</body>

</html>